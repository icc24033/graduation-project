<?php
echo "aaa";
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

//コース情報取得SQLクエリ
$course_sql = ("SELECT * FROM course;");

$_SESSION['student_account'] = [
    'success' => true,
    'before' => 'teacher_home',
    'database_connection' => $dsn,
    'database_user_name' => $user_name,
    'database_user_pass' => $user_pass,
    'database_options' => $options,
    'course_sql' => $course_sql
];

//試しにSQLクエリ実行
$stmt = new PDO($dsn, $user_name, $user_pass, $options);
$stmt = $stmt->query($course_sql);
var_dump($stmt->fetchAll());
echo "aaa";

// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../public/student/student_addition.php");
exit(); // リダイレクト後は必ず処理を終了

?>