<!DOCTYPE html>
<html lang="ja">
<head>
    <title>先生アカウント作成編集 アカウントの追加</title>
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
                    <li class="nav-item"><a href="teacher_addition.php" class="is-active">アカウントの追加</a></li>
                    <li class="nav-item"><a href="teacher_delete.php">アカウントの削除</a></li>
                    <li class="nav-item"><a href="teacher_Information.php">アカウント情報変更</a></li>
                    <li class="nav-item"><a href="master.php">マスタの付与</a></li>
                    <li class="nav-item"><a href="class.php">担当授業確認</a></li>
                </ul>
                <button class="download-button">
                    <span class="material-symbols-outlined download-icon">download</span>
                    名簿ダウンロード
                </button>
            </nav>
            
            <div class="content-area">
                <div class="account-table-container">
                    <div class="table-header">
                        <div class="column-name">氏名</div>
                        <div class="column-mail">メールアドレス</div>
                    </div>
                    <div class="table-row"> 
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row">
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row"> 
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row">
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row"> 
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row">
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row"> 
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row">
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row"> 
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row">
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row"> 
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row">
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row"> 
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row">
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                    <div class="table-row"> 
                        <div class="column-name"><input type="text" value="氏名"></div>
                        <div class="column-mail"><input type="email" value="メールアドレス"></div>
                    </div>
                </div>
                
                <button class="add-button">追加</button>
                <button class="complete-button">完了</button>
            </div>
        </main>
    </div>
    <script src="js/script.js"></script>
</body>
</html>