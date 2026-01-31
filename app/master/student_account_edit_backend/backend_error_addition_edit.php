<?php
// セッション開始
if (session_status() === PHP_SESSION_NONE) session_start();

// SecurityHelperの読み込み（パスは各ファイルから適切に合わせてください）
require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';

// ★ CSRFトークンの検証
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    // セッション切れなどの場合に備え、エラーメッセージを出して終了
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

$errorRepository = RepositoryFactory::getErrorStudentRepository();
$studentRepository = RepositoryFactory::getStudentRepository();
$studentLoginRepository = RepositoryFactory::getStudentLoginRepository();

if (isset($_POST['students']) && is_array($_POST['students'])) {

    // --- 年度判定ロジック ---
    $current_year = (int)date('y'); // 下2桁 (例: 26)
    $current_month = (int)date('n');

    //今の月を取得し、4月より前か後かで学年を決定
    if ($current_month >= 4) {
        $grade = 1; // 4月以降は1年生
    } else {
        $grade = 0; // 3月以前は0年生
    }

    if ($current_month < 4) {
        $school_year = [ $current_year, $current_year - 1, $current_year - 2 ];             
    } else {
        $school_year = [ $current_year, $current_year - 1 ];
    }

    foreach ($_POST['students'] as $key => $student_data) {
        
        // POSTデータの受け取り
        $id         = $student_data['id']; 
        $student_id = $student_data['student_id']; 
        $name       = $student_data['name']; 
        $email      = $student_data['approvalUserAddress']; 
        $course_id  = $student_data['course_id']; 
        
        // 1. 学生番号形式チェック
        if (ctype_digit($student_id)) {
            $user_id_prefix = floor((int)$student_id / 1000); // 5桁ならここで上2桁が取れる
            
            $flg = 0;
            foreach ($school_year as $year) {
                if ($year == $user_id_prefix) {
                    $flg = 1;
                    $column_user_id = (int)$student_id;
                    break;
                }
            }

            if ($flg == 0) {
                // 学年不正：error_student_tableを更新して次へ
                $errorRepository->updataErrorDataTable($student_id, $name, $email, 1001, $id);
                continue; 
            }
        } else {
            // 形式不正：error_student_tableを更新して次へ
            $errorRepository->updataErrorDataTable($student_id, $name, $email, 1002, $id);
            continue;
        }

        // --- 2. 名前の入力チェック ---
        // trim() で前後の空白を消し、それが空文字かどうか判定します
        if (empty(trim((string)$name))) {
            $error_id = 2001; // 仮：名前未入力エラーID（DBに合わせて調整してください）
            $errorRepository->updataErrorDataTable($student_id, $name, $email, $error_id, $id);
            continue; 
        }

        // 3. メールアドレス形式チェック
        if (preg_match("/^icc$column_user_id@isahaya-cc\\.ac\\.jp$/", $email)) {
            $column_email = $email;
        } else {
            // メール形式不正：error_student_tableを更新して次へ
            $errorRepository->updataErrorDataTable($student_id, $name, $email, 2001, $id);
            continue;
        }

        // --- 4. DB保存処理 (ここが重要) ---
        
        // ① studentテーブルへ保存
        $studentRepository->addStudent(
            $column_user_id,
            $column_email,
            $name,
            $course_id,
            $grade
        );

        // ② student_login_tableへ保存
        // 【修正ポイント】ループの中に移動しました。これで一人ずつ確実に登録されます。
        $studentLoginRepository->addStudentLogin($column_user_id);

        // 格納されたデータをエラーテーブルから削除
        $errorRepository->deleteErrorDataById($id);

        // 【修正ポイント】ループ内で表示することで、誰の処理が成功したかリアルタイムでわかります。
        echo "✅ 学生番号: {$column_user_id} の登録に成功しました。<br>";
    }

    // 全員の処理が終わったらDB接続を閉じる
    RepositoryFactory::closePdo();

    echo "<h3>すべてのデータの処理が完了しました。</h3>";
    
    header("Location: ../../../public/master/student_account_edit/controls/student_account_edit_control.php?backend=student_addition");
    exit();

} else {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}