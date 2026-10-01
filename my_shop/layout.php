<?php
// قالب التصميم الجذري الخيالي - V-MAX PRO
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'V-MAX PRO - النظام الفاخر'; ?></title>
    <style>
        :root {
            --bg-deep: #030712;
            --glass-bg: rgba(15, 23, 42, 0.75);
            --glass-border: rgba(56, 189, 248, 0.25);
            --neon-glow: 0 0 20px rgba(56, 189, 248, 0.15);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-cyan: #38bdf8;
            --accent-green: #10b981;
            --accent-gold: #f59e0b;
        }
        body {
            font-family: Tahoma, sans-serif;
            background-color: var(--bg-deep);
            background-image: 
                radial-gradient(at 0% 0%, rgba(30, 58, 138, 0.3) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(15, 23, 42, 0.8) 0px, transparent 50%);
            color: var(--text-main);
            margin: 0;
            padding: 14px;
            padding-bottom: 150px;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .main-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .app-header {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95));
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 16px;
            text-align: center;
            font-size: 17px;
            font-weight: 900;
            color: var(--accent-cyan);
            margin-bottom: 16px;
            box-shadow: var(--neon-glow);
            letter-spacing: 0.5px;
        }
        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .form-control {
            width: 100%;
            padding: 13px 15px;
            background: rgba(3, 7, 18, 0.85);
            color: #fff;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            box-sizing: border-box;
            margin-bottom: 12px;
            font-size: 13px;
            outline: none;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.3);
        }
        .btn-main {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: bold;
            width: 100%;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(2, 132, 199, 0.4);
            transition: all 0.2s ease;
        }
        .btn-main:active { transform: scale(0.98); }
        
        /* شريط التنقل السفلي الفاخر والمتباعد بانتظام تام */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(3, 7, 18, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 8px 5px;
            z-index: 9999;
            box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.8);
        }
        .nav-item {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 10px;
            text-align: center;
            flex: 1;
            padding: 6px 2px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: bold;
        }
        .nav-item span {
            font-size: 18px;
            display: block;
            margin-bottom: 3px;
        }
        .nav-item:hover, .nav-item.active {
            color: var(--accent-cyan);
            background: rgba(56, 189, 248, 0.1);
            text-shadow: 0 0 10px rgba(56, 189, 248, 0.6);
        }
        
        .table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            margin-top: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            white-space: nowrap;
        }
        .table th, .table td {
            padding: 11px;
            text-align: center;
        }
        .table th {
            background: rgba(15, 23, 42, 0.95);
            color: var(--accent-cyan);
            font-weight: bold;
            border-bottom: 1px solid var(--glass-border);
        }
        .table td {
            border-bottom: 1px solid rgba(30, 41, 59, 0.6);
        }
    </style>
</head>
<body>
<div class="main-container">
