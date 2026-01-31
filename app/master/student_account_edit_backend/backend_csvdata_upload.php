<?php
// --- デバッグ用設定 ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// セッション開始
if (session_status() === PHP_SESSION_NONE) session_start();

// SecurityHelperの読み込み（パスは各ファイルから適切に合わせてください）
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';

// ★ CSRFトークンの検証
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    // セッション切れなどの場合に備え、エラーメッセージを出して終了
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

// 現在の年度の取得
$current_year = date("Y");
$current_year = substr($current_year, -2); // 下2桁を取得

//今の月を取得し、4月より前か後かで学年を決定
$current_month = date('n');

if ($current_month >= 4) {
    $grade = 1; // 4月以降は1年生
} else {
    $grade = 0; // 3月以前は0年生
}

// 学年度の配列を作成
if ($current_month < 4) {
    $school_year = [ $current_year, $current_year - 1, $current_year - 2 ];             
}
else {
    $school_year = [ $current_year, $current_year - 1 ];
}

//csv_tableに格納されている学生の取得
$csv_table_student_sql = ("SELECT * FROM csv_table;");

//csv_tableから取得した学生情報を格納するSQLクエリ
$insert_student_sql = ("INSERT IGNORE INTO student (student_id, student_mail, student_name, course_id, grade) VALUES (?, ?, ?, ?, ?);");

//csv_tableから取得した学生情報をstudent_login_tableに格納するSQLクエリ
$insert_student_login_sql = ("INSERT INTO 
                                student_login_table (student_id, user_grade) 
                              SELECT 
                                ?, 'student@icc_ac.jp' 
                              WHERE NOT EXISTS 
                                (SELECT 1 FROM student_login_table WHERE student_id = ?);");

//student_login_tableに格納してある値の数を取得するSQLクエリ
$student_login_count_sql = ("SELECT COUNT(*) FROM student_login_table;");

//csv_tableから取得した学生情報を1行ずつ削除するSQLクエリ
$delete_csv_table_sql = ("DELETE FROM csv_table WHERE student_id = ?;");

//csv_tableに値が格納されているか確認
$count_csv_table_sql = ("SELECT COUNT(*) as count FROM csv_table;");

try {
    // RepositoryFactoryを使用してPDOインスタンスを取得
    require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
    $pdo = RepositoryFactory::getPdo();

    //csv_tableに格納されている学生の取得
    $stmt_select = $pdo->prepare($csv_table_student_sql);
    $stmt_select->execute();

    //student_login_tableに格納してある値の数を取得
    $stmt_login_count = $pdo->prepare($student_login_count_sql);
    $stmt_login_count->execute();
    $login_count_result = $stmt_login_count->fetch();

    //csv_tableから取得した学生情報を1行ずつ処理
    while ($row = $stmt_select->fetch()) {
        //studentテーブルに学生情報を挿入
        $stmt_insert_student = $pdo->prepare($insert_student_sql);
        $stmt_insert_student->execute([
            $row['student_id'],
            $row['approvalUserAddress'],
            $row['name'],
            $row['course_id'],
            $grade
        ]);

        //student_login_tableに学生情報を挿入
        $stmt_insert_login = $pdo->prepare($insert_student_login_sql);
        $stmt_insert_login->execute([
            $row['student_id'],
            $row['student_id']
        ]);
        
        //csv_tableから取得した学生情報を1行ずつ削除
        $stmt_delete = $pdo->prepare($delete_csv_table_sql);
        $stmt_delete->execute([$row['student_id']]);

    }

    // csv_tableに値が格納されているか確認
    $stmt_count = $pdo->prepare($count_csv_table_sql);
    $stmt_count->execute();
    $count_result = $stmt_count->fetch();

} 
catch (PDOException $e) {
    header("Location: ../../../public/login/connection_error.html");
    exit();
}

if ($count_result['count'] == 0) {
    //csv_tableが空の場合
    $backend = 'student_addition';
    $csv_error_table_sql = null;
}
else {
    //csv_tableにまだ値が格納されている場合
    $backend = 'csv_upload';
    $csv_error_table_sql = null;
}

// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../../../public/master/student_account_edit/controls/student_account_edit_control.php?backend={$backend}");
exit(); // リダイレクト後は必ず処理を終了

?>