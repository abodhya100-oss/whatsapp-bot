const { makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const pino = require('pino');

async function startBot() {
    const { state, saveCreds } = await useMultiFileAuthState('auth');
    const sock = makeWASocket({ auth: state, logger: pino({ level: 'silent' }) });
    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
        if (update.connection === 'open') {
            console.log('🔥 البوت متصل وجاهز للاستماع للرسائل...');
        }
    });

    sock.ev.on('messages.upsert', async (m) => {
        const msg = m.messages[0];
        if (!msg || !msg.message) return;
        
        // طباعة تفاصيل الرسالة الواردة في التيرمينال لنراها
        console.log("📥 رسالة واردة:", JSON.stringify(msg.message, null, 2));

        if (msg.key.fromMe) return; // تجاهل رسائلك المرسلة من هاتفك

        const sender = msg.key.remoteJid;
        const text = msg.message.conversation || msg.message.extendedTextMessage?.text || '';

        if (text) {
            console.log(`💬 تم استلام نص: "${text}" من ${sender}`);
            await sock.sendMessage(sender, { text: `✅ رد تجريبي من البوت: أرسلتَ ( ${text} )` });
        }
    });
}

startBot();
