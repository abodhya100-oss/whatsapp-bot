#!/bin/bash
echo "🚀 جاري تشغيل بوت متجر الغلا..."
pm2 start index.js --name "hotphone-bot"
termux-wake-lock
echo "✅ تم التشغيل بنجاح! جاري عرض السجل (Logs):"
pm2 logs hotphone-bot
