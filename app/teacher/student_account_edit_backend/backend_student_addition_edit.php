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
$insert_student_sql = ("INSERT IGNORE INTO student (student_id, student_mail, student_name, course_id, grade) VALUES (?, ?, ?, ?, 1);");

//csv_tableから取得した学生情報をstudent_login_tableに格納するSQLクエリ
$insert_student_login_sql = ("INSERT INTO student_login_table (id, student_id, user_grade) VALUES (?, ?, "student@icc_ac.jp");");

//csv_tableから取得した学生情報を1行ずつ削除するSQLクエリ
$delete_csv_table_sql = ("DELETE FROM csv_table WHERE id = ?;");

?>