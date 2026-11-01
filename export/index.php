<?php
/**
 * Главная страница экспорта заявок в Excel
 * Версия 3.0 - Фильтрация по номинациям (reason)
 * Автоматическая фильтрация: текущий год + следующий год
 */

// Путь к лог-файлу от корня сайта
$logFilePath = '../core/components/artrequest/orders.log';

// Функция для парсинга print_r() формата в массив
function parsePrintRLog($filePath) {
    if (!file_exists($filePath)) {
        return array('error' => 'Файл orders.log не найден');
    }
    
    $content = file_get_contents($filePath);
    if ($content === false) {
        return array('error' => 'Ошибка чтения файла');
    }
    
    $orders = array();
    
    // Разделяем по "Array\n(" - начало каждой записи
    $entries = preg_split('/^Array\s*\(/m', $content);
    
    foreach ($entries as $entry) {
        if (empty(trim($entry))) {
            continue;
        }

if (!function_exists('removeDuplicates')) {
/**
 * Удаление дубликатов из массива данных
 * Сравнивает все поля записи для определения дубликата
 */
function removeDuplicates($data) {
    $unique = array();
    $hashes = array();
    
    foreach ($data as $row) {
        // Создаём хэш из всех полей записи
        $hash = md5(serialize($row));
        
        // Если такой хэш ещё не встречался, добавляем запись
        if (!isset($hashes[$hash])) {
            $hashes[$hash] = true;
            $unique[] = $row;
        }
    }
    
    return $unique;
}
}

        $order = array();
        
        // Извлекаем время
        if (preg_match('/\[time\]\s*=>\s*(.+)$/m', $entry, $matches)) {
            $order['time'] = trim($matches[1]);
            $order['created'] = strtotime($order['time']);
        }
        
        // Извлекаем clientemail
        if (preg_match('/\[clientemail\]\s*=>\s*(.+)$/m', $entry, $matches)) {
            $order['clientemail'] = trim($matches[1]);
        }
        
        // Извлекаем основные contacts
        if (preg_match('/\[contacts\]\s*=>\s*(.+?)(?=\s*\[)/s', $entry, $matches)) {
            $order['contacts'] = trim($matches[1]);
        }
        
        // Парсим persons
        $order['persons'] = array();
        
        // Находим блок persons
        if (preg_match('/\[persons\]\s*=>\s*Array\s*\((.*?)\[orgpersons\]/s', $entry, $personsBlock)) {
            $personsText = $personsBlock[1];
            
            // Разделяем на отдельных persons по [0], [1], [2] и т.д.
            preg_match_all('/\[(\d+)\]\s*=>\s*Array\s*\((.*?)(?=\[\d+\]|\[orgpersons\]|$)/s', $personsText, $personMatches, PREG_SET_ORDER);
            
            foreach ($personMatches as $personMatch) {
                $personText = $personMatch[2];
                $person = array();
                
                // Извлекаем поля person
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
        
        // Парсим orgpersons (аналогично)
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

// Маппинг номинаций
$reasonMap = array(
    '0' => 'Профессиональные художники',
    '1' => 'Студенты художественных ВУЗов',
    '2' => 'Непрофессиональные художники',
    '3' => 'Юные художники'
);

// Получаем все заявки
$orders = parsePrintRLog($logFilePath);

if (isset($orders['error'])) {
    die('<h2>Ошибка: ' . $orders['error'] . '</h2>');
}

// Определяем текущий и следующий год
$currentYear = date('Y');
$nextYear = $currentYear + 1;

// Фильтруем заявки по текущему и следующему году
$filteredOrders = array();
foreach ($orders as $order) {
    if (isset($order['created'])) {
        $year = date('Y', $order['created']);
        if ($year == $currentYear || $year == $nextYear) {
            $filteredOrders[] = $order;
        }
    }
}

// Подсчитываем статистику по номинациям
$reasonStats = array();
foreach ($filteredOrders as $order) {
    // Проверяем persons
    if (isset($order['persons'])) {
        foreach ($order['persons'] as $person) {
            if (isset($person['reason'])) {
                $reason = $person['reason'];
                if (!isset($reasonStats[$reason])) {
                    $reasonStats[$reason] = 0;
                }
                $reasonStats[$reason]++;
            }
        }
    }
    
    // Проверяем orgpersons
    if (isset($order['orgpersons'])) {
        foreach ($order['orgpersons'] as $person) {
            if (isset($person['reason'])) {
                $reason = $person['reason'];
                if (!isset($reasonStats[$reason])) {
                    $reasonStats[$reason] = 0;
                }
                $reasonStats[$reason]++;
            }
        }
    }
}

// Удаляем дубликаты из отфильтрованных заявок
$filteredOrders = removeDuplicates($filteredOrders);

// Сортируем статистику по reason
// Удаляем дубликаты из отфильтрованных заявок
// (дубликаты могут быть в persons и orgpersons)
$filteredOrders = removeDuplicates($filteredOrders);

// Пересчитываем статистику после удаления дубликатов
$reasonStats = array();
foreach ($filteredOrders as $order) {
    if (isset($order["persons"])) {
        foreach ($order["persons"] as $person) {
            if (isset($person["reason"])) {
                $reason = $person["reason"];
                if (!isset($reasonStats[$reason])) {
                    $reasonStats[$reason] = 0;
                }
                $reasonStats[$reason]++;
            }
        }
    }
    if (isset($order["orgpersons"])) {
        foreach ($order["orgpersons"] as $person) {
            if (isset($person["reason"])) {
                $reason = $person["reason"];
                if (!isset($reasonStats[$reason])) {
                    $reasonStats[$reason] = 0;
                }
                $reasonStats[$reason]++;
            }
        }
    }
}


ksort($reasonStats);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Экспорт заявок в Excel «Семья – душа России»</title>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 700px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
                margin-top: 25px;
            border-radius: 5px;
        }
        
        .info-box p {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .info-box strong {
            color: #667eea;
        }
        
        .year-display {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            border: 2px solid #e0e0e0;
        }
        
        .year-display strong {
            color: #667eea;
            font-size: 18px;
        }
        
        .download-log-btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
            border: none;
            cursor: pointer;
        }
        
        .download-log-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
            background: linear-gradient(135deg, #0e8074 0%, #2ed968 100%);
        }
        
        .download-log-btn:active {
            transform: translateY(0);
        }
        
        .log-download-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f9fafb;
            border-radius: 10px;
            border: 2px dashed #11998e;
        }
        
        .log-download-section p {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            color: #333;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        select:hover {
            border-color: #667eea;
        }
        
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        button {
            flex: 1;
            padding: 14px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-preview {
            background: #667eea;
            color: white;
        }
        
        .btn-preview:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .stats-card {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e0e0e0;
        }
        
        .stats-number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #666;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .total-stats {
            background: #667eea;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
        }
        
        .total-stats .stats-number {
            color: white;
            font-size: 36px;
        }
        
        .total-stats .stats-label {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
		
		
		.info-box-2 {
		    font-size: 13px;
			text-align: center;
			color: #4c4c4c;
			padding: 0 100px;
		}
		
		
		
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Экспорт заявок в Excel</h1>
        <p class="subtitle">Система экспорта художественных работ конкурса «Семья – душа России»</p>
        <div class="year-display">
            <strong>📅 <?php echo $currentYear; ?> год</strong>
        </div>
        
<form method="GET" id="exportForm">
    <div class="form-group">
        <label for="reason">Выберите номинацию:</label>
        <select name="reason" id="reason" required>
            <option value="">-- Выберите номинацию --</option>
            <?php foreach ($reasonMap as $reasonId => $reasonName): ?>
                <option value="<?php echo $reasonId; ?>">
                    <?php echo $reasonName; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="button-group">
        <!-- Кнопка "Смотреть миниатюры" → preview.php -->
        <button type="button" class="btn-preview zip" data-action="preview">
            Смотреть миниатюры
        </button>
        <br>
        <!-- Кнопка "Смотреть оригиналы" → preview_original.php -->
        <button type="button" class="btn-preview org" data-action="preview_original">
            Смотреть оригиналы
        </button>
    </div>
</form>
        
       
        
        <div class="info-box">
         <p><strong>«Миниатюры»</strong> - это уменьшенные в размере картинки. Они уменьшают размер создаваемого файла и время создания. Создание миниатюр происходит при просмотре страниц и увеличивает время их загрузки. При первом экспорте создаются тоже, время создания от 1-3 мин и зависит от количества заявок. При повторном просмотре страниц, время загрузки и создание файлов уменьшится. Экспорт можно делать всех заявок сразу в номинации фильтра.</p>
<br>
<p><strong>«Оригиналы»</strong> - это реальные в размере картинки. Увеличенный размер файла создаст большие размеры файлов Excel и увеличит время их создания. Экспорт можно делать только по частям 25 или 50, чтобы не превысить нагрузку и тайм-аута сервера. Если делать по 100 то может быть ошибка, зависит от размера загруженных файлов.</p>
    </div>       
  <!-- Секция скачивания лог-файла -->
        <div class="info-box-2">
		<br>
            <p>Для проверки и анализа полученных данных, можно скачать лог файл заявок по данной <a href="download_log.php" class="_download-log-btn">ссылке</a></p>            
        </div>      
    </div>
    
    <script>
/*          // Валидация формы
        document.getElementById('exportForm').addEventListener('submit', function(e) {
            var reason = document.getElementById('reason').value;
            if (!reason) {
                e.preventDefault();
                alert('Пожалуйста, выберите номинацию!');
            }
        });  */
    </script>
<!-- ИНДИКАТОР ДЛЯ INDEX.PHP - START -->

<style>
    /* Оверлей */
    #index-loader {
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
    
    #index-loader.active {
        display: flex;
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* Контейнер */
    .index-loader-box {
        text-align: center;
        background: #fff;
        padding: 40px 60px;
        border-radius: 20px;
        box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
        max-width: 450px;
    }
    
    /* Спиннер */
    .index-spinner {
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
    .index-title {
        font-size: 22px;
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
    }
    
    .index-subtitle {
        font-size: 14px;
        color: #666;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    
    /* Прогресс-бар с шагами */
    .index-progress-steps {
        margin-top: 25px;
        text-align: left;
    }
    
    .index-step {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        font-size: 13px;
        color: #999;
        transition: all 0.3s ease;
    }
    
    .index-step.active {
        color: #3498db;
        font-weight: 600;
    }
    
    .index-step.completed {
        color: #2ecc71;
    }
    
    .index-step-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 12px;
        transition: all 0.3s ease;
    }
    
    .index-step.active .index-step-icon {
        background: #3498db;
        color: white;
        animation: pulse 1s ease-in-out infinite;
    }
    
    .index-step.completed .index-step-icon {
        background: #2ecc71;
        color: white;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    /* Иконка */
    .index-icon {
        font-size: 48px;
        margin-bottom: 15px;
        animation: bounce 1s ease-in-out infinite;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    
    /* Таймер */
    .index-timer {
        font-size: 12px;
        color: #999;
        margin-top: 15px;
    }
</style>

<div id="index-loader">
    <div class="index-loader-box">
        <div class="index-icon" id="index-icon">📊</div>
        <div class="index-spinner"></div>
        <div class="index-title" id="index-title">Загрузка...</div>
        <div class="index-subtitle" id="index-subtitle">Подготовка данных...</div>
        
        <!-- Прогресс-бар с шагами -->
        <div class="index-progress-steps" id="index-steps">
            <div class="index-step" id="step1">
                <div class="index-step-icon">1</div>
                <span>Проверка номинации</span>
            </div>
            <div class="index-step" id="step2">
                <div class="index-step-icon">2</div>
                <span>Загрузка данных</span>
            </div>
            <div class="index-step" id="step3">
                <div class="index-step-icon">3</div>
                <span>Подготовка предпросмотра</span>
            </div>
        </div>
        
        <div class="index-timer" id="index-timer">Прошло: 0 сек</div>
    </div>
</div>

<script>
$(document).ready(function() {
    var timerInterval;
    var elapsedSeconds = 0;
    
    // ============================================
    // ОБРАБОТКА КЛИКОВ НА КНОПКИ
    // ============================================
    $('.btn-preview').on('click', function() {
        var action = $(this).data('action'); // 'preview' или 'preview_original'
        var reason = $('#reason').val();
        
        // Проверка выбора номинации
        if (!reason || reason === '') {
            alert('⚠️ Пожалуйста, выберите номинацию!');
            return false;
        }
        
        // Определяем параметры индикатора
        var title, subtitle, icon, steps;
        
        if (action === 'preview_original') {
            title = '🖼️ Загрузка';
            subtitle = 'Подготовка изображений...';
            icon = '🖼️';
            steps = [
                'Проверка номинации',
                'Загрузка изображений',
                'Подготовка (может занять время)'
            ];
        } else {
            title = '🔍 Загрузка';
            subtitle = 'Генерация миниатюр изображений...';
            icon = '🔍';
            steps = [
                'Проверка номинации',
                'Загрузка данных',
                'Генерация миниатюр'
            ];
        }
        
        // Показываем индикатор
        showIndexLoader(title, subtitle, icon, steps);
        
        // Переходим на нужную страницу
        window.location.href = action + '.php?reason=' + reason;
    });
    
    // ============================================
    // ФУНКЦИЯ ПОКАЗА ИНДИКАТОРА
    // ============================================
    function showIndexLoader(title, subtitle, icon, steps) {
        $('#index-title').text(title);
        $('#index-subtitle').text(subtitle);
        $('#index-icon').text(icon);
        $('#index-loader').addClass('active');
        
        // Сброс шагов
        $('.index-step').removeClass('active completed');
        elapsedSeconds = 0;
        $('#index-timer').text('Прошло: 0 сек');
        
        // Обновляем текст шагов
        steps.forEach(function(stepText, index) {
            $('#step' + (index + 1) + ' span').text(stepText);
        });
        
        // Запускаем анимацию шагов
        animateSteps();
        
        // Запускаем таймер
        startIndexTimer();
    }
    
    // ============================================
    // АНИМАЦИЯ ШАГОВ
    // ============================================
    function animateSteps() {
        var stepDuration = 1500; // 1.5 секунды на шаг
        
        function activateStep(stepNum) {
            if (stepNum > 1) {
                $('#step' + (stepNum - 1)).removeClass('active').addClass('completed');
            }
            
            if (stepNum <= 3) {
                $('#step' + stepNum).addClass('active');
                
                setTimeout(function() {
                    if ($('#index-loader').hasClass('active')) {
                        activateStep(stepNum + 1);
                    }
                }, stepDuration);
            }
        }
        
        // Начинаем с первого шага
        setTimeout(function() {
            activateStep(1);
        }, 300);
    }
    
    // ============================================
    // ТАЙМЕР
    // ============================================
    function startIndexTimer() {
        timerInterval = setInterval(function() {
            elapsedSeconds++;
            $('#index-timer').text('Прошло: ' + elapsedSeconds + ' сек');
        }, 1000);
    }
    
    function stopIndexTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }
    
    // ============================================
    // АВТОМАТИЧЕСКОЕ СКРЫТИЕ ЧЕРЕЗ 30 СЕКУНД
    // ============================================
    setTimeout(function() {
        if ($('#index-loader').hasClass('active')) {
            $('#index-loader').removeClass('active');
            stopIndexTimer();
        }
    }, 30000);
});
</script>


</body>
</html>
