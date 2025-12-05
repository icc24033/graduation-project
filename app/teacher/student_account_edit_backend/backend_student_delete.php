<?php
//セッション開始
session_start();

// ----------------------------------------------------
// 【重要】JavaScriptから送信されたコースIDを取得する処理
// ----------------------------------------------------


$received_course_id = null;
$message = "コースIDは受信されませんでした。";

// 2. デコードが成功し、かつ 'course_id' と'current_year'が存在するかチェック
if (isset($_GET['course_id']) && isset($_GET['current_year'])) {
    // コースIDを取得
    $received_course_id = $_GET['course_id'];
    // 年度の取得
    $received_current_year = $_GET['current_year'];
} else {
    // データ受信に失敗した場合
    $received_course_id = 1; // デフォルト値を設定（例: 1）

    $received_current_year = date("Y"); 
    
    // $received_current_year の下2桁を取得
    $received_current_year = substr($received_current_year, -2);
}


//データベース接続情報
$host = 'localhost';
$db_name = 'icc_smart_campus';
$user_name = 'root';
$user_pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

//コース情報取得SQLクエリ
$course_sql = ("SELECT * FROM course;");
//テストstudentに格納されている学生情報の取得
$student_sql = ("SELECT * FROM test_student WHERE course_id = ?;");

$_SESSION['student_account'] = [
    'success' => true,
    'before' => 'teacher_home',
    'database_connection' => $dsn,
    'database_user_name' => $user_name,
    'database_user_pass' => $user_pass,
    'database_options' => $options,
    'course_sql' => $course_sql,
    'course_id' => $received_course_id,
    'student_sql' => $student_sql,
    'current_year' => $received_current_year
];

// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../../../public/teacher/student_account_edit/student_delete.php");
exit(); // リダイレクト後は必ず処理を終了

?>
