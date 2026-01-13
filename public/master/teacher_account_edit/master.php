<?php
// require_once __DIR__ . '/../session/session_config.php'; // セッション設定を読み込む

// SecurityHelperの読み込み
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <title>先生アカウント作成編集 マスタの付与</title>
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
            <img class="user_icon" src="../images/user-icon.png"alt="ユーザーアイコン">
        </header>

        <main class="main-content">
            <nav class="sidebar">
                <li class="nav-item is-group-label"><a href="#">編集</a></li>
                <ul>
                    <li class="nav-item"><a href="teacher_addition_control.php">アカウントの追加</a></li>
                    <li class="nav-item"><a href="teacher_delete_control.php">アカウントの削除</a></li>
                    <li class="nav-item"><a href="teacher_info_control.php">アカウント情報変更</a></li>
                    <li class="nav-item"><a href="master_edit_control.php" class="is-active">マスタの付与</a></li>
                    <li class="nav-item"><a href="class.html">担当授業確認</a></li>
                </ul>
            </nav>
            
            <div class="content-area">
                <form action="..\..\..\..\app\master\teacher_account_edit_backend\backend_update_master.php" method="post">
                    
                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCsrfToken(); ?>">

                    <div class="account-table-container master-grant-table">
                        <div class="table-header">
                            <img class="teacher-avatar" src="../images/test_icon.png" alt="講師アイコン">
                            <div class="column-name">講師名</div>
                            <div class="column-check"><input type="checkbox" id="selectAllCheckbox"></div>
                        </div>

                        <?php foreach ($teacherList as $teacher): ?>
                            <div class="table-row">
                            <img class="teacher-avatar" src="../images/test_icon.png" alt="講師アイコン">
                                <div class="column-name">
                                    <span><?php echo SecurityHelper::escapeHtml((string)$teacher['teacher_name']); ?></span>
                                </div>
                                <div class="column-check">
                                    <input type="checkbox" 
                                        name="teacher_ids[]" 
                                        value="<?php echo SecurityHelper::escapeHtml((string)$teacher['teacher_id']); ?>"
                                        <?php echo ($teacher['master_flg'] == 1) ? 'checked' : ''; ?>>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" class="complete-button">保存</button>
                </form>
            </div>
        </main>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>