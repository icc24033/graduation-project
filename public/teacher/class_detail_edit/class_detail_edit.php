<!DOCTYPE html>
<html lang="ja">
<head>
    <title>授業詳細システム</title>
    <meta charset="utf-8">
    <meta name="description" content="授業詳細の確認と編集ができます。">
    <meta name="keywords" content="授業, スケジュール, 管理">
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
                <li class="is-group-label">フィルター</li>
                
                <li class="nav-item has-dropdown">
                    <button class="dropdown-toggle" id="gradeDropdownToggle" aria-expanded="false">
                        <span class="current-value" id="displayGradeSidebar">全学年</span>
                    </button>
                    <ul class="dropdown-menu" id="gradeDropdownMenu">
                        </ul>
                </li>

                <li class="nav-item has-dropdown">
                    <button class="dropdown-toggle" id="courseDropdownToggle" aria-expanded="false">
                        <span class="current-value" id="displayCourseSidebar">全コース</span>
                    </button>
                    <ul class="dropdown-menu" id="courseDropdownMenu">
                        </ul>
                </li>

                <li class="nav-item has-dropdown">
                    <button class="dropdown-toggle" id="subjectDropdownToggle" aria-expanded="false">
                        <span class="current-value" id="currentSubjectDisplay">授業を選択</span>
                    </button>
                    <ul class="dropdown-menu" id="subjectDropdownMenu">
                        </ul>
                </li>
            
                <div class="next-lesson-wrapper">
                    <li class="is-group-label">次回の授業</li>
                </div>
                <div id="sidebarLessonList">
                    <div class="lesson-status-wrapper" style="display:none;">
                        <div class="lesson-date-item">○月○日(○) ○限</div>
                        <button class="status-button not-created">未作成</button>
                    </div>
                    <div class="lesson-status-wrapper" style="display:none;">
                        <div class="lesson-date-item">○月○日(○) ○限</div>
                        <button class="status-button not-created">未作成</button>
                    </div>
                    <div class="lesson-status-wrapper" style="display:none;">
                        <div class="lesson-date-item">○月○日(○) ○限</div>
                        <button class="status-button not-created">未作成</button>
                    </div>
                    <div class="lesson-status-wrapper" style="display:none;">
                        <div class="lesson-date-item">○月○日(○) ○限</div>
                        <button class="status-button not-created">未作成</button>
                    </div>
                    <div class="lesson-status-wrapper" style="display:none;">
                        <div class="lesson-date-item">○月○日(○) ○限</div>
                        <button class="status-button not-created">未作成</button>
                    </div>
                </div>
            </ul>
        </nav>

        <section class="content-area">
            <div class="month-selector">
                <div class="month-search">
                    <button class="arrow" id="prevMonth">
                        <img class="left-arrow" src="images/left.png" alt="前月へ">
                    </button>
                    <p class="month" id="currentMonthDisplay">1月</p>
                    <button class="arrow" id="nextMonth">
                        <img class="right-arrow" src="images/right.png" alt="次月へ">
                    </button>
                </div>
                <div class="month-wrapper">
                    <span class="course-name-title" id="displayGrade">学年</span>
                    <span class="course-name-title" id="displayCourse">コース名</span>
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
                        <p class="modal-date" id="modalDateDisplay">〇月〇日(〇)</p>
                    </div>
                    <img class="user-icon" src="images/user-icon.png" alt="アイコン">
                </header>
                
                <div class="modal-body">
                    <div class="form-section">
                        <h2 class="form-title">授業詳細</h2>
                        <textarea class="lesson-details-textarea" id="lessonContentText" placeholder="授業の内容、連絡事項などを入力してください。"></textarea>
                        <p class="char-count" id="charCountDisplay">0/200文字</p>
                    </div>
                    
                    <div class="form-section">
                        <h2 class="form-title">課題・持ち物</h2>
                        
                        <div class="common-items-area">
                            <h3 class="sub-title">よく使う持ち物テンプレート</h3>
                            <div class="item-list-wrapper">
                                <div class="item-tags" id="templateTags">
                                    <div class="item-tag-container"><span class="item-tag">ノートパソコン</span></div>
                                    <div class="item-tag-container"><span class="item-tag">筆記用具</span></div>
                                    <div class="item-tag-container"><span class="item-tag">教科書1</span></div>
                                    <div class="item-tag-container"><span class="item-tag">教科書2</span></div>
                                    <div class="item-tag-container"><span class="item-tag">プリント</span></div>
                                </div>
                                <div class="item-delete-icon-wrapper">
                                    <img class="delete-icon" src="images/Vector.png" alt="テンプレート削除">
                                </div>
                            </div>
                            
                            <div class="add-item-input-wrapper">
                                <input type="text" class="add-item-input" id="newTemplateInput" placeholder="テンプレートを追加">
                                <button class="add-button" id="addTemplateBtn">追加</button>
                            </div>
                        </div>

                        <div class="current-items-area">
                            <textarea id="detailsTextarea" class="details-items-textarea" placeholder="持ち物は入力されていません"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button class="delete-button" id="deleteLessonBtn">削除</button>
                    <div>
                        <button class="save-button temp-save-button" id="tempSaveBtn">一時保存</button>
                        <button class="save-button complete-button" id="completeBtn">完了</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer></footer>
    
    <script src="js/script.js"></script>
</body>
</html>