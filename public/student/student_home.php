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
    <link rel="stylesheet" href="../css/add_style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Noto+Sans+JP:wght@100..900&display=swap" rel="stylesheet">
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
                    <?php echo SecurityHelper::escapeHtml((string)$today_date_value); ?>
                </button>
                <input type="date" id="hiddenDateInput" name="search_date" value="<?php echo SecurityHelper::escapeHtml((string)$today_date_value); ?>">
            </div>

            <div class="dropdown-wrapper course-select-wrapper">
                <button type="button" class="course-select dropdown-toggle" id="course-toggle-button">
                    <span id="course-display-text"><?php echo SecurityHelper::escapeHtml((string)$course_label); ?></span>
                    <img class="select button-icon" src="../images/chevron-down.svg" alt="">
                </button>
                <input type="hidden" name="selected_course" id="hiddenCourseInput" value="<?php echo SecurityHelper::escapeHtml((string)$selected_course); ?>">

                <div class="dropdown-content course-content" id="course-content">
                    <?php foreach($course_labels as $id => $name): ?>
                        <div class="dropdown-item" data-value="<?php echo SecurityHelper::escapeHtml((string)$id); ?>"><?php echo SecurityHelper::escapeHtml((string)$name); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </form>
    
    <p style="margin-top: 20px; font-weight: bold; text-align:center;">
        <?php echo SecurityHelper::escapeHtml((string)$formatted_full_date); ?><br>
        <?php echo SecurityHelper::escapeHtml((string)$course_label); ?> の時間割
    </p>

    <main class="main-content" id="schedule-container">
        <div class="schedule-list">
            <?php
            for ($period = 1; $period <= 5; $period++) {
                $item = $schedule_by_period[$period] ?? null;
                $subject_name = (string)($item["subject_name"] ?? '');
                $class_detail = (string)($item["class_detail"] ?? '詳細情報はありません。');
                $bring_object = (string)($item["bring_object"] ?? '特になし');
                $room         = (string)($item["room_name"] ?? '-');
                $display_title = $subject_name ?: '（授業なし）';
                $time_str = (string)($time_schedule[$period] ?? "時間未定");
                $item_list = preg_split('/[、,，\s\x{3000}]+/u', $bring_object, -1, PREG_SPLIT_NO_EMPTY);
            ?>
            
            <section class="card <?php echo ($item['is_changed'] ?? false) ? 'is-changed' : ''; ?>">
                <div class="info">
                    <div class="subject-details">
                        <h2 class="subject">
                            <?php echo SecurityHelper::escapeHtml($display_title); ?>
                            <?php if ($item['is_changed'] ?? false): ?>
                                <span class="change-badge">時間割変更</span>
                            <?php endif; ?>
                        </h2>
                        <p class="room-name">教室: <?php echo SecurityHelper::escapeHtml($room); ?></p>
                    </div>
                    <div class="period-details">
                        <p class="period-time"><?php echo SecurityHelper::escapeHtml((string)$period); ?>限（<?php echo SecurityHelper::escapeHtml($time_str); ?>）</p>
                        <div class="button-container">
                            <div class="dropdown-wrapper detail-dropdown-wrapper">
                                <button type="button" class="button dropdown-toggle detail-toggle">
                                    <p>授業詳細</p>
                                    <img class="button-icon detail-icon" src="images/arrow_right.svg" alt="">
                                </button>
                                <div class="dropdown-content detail-content">
                                    <div class="detail-box">
                                        <div class="detail-title">内容</div>
                                        <p class="detail-text"><?php echo SecurityHelper::escapeHtml($class_detail); ?></p>
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
                                            <?php $chkId = "item-chk-" . SecurityHelper::escapeHtml((string)$period) . "-" . SecurityHelper::escapeHtml((string)$idx); ?>
                                            <li>
                                                <input type="checkbox" id="<?php echo $chkId; ?>">
                                                <label for="<?php echo $chkId; ?>"><?php echo SecurityHelper::escapeHtml(trim((string)$val)); ?></label>
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