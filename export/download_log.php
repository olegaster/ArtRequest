<?php
/**
 * Скрипт для скачивания orders.log
 * Обходит ограничения прямого доступа к файлу
 */

// Путь к лог-файлу
$logFilePath = '../core/components/artrequest/orders.log';

// Проверка существования файла
if (!file_exists($logFilePath)) {
    http_response_code(404);
    die('Ошибка: Файл orders.log не найден');
}

// Проверка читаемости
if (!is_readable($logFilePath)) {
    http_response_code(403);
    die('Ошибка: Нет доступа к файлу orders.log');
}

// Получаем размер файла
$fileSize = filesize($logFilePath);

// Устанавливаем заголовки для скачивания
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="orders.log"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Очищаем буфер вывода
if (ob_get_level()) {
    ob_end_clean();
}

// Отдаём файл
readfile($logFilePath);
exit;
?>
