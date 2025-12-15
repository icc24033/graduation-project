<?php

// require_once __DIR__ . '/../session/session_config.php'; // セッション設定を読み込む

// セッション開始
session_start();

// セッションから処理結果を取得
$status = $_SESSION['student_account'] ?? null;

if ($status['backend'] === 'student_addition') {
    // 現在の年度の取得
    $current_year = date("Y");
    $current_year = (int)substr($current_year, -2); // 下2桁を取得

    try {
        //データベース接続
        $pdo = 
            new PDO(
                $status['database_connection'],
                $status['database_user_name'],
                $status['database_user_pass'],
                $status['database_options']
            );

        // 　テストstudentに格納されている今年度の学生数の取得
        $stmt_test_student = $pdo->prepare($status['student_count_sql']);
        $stmt_test_student->execute([$current_year]);
        $student_count = $stmt_test_student->fetchColumn();
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
        $pdo = 
            new PDO(
                $status['database_connection'],
                $status['database_user_name'],
                $status['database_user_pass'],
                $status['database_options']
            );

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

            <div class="content-area">
                <div class="account-table-container">
                    <div class="table-header">
                        <div class="column-check"></div> <div class="column-student-id">学生番号</div>
                        <div class="column-name">氏名</div>
                        <div class="column-course">コース</div>
                    </div>
                    
                    <?php  if ($status['backend'] === 'student_addition'): ?>
                    <div class="table-row">
                        <div class="column-check">
                        </div> 
                        <div class="column-student-id">
                            <input type="text" value=<?php echo htmlspecialchars($student_count + 1 + ($current_year * 1000)); ?>>
                        </div> 
                        <div class="column-name">
                            <input type="text" name="name" placeholder="氏名">
                        </div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>

                    <?php  elseif ($status['backend'] === 'csv_upload'): 
                        while ($row = $stmt_csv_table->fetch()):
                    ?>
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
                    <?php  endwhile; ?>
                    <?php  endif; ?>
                </div>

                <?php if ($status['backend'] === 'student_addition'): ?>
                <div class="button-group">
                    <button class="add-button">追加</button>
                    <button class="add-button">追加人数入力</button>
                </div>
                <button class="complete-button">完了</button>

                <?php elseif ($status['backend'] === 'csv_upload' && $status['error_csv'] === false): ?>
                    <form action="..\..\..\app\teacher\student_account_edit_backend\backend_student_addition_edit.php" method="post">
                        <button class="complete-button">追加完了</button>
                    </form>
                <?php endif; ?>
            </div>
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
                <div class="column-check"></div> <div class="column-student-id">学生番号</div>
                <div class="column-name">氏名</div>
                <div class="column-course">コース</div>
            </div>
                    
            <div class="table-row">
                <div class="column-check">
                </div> 
                <div class="column-student-id">
                    <input type="text" value=<?php echo htmlspecialchars($student_count + 1 + ($current_year * 1000)); ?>>
                </div> 
                <div class="column-name">
                    <input type="text" name="name" placeholder="氏名">
                </div> 
                <div class="column-course">
                    <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                </div>
                <div class="column-mail-address">
                    <input type="text" name="mail-address" placeholder="メールアドレス">
                </div> 
            </div>
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