// --- データ管理 ---
let savedTimetables = [
    {
        id: 1,
        course: "システムデザインコース",
        startDate: "2026-01-01",
        endDate: "2026-01-15",
        data: [
            { day: "月", period: "1", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "201教室" },
            { day: "火", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" }
        ],
        changes: [] 
    },
    {
        id: 2,
        course: "システムデザインコース",
        startDate: "2026-01-16",
        endDate: "2026-01-30",
        data: [
            { day: "月", period: "1", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "201教室" },
            { day: "火", period: "2", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" },
            { day: "金", period: "2", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" }
        ],
        changes: [] 
    },
    {
        id: 3,
        course: "システムデザインコース",
        startDate: "2026-02-01",
        endDate: "2026-02-14",
        data: [
            { day: "月", period: "1", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "201教室" },
            { day: "火", period: "3", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" }
        ],
        changes: [] 
    }
];

let currentRecord = null; // 現在編集中の時間割レコード
let currentWeekStart = null; // 現在表示中の週の月曜日 (Dateオブジェクト)

// --- 初期化 ---
window.addEventListener('DOMContentLoaded', () => {
    renderSidebarList(); // 初期表示
});

// --- ステータス判定 ---
/**
 * 時間割レコードが現在適用中かどうかを判定する
 * @param {Object} record - 判定対象の時間割レコード
 * @returns {boolean} 適用中の場合はtrue、そうでない場合はfalse
 * @example
 * const record = { startDate: "2026-01-01", endDate: "2026-01-15", ... };
 * if (isRecordActive(record)) {
 *     console.log("この時間割は現在適用中です");
 * }
 */
function isRecordActive(record) {
    // 期間が未設定の場合は適用中ではない
    if (!record.startDate || !record.endDate) return false;
    
    const todayStr = new Date().toISOString().split('T')[0];
    const isStarted = record.startDate <= todayStr;
    const isNotEnded = record.endDate >= todayStr;
    return isStarted && isNotEnded;
}

// --- ヘッダー更新 ---
/**
 * メインコンテンツエリアのヘッダー表示を更新する
 * コース名と適用状態バッジ（適用中/次回反映）を表示
 * @param {Object} record - 表示対象の時間割レコード
 * @example
 * updateHeaderDisplay(currentRecord);
 */
function updateHeaderDisplay(record) {
    const displayEl = document.getElementById('mainCourseDisplay');
    if(!displayEl) return;
    
    const todayStr = new Date().toISOString().split('T')[0];
    let badgeHtml = '';
    
    if(isRecordActive(record)) {
        // 適用中
        badgeHtml = '<span class="active-badge current">適用中</span>';
    } else if (record.startDate > todayStr) {
        // 次回反映
        badgeHtml = '<span class="active-badge next">次回反映</span>';
    }
    
    displayEl.innerHTML = record.course + badgeHtml;
}

// --- ラジオボタン制御 ---
/**
 * ラジオボタンによる表示モード切り替え処理
 * @param {string} mode - 表示モード ('select'|'current'|'next')
 *   - 'select': ドロップダウンで選択したコースの時間割を表示
 *   - 'current': 現在適用中の時間割のみ表示
 *   - 'next': 次回反映予定の時間割のみ表示
 * @example
 * changeDisplayMode('current'); // 現在適用中の時間割のみ表示
 */
function changeDisplayMode(mode) {
    // ドロップダウンの有効/無効切り替え
    const toggleBtn = document.getElementById('courseDropdownToggle');
    if (mode === 'select') {
        toggleBtn.classList.remove('disabled');
    } else {
        toggleBtn.classList.add('disabled');
        // 「現在反映」「次回反映」モードの場合は、最初に見つかったコースを選択
        const firstCourse = savedTimetables.length > 0 ? savedTimetables[0].course : 'システムデザインコース';
        toggleBtn.querySelector('.current-value').textContent = firstCourse;
    }
    renderSidebarList(mode);
}

// --- リスト描画 (フィルタリング付き) ---
/**
 * サイドバーの時間割リストを描画する
 * 選択されたコースとモードに応じてフィルタリングを行う
 * @param {string} [mode='select'] - 表示モード ('select'|'current'|'next')
 * @example
 * renderSidebarList('select'); // 選択モードでリストを描画
 * renderSidebarList('current'); // 現在適用中のみでリストを描画
 */
function renderSidebarList(mode = 'select') {
    const container = document.getElementById('savedListContainer');
    const items = container.querySelectorAll('li:not(.is-group-label)');
    items.forEach(item => item.remove());

    const todayStr = new Date().toISOString().split('T')[0];
    const currentCourse = document.querySelector('#courseDropdownToggle .current-value').textContent;
    
    // コースで絞り込み
    let filteredRecords = savedTimetables.filter(item => item.course === currentCourse);

    // モードによる絞り込み
    if (mode === 'current') {
        filteredRecords = filteredRecords.filter(item => {
            const isStarted = item.startDate <= todayStr;
            const isNotEnded = !item.endDate || item.endDate >= todayStr;
            return isStarted && isNotEnded;
        });
    } else if (mode === 'next') {
        filteredRecords = filteredRecords.filter(item => {
            return item.startDate > todayStr;
        });
    }

    // 「次回」判定用：同じコースの未来の時間割で最も開始日が近いものを特定
    const futureRecords = savedTimetables.filter(item => 
        item.course === currentCourse && item.startDate > todayStr
    );
    futureRecords.sort((a, b) => a.startDate.localeCompare(b.startDate));
    const nextRecordId = futureRecords.length > 0 ? futureRecords[0].id : null;

    // リスト生成
    filteredRecords.forEach(record => {
        const hasChanges = record.changes && record.changes.length > 0;
        const newItem = document.createElement('li'); 
        newItem.className = `nav-item saved-item ${hasChanges ? 'has-changes' : ''}`;
        newItem.setAttribute('data-id', record.id);
        
        let badgeHtml = hasChanges ? '<span class="changed-badge"></span>' : '';
        
        const dateLabel = `${new Date(record.startDate).getMonth()+1}/${new Date(record.startDate).getDate()}~`;
        let statusText = "";
        let statusClass = "text-slate-500";
        
        if (isRecordActive(record)) {
            statusText = "適用中：";
            statusClass = "text-emerald-600 font-bold";
        } else if (record.id === nextRecordId) {
            statusText = "次回：";
            statusClass = "text-blue-600 font-bold";
        } else if (!record.startDate || !record.endDate) {
            statusText = "次回以降：";
            statusClass = "text-indigo-600 font-bold";
        } else if (record.startDate && record.startDate > todayStr) {
            statusText = "次回以降：";
            statusClass = "text-indigo-600 font-bold";
        } else if (record.endDate && record.endDate < todayStr) {
            statusText = "過去：";
            statusClass = "text-gray-400";
        }
        
        newItem.innerHTML = `
            <a href="#" onclick="selectTimetable(${record.id}, this)"> 
                <i class="fa-regular text-blue-500 flex-shrink-0 mt-1"></i>
                <div class="flex flex-col min-w-0">
                    <span class="truncate font-bold">${record.course}</span>
                    <span class="text-xs truncate ${statusClass}">
                        ${statusText}${dateLabel}
                    </span>
                </div>
                ${badgeHtml}
            </a>
        `;
        container.appendChild(newItem);
    });
}

// --- 時間割選択処理 ---
/**
 * サイドバーから時間割を選択した際の処理
 * 選択された時間割をメインエリアに表示し、週ナビゲーションを初期化する
 * @param {number} id - 選択された時間割のID
 * @param {HTMLElement} element - クリックされたリスト要素
 * @example
 * // HTMLから呼び出し: onclick="selectTimetable(1, this)"
 */
function selectTimetable(id, element) {
    // UIのアクティブ化
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
    element.parentElement.classList.add('active');

    // データ取得
    currentRecord = savedTimetables.find(r => r.id === id);
    
    if (currentRecord) {
        // 表示初期化（バッジ付き）
        updateHeaderDisplay(currentRecord);
        
        // 開始日を基準に最初の週を表示
        const start = new Date(currentRecord.startDate);
        // 月曜日まで戻す
        const day = start.getDay();
        const diff = start.getDate() - day + (day == 0 ? -6 : 1); 
        currentWeekStart = new Date(start.setDate(diff));
        
        updateWeekDisplay();
    }
}

// --- ドロップダウン制御 ---
/**
 * ドロップダウンメニューの初期設定を行う
 * クリックイベントとメニュー項目選択時のコールバックを設定
 * @param {string} toggleId - トグルボタンのID
 * @param {string} menuId - ドロップダウンメニューのID
 * @param {Function} [onChangeCallback] - 項目選択時に実行されるコールバック関数
 * @example
 * setupDropdown('courseDropdownToggle', 'courseDropdownMenu', function() {
 *     console.log('コースが変更されました');
 * });
 */
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
                menu.style.top = `${rect.top}px`;
                menu.style.left = `${rect.right + 10}px`;
                menu.classList.add('is-open');
            } else {
                menu.classList.remove('is-open');
            }
        });
        menu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const selectedValue = e.target.textContent;
                toggle.querySelector('.current-value').textContent = selectedValue;
                toggle.setAttribute('aria-expanded', 'false');
                menu.classList.remove('is-open');
                if (onChangeCallback) onChangeCallback();
            });
        });
    }
}
setupDropdown('courseDropdownToggle', 'courseDropdownMenu', function() {
    // コース変更時にリストを再描画
    renderSidebarList('select');
    
    // 表示クリア
    document.getElementById('mainCourseDisplay').textContent = "（未選択）";
    document.querySelectorAll('.timetable-cell').forEach(cell => {
        cell.innerHTML = ''; cell.classList.remove('is-filled'); cell.classList.remove('is-changed');
    });
    document.getElementById('weekDisplay').textContent = "（時間割を選択してください）";
    currentRecord = null;
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
});

// ドロップダウン閉じる
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

// --- 週ナビゲーション処理 ---
/**
 * 週ナビゲーションの表示を更新する
 * 現在の週の日付範囲を表示し、前後ボタンの有効/無効を制御する
 * @example
 * currentWeekStart = new Date('2026-01-06');
 * updateWeekDisplay(); // 1/6〜1/12の週を表示
 */
function updateWeekDisplay() {
    if (!currentWeekStart || !currentRecord) return;

    const weekEnd = new Date(currentWeekStart);
    weekEnd.setDate(currentWeekStart.getDate() + 6);

    const fmt = (d) => `${d.getFullYear()}年${d.getMonth()+1}月${d.getDate()}日`;
    document.getElementById('weekDisplay').textContent = `${fmt(currentWeekStart)} 〜 ${fmt(weekEnd)}`;

    const recStart = new Date(currentRecord.startDate);
    const recEnd = new Date(currentRecord.endDate);
    const prevWeek = new Date(currentWeekStart); prevWeek.setDate(prevWeek.getDate() - 7);
    const nextWeek = new Date(currentWeekStart); nextWeek.setDate(nextWeek.getDate() + 7);

    // 比較用（時間を0に）
    const zeroTime = (d) => { const nd = new Date(d); nd.setHours(0,0,0,0); return nd; };

    // 週の開始日が期間開始日より後なら戻れる
    document.getElementById('prevWeekBtn').disabled = (zeroTime(currentWeekStart) <= zeroTime(recStart)); 
    // 週の終了日が期間終了日より前なら進める
    document.getElementById('nextWeekBtn').disabled = (zeroTime(weekEnd) >= zeroTime(recEnd));

    // ヘッダー更新
    const days = ['月', '火', '水', '木', '金'];
    const thIds = ['th-mon', 'th-tue', 'th-wed', 'th-thu', 'th-fri'];
    
    days.forEach((d, idx) => {
        const targetDate = new Date(currentWeekStart);
        targetDate.setDate(currentWeekStart.getDate() + idx);
        const m = targetDate.getMonth() + 1;
        const date = targetDate.getDate();
        const th = document.getElementById(thIds[idx]);
        th.innerHTML = `${d} <span class="text-sm font-normal">(${m}/${date})</span>`;
        
        const y = targetDate.getFullYear();
        const mo = String(targetDate.getMonth() + 1).padStart(2, '0');
        const da = String(targetDate.getDate()).padStart(2, '0');
        th.dataset.date = `${y}-${mo}-${da}`;
    });

    renderTable();
}

document.getElementById('prevWeekBtn').addEventListener('click', () => {
    currentWeekStart.setDate(currentWeekStart.getDate() - 7);
    updateWeekDisplay();
});
document.getElementById('nextWeekBtn').addEventListener('click', () => {
    currentWeekStart.setDate(currentWeekStart.getDate() + 7);
    updateWeekDisplay();
});

/**
 * 時間割テーブルを描画する
 * 基本データと変更データを適用してセルを更新する
 * @example
 * renderTable(); // 現在の週の時間割を描画
 */
function renderTable() {
    // クリア
    document.querySelectorAll('.timetable-cell').forEach(cell => {
        cell.innerHTML = '';
        cell.className = 'timetable-cell'; 
        cell.dataset.date = ''; 
    });

    // 日付セット
    const ths = ['th-mon', 'th-tue', 'th-wed', 'th-thu', 'th-fri'];
    const days = ['月','火','水','木','金'];
    days.forEach((d, idx) => {
        const dateStr = document.getElementById(ths[idx]).dataset.date;
        document.querySelectorAll(`.timetable-cell[data-day="${d}"]`).forEach(cell => {
            cell.dataset.date = dateStr;
        });
    });

    // 各日付ごとに適用される時間割を判定して描画
    days.forEach((d, idx) => {
        const dateStr = document.getElementById(ths[idx]).dataset.date;
        
        // 現在選択中のレコードの適用期間をチェック
        const isOutOfPeriod = !currentRecord || 
            !dateStr || 
            dateStr < currentRecord.startDate || 
            dateStr > currentRecord.endDate;
        
        // 適用期間外の場合はスタイルを適用
        document.querySelectorAll(`.timetable-cell[data-day="${d}"]`).forEach(cell => {
            if (isOutOfPeriod) {
                cell.classList.add('is-out-of-period');
            }
        });
        
        // この日付に適用される時間割レコードを検索
        const applicableRecord = findApplicableRecord(dateStr);
        if (!applicableRecord) return;
        
        // 基本データ展開（この曜日のデータのみ）
        applicableRecord.data.forEach(item => {
            if (item.day !== d) return;
            
            const targetCell = document.querySelector(`.timetable-cell[data-day="${d}"][data-period="${item.period}"]`);
            if (targetCell) {
                const teacherNames = Array.isArray(item.teacherName) ? item.teacherName : (item.teacherName ? [item.teacherName] : []);
                const roomNames = Array.isArray(item.roomName) ? item.roomName : (item.roomName ? [item.roomName] : []);
                updateCellContent(targetCell, item.className, teacherNames, roomNames);
            }
        });

        // 変更データ適用（この日付のデータのみ）
        if (applicableRecord.changes) {
            applicableRecord.changes.forEach(change => {
                if (change.date !== dateStr) return;
                
                const targetCell = document.querySelector(`.timetable-cell[data-day="${d}"][data-period="${change.period}"]`);
                if (targetCell) {
                    const teacherNames = Array.isArray(change.teacherNames) ? change.teacherNames : (change.teacherName ? [change.teacherName] : []);
                    const roomNames = Array.isArray(change.roomNames) ? change.roomNames : (change.roomName ? [change.roomName] : []);
                    updateCellContent(targetCell, change.className, teacherNames, roomNames);
                    targetCell.classList.add('is-changed'); // ハイライト
                }
            });
        }
    });
}

/**
 * 指定日付に適用される時間割レコードを検索する
 * 同じコースで期間が重複する場合は開始日が最も新しいものを優先
 * @param {string} dateStr - 検索対象の日付 (YYYY-MM-DD形式)
 * @returns {Object|null} 適用される時間割レコード、見つからない場合はnull
 * @example
 * const record = findApplicableRecord('2026-01-10');
 * if (record) {
 *     console.log('適用される時間割:', record.course);
 * }
 */
function findApplicableRecord(dateStr) {
    // 現在選択中のコースでフィルタ
    const currentCourse = currentRecord ? currentRecord.course : null;
    if (!currentCourse) return null;
    
    // 同じコースの時間割から、指定日付に適用されるものを検索
    const applicableRecords = savedTimetables.filter(record => {
        if (record.course !== currentCourse) return false;
        if (!record.startDate || !record.endDate) return false;
        return record.startDate <= dateStr && record.endDate >= dateStr;
    });
    
    // 複数ある場合は開始日が最も新しいものを優先
    if (applicableRecords.length > 0) {
        applicableRecords.sort((a, b) => b.startDate.localeCompare(a.startDate));
        return applicableRecords[0];
    }
    
    return null;
}

/**
 * 時間割セルの内容を更新する
 * 授業名、担当教員、教室情報を表示
 * @param {HTMLElement} cell - 更新対象のセル要素
 * @param {string} c - 授業名（空の場合は休講/空き表示）
 * @param {string[]} teacherArr - 担当教員名の配列
 * @param {string[]} roomArr - 教室名の配列
 * @example
 * const cell = document.querySelector('.timetable-cell');
 * updateCellContent(cell, 'Javaプログラミング', ['佐藤 健一'], ['201教室']);
 */
function updateCellContent(cell, c, teacherArr, roomArr) {
    if (!c && (!teacherArr || teacherArr.length === 0) && (!roomArr || roomArr.length === 0)) {
        cell.innerHTML = `<div class="class-content"><div class="class-name text-gray-400">(休講/空き)</div><div class="class-detail"></div></div>`;
        cell.classList.remove('is-filled');
        return;
    }
    
    // 複数の先生をHTML化
    let teacherHtml = '';
    if (teacherArr && teacherArr.length > 0) {
        teacherArr.forEach(teacher => {
            if (teacher) {
                teacherHtml += `<div class="teacher-name"><i class="fa-solid fa-user icon"></i><span>${teacher}</span></div>`;
            }
        });
    }
    
    // 複数の教室をHTML化
    let roomHtml = '';
    if (roomArr && roomArr.length > 0) {
        roomArr.forEach(room => {
            if (room) {
                roomHtml += `<div class="room-name"><i class="fa-solid fa-location-dot icon"></i><span>${room}</span></div>`;
            }
        });
    }
    
    cell.innerHTML = `<div class="class-content"><div class="class-name">${c}</div><div class="class-detail">${teacherHtml}${roomHtml}</div></div>`;
    cell.classList.add('is-filled');
}

// --- モーダル & 変更処理 ---

/**
 * 担当教員入力フィールドの配列を取得する
 * @returns {HTMLSelectElement[]} 担当教員セレクトボックスの配列
 * @example
 * const inputs = getTeacherInputs();
 * inputs.forEach(input => console.log(input.value));
 */
function getTeacherInputs() {
    const teacherSelectionArea = document.getElementById('teacherSelectionArea');
    return Array.from(teacherSelectionArea.querySelectorAll('.teacher-input'));
}

/**
 * 教室入力フィールドの配列を取得する
 * @returns {HTMLSelectElement[]} 教室セレクトボックスの配列
 * @example
 * const inputs = getRoomInputs();
 * inputs.forEach(input => console.log(input.value));
 */
function getRoomInputs() {
    const roomSelectionArea = document.getElementById('roomSelectionArea');
    return Array.from(roomSelectionArea.querySelectorAll('.room-input'));
}

/**
 * 担当教員入力行を追加する
 * 最大5個まで追加可能
 * @example
 * addTeacherRow(); // 新しい担当教員入力フィールドを追加
 */
function addTeacherRow() {
    const teacherSelectionArea = document.getElementById('teacherSelectionArea');
    const currentCount = getTeacherInputs().length;
    if (currentCount >= 5) {
        alert('最大5個まで追加できます。');
        return;
    }
    
    const rowDiv = document.createElement('div');
    rowDiv.className = 'teacher-input-row';
    
    const select = document.createElement('select');
    select.className = 'teacher-input modal-select';
    select.innerHTML = '<option value="">(選択してください)</option><option value="佐藤 健一">佐藤 健一</option><option value="鈴木 花子">鈴木 花子</option><option value="高橋 誠">高橋 誠</option><option value="田中 優子">田中 優子</option><option value="渡辺 剛">渡辺 剛</option><option value="伊藤 直人">伊藤 直人</option><option value="山本 さくら">山本 さくら</option>';
    
    const arrowDiv = document.createElement('div');
    arrowDiv.className = 'select-arrow';
    arrowDiv.innerHTML = '<i class="fa-solid fa-chevron-down text-xs"></i>';
    
    const removeBtn = document.createElement('button');
    removeBtn.className = 'remove-teacher-btn';
    removeBtn.textContent = '×';
    removeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        rowDiv.remove();
        updateTeacherRemoveButtons();
    });
    
    rowDiv.appendChild(select);
    rowDiv.appendChild(arrowDiv);
    rowDiv.appendChild(removeBtn);
    teacherSelectionArea.appendChild(rowDiv);
    
    updateTeacherRemoveButtons();
}

/**
 * 教室入力行を追加する
 * 最大5個まで追加可能
 * @example
 * addRoomRow(); // 新しい教室入力フィールドを追加
 */
function addRoomRow() {
    const roomSelectionArea = document.getElementById('roomSelectionArea');
    const currentCount = getRoomInputs().length;
    if (currentCount >= 5) {
        alert('最大5個まで追加できます。');
        return;
    }
    
    const rowDiv = document.createElement('div');
    rowDiv.className = 'room-input-row';
    
    const select = document.createElement('select');
    select.className = 'room-input modal-select';
    select.innerHTML = '<option value="">(選択してください)</option><option value="201教室">201教室</option><option value="202教室">201教室</option><option value="301演習室">301演習室</option><option value="302演習室">302演習室</option><option value="4F大講義室">4F大講義室</option><option value="別館Lab A">別館Lab A</option><option value="別館Lab B">別館Lab B</option>';
    
    const arrowDiv = document.createElement('div');
    arrowDiv.className = 'select-arrow';
    arrowDiv.innerHTML = '<i class="fa-solid fa-chevron-down text-xs"></i>'; 
    
    const removeBtn = document.createElement('button');
    removeBtn.className = 'remove-room-btn';
    removeBtn.textContent = '×';
    removeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        rowDiv.remove();
        updateRoomRemoveButtons();
    });
    
    rowDiv.appendChild(select);
    rowDiv.appendChild(arrowDiv);
    rowDiv.appendChild(removeBtn);
    roomSelectionArea.appendChild(rowDiv);
    
    updateRoomRemoveButtons();
}

/**
 * 担当教員削除ボタンの表示/非表示を更新する
 * 入力フィールドが1つの場合は削除ボタンを非表示にする
 * @example
 * updateTeacherRemoveButtons(); // 削除ボタンの表示状態を更新
 */
function updateTeacherRemoveButtons() {
    const inputs = getTeacherInputs();
    inputs.forEach(input => {
        const row = input.closest('.teacher-input-row');
        const removeBtn = row.querySelector('.remove-teacher-btn');
        removeBtn.style.display = inputs.length > 1 ? 'block' : 'none';
    });
}

/**
 * 教室削除ボタンの表示/非表示を更新する
 * 入力フィールドが1つの場合は削除ボタンを非表示にする
 * @example
 * updateRoomRemoveButtons(); // 削除ボタンの表示状態を更新
 */
function updateRoomRemoveButtons() {
    const inputs = getRoomInputs();
    inputs.forEach(input => {
        const row = input.closest('.room-input-row');
        const removeBtn = row.querySelector('.remove-room-btn');
        removeBtn.style.display = inputs.length > 1 ? 'block' : 'none';
    });
}

/**
 * セレクトボックスの値を設定する
 * オプションに存在する値の場合はその項目を選択、存在しない場合は空にする
 * @param {HTMLSelectElement} sel - 対象のセレクトボックス
 * @param {string} val - 設定する値
 * @example
 * setSelectValue(document.getElementById('inputClassName'), 'Javaプログラミング');
 */
function setSelectValue(sel, val) {
    let found = false;
    for(let i=0; i<sel.options.length; i++) { if(sel.options[i].value === val) { sel.selectedIndex = i; found = true; break; } }
    if(!found) sel.value = "";
}

const modal = document.getElementById('changeModal');
const modalTitle = document.getElementById('modalTitle');
const inputDate = document.getElementById('targetDateInput');
const inputClassName = document.getElementById('inputClassName');
const inputTeacherName = document.getElementById('inputTeacherName');
const inputRoomName = document.getElementById('inputRoomName');
const btnCancel = document.getElementById('btnCancel');
const btnUpdate = document.getElementById('btnUpdate');
let editingCell = null;
let originalValues = {}; // 元の値を保存

document.querySelectorAll('.timetable-cell').forEach(cell => {
    cell.addEventListener('click', function() {
        if (!currentRecord) {
            alert('左のリストから変更したい時間割を選択してください。');
            return;
        }

        // 適用期間外のセルの場合はアラートを表示
        if (this.classList.contains('is-out-of-period')) {
            alert('この時間割は適用期間外のため編集できません。');
            return;
        }

        const date = this.dataset.date;
        const targetRecord = findApplicableRecord(date);

        editingCell = this;
        const day = this.dataset.day;
        const period = this.dataset.period;

        modalTitle.textContent = `${day}曜日 ${period}限 の変更`;
        inputDate.value = date; // 日付をセット

        // 変更済みセルかどうか判定して「変更を取り消す」ボタンの表示を切り替え
        const btnRevert = document.getElementById('btnRevert');
        const hasChange = targetRecord && targetRecord.changes && 
            targetRecord.changes.some(ch => ch.date === date && ch.period === period);
        
        if (hasChange) {
            btnRevert.classList.remove('hidden');
        } else {
            btnRevert.classList.add('hidden');
        }

        // 現在の値をセット
        const cName = this.querySelector('.class-name')?.textContent.replace('(休講/空き)', '') || '';
        const teacherElems = this.querySelectorAll('.teacher-name span');
        const roomElems = this.querySelectorAll('.room-name span');

        setSelectValue(inputClassName, cName);
        
        // 複数の先生を取得
        const teacherInputs = getTeacherInputs();
        
        // 先生フィールドをリセット（最初のフィールドのみ残す）
        while (teacherInputs.length > 1) {
            teacherInputs[teacherInputs.length - 1].closest('.teacher-input-row').remove();
            teacherInputs.pop();
        }
        
        // 先生データをセット
        teacherInputs.forEach((input, idx) => {
            setSelectValue(input, teacherElems[idx]?.textContent || '');
        });
        
        // 複数の教室を取得
        const roomInputs = getRoomInputs();
        
        // 教室フィールドをリセット（最初のフィールドのみ残す）
        while (roomInputs.length > 1) {
            roomInputs[roomInputs.length - 1].closest('.room-input-row').remove();
            roomInputs.pop();
        }
        
        // 教室データをセット
        roomInputs.forEach((input, idx) => {
            setSelectValue(input, roomElems[idx]?.textContent || '');
        });

        updateTeacherRemoveButtons();
        updateRoomRemoveButtons();

        // 元の値を保存
        originalValues = {
            className: cName,
            teacherNames: Array.from(teacherElems).map(el => el.textContent),
            roomNames: Array.from(roomElems).map(el => el.textContent)
        };

        modal.classList.remove('hidden');
        document.body.classList.add('modal-open');
    });
});

btnCancel.addEventListener('click', () => {
    modal.classList.add('hidden');
    document.body.classList.remove('modal-open');
});

// 変更を取り消すボタン
document.getElementById('btnRevert').addEventListener('click', () => {
    if (!editingCell) return;
    
    const date = inputDate.value;
    const period = editingCell.dataset.period;
    
    // 該当日付に適用されるレコードを検索
    const targetRecord = findApplicableRecord(date);
    if (!targetRecord) return;
    
    // 変更を削除
    const changeIndex = targetRecord.changes.findIndex(ch => ch.date === date && ch.period === period);
    if (changeIndex !== -1) {
        targetRecord.changes.splice(changeIndex, 1);
    }
    
    // 画面更新
    renderTable();
    renderSidebarList();
    
    modal.classList.add('hidden');
    document.body.classList.remove('modal-open');
});

// Initialize remove buttons visibility on page load
window.addEventListener('DOMContentLoaded', () => {
    updateTeacherRemoveButtons();
    updateRoomRemoveButtons();
}, { once: true });

// Add button event listeners
document.getElementById('addTeacherBtn').addEventListener('click', (e) => {
    e.preventDefault();
    addTeacherRow();
});
document.getElementById('addRoomBtn').addEventListener('click', (e) => {
    e.preventDefault();
    addRoomRow();
});

btnUpdate.addEventListener('click', () => {
    if (!editingCell || !currentRecord) return;

    const date = inputDate.value;
    const day = editingCell.dataset.day;
    const period = editingCell.dataset.period;
    const c = inputClassName.value;
    
    // この日付に適用されるレコードを検索
    const targetRecord = findApplicableRecord(date);
    if (!targetRecord) {
        alert('この日付に適用される時間割がありません。');
        return;
    }
    
    // 複数の先生を取得
    const teacherInputs = getTeacherInputs();
    const teacherNames = teacherInputs.map(input => input.value).filter(val => val !== '');
    
    // 複数の教室を取得
    const roomInputs = getRoomInputs();
    const roomNames = roomInputs.map(input => input.value).filter(val => val !== '');

    // 値が変更されたかチェック
    const isClassNameChanged = c !== originalValues.className;
    const isTeacherNamesChanged = JSON.stringify(teacherNames) !== JSON.stringify(originalValues.teacherNames);
    const isRoomNamesChanged = JSON.stringify(roomNames) !== JSON.stringify(originalValues.roomNames);

    // 何も変更されていない場合は処理を終了
    if (!isClassNameChanged && !isTeacherNamesChanged && !isRoomNamesChanged) {
        modal.classList.add('hidden');
        document.body.classList.remove('modal-open');
        return;
    }

    // 変更データを該当レコードに保存
    const existingChangeIndex = targetRecord.changes.findIndex(ch => ch.date === date && ch.period === period);
    
    const newChange = {
        date: date,
        day: day,
        period: period,
        className: c,
        teacherNames: teacherNames,
        roomNames: roomNames
    };

    if (existingChangeIndex !== -1) {
        targetRecord.changes[existingChangeIndex] = newChange;
    } else {
        targetRecord.changes.push(newChange);
    }

    // 画面更新
    renderTable();
    renderSidebarList(); // バッジ更新のため

    modal.classList.add('hidden');
    document.body.classList.remove('modal-open');
});

// 変更を保存ボタン (サーバー送信などを想定、ここではアラートのみ)
document.getElementById('saveChangesBtn').addEventListener('click', () => {
    if(!currentRecord) return;
    alert('変更内容を保存しました。');
    // ここでJSONなどをサーバーに送る
});
