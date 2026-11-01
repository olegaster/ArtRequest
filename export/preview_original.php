<?php
// Определяем константу
define('MODX_API_MODE', true);
// подключаем index.php
require $_SERVER['DOCUMENT_ROOT'].'/index.php';	
	
// Включаем обработку ошибок
$modx->getService('error','error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');	

// УВЕЛИЧЕННЫЕ ЛИМИТЫ ДЛЯ БОЛЬШИХ ИЗОБРАЖЕНИЙ (54+ МБ)
ini_set('memory_limit', '1024M');  // 1 ГБ для обработки больших JPEG
ini_set('max_execution_time', '300');  // 5 минут для phpthumbof
set_time_limit(300);

// Логирование ошибок
error_reporting(E_ALL);
ini_set('display_errors', 0);  // НЕ показываем ошибки на странице
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/export_errors.log');

/**
 * Страница предпросмотра заявок с пагинацией
 * Версия 2.3 - Добавлены миниатюры 200px через phpThumbOf и Magnific Popup для просмотра
 */

// Путь к лог-файлу от корня сайта
$logFilePath = '../core/components/artrequest/orders.log';

// Базовый URL сайта (измените на свой)
$siteUrl = 'https://art.fondsci.localhost';

// Путь к phpThumbOf (для генерации миниатюр)
//$phpThumbOfPath = '../core/components/phpthumbof/elements/snippets/phpthumbof.snippet.php';

// Настройки пагинации
$perPageOptions = array(25, 50, 100);
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
if (!in_array($perPage, $perPageOptions)) {
    $perPage = 25;
}

$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

/**
 * Генерация миниатюры через phpThumbOf
 * @param string $imagePath - путь к изображению от корня сайта (например /uploads/works_up/professionals/file.jpg)
 * @param int $width - ширина миниатюры
 * @param int $height - высота миниатюры (0 = пропорционально)
 * @param string $zc - режим обрезки (C = crop центр, 1 = crop, 0 = resize без crop)
 * @return string - путь к миниатюре или оригинальное изображение
 */
// Функция для парсинга print_r() формата
function parsePrintRLog($filePath) {
    if (!file_exists($filePath)) {
        return array('error' => 'Файл orders.log не найден');
    }
    
    $content = file_get_contents($filePath);
    if ($content === false) {
        return array('error' => 'Ошибка чтения файла');
    }
    
    $orders = array();
    $entries = preg_split('/(?=^Array\s*\()/m', $content, -1, PREG_SPLIT_NO_EMPTY); // FIXED
    
    foreach ($entries as $entry) {
        if (empty(trim($entry))) {
            continue;
        }



        $order = array();
        
        if (preg_match('/\[time\]\s*=>\s*(.+)$/m', $entry, $matches)) {
            $order['time'] = trim($matches[1]);
            $order['created'] = strtotime($order['time']);
        }
        
        if (preg_match('/\[clientemail\]\s*=>\s*(.+)$/m', $entry, $matches)) {
            $order['clientemail'] = trim($matches[1]);
        }
        
        if (preg_match('/\[contacts\]\s*=>\s*(.+?)(?=\s*\[)/s', $entry, $matches)) {
            $order['contacts'] = trim($matches[1]);
        }
        
        $order['persons'] = array();
        
        if (preg_match('/\[persons\]\s*=>\s*Array\s*\((.*?)\[orgpersons\]/s', $entry, $personsBlock)) {
            $personsText = $personsBlock[1];
            preg_match_all('/\[(\d+)\]\s*=>\s*Array\s*\((.*?)(?=\[\d+\]|\[orgpersons\]|$)/s', $personsText, $personMatches, PREG_SET_ORDER);
            
            foreach ($personMatches as $personMatch) {
                $personText = $personMatch[2];
                $person = array();
                
                $fields = array(
                    'name', 'ismale', 'birthdate', 'federalsubject', 'city',
                    'address', 'postal', 'reason', 'awards', 'contacts',
                    'photo', 'work1', 'work1name', 'work1about', 'work1framing',
                    'work2', 'work2name', 'work2about', 'work2framing',
                    'work3', 'work3name', 'work3about', 'work3framing'
                );
                
                foreach ($fields as $field) {
                    if (preg_match('/\[' . $field . '\]\s*=>\s*(.+?)$/m', $personText, $match)) {
                        $person[$field] = trim($match[1]);
                    }
                }
                
                $order['persons'][] = $person;
            }
        }
        
        $order['orgpersons'] = array();
        
        if (preg_match('/\[orgpersons\]\s*=>\s*Array\s*\((.*?)(?=\[time\]|$)/s', $entry, $orgpersonsBlock)) {
            $orgpersonsText = $orgpersonsBlock[1];
            preg_match_all('/\[(\d+)\]\s*=>\s*Array\s*\((.*?)(?=\[\d+\]|$)/s', $orgpersonsText, $orgpersonMatches, PREG_SET_ORDER);
            
            foreach ($orgpersonMatches as $orgpersonMatch) {
                $personText = $orgpersonMatch[2];
                $person = array();
                
                $fields = array(
                    'name', 'ismale', 'birthdate', 'federalsubject', 'city',
                    'address', 'postal', 'reason', 'awards', 'contacts',
                    'photo', 'work1', 'work1name', 'work1about', 'work1framing',
                    'work2', 'work2name', 'work2about', 'work2framing',
                    'work3', 'work3name', 'work3about', 'work3framing'
                );
                
                foreach ($fields as $field) {
                    if (preg_match('/\[' . $field . '\]\s*=>\s*(.+?)$/m', $personText, $match)) {
                        $person[$field] = trim($match[1]);
                    }
                }
                
                $order['orgpersons'][] = $person;
            }
        }
        
        if (!empty($order['time'])) {
            $orders[] = $order;
        }
    }
    
    return $orders;
}

function getImagePath($reason) {
    $paths = array(
        '0' => '/uploads/works_up/professionals/',
        '1' => '/uploads/works_up/students/',
        '2' => '/uploads/works_up/amateurs/',
        '3' => '/uploads/works_up/young/'
    );
    
    return isset($paths[$reason]) ? $paths[$reason] : '/uploads/works_up/professionals/';
}

function extractYear($birthdate) {
    if (empty($birthdate)) {
        return '';
    }
    
    $parts = explode('.', $birthdate);
    if (count($parts) === 3) {
        return $parts[2];
    }
    
    preg_match('/(\d{4})/', $birthdate, $matches);
    return isset($matches[1]) ? $matches[1] : '';
}

function extractEmail($contacts, $clientEmail = '') {
    if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $contacts, $matches)) {
        return $matches[1];
    }
    return $clientEmail;
}

function isValidWork($person, $workNum) {
    $workKey = 'work' . $workNum;
    
    if (!isset($person[$workKey]) || empty($person[$workKey]) || trim($person[$workKey]) == '') {
        return false;
    }
    
    $workFile = trim($person[$workKey]);
    if (strpos($workFile, '[') !== false || strpos($workFile, ']') !== false) {
        return false;
    }
    
    return true;
}


// Маппинг номинаций
$reasonMap = array(
    '0' => 'Профессиональные художники',
    '1' => 'Студенты художественных ВУЗов',
    '2' => 'Непрофессиональные художники',
    '3' => 'Юные художники'
);

// Определяем текущий и следующий год
$currentYear = date('Y');
$nextYear = $currentYear + 1;

$selectedReason = isset($_GET['reason']) ? $_GET['reason'] : 'all';

$orders = parsePrintRLog($logFilePath);

if (isset($orders['error'])) {
    die('<h2>Ошибка: ' . $orders['error'] . '</h2>');
}

// Собираем все данные (без пагинации на этом этапе)
$allTableData = array();
$rowNumber = 1;

foreach ($orders as $order) {
    $orderYear = date('Y');
    if (isset($order['created'])) {
        $orderYear = date('Y', $order['created']);
    }
    
    if ($orderYear != $currentYear && $orderYear != $nextYear) {
        continue;
    }
    
    $clientEmail = isset($order['clientemail']) ? $order['clientemail'] : '';
    
    if (isset($order['persons']) && is_array($order['persons'])) {
        foreach ($order['persons'] as $person) {
            $name = isset($person['name']) ? trim($person['name']) : '';
            
            if (strtolower($name) === 'test' || $name === 'test') {
                continue;
            }
            
            $birthdate = isset($person['birthdate']) ? $person['birthdate'] : '';
            $birthYear = extractYear($birthdate);
            $contacts = isset($person['contacts']) ? $person['contacts'] : '';
            $reason = isset($person['reason']) ? $person['reason'] : '0';
            
            // Фильтруем по номинации (reason)
            if ($selectedReason !== 'all' && $reason != $selectedReason) {
                continue;
            }
            
            $reasonName = isset($reasonMap[$reason]) ? $reasonMap[$reason] : 'Не указано';
            $imagePath = getImagePath($reason);
            
            $email = extractEmail($contacts, $clientEmail);
            if (!empty($email) && strpos($contacts, $email) === false) {
                $contacts .= "\r\nАдрес электронной почты: " . $email;
            }
            
            for ($i = 1; $i <= 3; $i++) {
                if (!isValidWork($person, $i)) {
                    continue;
                }
                
                $workKey = 'work' . $i;
                $workNameKey = 'work' . $i . 'name';
                $workAboutKey = 'work' . $i . 'about';
                $workFramingKey = 'work' . $i . 'framing';
                
                $originalImage = $imagePath . $person[$workKey];
                
                $allTableData[] = array(
                    'number' => $rowNumber++,
                    'name' => $name,
                    'birthYear' => $birthYear,
                    'workName' => isset($person[$workNameKey]) ? $person[$workNameKey] : '',
                    'technique' => isset($person[$workAboutKey]) ? $person[$workAboutKey] : '',
                    'framing' => isset($person[$workFramingKey]) ? $person[$workFramingKey] : '',
                    'image' => $originalImage,                   
					'thumbnail' => $originalImage,
                    'contacts' => $contacts
                );
            }
        }
    }
    
    if (isset($order['orgpersons']) && is_array($order['orgpersons'])) {
        foreach ($order['orgpersons'] as $person) {
            $name = isset($person['name']) ? trim($person['name']) : '';
            
            if (strtolower($name) === 'test' || $name === 'test') {
                continue;
            }
            
            $birthdate = isset($person['birthdate']) ? $person['birthdate'] : '';
            $birthYear = extractYear($birthdate);
            $contacts = isset($person['contacts']) ? $person['contacts'] : '';
            $reason = isset($person['reason']) ? $person['reason'] : '0';
            
            // Фильтруем по номинации (reason)
            if ($selectedReason !== 'all' && $reason != $selectedReason) {
                continue;
            }
            
            $reasonName = isset($reasonMap[$reason]) ? $reasonMap[$reason] : 'Не указано';
            $imagePath = getImagePath($reason);
            
            $email = extractEmail($contacts, $clientEmail);
            if (!empty($email) && strpos($contacts, $email) === false) {
                $contacts .= "\r\nАдрес электронной почты: " . $email;
            }
            
            for ($i = 1; $i <= 3; $i++) {
                if (!isValidWork($person, $i)) {
                    continue;
                }
                
                $workKey = 'work' . $i;
                $workNameKey = 'work' . $i . 'name';
                $workAboutKey = 'work' . $i . 'about';
                $workFramingKey = 'work' . $i . 'framing';
                
                $originalImage = $imagePath . $person[$workKey];
                
                $allTableData[] = array(
                    'number' => $rowNumber++,
                    'name' => $name,
                    'birthYear' => $birthYear,
                    'workName' => isset($person[$workNameKey]) ? $person[$workNameKey] : '',
                    'technique' => isset($person[$workAboutKey]) ? $person[$workAboutKey] : '',
                    'framing' => isset($person[$workFramingKey]) ? $person[$workFramingKey] : '',
                    'image' => $originalImage,
					'thumbnail' => $originalImage,
                    'contacts' => $contacts
                );
            }
        }
    }
}

// Вычисляем пагинацию
// Удаляем дубликаты записей

$totalRecords = count($allTableData);
$totalPages = ceil($totalRecords / $perPage);
$currentPage = min($currentPage, max(1, $totalPages));

$offset = ($currentPage - 1) * $perPage;
$tableData = array_slice($allTableData, $offset, $perPage);


/* print '<pre>';
print_r($tableData);
print '</pre>';  */

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Предпросмотр заявок - <?php echo ($selectedReason === 'all') ? 'Все номинации' : $reasonMap[$selectedReason]; ?> год</title>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Magnific Popup CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css">
    
    <!-- Magnific Popup JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #333;
            font-size: 24px;
        }
        
        .header-info {
            text-align: right;
        }
        
        .header-year {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .header-count {
            color: #666;
            font-size: 14px;
        }
        
        .actions {
            background: white;
            padding: 15px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-back {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-back:hover {
            background: #d0d0d0;
        }
        
        .btn-export {
            background: #48bb78;
            color: white;
        }
        
        .btn-export:hover {
            background: #38a169;
        }
        
        .per-page-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
        }
        
        .per-page-selector label {
            color: #666;
            font-size: 14px;
        }
        
        .per-page-selector select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .info-badge {
            background: #f0f4ff;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 13px;
            color: #667eea;
        }
        
        .pagination {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .pagination-info {
            color: #666;
            font-size: 14px;
        }
        
        .pagination-controls {
            display: flex;
            gap: 5px;
        }
        
        .page-btn {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            background: white;
            color: #333;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .page-btn:hover {
            background: #f0f0f0;
        }
        
        .page-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th {
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #333;
            vertical-align: middle;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        .number-cell {
            text-align: center;
            font-weight: bold;
            color: #667eea;
            width: 50px;
        }
        
        .image-cell {
            width: 220px;
            text-align: center;
        }
        
        .image-cell img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .image-cell img:hover {
            transform: scale(1.05);
        }
        
        .contacts-cell {
            font-size: 12px;
            color: #666;
            max-width: 200px;
            white-space: pre-wrap;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #999;
            font-size: 16px;
        }
        
        /* Magnific Popup customization */
        .mfp-title {
            padding: 10px 15px;
            color: #fff;
        }
        
        @media print {
            .header, .actions, .pagination {
                display: none;
            }
        }

        /* Стили для кнопки "Ссылка на фото" */
        .btn-photo-link {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .btn-photo-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.5);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .btn-photo-link:active {
            transform: translateY(0);
        }

        .photo-link-cell {
            min-width: 150px;
            vertical-align: middle;
        }
		
		.btn-photo-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.5);
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

.btn-photo-link:active {
    transform: translateY(0);
}

.photo-link-cell {
    min-width: 150px;
    vertical-align: middle;
}
		
		
		
		
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1>📋 Предпросмотр заявок 
				
<?php
// Пример 1: Простая проверка и вывод
if (isset($_GET['reason'])) {
    $reason = intval($_GET['reason']); // Преобразуем в число для безопасности
    
    switch ($reason) {
        case 0:
            echo "«Профессиональные художники»";
            break;
        case 1:
            echo "«Студенты художественных ВУЗов»";
            break;
        case 2:
            echo "«Непрофессиональные художники»";
            break;
        case 3:
            echo "«Юные художники»";
            break;
        default:
            echo "«Все категории»";
    }
} else {
    echo "Параметр reason не указан";
}

?>
				
				</h1>
        </div>
        <div class="header-info">



            <div class="header-year"><?php echo $currentYear . ' год';?></div>
            <div class="header-count"><?php echo $totalRecords; ?> записей всего</div>
        </div>
    </div>
    
    <div class="actions">
        <a href="index.php" class="btn btn-back">← Назад</a>
        <!--a href="export.php?reason=<?php echo $selectedReason; ?>&per_page=<?php echo $perPage; ?>&page=<?php echo $currentPage; ?>" class="btn btn-export">
            📥 Экспорт с миниатюрами ВСЕ записи
        </a-->
        <a href="export_original.php?reason=<?php echo $selectedReason; ?>&per_page=<?php echo $perPage; ?>&page=<?php echo $currentPage; ?>" class="btn btn-export">
            📥 Экспорт с оригиналами <?php echo min($perPage, count($tableData)); ?> записей
        </a>
  
        
        <div class="per-page-selector">
            <label>Показывать по:</label>
            <select onchange="changePerPage(this.value)">
                <?php foreach ($perPageOptions as $option): ?>
                    <option value="<?php echo $option; ?>" <?php echo $option == $perPage ? 'selected' : ''; ?>>
                        <?php echo $option; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <?php if ($totalRecords > 0): ?>
    <div class="pagination">
        <div class="pagination-info">
            Показаны записи <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalRecords); ?> из <?php echo $totalRecords; ?>
        </div>
        
        <div class="pagination-controls">
            <?php if ($currentPage > 1): ?>
                <a href="?reason=<?php echo $selectedReason; ?>&page=1&per_page=<?php echo $perPage; ?>" class="page-btn">« Первая</a>
                <a href="?reason=<?php echo $selectedReason; ?>&page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $perPage; ?>" class="page-btn">‹ Пред</a>
            <?php endif; ?>
            
            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <a href="?reason=<?php echo $selectedReason; ?>&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?>" 
                   class="page-btn <?php echo $i == $currentPage ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($currentPage < $totalPages): ?>
                <a href="?reason=<?php echo $selectedReason; ?>&page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $perPage; ?>" class="page-btn">След ›</a>
                <a href="?reason=<?php echo $selectedReason; ?>&page=<?php echo $totalPages; ?>&per_page=<?php echo $perPage; ?>" class="page-btn">Последняя »</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="table-container">
        <?php if (count($tableData) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>№</th>
                    <th>Ф.И.О автора</th>
                    <th>Год рождения</th>
                    <th>Наименование работы</th>
                    <th>Техника</th>
                    <th>Оформлена</th>
                    <th>Миниатюра работы</th>
                    <th>Контактные данные</th>
                    <th>Ссылка на фото</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tableData as $row): ?>
                    <?php
                    // ЗАЩИТА ОТ ОБРЫВА: проверяем наличие всех полей
                    $number = isset($row['number']) ? $row['number'] : 0;
                    $name = isset($row['name']) ? $row['name'] : '';
                    $birthYear = isset($row['birthYear']) ? $row['birthYear'] : '';
                    $workName = isset($row['workName']) ? $row['workName'] : '';
                    $technique = isset($row['technique']) ? $row['technique'] : '';
                    $framing = isset($row['framing']) ? $row['framing'] : '';
                    $image = isset($row['image']) ? $row['image'] : '';
                    $contacts = isset($row['contacts']) ? $row['contacts'] : '';
                    ?>
                <tr>
                    <td class="number-cell"><?php echo $number; ?></td>
                    <td><?php echo htmlspecialchars($name); ?></td>
                    <td><?php echo htmlspecialchars($birthYear); ?></td>
                    <td><?php echo htmlspecialchars($workName); ?></td>
                    <td><?php echo htmlspecialchars($technique); ?></td>
                    <td><?php echo htmlspecialchars($framing); ?></td>
                    <td class="image-cell">
                        <a href="<?php echo $siteUrl . $image; ?>" 
                           class="image-popup" 
                           title="<?php echo htmlspecialchars($name . ' - ' . $workName); ?>">
                            <img src="<?php echo $siteUrl . $image; ?>" 
                                 alt="<?php echo htmlspecialchars($workName); ?>"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23ddd%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%23999%22 font-size=%2212%22%3ENo image%3C/text%3E%3C/svg%3E'">
                        </a>
                    </td>
                    <td class="contacts-cell"><?php echo nl2br(htmlspecialchars($contacts)); ?></td>
                    <td class="photo-link-cell" style="text-align: center;">
                        <?php if (!empty($image)): ?>
                            <a href="<?php echo $siteUrl . $image; ?>" 
                               target="_blank" 
                               class="btn-photo-link"
                               title="Открыть оригинальное фото в новой вкладке">
                                🔗 Смотреть
                            </a>
                        <?php else: ?>
                            <span style="color: #999;">Нет фото</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">
            <p>📭 Заявок за <?php echo $selectedReason; ?> год не найдено</p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($totalRecords > 0): ?>
    <div class="pagination" style="margin-top: 20px;">
        <div class="pagination-info">
            Показаны записи <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalRecords); ?> из <?php echo $totalRecords; ?>
        </div>
        
        <div class="pagination-controls">
            <?php if ($currentPage > 1): ?>
                <a href="?reason=<?php echo $selectedReason; ?>&page=1&per_page=<?php echo $perPage; ?>" class="page-btn">« Первая</a>
                <a href="?reason=<?php echo $selectedReason; ?>&page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $perPage; ?>" class="page-btn">‹ Пред</a>
            <?php endif; ?>
            
            <?php
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <a href="?reason=<?php echo $selectedReason; ?>&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?>" 
                   class="page-btn <?php echo $i == $currentPage ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($currentPage < $totalPages): ?>
                <a href="?reason=<?php echo $selectedReason; ?>&page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $perPage; ?>" class="page-btn">След ›</a>
                <a href="?reason=<?php echo $selectedReason; ?>&page=<?php echo $totalPages; ?>&per_page=<?php echo $perPage; ?>" class="page-btn">Последняя »</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        function changePerPage(perPage) {
            window.location.href = '?reason=<?php echo $selectedReason; ?>&page=1&per_page=' + perPage;
        }
        
        // Инициализация Magnific Popup для увеличения изображений
        $(document).ready(function() {
            $('.image-popup').magnificPopup({
                type: 'image',
                closeOnContentClick: true,
                mainClass: 'mfp-img-mobile',
                image: {
                    verticalFit: true,
                    titleSrc: function(item) {
                        return item.el.attr('title');
                    }
                },
                zoom: {
                    enabled: true,
                    duration: 300
                }
            });
        });
    </script>
	
<!-- УМНЫЙ ИНДИКАТОР ЗАГРУЗКИ - START -->
<style>
    /* Оверлей на весь экран */
    #smart-loader {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(5px);
    }
    
    #smart-loader.active {
        display: flex;
    }
    
    /* Контейнер */
    .smart-loader-box {
        text-align: center;
        background: #fff;
        padding: 40px 60px;
        border-radius: 20px;
        box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
        max-width: 450px;
    }
    
    /* Спиннер */
    .smart-spinner {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border: 8px solid #f3f3f3;
        border-top: 8px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Текст */
    .smart-title {
        font-size: 22px;
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
    }
    
    .smart-subtitle {
        font-size: 14px;
        color: #666;
        margin-bottom: 20px;
    }
    
    /* Прогресс бар */
    .smart-progress {
        width: 100%;
        height: 6px;
        background: #e0e0e0;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 15px;
    }
    
    .smart-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #3498db, #2ecc71);
        width: 0%;
        transition: width 0.3s ease;
    }
    
    /* Иконка */
    .smart-icon {
        font-size: 48px;
        margin-bottom: 15px;
        animation: bounce 1s ease-in-out infinite;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    
    /* Таймер */
    .smart-timer {
        font-size: 12px;
        color: #999;
        margin-top: 10px;
    }
</style>

<div id="smart-loader">
    <div class="smart-loader-box">
        <div class="smart-icon">📊</div>
        <div class="smart-spinner"></div>
        <div class="smart-title" id="smart-title">Загрузка...</div>
        <div class="smart-subtitle" id="smart-subtitle">Пожалуйста, подождите</div>
        <div class="smart-progress">
            <div class="smart-progress-bar" id="smart-progress-bar"></div>
        </div>
        <div class="smart-timer" id="smart-timer">Прошло: 0 сек</div>
    </div>
</div>

<script>
$(document).ready(function() {
    var downloadCheckInterval;
    var timerInterval;
    var elapsedSeconds = 0;
    
    // Функция показа индикатора
    function showSmartLoader(title, subtitle, icon) {
        $('#smart-title').text(title || 'Загрузка...');
        $('#smart-subtitle').text(subtitle || 'Пожалуйста, подождите');
        $('.smart-icon').text(icon || '📊');
        $('#smart-loader').addClass('active');
        $('#smart-progress-bar').css('width', '0%');
        elapsedSeconds = 0;
        $('#smart-timer').text('Прошло: 0 сек');
        
        // Анимация прогресс-бара
        animateProgress();
        
        // Таймер
        startTimer();
    }
    
    // Функция скрытия индикатора
    function hideSmartLoader() {
        $('#smart-loader').removeClass('active');
        stopDownloadCheck();
        stopTimer();
    }
    
    // Анимация прогресс-бара (имитация)
    function animateProgress() {
        var progress = 0;
        var progressInterval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90; // Не достигаем 100% пока не закончится
            $('#smart-progress-bar').css('width', progress + '%');
            
            if (!$('#smart-loader').hasClass('active')) {
                clearInterval(progressInterval);
            }
        }, 500);
    }
    
    // Таймер времени
    function startTimer() {
        timerInterval = setInterval(function() {
            elapsedSeconds++;
            $('#smart-timer').text('Прошло: ' + elapsedSeconds + ' сек');
        }, 1000);
    }
    
    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }
    
    // ============================================
    // МЕТОД 1: Cookie-based detection (ЛУЧШИЙ для export)
    // ============================================
    function startDownloadCheck(cookieName) {
        var maxAttempts = 300; // 300 секунд = 5 минут
        var attempts = 0;
        
        // Устанавливаем cookie перед началом
        document.cookie = cookieName + "=checking; path=/";
        
        downloadCheckInterval = setInterval(function() {
            attempts++;
            
            // Проверяем cookie
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = cookies[i].trim();
                if (cookie.indexOf(cookieName + '=downloaded') === 0) {
                    // Файл начал скачиваться!
                    $('#smart-progress-bar').css('width', '100%');
                    $('#smart-subtitle').text('Скачивание началось!');
                    
                    setTimeout(function() {
                        hideSmartLoader();
                    }, 800);
                    
                    stopDownloadCheck();
                    return;
                }
            }
            
            // Таймаут (если что-то пошло не так)
            if (attempts >= maxAttempts) {
                hideSmartLoader();
                stopDownloadCheck();
            }
        }, 1000); // Проверяем каждую секунду
    }
    
    function stopDownloadCheck() {
        if (downloadCheckInterval) {
            clearInterval(downloadCheckInterval);
            downloadCheckInterval = null;
        }
    }
    
    // ============================================
    // ДЛЯ ЭКСПОРТА (export.php, export_original.php)
    // ============================================
    $('a[href*="export.php"], a[href*="export_original.php"]').on('click', function(e) {
        var href = $(this).attr('href');
        var cookieName = 'fileDownload_' + Date.now();
        
        // Добавляем cookie параметр к URL
        var separator = href.indexOf('?') !== -1 ? '&' : '?';
        $(this).attr('href', href + separator + 'downloadToken=' + cookieName);
        
        // Определяем тип экспорта
        if (href.indexOf('export_original.php') !== -1) {
            showSmartLoader(
                '📥 Экспорт с оригиналами', 
                'Создание Excel файла с полноразмерными изображениями. Это может занять до 5 минут...',
                '📄'
            );
        } else {
            showSmartLoader(
                '📥 Экспорт заявок', 
                'Создание Excel файла с миниатюрами изображений...',
                '📊'
            );
        }
        
        // Запускаем проверку скачивания
        startDownloadCheck(cookieName);
    });
    
    // ============================================
    // ДЛЯ PREVIEW (preview.php, preview_original.php)
    // Здесь используем window.onload
    // ============================================
    var isNavigatingToPreview = false;
    
    $('a[href*="preview.php"], a[href*="preview_original.php"]').on('click', function(e) {
        var href = $(this).attr('href');
        isNavigatingToPreview = true;
        
        // Определяем тип preview
        if (href.indexOf('preview_original.php') !== -1) {
            showSmartLoader(
                '🖼️ Загрузка оригиналов', 
                'Подготовка полноразмерных изображений. Это может занять несколько минут...',
                '🖼️'
            );
        } else {
            showSmartLoader(
                '🔍 Загрузка предпросмотра', 
                'Генерация миниатюр изображений через phpthumbof...',
                '🔍'
            );
        }
    });
    
    // Скрываем индикатор когда страница загрузилась
    if (isNavigatingToPreview) {
        $(window).on('load', function() {
            $('#smart-progress-bar').css('width', '100%');
            setTimeout(function() {
                hideSmartLoader();
            }, 500);
        });
    }
    
    // ============================================
    // FALLBACK: На случай ошибок
    // ============================================
    setTimeout(function() {
        if ($('#smart-loader').hasClass('active')) {
            // Если индикатор всё ещё активен через 10 минут - скрываем
            hideSmartLoader();
        }
    }, 600000); // 10 минут
});
</script>
<!-- УМНЫЙ ИНДИКАТОР ЗАГРУЗКИ - END -->


<!-- ДОПОЛНИТЕЛЬНО: Спиннеры на изображениях - START -->
<style>
    /* Контейнер изображения с индикатором */
    .image-cell {
        position: relative;
    }
    
    /* Спиннер на изображении */
    .image-loading-spinner {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 30px;
        height: 30px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        pointer-events: none;
        z-index: 10;
    }
    
    /* Затемнение пока изображение загружается */
    .image-cell img.loading {
        opacity: 0.3;
    }
    
    .image-cell img.loaded {
        opacity: 1;
        transition: opacity 0.3s ease;
    }
	
  /* Индикатор загрузки картинок (прогресс) */
    #image-progress {
        position: fixed;
        top: 10px;
        right: 10px;
        background: rgba(52, 152, 219, 0.95);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.3);
        z-index: 9998;
        display: none;
        font-size: 13px;
        font-weight: 600;
        min-width: 180px;
    }
    
    #image-progress.show {
        display: block;
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .image-progress-bar {
        width: 100%;
        height: 4px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 2px;
        margin-top: 8px;
        overflow: hidden;
    }
    
    .image-progress-fill {
        height: 100%;
        background: white;
        width: 0%;
        transition: width 0.3s ease;
    }	
	
</style>

<script>
$(document).ready(function() {
    // Добавляем спиннеры к каждому изображению
    $('table img, .image-cell img').each(function() {
        var $img = $(this);
        var $cell = $img.closest('td, .image-cell');
        
        if (!this.complete) {
            // Изображение ещё не загружено
            $img.addClass('loading');
            
            // Добавляем спиннер
            var $spinner = $('<div class="image-loading-spinner"></div>');
            $cell.css('position', 'relative').append($spinner);
            
            // Убираем спиннер после загрузки
            $img.on('load', function() {
                $img.removeClass('loading').addClass('loaded');
                $spinner.fadeOut(200, function() {
                    $(this).remove();
                });
            }).on('error', function() {
                $img.removeClass('loading');
                $spinner.fadeOut(200, function() {
                    $(this).remove();
                });
            });
        } else {
            // Изображение уже загружено
            $img.addClass('loaded');
        }
    });
});
</script>
<!-- ДОПОЛНИТЕЛЬНО: Спиннеры на изображениях - END -->	

<!-- Прогресс загрузки всех изображений на странице -->
<div id="image-progress">
    <div>🖼️ Загрузка изображений: <span id="image-count">0/0</span></div>
    <div class="image-progress-bar">
        <div class="image-progress-fill" id="image-progress-fill"></div>
    </div>
</div>

<script>
$(document).ready(function() {
    // ============================================
    // ОСНОВНОЙ ИНДИКАТОР (навигация, экспорт)
    // ============================================
    
    function showPreviewLoader(title, subtitle, icon) {
        $('#preview-title').text(title || 'Загрузка...');
        $('#preview-subtitle').text(subtitle || 'Пожалуйста, подождите');
        $('#preview-icon').text(icon || '📄');
        $('#preview-loader').addClass('active');
    }
    
    function hidePreviewLoader() {
        $('#preview-loader').removeClass('active');
    }
    
    // ============================================
    // МИНИ-ИНДИКАТОР (попапы, изображения)
    // ============================================
    
    function showMiniLoader(text) {
        $('#mini-text').text(text || 'Загрузка...');
        $('#mini-loader').addClass('show');
    }
    
    function hideMiniLoader() {
        $('#mini-loader').removeClass('show');
    }
    
    // ============================================
    // ПРОГРЕСС ЗАГРУЗКИ ИЗОБРАЖЕНИЙ
    // ============================================
    
    var totalImages = 0;
    var loadedImages = 0;
    
    function updateImageProgress() {
        $('#image-count').text(loadedImages + '/' + totalImages);
        var percent = totalImages > 0 ? (loadedImages / totalImages) * 100 : 0;
        $('#image-progress-fill').css('width', percent + '%');
        
        if (loadedImages >= totalImages && totalImages > 0) {
            // Все изображения загружены
            setTimeout(function() {
                $('#image-progress').removeClass('show');
            }, 2000); // Скрываем через 2 секунды
        }
    }
    
    function startImageProgressTracking() {
        // Находим все изображения на странице
        var images = $('table img, .image-cell img');
        totalImages = images.length;
        loadedImages = 0;
        
        if (totalImages > 0) {
            $('#image-progress').addClass('show');
            updateImageProgress();
            
            // Отслеживаем загрузку каждого изображения
            images.each(function() {
                var img = this;
                
                if (img.complete) {
                    // Изображение уже загружено
                    loadedImages++;
                    updateImageProgress();
                } else {
                    // Ждём загрузки
                    $(img).on('load', function() {
                        loadedImages++;
                        updateImageProgress();
                    }).on('error', function() {
                        // Даже при ошибке считаем как загруженное
                        loadedImages++;
                        updateImageProgress();
                    });
                }
            });
        }
    }
    
    // Запускаем отслеживание при загрузке страницы
    startImageProgressTracking();
    
  

});
</script>
<!-- ПОЛНЫЙ ИНДИКАТОР ДЛЯ PREVIEW - END -->	
	
</body>
</html>
 <?php exit; ?>
