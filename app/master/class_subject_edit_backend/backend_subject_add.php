<?php
// backend_subject_add.php
// セッション開始
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../app/classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../services/master/ClassSubjectEditService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

// ★ CSRFトークンの検証
if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

try {
    $pdo = RepositoryFactory::getPdo();
} catch (Exception $e) {
    header("Location: ../../../public/login/connection_error.html");
    exit;
}

$service = new ClassSubjectEditService();
$courseInfo = $service->getCourseInfoMaster();

$action     = $_POST['action'] ?? '';
$raw_grade  = $_POST['grade'] ?? '';
$course_id  = $_POST['course'] ?? '';
$title      = $_POST['title'] ?? '';

if ($action === 'insert_new' && !empty($title)) {
    try {
        $pdo->beginTransaction();

        // 1. subjectsテーブルの重複チェック（科目名）
        $stmtCheck = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_name = ? LIMIT 1");
        $stmtCheck->execute([$title]);
        $existing_subject_id = $stmtCheck->fetchColumn();

        if ($existing_subject_id) {
            $target_subject_id = $existing_subject_id;
        } else {
            $sqlSubject = "INSERT INTO subjects (subject_name) VALUES (?)";
            $stmt = $pdo->prepare($sqlSubject);
            $stmt->execute([$title]);
            $target_subject_id = $pdo->lastInsertId();
        }

        // 2. 登録対象コースの選定
        $targets = [];
        if ($raw_grade === '1_all') {
            foreach ($courseInfo as $info) { if ($info['grade'] == 1) $targets[] = $info; }
        } elseif ($raw_grade === '2_all') {
            foreach ($courseInfo as $info) { if ($info['grade'] == 2) $targets[] = $info; }
        } elseif ($raw_grade === 'all') {
            foreach ($courseInfo as $info) { $targets[] = $info; }
        } else {
            if (isset($courseInfo[$course_id])) { $targets[] = $courseInfo[$course_id]; }
        }

        // 3. subject_in_chargesテーブルへの登録（重複防止ロジック追加）
        // すでに「同じコースID + 同じ科目ID」の組み合わせがあるか確認するSQL
        $sqlExist = "SELECT COUNT(*) FROM subject_in_charges 
                     WHERE course_id = :cid AND subject_id = :sid";
        $stmtExist = $pdo->prepare($sqlExist);

        $sqlInsert = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) 
                      VALUES (:cid, :grade, :sid, NULL, NULL)";
        $stmtInsert = $pdo->prepare($sqlInsert);

        foreach ($targets as $target) {
            // 重複チェック実行
            $stmtExist->execute([
                ':cid' => $target['id'],
                ':sid' => $target_subject_id
            ]);
            $count = $stmtExist->fetchColumn();

            // まだ登録されていない場合のみINSERT
            if ($count == 0) {
                $stmtInsert->execute([
                    ':cid'   => $target['id'],
                    ':grade' => $target['grade'],
                    ':sid'   => $target_subject_id
                ]);
            }
        }

        $pdo->commit();
        header("Location: ../../../public/master/class_subject_edit/controls/addition_control.php");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: ../../../public/login/connection_error.html");
        exit;
    }
}