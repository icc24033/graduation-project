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

//studentに格納されているgradeの変更
$update_grade_sql = ("UPDATE student SET grade = ? WHERE student_id = ?");
try {
    //データベース接続
    $pdo = new PDO($dsn, $user_name, $user_pass, $options);
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
    'database_connection' => $dsn,
    'database_user_name' => $user_name,
    'database_user_pass' => $user_pass,
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