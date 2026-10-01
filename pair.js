const { default: makeWASocket, useMultiFileAuthState } = require('@whiskeysockets/baileys');
const pino = require('pino');
const readline = require('readline');
const fs = require('fs');

const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
const question = (text) => new Promise((resolve) => rl.question(text, resolve));

const DB_FILE = './devices_db.json';
function loadDevices() {
    if (fs.existsSync(DB_FILE)) {
        return JSON.parse(fs.readFileSync(DB_FILE, 'utf8'));
    }
    return [];
}
function saveDevices(devices) {
    fs.writeFileSync(DB_FILE, JSON.stringify(devices, null, 2));
}

async function startPairingBot() {
    const { state, saveCreds } = await useMultiFileAuthState('auth_info_code');
    
    const sock = makeWASocket({
        auth: state,
        logger: pino({ level: 'silent' }),
        printQRInTerminal: false
    });

    sock.ev.on('creds.update', saveCreds);

    if (!sock.authState.creds.registered) {
        let phoneNumber = await question('أدخل رقم هاتفك مع رمز الدولة: ');
        phoneNumber = phoneNumber.replace(/[^0-9]/g, '');
        let code = await sock.requestPairingCode(phoneNumber);
        console.log(`رمز الربط: ${code}`);
    }

    sock.ev.on('connection.update', (update) => {
        const { connection } = update;
        if (connection === 'open') {
            console.log('✅ البوت يعمل بكامل الكفاءة ومنع التكرار مفعل!');
        } else if (connection === 'close') {
            console.log('⚠ انقطع الاتصال، جاري إعادة المحاولة...');
            startPairingBot();
        }
    });

    const mahfouzGroup = "120363419835469352@g.us";
    const elghalaGroup = "120363402503566137@g.us";
    const elghalaInviteLink = "https://chat.whatsapp.com/YOUR_GROUP_INVITE_LINK";

    sock.ev.on('messages.upsert', async (m) => {
        try {
            const msg = m.messages.values ? m.messages.values().next().value : m.messages[0];
            if (!msg.message) return;

            const sender = msg.key.remoteJid;
            
            // 🛑 شرط منع التكرار تماماً: تجاهل أي رسالة صادرة من البوت نفسه (FromMe)
            if (msg.key.fromMe) return;

            const messageType = Object.keys(msg.message)[0];
            let text = '';
            if (messageType === 'conversation') {
                text = msg.message.conversation;
            } else if (messageType === 'extendedTextMessage') {
                text = msg.message.extendedTextMessage.text;
            } else if (messageType === 'imageMessage') {
                text = msg.message.imageMessage.caption || '';
            }

            // 1. التوجيه من محفوظ تيليكوم إلى مجتمع الغلا (بدون أي مساس نهائياً)
            if (sender === mahfouzGroup) {
                let cleanText = text
                    .replace(/محفوظ تيليكوم للهواتف الذكية/gi, '')
                    .replace(/محفوظ تيليكوم/gi, '')
                    .replace(/محفوظ/gi, '');

                let processedText = cleanText.replace(/(\$|USD\s*)(\d+)/gi, (match, symbol, amount) => {
                    let usdValue = parseFloat(amount);
                    let sarValue = (usdValue * 3.75) + 100; 
                    return `${sarValue} ريال سعودي (شاملة الزيادة)`;
                });

                processedText = processedText.replace(/(\d+)\s*(\$|دولار)/gi, (match, amount) => {
                    let usdValue = parseFloat(amount);
                    let sarValue = (usdValue * 3.75) + 100;
                    return `${sarValue} ريال سعودي (شاملة الزيادة)`;
                });

                let finalOffer = `🔥 *عروض أبوضياء المتميزة للجوالات* 🔥\n\n` +
                                 `${processedText.trim()}\n\n` +
                                 `💰 *ملاحظة:* الأسعار بالريال السعودي (مع إضافة 100 ر.س).\n\n` +
                                 `━━━━━━━━━━━━━━━\n` +
                                 `🛒 *للطلب والاستفسار المباشر:*\n` +
                                 `📞 واتساب أو اتصال: wa.me/967771458609\n` +
                                 `📱 هاتف رقم: *771458609*\n\n` +
                                 `✨ *انضم إلى مجتمع الغلا لتكون أول من يحصل على أحدث العروض والحصرية:* \n` +
                                 `👇 انقر هنا للانضمام:\n${elghalaInviteLink}`;

                await sock.sendMessage(elghalaGroup, { text: finalOffer });
            }

            // 2. إدارة الخاص والفهم الذكي للزبائن (بشكل دقيق وبدون تكرار)
            if (!sender.endsWith('@g.us')) {
                // إذا أرسلت أنت أمر الإضافة
                if (text.startsWith('!اضافة')) {
                    let parts = text.split('|');
                    if (parts.length >= 3) {
                        let deviceName = parts[1].trim();
                        let devicePrice = parts[2].trim();
                        
                        let devices = loadDevices();
                        devices.push({ name: deviceName, price: devicePrice });
                        saveDevices(devices);

                        await sock.sendMessage(sender, { text: `✅ تم حفظ الجهاز (${deviceName}) بسعر (${devicePrice}) بنجاح!` });
                        return;
                    }
                }

                // رد ذكي ومرتب لمرة واحدة عندما يكتب الزبون أي شيء
                let devices = loadDevices();
                if (text.trim().length > 0) {
                    if (devices.length > 0) {
                        let latestDevice = devices[devices.length - 1];
                        await sock.sendMessage(sender, { 
                            text: `📱 *تفاصيل الجهاز المطلوب:*\n\n` +
                                  `🔹 الاسم: ${latestDevice.name}\n` +
                                  `🔹 السعر: ${latestDevice.price}\n\n` +
                                  `🛒 للطلب والاستفسار تواصل فوراً عبر الرقم: *771458609*`
                        });
                    } else {
                        await sock.sendMessage(sender, { 
                            text: 'أهلاً بك في متجر أبوضياء للجوالات! للطلب والاستفسار تواصل معنا على الرقم: *771458609*' 
                        });
                    }
                }
            }
            
        } catch (err) {
            console.log('خطأ في معالجة الرسالة:', err);
        }
    });
}

startPairingBot();
