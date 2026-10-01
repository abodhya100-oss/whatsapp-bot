<?php
// ملف مستقل لعرض قائمة العملات المنسدلة - نظام أبوضياء العالمي
$currencies_data = include 'currencies.php';
$default_currency = $currencies_data['default'];
$currencies = $currencies_data['currencies'];
?>
<div class="currency-selector-box" style="margin-bottom: 15px;">
    <label style="font-size: 11px; color: #94a3b8; display: block; margin-bottom: 3px;">العملة:</label>
    <select name="currency_code" style="width: 100%; padding: 9px; background: rgba(15, 23, 42, 0.9); border: 1px solid rgba(56, 189, 248, 0.3); color: #fff; border-radius: 8px; font-size: 12px; box-sizing: border-box;">
        <?php foreach($currencies as $code => $curr): ?>
            <option value="<?php echo $code; ?>" <?php echo ($code === $default_currency) ? 'selected' : ''; ?>>
                <?php echo $curr['name'] . ' (' . $curr['symbol'] . ')'; ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
