<?php
// backend_update_master.php

// --- デバッグ用：エラーを表示させる設定（解決したら削除してください） ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';

// 1. POSTリクエストとCSRFトークンの検証
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('不正なアクセスです。');
}

if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    die('CSRFトークンが無効です。');
}

// 2. 画面から送信された「現在チェックが入っているID」のリスト
$submittedIds = isset($_POST['teacher_ids']) ? array_map('intval', $_POST['teacher_ids']) : [];

try {
    // 3. DB接続
    // RepositoryFactoryを使用してPDOインスタンスを取得
    require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
    $pdo = RepositoryFactory::getPdo();

    // 4. 現在の状態をすべて取得して比較・更新
    $stmt = $pdo->query("SELECT teacher_id, master_flg FROM teacher");
    $currentStates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateStmt = $pdo->prepare("UPDATE teacher SET master_flg = :flg WHERE teacher_id = :id");

    foreach ($currentStates as $row) {
        $id = (int)$row['teacher_id'];
        $currentFlg = (int)$row['master_flg'];

        // 送信データに含まれていれば 1、なければ 0
        $newFlg = in_array($id, $submittedIds) ? 1 : 0;

        // 変更がある場合のみ更新を実行
        if ($currentFlg !== $newFlg) {
            $updateStmt->execute([
                ':flg' => $newFlg,
                ':id'  => $id
            ]);
        }
    }

    // 5. 完了後に元のページへ戻る（パラメータなし）
    header("Location: ../../../public/master/teacher_account_edit/controls/master_edit_control.php");
    exit;

} catch (PDOException $e) {
    die("データベースエラーが発生しました。");
}