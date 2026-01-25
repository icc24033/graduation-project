<?php
// backend_subject_delete.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../services/master/ClassSubjectEditService.php';

$service = new ClassSubjectEditService();
$courseInfo = $service->getCourseInfoMaster(); 

try {
    $pdo = RepositoryFactory::getPdo();
} catch (Exception $e) {
    die("DB接続失敗: " . $e->getMessage());
}

$action       = $_POST['action'] ?? '';
$subject_name = $_POST['subject_name'] ?? '';
$target_grade = (int)($_POST['grade'] ?? 0);
$query_string = $_POST['query_string'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($subject_name)) {
    try {
        $pdo->beginTransaction();

        // 1. 科目名から subject_id を特定
        $stmtId = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_name = ? LIMIT 1");
        $stmtId->execute([$subject_name]);
        $subject_id = $stmtId->fetchColumn();

        if ($subject_id) {
            if ($action === 'delete_single') {
                // フロント（JS）から送られてきた course_id (例: "1", "2")
                $course_key = $_POST['course_key'] ?? '';
                
                if (isset($courseInfo[$course_key])) {
                    $cid = $courseInfo[$course_key]['id']; 
                    
                    // 【修正ポイント】
                    // 指定されたコース・学年・科目に紐づくレコードを「すべて」削除します。
                    // これにより、講師が複数人登録されていても、そのコースからは完全に科目が消えます。
                    $stmt = $pdo->prepare("DELETE FROM subject_in_charges 
                                           WHERE subject_id = ? AND course_id = ? AND grade = ?");
                    $stmt->execute([$subject_id, $cid, $target_grade]);
                }
            } elseif ($action === 'delete_all') {
                // すべてのコースから削除（既存の正常動作を維持）
                $stmt = $pdo->prepare("DELETE FROM subject_in_charges WHERE subject_id = ? AND grade = ?");
                $stmt->execute([$subject_id, $target_grade]);
            }
        }

        $pdo->commit();
        header("Location: ../../../public/master/class_subject_edit/controls/delete_control.php?" . $query_string);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("削除エラー: " . $e->getMessage());
    }
}