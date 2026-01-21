<?php
// student_home.php


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

        /* 非表示の日時入力フィールド */
        #hiddenDateInput { display: none; }
        .no-schedule, .error-msg {
            text-align: center;
            padding: 40px;
            color: #888;
            font-weight: bold;
        }
        .search { cursor: pointer; }

        /* ドロップダウンメニューのスタイル */
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
        
        /* ドロップダウンメニューの表示アニメーション */
        .dropdown-content.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* 持ってくるものリストのスタイル */
        .item-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        /* リストアイテムのスタイル */
        .item-list li {
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }

        /* 最後のリストアイテムのボーダーを削除 */
        .item-list li:last-child {
            border-bottom: none;
        }
        /* 日付ボタンの文字色を強制的に変える */
        #dateTriggerBtn.date-btn {
            color: #ffffff !important;   /* ゴールド（黄色系） */
            background-color: #495666;   /* 背景色 */
            font-weight: bold !important; /* 太字 */
        }
        /* 授業詳細ボックスの改善 */
        .detail-box {
            display: flex;
            align-items: stretch; /* 高さを揃える */
            background-color: #d1e7ff; /* 画像に近い薄い青色 */
            border-radius: 12px;
            overflow: hidden;
            margin: 5px 0;
        }

        .detail-title {
            display: flex;
            align-items: center;  /* 「内容」を垂直中央に */
            justify-content: center;
            flex-shrink: 0;       /* 幅を固定 */
            width: 60px;          /* 「内容」ラベルの幅 */
            font-weight: bold;
            color: #333;
            font-size: 0.9em;
            border-right: 1px solid rgba(0, 0, 0, 0.1);
        }

        .detail-text {
            flex-grow: 1;         /* 残りの幅をすべて使う */
            padding: 15px;        /* 文章の周りに余裕を持たせる */
            margin: 0;
            line-height: 1.6;     /* 行間を広くして読みやすく */
            font-size: 0.95em;
            color: #333;
            white-space: pre-wrap; /* 改行を有効にする */
            text-align: left;     /* 左寄せを強制 */
        }

        /* ドロップダウンメニュー内の余白調整 */
        .dropdown-content.detail-content {
            background-color: #ffffff; /* 背景を白にして読みやすく */
            border: 1px solid #ddd;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <?php
    // student_home.php

    // 1. 基本設定と時間割の定義
    date_default_timezone_set('Asia/Tokyo');

    // 時限ごとの時間帯定義
    $time_schedule = [
        1 => '9:10 ～ 10:40', 2 => '10:50 ～ 12:20', 3 => '13:10 ～ 14:40',
        4 => '14:50 ～ 16:20', 5 => '16:30 ～ 18:00', 6 => '18:10 ～ 19:40',
    ];

    $day_map_full = [0 => '日', 1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土'];
    $day_map_weekday = ['月', '火', '水', '木', '金'];

    // 2. 表示する日付の決定ロジック
    if (isset($_POST['search_date']) && !empty($_POST['search_date'])) {
        // ユーザーがカレンダーから日付を選択した場合
        $display_date_obj = new DateTime($_POST['search_date']);
    } else {
        // 初期表示：現在の時間に基づいて自動判定
        $current_time = new DateTime();
        $end_time_threshold = new DateTime(date('Y-m-d') . ' 16:20:00'); // 4限終了目安
        $display_date_obj = clone $current_time; 
        $current_day_jp = $day_map_full[(int)$display_date_obj->format('w')];

        // 平日の16:20以降は翌日の時間割を表示（土日は月曜にスキップ）
        if (in_array($current_day_jp, $day_map_weekday)) {
            if ($current_time >= $end_time_threshold) {
                $display_date_obj->modify('+1 day');
                $next_w = (int)$display_date_obj->format('w');
                if ($next_w === 6) { $display_date_obj->modify('+2 days'); } // 土曜なら月曜へ
                if ($next_w === 0) { $display_date_obj->modify('+1 day'); }  // 日曜なら月曜へ
            }
        } else {
            // 現在が土日の場合は直後の月曜日を表示
            if ($current_day_jp === '土') { $display_date_obj->modify('+2 days'); }
            elseif ($current_day_jp === '日') { $display_date_obj->modify('+1 day'); }
        }
    }

    // 表示用データの整形
    $display_day_jp = $day_map_full[(int)$display_date_obj->format('w')];
    $today_date_value = $display_date_obj->format('Y-m-d'); 
    $formatted_full_date = $display_date_obj->format('Y/n/j') . " (" . $display_day_jp . ")"; 

    // 3. コース選択状態の管理
    $selected_course = $_POST['selected_course'] ?? 'user_course';
    $course_labels = [
        'system' => 'システムデザインコース', 'web' => 'Web', 'multi' => 'マルチ',
        'ouyou' => '応用情報', 'kihon' => '基本情報', 'ipasu' => 'ITパスポート',
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
                <img class="search-img" src="../images/search.svg" alt="">
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
        // 4. データベースからのデータ取得処理
        try {
            $dsn  = 'mysql:host=localhost;dbname=icc_smart_campus;charset=utf8';
            $user = 'root';
            $pass = 'root'; 

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];

            $db = new PDO($dsn, $user, $pass, $options);

            // 曜日の日本語名をDB用の数値(1-7)に変換
            $day_to_num = ['月' => 1, '火' => 2, '水' => 3, '木' => 4, '金' => 5, '土' => 6, '日' => 7];
            $target_day_num = $day_to_num[$display_day_jp] ?? 1;

            // コース識別子をDBのIDに変換
            $course_to_id = [
                'system' => 1, 'web' => 2, 'multi' => 3, 
                'ouyou' => 4, 'kihon' => 5, 'itikumi' => 6, 'nikumi' => 7
            ];
            $target_timetable_id = $course_to_id[$selected_course] ?? 1;

            // SQL実行：科目名を取得するためにsubjectsテーブルをJOIN
            $sql = "SELECT td.*, s.subject_name 
                    FROM timetable_details td
                    LEFT JOIN subjects s ON td.subject_id = s.subject_id
                    WHERE td.day_of_week = :day 
                    AND td.timetable_id = :tid 
                    ORDER BY td.period ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':day' => $target_day_num,
                ':tid' => $target_timetable_id
            ]);
            $fetched_data = $stmt->fetchAll();

            // 取得したデータを「時限」をキーにした連想配列に整理
            $schedule_by_period = [];
            foreach ($fetched_data as $row) {
                $schedule_by_period[$row['period']] = $row;
            }

            // 5. 1限から4限までをループして表示生成
            for ($period = 1; $period <= 4; $period++) {
                $item = $schedule_by_period[$period] ?? null;
                
                // 変数のサニタイズと初期値設定
                $subject_name = htmlspecialchars($item["subject_name"] ?? '');
                $class_detail = htmlspecialchars($item["class_detail"] ?? '詳細情報はありません。');
                $bring_object = htmlspecialchars($item["bring_object"] ?? '特になし');
                $room         = htmlspecialchars($item["room_name"] ?? '-');
                
                $display_title = $subject_name ?: '（授業なし）';
                $time_str = $time_schedule[$period] ?? "時間未定";

                // 「持ってくるもの」を正規表現で分割（全角半角スペース、カンマ、読点に対応）
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
                                <button type="button" class="button dropdown-toggle detail-toggle" id="detail-toggle-button-<?php echo $period; ?>" aria-expanded="false">
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
                                <button type="button" class="button dropdown-toggle item-toggle" id="item-toggle-button-<?php echo $period; ?>" aria-expanded="false">
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
                        </div> </div>
                </div>
            </section>
            <?php
            } // end for

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