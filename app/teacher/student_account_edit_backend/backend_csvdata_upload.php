<?php

// セッション開始
session_start();

$config_path = __DIR__ . '/../../../config/secrets_local.php';

$config = require $config_path;

define('DB_HOST', $config['db_host']);
define('DB_NAME', $config['db_name']);
define('DB_USER', $config['db_user']);
define('DB_PASS', $config['db_pass']);

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

//今の月を取得し、4月より前か後かで学年を決定
$current_month = date('n');
if ($current_month >= 4) {
    $grade = 1; // 4月以降は1年生
} else {
    $grade = 0; // 3月以前は2年生
}

//csv_tableに格納されている学生の取得
$csv_table_student_sql = ("SELECT * FROM csv_table;");

//csv_tableから取得した学生情報を格納するSQLクエリ
$insert_student_sql = ("INSERT IGNORE INTO student (student_id, student_mail, student_name, course_id, grade) VALUES (?, ?, ?, ?, ?);");

//csv_tableから取得した学生情報をstudent_login_tableに格納するSQLクエリ
$insert_student_login_sql = ("INSERT INTO 
                                student_login_table (id, student_id, user_grade) 
                              SELECT 
                                ?, ?, 'student@icc_ac.jp' 
                              WHERE NOT EXISTS 
                                (SELECT 1 FROM student_login_table WHERE student_id = ?);");

//student_login_tableに格納してある値の数を取得するSQLクエリ
$student_login_count_sql = ("SELECT COUNT(*) FROM student_login_table;");

//csv_tableから取得した学生情報を1行ずつ削除するSQLクエリ
$delete_csv_table_sql = ("DELETE FROM csv_table WHERE student_id = ?;");

//csv_tableに値が格納されているか確認
$count_csv_table_sql = ("SELECT COUNT(*) as count FROM csv_table;");

try {
    //データベース接続
    $db = new PDO($dsn, DB_USER, DB_PASS);
    
    //csv_tableに格納されている学生の取得
    $stmt_select = $db->prepare($csv_table_student_sql);
    $stmt_select->execute();

    //student_login_tableに格納してある値の数を取得
    $stmt_login_count = $db->prepare($student_login_count_sql);
    $stmt_login_count->execute();
    $login_count_result = $stmt_login_count->fetch();

    $total_login_users = $login_count_result['COUNT(*)'] + 1; // 新しいIDの開始点のために1を加算

    //csv_tableから取得した学生情報を1行ずつ処理
    while ($row = $stmt_select->fetch()) {
        //studentテーブルに学生情報を挿入
        $stmt_insert_student = $db->prepare($insert_student_sql);
        $stmt_insert_student->execute([
            $row['student_id'],
            $row['approvalUserAddress'],
            $row['name'],
            $row['course_id'],
            $grade
        ]);

        //student_login_tableに学生情報を挿入
        $stmt_insert_login = $db->prepare($insert_student_login_sql);
        $stmt_insert_login->execute([
            $total_login_users,
            $row['student_id'],
            $row['student_id']
        ]);

        $total_login_users++; // 次のユーザーIDにインクリメント
        
        //csv_tableから取得した学生情報を1行ずつ削除
        $stmt_delete = $db->prepare($delete_csv_table_sql);
        $stmt_delete->execute([$row['student_id']]);

    }

    // csv_tableに値が格納されているか確認
    $stmt_count = $db->prepare($count_csv_table_sql);
    $stmt_count->execute();
    $count_result = $stmt_count->fetch();

} 
catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
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

//csv_tableに格納されている学生情報の取得
$csv_table_student_sql = ("SELECT * FROM csv_table;");
        
//コース情報取得SQLクエリ
$course_sql = ("SELECT * FROM course;");

//studentに格納されている学生情報の数の取得
$student_count_sql = ("SELECT COUNT(*)  FROM student WHERE LEFT(student_id, 2) = ?;");

$_SESSION['student_account'] = [
    'success' => true,
    'backend' => $backend,
    'error_csv' => false,
    'before' => 'teacher_home',
    'csv_table_student_sql' => $csv_table_student_sql,
    'course_sql' => $course_sql,
    'csv_error_table_sql' => $csv_error_table_sql,
    'student_count_sql' => $student_count_sql
];

// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../../../public/teacher/student_account_edit/student_addition.php");
exit(); // リダイレクト後は必ず処理を終了

?>