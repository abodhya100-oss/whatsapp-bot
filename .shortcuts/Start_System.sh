#!/bin/bash
termux-wake-lock

# تشغيل السيرفر إذا لم يكن يعمل
if ! pgrep -x "php" > /dev/null; then
    cd /data/data/com.termux/files/home/accounting_project
    /data/data/com.termux/files/usr/bin/php -d opcache.enable=0 -d sys_temp_dir="./tmp" -d upload_temp_dir="./tmp" -d session.save_path="./sessions" -S localhost:8080 > /dev/null 2>&1 &
    sleep 1
fi

# فتح المتصفح تلقائياً على النظام
am start -a android.intent.action.VIEW -d "http://localhost:8080"
exit
