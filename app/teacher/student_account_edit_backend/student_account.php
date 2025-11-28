<?php
//セッション開始
session_start();



// ----------------------------------------------------
// 【重要】JavaScriptから送信されたコースIDを取得する処理
// ----------------------------------------------------

// 1. HTTPリクエストのボディから生のJSONデータを取得
$json_data = file_get_contents('php://input');
$decoded_data = json_decode($json_data, true); // true を指定して連想配列に変換

$received_course_id = null;
$message = "コースIDは受信されませんでした。";

// 2. デコードが成功し、かつ 'course_id' が存在するかチェック
if (is_array($decoded_data) && isset($decoded_data['course_id'])) {
    $received_course_id = $decoded_data['course_id'];
    $message = "コースID「{$received_course_id}」を正常に受信しました。";
    
    // 取得したコースIDをセッションに一時的に保存し、リダイレクト後のページで確認できるようにする (デバッグ用)
    $_SESSION['last_received_course_id'] = $received_course_id;
} else {
    // データ受信に失敗した場合
    $_SESSION['last_received_course_id'] = '受信失敗';
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

$_SESSION['student_account'] = [
    'success' => true,
    'before' => 'teacher_home',
    'database_connection' => $dsn,
    'database_user_name' => $user_name,
    'database_user_pass' => $user_pass,
    'database_options' => $options,
    'course_sql' => $course_sql
];

// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../../../public/teacher/student_account_edit/student_addition.php");
exit(); // リダイレクト後は必ず処理を終了

?>