<?php
// セッション開始
session_start();

// セッションから処理結果を取得
$status = $_SESSION['student_account'] ?? null;

// セッションデータを取得したらすぐに削除 (二重表示防止のため)
////unset($_SESSION['student_account']);

// ★ 追加: コース名変数の初期化 (DB接続失敗時でもエラーを防ぐため)
$current_course_name = 'コースを選択してください';
$course = []; // コースデータを格納する配列を初期化

try {
    //データベース接続
    $pdo = 
        new PDO(
            $status['database_connection'],
            $status['database_user_name'],
            $status['database_user_pass'],
            $status['database_options']
        );

    //　リストに表示するコース情報を取得
    $stmt_course = $pdo->query($status['course_sql']);
    $course = $stmt_course->fetchAll(); // ここで取得されるのは連想配列の配列

    // 現在のコース名の初期値を設定 (最初の要素の 'course_name' を使用)
    if (!empty($course)) {
        // 連想配列のキーを指定して値を取得
        $current_course_name = $course[0]['course_name'];
    } else {
        $current_course_name = 'コース情報が見つかりません';
    }
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
                    <li class="nav-item is-group-label">年度</li> 
                    <li class="nav-item has-dropdown">
                        <button class="dropdown-toggle" id="yearDropdownToggle" aria-expanded="false">
                            <span class="current-value">2025年度</span>
                        </button>
                        <ul class="dropdown-menu" id="yearDropdownMenu">
                            <li><a href="#" data-year="2026">2026年度</a></li>
                            <li><a href="#" data-year="2025">2025年度</a></li>
                            <li><a href="#" data-year="2024">2024年度</a></li>
                            <li><a href="#" data-year="2023">2023年度</a></li>
                        </ul>
                    </li>
            
                    <li class="nav-item is-group-label">コース</li> 
                    <li class="nav-item has-dropdown">
                        <button class="dropdown-toggle" id="courseDropdownToggle" aria-expanded="false">
                            <span class="current-value"><?php echo htmlspecialchars($current_course_name); ?></span>
                        </button>
                        <ul class="dropdown-menu" id="courseDropdownMenu">
                            <?php if (!empty($course)): ?>
                                <?php foreach ($course as $row): ?>
                                    <li>
                                        <!-- data-courseには course_id の値を、表示名には course_name の値を指定 -->
                                        <a href="#" data-course="<?php echo htmlspecialchars($row['course_id']); ?>">
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
                    <li class="nav-item"><a href="student_addition.html">アカウントの追加</a></li>
                    <li class="nav-item"><a href="student_delete.html">アカウントの削除</a></li>
                    <li class="nav-item"><a href="student_grade_transfar.html">学年の移動</a></li>
                    <li class="nav-item"><a href="student_edit_course.html">コースの編集</a></li>
                </ul>
                
                <form action="..\..\app\csv_upload.php" method="post" enctype="multipart/form-data" class="download-form" id="uploadForm">
                    <div class="file-upload-wrapper">
                        <input type="file" id="csvFile" name="csvFile" accept=".csv" required class="visually-hidden" onchange="autoSubmitForm()">
                        <label for="csvFile" class="custom-file-upload-button">
                            <span class="material-symbols-outlined">upload</span> 名簿ダウンロード
                        </label>
                    </div>
                </form>
                
            </nav>
            <div class="content-area">
                <div class="account-table-container">
                    <div class="table-header">
                        <div class="column-check"><input type="checkbox" id="selectAllCheckbox"></div> <div class="column-student-id">学生番号</div>
                        <div class="column-name">氏名</div>
                        <div class="column-course">コース</div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20001" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20001"></div> 
                        <div class="column-name"><input type="text" name="name" placeholder="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20002" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20002"></div> 
                        <div class="column-name"><input type="text" name="name" placeholder="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20003" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20003"></div> 
                        <div class="column-name"><input type="text" name="name" placeholder="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20004" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20004"></div> 
                        <div class="column-name"><input type="text" name="name" placeholder="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20005" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20005"></div> 
                        <div class="column-name"><input type="text" name="name" placeholder="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20006" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20006"></div> 
                        <div class="column-name"><input type="text" name="name" placeholder="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20007" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20007"></div> 
                        <div class="column-name"><input type="text" name="name" placeholder="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20008" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20008"></div> 
                        <div class="column-name"><input type="text" name="name" placeholder="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20009" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20009"></div> 
                        <div class="column-name"><input type="text" value="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20010" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20010"></div> 
                        <div class="column-name"><input type="text" value="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20011" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20011"></div> 
                        <div class="column-name"><input type="text" value="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20012" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20012"></div> 
                        <div class="column-name"><input type="text" value="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20013" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20013"></div> 
                        <div class="column-name"><input type="text" value="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20014" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20014"></div> 
                        <div class="column-name"><input type="text" value="氏名"></div> 
                        <div class="column-course">
                            <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                        </div>
                    </div>
                    <div class="table-row">
                        <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="20015" data-student-name="氏名"></div> 
                        <div class="column-student-id"><input type="text" value="20015"></div> 
                        <div class="column-name"><input type="text" value="氏名"></div> 
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