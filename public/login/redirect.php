<?php
// redirect.php
// ブラウザの戻るボタン対策用の中間ページ

// 0. SecurityHelper.php の呼び出し
require_once __DIR__ . '/../../app/classes/security/SecurityHelper.php';
SecurityHelper::applySecureHeaders();

// セッション開始
SecurityHelper::requireLogin();

// 権限に応じたリダイレクト
$grade = $_SESSION['user_grade'] ?? '';

switch ($grade) {
    case 'teacher@icc_ac.jp':
        header('Location: ../teacher/teacher_home.php');
        exit();
        
    case 'master@icc_ac.jp':
        header('Location: ../master/master_home_control.php');
        exit();
        
    default:
        // 想定外のGrade、またはセッション切れ
        header('Location: login_error.html');
        exit();
}