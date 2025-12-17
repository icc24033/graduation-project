<?php

// require_once __DIR__ . '/../session/session_config.php'; // セッション設定を読み込む

// セッション開始
session_start();

// セッションから処理結果を取得
$status = $_SESSION['student_account'] ?? null;

// セッションデータを取得したらすぐに削除 (二重表示防止のため)
////unset($_SESSION['student_account']);

// コース名変数の初期化 (DB接続失敗時でもエラーを防ぐため)
$current_course_id = $status['course_id']; 
$course = []; // コースデータを格納する配列を初期化

// 現在の年度の取得
$current_year = date("Y");
$current_year = substr($current_year, -2); // 下2桁を取得

$selected_year = date("Y");
$selected_year = substr($selected_year, -2); // 下2桁を取得

// 現在の月を取得
$current_month = date('n');

// 学年度の配列を作成
if ($current_month < 4) {
    $school_year = [ $current_year, $current_year - 1, $current_year - 2 ];             
}
else {
    $school_year = [ $current_year, $current_year - 1 ];
}


try {
    
    $config_path = __DIR__ . '/../../../config/secrets_local.php';

    $config = require $config_path;

    define('DB_HOST', $config['db_host']);
    define('DB_NAME', $config['db_name']);
    define('DB_USER', $config['db_user']);
    define('DB_PASS', $config['db_pass']);

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    //データベース接続
    $pdo = new PDO($dsn, DB_USER, DB_PASS);

    //　リストに表示するコース情報を取得
    $stmt_course = $pdo->query($status['course_sql']);
    $course = $stmt_course->fetchAll(); // ここで取得されるのは連想配列の配列

    // 　テストstudentに格納されている学生情報の取得
    $stmt_test_student = $pdo->prepare($status['student_sql']);
    $stmt_test_student->execute([$status['course_id']]);

    // 現在のコース名の初期値を設定 (最初の要素の 'course_name' を使用)
    if (!empty($course)) {
        // 連想配列のキーを指定して値を取得
        $current_course_name = $course[$status['course_id'] - 1]['course_name'];// コースIDは1からなので、配列インデックス用に-1する
    } else {
        $current_course_name = 'コース情報が見つかりません';
    }
    // データべース接続切断
    $pdo = null;
}
catch (PDOException $e) {
    // データベース接続/クエリ実行エラー発生時
    error_log("DB Error: " . $e->getMessage());
    $current_course_name = 'エラー: データベース接続失敗';
    // 本番環境ではエラーを投げず、安全なメッセージを表示することが推奨されます
    // throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>



<!DOCTYPE html>
<html lang="ja">
<head>
    <title>生徒アカウント作成編集 アカウントの追加</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="grade_transfar">
    <div class="app-container">
        <header class="app-header">
            <h1>生徒アカウント作成編集</h1>
            <img class="user_icon" src="images/user_icon.png"alt="ユーザーアイコン">
        </header>

        <main class="main-content">
            <nav class="sidebar">
                <ul>
                <li class="nav-item is-group-label">年度</li> 
                    <li class="nav-item has-dropdown">
                        <button class="dropdown-toggle" id="yearDropdownToggle" aria-expanded="false" data-current-year="<?php echo htmlspecialchars($status['current_year']); ?>">
                            <span class="current-value">20<?php echo $status['current_year']?>年度</span>
                        </button>
                        <ul class="dropdown-menu" id="yearDropdownMenu">
                            <?php foreach ($school_year as $year): ?>
                                <li>
                                    <a href="#" 
                                       data-current-year="<?php echo htmlspecialchars($year);?>" 
                                       data-current-course="<?php echo htmlspecialchars($current_course_id); ?>"
                                       data-current-page="student_grade_transfer">
                                       20<?php echo htmlspecialchars($year); ?>年度
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <ul class="dropdown-menu" id="gradeDropdownMenu">
                            <?php for ($grade = 1; $grade <= 2; $grade++): ?>
                                <li>
                                    <a href="#" data-selected-grade-center="<?php echo $grade; ?>">
                                        <?php echo $grade; ?>年
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </li>
            
                    <li class="nav-item is-group-label">コース</li> 
                    <li class="nav-item has-dropdown">
                        <button class="dropdown-toggle" 
                                id="courseDropdownToggle" 
                                aria-expanded="false" 
                                data-current-course="<?php echo htmlspecialchars($current_course_id); ?>"
                                data-current-year="<?php echo htmlspecialchars($status['current_year']); ?>">
                            <span class="current-value"><?php echo htmlspecialchars($current_course_name); ?></span>
                        </button>
                        <ul class="dropdown-menu" id="courseDropdownMenu">
                            <?php if (!empty($course)): ?>
                                <?php foreach ($course as $row): ?>
                                    <li>
                                        <a href="#" 
                                           data-current-course="<?php echo htmlspecialchars($row['course_id']);?>" 
                                           data-current-year="<?php echo htmlspecialchars($status['current_year']); ?>"
                                           data-selected-course-center="<?php echo htmlspecialchars($row['course_id']); ?>"
                                           data-current-page="student_grade_transfer">
                                           <?php echo htmlspecialchars($row['course_name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <!-- ---------------------------------------------------------------------------------- -->
                            <?php else: ?>
                                <li><a href="#">コース情報が見つかりません</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    


                    <li class="nav-item is-group-label">アカウント作成・編集</li>
                    <li class="nav-item"><a href="..\..\..\app\teacher\student_account_edit_backend\backend_student_addition.php">アカウントの作成</a></li>
                    <li class="nav-item"><a href="..\..\..\app\teacher\student_account_edit_backend\backend_student_delete.php">アカウントの削除</a></li>
                    <li class="nav-item is-active"><a href="..\..\..\app\teacher\student_account_edit_backend\backend_student_grade_transfer.php">学年の移動</a></li>
                    <li class="nav-item"><a href="..\..\..\app\teacher\student_account_edit_backend\backend_student_course_edit.php">コースの編集</a></li>
                </ul>
                
            </nav>

            <div class="content-area">
                <form action="..\..\..\app\teacher\student_account_edit_backend\backend_student_grade_transfer_edit.php" method="post">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($status['course_id']); ?>">
                <input type="hidden" name="current_year" value="<?php echo htmlspecialchars($status['current_year']); ?>">
                    <div class="account-table-container">
                        <div class="table-header">
                            <div class="column-check"></div> <div class="column-student-id">学生番号</div>
                            <div class="column-name">氏名</div>
                            <div class="column-course">コース</div>
                        </div>
                        
                        <?php 
                        // $stmt_test_studentが有効な場合のみループ
                        if ($stmt_test_student): 
                            $has_students = false; // データが存在したかどうかのフラグ

                            while ($student_row = $stmt_test_student->fetch()): 
            
                                // ★ 変更点: student_idの頭2文字を取得し、現在の年度と比較
                                $student_year_prefix = substr($student_row['student_id'], 0, 2); // 学生IDの頭2文字を取得

                                if ($student_year_prefix == $status['current_year']): // 値が一致するか比較
                                $has_students = true;
                        ?>
                            <div class="table-row">
                                <div class="column-check">
                                </div>
                                <div class="column-student-id">
                                    <?php 
                                        // 現在の学年を計算
                                        // 注意: $selected_year と $current_year の値が同じなら結果は常に 1 になります。
                                        $current_grade = $student_row['grade'];
                                        $initial_display = $current_grade . '年'; 
                                        // 許可された最大学年。この値を基準に初期値を決定します。
                                        $max_grade = 2; 

                                        // 初期値が範囲外の場合、表示を調整する
                                        if ($current_grade < 1 || $current_grade > $max_grade) {
                                            $initial_display = '学年不明'; 
                                            $current_grade = 0; // 送信しない値
                                        }
                                    ?>
                                    <a href="#" class="course-display" 
                                        data-grade-display
                                        data-dropdown-for="gradeDropdownMenu" 
                                        data-current-grade-value="<?php echo htmlspecialchars($student_row['grade']); ?>">
                                        <?php echo htmlspecialchars($initial_display); ?>
                                    </a>
                                    <input type="hidden" 
                                        name="grade_changes[<?php echo htmlspecialchars($student_row['student_id']); ?>]" 
                                        value="<?php echo htmlspecialchars($current_grade); ?>"
                                        class="grade-hidden-input">
                                </div>

                                <div class="column-name">
                                    <input type="text" value="<?php echo htmlspecialchars($student_row['student_name']); ?>" disabled>
                                </div>
                                <div class="column-course">
                                    <input type="text" value="<?php echo htmlspecialchars($current_course_name); ?>" disabled>
                                </div>
                            </div>

                        <?php 
                                endif; // if ($student_year_prefix === $current_year_short) 終了
                            endwhile; // whileループ終了
                            
                            // ループ後にデータがなかった場合のエラー表示
                            if (!$has_students):
                        ?>
                                <div class="table-row">
                                    <div class="column-check"></div> 
                                    <div class="column-student-id"></div> 
                                    <div class="column-name">学生情報が見つかりません。</div> 
                                    <div class="column-course"></div>
                                </div>
                        <?php 
                            endif;
                            // DB接続エラーなどで$stmt_test_studentがnullの場合
                        else:
                        ?>
                            <div class="table-row">
                                <div class="column-check"></div> 
                                <div class="column-student-id"></div> 
                                <div class="column-name">データベースエラーのため、学生情報を表示できません。</div> 
                                <div class="column-course"></div>
                            </div>
                        <?php endif; ?>

                    </div>
                    <?php 
                        // $courseが空ではない、つまりコース情報が見つかった場合のみ表示
                        if ($has_students): 
                    ?>
                        <button class="add-button" type="submit">学年移動</button>
                    <?php 
                    endif; 
                    ?>
                </form>
            </div>
        </main>
    </div>
    <script src="js/script.js"></script>
</body>
</html>