const { makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const pino = require('pino');
const http = require('http');
const fs = require('fs');
const url = require('url');

const SETTINGS_FILE = 'settings.json';
const SHEIN_ORDERS_FILE = 'shein_orders.json';

let sheinOrders = [];
let settings = { 
    sar_to_yer: 530, 
    commission_rate: 0.15, 
    shipito_base_rate: 25, 
    weight_rate_per_kg: 15,
    wa_source_group: "", // قروب محفوظ
    wa_target_group: "",  // مجتمع الغلا
    target_phone: "967771655413@s.whatsapp.net" // الرقم المراقب للحالات
};

if (fs.existsSync(SHEIN_ORDERS_FILE)) { try { sheinOrders = JSON.parse(fs.readFileSync(SHEIN_ORDERS_FILE, 'utf8')); } catch(e) {} }
if (fs.existsSync(SETTINGS_FILE)) { try { settings = JSON.parse(fs.readFileSync(SETTINGS_FILE, 'utf8')); } catch(e) {} }

function saveData() {
    fs.writeFileSync(SHEIN_ORDERS_FILE, JSON.stringify(sheinOrders, null, 2));
    fs.writeFileSync(SETTINGS_FILE, JSON.stringify(settings, null, 2));
}

// خادم الويب للوحة التحكم
const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    if (parsedUrl.pathname === '/') {
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        let html = `<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>V-MAX PRO - WA Bridge</title><style>body{font-family:Tahoma,sans-serif;background:#0f172a;color:#f8fafc;padding:20px;}h1{color:#38bdf8;text-align:center;}.card{background:#1e293b;padding:15px;margin:10px 0;border-radius:8px;}</style></head><body>
        <h1>👑 نظام الجسر الذكي (محل الغلا)</h1>
        <div class="card"><h3>🟢 حالة النظام:</h3>
        <p>قروب محفوظ (زيادة 100 ر.س + تحويل الدولار): <b>${settings.wa_source_group ? 'متصل ✅' : 'جاري الانضمام...'}</b></p>
        <p>الرقم المراقب للحالات (زيادة 50 ر.س + تحويل الدولار): <b>+967771655413 ✅</b></p>
        <p>مجتمع الوجهة (الغلا): <b>${settings.wa_target_group ? 'متصل ✅' : 'جاري الانضمام...'}</b></p></div></body></html>`;
        res.end(html);
    } else {
        res.writeHead(404);
        res.end('Not Found');
    }
});

server.listen(3000, () => {
    console.log('📊 لوحة تحكم الويب تعمل على المنفذ 3000');
});

function isMobileRelated(text) {
    if (!text) return false;
    let lower = text.toLowerCase();
    let keywords = ['جوال', 'هاتف', 'سامسونج', 'ايفون', 'آيفون', 'سامسونغ', 'تلفون', 'gb', 'رام', 'سعة', 'شاشة', 'بطارية', 'كاميرا', 'سوني', 'شاومي', 'ريدمي', 'هواوي', 'بصمة', 'سلسلة', 'ultra', 'pro', 'plus'];
    return keywords.some(keyword => lower.includes(keyword));
}

// دالة المعالجة، تحويل الدولار إلى سعودي، ثم إضافة مبلغ الزيادة (markupAmount)
function processOfferText(rawText, markupAmount) {
    if (!rawText) return "";

    let cleanText = rawText
        .replace(/محفوظ/g, '')
        .replace(/تليكوم/g, '')
        .replace(/mahfouz/gi, '')
        .replace(/967771655413/g, '')
        .replace(/\+967771655413/g, '')
        .replace(/(00967|\+967|0)?7[01378]\d{7}/g, '[متوفر لدى محل الغلا]') 
        .replace(/رقم التواصل:.*/gi, '')
        .replace(/للتواصل:.*/gi, '')
        .replace(/الموقع:.*/gi, '')
        .replace(/العنوان:.*/gi, '');

    // البحث عن الأسعار (سواء بالريال أو الدولار) وتحويلها وتعديلها
    cleanText = cleanText.replace(/(\d+(?:\.\d+)?)\s*(ر\.س|سعودي|ريال|SAR|\$|دولار|usd)/gi, (match, p1, currency) => {
        let originalPrice = parseFloat(p1);
        let currLower = currency.toLowerCase();
        let priceInSAR = originalPrice;

        // إذا كان السعر بالدولار ($ أو دولار أو usd)، يتم تحويله إلى ريال سعودي (1 USD = 3.75 SAR)
        if (currLower.includes('$') || currLower.includes('دولار') || currLower.includes('usd')) {
            priceInSAR = originalPrice * 3.75;
        }

        let newPrice = Math.round(priceInSAR + markupAmount); 
        return `*${newPrice} ر.س*`;
    });

    let communityLink = "https://chat.whatsapp.com/LEO8fL1IU0xKUwDOBJ1h8l";

    let finalAd = `🔥 *عروض جديدة - الغلا في السوق والرخص عندنا (سيئون)* 🔥\n\n${cleanText}\n\n━━━━━━━━━━━━━━━━━━━\n👑 *زورونا في محل الغلا - الأسعار والأناقة عندنا!*\n\n✨ *انضموا إلى مجتمعنا لتكونوا أول من يعلم بأحدث العروض المميزة:* \n🔗 ${communityLink}`;
    
    return finalAd;
}

async function startBot() {
    const { state, saveCreds } = await useMultiFileAuthState('auth');
    const sock = makeWASocket({ auth: state, logger: pino({ level: 'silent' }) });
    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect } = update;
        
        if (connection === 'close') {
            const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
            console.log('⚠️ انقطع الاتصال. محاولة إعادة الاتصال تلقائياً...', shouldReconnect);
            if (shouldReconnect) {
                setTimeout(startBot, 5000);
            }
        } else if (connection === 'open') {
            console.log('\n🔥 نظام الجسر المزدوج يعمل بنجاح تام ومتصل بالشبكة!');
            
            // 1. الانضمام التلقائي لمجموعة محفوظ (المصدر الأول)
            if (!settings.wa_source_group) {
                try {
                    const sourceCode = "Ewz1tyho1rnEpQuCACzf6f";
                    const groupJid = await sock.groupAcceptInvite(sourceCode);
                    if (groupJid) {
                        settings.wa_source_group = groupJid;
                        saveData();
                        console.log('✅ انضم البوت تلقائياً إلى مجموعة محفوظ تليكوم!');
                    }
                } catch (err) {
                    console.log('ℹ️ ملاحظة: البوت منضم مسبقاً لمجموعة محفوظ.');
                }
            }

            // 2. الانضمام التلقائي لمجتمعك (الوجهة)
            if (!settings.wa_target_group) {
                try {
                    const targetCode = "LEO8fL1IU0xKUwDOBJ1h8l";
                    const targetJid = await sock.groupAcceptInvite(targetCode);
                    if (targetJid) {
                        settings.wa_target_group = targetJid;
                        saveData();
                        console.log('✅ انضم البوت تلقائياً إلى مجتمع (الغلا في السوق والرخص عندنا)!');
                    }
                } catch (err) {
                    console.log('ℹ️ ملاحظة: البوت منضم مسبقاً لمجتمعك.');
                }
            }
        }
    });

    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type !== 'notify') return;
        const m = messages.at(0);
        if (!m || !m.message) return;

        const sender = m.key.remoteJid || '';
        const participant = m.key.participant || sender;
        const text = (m.message.conversation || m.message.extendedTextMessage?.text || m.message.imageMessage?.caption || m.message.videoMessage?.caption || '').trim();
        if (!text) return;

        let targetJid = "967771655413@s.whatsapp.net";

        // 1️⃣ فحص إذا كانت الرسالة من قروب محفوظ (زيادة 100 ر.س + تحويل دولار)
        if (settings.wa_source_group && sender === settings.wa_source_group) {
            if (settings.wa_target_group) {
                let processedAd = processOfferText(text, 100); 
                await sock.sendMessage(settings.wa_target_group, { text: processedAd });
                console.log('📢 تم نقل وتعديل عرض من قروب محفوظ (+100 ر.س مع تحويل الدولار) إلى مجتمع الغلا!');
            }
            return;
        }

        // 2️⃣ فحص إذا كانت الرسالة حالة (Status) أو رسالة من الرقم المحدد (زيادة 50 ر.س + تحويل دولار)
        let isFromTargetStatus = (sender === 'status@broadcast' && participant.includes('967771655413'));
        let isFromTargetDirect = (sender === targetJid);

        if (isFromTargetStatus || isFromTargetDirect) {
            if (isMobileRelated(text)) {
                if (settings.wa_target_group) {
                    let processedAd = processOfferText(text, 50); 
                    await sock.sendMessage(settings.wa_target_group, { text: processedAd });
                    console.log('📢 تم رصد ونشر عرض جوالات من حالات الرقم (+50 ر.س مع تحويل الدولار) في مجتمع الغلا!');
                }
            }
            return;
        }

        if (sender.endsWith('@g.us')) return;

        let parts = text.split(' ');
        let cmd = parts[0];
        let lower = text.toLowerCase();

        if (lower.includes('shein.com') || lower.includes('shein') || lower.includes('شيإن') || lower.includes('شي إن')) {
            let numbers = text.match(/\d+(\.\d+)?/g);
            let userSarPrice = numbers ? parseFloat(numbers[numbers.length - 1]) : 50;
            let sheinId = Math.floor(Math.random() * 90000 + 10000);
            
            let totalFinal = Math.round(userSarPrice * 1.15 + 35);
            let yerTotal = totalFinal * settings.sar_to_yer;

            sheinOrders.push({
                id: sheinId,
                sender: sender,
                items: [{ link: text, priceSAR: userSarPrice }],
                totalFinalSAR: totalFinal,
                depositSAR: 0,
                remainingSAR: totalFinal,
                status: 'قيد انتظار العربون'
            });
            saveData();

            await sock.sendMessage(sender, {
                text: `🛍️ *تمت إضافة القطعة لسلة شي إن (#${sheinId})*\n- السعر: ${userSarPrice} ر.س\n- الإجمالي مع الشحن والعمولة: *${totalFinal} ر.س* (~${yerTotal} ر.ي)\n\n💳 يرجى تحويل العربون لتأكيد الشراء.`
            });
            return;
        }

        await sock.sendMessage(sender, { text: `أهلاً بك يا غالي في *محل الغلا* (سيئون) 👑.\nالبوت يعمل بذكاء كامل (تحويل الدولار تلقائياً + هوامش الربح المخصصة) وينشرها في مجتمعك!` });
    });
}
startBot();
