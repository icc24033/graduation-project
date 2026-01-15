<?php
// backend_subject_add.php

require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

// 1. コース情報の定義（提供された配列を使用）
$courseInfo = [   
    'itikumi'       => ['id' => 7, 'name' => '1年1組', 'grade' => 1],
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
$action    = $_POST['action'] ?? '';
$raw_grade = $_POST['grade'] ?? '';  // '1', '2', '1_all', '2_all', 'all'
$course_key = $_POST['course'] ?? ''; // 単一コース選択時のキー (例: 'itikumi')
$title     = $_POST['title'] ?? '';  // 科目名

if ($action === 'insert_new' && !empty($title)) {
    try {
        $pdo->beginTransaction();

        // A. subjectsテーブルに新しい科目を登録
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
        $stmt->execute([$title]);
        $new_subject_id = $pdo->lastInsertId();

        // B. 対象コースの選別ロジック
        $targets = [];

        if ($raw_grade === '1_all') {
            // 学年が1のものを全て抽出
            foreach ($courseInfo as $info) {
                if ($info['grade'] == 1) $targets[] = $info;
            }
        } elseif ($raw_grade === '2_all') {
            // 学年が2のものを全て抽出
            foreach ($courseInfo as $info) {
                if ($info['grade'] == 2) $targets[] = $info;
            }
        } elseif ($raw_grade === 'all') {
            // 全てのコース
            $targets = $courseInfo;
        } else {
            // 個別コース選択
            if (isset($courseInfo[$course_key])) {
                $targets[] = $courseInfo[$course_key];
            }
        }

        // C. subject_in_chargesテーブルに一括登録
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
        header("Location: ../tuika.php"); 
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("登録エラー: " . $e->getMessage());
    }
}