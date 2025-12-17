<?php
// 必要なファイルの読み込み
// 現在の edit_timetable.php (public/teacher/timetable_change/) からの相対パス
require_once '../../../app/classes/security/SecurityHelper.php';

// セキュリティヘッダーの適用（X-Frame-Options, X-XSS-Protectionなど）
SecurityHelper::applySecureHeaders();

// セッションの開始（もしログインチェック等も行うならここで実施）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="nofollow,noindex">
    <title>授業変更</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="css/reset.css">
</head>
<body>
    <!-- ヘッダー -->
    <header class="app-header">
        <h1>授業変更</h1>
        <i class="fa-regular fa-pen-to-square user_icon"></i>
    </header>

    <div class="app-container">
        <div class="main-section">
            <!-- サイドバー -->
            <nav class="sidebar" style="z-index: 50;">
                <div class="px-4 py-4 space-y-4">
                    <div>
                        <div class="mb-3">
                            <label class="radio-group-label">
                                <!-- ラジオボタン1（コース選択） -->
                                <input type="radio" name="displayMode" value="select" checked onchange="changeDisplayMode('select')">
                                <span class="font-bold">選択</span>
                            </label>
                            
                            <div class="ml-6 mt-1 relative">
                                <button id="courseDropdownToggle" class="dropdown-toggle" aria-expanded="false">
                                    <span class="current-value">システムデザインコース</span>
                                </button>
                                <!-- コースドロップダウンメニュー -->
                                <ul id="courseDropdownMenu" class="dropdown-menu">
                                    <li><a href="#">システムデザインコース</a></li>
                                    <li><a href="#">Webクリエイタコース</a></li>
                                    <li><a href="#">マルチメディアOAコース</a></li>
                                    <li><a href="#">応用情報コース</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="radio-group-label">
                                <!-- ラジオボタン2（全コースの表示）-->
                                <input type="radio" name="displayMode" value="all" onchange="changeDisplayMode('all')">
                                <span>すべてのコース</span>
                            </label>
                        </div>
                    </div>
                    <div id="savedListDivider" class="sidebar-divider"></div>
                    <ul id="savedListContainer">
                        <li class="is-group-label">作成済み時間割</li>
                        <!-- JSでリスト項目を追加 -->
                    </ul>
                </div>
            </nav>

            <!-- メインコンテンツ -->
            <main class="main-content">
                <div class="control-area">
                    <div class="week-navigator">
                        <button id="prevWeekBtn" class="week-nav-btn"><i class="fa-solid fa-chevron-left"></i></button>
                        <span id="weekDisplay" class="week-display">（時間割を選択してください）</span>
                        <button id="nextWeekBtn" class="week-nav-btn"><i class="fa-solid fa-chevron-right"></i></button>
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
                                    <th class="day-header" id="th-mon">月<span class="day-date-label"></span></th>
                                    <th class="day-header" id="th-tue">火<span class="day-date-label"></span></th>
                                    <th class="day-header" id="th-wed">水<span class="day-date-label"></span></th>
                                    <th class="day-header" id="th-thu">木<span class="day-date-label"></span></th>
                                    <th class="day-header" id="th-fri">金<span class="day-date-label"></span></th>
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

                <div class="footer-button-area">
                    <button id="saveChangesBtn" class="action-button save-changes-button">変更を保存</button>
                </div>
            </main>
        </div>
    </div>

    <!-- 変更用モーダル -->
    <div id="changeModal" class="modal-overlay hidden">
        <div class="modal-content">
            <h2 id="modalTitle" class="modal-title">授業変更</h2>
            <div class="modal-form-area">
                <div class="modal-form-item">
                    <label class="modal-label">変更日：</label>
                    <div class="modal-select-wrapper">
                        <input type="date" id="targetDateInput" class="modal-date-input" readonly>
                    </div>
                </div>
                <div class="modal-form-item">
                    <label class="modal-label">授業名：</label>
                    <div class="modal-select-wrapper">
                        <select id="inputClassName" class="modal-select">
                            <option value="">(選択してください)</option>
                            <option value="休講/空き">休講/空き</option>
                            <option value="Javaプログラミング">Javaプログラミング</option>
                            <option value="Webデザイン演習">Webデザイン演習</option>
                            <option value="データベース基礎">データベース基礎</option>
                            <option value="ネットワーク構築">ネットワーク構築</option>
                            <option value="セキュリティ概論">セキュリティ概論</option>
                            <option value="プロジェクト管理">プロジェクト管理</option>
                            <option value="キャリアデザイン">キャリアデザイン</option>
                            <option value="HR">HR</option>
                        </select>
                        <div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                    </div>
                </div>
                <div class="modal-form-item">
                    <label class="modal-label">担当先生：</label>
                    <div class="modal-select-wrapper">
                        <select id="inputTeacherName" class="modal-select">
                            <option value="">(選択してください)</option>
                            <option value="佐藤 健一">佐藤 健一</option>
                            <option value="鈴木 花子">鈴木 花子</option>
                            <option value="高橋 誠">高橋 誠</option>
                            <option value="田中 優子">田中 優子</option>
                            <option value="渡辺 剛">渡辺 剛</option>
                            <option value="伊藤 直人">伊藤 直人</option>
                            <option value="山本 さくら">山本 さくら</option>
                        </select>
                        <div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                    </div>
                </div>
                <div class="modal-form-item">
                    <label class="modal-label">教室場所：</label>
                    <div class="modal-select-wrapper">
                        <select id="inputRoomName" class="modal-select">
                            <option value="">(選択してください)</option>
                            <option value="201教室">201教室</option>
                            <option value="202教室">202教室</option>
                            <option value="301演習室">301演習室</option>
                            <option value="302演習室">302演習室</option>
                            <option value="4F大講義室">4F大講義室</option>
                            <option value="別館Lab A">別館Lab A</option>
                            <option value="別館Lab B">別館Lab B</option>
                        </select>
                        <div class="select-arrow"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                    </div>
                </div>
                <div id="validationErrors"></div>
            </div>
            <div class="modal-button-area">
                <button id="btnModalUndo" class="modal-undo-button" disabled>
                    <i class="fa-solid fa-rotate-left"></i>
                    変更を取り消し
                </button>
                <div style="display: flex; gap: 1rem;">
                    <button id="btnCancel" class="modal-cancel-button">キャンセル</button>
                    <button id="btnUpdate" class="modal-save-button">変更反映</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ==================== データ管理 ====================
        // データをデータベースから取得して、データ管理に格納する。
        // それに伴って、データベースに接続して、該当するデータを取得するためのphpファイルを作成する必要がある。
        // データ取得php
        // コースごとに、現在と次回反映予定の時間割を取得する。
        // Json形式でデータを格納していく
        // 変更予定の時間割を、振り分けたIDごとに用意しているchanges属性に格納し、データベーステーブルに格納していく。

        let savedTimetables = [
            {
                id: 1,
                course: "システムデザインコース",
                startDate: "2025-04-01",
                endDate: "2025-09-30",
                data: [
                    { day: "月", period: "1", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "201教室" },
                    { day: "月", period: "2", className: "データベース基礎", teacherName: "高橋 誠", roomName: "202教室" },
                    { day: "月", period: "3", className: "ネットワーク構築", teacherName: "渡辺 剛", roomName: "301演習室" },
                    { day: "月", period: "4", className: "プロジェクト管理", teacherName: "田中 優子", roomName: "4F大講義室" },
                    { day: "火", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" },
                    { day: "火", period: "2", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "201教室" },
                    { day: "火", period: "3", className: "セキュリティ概論", teacherName: "伊藤 直人", roomName: "202教室" },
                    { day: "火", period: "4", className: "データベース基礎", teacherName: "高橋 誠", roomName: "302演習室" },
                    { day: "水", period: "1", className: "ネットワーク構築", teacherName: "渡辺 剛", roomName: "別館Lab A" },
                    { day: "水", period: "2", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "201教室" },
                    { day: "水", period: "3", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" },
                    { day: "水", period: "4", className: "キャリアデザイン", teacherName: "山本 さくら", roomName: "4F大講義室" },
                    { day: "木", period: "1", className: "データベース基礎", teacherName: "高橋 誠", roomName: "202教室" },
                    { day: "木", period: "2", className: "セキュリティ概論", teacherName: "伊藤 直人", roomName: "別館Lab B" },
                    { day: "木", period: "3", className: "プロジェクト管理", teacherName: "田中 優子", roomName: "4F大講義室" },
                    { day: "木", period: "4", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "201教室" },
                    { day: "金", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" },
                    { day: "金", period: "2", className: "ネットワーク構築", teacherName: "渡辺 剛", roomName: "別館Lab A" },
                    { day: "金", period: "3", className: "データベース基礎", teacherName: "高橋 誠", roomName: "202教室" },
                    { day: "金", period: "4", className: "HR", teacherName: "田中 優子", roomName: "4F大講義室" }
                ],
                changes: [
                    { date: "2025-04-07", day: "月", period: "2", className: "", teacherName: "", roomName: "" },
                    { date: "2025-04-15", day: "火", period: "3", className: "プロジェクト管理", teacherName: "田中 優子", roomName: "4F大講義室" }
                ]
            },
            {
                id: 2,
                course: "システムデザインコース",
                startDate: "2025-10-01",
                endDate: "2026-03-31",
                data: [
                    { day: "月", period: "1", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "201教室" },
                    { day: "月", period: "2", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" },
                    { day: "火", period: "1", className: "データベース基礎", teacherName: "高橋 誠", roomName: "202教室" },
                    { day: "火", period: "2", className: "ネットワーク構築", teacherName: "渡辺 剛", roomName: "別館Lab A" },
                    { day: "水", period: "1", className: "セキュリティ概論", teacherName: "伊藤 直人", roomName: "別館Lab B" },
                    { day: "木", period: "1", className: "プロジェクト管理", teacherName: "田中 優子", roomName: "4F大講義室" },
                    { day: "金", period: "1", className: "キャリアデザイン", teacherName: "山本 さくら", roomName: "4F大講義室" }
                ],
                changes: []
            },
            {
                id: 3,
                course: "Webクリエイタコース",
                startDate: "2025-04-01",
                endDate: "2025-09-30",
                data: [
                    { day: "月", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" },
                    { day: "月", period: "2", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "201教室" },
                    { day: "月", period: "3", className: "データベース基礎", teacherName: "高橋 誠", roomName: "202教室" },
                    { day: "火", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" },
                    { day: "火", period: "2", className: "プロジェクト管理", teacherName: "田中 優子", roomName: "4F大講義室" },
                    { day: "水", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" },
                    { day: "水", period: "2", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "201教室" },
                    { day: "木", period: "1", className: "キャリアデザイン", teacherName: "山本 さくら", roomName: "4F大講義室" },
                    { day: "木", period: "2", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" },
                    { day: "金", period: "1", className: "データベース基礎", teacherName: "高橋 誠", roomName: "202教室" },
                    { day: "金", period: "2", className: "HR", teacherName: "鈴木 花子", roomName: "301演習室" }
                ],
                changes: []
            },
            {
                id: 4,
                course: "マルチメディアOAコース",
                startDate: "2025-04-01",                endDate: "2026-03-31",
                data: [
                    { day: "月", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" },
                    { day: "月", period: "2", className: "データベース基礎", teacherName: "高橋 誠", roomName: "202教室" },
                    { day: "火", period: "1", className: "Javaプログラミング", teacherName: "佐藤 健一", roomName: "201教室" },
                    { day: "火", period: "2", className: "プロジェクト管理", teacherName: "田中 優子", roomName: "4F大講義室" },
                    { day: "水", period: "1", className: "ネットワーク構築", teacherName: "渡辺 剛", roomName: "別館Lab A" },
                    { day: "木", period: "1", className: "Webデザイン演習", teacherName: "鈴木 花子", roomName: "301演習室" },
                    { day: "金", period: "1", className: "キャリアデザイン", teacherName: "山本 さくら", roomName: "4F大講義室" }
                ],
                changes: [
                    { date: "2025-05-12", day: "月", period: "1", className: "プロジェクト管理", teacherName: "田中 優子", roomName: "4F大講義室" }
                ]
            }
        ];

        let currentRecord = null;
        let currentWeekStart = null;
        let editingCell = null;

        // ==================== ユーティリティ関数 ====================
        
        function formatDate(date) {
            const y = date.getFullYear();
            const m = ('0' + (date.getMonth() + 1)).slice(-2);
            const d = ('0' + date.getDate()).slice(-2);
            return `${y}-${m}-${d}`;
        }
        
        function normalizeDate(date) {
            const d = new Date(date);
            d.setHours(0, 0, 0, 0);
            return d;
        }

        function setSelectValue(select, value) {
            for(let i = 0; i < select.options.length; i++) {
                if(select.options[i].value === value) {
                    select.selectedIndex = i;
                    return;
                }
            }
            select.value = "";
        }
        
        function clearAllCells() {
            document.querySelectorAll('.timetable-cell').forEach(cell => {
                cell.innerHTML = '';
                cell.className = 'timetable-cell';
                cell.removeAttribute('data-date');
            });
        }

        function updateCellContent(cell, className, teacherName, roomName, isChanged = false) {
            if (!className && !teacherName && !roomName) {
                // 休講または空き
                if (isChanged) {
                    // 休講への変更：通常スタイルで表示（赤字削除）
                    cell.innerHTML = `<div class="class-content"><div class="class-name">（休講）</div><div class="class-detail"></div></div>`;
                } else {
                    cell.innerHTML = ``;
                }
            } else {
                const teacherHTML = teacherName ? `<div class="teacher-name"><i class="fa-solid fa-user icon"></i><span>${teacherName}</span></div>` : '';
                const roomHTML = roomName ? `<div class="room-name"><i class="fa-solid fa-location-dot icon"></i><span>${roomName}</span></div>` : '';
                cell.innerHTML = `<div class="class-content"><div class="class-name">${className}</div><div class="class-detail">${teacherHTML}${roomHTML}</div></div>`;
            }

            if (isChanged) {
                cell.classList.add('is-changed');
                cell.classList.remove('is-filled');
            } else {
                cell.classList.remove('is-changed');
                if (className) {
                    cell.classList.add('is-filled');
                } else {
                    cell.classList.remove('is-filled');
                }
            }
        }

        // ==================== バリデーション ====================
        
        function validateChange(date, period, className, teacherName, roomName) {
            const errors = [];
            if (!currentRecord) {
                errors.push('時間割が選択されていません');
                return errors;
            }
            const changeDate = normalizeDate(new Date(date));
            const startDate = normalizeDate(new Date(currentRecord.startDate));
            const endDate = normalizeDate(new Date(currentRecord.endDate));
            if (changeDate < startDate || changeDate > endDate) {
                errors.push(`変更日は ${formatDate(startDate)} 〜 ${formatDate(endDate)} の範囲内である必要があります`);
            }
            if (className) {
                if (!teacherName) errors.push('授業名を入力した場合は担当先生を選択してください');
                if (!roomName) errors.push('授業名を入力した場合は教室場所を選択してください');
            }
            return errors;
        }

        function displayValidationErrors(errors) {
            const container = document.getElementById('validationErrors');
            if (errors.length === 0) {
                container.innerHTML = '';
                return;
            }
            container.innerHTML = errors.map(err => 
                `<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i>${err}</div>`
            ).join('');
        }

        // ==================== 初期化・イベントリスナー ====================
        
        window.addEventListener('DOMContentLoaded', () => {
            setupEventListeners();
            // 初期状態はサイドバーをレンダリングし、何も選択されていない状態
        });

        function setupEventListeners() {
            // 離脱防止アラート
            window.addEventListener('beforeunload', (e) => {
                e.preventDefault();
                e.returnValue = '';
            });

            // 週ナビゲーション
            document.getElementById('prevWeekBtn').addEventListener('click', () => {
                if(!currentWeekStart) return;
                currentWeekStart.setDate(currentWeekStart.getDate() - 7);
                updateWeekDisplay();
            });
            document.getElementById('nextWeekBtn').addEventListener('click', () => {
                if(!currentWeekStart) return;
                currentWeekStart.setDate(currentWeekStart.getDate() + 7);
                updateWeekDisplay();
            });
            
            // セルクリック（後ほどrenderTableでも再設定されるが、初期ロード用）
            document.querySelectorAll('.timetable-cell').forEach(cell => {
                cell.addEventListener('click', handleCellClick);
            });
            
            // モーダル
            document.getElementById('btnCancel').addEventListener('click', closeModal);
            document.getElementById('btnUpdate').addEventListener('click', handleModalUpdate);
            document.getElementById('btnModalUndo').addEventListener('click', handleModalUndo);
            
            // ESCキーでモーダルを閉じる
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('changeModal');
                    if (!modal.classList.contains('hidden')) {
                        closeModal();
                    }
                }
            });
            
            // モーダル外クリックで閉じる
            document.getElementById('changeModal').addEventListener('click', (e) => {
                if (e.target.id === 'changeModal') {
                    closeModal();
                }
            });
            
            // 保存ボタン
            document.getElementById('saveChangesBtn').addEventListener('click', () => {
                if(!currentRecord) return;
                alert('変更内容を保存しました');
            });
        }

        // ==================== UI更新関数 ====================
        

        /**
         * 関数 changeDisplayMode
         * 概要：ラジオボタンで選択した表示の内容を切り替える関数
         * 引数：mode
         * mode：
         * 'select'、'all'のどちらかを受け取る
         * 'select'の時は、
         */
        function changeDisplayMode(mode) {
            const toggleBtn = document.getElementById('courseDropdownToggle');
            if (mode === 'select') {
                toggleBtn.classList.remove('disabled');
            } else {
                toggleBtn.classList.add('disabled');
            }
            renderSidebarList(mode);
        }

        function renderSidebarList(mode = 'select') {
            const container = document.getElementById('savedListContainer');
            const items = container.querySelectorAll('.saved-item');
            items.forEach(item => item.remove());

            const currentCourse = document.querySelector('#courseDropdownToggle .current-value').textContent;
            
            let filteredRecords = savedTimetables;
            
            // モードによる絞り込み
            if (mode === 'select') {
                filteredRecords = savedTimetables.filter(item => item.course === currentCourse);
            }
            // mode === 'all' の場合はすべて表示（フィルタリングなし）

            filteredRecords.forEach(record => {
                const hasChanges = record.changes && record.changes.length > 0;
                const newItem = document.createElement('li');
                newItem.className = `saved-item ${hasChanges ? 'has-changes' : ''}`;
                newItem.setAttribute('data-id', record.id);
                
                const badgeHtml = hasChanges ? '<span class="changed-badge"></span>' : '';
                const s = new Date(record.startDate);
                const periodText = `${s.getFullYear()}/${s.getMonth()+1}/${s.getDate()}~`;
                
                newItem.innerHTML = `
                    <a href="#" onclick="selectTimetable(${record.id}, this); return false;">
                        ${badgeHtml}
                        <div style="flex:1;">
                            <div style="font-weight:bold;">${record.course}</div>
                            <div style="font-size:0.75rem; color:#666;">期間: ${periodText}</div>
                        </div>
                    </a>
                `;
                container.appendChild(newItem);
            });
            
            // 現在選択中のアイテムがあればactiveクラスを付与
            if (currentRecord) {
                const activeItem = container.querySelector(`.saved-item[data-id="${currentRecord.id}"]`);
                if (activeItem) activeItem.querySelector('a').classList.add('active');
            }
        }

        function selectTimetable(id, element) {
            // activeクラスの切り替え
            document.querySelectorAll('.saved-item a').forEach(el => el.classList.remove('active'));
            element.classList.add('active');

            currentRecord = savedTimetables.find(r => r.id === id);
            
            if (currentRecord) {
                document.getElementById('mainCourseDisplay').textContent = currentRecord.course;
                
                // 開始日の週を計算
                const start = new Date(currentRecord.startDate);
                const today = new Date();
                const end = new Date(currentRecord.endDate);
                
                // 今日が期間内なら今日、そうでなければ開始日
                let targetDate = (today >= start && today <= end) ? today : start;
                
                const day = targetDate.getDay();
                const diff = targetDate.getDate() - day + (day == 0 ? -6 : 1); 
                currentWeekStart = new Date(targetDate);
                currentWeekStart.setDate(diff);
                currentWeekStart = normalizeDate(currentWeekStart);
                
                updateWeekDisplay();
            }
        }

        function updateWeekDisplay() {
            if (!currentWeekStart || !currentRecord) return;

            const weekEnd = new Date(currentWeekStart);
            weekEnd.setDate(currentWeekStart.getDate() + 6);

            const sStr = `${currentWeekStart.getMonth()+1}/${currentWeekStart.getDate()}`;
            const eStr = `${weekEnd.getMonth()+1}/${weekEnd.getDate()}`;
            document.getElementById('weekDisplay').textContent = `${sStr} - ${eStr}`;

            const recStart = normalizeDate(new Date(currentRecord.startDate));
            const recEnd = normalizeDate(new Date(currentRecord.endDate));

            document.getElementById('prevWeekBtn').disabled = (normalizeDate(currentWeekStart) <= recStart);
            document.getElementById('nextWeekBtn').disabled = (normalizeDate(weekEnd) >= recEnd);

            updateDateHeaders();
            renderTable();
        }

        function updateDateHeaders() {
            const days = ['月', '火', '水', '木', '金'];
            const thIds = ['th-mon', 'th-tue', 'th-wed', 'th-thu', 'th-fri'];
            
            days.forEach((d, idx) => {
                const targetDate = new Date(currentWeekStart);
                targetDate.setDate(currentWeekStart.getDate() + idx);
                const m = targetDate.getMonth() + 1;
                const date = targetDate.getDate();
                const th = document.getElementById(thIds[idx]);
                th.innerHTML = `${d} <span class="text-xs font-normal text-gray-500">(${m}/${date})</span>`;
                
                const y = targetDate.getFullYear();
                const mo = String(targetDate.getMonth() + 1).padStart(2, '0');
                const da = String(targetDate.getDate()).padStart(2, '0');
                th.dataset.date = `${y}-${mo}-${da}`;
            });
        }

        function renderTable() {
            clearAllCells();

            const ths = ['th-mon', 'th-tue', 'th-wed', 'th-thu', 'th-fri'];
            const days = ['月','火','水','木','金'];
            
            // 各セルに日付データをセット
            days.forEach((d, idx) => {
                const dateStr = document.getElementById(ths[idx]).dataset.date;
                document.querySelectorAll(`.timetable-cell[data-day="${d}"]`).forEach(cell => {
                    cell.dataset.date = dateStr;
                    // セルクリックイベントを再設定（重複防止のため一度削除してからでも良いが、ここでは上書き的に動作）
                    cell.onclick = handleCellClick;
                });
            });

            // 1. 基本データを描画
            currentRecord.data.forEach(item => {
                const targetCells = document.querySelectorAll(`.timetable-cell[data-day="${item.day}"][data-period="${item.period}"]`);
                targetCells.forEach(cell => {
                    updateCellContent(cell, item.className, item.teacherName, item.roomName, false);
                });
            });

            // 2. 変更データを描画（上書き）
            if (currentRecord.changes) {
                currentRecord.changes.forEach(change => {
                    const changeDate = normalizeDate(new Date(change.date));
                    const weekStart = normalizeDate(currentWeekStart);
                    const weekEnd = new Date(weekStart);
                    weekEnd.setDate(weekEnd.getDate() + 6);
                    
                    if (changeDate >= weekStart && changeDate <= weekEnd) {
                        // この変更が現在の週表示範囲内にある場合
                        const targetCell = document.querySelector(`.timetable-cell[data-day="${change.day}"][data-period="${change.period}"]`);
                        
                        // change.dateとセルのdateが一致するか確認（念のため）
                        if (targetCell && targetCell.dataset.date === change.date) {
                             updateCellContent(targetCell, change.className, change.teacherName, change.roomName, true);
                        }
                    }
                });
            }
        }

        // ==================== モーダル処理 ====================
        
        function handleCellClick() {
            if (!currentRecord) {
                alert('左のリストから変更したい時間割を選択してください');
                return;
            }
            
            editingCell = this;
            const day = this.dataset.day;
            const period = this.dataset.period;
            const date = this.dataset.date;

            document.getElementById('modalTitle').textContent = `${day}曜日 ${period}限 の変更`;
            document.getElementById('targetDateInput').value = date;

            // 既存のデータをフォームにセット
            // class-nameからテキストを取得するが、(休講)等の表示用テキストを除去する必要があるため、データから再取得推奨
            // ここでは簡易的にDOMから取得しつつ、データがあれば優先するロジックにする
            
            // 変更があるか確認
            const change = currentRecord.changes.find(c => c.date === date && c.period === period);
            const base = currentRecord.data.find(d => d.day === day && d.period === period);
            
            let cName = "", tName = "", rName = "";
            let isChanged = false;

            if (change) {
                cName = change.className;
                tName = change.teacherName;
                rName = change.roomName;
                isChanged = true;
            } else if (base) {
                cName = base.className;
                tName = base.teacherName;
                rName = base.roomName;
            }

            setSelectValue(document.getElementById('inputClassName'), cName);
            setSelectValue(document.getElementById('inputTeacherName'), tName);
            setSelectValue(document.getElementById('inputRoomName'), rName);

            displayValidationErrors([]);

            // 変更を取り消しボタンの状態を更新
            document.getElementById('btnModalUndo').disabled = !isChanged;

            document.getElementById('changeModal').classList.remove('hidden');
            document.body.classList.add('modal-open');
        }

        function closeModal() {
            document.getElementById('changeModal').classList.add('hidden');
            document.body.classList.remove('modal-open');
            displayValidationErrors([]);
            editingCell = null;
        }

        function handleModalUpdate() {
            if (!editingCell || !currentRecord) return;

            const date = document.getElementById('targetDateInput').value;
            const day = editingCell.dataset.day;
            const period = editingCell.dataset.period;
            let className = document.getElementById('inputClassName').value;
            let teacherName = document.getElementById('inputTeacherName').value;
            let roomName = document.getElementById('inputRoomName').value;

            // 休講/空きの場合は先生・教室を空にする
            if (!className) {
                teacherName = "";
                roomName = "";
            }

            // バリデーション
            const errors = validateChange(date, period, className, teacherName, roomName);
            if (errors.length > 0) {
                displayValidationErrors(errors);
                return;
            }

            // 基本データと同じなら変更を削除（元に戻す）
            const base = currentRecord.data.find(d => d.day === day && d.period === period);
            const isBaseEmpty = !base;
            const isInputEmpty = !className;
            const isSameAsBase = base && base.className === className && base.teacherName === teacherName && base.roomName === roomName;

            if (isSameAsBase || (isBaseEmpty && isInputEmpty)) {
                // 変更リストから削除
                const idx = currentRecord.changes.findIndex(ch => ch.date === date && ch.period === period);
                if (idx !== -1) currentRecord.changes.splice(idx, 1);
            } else {
                // 変更データを保存
                const newChange = {
                    date: date,
                    day: day,
                    period: period,
                    className: className,
                    teacherName: teacherName,
                    roomName: roomName
                };

                const existingChangeIndex = currentRecord.changes.findIndex(ch => 
                    ch.date === date && ch.period === period
                );

                if (existingChangeIndex !== -1) {
                    currentRecord.changes[existingChangeIndex] = newChange;
                } else {
                    currentRecord.changes.push(newChange);
                }
            }

            // 画面更新
            renderTable();
            renderSidebarList(document.querySelector('input[name="displayMode"]:checked').value);

            closeModal();
        }

        function handleModalUndo() {
            if (!editingCell || !currentRecord) return;

            // 確認ダイアログを追加
            if (!confirm('この変更を取り消してもよろしいですか？')) {
                return;
            }

            const date = document.getElementById('targetDateInput').value;
            const period = editingCell.dataset.period;

            // 変更を削除
            const index = currentRecord.changes.findIndex(ch => 
                ch.date === date && ch.period === period
            );

            if (index !== -1) {
                currentRecord.changes.splice(index, 1);
                
                // 画面更新
                renderTable();
                renderSidebarList(document.querySelector('input[name="displayMode"]:checked').value);

                closeModal();
            }
        }

        // ==================== ドロップダウン制御 ====================
        
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
                        // fixed配置なので座標を設定
                        menu.style.top = `${rect.bottom + 5}px`;
                        menu.style.left = `${rect.left}px`;
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

        // ドロップダウンの初期設定
        setupDropdown('courseDropdownToggle', 'courseDropdownMenu', function() {
            // ドロップダウン変更時にリストを再描画
            renderSidebarList('select');
            
            // 選択解除などのリセット処理
            document.getElementById('mainCourseDisplay').textContent = "（未選択）";
            document.getElementById('weekDisplay').textContent = "（時間割を選択してください）";
            clearAllCells();
            currentRecord = null;
            document.getElementById('prevWeekBtn').disabled = true;
            document.getElementById('nextWeekBtn').disabled = true;
        });

        // 画面クリックでドロップダウンを閉じる
        document.addEventListener('click', (e) => {
            document.querySelectorAll('.dropdown-menu.is-open').forEach(menu => {
                if (menu.contains(e.target)) return;
                menu.classList.remove('is-open');
                const btnId = menu.id.replace('Menu', 'Toggle');
                const btn = document.getElementById(btnId);
                if(btn) btn.setAttribute('aria-expanded', 'false');
            });
        });

        // スクロール時も閉じる
        document.querySelector('.sidebar').addEventListener('scroll', () => {
            document.querySelectorAll('.dropdown-menu.is-open').forEach(menu => {
                menu.classList.remove('is-open');
                const btnId = menu.id.replace('Menu', 'Toggle');
                const btn = document.getElementById(btnId);
                if(btn) btn.setAttribute('aria-expanded', 'false');
            });
        });

        // 初期ロード実行
        renderSidebarList('select');
    </script>
</body>
</html>