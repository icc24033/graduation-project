<?php

$grade_changes = $_POST['grade_changes'] ?? [];


// $grade_changes をvar_dumpで確認
var_dump($grade_changes);
//array(2) { [24002]=> string(1) "2" [24003]=> string(1) "2" }

// 2. デコードが成功し、かつ 'course_id' と'current_year'が存在するかチェック
if (isset($_POST['course_id']) && isset($_POST['current_year'])) {
    // コースIDを取得
    $received_course_id = $_POST['course_id'];
    // 年度の取得
    $received_current_year = $_POST['current_year'];
} else {
    // データ受信に失敗した場合
    $received_course_id = 1; // デフォルト値を設定（例: 1）

    $received_current_year = date("Y"); 
    
    // $received_current_year の下2桁を取得
    $received_current_year = substr($received_current_year, -2);
}

$config_path = __DIR__ . '/../../../config/secrets_local.php';

$config = require $config_path;

define('DB_HOST', $config['db_host']);
define('DB_NAME', $config['db_name']);
define('DB_USER', $config['db_user']);
define('DB_PASS', $config['db_pass']);

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

//studentに格納されているgradeの変更
$update_grade_sql = ("UPDATE student SET grade = ? WHERE student_id = ?");
try {
    //データベース接続
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    //studentテーブルの更新
    $stmt_update = $pdo->prepare($update_grade_sql);

    foreach ($grade_changes as $student_id => $new_grade) {
        // update_grade_sqlのWHERE句のstudent_idに対応するレコードのgradeを更新

        //$new_grade = (int)$new_grade; // 学年を整数に変換
        $stmt_update->execute([$new_grade, $student_id]);
    }

} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

//コース情報取得SQLクエリ
$course_sql = ("SELECT * FROM course;");
//テストstudentに格納されている学生情報の取得
$student_sql = ("SELECT 
                    S.student_id,
                    S.student_name,
                    S.course_id,
                    C.course_name 
                FROM
                    student AS S 
                INNER JOIN 
                    course AS C 
                ON 
                    S.course_id = C.course_id 
                WHERE 
                    S.course_id = ?;"
                );

$_SESSION['student_account'] = [
    'success' => true,
    'before' => 'teacher_home',
    'database_options' => $options,
    'course_sql' => $course_sql,
    'course_id' => $received_course_id,
    'student_sql' => $student_sql,
    'current_year' => $received_current_year
];

// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../../../public/teacher/student_account_edit/student_grade_transfer.php");
exit(); // リダイレクト後は必ず処理を終了

?>