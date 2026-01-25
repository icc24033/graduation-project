/**
 * timetable_view.js
 * 時間割り閲覧画面用スクリプト
 */

let savedTimetables = [];
let coursePriorityList = []; // DBのコース順序を使用
let currentRecord = null;

// DOM読み込み完了時に実行
document.addEventListener('DOMContentLoaded', () => {
    // 1. データの受け取り
    initializeDataFromDB();

    // 2. グリッド生成
    initializeTimetableGrid();
    
    // 3. 初期表示（優先度順またはデータがある順）
    selectInitialTimetable();

    // 4. ドロップダウンの設定
    setupDropdown('courseDropdownToggle', 'courseDropdownMenu', () => {
        const mode = document.querySelector('input[name="displayMode"]:checked').value;
        renderSavedList(mode);
    });

    // 5. 初期モードの反映
    const initialModeInput = document.querySelector('input[name="displayMode"]:checked');
    if (initialModeInput) {
        changeDisplayMode(initialModeInput.value);
    }
    
    // 6. サイドバースクロール監視
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.addEventListener('scroll', closeAllDropdowns);
    }
});

/**
 * PHPから渡された変数をロードする
 */
function initializeDataFromDB() {
    // 時間割データ
    if (typeof dbTimetableData !== 'undefined' && Array.isArray(dbTimetableData)) {
        savedTimetables = dbTimetableData;
    } else {
        console.warn('DBからの時間割データが見つかりません。');
        savedTimetables = [];
    }

    // コースデータ（優先度リストとして使用）
    if (typeof dbCourseData !== 'undefined' && Array.isArray(dbCourseData)) {
        // コース名の配列を作成（DBの並び順＝表示優先度とする）
        coursePriorityList = dbCourseData.map(c => c.course_name);
        
        // 初期表示用にドロップダウンの先頭のコース名をセットしておく
        const toggleText = document.querySelector('#courseDropdownToggle .current-value');
        if (toggleText && coursePriorityList.length > 0) {
            toggleText.textContent = coursePriorityList[0];
        }
    }
}

/**
 * 時間割グリッド（空の行）を生成
 */
function initializeTimetableGrid() {
    const tbody = document.getElementById('timetable-body');
    if (!tbody) return;

    const times = [
        {s:'9:10',e:'10:40'}, 
        {s:'10:50',e:'12:20'}, 
        {s:'13:10',e:'14:40'}, 
        {s:'14:50',e:'16:20'}, 
        {s:'16:30',e:'17:50'}
    ];

    let html = '';
    for(let i = 1; i <= 5; i++) {
        html += `
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
        </tr>`;
    }
    tbody.innerHTML = html;
}

// 優先度順、またはデータが存在する順に初期選択を行う
function selectInitialTimetable() {
    if (savedTimetables.length === 0) return;

    // 1. まず「現在適用中(statusType=1)」のデータがあればそれを優先表示
    const activeRecord = savedTimetables.find(r => r.statusType == 1);
    if (activeRecord) {
        clickRecordItem(activeRecord);
        return;
    }

    // 2. なければ、コース優先度順に探して、データがある最初のコースを表示
    for (const courseName of coursePriorityList) {
        const record = savedTimetables.find(r => r.course === courseName);
        if (record) {
            clickRecordItem(record);
            return;
        }
    }

    // 3. それでもなければ、単純に配列の先頭を表示
    if (savedTimetables.length > 0) {
        clickRecordItem(savedTimetables[0]);
    }
}

// レコードに対応するUIを更新してクリックするヘルパー
function clickRecordItem(record) {
    // ドロップダウンの表示更新
    const toggleText = document.querySelector('#courseDropdownToggle .current-value');
    if(toggleText) toggleText.textContent = record.course;
    
    // リスト描画
    renderSavedList('select');
    
    // クリック発火
    setTimeout(() => {
        const targetItem = document.querySelector(`.saved-item[data-id="${record.id}"]`);
        if (targetItem) {
            targetItem.click();
        }
    }, 100);
}

// 表示モード変更
function changeDisplayMode(mode) {
    const toggleBtn = document.getElementById('courseDropdownToggle');
    const menu = document.getElementById('courseDropdownMenu');
    
    if (toggleBtn) {
        if (mode === 'select') {
            toggleBtn.classList.remove('disabled');
        } else {
            toggleBtn.classList.add('disabled');
            if (menu && menu.classList.contains('is-open')) {
                menu.classList.remove('is-open');
                toggleBtn.setAttribute('aria-expanded', 'false');
            }
        }
    }
    renderSavedList(mode);
}

// レコードが「現在適用中」かどうか判定
function isRecordActive(record) {
    // DBから statusType が来ている場合はそれを使うのが確実
    if (record.statusType !== undefined) {
        return parseInt(record.statusType) === 1;
    }

    // フォールバック（日付判定）
    const todayStr = new Date().toISOString().split('T')[0];
    const isStarted = record.startDate <= todayStr;
    const isNotEnded = !record.endDate || record.endDate >= todayStr;
    return isStarted && isNotEnded;
}

// ヘッダー表示更新
function updateHeaderDisplay(record) {
    const displayEl = document.getElementById('mainCourseDisplay');
    if(!displayEl) return;
    
    let badgeHtml = '';
    // statusType を優先利用
    const sType = record.statusType !== undefined ? parseInt(record.statusType) : null;

    if (sType === 1 || (sType === null && isRecordActive(record))) {
        badgeHtml = '<span class="active-badge">適用中</span>';
    } else if (sType === 2) { // 次回
        badgeHtml = '<span class="next-badge">次回反映</span>';
    } else if (sType >= 3) {
        badgeHtml = '<span class="next-badge" style="background:#6366f1;">次回以降</span>';
    }
    
    displayEl.innerHTML = record.course + badgeHtml;
}

// 日付フォーマット YYYY/MM/DD
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}/${month}/${day}`;
}

// リスト用日付フォーマット MM/DD~
function getFormattedDate(inputVal) {
    if (!inputVal) return '';
    const parts = inputVal.split('-');
    if (parts.length !== 3) return '';
    return `${parts[1]}/${parts[2]}~`;
}

// 左サイドバーのリスト描画
function renderSavedList(mode) {
    const container = document.getElementById('savedListContainer');
    const divider = document.getElementById('savedListDivider');
    if (!container) return;

    const items = container.querySelectorAll('li:not(.is-group-label)');
    items.forEach(item => item.remove());

    let filteredRecords = savedTimetables;

    // --- フィルタリング ---
    if (mode === 'select') {
        const toggleText = document.querySelector('#courseDropdownToggle .current-value');
        const currentCourse = toggleText ? toggleText.textContent.trim() : '';
        filteredRecords = filteredRecords.filter(item => item.course === currentCourse);
    } else if (mode === 'current') {
        // 現在適用中 (statusType=1)
        filteredRecords = filteredRecords.filter(item => item.statusType == 1);
    } else if (mode === 'next') {
        // 次回 (statusType=2)
        filteredRecords = filteredRecords.filter(item => item.statusType == 2);
    }

    // --- ソート ---
    filteredRecords.sort((a, b) => {
        // statusType昇順 (1:現在 -> 2:次回 -> 3:以降 -> 0:過去)
        // ただし0(過去)は一番後ろにしたい
        const typeA = parseInt(a.statusType);
        const typeB = parseInt(b.statusType);

        // 両方0なら日付降順
        if (typeA === 0 && typeB === 0) return b.startDate.localeCompare(a.startDate);
        
        // どちらかが0なら0を後ろへ
        if (typeA === 0) return 1;
        if (typeB === 0) return -1;

        // それ以外は昇順 (1 -> 2 -> 3)
        return typeA - typeB;
    });

    // --- 表示制御 ---
    if (filteredRecords.length > 0) {
        container.classList.remove('hidden');
        if(divider) divider.classList.remove('hidden');
        const emptyState = document.getElementById('emptyState');
        if(emptyState) emptyState.classList.add('hidden');
    } else {
        container.classList.add('hidden');
        if(divider) divider.classList.add('hidden');
        
        // リストが空の場合、コンテンツエリアも隠してエンプティステートを表示
        const emptyState = document.getElementById('emptyState');
        if(emptyState) emptyState.classList.remove('hidden');
        const contentArea = document.getElementById('contentArea');
        if(contentArea) contentArea.classList.add('hidden');
        return;
    }

    // --- リスト生成 ---
    filteredRecords.forEach(record => {
        const dateLabel = getFormattedDate(record.startDate);
        let statusText = "";
        let statusClass = "text-slate-500";
        
        const sType = parseInt(record.statusType);

        if (sType === 1) {
            statusText = "適用中：";
            statusClass = "text-emerald-600 font-bold";
        } else if (sType === 2) {
            statusText = "次回：";
            statusClass = "text-blue-600 font-bold";
        } else if (sType >= 3) {
            statusText = "次回以降：";
            statusClass = "text-indigo-600 font-bold";
        } else if (sType === 0) {
            statusText = "過去：";
            statusClass = "text-gray-400";
        }

        const newItem = document.createElement('li');
        newItem.className = 'nav-item saved-item';
        newItem.setAttribute('data-id', record.id);
        newItem.innerHTML = `
            <a href="#" onclick="return false;">
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

    // 選択状態の維持
    setTimeout(() => {
        const selectedId = currentRecord ? currentRecord.id : null;
        let targetItem = null;
        if (selectedId) {
            targetItem = container.querySelector(`.saved-item[data-id="${selectedId}"]`);
        }
        // 選択中のものがリストにない場合（フィルタ切り替え時など）、先頭を選択
        if (!targetItem) {
            targetItem = container.querySelector('.saved-item');
        }
        
        if (targetItem) {
            const id = parseInt(targetItem.getAttribute('data-id'));
            // ラジオボタンの更新はしない（クリック扱いだがモードは維持）
            selectSavedItemById(id, { updateRadio: false });
        }
    }, 50);
}

// ドロップダウンのセットアップ
function setupDropdown(toggleId, menuId, onChangeCallback) {
    const toggle = document.getElementById(toggleId);
    const menu = document.getElementById(menuId);
    if (toggle && menu) {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (toggle.classList.contains('disabled')) return;
            
            closeAllDropdowns(menu);

            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', !isExpanded);
            if (!isExpanded) {
                const rect = toggle.getBoundingClientRect();
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
                const selectedValue = e.currentTarget.textContent.trim();
                const currentValSpan = toggle.querySelector('.current-value');
                if(currentValSpan) currentValSpan.textContent = selectedValue;
                
                toggle.setAttribute('aria-expanded', 'false');
                menu.classList.remove('is-open');
                if (onChangeCallback) onChangeCallback();
            });
        });
    }
}

function closeAllDropdowns(exceptMenu = null) {
    document.querySelectorAll('.dropdown-menu.is-open').forEach(openMenu => {
        if (openMenu !== exceptMenu) {
            openMenu.classList.remove('is-open');
            const btnId = openMenu.id.replace('Menu', 'Toggle');
            const btn = document.getElementById(btnId);
            if(btn) btn.setAttribute('aria-expanded', 'false');
        }
    });
}

// 保存アイテムIDから表示内容を更新
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

        const emptyState = document.getElementById('emptyState');
        const contentArea = document.getElementById('contentArea');
        if(emptyState) emptyState.classList.add('hidden');
        if(contentArea) contentArea.classList.remove('hidden');

        updateHeaderDisplay(record);

        document.getElementById('displayStartDate').textContent = formatDate(record.startDate);
        document.getElementById('displayEndDate').textContent = formatDate(record.endDate);

        // グリッドをクリア
        document.querySelectorAll('.timetable-cell').forEach(cell => {
            cell.innerHTML = '';
            cell.classList.remove('is-filled');
        });

        // データを表示 (DB形式: teacherIds配列対応)
        if (record.data && Array.isArray(record.data)) {
            record.data.forEach(item => {
                const targetCell = document.querySelector(`.timetable-cell[data-day="${item.day}"][data-period="${item.period}"]`);
                if (targetCell) {
                    // HTML生成：複数教員・教室対応が必要だが、表示用には teacherName などを結合済みで持っている前提
                    // もし teacherName が単純な文字列ならそのまま表示
                    
                    targetCell.innerHTML = `
                    <div class="class-content">
                        <div class="class-name">${item.className || ''}</div>
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
}

// クリックハンドラ
function handleSavedItemClick(e) {
    if (e.preventDefault) e.preventDefault();
    // 親のli要素を探す（aタグクリックなどの場合）
    const targetLi = e.currentTarget.closest('.saved-item');
    if(!targetLi) return;

    const id = parseInt(targetLi.getAttribute('data-id'));
    const modeInput = document.querySelector('input[name="displayMode"]:checked');
    const currentMode = modeInput ? modeInput.value : 'select';
    
    // 「選択」モードならラジオはそのまま、それ以外（次回など）ならモードは維持
    const shouldUpdateRadio = currentMode === 'select';
    selectSavedItemById(id, { updateRadio: shouldUpdateRadio });
}