<?php
// require_once __DIR__ . '/../session/session_config.php'; // セッション設定を読み込む

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$status = $basic_data ?? null;

// コースIDの取得
$current_course_id = $status['course_id']; 

// 現在の年度の取得
$current_year = date("Y");
$current_year = substr($current_year, -2); // 下2桁を取得

$selected_year = date("Y");
$selected_year = substr($selected_year, -2); // 下2桁を取得

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

require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';

// 現在のコース名の初期値を設定 (最初の要素の 'course_name' を使用)
if (!empty($courseList)) {
    // 連想配列のキーを指定して値を取得
    $current_course_name = $courseList[$status['course_id'] - 1]['course_name'];// コースIDは1からなので、配列インデックス用に-1する
} else {
    $current_course_name = 'コース情報が見つかりません';
}

// データべース接続切断
$pdo = null;
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
<body id="grade_transfar">
    <div class="app-container">
        <header class="app-header">
            <h1>生徒アカウント作成編集</h1>
            <img class="user_icon" src="../images/user_icon.png"alt="ユーザーアイコン">
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
                                    data-current-page="student_grade_transfer">
                                    <?php echo SecurityHelper::escapeHtml((string)$gradeInfo['grade_name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <ul class="dropdown-menu" id="gradeDropdownMenu">
                            <?php foreach($gradeList as $yaer => $gradeInfo): ?>
                                <li>
                                    <a href="#" data-selected-grade-center="<?php echo SecurityHelper::escapeHtml((string)$gradeInfo['grade']); ?>">
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
                            <span class="current-value">
                                <?php echo SecurityHelper::escapeHtml((string)$current_course_name); ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu" id="courseDropdownMenu">
                            <?php if (!empty($courseList)): ?>
                                <?php foreach ($courseList as $row): ?>
                                    <li>
                                        <a href="#" 
                                        data-current-course="<?php echo SecurityHelper::escapeHtml((string)$row['course_id']); ?>" 
                                        data-current-year="<?php echo SecurityHelper::escapeHtml((string)$status['current_year']); ?>"
                                        data-selected-course-center="<?php echo SecurityHelper::escapeHtml((string)$row['course_id']); ?>"
                                        data-current-page="student_grade_transfer">
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
                    <li class="nav-item"><a href="student_account_delete_control.php">アカウントの削除</a></li>
                    <li class="nav-item is-active"><a href="student_account_transfer_control.php">学年の移動</a></li>
                    <li class="nav-item"><a href="student_account_course_control.php">コースの編集</a></li>
                </ul>
                
            </nav>

            <div class="content-area">
                <form action="..\..\..\..\app\master\student_account_edit_backend\backend_student_grade_transfer_edit.php" method="post">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($status['course_id']); ?>">
                <input type="hidden" name="current_year" value="<?php echo htmlspecialchars($status['current_year']); ?>">
                    <div class="account-table-container">
                        <div class="table-header">
                            <div class="column-check"></div> <div class="column-student-id">学年</div>
                            <div class="column-name">氏名</div>
                            <div class="column-course">コース</div>
                        </div>
                        
                        <?php 
                        // $stmt_test_studentが有効な場合のみループ
                        $has_students = false; // データが存在したかどうかのフラグ
                        if (!empty($status['students_in_course']) && is_array($status['students_in_course'])): 

                            foreach ($status['students_in_course'] as $student_row): 
            
                                // 今選択されている学年と生徒の学年を比較
                                $student_year_prefix = $student_row['grade'];
                                if ($student_year_prefix == $status['current_year']): // 値が一致するか比較
                                $has_students = true;
                        ?>
                            <div class="table-row">
                                <div class="column-check">
                                </div>
                                <div class="column-student-id">
                                    <?php 
                                        // 現在の学年を表示するロジック
                                        $initial_display = $gradeList[$student_row['grade']]['grade_name'] ?? '不明';
                                    ?>
                                    <a href="#" class="course-display" 
                                        data-grade-display
                                        data-dropdown-for="gradeDropdownMenu" 
                                        data-current-grade-value="<?php echo SecurityHelper::escapeHtml((string)$student_row['grade']); ?>">
                                        <?php echo SecurityHelper::escapeHtml((string)$initial_display); ?>
                                    </a>
                                    <input type="hidden" 
                                        name="grade_changes[<?php echo SecurityHelper::escapeHtml((string)$student_row['student_id']); ?>]" 
                                        value="<?php echo SecurityHelper::escapeHtml((string)$student_row['grade']); ?>"
                                        class="grade-hidden-input">
                                </div>

                                <div class="column-name">
                                    <input type="text" value="<?php echo SecurityHelper::escapeHtml((string)$student_row['student_name']); ?>" disabled>
                                </div>
                                <div class="column-course">
                                    <input type="text" value="<?php echo SecurityHelper::escapeHtml((string)$current_course_name); ?>" disabled>
                                </div>
                            </div>

                        <?php 
                                endif; // if ($student_year_prefix === $current_year_short) 終了
                            endforeach; // foreach 終了
                            
                            // ループ後にデータがなかった場合のエラー表示
                            if (!$has_students):
                        ?>
                            <div class="table-row no-data">
                                表示できる学生情報がありません。
                            </div>
                        <?php 
                            endif;
                            // DB接続エラーなどで$stmt_test_studentがnullの場合
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
                        <button class="complete-button" type="submit">学年移動</button>
                    <?php 
                    endif; 
                    ?>
                </form>
            </div>
        </main>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>