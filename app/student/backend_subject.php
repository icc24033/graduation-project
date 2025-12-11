<?php
//セッション開始
session_start();

// データベース接続情報
$dsn = "mysql:host=localhost;dbname=test;charset=utf8mb4"; 
$user = "root";
$pass = "root";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];


$target_table = 'subject'; // デフォルト

// 選択されたテーブルから、その曜日のデータを全件取得
// 1時間目から4時間目などを並び順(ASC)で取得
$subject_sql = "SELECT * FROM {$target_table} WHERE day_of_week = :day ORDER BY period ASC;";

$_SESSION['subject'] = [
    'success' => true,
    'dsn' => $dsn,
    'user' => $user,
    'pass' => $pass,
    'options' => $options,
    'subject_sql' => $subject_sql
];

// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../../public/student/student_home.php");
exit(); // リダイレクト後は必ず処理を終了

?>