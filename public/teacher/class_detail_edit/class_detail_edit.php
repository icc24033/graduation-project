<!DOCTYPE html>
<html lang="ja">
    <head>
        <title>授業詳細</title>
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
                    <li class="is-group-label">フィルター</li>
                    <li class="nav-item has-dropdown">
                        <button class="dropdown-toggle" id="gradeDropdownToggle" aria-expanded="false">
                            <span class="current-value">全学年</span>
                        </button>
                        <ul class="dropdown-menu" id="gradeDropdownMenu">
                            <li><a href="#" data-course="1nen">１年生</a></li>
                            <li><a href="#" data-course="2nen">２年生</a></li>
                            <li><a href="#" data-course="all">全学生</a></li>
                        </ul>
                    </li>
                    <li class="nav-item has-dropdown">
                        <button class="dropdown-toggle" id="courseDropdownToggle" aria-expanded="false">
                            <span class="current-value">全コース</span>
                        </button>
                        <ul class="dropdown-menu" id="courseDropdownMenu">
                            <li><a href="#" data-course="system-design">システムデザインコース</a></li>
                            <li><a href="#" data-course="web-creator">Webクリエイタコース</a></li>
                            <li><a href="#" data-course="all">全コース</a></li>
                        </ul>
                    </li>
                    <li class="nav-item has-dropdown">
                        <button class="dropdown-toggle" id="subjectDropdownToggle" aria-expanded="false">
                            <span class="current-value">
                                <?php echo !empty($subjects) ? htmlspecialchars($subjects[0]['subject_name']) : '教科選択'; ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu" id="subjectDropdownMenu">
                            <?php if (!empty($subjects)): ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <li><a href="#" data-subject-id="<?= $subject['teacher_id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></a></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="#">担当教科なし</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <div class="next-lesson-wrapper">
                        <li class="is-group-label">編集済みの授業</li>
                    </div>
                    <div id="sidebarLessonList">
                    </div>
                </ul>
            </nav>

            <section class="content-area">
                <div class="month-selector">
                    <div class="month-search">
                        <button class="arrow" id="prevBtn">
                            <img class="left-arrow" src="images/left.png" alt="前へ">
                        </button>
                        <p class="month">1月</p>
                        <button class="arrow" id="nextBtn">
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
                        <div class="day-header">土</div>
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
                            <textarea class="lesson-details-textarea" id="lessonContent" placeholder="授業の内容を入力してください。"></textarea>
                            <p class="char-count">0/200文字</p>
                        </div>
                        
                        <div class="form-section">
                            <h2 class="form-title">課題・持ち物</h2>
                            <div class="common-items-area">
                                <h3 class="sub-title">よく使う持ち物テンプレート</h3>
                                <div class="item-list-wrapper">
                                    <div class="item-tags">
                                        <div class="item-tag-container"><span class="item-tag">ノートパソコン</span></div>
                                        <div class="item-tag-container"><span class="item-tag">筆記用具</span></div>
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
                                <textarea id="belongingsTextarea" class="details-items-textarea" placeholder="持ち物は入力されていません"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="delete-button" id="deleteBtn">削除</button>
                        <div>
                            <button id="tempSaveButton" class="save-button temp-save-button">一時保存</button>
                            <button id="completeButton" class="save-button complete-button">完了</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <script src="js/script.js"></script>
    </body>
</html>