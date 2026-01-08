// script.js
// create_timetable.php で使用するJavaScriptコード

/**
 * 時間割作成画面用スクリプト
 * * PHP側から渡されたデータや設定は window.serverData などの
 * グローバルオブジェクト経由、またはHTMLのdata属性経由で取得することを推奨します。
 */

/* ↓ 以下の関数や変数が元のコードに含まれているはずです。
    これらを含むすべてのロジックをここに貼り付けてください。
*/

let savedTimetables = [];
let isCreatingMode = false;
let isViewOnly = false;
let currentRecord = null;
let tempCreatingData = null;
let originalRecordData = null; // 編集前のオリジナルデータ
let previousState = null; // 新規作成前の状態を保存

/*
* 概要: デモ用の時間割データを初期化する（ページロード時に呼ばれる）。
* 使用方法: ページ読み込み時に一度呼び出してください（内部で savedTimetables を設定します）。
*/
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
    
    // 未来の日付（3ヶ月後）
    const threeMonthsLater = new Date(today);
    threeMonthsLater.setMonth(threeMonthsLater.getMonth() + 3);
    const threeMonthsLaterStr = threeMonthsLater.toISOString().split('T')[0];

    // デモ用の作成済み時間割りデータ
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
            course: "マルチメディアOAコース",
            startDate: nextMonthStr,
            endDate: threeMonthsLaterStr,
            data: [
                {day: "月", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "302演習室"},
                {day: "月", period: "2", className: "プロジェクト管理", teacherName: "山本 さくら", roomName: "202教室"},
                {day: "火", period: "1", className: "データベース基礎", teacherName: "高橋 誠", roomName: "201教室"},
                {day: "水", period: "1", className: "キャリアデザイン", teacherName: "伊藤 直人", roomName: "4F大講義室"},
                {day: "木", period: "1", className: "HR", teacherName: "田中 優子", roomName: "202教室"}
            ]
        },
        {
            id: 1004,
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

/*
* 関数名: selectInitialTimetable
* 概要: 優先コースリストに基づき、保存された時間割データから最初に見つかった優先コースを自動選択して表示する。
* 実装理由: 存在している時間割りデータの中で、特定のコースを優先的に表示したい場合に使用します。
* 使用方法: データ読み込み後に呼び出すと、優先コースがあれば自動で選択して表示します。
* 引数: なし
* 戻り値: なし
*/
function selectInitialTimetable() {
    const priorityCourses = [
        "システムデザインコース",
        "Webクリエイタコース", 
        "マルチメディアOAコース",
        "応用情報コース",
        "基本情報コース",
        "ITパスポートコース",
        "１年１組",
        "１年２組"
    ];
    
    // 優先コース順に存在をチェックして最初に見つかったものを選択
    for (const courseName of priorityCourses) {
        // 該当コースの時間割が存在するか確認
        const record = savedTimetables.find(r => r.course === courseName);
        if (record) {
            // ドロップダウンの表示を該当コース名に更新する
            document.querySelector('#courseDropdownToggle .current-value').textContent = courseName;
            
            // リストを再描画して該当コースのみ表示する
            // モードは 'select' に設定
            renderSavedList('select');
            
            // 該当アイテムをクリックして選択状態にするための遅延処理
            setTimeout(() => {
                // 少し遅延させてからクリックイベントを実行する
                const targetItem = document.querySelector(`.saved-item[data-id="${record.id}"]`);
                if (targetItem) {
                    targetItem.click();
                }
            }, 100);
            
            return;
        }
    }
}

// イベント要素の参照の取得
// 各要素のIDに基づいて参照を取得します
const mainCreateNewBtn = document.getElementById('mainCreateNewBtn'); // 新規作成ボタンの参照
const defaultNewBtnArea = document.getElementById('defaultNewBtnArea'); // 新規ボタンエリア
const creatingItemArea = document.getElementById('creatingItemArea'); // 作成中アイコンの表示エリア
const creatingCourseName = document.getElementById('creatingCourseName'); // 作成中コース名表示要素
const mainStartDate = document.getElementById('mainStartDate'); // 適用開始日フィールド
const mainEndDate = document.getElementById('mainEndDate'); // 適用終了日フィールド
const resetViewBtn = document.getElementById('resetViewBtn'); // 表示リセットボタン
const createModal = document.getElementById('createModal'); // 作成モーダル
const createGradeSelect = document.getElementById('createGradeSelect'); // 学年選択ドロップダウン
const createCourseSelect = document.getElementById('createCourseSelect'); // コース選択ドロップダウン
const checkCsv = document.getElementById('checkCsv'); // CSVチェックボックス
const csvInputArea = document.getElementById('csvInputArea'); // CSV入力エリア
const createSubmitBtn = document.getElementById('createSubmitBtn'); // 作成送信ボタン（新規作成ボタンクリック後に表示されるポップアップ内の作成ボタンに関する参照）
const createCancelBtn = document.getElementById('createCancelBtn'); // 作成キャンセルボタン（新規作成ボタンクリック後に表示されるポップアップ内のキャンセルボタンに関する参照）
const footerArea = document.getElementById('footerArea'); // フッターエリア
const completeButton = document.getElementById('completeButton'); // 完了ボタン(作成フォーム内の完了ボタンに関する参照)
const cancelCreationBtn = document.getElementById('cancelCreationBtn'); // キャンセルボタン(作成フォーム内のキャンセルボタンに関する参照)

// 現在作成中の時間割りに戻るクリックイベント
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
    }
    
    resetViewBtn.classList.add('hidden');
    footerArea.classList.add('show');
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

// 適用期間の変更を監視
mainStartDate.addEventListener('input', () => {
    if (!isCreatingMode && currentRecord && originalRecordData) {
        if (mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate) {
            cancelCreationBtn.textContent = '変更を破棄';
            cancelCreationBtn.disabled = false;
            cancelCreationBtn.style.opacity = '1';
            cancelCreationBtn.style.cursor = 'pointer';
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
    if (!isCreatingMode && currentRecord && originalRecordData) {
        if (mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate) {
            cancelCreationBtn.textContent = '変更を破棄';
            cancelCreationBtn.disabled = false;
            cancelCreationBtn.style.opacity = '1';
            cancelCreationBtn.style.cursor = 'pointer';
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
    
    // 作成済み時間割を参照していた場合のみ期間をクリア
    if (currentRecord) {
        mainStartDate.value = '';
        mainEndDate.value = '';
    }
    
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
        
        // 作成中は適用期間を編集可能にする
        mainStartDate.disabled = false;
        mainEndDate.disabled = false;
        
        footerArea.classList.add('show');
        completeButton.textContent = '完了';
        cancelCreationBtn.textContent = 'キャンセル';
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
        const hasOverlap = savedTimetables.some(record => {
            if (record.course !== selectedCourse) return false;
            const recEnd = record.endDate || record.startDate;
            return (sDate <= recEnd) && (eDate >= record.startDate);
        });

        if (hasOverlap) {
            alert('指定された適用期間は、同じコースの既存の時間割と重複しています。\n別の期間を指定するか、既存の時間割を削除してください。');
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
    const todayStr = new Date().toISOString().split('T')[0];
    const isStarted = record.startDate <= todayStr;
    const isNotEnded = !record.endDate || record.endDate >= todayStr;
    return isStarted && isNotEnded;
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
const inputTeacherName = document.getElementById('inputTeacherName');
const inputRoomName = document.getElementById('inputRoomName');
const btnCancel = document.getElementById('btnCancel');
const btnSave = document.getElementById('btnSave');
let currentCell = null;

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
        setVal(inputTeacherName, this.querySelector('.teacher-name span')?.textContent || '');
        setVal(inputRoomName, this.querySelector('.room-name span')?.textContent || '');

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

btnSave.addEventListener('click', function() {
    if (!currentCell) return;
    
    // 編集前の値を取得
    const oldClassName = currentCell.querySelector('.class-name')?.textContent || '';
    const oldTeacherName = currentCell.querySelector('.teacher-name span')?.textContent || '';
    const oldRoomName = currentCell.querySelector('.room-name span')?.textContent || '';
    
    const c = inputClassName.value;
    const t = inputTeacherName.value;
    const r = inputRoomName.value;

    // 変更があったかチェック
    const hasChanged = (c !== oldClassName) || (t !== oldTeacherName) || (r !== oldRoomName);

    if (c || t || r) {
        currentCell.innerHTML = `
            <div class="class-content">
                <div class="class-name">${c}</div>
                <div class="class-detail">
                    ${t ? `<div class="teacher-name"><i class="fa-solid fa-user icon"></i><span>${t}</span></div>` : ''}
                    ${r ? `<div class="room-name"><i class="fa-solid fa-location-dot icon"></i><span>${r}</span></div>` : ''}
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
            cancelCreationBtn.textContent = '変更を破棄';
            cancelCreationBtn.disabled = false;
            cancelCreationBtn.style.opacity = '1';
            cancelCreationBtn.style.cursor = 'pointer';
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
    if (!mainStartDate.value) { 
        alert('適用期間を入力してください。'); 
        return; 
    }
    
    const currentGridData = getCurrentGridData();

    if (isCreatingMode) {
        // 新規作成の完了
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
        // 既存の編集の保存
        
        // 適用期間の重複チェック（自分以外のレコードとの重複）
        const sDate = mainStartDate.value;
        const eDate = mainEndDate.value;
        const hasOverlap = savedTimetables.some(record => {
            if (record.id === currentRecord.id) return false; // 自分自身は除外
            if (record.course !== currentRecord.course) return false;
            const recEnd = record.endDate || record.startDate;
            return (sDate <= recEnd) && (eDate >= record.startDate);
        });

        if (hasOverlap) {
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
function handleSavedItemClick(e) {
    if(e.preventDefault) e.preventDefault();
    
    let id = parseInt(e.currentTarget?.getAttribute('data-id'));

    // 別の時間割りへの遷移前に編集確認（遷移先が異なる場合のみ）
    if (originalRecordData) {
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
            gridData: getCurrentGridData()
        };
        
        // 作成中から他の時間割を見る = 閲覧専用
        isViewOnly = true;
    } else {
        // 通常は編集可能
        isViewOnly = false;
    }

    const selectRadio = document.querySelector('input[value="select"]');
    if(!selectRadio.checked) {
        selectRadio.checked = true;
    }
    
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
    const targetItem = document.querySelector(`.saved-item[data-id="${id}"]`);
    if(targetItem) targetItem.classList.add('active');

    const record = savedTimetables.find(item => item.id === id);
    if (record) {
        currentRecord = record;
        
        // オリジナルデータを保存（編集用）
        if (!isViewOnly) {
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
            resetViewBtn.textContent = '作成中に戻る';
            resetViewBtn.classList.remove('hidden');
            footerArea.classList.remove('show');
            mainStartDate.disabled = true;
            mainEndDate.disabled = true;
        } else {
            // 通常の編集（期間も編集可能）
            resetViewBtn.classList.add('hidden');
            footerArea.classList.add('show');
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
    initializeDemoData();
    selectInitialTimetable();
});

const displayModeRadios = document.querySelectorAll('input[name="displayMode"]');
displayModeRadios.forEach(radio => {
    radio.addEventListener('change', (e) => {
        changeDisplayMode(e.target.value);
    });
});

// --------------------------------------------------------
// ページ読み込み時の初期化処理（ここは元のままでOK）
// --------------------------------------------------------
initializeDemoData();
selectInitialTimetable();