<?php

// --- デバッグ用：エラーを表示させる設定（解決したら削除してください） ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// require_once __DIR__ . '/../session/session_config.php'; // セッション設定を読み込む

// セッション開始
session_start();

$config_path = __DIR__ . '/../../../config/secrets_local.php';

$config = require $config_path;

define('DB_HOST', $config['db_host']);
define('DB_NAME', $config['db_name']);
define('DB_USER', $config['db_user']);
define('DB_PASS', $config['db_pass']);

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

// セッションから処理結果を取得
$status = $_SESSION['student_account'] ?? null;

if ($status['backend'] === 'student_addition') {
    // 現在の年度の取得
    $current_year = date("Y");
    $current_year = (int)substr($current_year, -2); // 下2桁を取得

    try {
        //データベース接続
        $pdo = new PDO($dsn, DB_USER, DB_PASS);

        // 　テストstudentに格納されている今年度の学生数の取得
        $stmt_test_student = $pdo->prepare($status['student_count_sql']);
        $stmt_test_student->execute([$current_year]);
        $student_count = $stmt_test_student->fetchColumn();

        // コース情報の取得
        $stmt_course = $pdo->prepare($status['course_sql']);
        $stmt_course->execute();
        $courses = $stmt_course->fetchAll();
    }
    catch (PDOException $e) {
        // データベース接続/クエリ実行エラー発生時
        error_log("DB Error: " . $e->getMessage());
        $current_course_name = 'エラー: データベース接続失敗';
        // 本番環境ではエラーを投げず、安全なメッセージを表示することが推奨されます
        // throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
} 
else if ($status['backend'] === 'csv_upload') {
    try {
        //データベース接続
        $pdo = new PDO($dsn, DB_USER, DB_PASS);

        // csv_tableに格納されている今年度の学生の取得
        $stmt_csv_table = $pdo->prepare($status['csv_table_student_sql']);
        $stmt_csv_table->execute();

        // コース情報の取得
        $stmt_course = $pdo->prepare($status['course_sql']);
        $stmt_course->execute();
        $courses = $stmt_course->fetchAll();
    }
    catch (PDOException $e) {
        // データベース接続/クエリ実行エラー発生時
        error_log("DB Error: " . $e->getMessage());
        $current_course_name = 'エラー: データベース接続失敗';
        // 本番環境ではエラーを投げず、安全なメッセージを表示することが推奨されます
        // throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
} 
else {
    // 不正なアクセスの場合、エラーメッセージを表示して終了
    echo "不正なアクセスです。";
    exit();

}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <title>生徒アカウント作成編集 アカウントの追加</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body id="student_addition">

    <div class="app-container">
        <header class="app-header">
            <h1>生徒アカウント作成編集</h1>
            <img class="user_icon" src="images/user_icon.png"alt="ユーザーアイコン">
        </header>

        <main class="main-content">
            <nav class="sidebar">
                <ul>
                    <li class="nav-item is-group-label">アカウント作成・編集</li>
                    <li class="nav-item is-active"><a href="..\..\..\app\teacher\student_account_edit_backend\backend_student_addition.php">アカウントの作成</a></li>
                    <li class="nav-item"><a href="..\..\..\app\teacher\student_account_edit_backend\backend_student_delete.php">アカウントの削除</a></li>
                    <li class="nav-item"><a href="..\..\..\app\teacher\student_account_edit_backend\backend_student_grade_transfer.php">学年の移動</a></li>
                    <li class="nav-item"><a href="..\..\..\app\teacher\student_account_edit_backend\backend_student_course.php">コースの編集</a></li>
                </ul>
                
                <form action="..\..\..\app\teacher\student_account_edit_backend\backend_csv_upload.php" method="post" enctype="multipart/form-data" class="download-form" id="uploadForm">
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
                <form action="..\..\..\app\teacher\student_account_edit_backend\csv_edit.php" method="POST">
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
                                    name="students[<?php echo $i; ?>][student_id]" 
                                    value="<?php echo htmlspecialchars($student_count + 1 + ($current_year * 1000)); ?>">
                            </div> 
                            <div class="column-name">
                                <input type="text" 
                                    name="students[<?php echo $i; ?>][name]" 
                                    placeholder="氏名">
                            </div> 
                            <div class="column-course">
                                <span class="course-display" 
                                    data-course-name-display 
                                    data-dropdown-for="courseDropdownMenu"
                                    data-selected-course-center="7">
                                    1年1組
                                </span>
                                <input type="hidden" 
                                    name="students[<?php echo $i; ?>][course_id]" 
                                    value="7"
                                    class="course-hidden-input">
                            </div>
                            <div class="dropdown-menu" id="courseDropdownMenu">
                                <div class="dropdown-item" data-course-id="1">1年1組</div>
                                <div class="dropdown-item" data-course-id="2">1年2組</div>
                                <div class="dropdown-item" data-course-id="3">2年1組</div>
                                <div class="dropdown-item" data-course-id="4">2年2組</div>
                                <div class="dropdown-item" data-course-id="5">3年1組</div>
                                <div class="dropdown-item" data-course-id="6">3年2組</div>
                                <div class="dropdown-item" data-course-id="7">4年1組</div>
                                <div class="dropdown-item" data-course-id="8">4年2組</div>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button class="add-button" type="button">追加</button> 
                        <button class="add-button" type="button">追加人数入力</button>
                    </div>
                    <button class="complete-button" type="submit">完了</button>
                </form>
            </div>

            <?php $i++; // 1行目が終わったのでカウンタをインクリメント（複数行を扱うJavaScript実装に備えて） ?>


            
            <?php elseif ($status['backend'] === 'csv_upload'): ?>
            <div class="content-area">
                <div class="account-table-container">
                    <div class="table-header">
                        <div class="column-check"></div> <div class="column-student-id">学生番号</div>
                        <div class="column-name">氏名</div>
                        <div class="column-course">コース</div>
                    </div>
                    
                    <?php while ($row = $stmt_csv_table->fetch()): ?>
                    <div class="table-row">
                        <div class="column-check">
                        </div>
                        <div class="column-student-id">
                            <input type="text" value=<?php echo htmlspecialchars($row['student_id']); ?> disabled>
                        </div>
                        <div class="column-name">
                            <input type="text" value=<?php echo htmlspecialchars($row['name']); ?> disabled>
                        </div>
                        <div class="column-course">
                            <input type="text" value=<?php echo htmlspecialchars($courses[$row['course_id'] - 1]['course_name']); ?> disabled>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <?php if ($status['error_csv'] === false): ?>
                <form action="..\..\..\app\teacher\student_account_edit_backend\backend_csvdata_upload.php" method="post">
                    <button class="complete-button">追加完了</button>
                </form>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <?php endif; ?>
        </main>
    </div>

    <?php if ($status['error_csv'] === true): 
        $error_stmt = $pdo->prepare($status['csv_error_table_sql']);
        $error_stmt->execute();    
    ?>

    <div class="content-area">
        <div class="error-edit-container">
        <h3>CSVエラーデータ編集</h3>
        <div>
        <form action="..\..\..\app\teacher\student_account_edit_backend\backend_csv_error_student_edit.php" method="post">
        <div class="account-table-container">
                <div class="table-header">
                <div class="column-check"></div> 
                <div class="column-student-id">学生番号</div>
                <div class="column-name">氏名</div>
                <div class="column-course">メールアドレス</div>
            </div>
            <?php while ($error_row = $error_stmt->fetch()): ?>
            <div class="table-row">
                <div class="column-check">
                </div> 
                <div class="column-student-id">
                    <input type="text" 
                           name="students[<?php echo htmlspecialchars($error_row['id']); ?>][student_id]" 
                           value=<?php echo htmlspecialchars($error_row['student_id']); ?>>
                </div> 
                <div class="column-name">
                    <input type="text" 
                           name="students[<?php echo htmlspecialchars($error_row['id']); ?>][name]" 
                           value=<?php echo htmlspecialchars($error_row['name']); ?>>
                </div> 
                <div class="column-course">
                    <input type="text" 
                           name="students[<?php echo htmlspecialchars($error_row['id']); ?>][approvalUserAddress]" 
                           value=<?php echo htmlspecialchars($error_row['approvalUserAddress']); ?>>
                </div>
                <input type="hidden" 
                       name="students[<?php echo htmlspecialchars($error_row['id']); ?>][id]" 
                       value=<?php echo htmlspecialchars($error_row['id']); ?>>
                <input type="hidden" 
                       name="students[<?php echo htmlspecialchars($error_row['id']); ?>][course_id]" 
                       value=<?php echo htmlspecialchars($error_row['course_id']); ?>>
            </div>
            <?php endwhile; ?>
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

    <script src="js/script.js"></script>
</body>
</html>