<?php
// create_timetable.php
// 時間割り作成画面

// ----------------------------------------------------
// 0. SecurityHelperの読み込み
// ----------------------------------------------------
// ※配置場所が public/master/ などの2階層目を想定しています
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';

// セキュリティヘッダーを適用（一番最初に実行）
SecurityHelper::applySecureHeaders();

// ----------------------------------------------------
// 1. セッション設定（SSO維持のための設定）
// ----------------------------------------------------
// HomeRepositoryのsession_resettingメソッドを使用してセッション設定を行う
require_once __DIR__ . '/../../../app/classes/repository/home/HomeRepository.php';
HomeRepository::session_resetting();
// ----------------------------------------------------
// 2. ログインチェック（セッション開始含む）
// ----------------------------------------------------
// 未ログインの場合はログイン画面へリダイレクトされます
SecurityHelper::requireLogin();

// ----------------------------------------------------
// 3. ユーザー情報の取得（表示用）
// ----------------------------------------------------
// ※必要に応じて、現在ログインしているユーザー名などを取得する処理をここに追加します
$user_picture = $_SESSION['user_picture'] ?? 'images/default_icon.png';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="nofollow, noindex">
    <title>時間割り作成（作成済み時間割り入り）</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>

<body>
    <header class="app-header">
        <h1>時間割り作成</h1>
        <img class="header_icon" src="./images/calendar-plus.png">
    </header>

    <div class="app-container">
        <div class="main-section">
            <nav class="sidebar" style="z-index: 50;">
                <div class="pt-6 pb-4 border-b border-gray-200">
                    <div id="defaultNewBtnArea">
                        <button id="mainCreateNewBtn" class="sidebar-new-button">
                            <i class="fa-solid fa-plus mr-2"></i>
                            新規作成
                        </button>
                    </div>

                    <div id="creatingItemArea" class="hidden px-4 mt-2 mb-4">
                        <p class="text-xs font-bold text-blue-600 mb-2">現在作成中</p>
                        <div class="bg-white border border-blue-300 rounded-lg overflow-hidden shadow-sm cursor-pointer hover:bg-blue-50 transition-colors" id="creatingItemCard">
                            <div class="flex items-start gap-3 px-3 py-3">
                                <i class="fa-solid fa-pen-to-square text-blue-500 mt-1 flex-shrink-0"></i>
                                <div class="flex flex-col min-w-0">
                                    <span class="truncate font-bold text-sm text-slate-700" id="creatingCourseName"></span>
                                    <span class="text-xs text-slate-500 truncate" id="creatingPeriod"></span>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-center text-slate-400 mt-1">クリックで作成中に戻る</p>
                    </div>
                </div>

                <div class="px-4 py-4 space-y-4">
                    <div>
                        <div class="mb-3">
                            <label class="radio-group-label">
                                <input type="radio" name="displayMode" value="select" checked onchange="changeDisplayMode('select')">
                                <span class="font-bold">選択</span>
                            </label>
                            <div class="ml-6 mt-1 relative">
                                <button id="courseDropdownToggle" class="dropdown-toggle" aria-expanded="false">
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
                                <span>次回以降で反映される時間割り</span>
                            </label>
                        </div>
                    </div>

                    <div id="savedListDivider" class="sidebar-divider hidden"></div>
                    <ul id="savedListContainer" class="hidden">
                        <li class="is-group-label">作成済み時間割</li>
                    </ul>
                </div>
            </nav>

            <main class="main-content">
                <div class="control-area">
                    <div class="period-box flex items-center">
                        <span class="period-label">適用期間</span>
                        <div class="period-inputs">
                            <input type="date" class="date-input" id="mainStartDate">
                            <span class="date-separator">～</span>
                            <input type="date" class="date-input" id="mainEndDate">
                        </div>
                        
                        <button id="resetViewBtn" class="hidden reset-button">
                            表示を解除 (新規入力)
                        </button>
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

                <div id="footerArea" class="footer-button-area" style="display: none;">
                    <button id="cancelCreationBtn" class="delete-button">キャンセル</button>
                    <button id="completeButton" class="complete-button">完了</button>
                </div>
            </main>
        </div>
    </div>

    <div id="classModal" class="modal-overlay hidden">
        <div class="modal-content">
            <h2 id="modalTitle" class="modal-title">○曜日 ○限</h2>
            <div class="modal-form-area">
                <div class="modal-form-item"><label class="modal-label">授業名：</label><div class="modal-select-wrapper"><select id="inputClassName" class="modal-select"><option value="">(選択してください)</option><option value="Javaプログラミング">Javaプログラミング</option><option value="Webデザイン演習">Webデザイン演習</option><option value="データベース基礎">データベース基礎</option><option value="ネットワーク構築">ネットワーク構築</option><option value="セキュリティ概論">セキュリティ概論</option><option value="プロジェクト管理">プロジェクト管理</option><option value="キャリアデザイン">キャリアデザイン</option><option value="HR">HR</option></select><div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div></div></div>
                <div class="modal-form-item"><label class="modal-label">担当先生：</label><div class="modal-select-wrapper"><select id="inputTeacherName" class="modal-select"><option value="">(選択してください)</option><option value="佐藤 健一">佐藤 健一</option><option value="鈴木 花子">鈴木 花子</option><option value="高橋 誠">高橋 誠</option><option value="田中 優子">田中 優子</option><option value="渡辺 剛">渡辺 剛</option><option value="伊藤 直人">伊藤 直人</option><option value="山本 さくら">山本 さくら</option></select><div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div></div></div>
                <div class="modal-form-item"><label class="modal-label">教室場所：</label><div class="modal-select-wrapper"><select id="inputRoomName" class="modal-select"><option value="">(選択してください)</option><option value="201教室">201教室</option><option value="202教室">202教室</option><option value="301演習室">301演習室</option><option value="302演習室">302演習室</option><option value="4F大講義室">4F大講義室</option><option value="別館Lab A">別館Lab A</option><option value="別館Lab B">別館Lab B</option></select><div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div></div></div>
            </div>
            <div class="modal-button-area">
                <button id="btnCancel" class="modal-cancel-button">キャンセル</button>
                <button id="btnSave" class="modal-save-button">保存</button>
            </div>
        </div>
    </div>

    <div id="createModal" class="modal-overlay hidden">
        <div class="create-modal-content">
            <h2 class="text-xl font-bold text-slate-800 mb-6 border-b pb-2 border-slate-300">新規作成</h2>
            
            <p id="createPeriodDisplay" class="text-slate-600 mb-6 font-medium text-center bg-blue-50 py-2 rounded"></p>

            <div class="create-form-group">
                <label class="create-form-label">学年 <span class="text-red-500 text-xs ml-1">必須</span></label>
                <div class="modal-select-wrapper">
                    <select id="createGradeSelect" class="modal-select">
                        <option value="">選択してください</option>
                        <option value="1">1年生</option>
                        <option value="2">2年生</option>
                        <option value="all">全体</option>
                    </select>
                    <div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                </div>
            </div>

            <div class="create-form-group">
                <label class="create-form-label">コース <span class="text-red-500 text-xs ml-1">必須</span></label>
                <div class="modal-select-wrapper">
                    <select id="createCourseSelect" class="modal-select" disabled>
                        <option value="">先に学年を選択してください</option>
                    </select>
                    <div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                </div>
            </div>

            <div class="checkbox-wrapper">
                <label class="checkbox-label">
                    <input type="checkbox" id="checkTestMode">
                    テスト時間割りを作成する
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" id="checkCsv">
                    CSVファイルを挿入する
                </label>
                <div id="csvInputArea" class="file-input-area">
                    <input type="file" id="csvFile" class="file-input" accept=".csv">
                </div>
            </div>

            <div class="modal-button-area">
                <button id="createCancelBtn" class="modal-cancel-button">キャンセル</button>
                <button id="createSubmitBtn" class="modal-save-button" disabled>作成開始</button>
            </div>
        </div>
    </div>

    <script>
        let savedTimetables = [];
        let isCreatingMode = false;
        let isViewOnly = false;
        let currentRecord = null;
        let tempCreatingData = null;
        let originalRecordData = null; // 編集前のオリジナルデータ
        
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
                "システムデザインコース",
                "Webクリエイタコース", 
                "マルチメディアOAコース",
                "応用情報コース",
                "基本情報コース",
                "ITパスポートコース",
                "１年１組",
                "１年２組"
            ];
            
            for (const courseName of priorityCourses) {
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
            if (currentRecord && originalRecordData) {
                if (!confirm('編集中の内容は保存されていません。\n新規作成を開始しますか？')) {
                    return;
                }
            }
            
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
            footerArea.style.display = 'none';
            
            // 適用期間フィールドを有効化
            mainStartDate.disabled = false;
            mainEndDate.disabled = false;
            
            // グリッドをクリア
            document.querySelectorAll('.timetable-cell').forEach(cell => {
                cell.innerHTML = '';
                cell.classList.remove('is-filled');
            });
            document.getElementById('mainCourseDisplay').innerHTML = "（未選択）";
            
            // モーダルに適用期間を表示
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
            
            // 作成モーダルをキャンセルした場合、何も選択していない状態に戻す
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
                
                // 作成モード終了時は期間フィールドを有効化
                mainStartDate.disabled = false;
                mainEndDate.disabled = false;
                mainStartDate.value = '';
                mainEndDate.value = '';
                
                // グリッドもクリア
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
            });

            createModal.classList.add('hidden');
            document.body.classList.remove('modal-open');
            
            setTimeout(() => alert(`「${selectedCourse}」の作成を開始します。`), 10);
        });

        function changeDisplayMode(mode) {
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
                    alert('編集するには「作成中」エリアをクリックして作成中の時間割に戻ってください。');
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
            const c = inputClassName.value;
            const t = inputTeacherName.value;
            const r = inputRoomName.value;

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
            } else {
                currentCell.innerHTML = '';
                currentCell.classList.remove('is-filled');
            }
            
            // 編集があった場合、キャンセルボタンのラベルを変更
            if (!isCreatingMode && currentRecord && originalRecordData) {
                const hasGridChanges = JSON.stringify(getCurrentGridData()) !== JSON.stringify(originalRecordData.data);
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

        // グリッドデータを取得するヘルパー関数
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
                originalRecordData = null;
                
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
                    const hasGridChanges = JSON.stringify(getCurrentGridData()) !== JSON.stringify(originalRecordData.data);
                    
                    if (hasDateChanges || hasGridChanges) {
                        // 変更がある場合は破棄確認
                        if(!confirm('変更を破棄しますか？')) return;
                        
                        // オリジナルデータに戻す
                        currentRecord.data = JSON.parse(JSON.stringify(originalRecordData.data));
                        currentRecord.startDate = originalRecordData.startDate;
                        currentRecord.endDate = originalRecordData.endDate;
                        originalRecordData = null;
                        
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
                    footerArea.style.display = 'none';
                    mainStartDate.disabled = true;
                    mainEndDate.disabled = true;
                } else {
                    // 通常の編集（期間も編集可能）
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
    </script>
</body>
</html>