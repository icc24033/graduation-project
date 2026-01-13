<?php
// backend_add_teacher.php

// --- デバッグ用：エラーを表示させる設定 ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// SecurityHelperとRepositoryFactoryの読み込み（既存の階層に合わせて調整）
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

// 1. POSTリクエストとCSRFトークンの検証
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('不正なアクセスです。');
}

if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    die('CSRFトークンが無効です。');
}

// 2. 登録対象データの取得
$names = isset($_POST['teacher_names']) ? (array)$_POST['teacher_names'] : [];
$emails = isset($_POST['teacher_emails']) ? (array)$_POST['teacher_emails'] : [];

// 戻り先のURL（teacher_delete.phpと同じ階層のcontrolを想定）
$redirectUrl = "../../../public/master/teacher_account_edit/controls/teacher_addition_control.php";

if (empty($names)) {
    header("Location: $redirectUrl");
    exit;
}

try {
    $pdo = RepositoryFactory::getPdo();
    $pdo->beginTransaction();

    // A. teacher_id の現在の最大値を取得
    $stmtMax = $pdo->query("SELECT MAX(teacher_id) FROM teacher");
    $maxId = $stmtMax->fetchColumn();
    $nextId = $maxId ? (int)$maxId + 1 : 1;

    // B. 各種ステートメントの準備
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM teacher WHERE teacher_mail = ?");
    $stmtTeacher = $pdo->prepare("INSERT INTO teacher (teacher_id, teacher_mail, teacher_name, master_flg) VALUES (?, ?, ?, 0)");
    $stmtLogin = $pdo->prepare("INSERT INTO teacher_login_table (teacher_id, user_grade) VALUES (?, 'teacher@icc_ac.jp')");

    $allowedDomain = "@isahaya-cc.ac.jp";

    // 3. ループで登録実行
    foreach ($names as $index => $name) {
        $email = trim($emails[$index] ?? '');
        $name = trim($name);

        if ($name === '' || $email === '') continue;

        // ドメインチェック
        if (!str_ends_with($email, $allowedDomain)) {
            throw new Exception("メールアドレス「{$email}」は許可されていないドメインです。{$allowedDomain} のアドレスを入力してください。");
        }

        // 重複チェック
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception("メールアドレス「{$email}」は既に登録されています。");
        }

        // 実行
        $stmtTeacher->execute([$nextId, $email, $name]);
        $stmtLogin->execute([$nextId]);

        $nextId++;
    }

    $pdo->commit();

    // 成功メッセージを付与してリダイレクト
    $msg = urlencode("先生アカウントの登録が正常に完了しました。");
    header("Location: {$redirectUrl}?success_msg={$msg}");
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // エラーメッセージを付与してリダイレクト
    $msg = urlencode($e->getMessage());
    header("Location: {$redirectUrl}?error_msg={$msg}");
    exit;
}