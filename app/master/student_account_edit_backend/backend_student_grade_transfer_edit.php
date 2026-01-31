<?php
// student_grade_transfer_edit.php

// ★ セッション開始とSecurityHelperの読み込み
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';

// ★ POSTリクエストの検証
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

// ★ CSRFトークンの検証
if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

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
    header("Location: ../../../public/login/connection_error.html");
    exit();
}

// ★ student_account_transfer_control.php にリダイレクトして処理を終了
header("Location: ../../../public/master/student_account_edit/controls/student_account_transfer_control.php");
exit(); // リダイレクト後は必ず処理を終了

?>