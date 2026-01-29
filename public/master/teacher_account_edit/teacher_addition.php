<?php
// SecurityHelperの読み込み（パスは環境に合わせて調整してください）
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <title>先生アカウント作成編集 アカウントの追加</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/style.css"> 
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\common.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\teacher_home\user_menu.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="teacher_addition">
    <div class="app-container">
        <header class="app-header">
            <h1>先生アカウント作成編集</h1>
            <img class="user-icon" src="../images/user-icon.png"alt="ユーザーアイコン">
            <div class="user-avatar" id="userAvatar" style="position: absolute; right: 20px; top: 5px;">
                <img src="<?= SecurityHelper::escapeHtml((string)$data['user_picture']) ?>" alt="ユーザーアイコン" class="avatar-image">   
            </div>
                <div class="user-menu-popup" id="userMenuPopup">
                    <a href="../logout/logout.php" class="logout-button">
                        <span class="icon-key"></span>
                            アプリからログアウト
                    </a>
                    <a href="" class="help-button">
                        <span class="icon-lightbulb"></span> ヘルプ
                    </a>
                </div>
            <img src="<?= SecurityHelper::escapeHtml((string)$smartcampus_picture) ?>" alt="Webアプリアイコン" width="200" height="60" style="position: absolute; left: 20px; top: 5px;">
        </header>

        <main class="main-content">
            <nav class="sidebar">
                <li class="nav-item is-group-label"><a href="#">アカウント作成・編集</a></li>
                <ul>
                    <li class="nav-item"><a href="teacher_addition_control.php" class="is-active">アカウントの追加</a></li>
                    <li class="nav-item"><a href="teacher_delete_control.php">アカウントの削除</a></li>
                    <li class="nav-item"><a href="teacher_info_control.php">アカウント情報変更</a></li>
                    <li class="nav-item"><a href="master_edit_control.php">マスタの付与</a></li>
                </ul>
                <form action="../../../../app/master/teacher_account_edit_backend/backend_csv_upload.php" method="POST" enctype="multipart/form-data">
    
                    <input type="file" id="csvFile" name="csvFile" accept=".csv" required style="display: none;" onchange="this.form.submit();">
                    
                    <label for="csvFile" class="download-button">
                        <span class="material-symbols-outlined download-icon">download</span> 名簿アップロード
                    </label>

                </form>
            </nav>
            
            <?php if ($basicInfo['backend'] === 'teacher_addition'): ?>
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
            <?php elseif ($basicInfo['backend'] === 'csv_upload' && $basicInfo['csv_count_flg'] === true): ?>
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
                            <div class="column-action"></div> 
                        </div>

                        <?php foreach ($basicInfo['csv_data'] as $teacher): ?>
                        <div id="teacherInputContainer">
                            <div class="table-row"> 
                                <div class="column-name">
                                    <input type="text" name="teacher_names[]" value="<?php echo SecurityHelper::escapeHtml($teacher['name']); ?>">
                                </div>
                                <div class="column-mail">
                                    <input type="email" name="teacher_emails[]" value="<?php echo SecurityHelper::escapeHtml($teacher['email']); ?>">
                                </div>
                                <div class="column-action">
                                    <button type="button" class="remove-row-button">
                                        <span class="material-symbols-outlined">remove_circle</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" class="add-button" id="addRowButton">追加</button>
                    <?php if ($basicInfo['error_count_flg'] === false): ?>
                    <button type="submit" class="complete-button">完了</button>
                    <?php endif; ?>
                </form>
            </div>
            <?php else : ?>
                <div class="content-area">
                    <div class="empty-state-container" style="text-align: center; padding: 40px; background: #fff; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 20px;">
                        <span class="material-symbols-outlined" style="font-size: 48px; color: #ccc; vertical-align: middle;">person_off</span>
                        <p style="margin-top: 15px; color: #666; font-weight: bold;">追加可能な正常データが見つかりませんでした。</p>
                        <p style="font-size: 0.9em; color: #888;">CSVファイルの内容を確認するか、下部に表示される「エラーデータ編集」から修正を行ってください。</p>
                    </div>
                </div>
            <?php endif; ?>
        </main>

    </div>
    <?php if ($basicInfo['backend'] === 'csv_upload' && $basicInfo['error_count_flg'] === true): ?>
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

        <form id="additionForm" action="../../../../app/master/teacher_account_edit_backend/backend_csv_error_teacher_edit.php" method="post">
            <div class="error-edit-container">
                <h3>エラーデータ編集</h3>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCsrfToken(); ?>">

            <div class="account-table-container">
                <div class="table-header">
                    <div class="column-name">氏名</div>
                    <div class="column-mail">メールアドレス</div>
                    <div class="column-action"></div> 
                </div>

                <?php foreach ($basicInfo['error_data'] as $teacher): ?>
                    <div class="table-row"> 
                        <div class="column-name">
                            <input type="text" 
                                name="teachers[<?php echo $teacher['id']; ?>][name]" 
                                value="<?php echo SecurityHelper::escapeHtml($teacher['name']); ?>">
                        </div>
                        <div class="column-mail">
                            <input type="email" 
                                name="teachers[<?php echo $teacher['id']; ?>][email]" 
                                value="<?php echo SecurityHelper::escapeHtml($teacher['email']); ?>">
                        </div>
                        <div class="column-action">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
                    
            <button type="submit" class="complete-button">編集完了</button>
        </form>
    </div>
    <?php endif; ?>
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