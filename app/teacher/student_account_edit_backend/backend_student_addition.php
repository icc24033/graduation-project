<?php
//セッション開始
session_start();


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

//テストstudentに格納されている学生情報の数の取得
$student_count_sql = ("SELECT COUNT(*)  FROM test_student WHERE LEFT(student_id, 2) = ?;");

$_SESSION['student_account'] = [
    'success' => true,
    'backend' => 'student_addition',
    'before' => 'teacher_home',
    'database_connection' => $dsn,
    'database_user_name' => $user_name,
    'database_user_pass' => $user_pass,
    'database_options' => $options, 
    'student_count_sql' => $student_count_sql 
];


// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../../../public/teacher/student_account_edit/student_addition.php");
exit(); // リダイレクト後は必ず処理を終了

?>
