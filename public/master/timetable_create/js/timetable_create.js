// create_timetable.js

// 1. データ格納用の変数
let savedTimetables = [];

// 2. PHPからデータが渡ってきているか確認
if (typeof dbTimetableData !== 'undefined' && Array.isArray(dbTimetableData)) {
    // PHP (Repository) 側ですでに整形済みなので、そのまま代入するだけでOKです！
    savedTimetables = dbTimetableData;
} else {
    // データが無い場合は空配列で初期化
    console.warn("DBからのデータ読み込みに失敗しました、またはデータがありません。");
    savedTimetables = [];
}

// --- グローバル変数 ---
let isViewOnly = false;
let currentRecord = null;
let originalRecordData = null; // 編集前のオリジナルデータ
let previousState = null; // 新規作成前の状態を保存
let currentCourseId = null; // 現在選択中のコースID
let currentCourseName = 'システムデザインコース'; // 現在選択中のコース名（表示用）

// --- 状態管理用変数 ---
let isCreatingMode = false;    // 新規作成モードかどうか
let isTestMode = false;        // テスト時間割モードかどうか（trueならオートフィル無効）
let tempCreatingData = {};     // 作成中の時間割データを保持する { "月_1": { subjectId:..., teacherIds:[...], ... } }


// --- マスタデータ操作用ヘルパー ---
// ※dbMasterData は PHP 側で生成されたマスタデータオブジェクト
/**
 * getCurrentCourseMasterData
 * 概要：選択中のコースに紐づく「科目定義リスト」を取得する関数
 * 戻り値：配列（科目定義オブジェクトの配列）
 * 使用方法：コース選択後や、科目/教員/教室のドロップダウン生成時に呼び出してください。
 */
function getCurrentCourseMasterData() {
    if (!currentCourseId || !dbMasterData || !dbMasterData[currentCourseId]) {
        return [];
    }
    return dbMasterData[currentCourseId];
}

/**
 * getAvailableTeachers
 * 概要：現在のコースで利用可能な「先生リスト」を抽出（重複を除去する）
 * 戻り値：配列（先生オブジェクトの配列）
 * 使用方法：教員ドロップダウン生成時に呼び出してください。
 */
function getAvailableTeachers() {
    const data = getCurrentCourseMasterData();
    const teachers = new Map(); // 重複排除用
    
    data.forEach(row => {
        // teacher_id があり、かつ teacher_name がある場合のみ
        if (row.teacher_id && row.teacher_name) {
            teachers.set(row.teacher_id, row.teacher_name);
        }
    });
    
    return Array.from(teachers, ([id, name]) => ({ id, name }));
}

/**
 * getAvailableRooms
 * 概要：現在のコースで利用可能な「教室リスト」を抽出（重複を除去する）
 * 戻り値：配列（教室オブジェクトの配列）
 * 使用方法：教室ドロップダウン生成時に呼び出してください。
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


// --- 初期選択処理 ---
/*
 * 概要: 優先度順、またはデータが存在する順に初期選択を行う。
 * 使用方法: データ読み込み後に呼び出すと、適切なコースを自動で選択して表示します。
 */
function selectInitialTimetable() {
    if (!savedTimetables || savedTimetables.length === 0) return;

    let targetRecord = savedTimetables.find(r => r.statusType === 1);
    if (!targetRecord) targetRecord = savedTimetables[0];

    const targetCourseId = targetRecord.courseId;
    const targetTimetableId = targetRecord.id;

    // 変数をセット
    currentCourseId = targetCourseId;
    
    // ドロップダウン更新
    const toggleText = document.querySelector('#courseDropdownToggle .current-value');
    if(toggleText) toggleText.textContent = targetRecord.course;

    // リスト描画
    renderSavedList('select');

    // ★修正：確実にクリックイベントを発火させる
    setTimeout(() => {
        // コンテナ内の対象要素を探す
        const container = document.getElementById('savedListContainer');
        // data-id 属性を持つ要素を検索
        const sidebarItem = container.querySelector(`.saved-item[data-id="${targetTimetableId}"]`);

        if (sidebarItem) {
            sidebarItem.click();
        } else {
            console.error(`自動選択エラー: ID=${targetTimetableId} の要素が描画されていません。`);
        }
    }, 300); // 描画待ち時間を少し長めに(300ms)確保
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

// --- メイン画面の制御ロジック ---
// 作成中に戻るボタンの処理
document.getElementById('creatingItemCard').addEventListener('click', () => {
    if (!isCreatingMode) return;
    
    // 作成中に戻る = 閲覧モード解除
    isViewOnly = false;
    currentRecord = null;
    originalRecordData = null;
    
    if (tempCreatingData && tempCreatingData.gridData) {
        // グリッドをクリア
        document.querySelectorAll('.timetable-cell').forEach(cell => {
            cell.innerHTML = '';
            cell.classList.remove('is-filled');
            cell.classList.remove('is-edited');
            delete cell.dataset.subjectId;
            delete cell.dataset.teacherIds;
            delete cell.dataset.roomIds;
        });
        
        // データを復元
        tempCreatingData.gridData.forEach(item => {
            const targetCell = document.querySelector(`.timetable-cell[data-day="${item.day}"][data-period="${item.period}"]`);
            if (targetCell) {
                // HTML復元
                let teacherHtml = item.teacherName ? `<div class="teacher-name"><i class="fa-solid fa-user icon"></i><span>${item.teacherName}</span></div>` : '';
                let roomHtml = item.roomName ? `<div class="room-name"><i class="fa-solid fa-location-dot icon"></i><span>${item.roomName}</span></div>` : '';
                
                targetCell.innerHTML = `
                <div class="class-content">
                    <div class="class-name">${item.className}</div>
                    <div class="class-detail">${teacherHtml}${roomHtml}</div>
                </div>`;
                targetCell.classList.add('is-filled');

                // データ属性(ID)の復元（tempCreatingData作成時に保存しておく必要がありますが、まずは表示優先）
            }
        });
        
        document.getElementById('mainCourseDisplay').innerHTML = tempCreatingData.courseName + '<span class="active-badge creating">現在作成中</span>';
        mainStartDate.value = tempCreatingData.startDate;
        mainEndDate.value = tempCreatingData.endDate;
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
    
    // 編集モード時の削除ボタン表示切替
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
        const hasGridChanges = getGridDataForComparison(getTimetableData()) !== getGridDataForComparison(originalRecordData.data);
        
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
        gridData: getTimetableData(),
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

/**
 * createCancelBtn のクリックイベント
 * 修正版: 画面クリア後、少し待ってから確実に初期状態を復元する
 */
createCancelBtn.addEventListener('click', (e) => {
    if(e) e.preventDefault();

    // モーダル閉じる
    createModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    footerArea.classList.remove('show');

    // 変数リセット
    isCreatingMode = false;
    isViewOnly = false;
    currentRecord = null;
    originalRecordData = null;
    previousState = null;
    tempCreatingData = null;

    // UIクリア
    document.getElementById('mainCourseDisplay').innerHTML = "（読み込み中...）";
    mainStartDate.value = '';
    mainEndDate.value = '';
    mainStartDate.disabled = false;
    mainEndDate.disabled = false;

    // グリッドクリア
    document.querySelectorAll('.timetable-cell').forEach(cell => {
        cell.innerHTML = '';
        cell.classList.remove('is-filled');
        cell.classList.remove('is-edited');
        delete cell.dataset.subjectId;
        delete cell.dataset.teacherIds;
        delete cell.dataset.roomIds;
    });
    
    // サイドバー選択解除
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));

    // 表示モードを「選択(select)」に戻す
    const selectRadio = document.querySelector('input[name="displayMode"][value="select"]');
    if (selectRadio) selectRadio.checked = true;

    // ★重要：強化した初期化関数を呼ぶ
    // 変数のセット・リスト描画・クリックまで全部やってくれます
    selectInitialTimetable();
});

// --- 新規作成モーダルの制御ロジック ---

// 学年が変更されたときの処理
createGradeSelect.addEventListener('change', function() {
    const selectedGrade = this.value; // "1", "2", "all" または ""
    
    // コース選択肢をリセット
    createCourseSelect.innerHTML = '<option value="">コースを選択してください</option>';
    createCourseSelect.disabled = true;
    
    // バリデーションチェック（ボタン制御）
    checkCreateValidation();

    // 学年が未選択ならここで終了
    if (!selectedGrade) {
        createCourseSelect.innerHTML = '<option value="">先に学年を選択してください</option>';
        return;
    }

    // PHPから渡された dbCourseList を使ってフィルタリング
    // dbCourseList は [{course_id: 1, course_name: "...", grade: 1}, ...] の配列
    const filteredCourses = dbCourseList.filter(course => {
        if (selectedGrade === 'all') {
            return true; // "全体"なら全コース表示
        }
        // DBのgradeは数値、selectedGradeは文字列の可能性があるため、== (緩い比較)を使用
        return course.grade == selectedGrade;
    });

    // フィルタリング結果があれば選択肢を追加
    if (filteredCourses.length > 0) {
        filteredCourses.forEach(course => {
            const option = document.createElement('option');
            option.value = course.course_id;   // DBのIDをvalueに設定
            option.textContent = course.course_name; // コース名を表示
            createCourseSelect.appendChild(option);
        });
        createCourseSelect.disabled = false; // 有効化
    } else {
        const option = document.createElement('option');
        option.textContent = "該当するコースがありません";
        createCourseSelect.appendChild(option);
    }
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
    // 1. ID（value属性）と コース名（表示テキスト）を両方取得する
    const selectedCourseId = createCourseSelect.value;
    const selectedOption = createCourseSelect.options[createCourseSelect.selectedIndex];
    const selectedCourseName = selectedOption.text; // これで「Webクリエイターコース」などが取れます
    
    // メイン画面の適用期間値を取得
    const sDate = mainStartDate.value;
    const eDate = mainEndDate.value;
    
    // 適用期間が入力されている場合のみ重複チェック
    // ※チェックには ID (selectedCourseId) を使います
    if (sDate && eDate) {
        if (checkCourseOverlap(selectedCourseId, sDate, eDate)) {
            alert('指定された適用期間は、同じコースの既存の時間割と重複しています。\n別の期間を指定するか、既存の時間割を削除してください。');
            return;
        }
    } else {
        // 期間未設定の場合のチェック
        const existingNoPeriod = dbTimetableData.find(record => // ※savedTimetablesではなくdbTimetableDataを使用（前回のPHP渡し変数名に合わせる）
            record.course_id == selectedCourseId && (!record.start_date || !record.end_date)
        );
        if (existingNoPeriod) {
            alert('同じコースに期間未設定の時間割が既に存在します。\n先に既存の時間割に適用期間を設定するか、削除してください。');
            return;
        }
    }

    // 保存時に使うグローバル変数にIDをセットする
    currentCourseId = selectedCourseId;

    // 既存の選択をクリア
    currentRecord = null;
    originalRecordData = null; // もし使っていれば
    // isViewOnly = false; // ※必要に応じて定義すること
    document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
    
    // 作成モード開始 (表示用に Name を渡す)
    toggleCreatingMode(true, selectedCourseName, sDate, eDate);

    // メイン画面のヘッダー表示を「コース名」に変更
    document.getElementById('mainCourseDisplay').innerHTML = selectedCourseName;
    
    const selectRadio = document.querySelector('input[value="select"]');
    if (selectRadio && !selectRadio.checked) {
        selectRadio.checked = true;
    }
    
    // 保存済みリストの表示更新（ここはロジックによるのでIDかNameか、既存の実装に合わせます）
    // renderSavedList('select'); 

    // テーブルのクリア
    document.querySelectorAll('.timetable-cell').forEach(cell => {
        cell.innerHTML = '';
        cell.classList.remove('is-filled');
        cell.classList.remove('is-edited');
        // 新規作成なのでデータ属性もクリア
        delete cell.dataset.subjectId;
        delete cell.dataset.teacherId;
        delete cell.dataset.roomId;
    });

    // モーダルを閉じる
    createModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    
    // アラートにもコース名を表示
    setTimeout(() => alert(`「${selectedCourseName}」の作成を開始します。\nメイン画面で適用期間を設定し、授業情報を入力してください。`), 10);
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
    
    // コンテナがない場合は何もしない
    if (!container) return;

    const items = container.querySelectorAll('li:not(.is-group-label)');
    items.forEach(item => item.remove());

    let filteredRecords = savedTimetables;

    if (mode === 'select') {
        // ID未設定なら先頭をセット
        if (!currentCourseId && savedTimetables.length > 0) {
            currentCourseId = savedTimetables[0].courseId;
        }
        // ★IDで確実にフィルタリング (数値と文字列の差を無視するため == を使用)
        filteredRecords = filteredRecords.filter(item => item.courseId == currentCourseId);
    }

    // 日付フィルタリングロジック（変更なし）
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

    // ソート
    filteredRecords.sort((a, b) => {
        const aActive = isRecordActive(a) ? 0 : 1;
        const bActive = isRecordActive(b) ? 0 : 1;
        return aActive - bActive;
    });

    // ★重要：コンテナを強制的に表示状態にする
    if (filteredRecords.length > 0) {
        container.classList.remove('hidden');
        container.style.display = 'block'; // 念のためスタイルも直接指定
        if(divider) divider.classList.remove('hidden');
    }

    filteredRecords.forEach(record => {
        const dateLabel = getFormattedDate(record.startDate);
        let statusText = "";
        let statusClass = "text-slate-500";
        
        // ステータス判定（変更なし）
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

        const newItem = document.createElement('li');
        newItem.className = 'nav-item saved-item';
        newItem.setAttribute('data-id', record.id); // ここでIDをセット
        
        // ★視認性を高めるため、背景色とカーソルを明示
        newItem.style.cursor = 'pointer';
        
        newItem.innerHTML = `
            <a href="#" onclick="return false;" class="block p-2 hover:bg-gray-100">
                <div class="flex items-start">
                    <i class="fa-regular fa-file-lines text-blue-500 flex-shrink-0 mt-1 mr-2"></i>
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="truncate font-bold text-sm text-gray-900">${record.course}</span>
                        <span class="text-xs truncate ${statusClass}">
                            ${statusText}${dateLabel}
                        </span>
                    </div>
                </div>
            </a>
        `;
        
        // クリックイベントの登録
        newItem.addEventListener('click', (e) => {
            handleSavedItemClick(e);
        });
        
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

/**
 * renderSubjectDropdown
 * 概要：科目ドロップダウンを動的に生成する
 * ※セルをクリックした時に呼び出します
 * 使用方法：renderSubjectDropdown(selectedValue) の形式で呼び出してください。
 * 引数：
 * selectedValue - 事前に選択しておきたい科目名（省略可）
 */
function renderSubjectDropdown(selectedId = '') {
    const masterData = getCurrentCourseMasterData();
    
    // 一旦クリアする
    inputClassName.innerHTML = '<option value="">(科目を選択)</option>';

    masterData.forEach(item => {
        const option = document.createElement('option');
        
        // ★ここを修正：DB保存用にIDをvalueにセット
        option.value = item.subject_id; 
        
        // 表示は名称のまま
        option.textContent = item.subject_name;
        
        // IDで比較して選択状態にする
        // (型が数値と文字列で異なる場合があるので緩い比較 == を使用)
        if (item.subject_id == selectedId) {
            option.selected = true;
        }

        // オートフィル用データ属性（IDをセット）
        // 教員・教室もIDで自動選択させるため
        option.dataset.defTeacherId = item.teacher_id || '';
        option.dataset.defRoomId = item.room_id || '';

        inputClassName.appendChild(option);
    });
}

// --- オートフィル機能の実装 ---
/** 
 * inputClassName change event
 * 概要: 科目選択時に関連する先生・教室を自動入力する。
 * 使用方法: 科目ドロップダウンの change イベントにこの関数をセットしてください。
 * 引数: なし（this で選択された要素にアクセス）
*/
inputClassName.addEventListener('change', function() {
    // テストモードなら自動入力しない
    if (isTestMode) return;

    const selectedOption = this.options[this.selectedIndex];
    
    // データ属性からデフォルトのIDを取得
    const defTeacherId = selectedOption.dataset.defTeacherId;
    const defRoomId = selectedOption.dataset.defRoomId;

    // 1. 先生の自動選択（IDで指定）
    if (defTeacherId) {
        const teacherSelects = getTeacherInputs();
        if (teacherSelects.length > 0) {
            teacherSelects[0].value = defTeacherId; 
        }
    }

    // 2. 教室の自動選択（IDで指定）
    if (defRoomId) {
        const roomSelects = getRoomInputs();
        if (roomSelects.length > 0) {
            roomSelects[0].value = defRoomId; 
        }
    }
});

/*
 * addTeacherRow
 * 概要: 先生入力欄を追加する処理（動的データ対応版）
 * 使用方法: 追加ボタンのクリックイベント、またはデータ復元時に呼び出してください。
 * 引数 selectedValue: 初期選択状態にしたい先生ID（省略可）
 */
function addTeacherRow(selectedValue = null) {
    const currentCount = getTeacherInputs().length;
    if (currentCount >= 5) {
        alert('最大5個まで追加できます。');
        return;
    }
    
    const rowDiv = document.createElement('div');
    rowDiv.className = 'teacher-input-row';
    rowDiv.style.cssText = 'display: flex; gap: 8px; align-items: center; margin-bottom: 8px;';
    
    const select = document.createElement('select');
    select.className = 'teacher-input modal-select';
    select.style.flex = '1';
    
    // ★修正ポイント：マスタデータから動的にoptionを生成
    let html = '<option value="">(選択してください)</option>';
    const teachers = getAvailableTeachers(); // マスタから取得 [{id: 1, name: "佐藤"}, ...]
    
    teachers.forEach(t => {
        // IDで比較してselected属性を付与（数値と文字列の違いを吸収するため == を使用）
        const isSelected = (selectedValue == t.id) ? 'selected' : '';
        html += `<option value="${t.id}" ${isSelected}>${t.name}</option>`;
    });
    select.innerHTML = html;
    
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

/*
 * addRoomRow
 * 概要: 教室入力欄を追加する処理（動的データ対応版）
 * 使用方法: 追加ボタンのクリックイベント、またはデータ復元時に呼び出してください。
 * 引数 selectedValue: 初期選択状態にしたい教室ID（省略可）
 */
function addRoomRow(selectedValue = null) {
    const currentCount = getRoomInputs().length;
    if (currentCount >= 5) {
        alert('最大5個まで追加できます。');
        return;
    }
    
    const rowDiv = document.createElement('div');
    rowDiv.className = 'room-input-row';
    rowDiv.style.cssText = 'display: flex; gap: 8px; align-items: center; margin-bottom: 8px;';
    
    const select = document.createElement('select');
    select.className = 'room-input modal-select';
    select.style.flex = '1';
    
    // ★修正ポイント：マスタデータから動的にoptionを生成
    let html = '<option value="">(選択してください)</option>';
    const rooms = getAvailableRooms(); // マスタから取得 [{id: 1, name: "201教室"}, ...]
    
    rooms.forEach(r => {
        // IDで比較してselected属性を付与
        const isSelected = (selectedValue == r.id) ? 'selected' : '';
        html += `<option value="${r.id}" ${isSelected}>${r.name}</option>`;
    });
    select.innerHTML = html;
    
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

/**
 * timetable-cell click event
 * 概要: 時間割セルがクリックされたときの処理。
 * 使用方法: 各時間割セルのクリックイベントにこの関数をセットしてください。
 */
document.querySelectorAll('.timetable-cell').forEach(cell => {
    cell.addEventListener('click', function() {
        if (isViewOnly) {
            alert('既存の時間割りを編集することはできません。現在作成中の時間割りを作成し終えて編集を行ってください。');
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
        
        // 1. 保存されているIDを取得
        const currentSubjectId = this.dataset.subjectId || '';
        
        let currentTeacherIds = [];
        try {
            if (this.dataset.teacherIds) currentTeacherIds = JSON.parse(this.dataset.teacherIds);
        } catch (e) { console.error(e); }

        let currentRoomIds = [];
        try {
            if (this.dataset.roomIds) currentRoomIds = JSON.parse(this.dataset.roomIds);
        } catch (e) { console.error(e); }

        // 2. 科目ドロップダウン生成
        renderSubjectDropdown(currentSubjectId);

        // 3. 先生エリア復元
        teacherSelectionArea.innerHTML = '';
        if (currentTeacherIds.length > 0) {
            currentTeacherIds.forEach(id => addTeacherRow(id));
        } else {
            addTeacherRow();
        }
        
        // 4. 教室エリア復元
        roomSelectionArea.innerHTML = '';
        if (currentRoomIds.length > 0) {
            currentRoomIds.forEach(id => addRoomRow(id));
        } else {
            addRoomRow();
        }

        // 変更を戻すボタン制御
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

/**
 * btnCancel click event
 * 概要: モーダルのキャンセルボタンがクリックされたときの処理。
 * 使用方法: キャンセルボタンのクリックイベントにこの関数をセットしてください。
 * 引数: なし
 */
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

/**
 * btnSave click event
 * 概要: モーダルの保存ボタンがクリックされたときの処理。
 * ※ 裏データとして、セルの data 属性に ID 情報を保存します。
 * 使用方法: 保存ボタンのクリックイベントにこの関数をセットしてください。
 * 引数: なし
 */
btnSave.addEventListener('click', function() {
    if (!currentCell) return;
    
    // IDと名前を取得
    const subjectId = inputClassName.value;
    const subjectName = inputClassName.options[inputClassName.selectedIndex].text;
    const displaySubjectName = subjectId ? subjectName : '';

    const teacherInputs = getTeacherInputs();
    const teacherIds = teacherInputs.map(sel => sel.value).filter(v => v);
    const teacherNames = teacherInputs.filter(sel => sel.value).map(sel => sel.options[sel.selectedIndex].text);

    const roomInputs = getRoomInputs();
    const roomIds = roomInputs.map(sel => sel.value).filter(v => v);
    const roomNames = roomInputs.filter(sel => sel.value).map(sel => sel.options[sel.selectedIndex].text);

    const hasContent = subjectId || teacherIds.length > 0 || roomIds.length > 0;

    if (hasContent) {
        // 表示用HTML生成
        let teacherHtml = teacherNames.map(name => `<div class="teacher-name"><i class="fa-solid fa-user icon"></i><span>${name}</span></div>`).join('');
        let roomHtml = roomNames.map(name => `<div class="room-name"><i class="fa-solid fa-location-dot icon"></i><span>${name}</span></div>`).join('');
        
        currentCell.innerHTML = `
            <div class="class-content">
                <div class="class-name">${displaySubjectName}</div>
                <div class="class-detail">
                    ${teacherHtml}
                    ${roomHtml}
                </div>
            </div>`;
        currentCell.classList.add('is-filled');
        
        // ★IDをデータ属性に保存
        currentCell.dataset.subjectId = subjectId;
        currentCell.dataset.teacherIds = JSON.stringify(teacherIds);
        currentCell.dataset.roomIds = JSON.stringify(roomIds);
        
        // 編集ハイライト（既存編集モード時）
        if (!isCreatingMode && currentRecord) {
            currentCell.classList.add('is-edited');
        }
    } else {
        // 何も入力されていない場合はセルをクリア
        currentCell.innerHTML = '';
        currentCell.classList.remove('is-filled');
        currentCell.classList.remove('is-edited');
        
        // データ属性もクリア
        delete currentCell.dataset.subjectId;
        delete currentCell.dataset.teacherIds;
        delete currentCell.dataset.roomIds;
    }
    // モーダルを閉じる
    editModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    currentCell = null;
});

/*
* 概要: 現在のメイン画面のグリッド（入力済みセル）を配列として収集する。
* 使用方法: 現在の編集内容を取得して保存／比較に使う際に呼び出してください。
*/
/**
 * 画面上のグリッドデータを配列化して取得する
 * (ID取得ロジックを含む完全版)
 */
function getTimetableData() {
const data = [];
    document.querySelectorAll('.timetable-cell.is-filled').forEach(cell => {
        let tIds = [], rIds = [];
        try { if (cell.dataset.teacherIds) tIds = JSON.parse(cell.dataset.teacherIds); } catch (e) {}
        try { if (cell.dataset.roomIds) rIds = JSON.parse(cell.dataset.roomIds); } catch (e) {}

        const item = {
            // 文字列のまま取得
            day: cell.dataset.day,
            period: parseInt(cell.dataset.period),
            className: cell.querySelector('.class-name')?.textContent || '',
            subjectId: cell.dataset.subjectId || null,
            teacherId: tIds.length > 0 ? tIds[0] : null,
            roomId: rIds.length > 0 ? rIds[0] : null,
            teacherName: cell.querySelector('.teacher-name span')?.textContent || '',
            roomName: cell.querySelector('.room-name span')?.textContent || ''
        };
        data.push(item);
    });
    return data;
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

completeButton.addEventListener('click', async () => {
    // ボタンの多重クリック防止などを入れても良いですが、まずはシンプルに実行
    await saveTimetable();
});

cancelCreationBtn.addEventListener('click', () => {
    // ボタンが無効化されている場合は処理しない
    if (cancelCreationBtn.disabled) {
        alert('現在適用期間中の時間割は削除できません。');
        return;
    }
    
    if(isCreatingMode) {
        // ■作成中のキャンセル処理
        if(!confirm('作成中の内容を破棄してキャンセルしますか？')) return;

        // 1. 画面クリア
        document.querySelectorAll('.timetable-cell').forEach(cell => { 
            cell.innerHTML = ''; 
            cell.classList.remove('is-filled'); 
        });
        
        // 2. 変数リセット（IDをnullにしておくと初期化関数が自動で先頭を探してくれます）
        currentCourseName = "システムデザインコース"; // ここは念のため残します
        currentCourseId = null; // ★重要：ID選択状態をリセット
        
        document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
        
        // 3. モードを戻す
        toggleCreatingMode(false);

        // 初期表示に戻す
        selectInitialTimetable();

        setTimeout(() => alert('キャンセルしました。'), 10);
        return;
    }
    
    // ■既存データの「変更破棄」または「削除」
    if (currentRecord) {
        const hasChanges = originalRecordData !== null;
        
        if (hasChanges) {
            // --- 変更破棄のロジック（提示されたコードのまま） ---
            const hasDateChanges = mainStartDate.value !== originalRecordData.startDate || mainEndDate.value !== originalRecordData.endDate;
            const hasGridChanges = getGridDataForComparison(getTimetableData()) !== getGridDataForComparison(originalRecordData.data);

            if (hasDateChanges || hasGridChanges) {
                if(!confirm('変更を破棄しますか？')) return;
                
                // データを元に戻す
                currentRecord.data = JSON.parse(JSON.stringify(originalRecordData.data));
                currentRecord.startDate = originalRecordData.startDate;
                currentRecord.endDate = originalRecordData.endDate;
                
                originalRecordData = {
                    startDate: currentRecord.startDate,
                    endDate: currentRecord.endDate,
                    data: JSON.parse(JSON.stringify(currentRecord.data))
                };
                
                // ボタン状態復元
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
                
                mainStartDate.value = currentRecord.startDate;
                mainEndDate.value = currentRecord.endDate;
                
                const mode = document.querySelector('input[name="displayMode"]:checked').value;
                
                // リスト再描画
                renderSavedList(mode);
                
                // アクティブ状態を復元
                const targetItem = document.querySelector(`.saved-item[data-id="${currentRecord.id}"]`);
                if(targetItem) targetItem.classList.add('active');
                
                // グリッド再描画
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
        
        // --- 削除のロジック ---
        if (isRecordActive(currentRecord)) {
            alert('現在適用期間中の時間割は削除できません。');
            return;
        }
        
        if(!confirm('この時間割を削除しますか？')) return;

        const index = savedTimetables.findIndex(item => item.id === currentRecord.id);
        if (index !== -1) savedTimetables.splice(index, 1);
        
        // 削除処理
        const activeItem = document.querySelector('.saved-item.active');
        if (activeItem) activeItem.remove();
        
        // 変数クリア
        currentRecord = null;
        originalRecordData = null;
        footerArea.classList.remove('show');
        mainStartDate.disabled = false;
        mainEndDate.disabled = false;
        mainStartDate.value = '';
        mainEndDate.value = '';
        
        // 画面クリア
        document.querySelectorAll('.timetable-cell').forEach(cell => { 
            cell.innerHTML = ''; 
            cell.classList.remove('is-filled');
            cell.classList.remove('is-edited');
        });
        document.getElementById('mainCourseDisplay').innerHTML = "（未選択）";
        document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));

        // 初期表示に戻す
        selectInitialTimetable();

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
        const hasGridChanges = getGridDataForComparison(getTimetableData()) !== getGridDataForComparison(originalRecordData.data);

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
            gridData: JSON.parse(JSON.stringify(getTimetableData()))  // 深くコピーしてデータ混在を防ぐ
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

/**
 * saveTimetable
 * 概要:保存処理を実行する関数で、バックエンドのPHPスクリプトにデータを送信する。
 * 使用方法: 保存ボタンのクリックイベントなどで呼び出してください。
 * 引数: なし
 * 返り値: なし
 * ※ 新規時間割り作成モード：id=null  既存時間割り編集モード：id=割り当てられている時間割りID
 */
async function saveTimetable() {
    // 1. バリデーション
    if (!mainStartDate.value || !mainEndDate.value) {
        alert('適用期間を入力してください。');
        return;
    }
    if (mainStartDate.value > mainEndDate.value) {
        alert('開始日が終了日より後になっています。\n適用期間を正しく設定してください。');
        return;
    }

    // 2. 送信データの構築
    const gridData = getTimetableData();
    
    // IDの特定: 新規作成なら null, 編集なら currentRecord.id
    const targetId = (!isCreatingMode && currentRecord) ? currentRecord.id : null;

    const payload = {
        id: targetId,
        course_id: currentCourseId,
        start_date: mainStartDate.value,
        end_date: mainEndDate.value,
        timetable_data: gridData
    };

    // CSRFトークン取得
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = metaToken ? metaToken.getAttribute('content') : '';

    try {
        // 3. PHPへ送信
        // ※パスはファイルの配置場所に合わせて調整してください
        const response = await fetch('../../api/timetable/save_timetable.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        });

        // レスポンスがHTMLで返ってきてしまっている場合のエラーハンドリング
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const text = await response.text();
            console.error("予期せぬレスポンス:", text);
            throw new Error("サーバーエラーが発生しました (Not JSON response)");
        }

        const result = await response.json();

        if (result.success) {
            alert('保存しました。');
            location.reload(); // 成功したらリロードして反映
        } else {
            alert('保存に失敗しました: ' + (result.message || '不明なエラー'));
        }

    } catch (error) {
        console.error('保存エラー:', error);
        alert('通信エラーが発生しました。\nコンソールログを確認してください。');
    }
}

// ページ読み込み時の初期化
/*
 * 概要: DOMContentLoaded（初期化処理）
 */
window.addEventListener('DOMContentLoaded', () => {
    const dropdownItems = document.querySelectorAll('#courseDropdownMenu li');
    const toggleText = document.querySelector('#courseDropdownToggle .current-value');

    dropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const link = this.querySelector('a');
            const selectedText = link ? link.textContent.trim() : this.textContent.trim();
            const selectedId = this.getAttribute('data-value');

            if(toggleText) toggleText.textContent = selectedText;

            // グローバル変数を更新
            currentCourseId = selectedId;
            currentCourseName = selectedText;
            
            console.log(`コースが変更されました: ID=${currentCourseId}, Name=${currentCourseName}`);

            renderSavedList('select'); 
        });
    });

    // 初期データ表示
    selectInitialTimetable();
});