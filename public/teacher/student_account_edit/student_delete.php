<?php
// require_once __DIR__ . '/../session/session_config.php'; // セッション設定を読み込む

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

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
    $school_year = [ $current_year, $current_year - 1, $current_year - 2 ];             
}
else {
    $school_year = [ $current_year, $current_year - 1 ];
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="student_delete">
    <div class="app-container">
        <header class="app-header">
            <h1>生徒アカウント作成編集</h1>
            <img class="user_icon" src="../images/user_icon.png"alt="ユーザーアイコン">
        </header>

        <main class="main-content">
            <nav class="sidebar">
            <ul>
                <li class="nav-item is-group-label">年度</li> 
                <li class="nav-item has-dropdown">
                    <button class="dropdown-toggle" id="yearDropdownToggle" aria-expanded="false" 
                            data-current-year="<?php echo SecurityHelper::escapeHtml((string)$status['current_year']); ?>">
                        <span class="current-value">
                            20<?php echo SecurityHelper::escapeHtml((string)$status['current_year']); ?>年度
                        </span>
                    </button>
                    <ul class="dropdown-menu" id="yearDropdownMenu">
                        <?php foreach ($school_year as $year): ?>
                            <li>
                                <a href="#" 
                                data-current-year="<?php echo SecurityHelper::escapeHtml((string)$year); ?>" 
                                data-current-course="<?php echo SecurityHelper::escapeHtml((string)$current_course_id); ?>"
                                data-current-page="student_delete">
                                20<?php echo SecurityHelper::escapeHtml((string)$year); ?>年度
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
                    <li class="nav-item"><a href="studnet_account_transfer_control.php">学年の移動</a></li>
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

                            // student_idの頭2文字を取得し、現在の年度と比較
                            $student_year_prefix = substr($student_row['student_id'], 0, 2);

                            if ($student_year_prefix == $status['current_year']): 
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
                        <div class="table-row">
                            <div class="column-name">学生情報が見つかりません。</div> 
                        </div>
                    <?php 
                        endif;
                    else:
                    ?>
                        <div class="table-row">
                            <div class="column-name">表示できる学生情報がありません。</div> 
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

                                <form method="POST" action="../../../app/teacher/student_account_edit_backend/backend_student_delete_edit.php" id="deleteForm">
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
    <script src="../js/script.js"></script>
</body>
</html>