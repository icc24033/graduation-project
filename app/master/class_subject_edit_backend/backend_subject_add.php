<?php

// --- デバッグ用：エラーを表示させる設定 ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// backend_subject_add.php

require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

// 1. コース情報の定義
$courseInfo = [   
    'itikumi'      => ['id' => 7, 'name' => '1年1組', 'grade' => 1],
    'nikumi'        => ['id' => 8, 'name' => '1年2組', 'grade' => 1],
    'kihon'         => ['id' => 5, 'name' => '基本情報', 'grade' => 1],
    'applied-info'  => ['id' => 4, 'name' => '応用情報', 'grade' => 1],
    'multimedia'    => ['id' => 3, 'name' => 'マルチメディア', 'grade' => 2],
    'system-design' => ['id' => 1, 'name' => 'システムデザイン', 'grade' => 2],
    'web-creator'   => ['id' => 2, 'name' => 'Webクリエイター', 'grade' => 2]
];

try {
    $pdo = RepositoryFactory::getPdo();
} catch (Exception $e) {
    die("DB接続失敗: " . $e->getMessage());
}

// 2. データの受け取り
$action     = $_POST['action'] ?? '';
$raw_grade = $_POST['grade'] ?? '';  // '1', '2', '1_all', '2_all', 'all'
$course_key = $_POST['course'] ?? ''; // 単一コース選択時のキー
$title      = $_POST['title'] ?? '';  // 科目名

if ($action === 'insert_new' && !empty($title)) {
    try {
        $pdo->beginTransaction();

        // --- 修正箇所 A: subjectsテーブルに新しい科目を登録 ---
        // ※ 渡された $title を subject_name として登録し、自動採番されたIDを取得します。
        // ※ テーブル名やカラム名は一般的な推測に基づいています。環境に合わせて適宜変更してください。
        $sqlSubject = "INSERT INTO subjects (subject_name) VALUES (?)";
        $stmt = $pdo->prepare($sqlSubject);
        $stmt->execute([$title]);
        $new_subject_id = $pdo->lastInsertId();

        // B. 対象コースの選別ロジック
        $targets = [];

        if ($raw_grade === '1_all') {
            foreach ($courseInfo as $info) {
                if ($info['grade'] == 1) $targets[] = $info;
            }
        } elseif ($raw_grade === '2_all') {
            foreach ($courseInfo as $info) {
                if ($info['grade'] == 2) $targets[] = $info;
            }
        } elseif ($raw_grade === 'all') {
            $targets = $courseInfo;
        } else {
            if (isset($courseInfo[$course_key])) {
                $targets[] = $courseInfo[$course_key];
            }
        }

        // C. subject_in_chargesテーブルに一括登録
        // teacher_id=0 は「担当者未設定」などの運用と想定します。
        $sqlInsert = "INSERT INTO subject_in_charges (course_id, grade, subject_id, teacher_id, room_id) 
                      VALUES (:cid, :grade, :sid, 0, NULL)";
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