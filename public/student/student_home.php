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
        /* === 日付入力欄を隠すスタイル === */
        #hiddenDateInput { display: none; }
        
        /* エラーメッセージ等のスタイル */
        .no-schedule, .error-msg {
            text-align: center;
            padding: 40px;
            color: #888;
            font-weight: bold;
        }
        .search { cursor: pointer; }
    </style>
</head>
<body>
    <?php
    // =================================================================
    // 設定と初期化
    // =================================================================
    session_start();
    date_default_timezone_set('Asia/Tokyo');

    // ▼▼▼ データベース接続設定 (環境に合わせて変更してください) ▼▼▼
    $db_host = 'localhost';
    $db_name = 'test';      
    $db_user = 'root'; 
    $db_pass = 'root'; 
    
    $dsn = "mysql:dbname={$db_name};host={$db_host};charset=utf8";
    
    // === 時間割の時間定義 ===
    $time_schedule = [
        1 => '9:10 ～ 10:40', 2 => '10:50 ～ 12:20', 3 => '13:10 ～ 14:40',
        4 => '14:50 ～ 16:20', 5 => '16:30 ～ 18:00', 6 => '18:10 ～ 19:40',
    ];

    // --- 日付と曜日の決定ロジック ---
    $day_map_full = [0 => '日', 1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土'];
    $day_map_weekday = ['月', '火', '水', '木', '金'];
    
    // ユーザーが検索ボタンで日付を指定した場合
    if (isset($_POST['search_date']) && !empty($_POST['search_date'])) {
        $display_date_obj = new DateTime($_POST['search_date']);
        $today_php_day_num = (int)$display_date_obj->format('w');
        $display_day_jp = $day_map_full[$today_php_day_num];
    
    } else {
        // 【自動判定モード】
        $current_time = new DateTime();
        $end_time_threshold = new DateTime(date('Y-m-d') . ' 16:20:00'); 
        
        $today_php_day_num = (int)date('w'); 
        $current_day_jp = $day_map_full[$today_php_day_num];
        
        $display_date_obj = clone $current_time; 
        $display_day_jp = $current_day_jp;       
    
        if (in_array($current_day_jp, $day_map_weekday)) {
            if ($current_time >= $end_time_threshold) {
                // 平日かつ16:20過ぎなら翌日へ
                $display_date_obj->modify('+1 day');
                $next_w = (int)$display_date_obj->format('w');
                if ($next_w === 6) { $display_date_obj->modify('+2 days'); }
                if ($next_w === 0) { $display_date_obj->modify('+1 day'); }
            }
        } else {
            // 土日の場合は月曜を表示
            if ($current_day_jp === '土') {
                $display_date_obj->modify('+2 days');
            } elseif ($current_day_jp === '日') {
                $display_date_obj->modify('+1 day');
            }
        }
        // 最終的な曜日を再取得
        $display_day_jp = $day_map_full[(int)$display_date_obj->format('w')];
    }
    
    // 表示用変数
    $today_date_value = $display_date_obj->format('Y-m-d'); 
    $formatted_full_date = $display_date_obj->format('Y/n/j') . " (" . $display_day_jp . ")"; 

    // --- 【重要】コース選択とテーブルの決定 ---
    // POSTがない場合（初期表示）は 'system' をデフォルトにする
    $selected_course = $_POST['selected_course'] ?? 'system';
    
    // 変な値が入ってこないようにチェック
    if (!in_array($selected_course, ['system', 'web', 'multi'])) {
        $selected_course = 'system';
    }

    $target_table = 'subject'; // デフォルト

    switch ($selected_course) {
        case 'itikumi':
            $course_label = '1年1組';
            $target_table = 'itikumi_subject'; // Webコース用テーブル
            break;
        case 'nikumi':
            $course_label = '1年2組';
            $target_table = 'nikkumi_subject'; // Webコース用テーブル
            break;
        case 'kihon':
            $course_label = '基本情報コース';
            $target_table = 'kihon_subject'; // Webコース用テーブル
            break;
        case 'ouyou ':
            $course_label = '応用情報コース';
            $target_table = 'ouyou_subject'; // Webコース用テーブル
            break;
        
        case 'web':
            $course_label = 'Webクリエイタコース';
            $target_table = 'web_subject'; // Webコース用テーブル
            break;
        case 'multi':
            $course_label = 'マルチメディアOAコース';
            $target_table = 'maruti_subject'; // マルチメディアOAコース用テーブル
            break;
        case 'system':
        default:
            $course_label = 'システムデザインコース';
            $target_table = 'subject'; // システムデザインコース用テーブル
            break;
    }
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
                    <img class="select button-icon" src="images/chevron-down.svg" alt="ドロップダウンアイコン">
                </button>
                <input type="hidden" name="selected_course" id="hiddenCourseInput" value="<?php echo htmlspecialchars($selected_course); ?>">

                <div class="dropdown-content course-content" id="course-content" aria-labelledby="course-toggle-button">
                    <div class="dropdown-item" data-value="system">システムデザインコース</div>
                    <div class="dropdown-item" data-value="web">Webクリエイタコース</div>
                    <div class="dropdown-item" data-value="multi">マルチメディアOAコース</div>
                    <div class="dropdown-item" data-value="ouyou">応用情報コース</div>
                    <div class="dropdown-item" data-value="kihon">基本情報コース</div>
                    <div class="dropdown-item" data-value="itikumi">1年1組</div>
                    <div class="dropdown-item" data-value="nikumi">1年2組</div>

                </div>
            </div>

            <button type="submit" class="search">
                <img class="search-img" src="images/search.svg" alt="検索アイコン">
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
            // === データベース検索処理 ===
            try {
                $db = new PDO($dsn, $db_user, $db_pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);

                // 選択されたテーブルから、その曜日のデータを全件取得
                // 1時間目から4時間目などを並び順(ASC)で取得
                $sql = "SELECT * FROM {$target_table} WHERE day_of_week = :day ORDER BY period ASC";
                
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':day', $display_day_jp, PDO::PARAM_STR);
                $stmt->execute();
                
                $all_schedule = $stmt->fetchAll();

                if (empty($all_schedule)) {
                    echo "<div class='no-schedule'>授業データがありません（{$display_day_jp}曜日）</div>";
                } else {
                    // データをループして表示
                    foreach($all_schedule as $item) {
                        // DBのカラム名が正しいか確認してください
                        $s_name   = htmlspecialchars($item["subject_name"] ?? '未設定');
                        $teacher  = htmlspecialchars($item["teacher"] ?? '');
                        $room     = htmlspecialchars($item["room"] ?? '');
                        $period   = (int)$item["period"];
                        $detail   = nl2br(htmlspecialchars($item["course_detail"] ?? '詳細情報はありません。'));
                        
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
                                                <img class="button-icon detail-icon" src="images/arrow_right.svg" alt=">">
                                            </button>
                                            <div class="dropdown-content detail-content" id="detail-content-<?php echo $period; ?>">
                                                <div class="detail-box">
                                                    <div class="detail-title">担当教員</div>
                                                    <p class="detail-text"><?php echo $teacher; ?></p>
                                                </div>
                                                <div class="detail-box">
                                                    <div class="detail-title">内容</div>
                                                    <p class="detail-text"><?php echo $detail; ?></p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="dropdown-wrapper item-dropdown-wrapper">
                                            <button type="button" class="button dropdown-toggle item-toggle" id="item-toggle-button-<?php echo $period; ?>" aria-expanded="false">
                                                <span class="button-text-container">
                                                    <p>持ってくるもの</p>
                                                    <img class="select-icon" src="images/chevron-down.svg" alt="▼">
                                                </span>
                                                <img class="button-icon item-icon" src="images/arrow_right.svg" alt=">">
                                            </button>
                                            <div class="dropdown-content item-content" id="item-content-<?php echo $period; ?>">
                                                <ul class="item-list">
                                                    <li>持ってくるもの<img class="button-icon item-icon" src="images/arrow_drop_down.svg" alt="▼"></li>
                                                    <?php 
                                                    if (empty($item_list) || (count($item_list)===1 && trim($item_list[0])==='特になし')) {
                                                        echo '<li>特になし</li>';
                                                    } else {
                                                        foreach($item_list as $idx => $val) {
                                                            $val = htmlspecialchars(trim($val));
                                                            $chkId = "item-chk-{$period}-{$idx}";
                                                            echo "<li><input type='checkbox' id='{$chkId}'><label for='{$chkId}'>{$val}</label></li>";
                                                        }
                                                    }
                                                    ?>
                                                </ul>
                                            </div>
                                        </div> 
                                    </div>
                                </div>
                            </div>
                        </section>
                        <?php
                    } // end foreach
                } // end else
            } catch(PDOException $e) {
                echo "<div class='error-msg'>DB接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            ?>
        </div>
    </main>

    <script>
    "use strict";

    // === 日付ボタン制御 ===
    const dateBtn = document.getElementById('dateTriggerBtn');
    const dateInput = document.getElementById('hiddenDateInput');
    
    if(dateBtn && dateInput){
        dateBtn.addEventListener('click', () => {
            if (dateInput.showPicker) { 
                dateInput.showPicker(); 
            } else {
                dateInput.style.display = 'block'; 
                dateInput.focus();
                dateInput.click();
                dateInput.style.display = 'none';
            }
        });
        
        dateInput.addEventListener('change', () => {
            if (dateInput.value) { 
                dateBtn.textContent = dateInput.value; 
            }
        });
    }

    // === コース選択制御 ===
    document.addEventListener('DOMContentLoaded', function() {
        const courseItems = document.querySelectorAll('.course-select-wrapper .dropdown-item');
        const hiddenCourseInput = document.getElementById('hiddenCourseInput');
        const courseDisplayText = document.getElementById('course-display-text');
        const courseWrapper = document.querySelector('.course-select-wrapper');

        // コースのドロップダウン項目をクリックした時の動作
        courseItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const value = this.getAttribute('data-value'); // "system" や "web"
                const label = this.textContent;
                
                // 1. 隠しフィールドに値をセット
                hiddenCourseInput.value = value;
                courseDisplayText.textContent = label;
                
                // 2. ドロップダウンを閉じる
                closeDropdown(courseWrapper);

                // ★重要: ここでフォームを送信します！
                // これによりページが再読み込みされ、PHPが新しいコースデータをMySQLから取得します。
                document.getElementById('mainForm').submit();
            });
        });
    });

    // === ドロップダウン開閉ロジック（UI制御） ===
    function closeDropdown(wrapper) {
        const button = wrapper.querySelector('.dropdown-toggle');
        const contentElement = wrapper.querySelector('.dropdown-content');
        if(contentElement) contentElement.classList.remove('show');
        wrapper.classList.remove('active');
        if(button) button.setAttribute('aria-expanded', 'false');
    }
    
    function toggleDropdown(button) {
        const wrapper = button.closest('.dropdown-wrapper');
        const isDetailButton = button.classList.contains('detail-toggle');
        
        let contentElement;
        const idParts = button.id ? button.id.split('-') : [];
        const idNumber = idParts.pop();

        if (isDetailButton) {
            contentElement = document.getElementById(`detail-content-${idNumber}`);
        } else if (button.classList.contains('item-toggle')) {
            contentElement = document.getElementById(`item-content-${idNumber}`);
        } else if (wrapper.classList.contains('course-select-wrapper')) {
            contentElement = wrapper.querySelector('.dropdown-content');
        } else { return; }
        
        if(!contentElement) return;

        const isExpanded = button.getAttribute('aria-expanded') === 'true';

        // 他のドロップダウンを閉じる処理
        if (isDetailButton || button.classList.contains('item-toggle')) {
            const card = button.closest('.card');
            if(card) {
                const otherWrapperSelector = isDetailButton ? '.item-dropdown-wrapper' : '.detail-dropdown-wrapper';
                const otherWrapper = card.querySelector(otherWrapperSelector);
                if (otherWrapper && otherWrapper.classList.contains('active')) {
                    closeDropdown(otherWrapper);
                }
            }
        } else if (wrapper.classList.contains('course-select-wrapper')) {
            document.querySelectorAll('.dropdown-wrapper').forEach(w => { 
                if (w !== wrapper) closeDropdown(w); 
            });
        }

        if (isExpanded) { 
            closeDropdown(wrapper); 
        } else { 
            wrapper.classList.add('active'); 
            button.setAttribute('aria-expanded', 'true'); 
            contentElement.classList.add('show'); 
        }
    }
    
    // 初期ロード時のボタン設定
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.dropdown-toggle');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() { toggleDropdown(this); });
        });
    });
    </script>
</body>
</html>