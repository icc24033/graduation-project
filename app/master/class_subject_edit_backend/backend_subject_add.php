<?php

// --- デバッグ用：エラーを表示させる設定 ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// backend_subject_add.php

require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../services/master/ClassSubjectEditService.php';


try {
    $pdo = RepositoryFactory::getPdo();
} catch (Exception $e) {
    die("DB接続失敗: " . $e->getMessage());
}

// 1. コース情報の定義
$service = new ClassSubjectEditService();
$courseInfo = $service->getCourseInfoMaster();


// 2. データの受け取り
$action     = $_POST['action'] ?? '';
$raw_grade = $_POST['grade'] ?? '';  // '1', '2', '1_all', '2_all', 'all'
$course_key = $_POST['course'] ?? ''; // 単一コース選択時のキー
$title      = $_POST['title'] ?? '';  // 科目名

if ($action === 'insert_new' && !empty($title)) {
    try {
        $pdo->beginTransaction();

        // --- 修正箇所 A: subjectsテーブルに新しい科目を登録 ---
        // ※ 渡された $title を subject_name として登録し、自動採番されたIDを取得します
        $sqlSubject = "INSERT INTO subjects (subject_name) VALUES (?)";
        $stmt = $pdo->prepare($sqlSubject);
        $stmt->execute([$title]);
        $new_subject_id = $pdo->lastInsertId();

        // B. コース判定ロジック
        $course_id = $_POST['course'] ?? ''; // フロントからは数値のIDが届く
        $targets = [];

        if ($raw_grade === '1_all') {
            foreach ($courseInfo as $info) {
                if ($info['grade'] == 1) $targets[] = $info;
            }
        } elseif ($raw_grade === '2_all') {
            foreach ($courseInfo as $info) {
                if ($info['grade'] == 2) $targets[] = $info;
            }
        } else {
            // 直接IDで検索（キーがcourse_idなのでissetで判定可能）
            if (isset($courseInfo[$course_id])) {
                $targets[] = $courseInfo[$course_id];
            }
        }

        // C. subject_in_chargesテーブルに一括登録
        // teacher_id=0 は「担当者未設定」などの運用と想定します。
        $sqlInsert = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) 
                      VALUES (:cid, :grade, :sid, NULL, NULL)";
        $stmtInsert = $pdo->prepare($sqlInsert);

        foreach ($targets as $target) {
            $stmtInsert->execute([
                ':cid'   => $target['id'],
                ':grade' => $target['grade'],
                ':sid'   => $new_subject_id
            ]);
        }

        $pdo->commit();
        // 成功時のリダイレクト（必要に応じてコメントアウトを外してください）
        header("Location: ../../../public/master/class_subject_edit/controls/addition_control.php");
        echo "登録が完了しました。";
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("登録エラー: " . $e->getMessage());
    }
}