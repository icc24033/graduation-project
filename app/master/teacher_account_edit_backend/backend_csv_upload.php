<?php
// backend_csv_upload.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php'; // 追加

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

// CSRF検証を追加
if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

try {
    require_once __DIR__ . '/../../../app/classes/repository/RepositoryFactory.php';
    $pdo = RepositoryFactory::getPdo();

    // テーブル初期化
    $pdo->exec("DROP TABLE IF EXISTS temp_teacher_csv;");
    $pdo->exec("CREATE TABLE temp_teacher_csv (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), email VARCHAR(100));");
    $pdo->exec("DROP TABLE IF EXISTS error_teacher_table;");
    $pdo->exec("CREATE TABLE error_teacher_table (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), email VARCHAR(100), error_msg VARCHAR(255));");

} catch (PDOException $e) {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
    
    $tmp_file = $_FILES['csvFile']['tmp_name'];

    // 1. ファイルを丸ごと読み込む
    $raw_content = file_get_contents($tmp_file);
    
    // 2. 文字コードを UTF-8 に一括変換
    $utf8_content = mb_convert_encoding($raw_content, 'UTF-8', 'ASCII,JIS,UTF-8,CP932,SJIS-win');
    
    // 3. 改行コードを統一して、行ごとに配列に分割
    $utf8_content = str_replace(["\r\n", "\r"], "\n", $utf8_content);
    $lines = explode("\n", $utf8_content);

    $stmt_insert = $pdo->prepare("INSERT INTO temp_teacher_csv (name, email) VALUES (?, ?)");
    $stmt_error = $pdo->prepare("INSERT INTO error_teacher_table (name, email, error_msg) VALUES (?, ?, ?)");

    $loop_count = 0;

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue; // 空行を飛ばす

        // 4. カンマで分割 (str_getcsv を使用)
        $data = str_getcsv($line);

        $loop_count++;
        // mb_convert_kana で全角スペースも除去
        $name = isset($data[0]) ? trim(mb_convert_kana($data[0], "s")) : '';
        $email = isset($data[1]) ? trim($data[1]) : '';
        $error_reason = "";

        // --- バリデーション ---
        if (empty($name)) {
            $error_reason = "氏名が空です。";
        } 
        elseif (empty($email)) {
            $error_reason = "メールアドレスが空です。";
        }
        // ドメインチェック（小文字に揃えて判定）
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@isahaya-cc\.ac\.jp$/i', $email)) {
            $error_reason = "ドメイン不正: @isahaya-cc.ac.jp 以外は登録不可";
        }

        if ($error_reason === "") {
            if ($stmt_insert->execute([$name, $email])) {
                // 成功
            } else {
                $stmt_error->execute([$name, $email, "DB挿入失敗"]);
            }
        } else {
            $stmt_error->execute([$name, $email, $error_reason]);
        }
    }

    $_SESSION['upload_result'] = [
        'processed' => $loop_count,
        'success' => $pdo->query("SELECT COUNT(*) FROM temp_teacher_csv")->fetchColumn(),
        'error' => $pdo->query("SELECT COUNT(*) FROM error_teacher_table")->fetchColumn()
    ];
    
} else {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

header("Location: ../../../public/master/teacher_account_edit/controls/teacher_addition_control.php?backend=csv_upload");
exit();