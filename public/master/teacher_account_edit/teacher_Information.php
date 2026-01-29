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
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\common.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\teacher_home\user_menu.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="teacher_info">
    <div class="app-container">
        <header class="app-header">
            <h1>先生アカウント作成編集</h1>
            <img class="user-icon" src="../images/user-icon.png"alt="ユーザーアイコン">
            <div class="user-avatar" id="userAvatar" style="position: absolute; right: 20px; top: 5px;">
                <img src="<?= SecurityHelper::escapeHtml((string)$data['user_picture']) ?>" alt="ユーザーアイコン" class="avatar-image">   
            </div>
                <div class="user-menu-popup" id="userMenuPopup">
                    <a href="../../../logout/logout.php" class="logout-button">
                        <span class="icon-key"></span>
                            アプリからログアウト
                    </a>
                    <a href="../../../help/help_control.php?back_page=3" class="help-button" target="_blank" rel="noopener noreferrer">
                        <span class="icon-lightbulb"></span> ヘルプ
                    </a>
                </div>
            <a href="../../../login/redirect.php" 
                style="position: absolute; left: 20px; top: 5px;" 
                onclick="return confirm('ホーム画面に遷移しますか？ ※編集中の内容が消える恐れがあります');">
                    <img src="<?= SecurityHelper::escapeHtml((string)$smartcampus_picture) ?>" alt="Webアプリアイコン" width="200" height="60">
            </a>
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
    <script>
        const allCourseInfo = <?= json_encode($courseInfo) ?>;
        let currentData = {};

        document.addEventListener('DOMContentLoaded', function() {
                const userAvatar = document.getElementById('userAvatar');
                const userMenuPopup = document.getElementById('userMenuPopup');

                userAvatar.addEventListener('click', function(event) {
                    userMenuPopup.classList.toggle('is-visible');
                    event.stopPropagation();
                });

                document.addEventListener('click', function(event) {
                    if (!userMenuPopup.contains(event.target) && !userAvatar.contains(event.target)) {
                        userMenuPopup.classList.remove('is-visible');
                    }
                });
            });
    </script>
    <script src="../js/script.js"></script>
</body>
</html>