<?php
// timetable_change.php
// 授業変更画面のViewファイル

// セキュリティチェック（コントローラー呼び出し元で既にチェックされている場合もありますが、念のため）
if (!class_exists('SecurityHelper')) {
    require_once __DIR__ . '/../../../classes/security/SecurityHelper.php';
}
SecurityHelper::requireLogin();
SecurityHelper::applySecureHeaders();

// コントローラーからextract()で渡された変数が利用可能です:
// $savedTimetables, $sidebarCourseList, $rawCourseData, $masterSubjectData, $csrfToken
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業変更</title>
    <meta name="csrf-token" content="<?php echo SecurityHelper::escapeHtml($csrfToken); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/timetable_change.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\teacher_home\user_menu.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\common.css">
    <script src="js/timetable_change.js" defer></script>
</head>
<body>

    <header class="app-header">
        <h1>授業変更</h1>
        <img class="header_icon" src="images/square-pen.png">
        <div class="user-avatar" id="userAvatar" style="position: absolute; right: 20px; top: 5px;">
            <img src="<?= SecurityHelper::escapeHtml((string)$data['user_picture']) ?>" alt="ユーザーアイコン" class="avatar-image">   
        </div>
            <div class="user-menu-popup" id="userMenuPopup">
                <a href="../logout/logout.php" class="logout-button">
                    <span class="icon-key"></span>
                        アプリからログアウト
                </a>
                <a href="" class="help-button">
                    <span class="icon-lightbulb"></span> ヘルプ
                </a>
            </div>
        <img src="<?= SecurityHelper::escapeHtml((string)$smartcampus_picture) ?>" alt="Webアプリアイコン" width="200" height="60" style="position: absolute; left: 20px; top: 5px;">
    </header>

    <div class="app-container">
        <div class="main-section">

            <nav class="sidebar" style="z-index: 50;">
                <div class="px-4 py-4 space-y-4">
                    <div>
                        <div class="mb-3">
                            <label class="radio-group-label">
                                <input type="radio" name="displayMode" value="select" checked onchange="changeDisplayMode('select')">
                                <span class="font-bold">選択</span>
                            </label>
                            
                            <div class="ml-6 mt-1 relative">
                                <button id="courseDropdownToggle" class="dropdown-toggle" aria-expanded="false">
                                    <span class="current-value">コースを選択してください</span>
                                </button>
                                <ul id="courseDropdownMenu" class="dropdown-menu">
                                    <?php echo $sidebarCourseList; ?>
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
                        </ul>
                </div>
            </nav>

            <main class="main-content">
                <div class="control-area">
                    <div class="week-navigator">
                        <button id="prevWeekBtn" class="week-nav-btn"><i class="fa-solid fa-chevron-left"></i></button>
                        <span id="weekDisplay" class="week-display">（時間割を選択してください）</span>
                        <button id="nextWeekBtn" class="week-nav-btn"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>

                    <div class="course-display">
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
                                    // 枠だけ生成しておく（中身は空）
                                    const times = [
                                        {s:'9:10',e:'10:40'}, {s:'10:50',e:'12:20'}, 
                                        {s:'13:10',e:'14:40'}, {s:'14:50',e:'16:20'}, 
                                        {s:'16:30',e:'17:50'}
                                    ];
                                    for(let i=1; i<=5; i++) {
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

    <div id="changeModal" class="modal-overlay hidden">
        <div class="modal-content">
            <h2 id="modalTitle" class="modal-title">授業変更</h2>
            <div class="modal-form-area">
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
                            </select>
                        <div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                    </div>
                </div>

                <div>
                    <label class="modal-label">担当先生：</label>
                    <div id="teacherSelectionArea" class="teacher-selection-area">
                        </div>
                    <div class="add-teacher-btn-wrapper">
                        <button id="addTeacherBtn" class="add-teacher-btn">+ 追加</button>
                    </div>
                </div>

                <div>
                    <label class="modal-label">教室場所：</label>
                    <div id="roomSelectionArea" class="room-selection-area">
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
    <script>
        const allCourseInfo = <?= json_encode($courseInfo) ?>;
        let currentData = {};

        document.addEventListener('DOMContentLoaded', function() {
                const userAvatar = document.getElementById('userAvatar');
                const userMenuPopup = document.getElementById('userMenuPopup');

                userAvatar.addEventListener('click', function(event) {
                    userMenuPopup.classList.toggle('is-visible');
                    event.stopPropagation();
                });

                document.addEventListener('click', function(event) {
                    if (!userMenuPopup.contains(event.target) && !userAvatar.contains(event.target)) {
                        userMenuPopup.classList.remove('is-visible');
                    }
                });
            });
    </script>

    <script>
        // コントローラーから渡されたPHP変数をJSON化してJS定数に格納
        // JSON_HEX_TAG等はXSS対策の一環としてエスケープ処理を行う
        const dbTimetableData = <?php echo json_encode($savedTimetables ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const dbCourseList = <?php echo json_encode($rawCourseData ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;        // マスタデータ（科目・教員・教室の定義）
        const dbMasterData      = <?php echo json_encode($masterSubjectData ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        
        // ユーザー権限や設定など
        const currentUserPicture = "<?php echo SecurityHelper::escapeHtml($user_picture ?? ''); ?>";
    </script>
</body>
</html>