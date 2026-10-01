from http.server import BaseHTTPRequestHandler, HTTPServer
import json

PORT = 8080

class BotHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        # إذا فتحت الرابط من المتصفح يظهر لك رسالة جاهزة
        self.send_response(200)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        self.end_headers()
        self.wfile.write("<h3>سيرفر بوت أبوضياء للجوالات يعمل بنجاح ✅</h3>".encode('utf-8'))

    def do_POST(self):
        # لمعالجة رسائل الرد الآلي الواردة
        content_length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(content_length)
        
        try:
            data = json.loads(post_data.decode('utf-8'))
            incoming_msg = data.get('message', '').lower()
            sender = data.get('sender', '')
            
            print(f"[*] رسالة واردة من {sender}: {incoming_msg}")
            
            # تحديد الرد الآلي حسب محتوى الرسالة
            if 'سعر' in incoming_msg or 'جوال' in incoming_msg or 'كم' in incoming_msg:
                reply_text = "أهلاً بك في متجر أبوضياء! أسعار الجوالات لدينا تنافسية ومحدثة. اطلب اسم الجهاز لنوافيك بالسعر والتفاصيل."
            else:
                reply_text = "أهلاً بك! تم استلام رسالتك، وسيتم خدمتك في أقرب وقت."

            response = {"reply": reply_text}
            
            self.send_response(200)
            self.send_header('Content-Type', 'application/json; charset=utf-8')
            self.end_headers()
            self.wfile.write(json.dumps(response, ensure_ascii=False).encode('utf-8'))
            
        except Exception as e:
            print(f"[!] خطأ: {e}")
            self.send_response(500)
            self.end_headers()

def run_server():
    server_address = ('127.0.0.1', PORT)
    httpd = HTTPServer(server_address, BotHandler)
    print(f"[*] السيرفر يعمل الآن على البورت {PORT}...")
    httpd.serve_forever()

if __name__ == '__main__':
    run_server()
