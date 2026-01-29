<?php
// timetable_view.php
// 時間割り閲覧画面
SecurityHelper::applySecureHeaders();
SecurityHelper::requireLogin();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="nofollow, noindex">
    <meta name="csrf-token" content="<?php echo SecurityHelper::escapeHtml($csrfToken); ?>">
    <title>時間割り閲覧</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\common.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\teacher_home\user_menu.css">

</head>
<body>
    <header class="app-header">
        <h1>時間割り閲覧</h1>
        <img class="header_icon" src="./images/calendar-clock.png">
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
                                <!-- コース選択ドロップダウン -->
                                <button id="courseDropdownToggle" type="button" class="dropdown-toggle" aria-expanded="false">
                                    <span class="current-value">システムデザインコース</span>
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
                                <span>次回反映される時間割り</span>
                            </label>
                        </div>
                    </div>

                    <div id="savedListDivider" class="sidebar-divider hidden"></div>
                    <ul id="savedListContainer" class="hidden">
                        <li class="is-group-label">時間割一覧</li>
                    </ul>
                </div>
            </nav>

            <main class="main-content">
                <div id="emptyState" class="empty-state hidden">
                    <i class="fa-regular fa-calendar-xmark"></i>
                    <p class="font-bold">時間割が見つかりません</p>
                    <p class="text-sm">選択条件に該当する時間割がありません</p>
                </div>

                <div id="contentArea" class="hidden">
                    <div class="control-area">
                        <div class="period-box">
                            <span class="period-label">適用期間</span>
                            <div class="period-display" id="periodDisplay">
                                <span id="displayStartDate">-</span> ～ <span id="displayEndDate">-</span>
                            </div>
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
                                        <th class="day-header" data-day="月">月</th>
                                        <th class="day-header" data-day="火">火</th>
                                        <th class="day-header" data-day="水">水</th>
                                        <th class="day-header" data-day="木">木</th>
                                        <th class="day-header" data-day="金">金</th>
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
                </div>
            </main>
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
    <script src="js/timetable_view.js"></script>
    <script>
        // PHPから渡されたデータをJS変数にセット
        const dbTimetableData = <?= json_encode($savedTimetables, JSON_UNESCAPED_UNICODE); ?>;
        const dbCourseData = <?= json_encode($sidebarCourseList, JSON_UNESCAPED_UNICODE); ?>;
    </script>
</body>
</html>