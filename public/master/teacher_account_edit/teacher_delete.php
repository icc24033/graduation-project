<?php
// SecurityHelperの読み込み（パスは環境に合わせて調整してください）
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <title>先生アカウント作成編集 アカウントの削除</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/style.css"> 
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\common.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\teacher_home\user_menu.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="teacher_delete"> <div class="app-container">
        <header class="app-header">
            <h1>先生アカウント作成編集</h1>
            <img class="user-icon" src="../images/user-icon.png" alt="ユーザーアイコン">
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
                    <li class="nav-item"><a href="teacher_delete_control.php" class="is-active">アカウントの削除</a></li>
                    <li class="nav-item"><a href="teacher_info_control.php">アカウント情報変更</a></li>
                    <li class="nav-item"><a href="master_edit_control.php">マスタの付与</a></li>
                </ul>
            </nav>
            
            <div class="content-area">
                <form id="deleteForm" action="../../../../app/master/teacher_account_edit_backend/backend_delete_teacher.php" method="post">
                    
                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCsrfToken(); ?>">

                    <div class="account-table-container">
                        <div class="table-header">
                            <div class="column-check"><input type="checkbox" id="selectAllCheckbox"></div>
                            <div class="column-name">氏名</div>
                            <div class="column-mail">メールアドレス</div>
                        </div>

                        <?php if (!empty($teacherList)): ?>
                            <?php foreach ($teacherList as $teacher): ?>
                                <div class="table-row">
                                    <div class="column-check">
                                        <input type="checkbox" name="teacher_ids[]" 
                                            value="<?php echo SecurityHelper::escapeHtml((string)$teacher['teacher_id']); ?>"
                                            data-is-master="<?php echo (int)$teacher['master_flg']; ?>"> 
                                    </div> 

                                    <div class="column-name">
                                        <input type="text" value="<?php echo SecurityHelper::escapeHtml((string)$teacher['teacher_name']); ?>" readonly>
                                    </div>

                                    <div class="column-mail">
                                        <input type="email" value="<?php echo SecurityHelper::escapeHtml((string)$teacher['teacher_mail']); ?>" readonly>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="table-row"><div style="padding:15px;">登録データがありません。</div></div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="complete-button" id="deleteActionButton">削除</button>

                    <div class="modal-overlay" id="deleteModal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2>アカウント削除確認</h2>
                            </div>
                            <div class="modal-body">
                                <p>以下の0件のアカウントを削除してもよろしいですか？</p>
                                <div class="delete-list-container">
                                    <div id="selectedTeacherList"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="modal-button modal-cancel-button" id="cancelDeleteButton">キャンセル</button>
                                <button type="submit" class="modal-button modal-delete-button" id="confirmDeleteButton">削除</button>
                            </div>
                        </div>
                    </div>
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