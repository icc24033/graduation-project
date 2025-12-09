<?php

// require_once __DIR__ . '/../session/session_config.php'; // セッション設定を読み込む

// セッション開始
session_start();

// セッションから処理結果を取得
$status = $_SESSION['student_account'] ?? null;


// 現在の年度の取得
$current_year = date("Y");
$current_year = (int)substr($current_year, -2); // 下2桁を取得

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
    //データベース接続
    $pdo = 
        new PDO(
            $status['database_connection'],
            $status['database_user_name'],
            $status['database_user_pass'],
            $status['database_options']
        );

    // 　テストstudentに格納されている学生情報の取得
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
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <title>生徒アカウント作成編集 アカウントの追加</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
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
                    <li class="nav-item is-active"><a href="student_addition.php">アカウントの作成</a></li>
                    <li class="nav-item"><a href="..\..\..\app\teacher\student_account_edit_backend\backend_student_delete.php">アカウントの削除</a></li>
                    <li class="nav-item"><a href="..\..\..\app\teacher\student_account_edit_backend\backend_student_grade_transfer.php">学年の移動</a></li>
                    <li class="nav-item"><a href="..\..\..\app\teacher\student_account_edit_backend\backend_student_course.php">コースの編集</a></li>
                </ul>
                
                <form action="..\..\..\app\teacher\student_account_edit_backend\backend_csv_upload.php" method="post" enctype="multipart/form-data" class="download-form" id="uploadForm">
                    <div class="file-upload-wrapper">
                        <input type="file" id="csvFile" name="csvFile" accept=".csv" required class="visually-hidden" onchange="this.form.submit();">
                        <label for="csvFile" class="custom-file-upload-button">
                            <span class="material-symbols-outlined">upload</span> 名簿ダウンロード
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
                    
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20001" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value=<?php echo htmlspecialchars($student_count + 1 + ($current_year * 1000)); ?>></div> 
                        <div class="column-name"><input type="text" name="name" placeholder="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>

                </div>
                <div class="button-group">
                    <button class="add-button">追加</button>
                    <button class="add-button">追加人数入力</button>
                </div>
                <button class="complete-button">完了</button>
            </div>
        </main>
    </div>
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