<?php
//セッション開始
session_start();

// ----------------------------------------------------
// 【重要】JavaScriptから送信されたコースIDを取得する処理
// ----------------------------------------------------


$received_course_id = null;
$message = "コースIDは受信されませんでした。";

// 2. デコードが成功し、かつ 'course_id' と'current_year'が存在するかチェック
if (isset($_GET['course_id']) && isset($_GET['current_year'])) {
    // コースIDを取得
    $received_course_id = $_GET['course_id'];
    // 年度の取得
    $received_current_year = $_GET['current_year'];
} else {
    // データ受信に失敗した場合
    $received_course_id = 1; // デフォルト値を設定（例: 1）

    $received_current_year = date("Y"); 
    
    // $received_current_year の下2桁を取得
    $received_current_year = substr($received_current_year, -2);
}

//コース情報取得SQLクエリ
$course_sql = ("SELECT * FROM course;");
//studentテーブルに格納されている学生情報の取得
$student_sql = ("SELECT 
                    S.student_id,
                    S.student_name,
                    S.course_id,
                    S.grade,
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
    'course_sql' => $course_sql,
    'course_id' => $received_course_id,
    'student_sql' => $student_sql,
    'current_year' => $received_current_year
];

// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../../../public/teacher/student_account_edit/student_delete.php");
exit(); // リダイレクト後は必ず処理を終了

?>
