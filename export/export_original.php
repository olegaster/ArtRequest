<?php
// COOKIE-BASED DOWNLOAD DETECTION
if (isset($_GET['downloadToken'])) {
    $downloadToken = $_GET['downloadToken'];
    setcookie($downloadToken, 'downloaded', time() + 60, '/');
}	
		
/**
	* Export System - Excel Export with ORIGINAL IMAGES (no compression)
 * Version 3.4.1 - С ПАГИНАЦИЕЙ (25, 50, 100 строк)
 * 
 * Параметры GET:
 * - reason: номинация (0,1,2,3 или all)
 * - per_page: количество записей (25, 50, 100)
 * - page: номер страницы (1, 2, 3, ...)
 * 
 * Примеры:
 * export_original.php?reason=3&per_page=25&page=1
 * export_original.php?reason=0&per_page=50&page=2
 */

// Подключаем MODX API
define('MODX_API_MODE', true);
require $_SERVER['DOCUMENT_ROOT'].'/index.php';

// Включаем обработку ошибок
$modx->getService('error','error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

// Установка лимитов (увеличены для больших изображений)
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '900');
set_time_limit(900);

// Подключаем PHPExcel
require_once __DIR__ . '/vendor/autoload.php';

// Путь к лог-файлу
$logFilePath = '../core/components/artrequest/orders.log';

// Базовый URL сайта (измените на свой)
$siteUrl = 'https://art.fondsci.localhost';

// ПАРАМЕТРЫ ПАГИНАЦИИ
$perPageOptions = array(25, 50, 100);
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
if (!in_array($perPage, $perPageOptions)) {
    $perPage = 25;
}

$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

/**
 * Удаление дубликатов
 */

/**
 * Функция для парсинга print_r() формата
 */
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

// Параметры из GET
$selectedReason = isset($_GET['reason']) ? $_GET['reason'] : 'all';

// Парсим данные
$orders = parsePrintRLog($logFilePath);

if (isset($orders['error'])) {
    die('<h2>Ошибка: ' . $orders['error'] . '</h2>');
}

// Собираем ВСЕ данные (без пагинации на этом этапе)
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
    
    // Обработка persons
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
            
            if ($selectedReason !== 'all' && $reason != $selectedReason) {
                continue;
            }
            
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
                    'contacts' => $contacts
                );
            }
        }
    }
    
    // Обработка orgpersons
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
            
            if ($selectedReason !== 'all' && $reason != $selectedReason) {
                continue;
            }
            
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
                    'contacts' => $contacts
                );
            }
        }
    }
}

// Удаление дубликатов

if (empty($allTableData)) {
    die('<h2>Ошибка: Нет данных для экспорта. Проверьте фильтры и orders.log</h2>');
}

// ПРИМЕНЯЕМ ПАГИНАЦИЮ
$totalRecords = count($allTableData);
$totalPages = ceil($totalRecords / $perPage);
$currentPage = min($currentPage, max(1, $totalPages));

$offset = ($currentPage - 1) * $perPage;
$tableData = array_slice($allTableData, $offset, $perPage);

// Пересчитываем номера строк для текущей страницы
$displayRowNumber = $offset + 1;
foreach ($tableData as &$row) {
    $row['number'] = $displayRowNumber++;
}
unset($row);

// Создание Excel файла
$objPHPExcel = new PHPExcel();
$objPHPExcel->setActiveSheetIndex(0);
$sheet = $objPHPExcel->getActiveSheet();

// Установка заголовков столбцов
$sheet->setTitle('Заявки');
$sheet->setCellValue('A1', '№');
$sheet->setCellValue('B1', 'Ф.И.О автора');
$sheet->setCellValue('C1', 'Год рождения');
$sheet->setCellValue('D1', 'Наименование работы');
$sheet->setCellValue('E1', 'ТЕХНИКА');
$sheet->setCellValue('F1', 'Оформлена');
$sheet->setCellValue('G1', 'Оригинальное изображение');
$sheet->setCellValue('H1', 'Контакты');
$sheet->setCellValue('I1', 'Ссылка на фото');

// Стиль заголовка
$headerStyle = array(
    'font' => array(
        'bold' => true,
        'size' => 11,
        'color' => array('rgb' => '000000')
    ),
    'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'color' => array('rgb' => 'E0E0E0')
    ),
    'borders' => array(
        'allborders' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN
        )
    ),
    'alignment' => array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
    )
);

$sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(20);

// Ширина столбцов
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(40);
$sheet->getColumnDimension('E')->setWidth(30);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(90);
$sheet->getColumnDimension('H')->setWidth(35);
$sheet->getColumnDimension('I')->setWidth(55);


// Заполнение данных (только текущая страница)
$rowIndex = 2;

foreach ($tableData as $data) {
    // Заполнение текстовых данных
    $sheet->setCellValue('A'.$rowIndex, $data['number']);
    $sheet->setCellValue('B'.$rowIndex, $data['name']);
    $sheet->setCellValue('C'.$rowIndex, $data['birthYear']);
    $sheet->setCellValue('D'.$rowIndex, $data['workName']);
    $sheet->setCellValue('E'.$rowIndex, $data['technique']);
    $sheet->setCellValue('F'.$rowIndex, $data['framing']);
    $sheet->setCellValue('H'.$rowIndex, $data['contacts']);
    
    // ДОБАВЛЕНО: Ссылка на оригинальное фото
    if (!empty($data['image'])) {
        $photoUrl = $siteUrl . $data['image'];
        $sheet->setCellValue('I'.$rowIndex, $photoUrl);
        $sheet->getCell('I'.$rowIndex)->getHyperlink()->setUrl($photoUrl);
        $sheet->getStyle('I'.$rowIndex)->getFont()->getColor()->setRGB('0000FF');
        $sheet->getStyle('I'.$rowIndex)->getFont()->setUnderline(true);
    } else {
        $sheet->setCellValue('I'.$rowIndex, 'Нет фото');
    }
    
    // Стиль текста
    $sheet->getStyle('A'.$rowIndex.':I'.$rowIndex)->applyFromArray(array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        ),
        'alignment' => array(
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP,
            'wrap' => true
        )
    ));
    
    // 🔥 ВСТАВКА ОРИГИНАЛЬНОГО ИЗОБРАЖЕНИЯ (БЕЗ СЖАТИЯ)
    if (!empty($data['image'])) {
        $originalImagePath = $_SERVER['DOCUMENT_ROOT'] . $data['image'];
        
        if (file_exists($originalImagePath)) {
            try {
                list($imgWidth, $imgHeight) = getimagesize($originalImagePath);
                
                $targetWidth = 600;
                $targetHeight = ($imgHeight / $imgWidth) * $targetWidth;
                
                $objDrawing = new PHPExcel_Worksheet_Drawing();
                $objDrawing->setPath($originalImagePath);
                $objDrawing->setCoordinates('G'.$rowIndex);
                $objDrawing->setWidth($targetWidth);
                $objDrawing->setHeight($targetHeight);
                $objDrawing->setOffsetX(5);
                $objDrawing->setOffsetY(5);
                $objDrawing->setWorksheet($sheet);
                
                $rowHeightPoints = ($targetHeight * 0.75) + 10;
                $sheet->getRowDimension($rowIndex)->setRowHeight($rowHeightPoints);
                
                unset($objDrawing);
                
            } catch (Exception $e) {
                $sheet->setCellValue('G'.$rowIndex, 'Ошибка загрузки');
            }
        } else {
            $sheet->setCellValue('G'.$rowIndex, 'Файл не найден');
        }
    }
    
    $rowIndex++;
    
    // Очистка памяти каждые 50 строк
    if (($rowIndex - 2) % 50 == 0) {
        gc_collect_cycles();
    }
}

// Генерация имени файла с информацией о пагинации
$reasonText = $selectedReason === 'all' ? 'Все_номинации' : $reasonMap[$selectedReason];
$filename = sprintf(
    'Заявки_ОРИГИНАЛЫ_%s_стр%d_из_%d_(записи_%d-%d_из_%d)_%s.xls',
    $reasonText,
    $currentPage,
    $totalPages,
    $offset + 1,
    min($offset + $perPage, $totalRecords),
    $totalRecords,
    date('Y-m-d')
);

// Отправка файла
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

exit;
?>
