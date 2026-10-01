#!/bin/bash
pkill -f php
mkdir -p tmp sessions
chmod -R 777 tmp sessions

# إنشاء ملف إعدادات محلي يعزل PHP تماماً عن مجلدات النظام
cat << 'INEOF' > custom_php.ini
opcache.enable=0
session.save_path = "./sessions"
sys_temp_dir = "./tmp"
INEOF

echo "=== تشغيل سيرفر PHP بنجاح تام ==="
php -c custom_php.ini -S localhost:8000
