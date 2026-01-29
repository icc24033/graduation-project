<?php
// student_addition.php

$status = $basic_data ?? null;

if ($status['backend'] === 'student_addition') {
    // 現在の年度の取得
    $current_year = date("Y");
    $current_year = (int)substr($current_year, -2); // 下2桁を取得

} 
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <title>生徒アカウント作成編集 アカウントの追加</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" href="../css/style.css"> 
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\common.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\teacher_home\user_menu.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="student_addition">

    <div class="app-container">
        <header class="app-header">
            <h1>生徒アカウント作成編集</h1>
            <img class="user_icon" src="../images/user_icon.png"alt="ユーザーアイコン">
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

        <ul class="dropdown-menu" id="courseDropdownMenu">
            <?php if (!empty($courseList)): ?>
                <?php foreach ($courseList as $row): ?>
                    <li>
                        <a href="#" 
                        data-selected-course-center="<?php echo SecurityHelper::escapeHtml((string)$row['course_id']); ?>"
                        data-course-name="<?php echo SecurityHelper::escapeHtml((string)$row['course_name']); ?>">
                            <?php echo SecurityHelper::escapeHtml((string)$row['course_name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>

        <main class="main-content">
            <nav class="sidebar">
                <ul>
                    <li class="nav-item is-group-label">アカウント作成・編集</li>
                    <li class="nav-item is-active"><a href="student_account_edit_control.php">アカウントの作成</a></li>
                    <li class="nav-item"><a href="student_account_delete_control.php">アカウントの削除</a></li>
                    <li class="nav-item"><a href="student_account_transfer_control.php">学年の移動</a></li>
                    <li class="nav-item"><a href="student_account_course_control.php">コースの編集</a></li>
                </ul>
                
                <form action="..\..\..\..\app\master\student_account_edit_backend\backend_csv_upload.php" method="post" enctype="multipart/form-data" class="download-form" id="uploadForm">
                    <div class="file-upload-wrapper">
                        <input type="file" id="csvFile" name="csvFile" accept=".csv" required class="download-button" onchange="this.form.submit();">
                        <label for="csvFile" class="download-button">
                        <span class="material-symbols-outlined download-icon">download</span> 名簿ダウンロード
                        </label>
                    </div>
                </form>
            </nav>


            <?php if ($status['backend'] === 'student_addition'): ?>
            <?php $i = 0; // IDを管理するためのカウンタを初期化 ?>
            <div class="content-area">
                <form action="..\..\..\..\app\master\student_account_edit_backend\backend_student_addition_edit.php" method="POST">
                    <div class="account-table-container">
                        <div class="table-header">
                            <div class="column-check"></div> <div class="column-student-id">学生番号</div>
                            <div class="column-name">氏名</div>
                            <div class="column-course">コース</div>
                        </div>
                        
                        <div class="table-row">
                            <div class="column-check">
                            </div> 
                            <div class="column-student-id">
                                <input type="text" 
                                    name="students[<?php echo SecurityHelper::escapeHtml((string)$i); ?>][student_id]" 
                                    value="<?php echo SecurityHelper::escapeHtml((string)($status['student_count'] + 1 + $i + ($current_year * 1000))); ?>">
                            </div> 
                            <div class="column-name">
                                <input type="text" 
                                    name="students[<?php echo SecurityHelper::escapeHtml((string)$i); ?>][name]" 
                                    placeholder="氏名">
                            </div> 
                            <div class="column-course">
                                <a href="#" class="course-display" 
                                    data-course-name-display 
                                    data-dropdown-for="courseDropdownMenu"
                                    data-selected-course-center="7">
                                    1年1組
                                </a>
                                <input type="hidden" 
                                    name="students[<?php echo SecurityHelper::escapeHtml((string)$i); ?>][course_id]" 
                                    value="7"
                                    class="course-hidden-input">
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button class="add-button" type="button">追加</button> 
                        <button class="add-button" type="button">追加人数入力</button>
                    </div>
                    <?php 
                        if ($status['error_csv'] === false):
                    ?>
                        <button class="complete-button" type="submit">作成完了</button>
                    <?php 
                        endif;
                    ?>
                </form>
            </div>

            <?php $i++; // 1行目が終わったのでカウンタをインクリメント（複数行を扱うJavaScript実装に備えて） ?>


            
            <?php 
                elseif ($status['backend'] === 'csv_upload' && $status['success_csv'] === true): 
            ?>
            <div class="content-area">
                <div class="account-table-container">
                    <div class="table-header">
                        <div class="column-check"></div> 
                        <div class="column-student-id">学生番号</div>
                        <div class="column-name">氏名</div>
                        <div class="column-course">コース</div>
                    </div>
                    
                    <?php foreach ($status['csv_data'] as $row): ?>
                    <div class="table-row">
                        <div class="column-check">
                        </div>
                        <div class="column-student-id">
                            <input type="text" value="<?php echo SecurityHelper::escapeHtml((string)$row['student_id']); ?>" disabled>
                        </div>
                        <div class="column-name">
                            <input type="text" value="<?php echo SecurityHelper::escapeHtml((string)$row['name']); ?>" disabled>
                        </div>
                        <div class="column-course">
                            <?php 
                                // コース名を取得。配列のインデックス調整ロジックはそのまま維持
                                $course_name = $courseList[$row['course_id'] - 1]['course_name'] ?? '不明なコース';
                            ?>
                            <input type="text" value="<?php echo SecurityHelper::escapeHtml((string)$course_name); ?>" disabled>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($status['error_csv'] === false): ?>
                <form action="..\..\..\..\app\master\student_account_edit_backend\backend_csvdata_upload.php" method="post">
                    <button class="complete-button">追加完了</button>
                </form>
                <?php endif; ?>

            </div>
            <?php elseif ($status['backend'] === 'csv_upload' && $status['success_csv'] === false): ?>
                
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

    <?php 
        if ($status['error_csv'] === true && $status['backend'] === 'csv_upload'): 
    ?>
    <div class="content-area">
        <div class="error-edit-container">
        <h3>エラーデータ編集</h3>
        <div>
        <form action="..\..\..\..\app\master\student_account_edit_backend\backend_csv_error_student_edit.php" method="post">
        <div class="account-table-container">
            <div class="table-header">
                <div class="column-check"></div> 
                <div class="column-student-id">学生番号</div>
                <div class="column-name">氏名</div>
                <div class="column-course">メールアドレス</div>
            </div>
            
            <?php foreach ($status['error_data'] as $error_row): ?>
            <div class="table-row">
                <div class="column-check">
                </div> 
                
                <div class="column-student-id">
                    <input type="text" 
                        name="students[<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>][student_id]" 
                        value="<?php echo SecurityHelper::escapeHtml((string)$error_row['student_id']); ?>">
                </div> 
                
                <div class="column-name">
                    <input type="text" 
                        name="students[<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>][name]" 
                        value="<?php echo SecurityHelper::escapeHtml((string)$error_row['name']); ?>">
                </div> 
                
                <div class="column-course">
                    <input type="text" 
                        name="students[<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>][approvalUserAddress]" 
                        value="<?php echo SecurityHelper::escapeHtml((string)$error_row['approvalUserAddress']); ?>">
                </div>

                <input type="hidden" 
                    name="students[<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>][id]" 
                    value="<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>">
                    
                <input type="hidden" 
                    name="students[<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>][course_id]" 
                    value="<?php echo SecurityHelper::escapeHtml((string)$error_row['course_id']); ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <button class="add-button" id="deleteActionButton">編集完了</button>
        </form>

    </div>

    <?php 
        elseif ($status['error_csv'] === true && $status['backend'] === 'student_addition'):
    ?>
    <div class="content-area">
        <div class="error-edit-container">
        <h3>エラーデータ編集</h3>
        <div>
        <form action="..\..\..\..\app\master\student_account_edit_backend\backend_error_addition_edit.php" method="post">
        <div class="account-table-container">
            <div class="table-header">
                <div class="column-check"></div> 
                <div class="column-student-id">学生番号</div>
                <div class="column-name">氏名</div>
                <div class="column-course">メールアドレス</div>
            </div>
            
            <?php foreach ($status['error_data'] as $error_row): ?>
            <div class="table-row">
                <div class="column-check">
                </div> 
                
                <div class="column-student-id">
                    <input type="text" 
                        name="students[<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>][student_id]" 
                        value="<?php echo SecurityHelper::escapeHtml((string)$error_row['student_id']); ?>">
                </div> 
                
                <div class="column-name">
                    <input type="text" 
                        name="students[<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>][name]" 
                        value="<?php echo SecurityHelper::escapeHtml((string)$error_row['name']); ?>">
                </div> 
                
                <div class="column-course">
                    <input type="text" 
                        name="students[<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>][approvalUserAddress]" 
                        value="<?php echo SecurityHelper::escapeHtml((string)$error_row['approvalUserAddress']); ?>">
                </div>

                <input type="hidden" 
                    name="students[<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>][id]" 
                    value="<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>">
                    
                <input type="hidden" 
                    name="students[<?php echo SecurityHelper::escapeHtml((string)$error_row['id']); ?>][course_id]" 
                    value="<?php echo SecurityHelper::escapeHtml((string)$error_row['course_id']); ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <button class="add-button" id="deleteActionButton">編集完了</button>
        </form>

    </div>
    <?php endif; ?>

    <div class="modal-overlay" id="addCountModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>生徒アカウント追加</h2>
            </div>
            <div class="modal-body">
                <p>追加する生徒の人数を入力してください。</p>
                <div class="input-container">
                    <input type="number" id="studentCountInput" min="1" max="50" value="1">
            </div>
            <div class="modal-footer">
                <button class="modal-button modal-cancel-button" id="cancelAddCount">キャンセル</button>
                <button class="modal-button modal-confirm-button" id="confirmAddCount">追加</button>
            </div>
        </div>
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