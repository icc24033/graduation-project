<?php

/*
session_start();
$status = $_SESSION['timetable_details'] ?? [];
$dsn = $status['dsn'] ?? ''; 
$user = $status['user'] ?? '';
$pass = $status['pass'] ?? '';
$options = $status['options'] ?? [];
$subject_sql_first = $status['subject_sql_first'] ?? '';
$subject_sql_second = $status['subject_sql_second'] ?? '';
*/

// student_home.php

// 1. 基本設定
date_default_timezone_set('Asia/Tokyo');

/**
 * $courseList の内容に基づいて表示用ラベルを生成
 * var_dumpの内容: [ ["course_id"=>1, "course_name"=>"システムデザインコース"], ... ]
 */
$course_labels = [];
if (isset($courseList) && is_array($courseList)) {
    foreach ($courseList as $course) {
        $course_labels[$course['course_id']] = $course['course_name'];
    }
}

// コース選択状態の管理（POSTがなければデフォルトでID:1を選択）
$selected_course = isset($_POST['selected_course']) ? (int)$_POST['selected_course'] : 1;
$course_label = $course_labels[$selected_course] ?? 'コース不明';

// 2. 時限ごとの時間帯定義
$time_schedule = [
    1 => '9:10 ～ 10:40', 2 => '10:50 ～ 12:20', 3 => '13:10 ～ 14:40',
    4 => '14:50 ～ 16:20', 5 => '16:30 ～ 18:00', 6 => '18:10 ～ 19:40',
];

$day_map_full = [0 => '日', 1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土'];
$day_map_weekday = ['月', '火', '水', '木', '金'];

// 3. 表示する日付の決定ロジック
if (isset($_POST['search_date']) && !empty($_POST['search_date'])) {
    $display_date_obj = new DateTime($_POST['search_date']);
} else {
    $current_time = new DateTime();
    $end_time_threshold = new DateTime(date('Y-m-d') . ' 16:20:00'); 
    $display_date_obj = clone $current_time; 
    $current_day_jp = $day_map_full[(int)$display_date_obj->format('w')];

    if (in_array($current_day_jp, $day_map_weekday)) {
        if ($current_time >= $end_time_threshold) {
            $display_date_obj->modify('+1 day');
            $next_w = (int)$display_date_obj->format('w');
            if ($next_w === 6) { $display_date_obj->modify('+2 days'); }
            if ($next_w === 0) { $display_date_obj->modify('+1 day'); }
        }
    } else {
        if ($current_day_jp === '土') { $display_date_obj->modify('+2 days'); }
        elseif ($current_day_jp === '日') { $display_date_obj->modify('+1 day'); }
    }
}

$display_day_jp = $day_map_full[(int)$display_date_obj->format('w')];
$today_date_value = $display_date_obj->format('Y-m-d'); 
$formatted_full_date = $display_date_obj->format('Y/n/j') . " (" . $display_day_jp . ")"; 
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <title>ICCスマートキャンパス</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="nofollow,noindex">
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Noto+Sans+JP:wght@100..900&display=swap" rel="stylesheet">
    <style>
        #hiddenDateInput { display: none; }
        .no-schedule, .error-msg { text-align: center; padding: 40px; color: #888; font-weight: bold; }
        .search { cursor: pointer; }
        .dropdown-content { display: none; width: 100%; padding: 15px; box-sizing: border-box; background-color: #f9f9f9; border-top: 1px solid #eee; margin-top: 10px; border-radius: 0 0 8px 8px; }
        .dropdown-content.show { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        .item-list { list-style: none; padding: 0; margin: 0; }
        .item-list li { padding: 8px 0; border-bottom: 1px solid #ddd; }
        .item-list li:last-child { border-bottom: none; }
        #dateTriggerBtn.date-btn { color: #ffffff !important; background-color: #495666; font-weight: bold !important; }
        .detail-box { display: flex; align-items: stretch; background-color: #d1e7ff; border-radius: 12px; overflow: hidden; margin: 5px 0; }
        .detail-title { display: flex; align-items: center; justify-content: center; flex-shrink: 0; width: 60px; font-weight: bold; color: #333; font-size: 0.9em; border-right: 1px solid rgba(0, 0, 0, 0.1); }
        .detail-text { flex-grow: 1; padding: 15px; margin: 0; line-height: 1.6; font-size: 0.95em; color: #333; white-space: pre-wrap; text-align: left; }
        .dropdown-content.detail-content { background-color: #ffffff; border: 1px solid #ddd; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <header class="page-header">
        <p>ICCスマートキャンパス</p>
        <div class="user-icon">
            <div class="i_head"></div>
            <div class="i_body"></div>
        </div>
    </header>

    <form method="POST" action="" id="mainForm">
        <div class="action-buttons">
            <div class="date-container">
                <button type="button" id="dateTriggerBtn" class="date-btn">
                    <?php echo htmlspecialchars($today_date_value); ?>
                </button>
                <input type="date" id="hiddenDateInput" name="search_date" value="<?php echo htmlspecialchars($today_date_value); ?>">
            </div>

            <div class="dropdown-wrapper course-select-wrapper">
                <button type="button" class="course-select dropdown-toggle" id="course-toggle-button">
                    <span id="course-display-text"><?php echo htmlspecialchars($course_label); ?></span>
                    <img class="select button-icon" src="../images/chevron-down.svg" alt="">
                </button>
                <input type="hidden" name="selected_course" id="hiddenCourseInput" value="<?php echo htmlspecialchars($selected_course); ?>">

                <div class="dropdown-content course-content" id="course-content">
                    <?php foreach($course_labels as $id => $name): ?>
                        <div class="dropdown-item" data-value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="search">
                <img class="search-img" src="../images/search.svg" alt="">
                <p>検索</p>
            </button>
        </div>
    </form>
    
    <p style="margin-top: 20px; font-weight: bold; text-align:center;">
        <?php echo $formatted_full_date; ?><br>
        <?php echo htmlspecialchars($course_label); ?> の時間割
    </p>

   <main class="main-content" id="schedule-container">
    <div class="schedule-list">
        <?php
        try {
            $dsn  = 'mysql:host=localhost;dbname=icc_smart_campus;charset=utf8';
            $user = 'root';
            $pass = 'root'; 
            $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

            $day_to_num = ['月' => 1, '火' => 2, '水' => 3, '木' => 4, '金' => 5, '土' => 6, '日' => 7];
            $target_day_num = $day_to_num[$display_day_jp] ?? 1;

            // $selected_course はすでに course_id (数値) になっているためそのまま使用
            $target_timetable_id = $selected_course;

            $sql = "SELECT td.*, s.subject_name 
                    FROM timetable_details td
                    LEFT JOIN subjects s ON td.subject_id = s.subject_id
                    WHERE td.day_of_week = :day 
                    AND td.timetable_id = :tid 
                    ORDER BY td.period ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':day' => $target_day_num, ':tid' => $target_timetable_id]);
            $fetched_data = $stmt->fetchAll();

            $schedule_by_period = [];
            foreach ($fetched_data as $row) {
                $schedule_by_period[$row['period']] = $row;
            }

            for ($period = 1; $period <= 4; $period++) {
                $item = $schedule_by_period[$period] ?? null;
                $subject_name = htmlspecialchars($item["subject_name"] ?? '');
                $class_detail = htmlspecialchars($item["class_detail"] ?? '詳細情報はありません。');
                $bring_object = htmlspecialchars($item["bring_object"] ?? '特になし');
                $room         = htmlspecialchars($item["room_name"] ?? '-');
                $display_title = $subject_name ?: '（授業なし）';
                $time_str = $time_schedule[$period] ?? "時間未定";
                $item_list = preg_split('/[、,，\s\x{3000}]+/u', $bring_object, -1, PREG_SPLIT_NO_EMPTY);
            ?>
            
            <section class="card">
                <div class="info">
                    <div class="subject-details">
                        <h2 class="subject"><?php echo $display_title; ?></h2>
                        <p class="room-name">教室: <?php echo $room; ?></p>
                    </div>
                    <div class="period-details">
                        <p class="period-time"><?php echo $period; ?>限（<?php echo $time_str; ?>）</p>
                        <div class="button-container">
                            <div class="dropdown-wrapper detail-dropdown-wrapper">
                                <button type="button" class="button dropdown-toggle detail-toggle" id="detail-toggle-button-<?php echo $period; ?>">
                                    <p>授業詳細</p>
                                    <img class="button-icon detail-icon" src="images/arrow_right.svg" alt="">
                                </button>
                                <div class="dropdown-content detail-content" id="detail-content-<?php echo $period; ?>">
                                    <div class="detail-box">
                                        <div class="detail-title">内容</div>
                                        <p class="detail-text"><?php echo $class_detail; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-wrapper item-dropdown-wrapper">
                                <button type="button" class="button dropdown-toggle item-toggle" id="item-toggle-button-<?php echo $period; ?>">
                                    <p>持ってくるもの</p>
                                    <img class="button-icon item-icon" src="images/arrow_right.svg" alt="">
                                </button>
                                <div class="dropdown-content item-content" id="item-content-<?php echo $period; ?>">
                                    <ul class="item-list">
                                        <?php foreach($item_list as $idx => $val): ?>
                                            <?php $chkId = "item-chk-{$period}-{$idx}"; ?>
                                            <li>
                                                <input type="checkbox" id="<?php echo $chkId; ?>">
                                                <label for="<?php echo $chkId; ?>"><?php echo trim($val); ?></label>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php } // for end 
        } catch(Exception $e) {
            echo "<div class='error-msg'>エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>
</main>

<script>
    "use strict";

    const dateBtn = document.getElementById('dateTriggerBtn');
    const dateInput = document.getElementById('hiddenDateInput');
    if(dateBtn && dateInput){
        dateBtn.addEventListener('click', () => {
            if (dateInput.showPicker) { dateInput.showPicker(); } 
            else { dateInput.click(); }
        });
        dateInput.addEventListener('change', () => { document.getElementById('mainForm').submit(); });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const courseItems = document.querySelectorAll('.course-select-wrapper .dropdown-item');
        const hiddenCourseInput = document.getElementById('hiddenCourseInput');

        courseItems.forEach(item => {
            item.addEventListener('click', function() {
                // ここで course_id (数値) がセットされ、フォームが送信される
                hiddenCourseInput.value = this.getAttribute('data-value');
                document.getElementById('mainForm').submit();
            });
        });

        const toggleButtons = document.querySelectorAll('.dropdown-toggle');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() { toggleDropdown(this); });
        });
    });

    function closeDropdown(wrapper) {
        wrapper.classList.remove('active');
        const button = wrapper.querySelector('.dropdown-toggle');
        if(button) {
            button.setAttribute('aria-expanded', 'false');
            const internalContent = wrapper.querySelector('.dropdown-content');
            if(internalContent) internalContent.classList.remove('show');
        }
    }

    function toggleDropdown(button) {
        const wrapper = button.closest('.dropdown-wrapper');
        const isExpanded = wrapper.classList.contains('active');

        if (button.classList.contains('detail-toggle') || button.classList.contains('item-toggle')) {
            const card = button.closest('.card');
            const otherClass = button.classList.contains('detail-toggle') ? '.item-dropdown-wrapper' : '.detail-dropdown-wrapper';
            const otherWrapper = card.querySelector(otherClass);
            if (otherWrapper) closeDropdown(otherWrapper);
        }

        if (isExpanded) {
            closeDropdown(wrapper);
        } else {
            wrapper.classList.add('active');
            button.setAttribute('aria-expanded', 'true');
            const internalContent = wrapper.querySelector('.dropdown-content');
            if(internalContent) internalContent.classList.add('show');
        }
    }
</script>
</body>
</html>