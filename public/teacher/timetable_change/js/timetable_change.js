// timetable_change.js
// --- データ管理 ---
// --- データ管理 ---
let savedTimetables = [];

// PHPからデータが渡ってきているか確認して格納
if (typeof dbTimetableData !== 'undefined' && Array.isArray(dbTimetableData)) {
    savedTimetables = dbTimetableData;
}

let currentRecord = null; // 現在選択中の時間割レコード
let currentWeekStart = null; // 現在表示中の週の月曜日
let originalValues = {}; // 変更前の値を保持

// --- マスタデータ操作用ヘルパー ---
/**
 * 現在選択中のコースに紐づくマスタデータを取得
 */
function getCurrentCourseMasterData() {
    // currentRecord がない、またはマスタデータがない場合は空配列
    if (!currentRecord || !dbMasterData || !dbMasterData[currentRecord.courseId]) {
        return [];
    }
    return dbMasterData[currentRecord.courseId];
}

/**
 * 先生リストの抽出（重複排除）
 */
function getAvailableTeachers() {
    const data = getCurrentCourseMasterData();
    const teachers = new Map();
    data.forEach(row => {
        if (row.teacher_id && row.teacher_name) {
            teachers.set(row.teacher_id, row.teacher_name);
        }
    });
    return Array.from(teachers, ([id, name]) => ({ id, name }));
}

/**
 * 教室リストの抽出（重複排除）
 */
function getAvailableRooms() {
    const data = getCurrentCourseMasterData();
    const rooms = new Map();
    data.forEach(row => {
        if (row.room_id && row.room_name) {
            rooms.set(row.room_id, row.room_name);
        }
    });
    return Array.from(rooms, ([id, name]) => ({ id, name }));
}

// ドロップダウンの重複制御用
function updateDropdownOptions(targetClass) {
    const selects = Array.from(document.querySelectorAll(`.${targetClass}`));
    const selectedValues = selects.map(s => s.value).filter(v => v !== "");

    selects.forEach(select => {
        const currentValue = select.value;
        const options = Array.from(select.options);
        options.forEach(option => {
            if (option.value === "") return;
            if (selectedValues.includes(option.value) && option.value !== currentValue) {
                option.disabled = true;
                option.style.backgroundColor = '#f3f4f6';
            } else {
                option.disabled = false;
                option.style.backgroundColor = '';
            }
        });
    });
}

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
    const toggleBtn = document.getElementById('courseDropdownToggle');
    
    // ドロップダウンの有効/無効切り替え
    if (mode === 'select') {
        toggleBtn.classList.remove('disabled');
        toggleBtn.removeAttribute('disabled');
    } else {
        toggleBtn.classList.add('disabled');
        toggleBtn.setAttribute('disabled', 'true');
    }
    // リスト再描画
    renderSidebarList(mode);
}

/**
 * 初期表示時の時間割自動選択処理
 * 概要: IDが最も若いコースを選択し、その中で優先度（適用中 > 次回 > 未来 > 過去）の高い時間割を表示する
 */
function selectInitialTimetable() {
    // データがない場合は何もしない
    if (!savedTimetables || savedTimetables.length === 0) return;

    // 1. 最も若いコースIDを持つデータを特定するためにソート
    // コースIDの昇順でソート
    const sortedByCourse = [...savedTimetables].sort((a, b) => {
        return (a.courseId || 0) - (b.courseId || 0);
    });
    
    // 最も若いコースIDを取得
    const targetCourseId = sortedByCourse[0].courseId;
    const targetCourseName = sortedByCourse[0].course;

    // 2. そのコースの中で、statusTypeの優先度に従ってレコードを選択
    // statusType: 1=適用中, 2=次回, 3~=次回以降, 0=過去
    const courseRecords = savedTimetables.filter(r => r.courseId == targetCourseId);

    let targetRecord = null;

    // 優先順位1: 適用中 (statusType = 1)
    targetRecord = courseRecords.find(r => r.statusType == 1);

    // 優先順位2: 次回 (statusType = 2)
    if (!targetRecord) {
        targetRecord = courseRecords.find(r => r.statusType == 2);
    }

    // 優先順位3: 次回以降 (statusType >= 3) の中で最も若いもの
    if (!targetRecord) {
        const futureRecords = courseRecords.filter(r => r.statusType >= 3);
        if (futureRecords.length > 0) {
            futureRecords.sort((a, b) => a.statusType - b.statusType);
            targetRecord = futureRecords[0];
        }
    }

    // 優先順位4: 見つからなければ（過去データのみの場合など）、そのコースの先頭データ
    if (!targetRecord && courseRecords.length > 0) {
        targetRecord = courseRecords[0];
    }

    // 対象が見つかった場合のUI反映処理
    if (targetRecord) {
        // ドロップダウンの表示テキストを更新（renderSidebarListのフィルタリングに使用されるため重要）
        const toggleBtn = document.getElementById('courseDropdownToggle');
        if (toggleBtn) {
            toggleBtn.querySelector('.current-value').textContent = targetRecord.course;
        }

        // リストを描画 (selectモード)
        // これによりサイドバーに該当コースのリストが生成される
        renderSidebarList('select');

        // サイドバーの該当項目をプログラムからクリックして表示を実行
        // DOM描画待ちのためにわずかに遅延させる
        setTimeout(() => {
            // data-id属性を使ってリスト項目を検索
            const itemLink = document.querySelector(`.saved-item[data-id="${targetRecord.id}"] a`);
            if (itemLink) {
                itemLink.click();
            }
        }, 50);
    }
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
    
    // 既存のリスト項目をクリア（ラベル以外）
    const items = container.querySelectorAll('li:not(.is-group-label)');
    items.forEach(item => item.remove());

    let filteredRecords = [];

    // --- フィルタリングとソート ---
    if (mode === 'select') {
        // ■「選択」モード
        // ドロップダウンで選択中のコース名を取得
        const toggleBtn = document.querySelector('#courseDropdownToggle');
        const currentCourseName = toggleBtn ? toggleBtn.querySelector('.current-value').textContent.trim() : "";
        
        // 未選択状態でなければフィルタリング実行
        if (currentCourseName !== "（未選択）" && currentCourseName !== "コースを選択してください") {
            // コース名が一致し、かつ「過去(0)」以外のものを表示
            filteredRecords = savedTimetables.filter(item => 
                item.course === currentCourseName && item.statusType != 0
            );
        }
        
        // ソート: 適用中(1) -> 次回(2) -> 次回以降(3~) の昇順
        filteredRecords.sort((a, b) => a.statusType - b.statusType);

    } else if (mode === 'current') {
        // ■「現在反映」モード
        // コースに関係なく、ステータスが「適用中(1)」のもの全て
        filteredRecords = savedTimetables.filter(item => item.statusType == 1);
        
        // コースID順にソートして見やすくする
        filteredRecords.sort((a, b) => (a.courseId || 0) - (b.courseId || 0));

    } else if (mode === 'next') {
        // ■「次回の反映」モード
        // コースに関係なく、ステータスが「次回(2)」のもの全て
        filteredRecords = savedTimetables.filter(item => item.statusType == 2);
        
        // コースID順にソート
        filteredRecords.sort((a, b) => (a.courseId || 0) - (b.courseId || 0));
    }

    // --- リスト生成 ---
    filteredRecords.forEach(record => {
        const hasChanges = record.changes && record.changes.length > 0;
        const newItem = document.createElement('li'); 
        newItem.className = `nav-item saved-item ${hasChanges ? 'has-changes' : ''}`;
        newItem.setAttribute('data-id', record.id);
        
        // アクティブ状態の維持
        if (currentRecord && currentRecord.id == record.id) {
            newItem.classList.add('active');
        }
        
        let badgeHtml = hasChanges ? '<span class="changed-badge"></span>' : '';
        
        // 日付整形 (YYYY-MM-DD -> M/D~)
        const dateParts = record.startDate ? record.startDate.split('-') : [];
        const dateLabel = dateParts.length === 3 ? `${parseInt(dateParts[1])}/${parseInt(dateParts[2])}~` : '';

        // ステータスごとの表示設定
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
        } else {
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
    // プログラムからの呼び出し等でelementがない場合のガード
    if (element && element.parentElement) {
        element.parentElement.classList.add('active');
    } else {
        // elementが直接 <li> の場合や、要素指定がない場合の検索
        const target = document.querySelector(`.saved-item[data-id="${id}"]`);
        if (target) target.classList.add('active');
    }

    // データ取得
    currentRecord = savedTimetables.find(r => r.id === id);
    
    if (currentRecord) {
        // 表示初期化
        updateHeaderDisplay(currentRecord);
        
        // 表示する週の基準日を決定
        let targetDate;
        
        if (isRecordActive(currentRecord)) {
            // 適用中の場合は「今日」を基準にする
            targetDate = new Date();
        } else {
            // それ以外（次回・未来・過去）は「開始日」を基準にする
            // ※startDateがない場合は今日にするガードを入れる
            targetDate = currentRecord.startDate ? new Date(currentRecord.startDate) : new Date();
        }

        // 月曜日（週の始まり）まで戻す計算
        const day = targetDate.getDay();
        // getDay(): 日=0, 月=1, ... 土=6
        // 月曜(1)なら引かない(0)、火曜(2)なら1日引く... 日曜(0)なら6日引く
        const diff = targetDate.getDate() - (day === 0 ? 6 : day - 1);
        
        currentWeekStart = new Date(targetDate);
        currentWeekStart.setDate(diff);
        
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
    // 1. コース変更時にリストを再描画
    renderSidebarList('select');
    
    // 2. リストの一番上の項目を自動選択
    // DOM描画待ちのために少し遅延させてからクリックを実行
    setTimeout(() => {
        const container = document.getElementById('savedListContainer');
        // リスト内の最初のリンク要素を取得
        const firstItemLink = container.querySelector('.saved-item a');
        
        if (firstItemLink) {
            // 項目があればクリックして表示（selectTimetableが実行されます）
            firstItemLink.click();
        } else {
            // 項目が1つもない場合のクリア処理
            document.getElementById('mainCourseDisplay').textContent = "（データなし）";
            document.querySelectorAll('.timetable-cell').forEach(cell => {
                cell.innerHTML = '';
                cell.className = 'timetable-cell'; // クラスもリセット
                cell.dataset.date = '';
                delete cell.dataset.subjectId;
                delete cell.dataset.teacherIds;
                delete cell.dataset.roomIds;
            });
            document.getElementById('weekDisplay').textContent = "（時間割データがありません）";
            currentRecord = null;
        }
    }, 50);
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
        // 編集ハイライトなどもクリア
        cell.classList.remove('is-filled', 'is-changed', 'is-out-of-period', 'is-edited');
    });

    // 今日の日付を取得 (YYYY-MM-DD形式)
    const todayStr = new Date().toISOString().split('T')[0];

    // 日付セット
    const ths = ['th-mon', 'th-tue', 'th-wed', 'th-thu', 'th-fri'];
    const days = ['月','火','水','木','金'];
    
    // ヘッダーの日付情報をセルに伝播
    days.forEach((d, idx) => {
        const th = document.getElementById(ths[idx]);
        if (!th) return;
        
        const dateStr = th.dataset.date;
        document.querySelectorAll(`.timetable-cell[data-day="${d}"]`).forEach(cell => {
            cell.dataset.date = dateStr;
        });
    });

    // 各日付ごとに適用される時間割を判定して描画
    days.forEach((d, idx) => {
        const th = document.getElementById(ths[idx]);
        if (!th) return;
        const dateStr = th.dataset.date;
        
        // 1. 期間外チェック
        const isOutOfPeriod = !currentRecord || 
            !dateStr || 
            (currentRecord.startDate && dateStr < currentRecord.startDate) || 
            (currentRecord.endDate && dateStr > currentRecord.endDate);
        
        // 2. 過去日付チェック (今日より前なら編集不可)
        const isPastDate = dateStr && dateStr < todayStr;

        // 適用期間外 または 過去の日付 の場合はスタイルを適用
        document.querySelectorAll(`.timetable-cell[data-day="${d}"]`).forEach(cell => {
            // 既存の 'is-out-of-period' クラスを活用して編集不可・グレーアウトにする
            if (isOutOfPeriod || isPastDate) {
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
                // 配列かどうか確認して正規化
                let tNames = [];
                if (Array.isArray(item.teacherNames)) tNames = item.teacherNames;
                else if (item.teacherName) tNames = [item.teacherName]; // 旧データ互換

                let rNames = [];
                if (Array.isArray(item.roomNames)) rNames = item.roomNames;
                else if (item.roomName) rNames = [item.roomName]; // 旧データ互換

                updateCellContent(targetCell, item.className, tNames, rNames);
                
                // データ属性にIDもセットしておく（編集モーダル表示用）
                if(item.subjectId) targetCell.dataset.subjectId = item.subjectId;
                
                // ID配列のセット
                let tIds = item.teacherIds || (item.teacherId ? [item.teacherId] : []);
                targetCell.dataset.teacherIds = JSON.stringify(tIds);
                
                let rIds = item.roomIds || (item.roomId ? [item.roomId] : []);
                targetCell.dataset.roomIds = JSON.stringify(rIds);
            }
        });

        // 変更データ適用（この日付のデータのみ）
        if (applicableRecord.changes) {
            applicableRecord.changes.forEach(change => {
                if (change.date !== dateStr) return;
                
                const targetCell = document.querySelector(`.timetable-cell[data-day="${d}"][data-period="${change.period}"]`);
                if (targetCell) {
                    // 基本データと同様に配列チェック
                    let tNames = Array.isArray(change.teacherNames) ? change.teacherNames : (change.teacherName ? [change.teacherName] : []);
                    let rNames = Array.isArray(change.roomNames) ? change.roomNames : (change.roomName ? [change.roomName] : []);

                    updateCellContent(targetCell, change.className, tNames, rNames);
                    targetCell.classList.add('is-changed'); // ハイライト
                    
                    // 変更データに含まれるID情報でdatasetを上書き
                    if(change.subjectId) targetCell.dataset.subjectId = change.subjectId;
                    if(change.teacherIds) targetCell.dataset.teacherIds = JSON.stringify(change.teacherIds);
                    if(change.roomIds) targetCell.dataset.roomIds = JSON.stringify(change.roomIds);
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
function addTeacherRow(selectedValue = null) {
    const teacherSelectionArea = document.getElementById('teacherSelectionArea');
    const currentCount = teacherSelectionArea.querySelectorAll('.teacher-input').length;
    
    if (currentCount >= 5) {
        alert('最大5個まで追加できます。');
        return;
    }
    
    const rowDiv = document.createElement('div');
    rowDiv.className = 'teacher-input-row';
    
    const select = document.createElement('select');
    select.className = 'teacher-input modal-select';
    
    // 重複チェックイベント
    select.addEventListener('change', () => updateDropdownOptions('teacher-input'));

    // マスタデータからOption生成
    let html = '<option value="">(選択してください)</option>';
    const teachers = getAvailableTeachers();
    
    teachers.forEach(t => {
        // IDで比較 (数値/文字列の型違いを吸収するため == を使用)
        const isSelected = (selectedValue == t.id) ? 'selected' : '';
        html += `<option value="${t.id}" ${isSelected}>${t.name}</option>`;
    });
    select.innerHTML = html;
    
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
        updateDropdownOptions('teacher-input');
    });
    
    rowDiv.appendChild(select);
    rowDiv.appendChild(arrowDiv);
    rowDiv.appendChild(removeBtn);
    teacherSelectionArea.appendChild(rowDiv);
    
    updateTeacherRemoveButtons();
    updateDropdownOptions('teacher-input');
}

/**
 * 教室入力行を追加する
 * 最大5個まで追加可能
 * @example
 * addRoomRow(); // 新しい教室入力フィールドを追加
 */
function addRoomRow(selectedValue = null) {
    const roomSelectionArea = document.getElementById('roomSelectionArea');
    const currentCount = roomSelectionArea.querySelectorAll('.room-input').length;
    
    if (currentCount >= 5) {
        alert('最大5個まで追加できます。');
        return;
    }
    
    const rowDiv = document.createElement('div');
    rowDiv.className = 'room-input-row';
    
    const select = document.createElement('select');
    select.className = 'room-input modal-select';
    
    select.addEventListener('change', () => updateDropdownOptions('room-input'));

    let html = '<option value="">(選択してください)</option>';
    const rooms = getAvailableRooms();
    
    rooms.forEach(r => {
        const isSelected = (selectedValue == r.id) ? 'selected' : '';
        html += `<option value="${r.id}" ${isSelected}>${r.name}</option>`;
    });
    select.innerHTML = html;
    
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
        updateDropdownOptions('room-input');
    });
    
    rowDiv.appendChild(select);
    rowDiv.appendChild(arrowDiv);
    rowDiv.appendChild(removeBtn);
    roomSelectionArea.appendChild(rowDiv);
    
    updateRoomRemoveButtons();
    updateDropdownOptions('room-input');
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

/**
 * timetable-cell click event
 * 概要: 時間割セルがクリックされたときの処理。
 * 複数の先生・教室データを正しく復元してモーダルに表示します。
 */
document.querySelectorAll('.timetable-cell').forEach(cell => {
    cell.addEventListener('click', function() {
        // 1. ガード処理
        if (!currentRecord) {
            alert('編集する時間割を選択してください。');
            return;
        }

        // 期間外・過去のセルはクリック無効（renderTableでのクラス付与に依存）
        if (this.classList.contains('is-out-of-period')) {
            return;
        }

        // 現在操作中のセルを保持
        currentCell = this;
        editingCell = this; // 互換性のため両方保持
        
        // 日付・時限の取得
        const date = inputDate.value; // 日付選択欄、またはセルのデータから取得
        // ※ renderTableでセルに dataset.date を仕込んでいるのでそれを使うのが確実
        const cellDate = this.dataset.date;
        const day = this.dataset.day;
        const period = this.dataset.period;

        // モーダルの日付欄・タイトル更新
        inputDate.value = cellDate; 
        modalTitle.textContent = `${day}曜日 ${period}限 の変更`;

        // ----------------------------------------------------
        // 2. データの取得と復元
        // ----------------------------------------------------
        
        // 科目ID
        const currentSubjectId = this.dataset.subjectId || '';
        
        // 先生ID配列の取得
        let currentTeacherIds = [];
        try {
            if (this.dataset.teacherIds) {
                currentTeacherIds = JSON.parse(this.dataset.teacherIds);
            } else if (this.dataset.teacherId) {
                currentTeacherIds = [this.dataset.teacherId];
            }
        } catch (e) { 
            console.error('Teacher ID Parse Error', e);
            currentTeacherIds = [];
        }

        // 教室ID配列の取得
        let currentRoomIds = [];
        try {
            if (this.dataset.roomIds) {
                currentRoomIds = JSON.parse(this.dataset.roomIds);
            } else if (this.dataset.roomId) {
                currentRoomIds = [this.dataset.roomId];
            }
        } catch (e) { 
            console.error('Room ID Parse Error', e);
            currentRoomIds = [];
        }

        // ----------------------------------------------------
        // 3. モーダルUIの構築
        // ----------------------------------------------------

        // 科目ドロップダウン生成 & 初期値選択
        renderSubjectDropdown(currentSubjectId);

        // 先生エリアのリセットと復元
        const teacherSelectionArea = document.getElementById('teacherSelectionArea');
        teacherSelectionArea.innerHTML = '';
        if (currentTeacherIds.length > 0) {
            currentTeacherIds.forEach(id => addTeacherRow(id));
        } else {
            addTeacherRow(); // 空行
        }
        
        // 教室エリアのリセットと復元
        const roomSelectionArea = document.getElementById('roomSelectionArea');
        roomSelectionArea.innerHTML = '';
        if (currentRoomIds.length > 0) {
            currentRoomIds.forEach(id => addRoomRow(id));
        } else {
            addRoomRow(); // 空行
        }

        // 削除ボタンやドロップダウンの整合性更新
        updateTeacherRemoveButtons();
        updateRoomRemoveButtons();
        updateDropdownOptions('teacher-input');
        updateDropdownOptions('room-input');

        // ----------------------------------------------------
        // 4. 「変更を戻す」ボタンの表示制御
        // ----------------------------------------------------
        
        // 現在のレコードから、この日付・時限の変更データが存在するか確認
        const targetRecord = findApplicableRecord(cellDate);
        let hasExistingChange = false;

        if (targetRecord && targetRecord.changes) {
            hasExistingChange = targetRecord.changes.some(ch => ch.date === cellDate && ch.period === period);
        }

        const btnRevert = document.getElementById('btnRevert');
        if (hasExistingChange) {
            btnRevert.style.display = 'block'; // 変更があるなら表示
        } else {
            btnRevert.style.display = 'none';  // 基本データなら非表示
        }

        // ----------------------------------------------------
        // 5. 変更検知用の初期値を保存
        // ----------------------------------------------------
        const teacherInputs = getTeacherInputs();
        const roomInputs = getRoomInputs();

        originalValues = {
            subjectId: currentSubjectId,
            teacherIds: teacherInputs.map(input => input.value).filter(val => val !== ''),
            roomIds: roomInputs.map(input => input.value).filter(val => val !== '')
        };

        // モーダル表示
        modal.classList.remove('hidden');
        document.body.classList.add('modal-open');
        
        // フォーカス
        setTimeout(() => inputClassName.focus(), 100);
    });
});

function renderSubjectDropdown(selectedId = '') {
    const inputClassName = document.getElementById('inputClassName');
    // マスタデータ取得
    const masterData = getCurrentCourseMasterData();
    
    // HTML生成
    inputClassName.innerHTML = '<option value="">(休講/空き)</option>';

    masterData.forEach(item => {
        const option = document.createElement('option');
        option.value = item.subject_id;
        option.textContent = item.subject_name;
        
        if (item.subject_id == selectedId) {
            option.selected = true;
        }
        
        // オートフィル用データ
        option.dataset.defTeacherId = item.teacher_id || '';
        option.dataset.defRoomId = item.room_id || '';
        
        inputClassName.appendChild(option);
    });
}

// 科目変更時のオートフィル（create_timetable.jsと同様）
document.getElementById('inputClassName').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const defTeacherId = selectedOption.dataset.defTeacherId;
    const defRoomId = selectedOption.dataset.defRoomId;
    
    // 先生・教室が空の場合のみオートフィル
    if (defTeacherId) {
        const tInputs = document.querySelectorAll('.teacher-input');
        if (tInputs.length > 0 && !tInputs[0].value) tInputs[0].value = defTeacherId;
    }
    if (defRoomId) {
        const rInputs = document.querySelectorAll('.room-input');
        if (rInputs.length > 0 && !rInputs[0].value) rInputs[0].value = defRoomId;
    }
});

btnCancel.addEventListener('click', () => {
    modal.classList.add('hidden');
    document.body.classList.remove('modal-open');
});

// 変更を取り消すボタン
document.getElementById('btnRevert').addEventListener('click', () => {
    if (!editingCell) return;
    
    // 日付と時限を取得
    const date = inputDate.value;
    const period = editingCell.dataset.period;
    
    // 該当日付に適用されるレコードを検索
    const targetRecord = findApplicableRecord(date);
    if (!targetRecord || !targetRecord.changes) return;
    
    // 確認ダイアログ（誤操作防止）
    if (!confirm('変更を取り消して、元の時間割に戻しますか？')) {
        return;
    }

    // 変更データを検索して削除
    const changeIndex = targetRecord.changes.findIndex(ch => ch.date === date && ch.period === period);
    if (changeIndex !== -1) {
        targetRecord.changes.splice(changeIndex, 1);
        
        // 画面更新 (ここが重要)
        // データ削除後に renderTable を呼ぶと、renderTable 内のロジックで
        // 自動的に「基本データ (currentRecord.data)」が表示されます。
        renderTable();
        
        // サイドバーのバッジ更新
        renderSidebarList('select'); 
        
        alert('変更を取り消しました。');
    }
    
    // モーダルを閉じる
    modal.classList.add('hidden');
    document.body.classList.remove('modal-open');
});

// 初期化処理
window.addEventListener('DOMContentLoaded', () => {
    // 既存の renderSidebarList() だけの呼び出しを削除し、
    // 自動選択ロジックを含んだ関数を呼び出す
    selectInitialTimetable();
    
    // 削除ボタンの初期表示更新などはそのまま維持
    updateTeacherRemoveButtons();
    updateRoomRemoveButtons();
});

// Add button event listeners
document.getElementById('addTeacherBtn').addEventListener('click', (e) => {
    e.preventDefault();
    addTeacherRow();
});
document.getElementById('addRoomBtn').addEventListener('click', (e) => {
    e.preventDefault();
    addRoomRow();
});

/**
 * 変更反映ボタンクリックイベント (DB準拠版)
 * 概要：モーダルの入力値を収集し、対象レコードの changes 配列を更新する
 */
btnUpdate.addEventListener('click', () => {
    if (!editingCell || !currentRecord) return;

    // 1. 変更対象の日付・時限を特定
    const date = inputDate.value;
    const day = editingCell.dataset.day;
    const period = editingCell.dataset.period;
    
    // 2. この日付に適用されるレコードを再確認（念のため）
    const targetRecord = findApplicableRecord(date);
    if (!targetRecord) {
        alert('この日付に適用される時間割が見つかりません。');
        return;
    }

    // 3. 入力値の収集（IDと名前の両方を取得）
    const inputClassName = document.getElementById('inputClassName');
    const subjectId = inputClassName.value;
    const subjectName = subjectId ? inputClassName.options[inputClassName.selectedIndex].text : "";

    // 先生（複数）
    const teacherInputs = getTeacherInputs();
    const teacherIds = teacherInputs.map(input => input.value).filter(val => val !== '');
    const teacherNames = teacherInputs.filter(input => input.value).map(input => input.options[input.selectedIndex].text);

    // 教室（複数）
    const roomInputs = getRoomInputs();
    const roomIds = roomInputs.map(input => input.value).filter(val => val !== '');
    const roomNames = roomInputs.filter(input => input.value).map(input => input.options[input.selectedIndex].text);

    // 4. 値が変更されたかチェック
    // DB連携用にIDで比較するのが確実
    const isSubjectChanged = subjectId != (originalValues.subjectId || "");
    const isTeacherChanged = JSON.stringify(teacherIds) !== JSON.stringify(originalValues.teacherIds || []);
    const isRoomChanged = JSON.stringify(roomIds) !== JSON.stringify(originalValues.roomIds || []);

    // 変更がない場合は閉じるだけ
    if (!isSubjectChanged && !isTeacherChanged && !isRoomChanged) {
        modal.classList.add('hidden');
        document.body.classList.remove('modal-open');
        return;
    }

    // 5. 変更データオブジェクトの作成
    const newChange = {
        date: date,
        day: day,
        period: period,
        // 表示用
        className: subjectName, 
        teacherNames: teacherNames,
        roomNames: roomNames,
        // 保存用IDデータ (DB登録に必要)
        subjectId: subjectId,
        teacherIds: teacherIds,
        roomIds: roomIds
    };

    // 6. changes 配列への保存（既存があれば上書き、なければ追加）
    // targetRecord.changes が未定義なら初期化
    if (!targetRecord.changes) targetRecord.changes = [];
    
    const existingChangeIndex = targetRecord.changes.findIndex(ch => ch.date === date && ch.period === period);

    if (existingChangeIndex !== -1) {
        targetRecord.changes[existingChangeIndex] = newChange;
    } else {
        targetRecord.changes.push(newChange);
    }

    // 7. 画面更新
    // renderTable を呼ぶことで changes の内容がセルに反映される
    renderTable();
    
    // サイドバーのバッジ更新なども必要なら
    renderSidebarList('select'); // 現在のモードに合わせて引数は調整してください

    // モーダルを閉じる
    modal.classList.add('hidden');
    document.body.classList.remove('modal-open');
});

// 変更を保存ボタン (サーバー送信などを想定、ここではアラートのみ)
document.getElementById('saveChangesBtn').addEventListener('click', () => {
    if(!currentRecord) return;
    alert('変更内容を保存しました。');
    // ここでJSONなどをサーバーに送る
});
