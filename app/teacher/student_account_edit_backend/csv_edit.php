<?php

session_start();

//エラーデータ保存用テーブル作成
$sql_delete_error_table = "DROP TABLE IF EXISTS error_student_table;";
//ーーーーーーCSVデータの書式が確定していないので後回しーーーーーーーーーーーーーーーーーーーー
//↓user_idをVARCHAR型にしてるのは、不正な形式のユーザーIDも格納するため
$sql_create_error_table = 
    "CREATE TABLE error_student_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(100),
    name VARCHAR(100),
    approvalUserAddress VARCHAR(100),
    error_id INT,
    course_id INT
);";

//error_idの外部キー設定
$sql_error_id_foreign_key = 
    "ALTER TABLE error_student_table
    ADD CONSTRAINT fk_error_id
    FOREIGN KEY (error_id) REFERENCES error_table(error_id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION;
    ";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // RepositoryFactoryを使用してPDOインスタンスを取得
        require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
        $pdo = RepositoryFactory::getPdo();


        
        

?>