<?php                                         
// settings.php - إعدادات النظام              
require_once 'auth.php';                      
require_admin();                                                                            

$db_file = '/data/data/com.termux/files/home/secure_dbs/vmax.db';              
$db = new SQLite3($db_file);                                                                

// إنشاء جدول الإعدادات إذا لم يكن موجوداً
$db->exec("CREATE TABLE IF NOT EXISTS settings (setting_key TEXT PRIMARY KEY, setting_value TEXT)");                                                                                    

if ($_SERVER['REQUEST_METHOD'] === 'POST') {      
    $fields = ['store_name', 'store_desc', 'phone', 'address', 'commercial_reg', 'notes_text'];                                               
    foreach ($fields as $f) {                         
        $val = trim($_POST[$f] ?? '');                
        // التحقق مما إذا كان المفتاح موجوداً مسبقاً                                                  
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = :key");                                                     
        $stmt_check->bindValue(':key', $f, SQLITE3_TEXT);                                           
        $res_check = $stmt_check->execute();          
        $row_check = $res_check->fetchArray(SQLITE3_NUM);                                                                                         
        if ($row_check[0] > 0) {                          
            $stmt = $db->prepare("UPDATE settings SET setting_value = :val WHERE setting_key = :key");                                            
        } else {                                          
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :val)");                                        
        }                                             
        $stmt->bindValue(':key', $f, SQLITE3_TEXT);                                                 
        $stmt->bindValue(':val', $val, SQLITE3_TEXT);                                               
        $stmt->execute();                         
    }                                             
    echo "<script>alert('✅ تم حفظ الإعدادات بنجاح'); window.location='settings.php';</script>";                                              
    exit;                                     
}                                                                                           

// القيم الافتراضية                           
$config = [                                       
    'store_name' => 'مؤسسة أبو ضياء للتوزيع',     
    'store_desc' => 'التوزيع والتوكيل التجاري العام',                                           
    'phone' => '771458609',                       
    'address' => 'سيئون - السوق العام',           
    'commercial_reg' => '771458609',              
    'notes_text' => 'شكراً لتعاملكم معنا'      
];                                                                                          

$res = @$db->query("SELECT * FROM settings"); 
if ($res) {                                       
    while ($row = @$res->fetchArray(SQLITE3_ASSOC)) {                                               
        if (!empty($row['setting_key'])) {                
            $config[$row['setting_key']] = $row['setting_value'];                                   
        }                                         
    }                                         
}                                             
$db->close();                                 
?>                                            
<!DOCTYPE html>                               
<html lang="ar" dir="rtl">                    
<head>                                        
    <meta charset="UTF-8">                        
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
    <meta name='mobile-web-app-capable' content='yes'>                           
    <meta name='apple-mobile-web-app-capable' content='yes'>                                                                                      
    <title>إعدادات النظام</title>                                                
    <style>                                           
        * { box-sizing: border-box; }                 
        body { 
            font-family: 'Tahoma', Arial, sans-serif;                                                   
            background-color: #f1f5f9;                    
            margin: 0; padding: 15px;                        
            padding-bottom: 90px;
            direction: rtl; 
        }                 
        
        .main-container { 
            max-width: 500px; 
            margin: 0 auto; 
            background: #FFFFFF; 
            padding: 25px 20px; 
            border-radius: 14px; 
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02); 
        }                                    
        
        .header { 
            text-align: center; 
            margin-bottom: 25px; 
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 15px;
        }                 
        
        .header h2 { 
            margin: 0; 
            font-size: 18px; 
            color: #0f172a; 
            font-weight: 800;
        }                                  
        
        .form-group { margin-bottom: 16px; }          
        
        label { 
            display: block; 
            font-size: 12.5px; 
            font-weight: 700; 
            color: #475569; 
            margin-bottom: 6px; 
        }                                       
        
        .form-control { 
            width: 100%; 
            padding: 12px 14px; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            font-size: 13.5px; 
            font-family: inherit;
            background-color: #f8fafc;
            color: #1e293b;
            transition: all 0.2s ease;
        }                                                    
        
        .form-control:focus { 
            border-color: #0284c7; 
            background-color: #ffffff;
            outline: none; 
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }                               
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .btn-save { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #0284c7, #0369a1); 
            color: #FFF; 
            border: none; 
            border-radius: 8px; 
            font-weight: bold; 
            font-size: 14px; 
            cursor: pointer; 
            margin-top: 15px; 
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
            transition: all 0.2s ease;
        }                                                  
        
        .btn-save:active { transform: scale(0.98); }                                                
        
        /* شريط التنقل السفلي الاحترافي - مطابق لصفحة العملاء */
        .bottom-nav {                                     
            position: fixed; bottom: 0; left: 0; right: 0;                                              
            background: rgba(255, 255, 255, 0.95);                                                      
            backdrop-filter: blur(10px);                  
            display: flex; justify-content: space-around; align-items: center;                          
            padding: 10px 0; border-top-left-radius: 20px; border-top-right-radius: 20px;               
            box-shadow: 0 -4px 20px rgba(0,0,0,0.06); z-index: 1000;                                    
            border-top: 1px solid #e2e8f0;            
        }                                             
        
        .bottom-nav a {                                   
            text-decoration: none; color: #64748b; font-size: 11px;                                     
            text-align: center; font-weight: bold; flex: 1; display: flex;                              
            flex-direction: column; align-items: center; gap: 3px;                                  
        }                                             
        
        .bottom-nav a.active { color: #0284c7; }                                                    
        .bottom-nav a span { font-size: 20px; }  
    </style>                                  
</head>                                       
<body>                                                                                      
    <div style="display: flex; gap: 5px;">
    </div>
    <div style="display: flex; gap: 4px; align-items: center;">
        <span style="color: #94a3b8; font-size: 10px; font-weight: bold;">تكبير الشاشة:</span>
    </div>
</div>
</div>
    <div class="main-container">                      
        <div class="header">                              
            <h2>⚙️ إعدادات النظام العامة</h2>          
        </div>                                                                                      
        
        <form method="POST">                              
            <div class="form-group">                          
                <label>اسم المنشأة / المتجر:</label>                                                        
                <input type="text" name="store_name" class="form-control" value="<?php echo htmlspecialchars($config['store_name']); ?>">             
            </div>                                        
            
            <div class="form-group">                          
                <label>وصف النشاط:</label>                    
                <input type="text" name="store_desc" class="form-control" value="<?php echo htmlspecialchars($config['store_desc']); ?>">             
            </div>                                        
            
            <div class="form-group">                          
                <label>رقم الجوال:</label>                    
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($config['phone']); ?>">                       
            </div>                                        
            
            <div class="form-group">                          
                <label>العنوان:</label>                       
                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($config['address']); ?>">                   
            </div>                                        
            
            <div class="form-group">                          
                <label>السجل التجاري / الرقم الضريبي:</label>                                               
                <input type="text" name="commercial_reg" class="form-control" value="<?php echo htmlspecialchars($config['commercial_reg']); ?>">                                                   
            </div>                                        
            
            <div class="form-group">                          
                <label>النص الافتراضي / الملاحظات:</label>                                                  
                <textarea name="notes_text" class="form-control"><?php echo htmlspecialchars($config['notes_text']); ?></textarea>           
            </div>                                                                                      
            
            <button type="submit" class="btn-save">💾 حفظ الإعدادات</button>                        
        </form>                                   
    </div>                                                                                      
    
    <div class="bottom-nav">                          
        <a href="pos.php"><span>🛒</span>البيع</a>                                
        <a href="inventory.php"><span>📦</span>المخزن</a>                          
        <a href="debts.php"><span>📒</span>الديون</a>                              
        <a href="daily_report.php"><span>📊</span>التقرير</a>                      
        <a href="dashboard.php" class="active"><span>🏠</span>الرئيسية</a>             
    </div>                                                                                      
    
</body>                                       
</html>
