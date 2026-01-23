<?php
// backend_subject_delete.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// 必要なファイルを読み込み
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../services/master/ClassSubjectEditService.php';

// --- 修正箇所：手書きの $courseInfo 配列を削除し、Serviceから取得 ---
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
                // フロント（JS）から送られてきた数値ID（course_id）
                $course_id = $_POST['course_key'] ?? '';
                
                // マスタに存在するかチェック
                if (isset($courseInfo[$course_id])) {
                    $cid = $courseInfo[$course_id]['id']; // DB上の ID
                    
                    $stmt = $pdo->prepare("DELETE FROM subject_in_charges 
                                           WHERE subject_id = ? AND course_id = ? AND grade = ?");
                    $stmt->execute([$subject_id, $cid, $target_grade]);
                }
            } elseif ($action === 'delete_all') {
                // すべてのコースから削除
                $stmt = $pdo->prepare("DELETE FROM subject_in_charges WHERE subject_id = ? AND grade = ?");
                $stmt->execute([$subject_id, $target_grade]);
            }
        }

        $pdo->commit();
        // 削除後、元の画面に戻る
        header("Location: ../../../public/master/class_subject_edit/controls/delete_control.php?" . $query_string);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("削除エラー: " . $e->getMessage());
    }
}