<?php
// master_edit_control.php
require_once __DIR__ . '/../../../../app/session/session_config.php'; // セッション設定を読み込む

require_once '../../../../app/classes/security/SecurityHelper.php';
// セキュリティヘッダーの適用
SecurityHelper::applySecureHeaders();

// セッション開始とログイン判定を一括で行う
SecurityHelper::requireLogin();

require_once '../../../../app/controllers/master/teacher_account_editers/TeacherAccountEditController.php';

$controller = new TeacherAccountEditController();
$controller->index_addition();