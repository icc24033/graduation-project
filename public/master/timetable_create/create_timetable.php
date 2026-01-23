<?php
// create_timetable.php
SecurityHelper::applySecureHeaders();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <title>時間割り作成</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="nofollow, noindex">
    <meta name="csrf-token" content="<?php echo SecurityHelper::escapeHtml($csrfToken); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <nav class="sidebar">
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
                                    <span class="current-value">コースを選択してください</span>
                                </button>
                                <ul id="courseDropdownMenu" class="dropdown-menu">
                                    <?php echo $sidebarCourseList; ?>
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
                                <span>次回反映される時間割り</span>
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

                <div id="footerArea" class="footer-button-area">
                    <button id="cancelCreationBtn" class="delete-button">キャンセル</button>
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
                <div>
                    <label class="modal-label">担当先生：</label>
                    <div id="teacherSelectionArea" style="margin-left: 8rem; display: flex; flex-direction: column; gap: 8px;">
                        <div class="teacher-input-row" style="display: flex; gap: 8px; align-items: center;">
                            <select class="teacher-input modal-select" style="flex: 1;"><option value="">(選択してください)</option><option value="佐藤 健一">佐藤 健一</option><option value="鈴木 花子">鈴木 花子</option><option value="高橋 誠">高橋 誠</option><option value="田中 優子">田中 優子</option><option value="渡辺 剛">渡辺 剛</option><option value="伊藤 直人">伊藤 直人</option><option value="山本 さくら">山本 さくら</option></select>
                            <div class="select-arrow" style="flex-shrink: 0;"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                            <button class="remove-teacher-btn" style="display: none; padding: 4px 8px; background-color: #f87171; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; flex-shrink: 0;">×</button>
                        </div>
                    </div>
                    <div style="margin-left: 8rem; margin-top: 6px;">
                        <button id="addTeacherBtn" style="padding: 4px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">+ 追加</button>
                    </div>
                </div>
                <div>
                    <label class="modal-label">教室場所：</label>
                    <div id="roomSelectionArea" style="margin-left: 8rem; display: flex; flex-direction: column; gap: 8px;">
                        <div class="room-input-row" style="display: flex; gap: 8px; align-items: center;">
                            <select class="room-input modal-select" style="flex: 1;"><option value="">(選択してください)</option><option value="201教室">201教室</option><option value="202教室">202教室</option><option value="301演習室">301演習室</option><option value="302演習室">302演習室</option><option value="4F大講義室">4F大講義室</option><option value="別館Lab A">別館Lab A</option><option value="別館Lab B">別館Lab B</option></select>
                            <div class="select-arrow" style="flex-shrink: 0;"><i class="fa-solid fa-chevron-down text-xs"></i></div>
                            <button class="remove-room-btn" style="display: none; padding: 4px 8px; background-color: #f87171; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; flex-shrink: 0;">×</button>
                        </div>
                    </div>
                    <div style="margin-left: 8rem; margin-top: 6px;">
                        <button id="addRoomBtn" style="padding: 4px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">+ 追加</button>
                    </div>
                </div>
            </div>
            <div class="modal-button-area">
                <button id="btnRevert" class="modal-revert-button" style="display: none;">変更を戻す</button>
                <div style="flex: 1;"></div>
                <button id="btnCancel" class="modal-cancel-button">キャンセル</button>
                <button id="btnSave" class="modal-save-button">保存</button>
            </div>
        </div>
    </div>

    <!-- 新規作成モーダル -->
    <div id="createModal" class="modal-overlay hidden">
        <div class="create-modal-content">
            <h2 class="text-xl font-bold text-slate-800 mb-6 border-b pb-2 border-slate-300">新規作成</h2>

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
        // JS用データの埋め込み
        // コースのデータ一覧
        const dbCourseList = <?php echo json_encode($rawCourseData ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        // PHPからデータを渡すが、変数名をJS内の既存変数と被らないように変更する
        // デフォルトで空配列 [] を入れることでnullエラーを防ぐ
        const dbTimetableData = <?php echo json_encode($savedTimetables ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        //マスタデータ（科目・教員・教室）
        // JS側では dbMasterData[コースID] でアクセスする
        const dbMasterData = <?php echo json_encode($masterSubjectData ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script src="js/timetable_create.js?v=<?php echo time(); ?>"></script>
</body>
</html>