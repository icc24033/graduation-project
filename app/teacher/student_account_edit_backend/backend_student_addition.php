<?php
//セッション開始
session_start();

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

//studentに格納されている学生情報の数の取得
$student_count_sql = ("SELECT COUNT(*)  FROM student WHERE LEFT(student_id, 2) = ?;");

//コース情報取得SQLクエリ
$course_sql = ("SELECT * FROM course;");

$_SESSION['student_account'] = [
    'success' => true,
    'backend' => 'student_addition',
    'error_csv' => false,
    'before' => 'teacher_home',
    'database_options' => $options, 
    'student_count_sql' => $student_count_sql,
    'course_sql' => $course_sql
];


// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../../../public/teacher/student_account_edit/student_addition.php");
exit(); // リダイレクト後は必ず処理を終了

?>
