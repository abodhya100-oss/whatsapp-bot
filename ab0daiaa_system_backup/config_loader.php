<?php
// ملف مركزي لتحميل إعدادات النظام وتطبيقها في جميع الصفحات
function getSystemSetting($key, $default = '') {
    $dbFile = __DIR__ . '/vmax.db';
    if(!file_exists($dbFile)) { $dbFile = __DIR__ . '/database.db'; }
    if(file_exists($dbFile)) {
        try {
            $db = new SQLite3($dbFile);
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
            if($stmt) {
                $stmt->bindValue(':key', $key, SQLITE3_TEXT);
                $result = $stmt->execute();
                if($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    return $row['setting_value'];
                }
            }
        } catch(Exception $e) {
            // تجاهل الأخطاء بصمت
        }
    }
    
    // القيم الافتراضية المعتمدة لمؤسسة أبو ضياء للتوزيع
    $defaults = [
        'store_name' => 'مؤسسة أبو ضياء للتوزيع',
        'store_desc' => 'التوزيع والتوكيل التجاري العام',
        'phone' => '771458609',
        'address' => 'سيئون - السوق العام',
        'commercial_reg' => '771458609',
        'notes_text' => 'شكراً لتعاملكم معنا'
    ];
    
    return $defaults[$key] ?? $default;
}
?>
