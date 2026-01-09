<?php

//セッション開始
session_start();

// ----------------------------------------------------
// 【重要】JavaScriptから送信されたコースIDを取得する処理
// ----------------------------------------------------


$received_course_id = null;
$message = "コースIDは受信されませんでした。";

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

// POSTで受けとった値を変数に格納
$selected_student = $_POST['students'] ?? [];

//studentに格納されているcourse_idとcourse_nameの変更
$update_course_sql = ("UPDATE student SET course_id = ? WHERE student_id = ?");

try {
    // RepositoryFactoryを使用してPDOインスタンスを取得
    require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
    $pdo = RepositoryFactory::getPdo();

    //studentテーブルの更新
    $stmt_update = $pdo->prepare($update_course_sql);

    foreach ($selected_student as $student_id => $course_id) {
        // update_course_sqlのWHERE句のstudent_idに対応するレコードのcourse_idを更新

        ////$course_id = (int)$course_id; // コースIDを整数に変換
        $stmt_update->execute([$course_id, $student_id]);
    }
    
    // データベース接続を閉じる
    $pdo = null; 

}
catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// ★ student_account_course_control.php にリダイレクトして処理を終了
header("Location: ../../../public/master/student_account_edit/controls/student_account_course_control.php");
exit(); // リダイレクト後は必ず処理を終了
?>