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
        </div>
    </form>
    
    <p style="margin-top: 20px; font-weight: bold; text-align:center;">
        <?php echo $formatted_full_date; ?><br>
        <?php echo htmlspecialchars($course_label); ?> の時間割
    </p>

    <main class="main-content" id="schedule-container">
        <div class="schedule-list">
            <?php
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
                                <button type="button" class="button dropdown-toggle detail-toggle">
                                    <p>授業詳細</p>
                                    <img class="button-icon detail-icon" src="images/arrow_right.svg" alt="">
                                </button>
                                <div class="dropdown-content detail-content">
                                    <div class="detail-box">
                                        <div class="detail-title">内容</div>
                                        <p class="detail-text"><?php echo $class_detail; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-wrapper item-dropdown-wrapper">
                                <button type="button" class="button dropdown-toggle item-toggle">
                                    <p>持ってくるもの</p>
                                    <img class="button-icon item-icon" src="images/arrow_right.svg" alt="">
                                </button>
                                <div class="dropdown-content item-content">
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
            
            <?php } ?>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>