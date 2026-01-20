// create_timetable.js

// 1. データ格納用の変数
let savedTimetables = [];

// 2. PHPからデータが渡ってきているか確認
if (typeof dbTimetableData !== 'undefined' && Array.isArray(dbTimetableData)) {
    // PHP (Repository) 側ですでに整形済みなので、そのまま代入するだけでOKです！
    savedTimetables = dbTimetableData;
    console.log("DBから読み込んだデータ(PHP整形済み):", savedTimetables);
} else {
    // データが無い場合は空配列で初期化
    console.warn("DBからのデータ読み込みに失敗しました、またはデータがありません。");
    savedTimetables = [];
}

let isCreatingMode = false;
let isViewOnly = false;
let currentRecord = null;
let tempCreatingData = null;
let originalRecordData = null; // 編集前のオリジナルデータ
let previousState = null; // 新規作成前の状態を保存

/*
 * 概要: 優先度順、またはデータが存在する順に初期選択を行う。
 * 使用方法: データ読み込み後に呼び出すと、適切なコースを自動で選択して表示します。
 */
function selectInitialTimetable() {
    // 1. データがない場合は何もしない
    if (!savedTimetables || savedTimetables.length === 0) {
        return;
    }

    // 2. 初期選択する時間割レコードを決める
    // (A) ステータスが「1:反映中」のものを優先
    let targetRecord = savedTimetables.find(r => r.statusType === 1);

    // (B) なければ、データの先頭にあるものを使う
    if (!targetRecord) {
        targetRecord = savedTimetables[0];
    }

    const targetCourseId = targetRecord.courseId;
    const targetTimetableId = targetRecord.id; // 表示したい具体的な時間割のID

    // 3. ドロップダウン（コース選択）をクリックする
    const dropdownItem = document.querySelector(`li[data-value="${targetCourseId}"]`);

    if (dropdownItem) {
        console.log(`初期コース(ID:${targetCourseId})を自動選択します`);
        dropdownItem.click();

        // コースを選択した直後は、サイドバーの描画処理が実行されるため、
        // その描画が完了するのを待ってから、サイドバーの項目をクリックする
        setTimeout(() => {
            // サイドバーに生成されたリスト項目を探す
            // ※ renderSavedListの実装で class="saved-item" data-id="X" となっている前提です
            const sidebarItem = document.querySelector(`.saved-item[data-id="${targetTimetableId}"]`);
            
            if (sidebarItem) {
                console.log(`初期時間割データ(ID:${targetTimetableId})を自動表示します`);
                sidebarItem.click();
            } else {
                console.warn(`サイドバーの項目(ID:${targetTimetableId})が見つかりませんでした。renderSavedListの描画を確認してください。`);
            }
        }, 100); // 100ms待機（描画待ち）
    } else {
        // ドロップダウンが見つからない場合の予備処理
        const toggleText = document.querySelector('#courseDropdownToggle .current-value');
        if(toggleText) {
            toggleText.textContent = targetRecord.course;
        }
    }
}

const mainCreateNewBtn = document.getElementById('mainCreateNewBtn');
const defaultNewBtnArea = document.getElementById('defaultNewBtnArea');
const creatingItemArea = document.getElementById('creatingItemArea');
const creatingCourseName = document.getElementById('creatingCourseName');
const mainStartDate = document.getElementById('mainStartDate');
const mainEndDate = document.getElementById('mainEndDate');
const resetViewBtn = document.getElementById('resetViewBtn');
const createModal = document.getElementById('createModal');
const createGradeSelect = document.getElementById('createGradeSelect');
const createCourseSelect = document.getElementById('createCourseSelect');
const checkCsv = document.getElementById('checkCsv');
const csvInputArea = document.getElementById('csvInputArea');
const createSubmitBtn = document.getElementById('createSubmitBtn');
const createCancelBtn = document.getElementById('createCancelBtn');
const footerArea = document.getElementById('footerArea');
const completeButton = document.getElementById('completeButton');
const cancelCreationBtn = document.getElementById('cancelCreationBtn');

document.getElementById('creatingItemCard').addEventListener('click', () => {
    if (!isCreatingMode) return;
    
    // 作成中に戻る = 閲覧モード解除
    isViewOnly = false;
    currentRecord = null;
    originalRecordData = null;
    
    if (tempCreatingData) {
        document.querySelectorAll('.timetable-cell').forEach(cell => {
            cell.innerHTML = '';
            cell.classList.remove('is-filled');
            cell.classList.remove('is-edited');
        });
        
        tempCreatingData.gridData.forEach(item => {
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
        
        document.getElementById('mainCourseDisplay').innerHTML = tempCreatingData.courseName;
        mainStartDate.value = tempCreatingData.startDate;
        mainEndDate.value = tempCreatingData.endDate;
        
        // 「現在作成中」バッチを追加
        document.getElementById('mainCourseDisplay').innerHTML = tempCreatingData.courseName + '<span class="active-badge creating">現在作成中</span>';
    }
    
    resetViewBtn.classList.add('hidden');
    footerArea.classList.add('show');
    completeButton.textContent = '完了';
    cancelCreationBtn.textContent = 'キャンセル';
    cancelCreationBtn.disabled = false;
    cancelCreationBtn.style.opacity = '1';
    cancelCreationBtn.style.cursor = 'pointer';
    mainStartDate.disabled = false;
    mainEndDate.disabled = false;
    
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
});

const courseData = {
    "1": ["１年１組", "１年２組", "基本情報コース", "応用情報コース"],
    "2": ["システムデザインコース", "Webクリエイタコース", "マルチメディアOAコース"],
    "all": ["１年１組", "１年２組", "基本情報コース", "応用情報コース", "システムデザインコース", "Webクリエイタコース", "マルチメディアOAコース"]
};

// 適用期間の変更を監視
mainStartDate.addEventListener('input', () => {
    // 適用期間の逆転チェック
    if (mainStartDate.value && mainEndDate.value && mainStartDate.value > mainEndDate.value) {
        mainStartDate.style.borderColor = '#EF4444';
        mainEndDate.style.borderColor = '#EF4444';
    } else {
        mainStartDate.style.borderColor = '';
        mainEndDate.style.borderColor = '';
    }
    
    if (!isCreatingMode && currentRecord && originalRecordData) {
        if (mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate) {
            // 適用中の時間割は削除不可の表示で統一する
            if (isRecordActive(currentRecord)) {
                cancelCreationBtn.textContent = '削除不可（適用中）';
                cancelCreationBtn.disabled = true;
                cancelCreationBtn.style.opacity = '0.5';
                cancelCreationBtn.style.cursor = 'not-allowed';
            } else {
                cancelCreationBtn.textContent = '変更を破棄';
                cancelCreationBtn.disabled = false;
                cancelCreationBtn.style.opacity = '1';
                cancelCreationBtn.style.cursor = 'pointer';
            }
        } else {
            // 元の値に戻った場合
            if (isRecordActive(currentRecord)) {
                cancelCreationBtn.textContent = '削除不可（適用中）';
                cancelCreationBtn.disabled = true;
                cancelCreationBtn.style.opacity = '0.5';
                cancelCreationBtn.style.cursor = 'not-allowed';
            } else {
                cancelCreationBtn.textContent = '削除';
                cancelCreationBtn.disabled = false;
                cancelCreationBtn.style.opacity = '1';
                cancelCreationBtn.style.cursor = 'pointer';
            }
        }
    }
});

mainEndDate.addEventListener('input', () => {
    // 適用期間の逆転チェック
    if (mainStartDate.value && mainEndDate.value && mainStartDate.value > mainEndDate.value) {
        mainStartDate.style.borderColor = '#EF4444';
        mainEndDate.style.borderColor = '#EF4444';
    } else {
        mainStartDate.style.borderColor = '';
        mainEndDate.style.borderColor = '';
    }
    
    if (!isCreatingMode && currentRecord && originalRecordData) {
        if (mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate) {
            // 適用中の時間割は削除不可の表示で統一する
            if (isRecordActive(currentRecord)) {
                cancelCreationBtn.textContent = '削除不可（適用中）';
                cancelCreationBtn.disabled = true;
                cancelCreationBtn.style.opacity = '0.5';
                cancelCreationBtn.style.cursor = 'not-allowed';
            } else {
                cancelCreationBtn.textContent = '変更を破棄';
                cancelCreationBtn.disabled = false;
                cancelCreationBtn.style.opacity = '1';
                cancelCreationBtn.style.cursor = 'pointer';
            }
        } else {
            // 元の値に戻った場合
            if (isRecordActive(currentRecord)) {
                cancelCreationBtn.textContent = '削除不可（適用中）';
                cancelCreationBtn.disabled = true;
                cancelCreationBtn.style.opacity = '0.5';
                cancelCreationBtn.style.cursor = 'not-allowed';
            } else {
                cancelCreationBtn.textContent = '削除';
                cancelCreationBtn.disabled = false;
                cancelCreationBtn.style.opacity = '1';
                cancelCreationBtn.style.cursor = 'pointer';
            }
        }
    }
});

resetViewBtn.addEventListener('click', () => {
    // 作成中モードでのみ使用（作成中に戻る）
    if (isCreatingMode) {
        document.getElementById('creatingItemCard').click();
        return;
    }
});

mainCreateNewBtn.addEventListener('click', () => {
    // 編集中の時間割がある場合は確認
    if (currentRecord && originalRecordData && !isCreatingMode && !isViewOnly) {
        // 実際に編集があるかチェック
        const hasDateChanges = mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate;
        const hasGridChanges = getGridDataForComparison(getCurrentGridData()) !== getGridDataForComparison(originalRecordData.data);
        
        if (hasDateChanges || hasGridChanges) {
            if (!confirm('編集中の内容は保存されていません。\n新規作成を開始しますか？')) {
                return;
            }
        }
    }
    
    // 新規作成前の状態を保存
    previousState = {
        currentRecord: currentRecord,
        originalRecordData: originalRecordData ? JSON.parse(JSON.stringify(originalRecordData)) : null,
        startDate: mainStartDate.value,
        endDate: mainEndDate.value,
        gridData: getCurrentGridData(),
        courseDisplayText: document.getElementById('mainCourseDisplay').innerHTML,
        selectedItemId: document.querySelector('.saved-item.active')?.getAttribute('data-id')
    };
    
    // モーダルに表示する期間を先に取得
    const sVal = mainStartDate.value;
    const eVal = mainEndDate.value;
    
    // 適用期間を常にクリア（新規作成なので）
    mainStartDate.value = '';
    mainEndDate.value = '';
    
    // 既存の選択をクリアして新規入力可能な状態に
    currentRecord = null;
    originalRecordData = null;
    isViewOnly = false;
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
    footerArea.classList.remove('show');
    
    // 適用期間フィールドを有効化
    mainStartDate.disabled = false;
    mainEndDate.disabled = false;
    
    // グリッドをクリア
    document.querySelectorAll('.timetable-cell').forEach(cell => {
        cell.innerHTML = '';
        cell.classList.remove('is-filled');
        cell.classList.remove('is-edited');
    });
    document.getElementById('mainCourseDisplay').innerHTML = "（未選択）";
    
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
    
    // 前回表示していた状態を復元
    if (previousState) {
        currentRecord = previousState.currentRecord;
        originalRecordData = previousState.originalRecordData;
        mainStartDate.value = previousState.startDate;
        mainEndDate.value = previousState.endDate;
        document.getElementById('mainCourseDisplay').innerHTML = previousState.courseDisplayText;
        
        // グリッドを復元
        document.querySelectorAll('.timetable-cell').forEach(cell => {
            cell.innerHTML = '';
            cell.classList.remove('is-filled');
            cell.classList.remove('is-edited');
        });
        previousState.gridData.forEach(item => {
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
        
        // 選択状態を復元
        if (previousState.selectedItemId) {
            document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
            const targetItem = document.querySelector(`.saved-item[data-id="${previousState.selectedItemId}"]`);
            if (targetItem) targetItem.classList.add('active');
        }
        
        previousState = null;
    } else {
        // 作成モーダルをキャンセルした場合、何も選択していない状態に戻す
        if (!isCreatingMode && !currentRecord) {
            mainStartDate.disabled = false;
            mainEndDate.disabled = false;
        }
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

/*
    * 概要: 新規作成フォームの入力検証を行い、作成開始ボタンの有効/無効を切り替える。
    * 使用方法: 学年/コース選択や CSV チェックボックスの変更時に呼び出してください。
    */
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

/*
    * 概要: 作成モードの切替を行う（UI の表示/非表示や入力可否の制御）。
    * 使用方法: 新規作成開始/終了やキャンセル時に呼び出して UI を同期させてください。
    */
function toggleCreatingMode(isCreating, courseName = '', sDate = '', eDate = '') {
    isCreatingMode = isCreating;
    if (isCreating) {
        isViewOnly = false; 
        resetViewBtn.classList.add('hidden'); 

        defaultNewBtnArea.classList.add('hidden');
        creatingItemArea.classList.remove('hidden');
        
        creatingCourseName.textContent = courseName;
        
        // 作成開始時に「現在作成中」バッチを表示
        document.getElementById('mainCourseDisplay').innerHTML = courseName + '<span class="active-badge creating">現在作成中</span>';
        
        // ヘッダーを「時間割り作成」に戻す
        document.querySelector('.app-header h1').textContent = '時間割り作成';
        
        // 作成中は適用期間を編集可能にする
        mainStartDate.disabled = false;
        mainEndDate.disabled = false;
        
        footerArea.classList.add('show');
        completeButton.textContent = '完了';
        cancelCreationBtn.textContent = 'キャンセル';
        cancelCreationBtn.disabled = false;
        cancelCreationBtn.style.opacity = '1';
        cancelCreationBtn.style.cursor = 'pointer';
    } else {
        defaultNewBtnArea.classList.remove('hidden');
        creatingItemArea.classList.add('hidden');
        
        // 作成モード終了時は期間フィールドをクリア
        mainStartDate.disabled = false;
        mainEndDate.disabled = false;
        mainStartDate.value = '';
        mainEndDate.value = '';
        
        // グリッドもクリア
        document.querySelectorAll('.timetable-cell').forEach(cell => {
            cell.innerHTML = '';
            cell.classList.remove('is-filled');
            cell.classList.remove('is-edited');
        });
        document.getElementById('mainCourseDisplay').innerHTML = "（未選択）";
        
        // ヘッダーを「時間割り作成」に戻す
        document.querySelector('.app-header h1').textContent = '時間割り作成';
        
        tempCreatingData = null;
        currentRecord = null;
        originalRecordData = null;
        footerArea.classList.remove('show');
    }
}

createSubmitBtn.addEventListener('click', () => {
    const selectedCourse = createCourseSelect.value;
    
    // メイン画面の適用期間値を取得
    const sDate = mainStartDate.value;
    const eDate = mainEndDate.value;
    
    // 適用期間が入力されている場合のみ重複チェック
    if (sDate && eDate) {
        if (checkCourseOverlap(selectedCourse, sDate, eDate)) {
            alert('指定された適用期間は、同じコースの既存の時間割と重複しています。\n別の期間を指定するか、既存の時間割を削除してください。');
            return;
        }
    } else {
        // 期間未設定の場合、同じコースに既に期間未設定の時間割があるかチェック
        const existingNoPeriod = savedTimetables.find(record => 
            record.course === selectedCourse && (!record.startDate || !record.endDate)
        );
        if (existingNoPeriod) {
            alert('同じコースに期間未設定の時間割が既に存在します。\n先に既存の時間割に適用期間を設定するか、削除してください。');
            return;
        }
    }
    
    // 既存の選択をクリア
    currentRecord = null;
    originalRecordData = null;
    isViewOnly = false;
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
    
    // 作成モード開始
    toggleCreatingMode(true, selectedCourse, sDate, eDate);

    document.getElementById('mainCourseDisplay').innerHTML = selectedCourse;
    
    const selectRadio = document.querySelector('input[value="select"]');
    if (!selectRadio.checked) {
        selectRadio.checked = true;
    }
    renderSavedList('select');

    document.querySelectorAll('.timetable-cell').forEach(cell => {
        cell.innerHTML = '';
        cell.classList.remove('is-filled');
        cell.classList.remove('is-edited');
    });

    createModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    
    setTimeout(() => alert(`「${selectedCourse}」の作成を開始します。\nメイン画面で適用期間を設定し、授業情報を入力してください。`), 10);
});

/*
    * 概要: サイドバーの表示モード（select/current/next）を切り替え、ドロップダウンの有効/無効を制御する。
    * 使用方法: 表示モードラジオボタンの変更時に呼び出してリストを再描画してください。
    */
function changeDisplayMode(mode) {
    const toggleBtn = document.getElementById('courseDropdownToggle');
    if (mode === 'select') {
        toggleBtn.classList.remove('disabled');
    } else {
        toggleBtn.classList.add('disabled');
    }
    renderSavedList(mode);
}

/*
    * 概要: 与えられた時間割レコードが現在適用中（期間内）かを判定する。
    * 使用方法: レコードオブジェクトを渡すと true/false を返します（UI バッジや削除可否判定に使用）。
    */
function isRecordActive(record) {
    // 期間が未設定の場合は適用中ではない
    if (!record.startDate || !record.endDate) {
        return false;
    }
    
    const todayStr = new Date().toISOString().split('T')[0];
    const isStarted = record.startDate <= todayStr;
    const isNotEnded = record.endDate >= todayStr;
    return isStarted && isNotEnded;
}

/*
    * 概要: 2つの適用期間が重複しているかをチェックする。
    * 使用方法: checkDateOverlap(startDate1, endDate1, startDate2, endDate2) の形式で呼び出してください。
    */
function checkDateOverlap(start1, end1, start2, end2) {
    // いずれかの期間が完全に未設定の場合（両方の日付がない）は重複しないと判定
    const has1 = start1 && end1;
    const has2 = start2 && end2;
    
    if (!has1 && !has2) {
        // 両方とも期間なし = 同じ「次回適用」グループ = 重複
        return true;
    }
    
    if (!has1 || !has2) {
        // 片方だけ期間なし = 重複しないと判定（期間未設定のものは「次回」）
        return false;
    }
    
    // 両方とも期間あり = 期間が重複しているかチェック: start1 <= end2 && start2 <= end1
    return start1 <= end2 && start2 <= end1;
}

/*
    * 概要: 指定されたコースと期間で重複する時間割がないかをチェック（除外ID指定可能）。
    * 使用方法: checkCourseOverlap(course, startDate, endDate, excludeId) の形式で呼び出してください。
    */
function checkCourseOverlap(course, startDate, endDate, excludeId = null) {
    return savedTimetables.some(record => {
        if (excludeId && record.id === excludeId) return false; // 自分自身を除外
        if (record.course !== course) return false; // 同じコースのみ対象
        const recEnd = record.endDate || record.startDate;
        return checkDateOverlap(startDate, endDate, record.startDate, recEnd);
    });
}

/*
    * 概要: ヘッダー（コース表示）をレコードの状態に合わせて更新する（適用中/次回反映バッジ追加など）。
    * 使用方法: レコードを選択した後に呼び出して表示を更新してください。
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

/*
    * 概要: yyyy-mm-dd の文字列を MM/DD~ の短縮表示に変換するユーティリティ。
    * 使用方法: 日付文字列を渡すと短縮された表示を返します（リスト項目のラベル用）。
    */
function getFormattedDate(inputVal) {
    if (!inputVal) return '';
    const parts = inputVal.split('-');
    if (parts.length !== 3) return '';
    return `${parts[1]}/${parts[2]}~`;
}

/*
    * 概要: 保存された時間割のリストを指定モードに合わせてフィルタ・ソートして描画する。
    * 使用方法: 表示モードやドロップダウン選択が変わったら呼び出してリストを再描画してください。
    */
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

    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];

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

    // 適用中の時間割りを一番上に表示するようにソート
    filteredRecords.sort((a, b) => {
        const aActive = isRecordActive(a) ? 0 : 1;
        const bActive = isRecordActive(b) ? 0 : 1;
        return aActive - bActive;
    });

    if (filteredRecords.length > 0) {
        container.classList.remove('hidden');
        divider.classList.remove('hidden');
    }

    filteredRecords.forEach(record => {
        const dateLabel = getFormattedDate(record.startDate);
        let statusText = "";
        let statusClass = "text-slate-500";
        
        // 来月の初日と末日を計算
        const nextMonthStart = new Date(today);
        nextMonthStart.setMonth(nextMonthStart.getMonth() + 1);
        nextMonthStart.setDate(1);
        const nextMonthStartStr = nextMonthStart.toISOString().split('T')[0];
        
        const nextMonthEnd = new Date(nextMonthStart);
        nextMonthEnd.setMonth(nextMonthEnd.getMonth() + 1, 0);
        const nextMonthEndStr = nextMonthEnd.toISOString().split('T')[0];
        
        if (isRecordActive(record)) {
            statusText = "適用中：";
            statusClass = "text-emerald-600 font-bold";
        } else if (record.startDate && record.startDate >= nextMonthStartStr && record.startDate <= nextMonthEndStr) {
            // 来月予定 = 次回
            statusText = "次回：";
            statusClass = "text-blue-600 font-bold";
        } else if (!record.startDate || !record.endDate) {
            // 期間未設定 = 次回以降
            statusText = "次回以降：";
            statusClass = "text-indigo-600 font-bold";
        } else if (record.startDate && record.startDate > todayStr) {
            // 来月より後 = 次回以降
            statusText = "次回以降：";
            statusClass = "text-indigo-600 font-bold";
        } else if (record.endDate && record.endDate < todayStr) {
            statusText = "過去：";
            statusClass = "text-gray-400";
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

/*
    * 概要: カスタムドロップダウンの挙動（開閉・選択）をセットアップするユーティリティ。
    * 使用方法: ドロップダウン要素の id を渡し、選択時のコールバックを指定して初期化してください。
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

const editModal = document.getElementById('classModal');
const modalTitle = document.getElementById('modalTitle');
const inputClassName = document.getElementById('inputClassName');
const teacherSelectionArea = document.getElementById('teacherSelectionArea');
const roomSelectionArea = document.getElementById('roomSelectionArea');
const btnCancel = document.getElementById('btnCancel');
const btnSave = document.getElementById('btnSave');
const btnRevert = document.getElementById('btnRevert');
let currentCell = null;

// Helper function to get teacher inputs
function getTeacherInputs() {
    return Array.from(teacherSelectionArea.querySelectorAll('.teacher-input'));
}

// Helper function to get room inputs
function getRoomInputs() {
    return Array.from(roomSelectionArea.querySelectorAll('.room-input'));
}

// Add teacher input row
function addTeacherRow() {
    const currentCount = getTeacherInputs().length;
    if (currentCount >= 5) {
        alert('最大5個まで追加できます。');
        return;
    }
    
    const rowDiv = document.createElement('div');
    rowDiv.className = 'teacher-input-row';
    rowDiv.style.cssText = 'display: flex; gap: 8px; align-items: center;';
    
    const select = document.createElement('select');
    select.className = 'teacher-input modal-select';
    select.style.flex = '1';
    select.innerHTML = '<option value="">(選択してください)</option><option value="佐藤 健一">佐藤 健一</option><option value="鈴木 花子">鈴木 花子</option><option value="高橋 誠">高橋 誠</option><option value="田中 優子">田中 優子</option><option value="渡辺 剛">渡辺 剛</option><option value="伊藤 直人">伊藤 直人</option><option value="山本 さくら">山本 さくら</option>';
    
    const arrowDiv = document.createElement('div');
    arrowDiv.className = 'select-arrow';
    arrowDiv.style.cssText = 'flex-shrink: 0;';
    arrowDiv.innerHTML = '<i class="fa-solid fa-chevron-down text-xs"></i>';
    
    const removeBtn = document.createElement('button');
    removeBtn.className = 'remove-teacher-btn';
    removeBtn.style.cssText = 'padding: 4px 8px; background-color: #f87171; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; flex-shrink: 0;';
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

// Add room input row
function addRoomRow() {
    const currentCount = getRoomInputs().length;
    if (currentCount >= 5) {
        alert('最大5個まで追加できます。');
        return;
    }
    
    const rowDiv = document.createElement('div');
    rowDiv.className = 'room-input-row';
    rowDiv.style.cssText = 'display: flex; gap: 8px; align-items: center;';
    
    const select = document.createElement('select');
    select.className = 'room-input modal-select';
    select.style.flex = '1';
    select.innerHTML = '<option value="">(選択してください)</option><option value="201教室">201教室</option><option value="202教室">202教室</option><option value="301演習室">301演習室</option><option value="302演習室">302演習室</option><option value="4F大講義室">4F大講義室</option><option value="別館Lab A">別館Lab A</option><option value="別館Lab B">別館Lab B</option>';
    
    const arrowDiv = document.createElement('div');
    arrowDiv.className = 'select-arrow';
    arrowDiv.style.cssText = 'flex-shrink: 0;';
    arrowDiv.innerHTML = '<i class="fa-solid fa-chevron-down text-xs"></i>';
    
    const removeBtn = document.createElement('button');
    removeBtn.className = 'remove-room-btn';
    removeBtn.style.cssText = 'padding: 4px 8px; background-color: #f87171; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; flex-shrink: 0;';
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

// Update visibility of remove buttons
function updateTeacherRemoveButtons() {
    const inputs = getTeacherInputs();
    inputs.forEach(input => {
        const row = input.closest('.teacher-input-row');
        const removeBtn = row.querySelector('.remove-teacher-btn');
        removeBtn.style.display = inputs.length > 1 ? 'block' : 'none';
    });
}

function updateRoomRemoveButtons() {
    const inputs = getRoomInputs();
    inputs.forEach(input => {
        const row = input.closest('.room-input-row');
        const removeBtn = row.querySelector('.remove-room-btn');
        removeBtn.style.display = inputs.length > 1 ? 'block' : 'none';
    });
}

// Add button event listeners
document.getElementById('addTeacherBtn').addEventListener('click', (e) => {
    e.preventDefault();
    addTeacherRow();
});
document.getElementById('addRoomBtn').addEventListener('click', (e) => {
    e.preventDefault();
    addRoomRow();
});

// Initialize remove buttons visibility
updateTeacherRemoveButtons();
updateRoomRemoveButtons();

document.querySelectorAll('.timetable-cell').forEach(cell => {
    cell.addEventListener('click', function() {
        // 閲覧専用モードの場合のみ編集不可
        if (isViewOnly) {
            alert('既存の時間割りを編集することはできません。現在作成中の時間割りを作成し終えて編集を行ってください。');
            return;
        }

        // 作成中でもなく、選択もしていない場合は編集不可
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
        
        // 複数の先生を取得
        const teacherElems = this.querySelectorAll('.teacher-name span');
        const teacherInputs = getTeacherInputs();
        
        // 先生フィールドをリセット（最初のフィールドのみ残す）
        while (teacherInputs.length > 1) {
            teacherInputs[teacherInputs.length - 1].closest('.teacher-input-row').remove();
            teacherInputs.pop();
        }
        
        // 先生データをセット
        teacherInputs.forEach((input, idx) => {
            setVal(input, teacherElems[idx]?.textContent || '');
        });
        
        // 複数の教室を取得
        const roomElems = this.querySelectorAll('.room-name span');
        const roomInputs = getRoomInputs();
        
        // 教室フィールドをリセット（最初のフィールドのみ残す）
        while (roomInputs.length > 1) {
            roomInputs[roomInputs.length - 1].closest('.room-input-row').remove();
            roomInputs.pop();
        }
        
        // 教室データをセット
        roomInputs.forEach((input, idx) => {
            setVal(input, roomElems[idx]?.textContent || '');
        });

        // 「変更を戻す」ボタンを表示するかどうかを判定（変更済みで既存時間割の場合のみ表示）
        const isEdited = this.classList.contains('is-edited');
        if (isEdited && !isCreatingMode && currentRecord && originalRecordData) {
            btnRevert.style.display = 'block';
        } else {
            btnRevert.style.display = 'none';
        }

        updateTeacherRemoveButtons();
        updateRoomRemoveButtons();

        editModal.classList.remove('hidden');
        document.body.classList.add('modal-open');
        setTimeout(() => inputClassName.focus(), 100);
    });
});

btnCancel.addEventListener('click', () => {
    editModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    currentCell = null;
});

btnRevert.addEventListener('click', () => {
    if (!currentCell || !currentRecord || !originalRecordData) return;
    
    // 現在のセル位置の元データを検索
    const day = currentCell.dataset.day;
    const period = currentCell.dataset.period;
    const originalItem = originalRecordData.data.find(item => item.day === day && item.period === period);
    
    // 元データがあればそれを復元、なければ空にする
    if (originalItem) {
        currentCell.innerHTML = `
            <div class="class-content">
                <div class="class-name">${originalItem.className}</div>
                <div class="class-detail">
                    ${originalItem.teacherName ? `<div class="teacher-name"><i class="fa-solid fa-user icon"></i><span>${originalItem.teacherName}</span></div>` : ''}
                    ${originalItem.roomName ? `<div class="room-name"><i class="fa-solid fa-location-dot icon"></i><span>${originalItem.roomName}</span></div>` : ''}
                </div>
            </div>`;
        currentCell.classList.add('is-filled');
    } else {
        currentCell.innerHTML = '';
        currentCell.classList.remove('is-filled');
    }
    
    // 編集ハイライトを削除
    currentCell.classList.remove('is-edited');
    
    // モーダルを閉じる
    editModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    currentCell = null;
    
    alert('変更を戻しました。');
});

btnSave.addEventListener('click', function() {
    if (!currentCell) return;
    
    // 編集前の値を取得
    const oldClassName = currentCell.querySelector('.class-name')?.textContent || '';
    const oldTeachers = Array.from(currentCell.querySelectorAll('.teacher-name span')).map(el => el.textContent);
    const oldRooms = Array.from(currentCell.querySelectorAll('.room-name span')).map(el => el.textContent);
    
    const c = inputClassName.value;
    const teachers = getTeacherInputs().map(sel => sel.value).filter(v => v);
    const rooms = getRoomInputs().map(sel => sel.value).filter(v => v);

    // 変更があったかチェック
    const teachersChanged = teachers.join(',') !== oldTeachers.join(',');
    const roomsChanged = rooms.join(',') !== oldRooms.join(',');
    const hasChanged = (c !== oldClassName) || teachersChanged || roomsChanged;

    if (c || teachers.length > 0 || rooms.length > 0) {
        let teacherHtml = teachers.map(t => `<div class="teacher-name"><i class="fa-solid fa-user icon"></i><span>${t}</span></div>`).join('');
        let roomHtml = rooms.map(r => `<div class="room-name"><i class="fa-solid fa-location-dot icon"></i><span>${r}</span></div>`).join('');
        
        currentCell.innerHTML = `
            <div class="class-content">
                <div class="class-name">${c}</div>
                <div class="class-detail">
                    ${teacherHtml}
                    ${roomHtml}
                </div>
            </div>`;
        currentCell.classList.add('is-filled');
        
        // 既存の時間割を編集している場合のみハイライト
        if (!isCreatingMode && currentRecord && originalRecordData && hasChanged) {
            currentCell.classList.add('is-edited');
        }
    } else {
        currentCell.innerHTML = '';
        currentCell.classList.remove('is-filled');
        currentCell.classList.remove('is-edited');
    }
    
    // 編集があった場合、キャンセルボタンのラベルを変更
    if (!isCreatingMode && currentRecord && originalRecordData) {
        const hasGridChanges = getGridDataForComparison(getCurrentGridData()) !== getGridDataForComparison(originalRecordData.data);
        const hasDateChanges = mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate;
        if (hasGridChanges || hasDateChanges) {
            // 適用中の時間割は破棄ボタンに変えず常に削除不可で表示する
            if (isRecordActive(currentRecord)) {
                cancelCreationBtn.textContent = '削除不可（適用中）';
                cancelCreationBtn.disabled = true;
                cancelCreationBtn.style.opacity = '0.5';
                cancelCreationBtn.style.cursor = 'not-allowed';
            } else {
                cancelCreationBtn.textContent = '変更を破棄';
                cancelCreationBtn.disabled = false;
                cancelCreationBtn.style.opacity = '1';
                cancelCreationBtn.style.cursor = 'pointer';
            }
        } else {
            // 変更がない状態に戻った場合
            if (isRecordActive(currentRecord)) {
                cancelCreationBtn.textContent = '削除不可（適用中）';
                cancelCreationBtn.disabled = true;
                cancelCreationBtn.style.opacity = '0.5';
                cancelCreationBtn.style.cursor = 'not-allowed';
            } else {
                cancelCreationBtn.textContent = '削除';
                cancelCreationBtn.disabled = false;
                cancelCreationBtn.style.opacity = '1';
                cancelCreationBtn.style.cursor = 'pointer';
            }
        }
    }
    
    editModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    currentCell = null;
});

/*
    * 概要: 現在のメイン画面のグリッド（入力済みセル）を配列として収集する。
    * 使用方法: 現在の編集内容を取得して保存／比較に使う際に呼び出してください。
    */
function getCurrentGridData() {
    const gridData = [];
    document.querySelectorAll('.timetable-cell.is-filled').forEach(cell => {
        const teachers = Array.from(cell.querySelectorAll('.teacher-name span')).map(el => el.textContent);
        const rooms = Array.from(cell.querySelectorAll('.room-name span')).map(el => el.textContent);
        gridData.push({
            day: cell.dataset.day, 
            period: cell.dataset.period,
            className: cell.querySelector('.class-name')?.textContent || '',
            teacherName: teachers[0] || '',
            roomName: rooms[0] || ''
        });
    });
    return gridData;
}

/*
    * 概要: 比較用にグリッドデータを日→時限でソートして安定化した JSON を返す。
    * 使用方法: 変更検出の比較（元データ vs 現在データ）にこの関数の返り値を使ってください。
    */
function getGridDataForComparison(gridData) {
    const dayOrder = { '月': 0, '火': 1, '水': 2, '木': 3, '金': 4, '土': 5, '日': 6 };
    const sorted = JSON.parse(JSON.stringify(gridData)).sort((a, b) => {
        if (dayOrder[a.day] !== dayOrder[b.day]) {
            return dayOrder[a.day] - dayOrder[b.day];
        }
        return parseInt(a.period) - parseInt(b.period);
    });
    return JSON.stringify(sorted);
}

completeButton.addEventListener('click', () => {
    if (!mainStartDate.value || !mainEndDate.value) { 
        alert('適用期間を入力してください。'); 
        return; 
    }
    
    // 適用期間が逆転していないかチェック
    if (mainStartDate.value > mainEndDate.value) {
        alert('開始日が終了日より後になっています。\n適用期間を正しく設定してください。');
        return;
    }
    
    const currentGridData = getCurrentGridData();

    if (isCreatingMode) {
        // 新規作成の完了
        const courseText = document.getElementById('creatingCourseName').textContent;
        
        // 適用期間の重複チェック
        const sDate = mainStartDate.value;
        const eDate = mainEndDate.value;
        
        if (sDate && eDate) {
            if (checkCourseOverlap(courseText, sDate, eDate)) {
                alert('指定された適用期間は、同じコースの既存の時間割と重複しています。\n別の期間を指定するか、既存の時間割を削除してください。');
                return;
            }
        } else {
            // 期間未設定の場合、同じコースに既に期間未設定の時間割があるかチェック
            const existingNoPeriod = savedTimetables.find(record => 
                record.course === courseText && (!record.startDate || !record.endDate)
            );
            if (existingNoPeriod) {
                alert('同じコースに期間未設定の時間割が既に存在します。\n先に既存の時間割に適用期間を設定するか、削除してください。');
                return;
            }
        }

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
        }, true);

        alert('保存しました。');
    } else if (currentRecord) {
        // 既存の編集の保存
        
        // 適用期間の重複チェック（自分以外のレコードとの重複）
        const sDate = mainStartDate.value;
        const eDate = mainEndDate.value;
        
        if (sDate && eDate && checkCourseOverlap(currentRecord.course, sDate, eDate, currentRecord.id)) {
            alert('指定された適用期間は、同じコースの既存の時間割と重複しています。\n別の期間を指定してください。');
            return;
        }
        
        // データと期間を更新
        currentRecord.data = currentGridData;
        currentRecord.startDate = mainStartDate.value;
        currentRecord.endDate = mainEndDate.value;
        
        // originalRecordDataを再度設定（保存状態をリセット）
        originalRecordData = {
            data: JSON.parse(JSON.stringify(currentRecord.data)),
            startDate: currentRecord.startDate,
            endDate: currentRecord.endDate
        };
        
        // ボタンラベルをリセット
        completeButton.textContent = '保存';
        
        // 更新後の適用期間状態に応じてボタンを設定
        if (isRecordActive(currentRecord)) {
            cancelCreationBtn.textContent = '削除不可（適用中）';
            cancelCreationBtn.disabled = true;
            cancelCreationBtn.style.opacity = '0.5';
            cancelCreationBtn.style.cursor = 'not-allowed';
        } else {
            cancelCreationBtn.textContent = '削除';
            cancelCreationBtn.disabled = false;
            cancelCreationBtn.style.opacity = '1';
            cancelCreationBtn.style.cursor = 'pointer';
        }
        
        // リストを更新
        const mode = document.querySelector('input[name="displayMode"]:checked').value;
        renderSavedList(mode);
        
        // 選択状態を維持
        const targetItem = document.querySelector(`.saved-item[data-id="${currentRecord.id}"]`);
        if(targetItem) targetItem.classList.add('active');
        
        // 編集ハイライトを削除
        document.querySelectorAll('.timetable-cell.is-edited').forEach(cell => {
            cell.classList.remove('is-edited');
        });
        
        alert('変更を保存しました。');
    }
});

cancelCreationBtn.addEventListener('click', () => {
    // ボタンが無効化されている場合は処理しない
    if (cancelCreationBtn.disabled) {
        alert('現在適用期間中の時間割は削除できません。');
        return;
    }
    
    if(isCreatingMode) {
        // 作成中のキャンセル
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
        // 削除または変更破棄
        const hasChanges = originalRecordData !== null;
        
        if (hasChanges) {
            // 適用期間の変更チェック
            const hasDateChanges = mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate;
            const hasGridChanges = getGridDataForComparison(getCurrentGridData()) !== getGridDataForComparison(originalRecordData.data);

            if (hasDateChanges || hasGridChanges) {
                // 変更がある場合は破棄確認
                if(!confirm('変更を破棄しますか？')) return;
                
                // オリジナルデータに戻す
                currentRecord.data = JSON.parse(JSON.stringify(originalRecordData.data));
                currentRecord.startDate = originalRecordData.startDate;
                currentRecord.endDate = originalRecordData.endDate;
                
                // originalRecordDataを再度設定（次の編集で変更検出できるように）
                originalRecordData = {
                    startDate: currentRecord.startDate,
                    endDate: currentRecord.endDate,
                    data: JSON.parse(JSON.stringify(currentRecord.data))
                };
                
                // ボタンラベルをリセット
                completeButton.textContent = '保存';
                if (isRecordActive(currentRecord)) {
                    cancelCreationBtn.textContent = '削除不可（適用中）';
                    cancelCreationBtn.disabled = true;
                    cancelCreationBtn.style.opacity = '0.5';
                    cancelCreationBtn.style.cursor = 'not-allowed';
                } else {
                    cancelCreationBtn.textContent = '削除';
                    cancelCreationBtn.disabled = false;
                    cancelCreationBtn.style.opacity = '1';
                    cancelCreationBtn.style.cursor = 'pointer';
                }
                
                // 適用期間を復元
                mainStartDate.value = currentRecord.startDate;
                mainEndDate.value = currentRecord.endDate;
                
                // リストを更新
                const mode = document.querySelector('input[name="displayMode"]:checked').value;
                renderSavedList(mode);
                const targetItem = document.querySelector(`.saved-item[data-id="${currentRecord.id}"]`);
                if(targetItem) targetItem.classList.add('active');
                
                // 画面を再描画
                document.querySelectorAll('.timetable-cell').forEach(cell => { 
                    cell.innerHTML = ''; 
                    cell.classList.remove('is-filled');
                    cell.classList.remove('is-edited');
                });
                currentRecord.data.forEach(item => {
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
                
                alert('変更を破棄しました。');
                return;
            }
        }
        
        // 変更がない場合は削除
        // 適用期間中チェック（念のため再度チェック）
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
        footerArea.classList.remove('show');
        mainStartDate.disabled = false;
        mainEndDate.disabled = false;
        mainStartDate.value = '';
        mainEndDate.value = '';
        document.querySelectorAll('.timetable-cell').forEach(cell => { 
            cell.innerHTML = ''; 
            cell.classList.remove('is-filled');
            cell.classList.remove('is-edited');
        });
        document.getElementById('mainCourseDisplay').innerHTML = "（未選択）";
        
        document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));

        setTimeout(() => alert('削除しました。'), 10);
    }
});

/*
    * 概要: 保存済みリストの項目がクリックされた際のハンドラ。編集確認、閲覧モード切替、選択状態の反映を行う。
    * 使用方法: 各リスト項目の click イベントにこの関数をバインドしてください（内部で UI 更新を行います）。
    */
function handleSavedItemClick(e, forceSelect = false) {
    if(e.preventDefault) e.preventDefault();
    
    let id = parseInt(e.currentTarget?.getAttribute('data-id'));

    // 別の時間割りへの遷移前に編集確認（遷移先が異なる場合のみ）
    if (currentRecord && currentRecord.id !== id && originalRecordData) {
        // 現在の時間割りで編集があるかチェック
        const hasDateChanges = mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate;
        const hasGridChanges = getGridDataForComparison(getCurrentGridData()) !== getGridDataForComparison(originalRecordData.data);

        if (hasDateChanges || hasGridChanges) {
            if (!confirm('現在の時間割りの編集が保存されていません。\n別の時間割りに移動しますか？\n（変更は失われます）')) {
                return;
            }
        }
    }

    // 作成中モードで他の時間割を見る場合は閲覧専用
    if (isCreatingMode && !isViewOnly) {
        tempCreatingData = {
            courseName: document.getElementById('creatingCourseName').textContent,
            startDate: mainStartDate.value,
            endDate: mainEndDate.value,
            gridData: JSON.parse(JSON.stringify(getCurrentGridData()))  // 深くコピーしてデータ混在を防ぐ
        };
        
        // 作成中から他の時間割を見る = 閲覧専用
        isViewOnly = true;
    } else if (!isCreatingMode) {
        // 通常は編集可能（作成中ではない場合のみ）
        isViewOnly = false;
    }
    // 既に isViewOnly = true なら、tempCreatingData は上書きしない

    // 新規作成完了時のみ「選択」ラジオボタンを自動選択
    if (forceSelect) {
        const selectRadio = document.querySelector('input[value="select"]');
        if(selectRadio && !selectRadio.checked) {
            selectRadio.checked = true;
        }
    }
    
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
    const targetItem = document.querySelector(`.saved-item[data-id="${id}"]`);
    if(targetItem) targetItem.classList.add('active');

    const record = savedTimetables.find(item => item.id === id);
    if (record) {
        currentRecord = record;
        
        // オリジナルデータを保存（編集用）
        if (!isViewOnly) {
            // record.data も深くコピーしてデータ混在を防ぐ
            originalRecordData = {
                data: JSON.parse(JSON.stringify(record.data)),
                startDate: record.startDate,
                endDate: record.endDate
            };
            completeButton.textContent = '保存';
            
            // 適用期間中かチェック
            if (isRecordActive(record)) {
                cancelCreationBtn.textContent = '削除不可（適用中）';
                cancelCreationBtn.disabled = true;
                cancelCreationBtn.style.opacity = '0.5';
                cancelCreationBtn.style.cursor = 'not-allowed';
            } else {
                cancelCreationBtn.textContent = '削除';
                cancelCreationBtn.disabled = false;
                cancelCreationBtn.style.opacity = '1';
                cancelCreationBtn.style.cursor = 'pointer';
            }
        }
        
        // UIの更新
        if (isCreatingMode && isViewOnly) {
            // 作成中から閲覧
            resetViewBtn.classList.add('hidden');
            footerArea.classList.remove('show');
            mainStartDate.disabled = true;
            mainEndDate.disabled = true;
        } else {
            // 通常の編集（期間も編集可能）
            resetViewBtn.classList.add('hidden');
            footerArea.classList.add('show');
            completeButton.textContent = '保存';
            // 適用中の場合は削除不可を維持、そうでなければ削除
            if (isRecordActive(record)) {
                cancelCreationBtn.textContent = '削除不可（適用中）';
            } else {
                cancelCreationBtn.textContent = '削除';
            }
            mainStartDate.disabled = false;
            mainEndDate.disabled = false;
            
            // ヘッダーを「時間割り編集」に変更
            document.querySelector('.app-header h1').textContent = '時間割り編集';
        }

        updateHeaderDisplay(record);
        
        mainStartDate.value = record.startDate;
        mainEndDate.value = record.endDate;
        
        // ボーダーのスタイルをクリア（赤色ボーダーを削除）
        mainStartDate.style.borderColor = '';
        mainEndDate.style.borderColor = '';
        
        document.querySelectorAll('.timetable-cell').forEach(cell => { 
            cell.innerHTML = ''; 
            cell.classList.remove('is-filled');
            cell.classList.remove('is-edited');
        });
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

// ページ読み込み時の初期化
window.addEventListener('DOMContentLoaded', () => {
const dropdownItems = document.querySelectorAll('[data-value]'); // ViewHelperのliを取得
    const toggleText = document.querySelector('#courseDropdownToggle .current-value');
    // const dropdownMenu = ... (その他の定義)

    dropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault(); // aタグのリンク遷移を無効化
            
            // 選択されたテキストとIDを取得
            const selectedText = this.textContent.trim(); // aタグの中身のテキスト
            const selectedId = this.getAttribute('data-value');

            // 表示を更新
            if(toggleText) toggleText.textContent = selectedText;

            // グローバル変数を更新
            currentCourseId = selectedId;

            // リスト（サイドバー）を再描画
            if (typeof renderSavedList === 'function') {
                renderSavedList('select'); 
            }
        });
    });

    // ... その他の初期化処理があればここに記述する ...

    selectInitialTimetable();

});