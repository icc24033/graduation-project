<?php
// --- デバッグ用：エラーを表示させる設定（本番公開時は削除または 0 にしてください） ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// SecurityHelperとRepositoryFactoryの読み込み（パスは環境に合わせて調整してください）
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

// 1. POSTリクエストとCSRFトークンの検証
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('不正なアクセスです。');
}

if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    die('CSRFトークンが無効です。');
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
    // 3. DB接続
    $pdo = RepositoryFactory::getPdo();

    // 4. 削除実行
    // 効率化のため WHERE IN を使用して一括削除
    $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
    $sql = "DELETE FROM teacher WHERE teacher_id IN ($placeholders)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($targetIds);

    // 5. 完了後に元のページへ戻る
    header("Location: ../../../public/master/teacher_account_edit/controls/teacher_delete_control.php");
    exit;

} catch (PDOException $e) {
    // ログ出力などを行うのが望ましい
    die("データベースエラーが発生しました。");
}