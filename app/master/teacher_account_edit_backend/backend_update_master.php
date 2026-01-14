<?php
// backend_update_master.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('不正なアクセスです。');
}

if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    die('CSRFトークンが無効です。');
}

$submittedIds = isset($_POST['teacher_ids']) ? array_map('intval', $_POST['teacher_ids']) : [];

try {
    require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
    $pdo = RepositoryFactory::getPdo();

    // トランザクション開始（両方のテーブルの整合性を保つため）
    $pdo->beginTransaction();

    $stmt = $pdo->query("SELECT teacher_id, master_flg FROM teacher");
    $currentStates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 1. teacherテーブル更新用の準備
    $updateTeacherStmt = $pdo->prepare("UPDATE teacher SET master_flg = :flg WHERE teacher_id = :id");
    
    // 2. teacher_login_table更新用の準備
    $updateLoginStmt = $pdo->prepare("UPDATE teacher_login_table SET user_grade = :grade WHERE teacher_id = :id");

    foreach ($currentStates as $row) {
        $id = (int)$row['teacher_id'];
        $currentFlg = (int)$row['master_flg'];

        // 送信データに含まれていれば 1（マスタ）、なければ 0（一般）
        $newFlg = in_array($id, $submittedIds) ? 1 : 0;
        // master_flgに対応するメール形式のgradeを決定
        $newGrade = ($newFlg === 1) ? 'master@icc_ac.jp' : 'teacher@icc_ac.jp';

        if ($currentFlg !== $newFlg) {
            // teacherテーブルのフラグ更新
            $updateTeacherStmt->execute([
                ':flg' => $newFlg,
                ':id'  => $id
            ]);

            // teacher_login_tableのgrade更新
            $updateLoginStmt->execute([
                ':grade' => $newGrade,
                ':id'    => $id
            ]);
        }
    }

    $pdo->commit();

    header("Location: ../../../public/master/teacher_account_edit/controls/master_edit_control.php?status=success");
    exit;

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    die("データベースエラーが発生しました。");
}