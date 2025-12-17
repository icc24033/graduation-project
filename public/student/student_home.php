<?php
session_start();
$status = $_SESSION['subject'] ?? [];
$dsn = $status['dsn'] ?? ''; 
$user = $status['user'] ?? '';
$pass = $status['pass'] ?? '';
$options = $status['options'] ?? [];
$subject_sql_first = $status['subject_sql_first'] ?? '';
$subject_sql_second = $status['subject_sql_second'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <title>ICCスマートキャンパス</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="nofollow,noindex">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Noto+Sans+JP:wght@100..900&display=swap" rel="stylesheet">
    <style>
        #hiddenDateInput { display: none; }
        .no-schedule, .error-msg {
            text-align: center;
            padding: 40px;
            color: #888;
            font-weight: bold;
        }
        .search { cursor: pointer; }

        .dropdown-content {
            display: none;
            width: 100%;
            padding: 15px;
            box-sizing: border-box;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
            margin-top: 10px;
            border-radius: 0 0 8px 8px;
        }
        
        .dropdown-content.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .item-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .item-list li {
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        .item-list li:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php
    date_default_timezone_set('Asia/Tokyo');

    $time_schedule = [
        1 => '9:10 ～ 10:40', 2 => '10:50 ～ 12:20', 3 => '13:10 ～ 14:40',
        4 => '14:50 ～ 16:20', 5 => '16:30 ～ 18:00', 6 => '18:10 ～ 19:40',
    ];

    $day_map_full = [0 => '日', 1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土'];
    $day_map_weekday = ['月', '火', '水', '木', '金'];
    
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

    $selected_course = $_POST['selected_course'] ?? 'system';
    $course_labels = [
        'system' => 'システム', 'web' => 'Web', 'multi' => 'マルチ',
        'ouyou' => '応用情報', 'kihon' => '基本情報',
        'itikumi' => '1年1組', 'nikumi' => '1年2組'
    ];
    $course_label = $course_labels[$selected_course] ?? 'システム';
    ?>

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
                <button type="button" class="course-select dropdown-toggle" id="course-toggle-button" aria-expanded="false">
                    <span id="course-display-text"><?php echo htmlspecialchars($course_label); ?></span>
                    <img class="select button-icon" src="images/chevron-down.svg" alt="">
                </button>
                <input type="hidden" name="selected_course" id="hiddenCourseInput" value="<?php echo htmlspecialchars($selected_course); ?>">

                <div class="dropdown-content course-content" id="course-content">
                    <?php foreach($course_labels as $val => $name): ?>
                        <div class="dropdown-item" data-value="<?php echo $val; ?>"><?php echo $name; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="search">
                <img class="search-img" src="images/search.svg" alt="">
                <p>検索</p>
            </button>
        </div>
    </form>
    
    <p style="margin-top: 20px; font-weight: bold; text-align:center;">
        <?php echo $formatted_full_date; ?><br>
        <?php echo $course_label; ?> の時間割
    </p>

    <main class="main-content" id="schedule-container">
        <div class="schedule-list">
            <?php
            try {
                if(empty($status)) throw new Exception("セッション設定がありません");

                $config = $_SESSION['subject'];
                $db = new PDO($config['dsn'], $config['user'], $config['pass'], $config['options']);

                $target_table = $config['table_map'][$selected_course] ?? $config['table_map']['system'];
                $sql = sprintf($config['base_sql'], $target_table);
                
                $stmt = $db->prepare($sql);
                $stmt->execute([(string)$display_day_jp]);
                $all_schedule = $stmt->fetchAll();

                if (empty($all_schedule)) {
                    echo "<div class='no-schedule'>授業データがありません（{$display_day_jp}曜日）</div>";
                } else {
                    foreach($all_schedule as $item) {
                        $period = (int)$item["period"];
                        $s_name = htmlspecialchars($item["subject_name"] ?? '未設定');
                        $room = htmlspecialchars($item["room"] ?? '');
                        $teacher = htmlspecialchars($item["teacher"] ?? '');
                        $detail = nl2br(htmlspecialchars($item["course_detail"] ?? '詳細情報はありません'));
                        $kadai_id = htmlspecialchars($item["kadai_id"] ?? 'なし');
                        $item_str = $item["course_item"] ?? '特になし';
                        $item_list = preg_split('/[、,，]+/', $item_str, -1, PREG_SPLIT_NO_EMPTY);
                        $time_str = $time_schedule[$period] ?? "時間未定";
                        ?>
                        <section class="card">
                            <div class="info">
                                <div class="subject-details">
                                    <h2 class="subject"><?php echo $s_name; ?></h2>
                                    <p class="room-name"><?php echo $room; ?></p>
                                </div>
                                <div class="period-details">
                                    <p class="period-time"><?php echo $period; ?>限（<?php echo $time_str; ?>）</p>
                                    <div class="button-container">
                                        <div class="dropdown-wrapper detail-dropdown-wrapper">
                                            <button type="button" class="button dropdown-toggle detail-toggle" id="detail-toggle-button-<?php echo $period; ?>" aria-expanded="false">
                                                <p>授業詳細</p>
                                                <img class="button-icon detail-icon" src="images/arrow_right.svg" alt="">
                                            </button>
                                        </div>
                                        <div class="dropdown-wrapper item-dropdown-wrapper">
                                            <button type="button" class="button dropdown-toggle item-toggle" id="item-toggle-button-<?php echo $period; ?>" aria-expanded="false">
                                                <span class="button-text-container"><p>持ってくるもの</p></span>
                                                <img class="button-icon item-icon" src="images/arrow_right.svg" alt="">
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="dropdown-content detail-content" id="detail-content-<?php echo $period; ?>">
                                <div class="detail-box">
                                    <div class="detail-title" style="font-weight:bold; color:#555;">担当教員</div>
                                    <p class="detail-text"><?php echo $teacher; ?></p>
                                </div>
                                <div class="detail-box" style="margin-top:10px;">
                                    <div class="detail-title" style="font-weight:bold; color:#555;">課題</div>
                                    <p class="detail-text"><?php echo $kadai_id; ?></p>
                                </div>
                                <div class="detail-box" style="margin-top:10px;">
                                    <div class="detail-title" style="font-weight:bold; color:#555;">授業の内容:</div>
                                    <p class="detail-text"><?php echo $detail; ?></p>
                                </div>
                            </div>

                            <div class="dropdown-content item-content" id="item-content-<?php echo $period; ?>">
                                <ul class="item-list">
                                    <li style="font-weight:bold;">持ってくるもの</li>
                                    <?php 
                                    if (empty($item_list) || (count($item_list)===1 && trim($item_list[0])==='特になし')) {
                                        echo '<li>特になし</li>';
                                    } else {
                                        foreach($item_list as $idx => $val) {
                                            $val = htmlspecialchars(trim($val));
                                            $chkId = "item-chk-{$period}-{$idx}";
                                            echo "<li><input type='checkbox' id='{$chkId}'><label for='{$chkId}' style='margin-left:5px;'>{$val}</label></li>";
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        </section> <?php
                    } 
                } 
            } catch(Exception $e) {
                echo "<div class='error-msg'>DBエラー: " . htmlspecialchars($e->getMessage()) . "</div>";
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
        const courseWrapper = document.querySelector('.course-select-wrapper');

        courseItems.forEach(item => {
            item.addEventListener('click', function(e) {
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
            const idParts = button.id.split('-');
            const type = idParts[0];
            const num = idParts[idParts.length - 1];
            const contentElement = document.getElementById(`${type}-content-${num}`);
            if(contentElement) contentElement.classList.remove('show');
        }
        const internalContent = wrapper.querySelector('.dropdown-content');
        if(internalContent) internalContent.classList.remove('show');
    }

    function toggleDropdown(button) {
        const wrapper = button.closest('.dropdown-wrapper');
        const isExpanded = button.getAttribute('aria-expanded') === 'true';

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
            const idParts = button.id.split('-');
            const contentId = `${idParts[0]}-content-${idParts[idParts.length - 1]}`;
            const contentElement = document.getElementById(contentId);
            if (contentElement) contentElement.classList.add('show');
            
            const internalContent = wrapper.querySelector('.dropdown-content');
            if (internalContent) internalContent.classList.add('show');
        }
    }
    </script>
</body>
</html>