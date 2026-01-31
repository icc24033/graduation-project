<?php
// backend_delete_teacher.php

// SecurityHelperとRepositoryFactoryの読み込み（パスは環境に合わせて調整してください）
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

// 1. POSTリクエストとCSRFトークンの検証
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

// 2. 削除対象のIDリストを取得
// 送信されていない、または空の場合は空配列にする
$targetIds = isset($_POST['teacher_ids']) ? (array)$_POST['teacher_ids'] : [];

// 削除対象がない場合は、何もせず元のページへ戻す
if (empty($targetIds)) {
    header("Location: ../../../public/master/teacher_account_edit/controls/teacher_delete_control.php"); // ※適切な戻り先に変更してください
    exit;
}

// 整数値に変換して安全性を確保
$targetIds = array_map('intval', $targetIds);

try {
    $pdo = RepositoryFactory::getPdo();

    // 1. 現在のマスタ権限保持者のIDをすべて取得
    $stmtMaster = $pdo->query("SELECT teacher_id FROM teacher WHERE master_flg = 1");
    $allMasterIds = $stmtMaster->fetchAll(PDO::FETCH_COLUMN, 0);

    // 2. 削除対象の中にマスタが何人含まれているか確認
    $mastersToBeDeleted = array_intersect($allMasterIds, $targetIds);

    // 3. もし「全員」または「マスタ全員」を消そうとしていたらブロック
    if (count($mastersToBeDeleted) >= count($allMasterIds)) {
        // エラーメッセージ付きでリダイレクト（あるいはdie）
        header("Location: ../../../public/master/teacher_account_edit/controls/teacher_delete_control.php?status=error_master");
        exit;
    }

    // 4. 削除実行 (既存の処理)
    $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
    $sql = "DELETE FROM teacher WHERE teacher_id IN ($placeholders)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($targetIds);

    // 5. 完了後に元のページへ戻る
    header("Location: ../../../public/master/teacher_account_edit/controls/teacher_delete_control.php?status=success");
    exit;
    
} catch (PDOException $e) {
    // ログ出力などを行うのが望ましい
    header("Location: ../../../public/login/connection_error.html");
    exit;
}