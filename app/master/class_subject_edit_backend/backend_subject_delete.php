<?php
// backend_subject_delete.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

// sakuzyo.php と完全に一致するキーと ID のセットを定義
$courseInfo = [   
    'itikumi'       => ['id' => 7],
    'nikumi'        => ['id' => 8],
    'kihon'         => ['id' => 5],
    'applied-info'  => ['id' => 4],
    'multimedia'    => ['id' => 3],
    'system-design' => ['id' => 1],
    'web-creator'   => ['id' => 2]
];

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
        $stmtId = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_name = ?");
        $stmtId->execute([$subject_name]);
        $subject_id = $stmtId->fetchColumn();

        if ($subject_id) {
            if ($action === 'delete_single') {
                // セレクトボックスで選択されたキー（例: 'itikumi'）を取得
                $course_key = $_POST['course_key'] ?? '';
                
                // キーに対応する数値IDが存在するかチェック
                if (isset($courseInfo[$course_key])) {
                    $cid = $courseInfo[$course_key]['id']; // 例: 7
                    
                    // 管理テーブル subject_in_charges から該当レコードを削除
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
        header("Location: ../../../public/master/class_subject_edit/controls/delete_control.php?" . $query_string);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("削除エラー: " . $e->getMessage());
    }
}