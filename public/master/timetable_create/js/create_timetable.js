// public/master/timetable_create/js/main.js

let savedTimetables = [];
let isCreatingMode = false;
let isViewOnly = false;
let currentRecord = null;
let tempCreatingData = null;
let originalRecordData = null; // 編集前のオリジナルデータ

// 時間割グリッドの初期描画
/**
 * renderTimeTableGrid
 * 概要：時間割グリッドを描画する関数
 * 仕様：5限までの時間割グリッドを生成し、各セルにクリックイベントを設定する
 * 引数：なし
 * 戻り値：なし
 * 使用方法：DOMContentLoadedイベント内で呼び出す
 * @returns 
 */
function renderTimeTableGrid() {
    const tbody = document.getElementById('timetable-body');
    if (!tbody) return;

    let html = '';
    for(let i=1; i<=5; i++) {
        const times = [{s:'9:10',e:'10:40'}, {s:'10:50',e:'12:20'}, {s:'13:10',e:'14:40'}, {s:'14:50',e:'16:20'}, {s:'16:30',e:'17:50'}];
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
    
    // セルへのイベントリスナー再設定
    setupCellClickEvents();
}

// デモデータの初期化
function initializeDemoData() {
    const today = new Date();
    // 日付操作の簡略化のため文字列処理等は元のまま維持
    const todayStr = today.toISOString().split('T')[0];
    
    const lastMonth = new Date(today);
    lastMonth.setMonth(lastMonth.getMonth() - 1);
    const lastMonthStr = lastMonth.toISOString().split('T')[0];
    
    const nextMonth = new Date(today);
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    const nextMonthStr = nextMonth.toISOString().split('T')[0];
    
    const twoMonthsLater = new Date(today);
    twoMonthsLater.setMonth(twoMonthsLater.getMonth() + 2);
    const twoMonthsLaterStr = twoMonthsLater.toISOString().split('T')[0];

    savedTimetables = [
        {
            id: 1001,
            course: "システムデザインコース",
            startDate: lastMonthStr,
            endDate: twoMonthsLaterStr,
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
            id: 1003,
            course: "１年１組",
            startDate: nextMonthStr,
            endDate: twoMonthsLaterStr,
            data: [
                {day: "月", period: "1", className: "データベース基礎", teacherName: "高橋 誠", roomName: "201教室"},
                {day: "火", period: "1", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "301演習室"},
                {day: "水", period: "1", className: "HR", teacherName: "田中 優子", roomName: "201教室"}
            ]
        }
    ];
}

// 優先度順にコースをチェック
function selectInitialTimetable() {
    const priorityCourses = [
        "システムデザインコース", "Webクリエイタコース", "マルチメディアOAコース",
        "応用情報コース", "基本情報コース", "ITパスポートコース", "１年１組", "１年２組"
    ];
    
    for (const courseName of priorityCourses) {
        const record = savedTimetables.find(r => r.course === courseName);
        if (record) {
            document.querySelector('#courseDropdownToggle .current-value').textContent = courseName;
            renderSavedList('select');
            setTimeout(() => {
                const targetItem = document.querySelector(`.saved-item[data-id="${record.id}"]`);
                if (targetItem) targetItem.click();
            }, 100);
            return;
        }
    }
}

// DOM要素の取得（DOMContentLoaded後に実行される関数内で使用またはグローバルで取得）
// ここではグローバル変数は定義のみ行い、DOMContentLoaded内で代入または取得時に都度参照する形が安全ですが、
// 既存コードの構造を維持するため、要素取得ロジックはDOMContentLoaded内に集約するか、トップレベルで取得します。
// 今回は「scriptタグをbodyの最後に置く」前提で、トップレベル実行させます。

const mainCreateNewBtn = document.getElementById('mainCreateNewBtn');
const defaultNewBtnArea = document.getElementById('defaultNewBtnArea');
const creatingItemArea = document.getElementById('creatingItemArea');
const creatingCourseName = document.getElementById('creatingCourseName');
const creatingPeriod = document.getElementById('creatingPeriod');
const mainStartDate = document.getElementById('mainStartDate');
const mainEndDate = document.getElementById('mainEndDate');
const resetViewBtn = document.getElementById('resetViewBtn');
const createModal = document.getElementById('createModal');
const createPeriodDisplay = document.getElementById('createPeriodDisplay');
const createGradeSelect = document.getElementById('createGradeSelect');
const createCourseSelect = document.getElementById('createCourseSelect');
const checkCsv = document.getElementById('checkCsv');
const csvInputArea = document.getElementById('csvInputArea');
const createSubmitBtn = document.getElementById('createSubmitBtn');
const createCancelBtn = document.getElementById('createCancelBtn');
const footerArea = document.getElementById('footerArea');
const completeButton = document.getElementById('completeButton');
const cancelCreationBtn = document.getElementById('cancelCreationBtn');

// イベントリスナーの設定などはDOM要素が存在する前提で実行されます
document.getElementById('creatingItemCard').addEventListener('click', () => {
    if (!isCreatingMode) return;
    isViewOnly = false;
    currentRecord = null;
    originalRecordData = null;
    
    if (tempCreatingData) {
        document.querySelectorAll('.timetable-cell').forEach(cell => {
            cell.innerHTML = '';
            cell.classList.remove('is-filled');
        });
        
        tempCreatingData.gridData.forEach(item => {
            const targetCell = document.querySelector(`.timetable-cell[data-day="${item.day}"][data-period="${item.period}"]`);
            if (targetCell) {
                renderCellContent(targetCell, item.className, item.teacherName, item.roomName);
            }
        });
        
        document.getElementById('mainCourseDisplay').innerHTML = tempCreatingData.courseName;
        mainStartDate.value = tempCreatingData.startDate;
        mainEndDate.value = tempCreatingData.endDate;
    }
    
    resetViewBtn.classList.add('hidden');
    footerArea.style.display = 'flex';
    completeButton.textContent = '完了';
    cancelCreationBtn.textContent = 'キャンセル';
    mainStartDate.disabled = true;
    mainEndDate.disabled = true;
    
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
});

const courseData = {
    "1": ["１年１組", "１年２組", "基本情報コース", "応用情報コース"],
    "2": ["システムデザインコース", "Webクリエイタコース", "マルチメディアOAコース"],
    "all": ["１年１組", "１年２組", "基本情報コース", "応用情報コース", "システムデザインコース", "Webクリエイタコース", "マルチメディアOAコース"]
};

// 適用期間監視
mainStartDate.addEventListener('input', checkDateChanges);
mainEndDate.addEventListener('input', checkDateChanges);

function checkDateChanges() {
    if (!isCreatingMode && currentRecord && originalRecordData) {
        if (mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate) {
            setCancelButtonState('change');
        } else {
            if (isRecordActive(currentRecord)) {
                setCancelButtonState('active');
            } else {
                setCancelButtonState('delete');
            }
        }
    }
}

function setCancelButtonState(state) {
    if(state === 'change') {
        cancelCreationBtn.textContent = '変更を破棄';
        cancelCreationBtn.disabled = false;
        cancelCreationBtn.style.opacity = '1';
        cancelCreationBtn.style.cursor = 'pointer';
    } else if(state === 'active') {
        cancelCreationBtn.textContent = '削除不可（適用中）';
        cancelCreationBtn.disabled = true;
        cancelCreationBtn.style.opacity = '0.5';
        cancelCreationBtn.style.cursor = 'not-allowed';
    } else if(state === 'delete') {
        cancelCreationBtn.textContent = '削除';
        cancelCreationBtn.disabled = false;
        cancelCreationBtn.style.opacity = '1';
        cancelCreationBtn.style.cursor = 'pointer';
    }
}

resetViewBtn.addEventListener('click', () => {
    if (isCreatingMode) {
        document.getElementById('creatingItemCard').click();
        return;
    }
});

mainCreateNewBtn.addEventListener('click', () => {
    if (currentRecord && originalRecordData) {
        if (!confirm('編集中の内容は保存されていません。\n新規作成を開始しますか？')) return;
    }
    
    const sVal = mainStartDate.value;
    const eVal = mainEndDate.value;
    
    if (currentRecord) {
        mainStartDate.value = '';
        mainEndDate.value = '';
    }
    
    currentRecord = null;
    originalRecordData = null;
    isViewOnly = false;
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
    footerArea.style.display = 'none';
    
    mainStartDate.disabled = false;
    mainEndDate.disabled = false;
    
    document.querySelectorAll('.timetable-cell').forEach(cell => {
        cell.innerHTML = '';
        cell.classList.remove('is-filled');
    });
    document.getElementById('mainCourseDisplay').innerHTML = "（未選択）";
    
    if(sVal && eVal) {
        createPeriodDisplay.textContent = `適用期間: ${sVal} ～ ${eVal}`;
    } else {
        createPeriodDisplay.textContent = "適用期間: 未設定（メイン画面で入力してください）";
    }
    
    createModal.classList.remove('hidden');
    document.body.classList.add('modal-open');
    createGradeSelect.value = "";
    createCourseSelect.innerHTML = '<option value="">先に学年を選択してください</option>';
    createCourseSelect.disabled = true;
    checkCsv.checked = false;
    csvInputArea.classList.remove('active');
    checkCreateValidation();
});

createCancelBtn.addEventListener('click', () => {
    createModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    if (!isCreatingMode && !currentRecord) {
        mainStartDate.disabled = false;
        mainEndDate.disabled = false;
    }
});

createGradeSelect.addEventListener('change', function() {
    const grade = this.value;
    createCourseSelect.innerHTML = '<option value="">コースを選択してください</option>';
    if (grade && courseData[grade]) {
        courseData[grade].forEach(c => {
            const op = document.createElement('option');
            op.value = c; op.textContent = c; createCourseSelect.appendChild(op);
        });
        createCourseSelect.disabled = false;
    } else {
        createCourseSelect.innerHTML = '<option value="">先に学年を選択してください</option>';
        createCourseSelect.disabled = true;
    }
    checkCreateValidation();
});

function checkCreateValidation() {
    const grade = createGradeSelect.value;
    const course = createCourseSelect.value;
    const isCsv = checkCsv.checked;
    const hasFile = document.getElementById('csvFile').files.length > 0;

    let isValid = grade !== "" && course !== "";
    if (isCsv && !hasFile) isValid = false;

    createSubmitBtn.disabled = !isValid;
}

createCourseSelect.addEventListener('change', checkCreateValidation);
checkCsv.addEventListener('change', () => {
    if (checkCsv.checked) csvInputArea.classList.add('active');
    else csvInputArea.classList.remove('active');
    checkCreateValidation();
});
document.getElementById('csvFile').addEventListener('change', checkCreateValidation);

function toggleCreatingMode(isCreating, courseName = '', sDate = '', eDate = '') {
    isCreatingMode = isCreating;
    if (isCreating) {
        isViewOnly = false; 
        resetViewBtn.classList.add('hidden'); 

        defaultNewBtnArea.classList.add('hidden');
        creatingItemArea.classList.remove('hidden');
        
        creatingCourseName.textContent = courseName;
        creatingPeriod.textContent = (sDate && eDate) ? `${getFormattedDate(sDate)}〜${getFormattedDate(eDate)}` : "(期間未設定)";
        
        mainStartDate.disabled = true;
        mainEndDate.disabled = true;
        
        footerArea.style.display = 'flex';
        completeButton.textContent = '完了';
        cancelCreationBtn.textContent = 'キャンセル';
    } else {
        defaultNewBtnArea.classList.remove('hidden');
        creatingItemArea.classList.add('hidden');
        
        mainStartDate.disabled = false;
        mainEndDate.disabled = false;
        mainStartDate.value = '';
        mainEndDate.value = '';
        
        document.querySelectorAll('.timetable-cell').forEach(cell => {
            cell.innerHTML = '';
            cell.classList.remove('is-filled');
        });
        document.getElementById('mainCourseDisplay').innerHTML = "（未選択）";
        
        tempCreatingData = null;
        currentRecord = null;
        originalRecordData = null;
        footerArea.style.display = 'none';
    }
}

createSubmitBtn.addEventListener('click', () => {
    const selectedCourse = createCourseSelect.value;
    const sDate = mainStartDate.value;
    const eDate = mainEndDate.value;
    if (!sDate || !eDate) {
        alert("適用期間が入力されていません。\nメイン画面で期間を入力してから作成を開始してください。");
        createModal.classList.add('hidden');
        document.body.classList.remove('modal-open');
        setTimeout(() => mainStartDate.focus(), 100);
        return;
    }
    
    const hasOverlap = savedTimetables.some(record => {
        if (record.course !== selectedCourse) return false;
        const recEnd = record.endDate || record.startDate;
        return (sDate <= recEnd) && (eDate >= record.startDate);
    });

    if (hasOverlap) {
        alert('指定された適用期間は、同じコースの既存の時間割と重複しています。\n別の期間を指定するか、既存の時間割を削除してください。');
        return;
    }
    
    currentRecord = null;
    originalRecordData = null;
    isViewOnly = false;
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
    
    toggleCreatingMode(true, selectedCourse, sDate, eDate);
    document.getElementById('mainCourseDisplay').innerHTML = selectedCourse;
    
    const selectRadio = document.querySelector('input[value="select"]');
    if (!selectRadio.checked) selectRadio.checked = true;
    renderSavedList('select');

    document.querySelectorAll('.timetable-cell').forEach(cell => {
        cell.innerHTML = '';
        cell.classList.remove('is-filled');
    });

    createModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    setTimeout(() => alert(`「${selectedCourse}」の作成を開始します。`), 10);
});

// グローバルスコープでアクセスできるように設定
window.changeDisplayMode = function(mode) {
    const toggleBtn = document.getElementById('courseDropdownToggle');
    if (mode === 'select') {
        toggleBtn.classList.remove('disabled');
    } else {
        toggleBtn.classList.add('disabled');
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
    
    let badgeHtml = '';
    if(isRecordActive(record)) {
        badgeHtml = '<span class="active-badge">適用中</span>';
    }
    displayEl.innerHTML = record.course + badgeHtml;
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
        filteredRecords = filteredRecords.filter(item => {
            if (!item.startDate) return false;
            return item.startDate > todayStr;
        });
    }

    if (filteredRecords.length > 0) {
        container.classList.remove('hidden');
        divider.classList.remove('hidden');
    } else {
        // コンテナの表示・非表示制御が必要であればここに追加
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

setupDropdown('courseDropdownToggle', 'courseDropdownMenu', () => {
    const mode = document.querySelector('input[name="displayMode"]:checked').value;
    renderSavedList(mode);
});

document.addEventListener('click', (e) => {
    if (e.target.closest('.modal-content') || e.target.closest('.create-modal-content')) return;
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

// 編集モーダル関連
const editModal = document.getElementById('classModal');
const modalTitle = document.getElementById('modalTitle');
const inputClassName = document.getElementById('inputClassName');
const inputTeacherName = document.getElementById('inputTeacherName');
const inputRoomName = document.getElementById('inputRoomName');
const btnCancel = document.getElementById('btnCancel');
const btnSave = document.getElementById('btnSave');
let currentCell = null;

// セルクリックイベントの設定関数（render後に呼ぶ）
function setupCellClickEvents() {
    document.querySelectorAll('.timetable-cell').forEach(cell => {
        cell.addEventListener('click', function() {
            if (isViewOnly) {
                alert('編集するには「作成中」エリアをクリックして作成中の時間割に戻ってください。');
                return;
            }
            if (!isCreatingMode && !currentRecord) {
                alert('リストから編集する時間割を選択するか、新規作成を行ってください。');
                return;
            }

            currentCell = this;
            const day = this.dataset.day;
            const period = this.dataset.period;
            modalTitle.textContent = `${day}曜日 ${period}限`;
            
            const setVal = (sel, val) => {
                let found = false;
                for(let i=0; i<sel.options.length; i++) { 
                    if(sel.options[i].value === val) { 
                        sel.selectedIndex = i; 
                        found = true; 
                        break; 
                    } 
                }
                if(!found) sel.value = "";
            };

            setVal(inputClassName, this.querySelector('.class-name')?.textContent || '');
            setVal(inputTeacherName, this.querySelector('.teacher-name span')?.textContent || '');
            setVal(inputRoomName, this.querySelector('.room-name span')?.textContent || '');

            editModal.classList.remove('hidden');
            document.body.classList.add('modal-open');
            setTimeout(() => inputClassName.focus(), 100);
        });
    });
}

btnCancel.addEventListener('click', () => {
    editModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    currentCell = null;
});

// セル内容描画ヘルパー
function renderCellContent(cell, c, t, r) {
    if (c || t || r) {
        cell.innerHTML = `
            <div class="class-content">
                <div class="class-name">${c}</div>
                <div class="class-detail">
                    ${t ? `<div class="teacher-name"><i class="fa-solid fa-user icon"></i><span>${t}</span></div>` : ''}
                    ${r ? `<div class="room-name"><i class="fa-solid fa-location-dot icon"></i><span>${r}</span></div>` : ''}
                </div>
            </div>`;
        cell.classList.add('is-filled');
    } else {
        cell.innerHTML = '';
        cell.classList.remove('is-filled');
    }
}

btnSave.addEventListener('click', function() {
    if (!currentCell) return;
    renderCellContent(currentCell, inputClassName.value, inputTeacherName.value, inputRoomName.value);
    
    // 変更検出とボタン制御
    if (!isCreatingMode && currentRecord && originalRecordData) {
        const hasGridChanges = JSON.stringify(getCurrentGridData()) !== JSON.stringify(originalRecordData.data);
        const hasDateChanges = mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate;
        if (hasGridChanges || hasDateChanges) {
            setCancelButtonState('change');
        } else {
            if (isRecordActive(currentRecord)) {
                setCancelButtonState('active');
            } else {
                setCancelButtonState('delete');
            }
        }
    }
    
    editModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    currentCell = null;
});

function getCurrentGridData() {
    const gridData = [];
    document.querySelectorAll('.timetable-cell.is-filled').forEach(cell => {
        gridData.push({
            day: cell.dataset.day, 
            period: cell.dataset.period,
            className: cell.querySelector('.class-name')?.textContent || '',
            teacherName: cell.querySelector('.teacher-name span')?.textContent || '',
            roomName: cell.querySelector('.room-name span')?.textContent || ''
        });
    });
    return gridData;
}

completeButton.addEventListener('click', () => {
    if (!mainStartDate.value) { 
        alert('適用期間を入力してください。'); 
        return; 
    }
    
    const currentGridData = getCurrentGridData();

    if (isCreatingMode) {
        const courseText = document.getElementById('creatingCourseName').textContent;
        const newRecord = {
            id: Date.now(),
            course: courseText,
            startDate: mainStartDate.value,
            endDate: mainEndDate.value,
            data: currentGridData
        };
        savedTimetables.push(newRecord);
        
        toggleCreatingMode(false);
        
        const mode = document.querySelector('input[name="displayMode"]:checked').value;
        renderSavedList(mode);
        
        handleSavedItemClick({
            preventDefault: () => {},
            currentTarget: { getAttribute: () => newRecord.id }
        });

        alert('保存しました。');
    } else if (currentRecord) {
        const sDate = mainStartDate.value;
        const eDate = mainEndDate.value;
        const hasOverlap = savedTimetables.some(record => {
            if (record.id === currentRecord.id) return false;
            if (record.course !== currentRecord.course) return false;
            const recEnd = record.endDate || record.startDate;
            return (sDate <= recEnd) && (eDate >= record.startDate);
        });

        if (hasOverlap) {
            alert('指定された適用期間は、同じコースの既存の時間割と重複しています。\n別の期間を指定してください。');
            return;
        }
        
        currentRecord.data = currentGridData;
        currentRecord.startDate = mainStartDate.value;
        currentRecord.endDate = mainEndDate.value;
        originalRecordData = null;
        
        completeButton.textContent = '保存';
        
        if (isRecordActive(currentRecord)) {
            setCancelButtonState('active');
        } else {
            setCancelButtonState('delete');
        }
        
        const mode = document.querySelector('input[name="displayMode"]:checked').value;
        renderSavedList(mode);
        
        const targetItem = document.querySelector(`.saved-item[data-id="${currentRecord.id}"]`);
        if(targetItem) targetItem.classList.add('active');
        
        alert('変更を保存しました。');
    }
});

cancelCreationBtn.addEventListener('click', () => {
    if (cancelCreationBtn.disabled) {
        alert('現在適用期間中の時間割は削除できません。');
        return;
    }
    
    if(isCreatingMode) {
        if(!confirm('作成中の内容を破棄してキャンセルしますか？')) return;

        document.querySelectorAll('.timetable-cell').forEach(cell => { 
            cell.innerHTML = ''; 
            cell.classList.remove('is-filled'); 
        });
        document.getElementById('mainCourseDisplay').innerHTML = "（未選択）";
        document.querySelector('#courseDropdownToggle .current-value').textContent = "システムデザインコース";
        document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
        
        toggleCreatingMode(false);
        setTimeout(() => alert('キャンセルしました。'), 10);
        return;
    }
    
    if (currentRecord) {
        const hasChanges = originalRecordData !== null;
        if (hasChanges) {
            const hasDateChanges = mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate;
            const hasGridChanges = JSON.stringify(getCurrentGridData()) !== JSON.stringify(originalRecordData.data);
            
            if (hasDateChanges || hasGridChanges) {
                if(!confirm('変更を破棄しますか？')) return;
                
                currentRecord.data = JSON.parse(JSON.stringify(originalRecordData.data));
                currentRecord.startDate = originalRecordData.startDate;
                currentRecord.endDate = originalRecordData.endDate;
                originalRecordData = null;
                
                completeButton.textContent = '保存';
                if (isRecordActive(currentRecord)) {
                    setCancelButtonState('active');
                } else {
                    setCancelButtonState('delete');
                }
                
                mainStartDate.value = currentRecord.startDate;
                mainEndDate.value = currentRecord.endDate;
                
                const mode = document.querySelector('input[name="displayMode"]:checked').value;
                renderSavedList(mode);
                const targetItem = document.querySelector(`.saved-item[data-id="${currentRecord.id}"]`);
                if(targetItem) targetItem.classList.add('active');
                
                document.querySelectorAll('.timetable-cell').forEach(cell => { 
                    cell.innerHTML = ''; 
                    cell.classList.remove('is-filled'); 
                });
                currentRecord.data.forEach(item => {
                    const targetCell = document.querySelector(`.timetable-cell[data-day="${item.day}"][data-period="${item.period}"]`);
                    if (targetCell) renderCellContent(targetCell, item.className, item.teacherName, item.roomName);
                });
                
                alert('変更を破棄しました。');
                return;
            }
        }
        
        if (isRecordActive(currentRecord)) {
            alert('現在適用期間中の時間割は削除できません。');
            return;
        }
        
        if(!confirm('この時間割を削除しますか？')) return;

        const index = savedTimetables.findIndex(item => item.id === currentRecord.id);
        if (index !== -1) savedTimetables.splice(index, 1);
        
        const activeItem = document.querySelector('.saved-item.active');
        if (activeItem) activeItem.remove();
        
        const mode = document.querySelector('input[name="displayMode"]:checked').value;
        renderSavedList(mode);
        
        currentRecord = null;
        originalRecordData = null;
        footerArea.style.display = 'none';
        mainStartDate.disabled = false;
        mainEndDate.disabled = false;
        mainStartDate.value = '';
        mainEndDate.value = '';
        document.querySelectorAll('.timetable-cell').forEach(cell => { 
            cell.innerHTML = ''; 
            cell.classList.remove('is-filled'); 
        });
        document.getElementById('mainCourseDisplay').innerHTML = "（未選択）";
        
        document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));

        setTimeout(() => alert('削除しました。'), 10);
    }
});

function handleSavedItemClick(e) {
    if(e.preventDefault) e.preventDefault();
    
    let id;
    if(e.currentTarget && e.currentTarget.getAttribute) {
        id = parseInt(e.currentTarget.getAttribute('data-id'));
    } else {
        id = parseInt(e.currentTarget.getAttribute('data-id'));
    }

    if (isCreatingMode && !isViewOnly) {
        tempCreatingData = {
            courseName: document.getElementById('creatingCourseName').textContent,
            startDate: mainStartDate.value,
            endDate: mainEndDate.value,
            gridData: getCurrentGridData()
        };
        isViewOnly = true;
    } else {
        isViewOnly = false;
    }

    const selectRadio = document.querySelector('input[value="select"]');
    if(!selectRadio.checked) selectRadio.checked = true;
    
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
    const targetItem = document.querySelector(`.saved-item[data-id="${id}"]`);
    if(targetItem) targetItem.classList.add('active');

    const record = savedTimetables.find(item => item.id === id);
    if (record) {
        currentRecord = record;
        
        if (!isViewOnly) {
            originalRecordData = {
                data: JSON.parse(JSON.stringify(record.data)),
                startDate: record.startDate,
                endDate: record.endDate
            };
            completeButton.textContent = '保存';
            
            if (isRecordActive(record)) {
                setCancelButtonState('active');
            } else {
                setCancelButtonState('delete');
            }
        }
        
        if (isCreatingMode && isViewOnly) {
            resetViewBtn.textContent = '作成中に戻る';
            resetViewBtn.classList.remove('hidden');
            footerArea.style.display = 'none';
            mainStartDate.disabled = true;
            mainEndDate.disabled = true;
        } else {
            resetViewBtn.classList.add('hidden');
            footerArea.style.display = 'flex';
            completeButton.textContent = '保存';
            cancelCreationBtn.textContent = '削除';
            mainStartDate.disabled = false;
            mainEndDate.disabled = false;
        }

        updateHeaderDisplay(record);
        
        mainStartDate.value = record.startDate;
        mainEndDate.value = record.endDate;
        
        document.querySelectorAll('.timetable-cell').forEach(cell => { 
            cell.innerHTML = ''; 
            cell.classList.remove('is-filled'); 
        });
        record.data.forEach(item => {
            const targetCell = document.querySelector(`.timetable-cell[data-day="${item.day}"][data-period="${item.period}"]`);
            if (targetCell) renderCellContent(targetCell, item.className, item.teacherName, item.roomName);
        });
    }
}

// ページ読み込み時の初期化
window.addEventListener('DOMContentLoaded', () => {
    // 1. グリッドの描画 (document.writeの代わり)
    renderTimeTableGrid();
    
    // 2. データの初期化
    initializeDemoData();
    selectInitialTimetable();
});