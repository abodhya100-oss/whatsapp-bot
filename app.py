import os
import re
import sqlite3
from flask import Flask, request, jsonify
from PIL import Image, ImageDraw, ImageFont

app = Flask(__name__)

# إعداد قاعدة البيانات المحلية SQLite
DB_FILE = "store_database.db"

def init_db():
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS phones (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            specs TEXT,
            price REAL,
            image_path TEXT,
            date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    conn.commit()
    conn.close()

# تهيئة قاعدة البيانات عند بدء التشغيل
init_db()

# دالة إضافة العلامة المائية على الصور
def add_watermark(input_image_path, output_image_path):
    try:
        image = Image.open(input_image_path).convert("RGBA")
        txt_overlay = Image.new("RGBA", image.size, (255, 255, 255, 0))
        draw = ImageDraw.Draw(txt_overlay)
        text = "متجر الغلا | 771458609"
        
        width, height = image.size
        # رسم مستطيل أسود شفاف أسفل الصورة لحماية وضوح النص
        draw.rectangle([0, height - 60, width, height], fill=(0, 0, 0, 180))
        
        # كتابة النص على الصورة
        draw.text((30, height - 45), text, fill=(255, 255, 255, 255))
        
        watermarked = Image.alpha_composite(image, txt_overlay)
        watermarked.convert("RGB").save(output_image_path, "JPEG")
        return output_image_path
    except Exception as e:
        print(f"خطأ أثناء إضافة العلامة المائية: {e}")
        return input_image_path

# دالة استخراج السعر وزيادة 100 ريال
def adjust_offer_price(text):
    match = re.search(r'(\d+)\s*(ريال|رس|SAR|\$)', text)
    if match:
        original_price = float(match.group(1))
        new_price = original_price + 100
        # استبدال السعر القديم بالسعر الجديد في النص
        updated_text = text.replace(match.group(0), f"{new_price} ريال سعودي")
        return updated_text, new_price
    return text, None

# نقطة استقبال الرسائل (Webhook) من واتساب
@app.route('/webhook', methods=['POST'])
def webhook():
    data = request.json
    try:
        message_text = data.get("message", "")
        sender_phone = data.get("sender", "")
        admin_phone = "967771458609" # رقم المدير المعتمد

        # 1. إذا كان المرسل هو المدير (إضافة أو حفظ جهاز جديد)
        if sender_phone == admin_phone and ("حفظ" in message_text or "جديد" in message_text):
            name = data.get("phone_name", "جوال مميز")
            specs = data.get("specs", "ذاكرة قوية / ضمان شامل")
            price = float(data.get("price", 0))
            image_path = data.get("image_path", "")
            
            # معالجة الصورة وإضافة العلامة المائية فوراً
            if image_path and os.path.exists(image_path):
                watermarked_path = f"static/watermarked_{os.path.basename(image_path)}"
                os.makedirs("static", exist_ok=True)
                add_watermark(image_path, watermarked_path)
                image_path = watermarked_path

            # تخزين البيانات في قاعدة البيانات
            conn = sqlite3.connect(DB_FILE)
            cursor = conn.cursor()
            cursor.execute("INSERT INTO phones (name, specs, price, image_path) VALUES (?, ?, ?, ?)",
                           (name, specs, price, image_path))
            conn.commit()
            conn.close()
            return jsonify({"status": "success", "message": "تم حفظ وتحديث تفاصيل الجوال وصورته بنجاح!"})

        # 2. إذا كان عميل يسأل عن سعر أو جهاز معين
        if any(keyword in message_text for keyword in ["سعر", "عندكم", "توفر", "جهاز", "كم"]):
            conn = sqlite3.connect(DB_FILE)
            cursor = conn.cursor()
            cursor.execute("SELECT name, specs, price, image_path FROM phones ORDER BY id DESC LIMIT 1")
            row = cursor.fetchone()
            conn.close()

            if row:
                name, specs, price, img = row
                # صياغة رد أنيق يفتح النفس للعميل
                response_msg = (
                    f"✨ *عروض متجر الغلا المميزة* ✨\n"
                    f"-----------------------------------\n"
                    f"📱 *اسم الجهاز:* {name}\n"
                    f"⚙️ *المواصفات:* {specs}\n"
                    f"💰 *السعر الحالي:* *{price} ريال سعودي*\n"
                    f"-----------------------------------\n"
                    f"📞 للطلب والاستفسار: 771458609\n"
                    f"📍 متوفر الآن في المتجر - اطلبه قبل نفاد الكمية!"
                )
                return jsonify({"reply": response_msg, "image": img})
            else:
                return jsonify({"reply": "عذراً، لا توجد عروض مسجلة حالياً. تابعنا لكل جديد!"})

        # 3. معالجة عروض الجوالات الواردة وسحبها تلقائياً مع زيادة 100 ريال
        updated_text, new_price = adjust_offer_price(message_text)
        if new_price:
            forward_msg = (
                f"🔥 *عرض جديد من متجر الغلا* 🔥\n\n"
                f"{updated_text}\n\n"
                f"📞 للطلب: 771458609"
            )
            return jsonify({"action": "forward_to_group", "formatted_message": forward_msg})

        return jsonify({"status": "received"})

    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
