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
// teacher_home.php と同様の設定を適用し、セッション切れを防ぎます
$session_duration = 604800; // 7日間 (秒単位: 7 * 24 * 60 * 60)

// サーバー側GCの有効期限を設定
ini_set('session.gc_maxlifetime', $session_duration);

// クライアント側（ブラウザ）のCookie有効期限を設定
session_set_cookie_params([
    'lifetime' => $session_duration,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // HTTPSならtrue
    'httponly' => true,
    'samesite' => 'Lax'
]);

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
    <title>時間割り作成</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

            <!-- サイドバー -->
            <nav class="sidebar" style="z-index: 50;">
                <!-- 新規作成ボタン -->
                <div class="pt-6 pb-4 border-b border-gray-200">
                    <button id="mainCreateNewBtn" class="sidebar-new-button">
                        <i class="fa-solid fa-plus mr-2"></i>
                        新規作成
                    </button>
                    <p class="text-xs text-center text-slate-500 mt-2">※適用期間を入力してください</p>
                </div>

                <!-- 作成済み時間割エリア -->
                <div class="px-4 py-4 space-y-4">
                    <!-- ラジオボタン群 -->
                    <div>
                        <div class="mb-3">
                            <label class="radio-group-label">
                                <input type="radio" name="displayMode" value="select" checked onchange="changeDisplayMode('select')">
                                <span class="font-bold">選択</span>
                            </label>
                            <!-- コース選択ドロップダウン -->
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
                                <span>次の期間で反映される時間割り</span>
                            </label>
                        </div>
                    </div>

                    <div id="savedListDivider" class="sidebar-divider hidden"></div>
                    <ul id="savedListContainer" class="hidden">
                        <li class="is-group-label">作成済み時間割</li>
                    </ul>
                </div>
            </nav>

            <!-- メインコンテンツ -->
            <main class="main-content">
                <div class="control-area">
                    <div class="period-box">
                        <span class="period-label">適用期間</span>
                        <div class="period-inputs">
                            <input type="date" class="date-input" id="mainStartDate">
                            <span class="date-separator">～</span>
                            <input type="date" class="date-input" id="mainEndDate">
                        </div>
                    </div>

                    <!-- コース表示エリア -->
                    <div class="course-display">
                        <h2 id="mainCourseDisplay" class="text-2xl font-bold text-slate-700 border-b-2 border-blue-200 px-2 inline-block">
                            システムデザインコース
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
                                <!-- 1-5限のループ -->
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

                <div class="footer-button-area">
                    <button id="deleteButton" class="delete-button">削除</button>
                    <button id="completeButton" class="complete-button">完了</button>
                </div>
            </main>
        </div>
    </div>

    <!-- 授業編集モーダル -->
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

    <!-- 新規作成モーダル -->
    <div id="createModal" class="modal-overlay hidden">
        <div class="create-modal-content">
            <h2 class="text-xl font-bold text-slate-800 mb-6 border-b pb-2 border-slate-300">新規作成</h2>
            
            <p id="createPeriodDisplay" class="text-slate-600 mb-6 font-medium text-center bg-blue-50 py-2 rounded">
                <!-- JSで期間を表示 -->
            </p>

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
        // --- 共通変数 ---
        let savedTimetables = [];
        
        // --- 要素取得 ---
        const mainCreateNewBtn = document.getElementById('mainCreateNewBtn');
        const mainStartDate = document.getElementById('mainStartDate');
        const mainEndDate = document.getElementById('mainEndDate');
        
        const createModal = document.getElementById('createModal');
        const createPeriodDisplay = document.getElementById('createPeriodDisplay');
        const createGradeSelect = document.getElementById('createGradeSelect');
        const createCourseSelect = document.getElementById('createCourseSelect');
        const checkCsv = document.getElementById('checkCsv');
        const csvInputArea = document.getElementById('csvInputArea');
        const createSubmitBtn = document.getElementById('createSubmitBtn');
        const createCancelBtn = document.getElementById('createCancelBtn');

        // --- データ定義 ---
        const courseData = {
            "1": ["１年１組", "１年２組", "基本情報コース", "応用情報コース"],
            "2": ["システムデザインコース", "Webクリエイタコース", "マルチメディアOAコース"],
            "all": ["１年１組", "１年２組", "基本情報コース", "応用情報コース", "システムデザインコース", "Webクリエイタコース", "マルチメディアOAコース"]
        };

        // ==========================
        //  メイン画面ロジック
        // ==========================

        // 日付入力監視 -> 新規作成ボタン有効化
        function checkMainDateInputs() {
            mainCreateNewBtn.disabled = !(mainStartDate.value && mainEndDate.value);
        }
        mainStartDate.addEventListener('input', checkMainDateInputs);
        mainEndDate.addEventListener('input', checkMainDateInputs);

        // 新規作成ボタンクリック -> モーダル表示
        mainCreateNewBtn.addEventListener('click', () => {
            if (mainCreateNewBtn.disabled) return;
            
            // モーダル表示
            createPeriodDisplay.textContent = `適用期間: ${mainStartDate.value} ～ ${mainEndDate.value}`;
            createModal.classList.remove('hidden');
            document.body.classList.add('modal-open');
            
            // 初期化
            createGradeSelect.value = "";
            createCourseSelect.innerHTML = '<option value="">先に学年を選択してください</option>';
            createCourseSelect.disabled = true;
            checkCsv.checked = false;
            csvInputArea.classList.remove('active');
            checkCreateValidation();
        });

        // モーダル閉じる (キャンセル)
        createCancelBtn.addEventListener('click', () => {
            createModal.classList.add('hidden');
            document.body.classList.remove('modal-open');
        });

        // ==========================
        //  新規作成モーダルロジック
        // ==========================
        
        // 学年選択 -> コース絞り込み
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

        // バリデーションチェック
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

        // 作成開始ボタンクリック -> 処理 & モーダル閉じる
        createSubmitBtn.addEventListener('click', () => {
            const selectedCourse = createCourseSelect.value;
            const isTest = document.getElementById('checkTestMode').checked;
            
            // --- ★追加: 重複チェック ---
            const sDate = mainStartDate.value;
            const eDate = mainEndDate.value;
            
            const hasOverlap = savedTimetables.some(record => {
                if (record.course !== selectedCourse) return false;
                const recEnd = record.endDate || record.startDate;
                return (sDate <= recEnd) && (eDate >= record.startDate);
            });

            if (hasOverlap) {
                alert('指定された適用期間は、同じコースの既存の時間割と重複しています。\n別の期間を指定するか、既存の時間割を削除してください。');
                return;  //処理中断
            }
            // ---------------------------

            // メイン画面反映（コース名）
            const toggle = document.querySelector('#courseDropdownToggle .current-value');
            toggle.textContent = selectedCourse;
            document.getElementById('mainCourseDisplay').textContent = selectedCourse;
            
            // モードを「選択」に強制切り替え（編集可能にするため）
            const selectRadio = document.querySelector('input[value="select"]');
            if (!selectRadio.checked) {
                selectRadio.checked = true;
                changeDisplayMode('select'); 
            } else {
                renderSavedList('select');
            }

            // テーブルクリア（新規作成なので）
            document.querySelectorAll('.timetable-cell').forEach(cell => {
                cell.innerHTML = '';
                cell.classList.remove('is-filled');
            });
            
            // 保存リストの選択状態解除
            document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));

            // モーダルを閉じる
            createModal.classList.add('hidden');
            document.body.classList.remove('modal-open');
            
            let msg = `「${selectedCourse}」の作成を開始します。`;
            if (isTest) msg += "\n(テスト時間割モード)";
            
            setTimeout(() => alert(msg), 10);
        });


        // ==========================
        //  既存ロジック
        // ==========================

        let courseToggle = document.getElementById('courseDropdownToggle');

        function changeDisplayMode(mode) {
            const toggleBtn = document.getElementById('courseDropdownToggle');
            if (mode === 'select') {
                toggleBtn.classList.remove('disabled');
            } else {
                toggleBtn.classList.add('disabled');
            }
            renderSavedList(mode);
        }

        function renderSavedList(mode) {
            const container = document.getElementById('savedListContainer');
            const divider = document.getElementById('savedListDivider');
            const items = container.querySelectorAll('li:not(.is-group-label)');
            items.forEach(item => item.remove());

            const todayStr = new Date().toISOString().split('T')[0];
            const currentCourse = document.querySelector('#courseDropdownToggle .current-value').textContent;
            let filteredRecords = savedTimetables.filter(item => item.course === currentCourse);

            if (mode === 'current') {
                filteredRecords = filteredRecords.filter(item => {
                    if (!item.startDate) return false;
                    return item.startDate <= todayStr && (!item.endDate || item.endDate >= todayStr);
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
                // データなし
            }

            filteredRecords.forEach(record => {
                const dateLabel = getFormattedDate(record.startDate);
                const newItem = document.createElement('li');
                newItem.className = 'nav-item saved-item';
                newItem.setAttribute('data-id', record.id);
                newItem.innerHTML = `<a href="#"><i class="fa-regular fa-file-lines text-blue-500"></i><span>${record.course}</span><span class="saved-meta">${dateLabel}</span></a>`;
                newItem.addEventListener('click', handleSavedItemClick);
                container.appendChild(newItem);
            });
        }

        function getFormattedDate(inputVal) {
            if (!inputVal) return '';
            const parts = inputVal.split('-');
            if (parts.length !== 3) return '';
            return `${parts[1]}/${parts[2]}~`;
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
        
        setupDropdown('courseDropdownToggle', 'courseDropdownMenu', refreshTimetableDisplay);

        document.addEventListener('click', (e) => {
            // モーダル内クリックなら閉じない
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

        // 授業編集モーダル
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
                if (!document.querySelector('input[value="select"]').checked) {
                    alert('編集は「選択」モードで行ってください。');
                    return;
                }
                currentCell = this;
                const day = this.dataset.day;
                const period = this.dataset.period;
                modalTitle.textContent = `${day}曜日 ${period}限`;
                
                const setVal = (sel, val) => {
                    let found = false;
                    for(let i=0; i<sel.options.length; i++) { if(sel.options[i].value === val) { sel.selectedIndex = i; found = true; break; } }
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
            editModal.classList.add('hidden');
            document.body.classList.remove('modal-open');
            currentCell = null;
        });

        // 完了・削除ボタン
        const completeButton = document.getElementById('completeButton');
        const deleteButton = document.getElementById('deleteButton');
        const currentCourseEl = document.querySelector('#courseDropdownToggle .current-value');

        function refreshTimetableDisplay() {
            const mode = document.querySelector('input[name="displayMode"]:checked').value;
            if (mode !== 'select') return;
            const currentCourse = document.querySelector('#courseDropdownToggle .current-value').textContent;
            const displayEl = document.getElementById('mainCourseDisplay');
            if(displayEl) displayEl.textContent = currentCourse;
            renderSavedList('select');
            const record = savedTimetables.slice().reverse().find(item => item.course === currentCourse);
            document.querySelectorAll('.timetable-cell').forEach(cell => {
                cell.innerHTML = ''; cell.classList.remove('is-filled');
            });
            document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
            if (record) {
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
                const listItem = document.querySelector(`.saved-item[data-id="${record.id}"]`);
                if (listItem) listItem.classList.add('active');
            }
        }

        completeButton.addEventListener('click', () => {
            if (!document.querySelector('input[value="select"]').checked) { alert('「選択」モードでのみ保存可能です。'); return; }
            if (!mainStartDate.value) { alert('適用期間を入力してください。'); return; }
            
            const courseText = courseToggle.querySelector('.current-value').textContent;
            const currentGridData = [];
            document.querySelectorAll('.timetable-cell.is-filled').forEach(cell => {
                currentGridData.push({
                    day: cell.dataset.day, period: cell.dataset.period,
                    className: cell.querySelector('.class-name')?.textContent || '',
                    teacherName: cell.querySelector('.teacher-name span')?.textContent || '',
                    roomName: cell.querySelector('.room-name span')?.textContent || ''
                });
            });

            const newRecord = {
                id: Date.now(), course: courseText,
                startDate: mainStartDate.value, endDate: mainEndDate.value,
                data: currentGridData
            };
            savedTimetables.push(newRecord);
            renderSavedList(document.querySelector('input[name="displayMode"]:checked').value);
            
            // 今保存したものをアクティブに
            setTimeout(() => {
                const item = document.querySelector(`.saved-item[data-id="${newRecord.id}"]`);
                if(item) item.classList.add('active');
            }, 0);

            alert('保存しました。');
        });

        // 削除ボタン
        deleteButton.addEventListener('click', () => {
            if (!document.querySelector('input[value="select"]').checked) { alert('削除は「選択」モードで行ってください。'); return; }
            const activeItem = document.querySelector('.saved-item.active');
            if (!activeItem) { alert('削除する時間割を選択してください。'); return; }

            const id = parseInt(activeItem.getAttribute('data-id'));
            const record = savedTimetables.find(item => item.id === id);
            
            if (record) {
                const todayStr = new Date().toISOString().split('T')[0];
                const isStarted = record.startDate <= todayStr;
                const isNotEnded = !record.endDate || record.endDate >= todayStr;

                if (isStarted && isNotEnded) {
                    alert('現在適用されている期間の時間割は削除できません。');
                    return;
                }
            }

            if(!confirm('本当に削除しますか？')) return;

            const index = savedTimetables.findIndex(item => item.id === id);
            if (index !== -1) savedTimetables.splice(index, 1);

            activeItem.remove();
            
            const currentMode = document.querySelector('input[name="displayMode"]:checked').value;
            renderSavedList(currentMode);
            
            document.querySelectorAll('.timetable-cell').forEach(cell => { cell.innerHTML = ''; cell.classList.remove('is-filled'); });
            document.getElementById('mainCourseDisplay').textContent = "（未選択）";
            
            setTimeout(() => alert('削除しました。'), 10);
        });

        function handleSavedItemClick(e) {
            e.preventDefault();
            const id = parseInt(e.currentTarget.getAttribute('data-id'));
            const selectRadio = document.querySelector('input[value="select"]');
            if(!selectRadio.checked) { selectRadio.checked = true; changeDisplayMode('select'); }
            
            document.querySelectorAll('.saved-item').forEach(el => el.classList.remove('active'));
            const targetItem = document.querySelector(`.saved-item[data-id="${id}"]`);
            if(targetItem) targetItem.classList.add('active');

            const record = savedTimetables.find(item => item.id === id);
            if (record) {
                const toggle = document.querySelector('#courseDropdownToggle .current-value');
                toggle.textContent = record.course;
                document.getElementById('mainCourseDisplay').textContent = record.course;
                mainStartDate.value = record.startDate;
                mainEndDate.value = record.endDate;
                
                document.querySelectorAll('.timetable-cell').forEach(cell => { cell.innerHTML = ''; cell.classList.remove('is-filled'); });
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
    </script>
</body>
</html>