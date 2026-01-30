<?php
// require_once __DIR__ . '/../session/session_config.php'; // セッション設定を読み込む

$status = $basic_data ?? null;

// コースIDの取得
$current_course_id = $status['course_id']; 

// 現在の年度の取得
$current_year = date("Y");
$current_year = substr($current_year, -2); // 下2桁を取得

// 現在の月を取得
$current_month = date('n');

// 学年度の配列を作成
if ($current_month < 4) {
    unset($gradeList[3]); // 卒業生を表示させない         
}
else if ($current_month < 5) {
    unset($gradeList[0]); // 入学予定者を表示させない
}
else {
    unset($gradeList[0]); // 入学予定者を表示させない
    unset($gradeList[3]); // 卒業生を表示させない         
}

// 現在のコース名の初期値を設定 (最初の要素の 'course_name' を使用)
if (!empty($courseList)) {
    // 連想配列のキーを指定して値を取得
    $current_course_name = $courseList[$status['course_id'] - 1]['course_name'];// コースIDは1からなので、配列インデックス用に-1する
} else {
    $current_course_name = 'コース情報が見つかりません';
}
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <title>生徒アカウント作成編集 アカウントの追加</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="../css/style.css"> 
    <link rel="stylesheet" href="../css/reset.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\common.css">
    <link rel="stylesheet" type="text/css" href="/2025\sotsuken\graduation-project\public\master\css\teacher_home\user_menu.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="student_delete">
    <div class="app-container">
        <header class="app-header">
            <h1>生徒アカウント作成編集</h1>
            <img class="user_icon" src="../images/user_icon.png"alt="ユーザーアイコン">
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
            <ul>
                <li class="nav-item is-group-label">学年</li> 
                <li class="nav-item has-dropdown">
                    <button class="dropdown-toggle" id="yearDropdownToggle" aria-expanded="false" 
                            data-current-year="<?php echo SecurityHelper::escapeHtml((string)$status['current_year']); ?>">
                        <span class="current-value">
                            <?php echo SecurityHelper::escapeHtml((string)$gradeList[$status['current_year']]['grade_name']); ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu" id="yearDropdownMenu">
                        <?php foreach ($gradeList as $year => $gradeInfo): ?>
                            <li>
                                <a href="#" 
                                data-current-year="<?php echo SecurityHelper::escapeHtml((string)$gradeInfo['grade']); ?>"
                                data-current-course="<?php echo SecurityHelper::escapeHtml((string)$current_course_id); ?>"
                                data-current-page="student_delete">
                                <?php echo SecurityHelper::escapeHtml((string)$gradeInfo['grade_name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                    <li class="nav-item is-group-label">コース</li> 
                    <li class="nav-item has-dropdown">
                        <button class="dropdown-toggle" 
                                id="courseDropdownToggle" 
                                aria-expanded="false" 
                                data-current-course="<?php echo SecurityHelper::escapeHtml((string)$current_course_id); ?>"
                                data-current-year="<?php echo SecurityHelper::escapeHtml((string)$status['current_year']); ?>">
                            <span class="current-value"><?php echo SecurityHelper::escapeHtml((string)$current_course_name); ?></span>
                        </button>
                        <ul class="dropdown-menu" id="courseDropdownMenu">
                            <?php if (!empty($courseList)): ?>
                                <?php foreach ($courseList as $row): ?>
                                    <li>
                                        <a href="#" 
                                        data-current-course="<?php echo SecurityHelper::escapeHtml((string)$row['course_id']); ?>" 
                                        data-current-year="<?php echo SecurityHelper::escapeHtml((string)$status['current_year']); ?>"
                                        data-selected-course-center="<?php echo SecurityHelper::escapeHtml((string)$row['course_id']); ?>"
                                        data-current-page="student_delete">
                                        <?php echo SecurityHelper::escapeHtml((string)$row['course_name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="#">コース情報が見つかりません</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <li class="nav-item is-group-label">アカウント作成・編集</li>
                    <li class="nav-item"><a href="student_account_edit_control.php">アカウントの作成</a></li>
                    <li class="nav-item is-active"><a href="student_account_delete_control.php">アカウントの削除</a></li>
                    <li class="nav-item"><a href="student_account_transfer_control.php">学年の移動</a></li>
                    <li class="nav-item"><a href="student_account_course_control.php">コースの編集</a></li>
                </ul>
            </nav>

            <div class="content-area">
                <div class="account-table-container">
                    <div class="table-header">
                        <div class="column-check"></div> <div class="column-student-id">学生番号</div>
                        <div class="column-name">氏名</div>
                        <div class="column-course">コース</div>
                    </div>
                    <?php 
                    // $status['students_in_course']が有効な場合のみループ
                    if (!empty($status['students_in_course']) && is_array($status['students_in_course'])): 
                        $has_students = false; // データが存在したかどうかのフラグ

                        // ★修正箇所: while (...) -> fetch() を foreach (...) に変更
                        foreach ($status['students_in_course'] as $student_row): 

                            // 今選択されている学年と生徒の学年を比較
                            $student_year_prefix = $student_row['grade'];
                            if ($student_year_prefix == $status['current_year']): // 値が一致するか比較
                            $has_students = true;
                    ?>
                        <div class="table-row">
                            <div class="column-check">
                                <input type="checkbox" 
                                    class="row-checkbox" 
                                    data-student-id="<?php echo SecurityHelper::escapeHtml((string)$student_row['student_id']); ?>">
                            </div>
                            <div class="column-student-id">
                                <input type="text" value="<?php echo SecurityHelper::escapeHtml((string)$student_row['student_id']); ?>" disabled>
                            </div>
                            <div class="column-name">
                                <input type="text" value="<?php echo SecurityHelper::escapeHtml((string)$student_row['student_name']); ?>" disabled>
                            </div>
                            <div class="column-course">
                                <input type="text" value="<?php echo SecurityHelper::escapeHtml((string)$current_course_name); ?>" disabled>
                            </div>
                        </div>
                    <?php 
                            endif; 
                        endforeach; // ★修正箇所: endwhile を endforeach に変更

                        // ループ後に表示対象がなかった場合
                        if (!$has_students):
                    ?>
                        <div class="table-row no-data">
                            表示できる学生情報がありません。
                        </div>
                    <?php 
                        endif;
                    else:
                    ?>
                        <div class="table-row no-data">
                            表示できる学生情報がありません。
                        </div>
                    <?php endif; ?>
                </div>
                <?php 
                    // $courseListが空ではない、つまりコース情報が見つかった場合のみ表示
                    if ($has_students): 
                ?>
                    <button class="add-button" id="deleteActionButton">削除</button>
                    <div class="modal-overlay" id="deleteModal">
                        <div class="modal-content">
                             <div class="modal-header">
                                <h2>アカウント削除確認</h2>
                            </div>
                            <div class="modal-body">
                                <p>以下の0件のアカウントを削除してもよろしいですか？</p>
                                <div class="delete-list-container">
                                    <div id="selectedStudentList"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="modal-button modal-cancel-button" id="cancelDeleteButton">キャンセル</button>

                                <form method="POST" action="../../../../app/master/student_account_edit_backend/backend_student_delete_edit.php" id="deleteForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCsrfToken(); ?>">
                                    
                                    <div id="hiddenInputsContainer" style="display: none;"></div>
                                    <button type="submit" class="modal-button modal-delete-button" id="confirmDeleteButton">削除</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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