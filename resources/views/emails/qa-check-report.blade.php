<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; border-left: 5px solid #007bff; }
        .footer { margin-top: 30px; font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>📊 Ежедневный отчет: Устройства на проверке ОТК</h2>
        <p><strong>Дата формирования:</strong> {{ date('d.m.Y H:i') }}</p>
    </div>
    
    <p>Добрый день!</p>
    <p>Во вложении находится Excel-файл со списком всех устройств, которые на текущий момент находятся в статусе <strong>"На проверке ОТК"</strong>.</p>
    
    <div class="footer">
        <p>Это автоматическое сообщение. Пожалуйста, не отвечайте на него.</p>
        <p>С уважением,<br>Система управления ремонтами</p>
    </div>
</body>
</html>