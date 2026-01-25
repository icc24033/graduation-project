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
    <title>時間割り閲覧</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/style.css">

</head>
<body>
    <header class="app-header">
        <h1>時間割り閲覧</h1>
        <img class="header_icon" src="./images/calendar-clock.png">
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
                                <button id="courseDropdownToggle" type="button" class="dropdown-toggle" aria-expanded="false">
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
        let savedTimetables = [];
        let currentRecord = null;
        
        // コースの優先度（表示したい順）
        const coursePriorityList = [
            "システムデザインコース",
            "Webクリエイタコース",
            "マルチメディアOAコース",
            "応用情報コース",
            "基本情報コース",
            "ITパスポートコース",
            "１年１組",
            "１年２組"
        ];
        
        // デモデータの初期化
        function initializeDemoData() {
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            
            // 過去の日付（先月）
            const lastMonth = new Date(today);
            lastMonth.setMonth(lastMonth.getMonth() - 1);
            const lastMonthStr = lastMonth.toISOString().split('T')[0];
            
            // 未来の日付（来月）
            const nextMonth = new Date(today);
            nextMonth.setMonth(nextMonth.getMonth() + 1);
            const nextMonthStr = nextMonth.toISOString().split('T')[0];
            
            // 未来の日付（2ヶ月後）
            const twoMonthsLater = new Date(today);
            twoMonthsLater.setMonth(twoMonthsLater.getMonth() + 2);
            const twoMonthsLaterStr = twoMonthsLater.toISOString().split('T')[0];

            savedTimetables = [
                {
                    id: 1001,
                    course: "システムデザインコース",
                    startDate: lastMonthStr,
                    endDate: nextMonthStr,
                    data: [
                        {day: "月", period: "1", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "301演習室"},
                        {day: "月", period: "2", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "302演習室"},
                        {day: "火", period: "1", className: "データベース基礎", teacherName: "高橋 誠", roomName: "201教室"},
                        {day: "水", period: "1", className: "ネットワーク構築", teacherName: "田中 優子", roomName: "301演習室"},
                        {day: "木", period: "1", className: "セキュリティ概論", teacherName: "渡辺 剛", roomName: "4F大講義室"},
                        {day: "金", period: "1", className: "HR", teacherName: "伊藤 直人", roomName: "202教室"}
                    ]
                },
                {
                    id: 1002,
                    course: "システムデザインコース",
                    startDate: twoMonthsLaterStr,
                    endDate: null,
                    data: [
                        {day: "月", period: "1", className: "AI・機械学習", teacherName: "田中 優子", roomName: "301演習室"},
                        {day: "月", period: "2", className: "クラウド技術", teacherName: "佐藤 健一", roomName: "302演習室"},
                        {day: "火", period: "1", className: "データベース応用", teacherName: "高橋 誠", roomName: "201教室"},
                        {day: "水", period: "1", className: "セキュリティ演習", teacherName: "渡辺 剛", roomName: "4F大講義室"}
                    ]
                },
                {
                    id: 1003,
                    course: "Webクリエイタコース",
                    startDate: lastMonthStr,
                    endDate: nextMonthStr,
                    data: [
                        {day: "月", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "302演習室"},
                        {day: "月", period: "2", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "301演習室"},
                        {day: "火", period: "1", className: "プロジェクト管理", teacherName: "山本 さくら", roomName: "202教室"},
                        {day: "水", period: "1", className: "キャリアデザイン", teacherName: "伊藤 直人", roomName: "4F大講義室"}
                    ]
                },
                {
                    id: 1004,
                    course: "Webクリエイタコース",
                    startDate: twoMonthsLaterStr,
                    endDate: null,
                    data: [
                        {day: "月", period: "1", className: "UI/UXデザイン", teacherName: "鈴木 花子", roomName: "302演習室"},
                        {day: "火", period: "1", className: "フロントエンド開発", teacherName: "山本 さくら", roomName: "301演習室"},
                        {day: "水", period: "1", className: "ポートフォリオ制作", teacherName: "伊藤 直人", roomName: "202教室"}
                    ]
                },
                {
                    id: 1005,
                    course: "１年１組",
                    startDate: lastMonthStr,
                    endDate: nextMonthStr,
                    data: [
                        {day: "月", period: "1", className: "データベース基礎", teacherName: "高橋 誠", roomName: "201教室"},
                        {day: "火", period: "1", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "301演習室"},
                        {day: "水", period: "1", className: "HR", teacherName: "田中 優子", roomName: "201教室"}
                    ]
                },
                {
                    id: 1006,
                    course: "１年１組",
                    startDate: twoMonthsLaterStr,
                    endDate: null,
                    data: [
                        {day: "月", period: "1", className: "プログラミング応用", teacherName: "佐藤 健一", roomName: "301演習室"},
                        {day: "火", period: "1", className: "情報処理技術", teacherName: "高橋 誠", roomName: "201教室"},
                        {day: "木", period: "1", className: "HR", teacherName: "田中 優子", roomName: "201教室"}
                    ]
                }
            ];
        }
        
        // 優先度順にコースをチェック
        function selectInitialTimetable() {
            for (const courseName of coursePriorityList) {
                const record = savedTimetables.find(r => r.course === courseName);
                if (record) {
                    // ドロップダウンをそのコースに設定
                    document.querySelector('#courseDropdownToggle .current-value').textContent = courseName;
                    
                    // リストを描画
                    renderSavedList('select');
                    
                    // そのレコードを選択
                    setTimeout(() => {
                        const targetItem = document.querySelector(`.saved-item[data-id="${record.id}"]`);
                        if (targetItem) {
                            targetItem.click();
                        }
                    }, 100);
                    
                    return;
                }
            }
        }

        function changeDisplayMode(mode) {
            const toggleBtn = document.getElementById('courseDropdownToggle');
            const menu = document.getElementById('courseDropdownMenu');
            if (mode === 'select') {
                toggleBtn.classList.remove('disabled');
            } else {
                toggleBtn.classList.add('disabled');
                // モード切替で選択不可にしたらメニューが開いていれば閉じる
                if (menu && menu.classList.contains('is-open')) {
                    menu.classList.remove('is-open');
                    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
                }
            }
            renderSavedList(mode);
        }

        function isRecordActive(record) {
            const todayStr = new Date().toISOString().split('T')[0];
            const isStarted = record.startDate <= todayStr;
            const isNotEnded = !record.endDate || record.endDate >= todayStr;
            return isStarted && isNotEnded;
        }

        function updateHeaderDisplay(record) {
            const displayEl = document.getElementById('mainCourseDisplay');
            if(!displayEl) return;
            
            const todayStr = new Date().toISOString().split('T')[0];
            let badgeHtml = '';
            
            if(isRecordActive(record)) {
                badgeHtml = '<span class="active-badge">適用中</span>';
            } else if(record.startDate > todayStr) {
                badgeHtml = '<span class="next-badge">次回反映</span>';
            }
            
            displayEl.innerHTML = record.course + badgeHtml;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}/${month}/${day}`;
        }

        function getFormattedDate(inputVal) {
            if (!inputVal) return '';
            const parts = inputVal.split('-');
            if (parts.length !== 3) return '';
            return `${parts[1]}/${parts[2]}~`;
        }

        function renderSavedList(mode) {
            const container = document.getElementById('savedListContainer');
            const divider = document.getElementById('savedListDivider');
            const items = container.querySelectorAll('li:not(.is-group-label)');
            items.forEach(item => item.remove());

            let filteredRecords = savedTimetables;

            if (mode === 'select') {
                const currentCourse = document.querySelector('#courseDropdownToggle .current-value').textContent;
                filteredRecords = filteredRecords.filter(item => item.course === currentCourse);
            }

            const todayStr = new Date().toISOString().split('T')[0];

            if (mode === 'current') {
                filteredRecords = filteredRecords.filter(item => {
                    if (!item.startDate) return false;
                    const isStarted = item.startDate <= todayStr;
                    const isNotEnded = !item.endDate || item.endDate >= todayStr;
                    return isStarted && isNotEnded;
                });
            } else if (mode === 'next') {
                // 次回反映される時間割り：今日より後に開始される最も早い開始日の「1件のみ」を表示
                const futureRecords = filteredRecords.filter(item => item.startDate && item.startDate > todayStr);

                if (futureRecords.length > 0) {
                    // 最も早い開始日を見つける
                    const earliestDate = futureRecords.reduce((earliest, record) => {
                        return record.startDate < earliest ? record.startDate : earliest;
                    }, futureRecords[0].startDate);

                    // その開始日の時間割り候補
                    const candidates = futureRecords.filter(item => item.startDate === earliestDate);

                    // 候補が複数ある場合はコース優先度で1件を選択
                    let selected = candidates[0];
                    for (const courseName of coursePriorityList) {
                        const found = candidates.find(c => c.course === courseName);
                        if (found) { selected = found; break; }
                    }

                    filteredRecords = [selected];
                } else {
                    filteredRecords = [];
                }
            }

            // ソート：適用中が上、次回が下
            filteredRecords.sort((a, b) => {
                const aIsActive = isRecordActive(a);
                const bIsActive = isRecordActive(b);
                
                if (aIsActive && !bIsActive) return -1;
                if (!aIsActive && bIsActive) return 1;
                
                // 同じステータスの場合は開始日順
                return a.startDate.localeCompare(b.startDate);
            });

            if (filteredRecords.length > 0) {
                container.classList.remove('hidden');
                divider.classList.remove('hidden');
                document.getElementById('emptyState').classList.add('hidden');
            } else {
                container.classList.add('hidden');
                divider.classList.add('hidden');
                document.getElementById('emptyState').classList.remove('hidden');
                document.getElementById('contentArea').classList.add('hidden');
                return;
            }

            filteredRecords.forEach(record => {
                const dateLabel = getFormattedDate(record.startDate);
                let statusText = "";
                let statusClass = "text-slate-500";
                
                if (isRecordActive(record)) {
                    statusText = "適用中：";
                    statusClass = "text-emerald-600 font-bold";
                } else if (record.startDate > todayStr) {
                    statusText = "次回：";
                    statusClass = "text-blue-600 font-bold";
                }

                const newItem = document.createElement('li');
                newItem.className = 'nav-item saved-item';
                newItem.setAttribute('data-id', record.id);
                newItem.innerHTML = `
                    <a href="#">
                        <i class="fa-regular fa-file-lines text-blue-500 flex-shrink-0 mt-1"></i>
                        <div class="flex flex-col min-w-0 flex-1">
                            <span class="truncate font-bold text-sm">${record.course}</span>
                            <span class="text-xs truncate ${statusClass}">
                                ${statusText}${dateLabel}
                            </span>
                        </div>
                    </a>
                `;
                newItem.addEventListener('click', handleSavedItemClick);
                container.appendChild(newItem);
            });

            // 選択状態を維持：currentRecordがフィルタ内にあればそれを選択、なければ先頭を自動選択（自動選択はラジオ切替を行わない）
            if (filteredRecords.length > 0) {
                setTimeout(() => {
                    const selectedId = currentRecord ? currentRecord.id : null;
                    let targetItem = null;
                    if (selectedId) {
                        targetItem = container.querySelector(`.saved-item[data-id="${selectedId}"]`);
                    }
                    if (!targetItem) {
                        targetItem = container.querySelector('.saved-item');
                    }
                    if (targetItem) {
                        const id = parseInt(targetItem.getAttribute('data-id'));
                        selectSavedItemById(id, { updateRadio: false });
                    }
                }, 100);
            }
        }

        function setupDropdown(toggleId, menuId, onChangeCallback) {
            const toggle = document.getElementById(toggleId);
            const menu = document.getElementById(menuId);
            if (toggle && menu) {
                toggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (toggle.classList.contains('disabled')) return;
                    document.querySelectorAll('.dropdown-menu.is-open').forEach(openMenu => {
                        if (openMenu !== menu) {
                            openMenu.classList.remove('is-open');
                            const btnId = openMenu.id.replace('Menu', 'Toggle');
                            const btn = document.getElementById(btnId);
                            if(btn) btn.setAttribute('aria-expanded', 'false');
                        }
                    });
                    const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                    toggle.setAttribute('aria-expanded', !isExpanded);
                    if (!isExpanded) {
                        const rect = toggle.getBoundingClientRect();
                        // トグルの下にメニューを表示する（左揃え）
                        menu.style.top = `${rect.bottom}px`;
                        menu.style.left = `${rect.left}px`;
                        menu.classList.add('is-open');
                    } else {
                        menu.classList.remove('is-open');
                    }
                });
                menu.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        // e.currentTarget の方が堅牢
                        const selectedValue = e.currentTarget.textContent.trim();
                        toggle.querySelector('.current-value').textContent = selectedValue;
                        toggle.setAttribute('aria-expanded', 'false');
                        menu.classList.remove('is-open');
                        if (onChangeCallback) onChangeCallback();
                    });
                });
            }
        }
        
        setupDropdown('courseDropdownToggle', 'courseDropdownMenu', () => {
            const mode = document.querySelector('input[name="displayMode"]:checked').value;
            renderSavedList(mode);
        });

        // 初期モードを明示的に適用して、トグルの無効化状態を確実に反映させる
        changeDisplayMode(document.querySelector('input[name="displayMode"]:checked').value);

        document.addEventListener('click', (e) => {
            document.querySelectorAll('.dropdown-menu.is-open').forEach(menu => {
                if (menu.contains(e.target)) return;
                menu.classList.remove('is-open');
                const btnId = menu.id.replace('Menu', 'Toggle');
                const btn = document.getElementById(btnId);
                if(btn) btn.setAttribute('aria-expanded', 'false');
            });
        });
        
        document.querySelector('.sidebar').addEventListener('scroll', () => {
            document.querySelectorAll('.dropdown-menu.is-open').forEach(menu => {
                menu.classList.remove('is-open');
                const btnId = menu.id.replace('Menu', 'Toggle');
                const btn = document.getElementById(btnId);
                if(btn) btn.setAttribute('aria-expanded', 'false');
            });
        });

        // 保存アイテムを選択してUIに反映するヘルパー
        function selectSavedItemById(id, options = { updateRadio: true }) {
            document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
            const targetItem = document.querySelector(`.saved-item[data-id="${id}"]`);
            if (targetItem) targetItem.classList.add('active');

            const record = savedTimetables.find(item => item.id === id);
            if (record) {
                currentRecord = record;

                if (options.updateRadio) {
                    const selectRadio = document.querySelector('input[value="select"]');
                    if (selectRadio && !selectRadio.checked) selectRadio.checked = true;
                }

                // 空の状態を非表示、コンテンツエリアを表示
                document.getElementById('emptyState').classList.add('hidden');
                document.getElementById('contentArea').classList.remove('hidden');

                updateHeaderDisplay(record);

                // 適用期間を表示
                document.getElementById('displayStartDate').textContent = formatDate(record.startDate);
                document.getElementById('displayEndDate').textContent = formatDate(record.endDate);

                // グリッドをクリア
                document.querySelectorAll('.timetable-cell').forEach(cell => {
                    cell.innerHTML = '';
                    cell.classList.remove('is-filled');
                });

                // データを表示
                record.data.forEach(item => {
                    const targetCell = document.querySelector(`.timetable-cell[data-day="${item.day}"][data-period="${item.period}"]`);
                    if (targetCell) {
                        targetCell.innerHTML = `
                        <div class="class-content">
                            <div class="class-name">${item.className}</div>
                            <div class="class-detail">
                                ${item.teacherName ? `<div class="teacher-name"><i class="fa-solid fa-user icon"></i><span>${item.teacherName}</span></div>` : ''}
                                ${item.roomName ? `<div class="room-name"><i class="fa-solid fa-location-dot icon"></i><span>${item.roomName}</span></div>` : ''}
                            </div>
                        </div>`;
                        targetCell.classList.add('is-filled');
                    }
                });
            }
        }

        // ユーザーがクリックした場合のハンドラ（ラジオを切替）
        function handleSavedItemClick(e) {
            if (e.preventDefault) e.preventDefault();
            const id = parseInt(e.currentTarget.getAttribute('data-id'));
            // 「選択」モード以外の場合はラジオボタンを切り替えない
            const currentMode = document.querySelector('input[name="displayMode"]:checked').value;
            const shouldUpdateRadio = currentMode === 'select';
            selectSavedItemById(id, { updateRadio: shouldUpdateRadio });
        }

        // 初期化
        initializeDemoData();
        selectInitialTimetable();
    </script>
</body>
</html>