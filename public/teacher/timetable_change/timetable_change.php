<?php
// timetable_change.php
// 授業変更画面のViewファイル
SecurityHelper::requireLogin();
SecurityHelper::applySecureHeaders();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業変更</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/timetable_change.css">
    <script src="js/timetable_change.js" defer></script>
</head>
<body>

    <!-- ヘッダー -->
    <header class="app-header">
        <h1>授業変更</h1>
        <img class="header_icon" src="images/square-pen.png">
    </header>

    <div class="app-container">
        <div class="main-section">

            <!-- サイドバー -->
            <nav class="sidebar" style="z-index: 50;">
                <!-- フィルタリング & 作成済み時間割リスト -->
                <div class="px-4 py-4 space-y-4">
                    <!-- ラジオボタン群 (復活) -->
                    <div>
                        <div class="mb-3">
                            <label class="radio-group-label">
                                <input type="radio" name="displayMode" value="select" checked onchange="changeDisplayMode('select')">
                                <span class="font-bold">選択</span>
                            </label>
                            
                            <!-- コース選択ドロップダウン (復活) -->
                            <div class="ml-6 mt-1 relative">
                                <button id="courseDropdownToggle" class="dropdown-toggle" aria-expanded="false">
                                    <span class="current-value">システムデザインコース</span>
                                </button>
                                <ul id="courseDropdownMenu" class="dropdown-menu">
                                    <li><a href="#">システムデザインコース</a></li>
                                    <li><a href="#">Webクリエイタコース</a></li>
                                    <li><a href="#">マルチメディアOAコース</a></li>
                                    <li><a href="#">応用情報コース</a></li>
                                    <li><a href="#">基本情報コース</a></li>
                                    <li><a href="#">ITパスポートコース</a></li>
                                    <li><a href="#">１年１組</a></li>
                                    <li><a href="#">１年２組</a></li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="radio-group-label">
                                <input type="radio" name="displayMode" value="current" onchange="changeDisplayMode('current')">
                                <span>現在反映されている時間割り</span>
                            </label>
                        </div>

                        <div class="mb-2">
                            <label class="radio-group-label">
                                <input type="radio" name="displayMode" value="next" onchange="changeDisplayMode('next')">
                                <span>次の期間で反映される時間割り</span>
                            </label>
                        </div>
                    </div>

                    <div id="savedListDivider" class="sidebar-divider"></div>
                    <ul id="savedListContainer">
                        <li class="is-group-label">作成済み時間割</li>
                        <!-- JSでリスト追加 -->
                    </ul>
                </div>
            </nav>

            <!-- メインコンテンツ -->
            <main class="main-content">
                <div class="control-area">
                    <!-- 週ナビゲーション -->
                    <div class="week-navigator">
                        <button id="prevWeekBtn" class="week-nav-btn"><i class="fa-solid fa-chevron-left"></i></button>
                        <span id="weekDisplay" class="week-display">（時間割を選択してください）</span>
                        <button id="nextWeekBtn" class="week-nav-btn"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>

                    <!-- コース表示エリア -->
                    <div class="course-display">
                        <!-- ★変更箇所: border-[#C0DEFF] に固定 -->
                        <h2 id="mainCourseDisplay" class="text-2xl font-bold text-slate-700 border-b-2 border-[#C0DEFF] px-2 inline-block">
                            （未選択）
                        </h2>
                    </div>
                </div>

                <div class="timetable-container">
                    <div class="timetable-wrap">
                        <table class="timetable">
                            <thead>
                                <tr>
                                    <th class="table-corner"></th>
                                    <th class="day-header" id="th-mon">月</th>
                                    <th class="day-header" id="th-tue">火</th>
                                    <th class="day-header" id="th-wed">水</th>
                                    <th class="day-header" id="th-thu">木</th>
                                    <th class="day-header" id="th-fri">金</th>
                                </tr>
                            </thead>
                            <tbody id="timetable-body">
                                <script>
                                    for(let i=1; i<=5; i++) {
                                        const times = [{s:'9:10',e:'10:40'}, {s:'10:50',e:'12:20'}, {s:'13:10',e:'14:40'}, {s:'14:50',e:'16:20'}, {s:'16:30',e:'17:50'}];
                                        document.write(`
                                        <tr>
                                            <td class="period-cell">
                                                <div class="period-number">${i}</div>
                                                <div class="period-time">${times[i-1].s}~</div>
                                                <div class="period-time">${times[i-1].e}</div>
                                            </td>
                                            <td class="timetable-cell" data-day="月" data-period="${i}"></td>
                                            <td class="timetable-cell" data-day="火" data-period="${i}"></td>
                                            <td class="timetable-cell" data-day="水" data-period="${i}"></td>
                                            <td class="timetable-cell" data-day="木" data-period="${i}"></td>
                                            <td class="timetable-cell" data-day="金" data-period="${i}"></td>
                                        </tr>`);
                                    }
                                </script>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="footer-button-area">
                    <button id="saveChangesBtn" class="save-changes-button">変更を保存</button>
                </div>
            </main>
        </div>
    </div>

    <!-- 変更用モーダル -->
    <div id="changeModal" class="modal-overlay hidden">
        <div class="modal-content">
            <h2 id="modalTitle" class="modal-title">授業変更</h2>
            <div class="modal-form-area">
                <!-- 変更日 (読み取り専用) -->
                <div class="modal-form-item">
                    <label class="modal-label">変更日：</label>
                    <div class="modal-select-wrapper">
                        <input type="date" id="targetDateInput" class="modal-date-input" readonly>
                    </div>
                </div>

                <div class="modal-form-item">
                    <label class="modal-label">授業名：</label>
                    <div class="modal-select-wrapper">
                        <select id="inputClassName" class="modal-select">
                            <option value="">(休講/空き)</option>
                            <option value="Javaプログラミング">Javaプログラミング</option>
                            <option value="Webデザイン演習">Webデザイン演習</option>
                            <option value="データベース基礎">データベース基礎</option>
                            <option value="ネットワーク構築">ネットワーク構築</option>
                            <option value="セキュリティ概論">セキュリティ概論</option>
                            <option value="プロジェクト管理">プロジェクト管理</option>
                            <option value="キャリアデザイン">キャリアデザイン</option>
                            <option value="HR">HR</option>
                        </select>
                        <div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                    </div>
                </div>
                <div>
                    <label class="modal-label">担当先生：</label>
                    <div id="teacherSelectionArea" class="teacher-selection-area">
                        <div class="teacher-input-row">
                            <select class="teacher-input modal-select"><option value="">(選択してください)</option><option value="佐藤 健一">佐藤 健一</option><option value="鈴木 花子">鈴木 花子</option><option value="高橋 誠">高橋 誠</option><option value="田中 優子">田中 優子</option><option value="渡辺 剛">渡辺 剛</option><option value="伊藤 直人">伊藤 直人</option><option value="山本 さくら">山本 さくら</option></select>
                            <div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                            <button class="remove-teacher-btn" style="display: none;">×</button>
                        </div>
                    </div>
                    <div class="add-teacher-btn-wrapper">
                        <button id="addTeacherBtn" class="add-teacher-btn">+ 追加</button>
                    </div>
                </div>
                <div>
                    <label class="modal-label">教室場所：</label>
                    <div id="roomSelectionArea" class="room-selection-area">
                        <div class="room-input-row">
                            <select class="room-input modal-select"><option value="">(選択してください)</option><option value="201教室">201教室</option><option value="202教室">202教室</option><option value="301演習室">301演習室</option><option value="302演習室">302演習室</option><option value="4F大講義室">4F大講義室</option><option value="別館Lab A">別館Lab A</option><option value="別館Lab B">別館Lab B</option></select>
                            <div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                            <button class="remove-room-btn" style="display: none;">×</button>
                        </div>
                    </div>
                    <div class="add-room-btn-wrapper">
                        <button id="addRoomBtn" class="add-room-btn">+ 追加</button>
                    </div>
                </div>
            </div>
            <div class="modal-button-area">
                <button id="btnRevert" class="modal-revert-button hidden">変更を取り消す</button>
                <div class="flex-grow"></div>
                <button id="btnCancel" class="modal-cancel-button">キャンセル</button>
                <button id="btnUpdate" class="modal-save-button">変更反映</button>
            </div>
        </div>
    </div>
</body>
</html>