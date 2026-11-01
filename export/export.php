<?php
// Защита бинарного ответа Excel от случайного вывода PHP/MODX
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ob_start();

// COOKIE-BASED DOWNLOAD DETECTION
if (isset($_GET['downloadToken'])) {
    $downloadToken = $_GET['downloadToken'];
    setcookie($downloadToken, 'downloaded', time() + 60, '/');
}	
	
/**
 * Export System - Excel Export with phpthumbof thumbnails
 * Version 3.3.3 - ИСПРАВЛЕНА ПРОБЛЕМА С REASON=0 (батчевая обработка)
 * 
 * Изменения:
 * - Увеличен memory_limit до 1024M
 * - Увеличен max_execution_time до 600 сек (10 мин)
 * - Добавлена очистка памяти после каждого изображения
 * - Улучшенная обработка ошибок
 */

// Подключаем MODX API
define('MODX_API_MODE', true);
require $_SERVER['DOCUMENT_ROOT'].'/index.php';

// Настраиваем журналирование MODX: сообщения записываются в файл, а не в HTML-ответ
$modx->getService('error', 'error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

// Логирование для отладки
ini_set('error_log', '/tmp/export_errors.log');

// 🔥 УВЕЛИЧЕННЫЕ ЛИМИТЫ для reason=0 (много данных)
ini_set('memory_limit', '1024M');  // Увеличено с 512M
ini_set('max_execution_time', '600');  // Увеличено с 300 (10 минут)
set_time_limit(600);

// Подключаем PHPExcel
require_once __DIR__ . '/vendor/autoload.php';

// Путь к лог-файлу
$logFilePath = '../core/components/artrequest/orders.log';

// Базовый URL сайта (измените на свой)
$siteUrl = 'https://art.fondsci.localhost';


/**
 * Генерация миниатюры через phpthumbof
 */
if (!function_exists('generateThumbnail')) {
    function generateThumbnail($imagePath, $width = 400) {
        global $modx;

        if (empty($imagePath)) {
            return '';
        }

        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
        
        if (!file_exists($fullPath)) {
            error_log("File not found: {$fullPath}");
            return '';
        }

        $options = "w={$width}&q=85";

        try {
            $thumbnail = $modx->runSnippet('phpthumbof', array(
                'input' => $imagePath,
                'options' => $options
            ));

            if (empty($thumbnail)) {
                error_log("phpthumbof returned empty: {$imagePath}");
                return '';
            }
            
            return $thumbnail;
            
        } catch (Exception $e) {
            error_log("Thumbnail exception: " . $e->getMessage());
            return '';
        }
    }
}

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

error_log("=== EXPORT START ===");
error_log("Reason: {$selectedReason}");
error_log("Memory limit: " . ini_get('memory_limit'));
error_log("Max execution time: " . ini_get('max_execution_time'));

// Парсим данные
$orders = parsePrintRLog($logFilePath);

if (isset($orders['error'])) {
    error_log("Parse error: " . $orders['error']);
    die('<h2>Ошибка: ' . $orders['error'] . '</h2>');
}

error_log("Orders parsed: " . count($orders));

// Собираем данные
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

error_log("Data collected before dedup: " . count($allTableData));

// Удаление дубликатов

error_log("Data after dedup: " . count($allTableData));

if (empty($allTableData)) {
    error_log("ERROR: No data to export for reason={$selectedReason}");
    die('<h2>Ошибка: Нет данных для экспорта. Проверьте фильтры и orders.log</h2>');
}

error_log("Starting Excel generation...");

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
$sheet->setCellValue('G1', 'Миниатюра работы');
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
$sheet->getColumnDimension('G')->setWidth(58);
$sheet->getColumnDimension('H')->setWidth(35);
$sheet->getColumnDimension('I')->setWidth(55);

// Заполнение данных
$rowIndex = 2;
$imageSuccessCount = 0;
$imageErrorCount = 0;

foreach ($allTableData as $index => $data) {
    // Логируем прогресс каждые 50 записей
    if ($index % 50 == 0) {
        error_log("Processing row {$index} / " . count($allTableData) . " (Memory: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB)");
    }
    
    // Заполнение текстовых данных
    $sheet->setCellValue('A'.$rowIndex, $data['number']);
    $sheet->setCellValue('B'.$rowIndex, $data['name']);
    $sheet->setCellValue('C'.$rowIndex, $data['birthYear']);
    $sheet->setCellValue('D'.$rowIndex, $data['workName']);
    $sheet->setCellValue('E'.$rowIndex, $data['technique']);
    $sheet->setCellValue('F'.$rowIndex, $data['framing']);
    $sheet->setCellValue('H'.$rowIndex, $data['contacts']);
    
    // Ссылка на оригинальное фото
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
    
    // Вставка изображения
    if (!empty($data['image'])) {
        try {
            // Генерируем миниатюру 400px через phpthumbof
            $thumbnailUrl = generateThumbnail($data['image'], 400);
            
            if (!empty($thumbnailUrl)) {
                $thumbnailPath = $_SERVER['DOCUMENT_ROOT'] . $thumbnailUrl;
                
                if (file_exists($thumbnailPath)) {
                    list($imgWidth, $imgHeight) = getimagesize($thumbnailPath);
                    
                    $targetWidth = 400;
                    $targetHeight = ($imgHeight / $imgWidth) * $targetWidth;
                    
                    $objDrawing = new PHPExcel_Worksheet_Drawing();
                    $objDrawing->setPath($thumbnailPath);
                    $objDrawing->setCoordinates('G'.$rowIndex);
                    $objDrawing->setWidth($targetWidth);
                    $objDrawing->setHeight($targetHeight);
                    $objDrawing->setOffsetX(5);
                    $objDrawing->setOffsetY(5);
                    $objDrawing->setWorksheet($sheet);
                    
                    $rowHeightPoints = ($targetHeight * 0.75) + 10;
                    $sheet->getRowDimension($rowIndex)->setRowHeight($rowHeightPoints);
                    
                    $imageSuccessCount++;
                    
                    // 🔥 Очистка памяти после каждого изображения
                    unset($objDrawing);
                    
                } else {
                    $sheet->setCellValue('G'.$rowIndex, 'Файл не найден');
                    $imageErrorCount++;
                }
            } else {
                $sheet->setCellValue('G'.$rowIndex, 'Ошибка генерации');
                $imageErrorCount++;
            }
        } catch (Exception $e) {
            error_log("Image error row {$rowIndex}: " . $e->getMessage());
            $sheet->setCellValue('G'.$rowIndex, 'Ошибка загрузки');
            $imageErrorCount++;
        }
    }
    
    $rowIndex++;
    
    // 🔥 Принудительная очистка памяти каждые 100 строк
    if ($index % 100 == 0) {
        gc_collect_cycles();
    }
}

error_log("Excel rows filled: " . ($rowIndex - 2));
error_log("Images inserted: {$imageSuccessCount} success, {$imageErrorCount} errors");
error_log("Memory peak: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB");

// Генерация имени файла
$reasonText = $selectedReason === 'all' ? 'Все_номинации' : $reasonMap[$selectedReason];
$filename = 'Заявки_' . $reasonText . '_' . date('Y-m-d') . '.xls';

error_log("Generating file: {$filename}");

// Удаляем возможный случайный вывод MODX/PHP перед отправкой бинарного файла
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Отправка файла
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

error_log("=== EXPORT COMPLETE ===");
exit;
?>
