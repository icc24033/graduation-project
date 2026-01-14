<?php
// SecurityHelperの読み込み（パスは環境に合わせて調整してください）
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <title>先生アカウント作成編集 アカウントの追加</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/style.css"> 
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="teacher_addition">
    <div class="app-container">
        <header class="app-header">
            <h1>先生アカウント作成編集</h1>
            <img class="user-icon" src="../images/user-icon.png"alt="ユーザーアイコン">
        </header>

        <main class="main-content">
            <nav class="sidebar">
                <li class="nav-item is-group-label"><a href="#">編集</a></li>
                <ul>
                    <li class="nav-item"><a href="teacher_addition_control.php" class="is-active">アカウントの追加</a></li>
                    <li class="nav-item"><a href="teacher_delete_control.php">アカウントの削除</a></li>
                    <li class="nav-item"><a href="teacher_info_control.php">アカウント情報変更</a></li>
                    <li class="nav-item"><a href="master_edit_control.php">マスタの付与</a></li>
                    <!-- <li class="nav-item"><a href="class.html">担当授業確認</a></li> -->
                </ul>
                <form action="../../../../app/master/teacher_account_edit_backend/backend_csv_upload.php" method="POST" enctype="multipart/form-data">
    
                    <input type="file" id="csvFile" name="csvFile" accept=".csv" required style="display: none;" onchange="this.form.submit();">
                    
                    <label for="csvFile" class="download-button">
                        <span class="material-symbols-outlined download-icon">download</span> 名簿アップロード
                    </label>

                </form>
            </nav>
            
            <div class="content-area">
                <?php if (isset($_GET['success_msg'])): ?>
                    <div class="alert alert-success">
                        <?php echo SecurityHelper::escapeHtml($_GET['success_msg']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error_msg'])): ?>
                    <div class="alert alert-error">
                        <?php echo SecurityHelper::escapeHtml($_GET['error_msg']); ?>
                    </div>
                <?php endif; ?>

                <form id="additionForm" action="../../../../app/master/teacher_account_edit_backend/backend_add_teacher.php" method="post">
                    
                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCsrfToken(); ?>">

                    <div class="account-table-container">
                        <div class="table-header">
                            <div class="column-name">氏名</div>
                            <div class="column-mail">メールアドレス</div>
                            <div class="column-action"></div> </div>
                        <div id="teacherInputContainer">
                            <div class="table-row"> 
                                <div class="column-name">
                                    <input type="text" name="teacher_names[]" placeholder="氏名" required>
                                </div>
                                <div class="column-mail">
                                    <input type="email" name="teacher_emails[]" placeholder="メールアドレス" required>
                                </div>
                                <div class="column-action">
                                    <button type="button" class="remove-row-button">
                                        <span class="material-symbols-outlined">remove_circle</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="add-button" id="addRowButton">追加</button>
                    <button type="submit" class="complete-button">完了</button>
                </form>
            </div>
        </main>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>