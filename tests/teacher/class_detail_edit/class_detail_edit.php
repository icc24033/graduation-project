<?php
// class_detail_edit.php
// 授業詳細編集画面のビュー
// SecurityHelper::requireLogin();
SecurityHelper::applySecureHeaders();

// コントローラーから渡されていない場合の初期値設定
$gradeList = $gradeList ?? [];
$courseList = $courseList ?? [];
$subjectList = $subjectList ?? [];
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <title>授業詳細編集</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="robots" content="nofollow,noindex">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/reset.css">
        <link rel="stylesheet" href="css/style.css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    </head>
    <body>
        <header>
            <div class="header">
                <h1>授業詳細</h1>
                <img class="user-icon" src="images/user-icon.png" alt="アイコン">
            </div>
        </header>
        <main>
            <nav class="sidebar">
                <ul>
                    <li class="is-group-label">フィルター設定</li>
                    
                    <li class="nav-item has-dropdown">
                        <button class="dropdown-toggle" id="gradeFilterToggle" aria-expanded="false">
                            <span class="current-value">全学年</span>
                        </button>
                        <ul class="dropdown-menu" id="gradeFilterMenu">
                            </ul>
                    </li>

                    <li class="nav-item has-dropdown">
                        <button class="dropdown-toggle" id="courseFilterToggle" aria-expanded="false">
                            <span class="current-value">全コース</span>
                        </button>
                        <ul class="dropdown-menu" id="courseFilterMenu">
                            </ul>
                    </li>

                    <li style="border-top: 1px solid #ccc; margin: 15px 10px; padding-top: 15px;"></li>
                    <li class="is-group-label" style="color: #0056b3; font-weight:bold;">編集対象の科目</li>

                    <li class="nav-item has-dropdown subject-selector-wrapper">
                        <button class="dropdown-toggle special-subject-toggle" id="subjectSelectorToggle" aria-expanded="false">
                            <span class="current-value">読込中...</span>
                        </button>
                        <ul class="dropdown-menu" id="subjectSelectorMenu">
                            </ul>
                    </li>
                    
                    <div class="next-lesson-wrapper">
                        <li class="is-group-label">次回の授業</li>
                    </div>
                    <div class="lesson-status-wrapper">
                        <div class="lesson-date-item">--月--日(-) --限</div>
                        <button class="status-button not-created">未作成</button>
                    </div>
                    
                    <li class="is-group-label">次回以降</li>
                    
                    <div class="lesson-status-wrapper">
                        <div class="lesson-date-item">--月--日(-) --限</div>
                        <button class="status-button not-created">未作成</button>
                    </div>
                </ul>
            </nav>
            
            <section class="content-area">
                <div class="month-selector">
                    <div class="month-search">
                        <button class="arrow">
                            <img class="left-arrow" src="images/left.png" alt="前へ">
                        </button>
                        <p class="month">1月</p>
                        <button class="arrow">
                            <img class="right-arrow" src="images/right.png" alt="後ろへ">
                        </button>
                    </div>
                    <div class="month-wrapper">
                        <span class="course-name-title">学年</span>
                        <span class="course-name-title" id="displayCourseName">授業名</span>
                    </div>
                </div>
                <div class="calendar-container">
                    <div class="calendar-grid" id="calendarGrid">
                        <div class="day-header is-sunday">日</div>
                        <div class="day-header">月</div>
                        <div class="day-header">火</div>
                        <div class="day-header">水</div>
                        <div class="day-header">木</div>
                        <div class="day-header">金</div>
                        <div class="day-header is-saturday">土</div>
                    </div>
                </div>
            </section>

            <div id="lessonModal" class="modal-overlay">
                <div class="modal-content">
                    <header class="modal-header">
                        <div class="modal-header-content">
                            <p class="modal-date">〇月〇日(〇)</p>
                        </div>
                        <img class="user-icon" src="images/user-icon.png" alt="アイコン">
                    </header>
                    <div class="modal-body">
                        <div class="form-section">
                            <h2 class="form-title">授業詳細</h2>
                            <textarea class="lesson-details-textarea" placeholder="授業の内容、連絡事項などを入力してください。"></textarea>
                            <p class="char-count">0/200文字</p>
                        </div>
                        
                        <div class="form-section">
                            <h2 class="form-title">課題・持ち物</h2>
                            
                            <div class="common-items-area">
                                <h3 class="sub-title" id="template-title">よく使う持ち物テンプレート</h3>
                                <div class="item-list-wrapper">
                                    <div class="item-tags">
                                        <div class="item-tag-container">
                                            <span class="item-tag">ノートパソコン</span>
                                        </div>
                                        <div class="item-tag-container">
                                            <span class="item-tag">教科書1</span>
                                        </div>
                                    </div>
                                    <div class="item-delete-icon-wrapper">
                                        <img class="delete-icon" src="images/Vector.png" alt="削除">
                                    </div>
                                </div>
                                
                                <div class="add-item-input-wrapper">
                                    <input type="text" class="add-item-input" placeholder="テンプレートを追加">
                                    <button class="add-button">追加</button>
                                </div>
                            </div>

                            <div class="current-items-area">
                                <textarea id="detailsTextarea" class="details-items-textarea" placeholder="持ち物は入力されていません"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="delete-button">削除</button>
                        <div>
                            <button class="save-button temp-save-button">一時保存</button>
                            <button class="save-button complete-button">完了</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <script>
            // PHPの配列をJSON形式でJSオブジェクトとして受け取る
            const assignedClassesData = <?php echo json_encode($assignedClasses ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        </script>
        <script src="js/class_detail_edit.js"></script>
    </body>
</html>