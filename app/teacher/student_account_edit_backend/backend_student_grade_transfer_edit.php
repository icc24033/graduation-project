<?php

$grade_changes = $_POST['grade_changes'] ?? [];

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

//studentに格納されているgradeの変更
$update_grade_sql = ("UPDATE student SET grade = ? WHERE student_id = ?");

try {
    // RepositoryFactoryを使用してPDOインスタンスを取得
    require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
    $pdo = RepositoryFactory::getPdo();

    //studentテーブルの更新
    $stmt_update = $pdo->prepare($update_grade_sql);

    foreach ($grade_changes as $student_id => $new_grade) {
        // update_grade_sqlのWHERE句のstudent_idに対応するレコードのgradeを更新

        //$new_grade = (int)$new_grade; // 学年を整数に変換
        $stmt_update->execute([$new_grade, $student_id]);
    }
    // データベース接続を閉じる
    $pdo = null;

} 
catch (PDOException $e) {
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
    'course_sql' => $course_sql,
    'course_id' => $received_course_id,
    'student_sql' => $student_sql,
    'current_year' => $received_current_year
];

// ★ student_addition.php にリダイレクトして処理を終了
header("Location: ../../../public/teacher/student_account_edit/student_grade_transfer.php");
exit(); // リダイレクト後は必ず処理を終了

?>