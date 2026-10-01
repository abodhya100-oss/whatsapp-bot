import shutil
import os
import datetime

db_file = 'vmax.db'
backup_dir = 'backups'

if not os.path.exists(backup_dir):
    os.makedirs(backup_dir)

if os.path.exists(db_file):
    timestamp = datetime.datetime.now().strftime('%Y-%m-%d_%H-%M-%S')
    backup_path = os.path.join(backup_dir, f'vmax_backup_{timestamp}.db')
    shutil.copy(db_file, backup_path)
    print(f"✅ تم حفظ نسخة احتياطية جديدة بنجاح: {backup_path}")
else:
    print("⚠️ ملف قاعدة البيانات غير موجود!")
