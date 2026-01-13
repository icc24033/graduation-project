<!DOCTYPE html>
<html lang="ja">
<head>
    <title>先生アカウント作成編集 担当授業確認</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="teacher_addition">
    <div class="app-container">
        <header class="app-header">
            <h1>先生アカウント作成編集</h1>
            <img class="user-icon" src="images/user-icon.png"alt="ユーザーアイコン">
        </header>

        <main class="main-content">
            <nav class="sidebar">
                <li class="nav-item is-group-label"><a href="#">編集</a></li>
                <ul>
                    <li class="nav-item"><a href="teacher_addition.php">アカウントの追加</a></li>
                    <li class="nav-item"><a href="teacher_delete.php">アカウントの削除</a></li>
                    <li class="nav-item"><a href="teacher_Information.php">アカウント情報変更</a></li>
                    <li class="nav-item"><a href="master.php">マスタの付与</a></li>
                    <li class="nav-item"><a href="class.php" class="is-active">担当授業確認</a></li>
                </ul>
            </nav>
            
           <div class="content-area">
                <div class="account-table-container master-grant-table">
                    <div class="table-header">
                        <div class="avatar-placeholder header-avatar"></div> <div class="column-name">講師名</div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span>山田 太郎</span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span>佐藤 次郎</span></div>
                    </div>

                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span>田中 花子</span></div>
                    </div>
             <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span>山本 一郎</span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span></span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span></span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span></span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span></span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span></span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span></span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span></span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span></span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span></span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span></span></div>
                    </div>
                    <div class="table-row">
                        <div class="avatar-placeholder"></div>
                        <div class="column-name"><span></span></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <div class="modal-overlay" id="teacherDetailsModal">
        <div class="modal-content details-modal-content">
            <div class="modal-header">
                <h2 id="modalTeacherName" class="teacher-name-in-modal">先生</h2>
            </div>
            
            <div class="modal-body details-modal-body">
                <div class="modal-panel">
                    <div>                   
                    <div class="panel-row subject-section">
                        <label class="panel-label">現在の担当授業</label>
                        <div class="subject-list" id="modalSubjectList">
                            <div class="subject-item" data-dropdown-for="modalSubjectDropdown">新規授業</div>
                        </div>
                    </div> 
                    <div class="modal-action-buttons">
                        <div class="add-subject-button">＋</div> 
                        <div class="delete-subject-button">ー</div> 
                    </div>
                    </div>
                    <div class="dropdown-menu modal-dropdown-menu" id="modalSubjectDropdown">
                        <ul>
                            <li><a href="#">Web技術</a></li>
                            <li><a href="#">Web基本</a></li>
                            <li><a href="#">Java</a></li>
                            <li><a href="#">JavaScript</a></li>
                            <li><a href="#">C言語</a></li>
                            <li><a href="#">C#</a></li>
                            <li><a href="#">マルチ基本</a></li>
                            <li><a href="#">ネットワーク</a></li>
                            <li><a href="#">ITパスポート</a></li>
                            <li><a href="#">情報セキュリティマネジメント</a></li>
                            <li><a href="#">基本情報</a></li>
                            <li><a href="#">応用情報・高度</a></li>
                            <li><a href="#">簿記</a></li>
                            <li><a href="#">社会学</a></li>
                        </ul>
                    </div>
                </div>
    
                <div class="modal-action-area">
                    <button class="confirm-button" id="confirmButton">確定</button>
                </div>
            </div>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>