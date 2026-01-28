<?php
// require_once __DIR__ . '/../session/session_config.php'; // セッション設定を読み込む

?>



<!DOCTYPE html>
<html lang="ja">
<head>
    <title>先生アカウント作成編集 アカウント情報変更</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/style.css"> 
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="teacher_info">
    <div class="app-container">
        <header class="app-header">
            <h1>先生アカウント作成編集</h1>
            <img class="user-icon" src="../images/user-icon.png"alt="ユーザーアイコン">
        </header>

        <main class="main-content">
            <nav class="sidebar">
                <li class="nav-item is-group-label"><a href="#">アカウント作成・編集</a></li>
                <ul>
                    <li class="nav-item"><a href="teacher_addition_control.php">アカウントの追加</a></li>
                    <li class="nav-item"><a href="teacher_delete_control.php">アカウントの削除</a></li>
                    <li class="nav-item"><a href="teacher_info_control.php" class="is-active">アカウント情報変更</a></li>
                    <li class="nav-item"><a href="master_edit_control.php">マスタの付与</a></li>
                </ul>
            </nav>
            
            <div class="content-area">
                <form id="updateForm" action="../../../../app/master/teacher_account_edit_backend/backend_edit_info.php" method="post">
                    
                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCsrfToken(); ?>">

                    <div class="account-table-container">
                        <div class="table-header">
                            <div class="column-check"></div>
                            <div class="column-name">氏名</div>
                            <div class="column-mail">メールアドレス</div>
                        </div>

                        <?php if (!empty($viewData['teacherList'])): ?>
                            <?php foreach ($viewData['teacherList'] as $index => $teacher): ?>
                                <div class="table-row">
                                    <div class="column-check">
                                        <input type="checkbox" name="update_indices[]" value="<?= $index ?>">
                                        <input type="hidden" name="teacher_data[<?= $index ?>][id]" value="<?= htmlspecialchars($teacher['teacher_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div> 
                                    
                                    <div class="column-name">
                                        <input type="text" name="teacher_data[<?= $index ?>][name]" value="<?= htmlspecialchars($teacher['teacher_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    
                                    <div class="column-mail">
                                        <input type="email" name="teacher_data[<?= $index ?>][mail]" value="<?= htmlspecialchars($teacher['teacher_mail'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="table-row"><div style="padding:15px;">データがありません。</div></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="complete-button">変更を保存</button>
                </form>
            </div>
        </main>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>