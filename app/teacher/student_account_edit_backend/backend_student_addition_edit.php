<?php

// セッション開始
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

//csv_tableに格納されている学生の取得
$csv_table_student_sql = ("SELECT * FROM csv_table;");

//csv_tableから取得した学生情報を格納するSQLクエリ
$insert_student_sql = ("INSERT INTO test_student

?>