<?php
session_start();

// ▼▼▼ DB接続設定 ▼▼▼
$db_host = 'localhost';
$db_name = 'test';
$db_user = 'root';
$db_pass = 'root';

$dsn = "mysql:dbname={$db_name};host={$db_host};charset=utf8";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

// ▼▼▼ 【重要】コースIDとテーブル名の対応リスト（ホワイトリスト） ▼▼▼
// 公開ファイル側でこれを使って判定するため、不正なテーブル名が入り込むのを防げます
$course_table_map = [
    'system'  => 'subject',          // システムデザイン
    'web'     => 'web_subject',      // Webクリエイタ
    'multi'   => 'maruti_subject',   // マルチメディア
    'ouyou'   => 'ouyou_subject',    // 応用情報
    'kihon'   => 'kihon_subject',    // 基本情報
    'itikumi' => 'itikumi_subject',  // 1年1組
    'nikumi'  => 'nikkumi_subject'   // 1年2組
];

// ▼▼▼ SQLのひな形（%s の部分にテーブル名が入ります） ▼▼▼
// ` (バッククォート) で囲むことで subject などの予約語エラーを回避します
$base_sql = "SELECT * FROM `%s` WHERE day_of_week = ? ORDER BY period ASC";

$_SESSION['subject'] = [
    'success'   => true,
    'dsn'       => $dsn,
    'user'      => $db_user,
    'pass'      => $db_pass,
    'options'   => $options,
    'table_map' => $course_table_map, // 対応リストを保存
    'base_sql'  => $base_sql          // SQLひな形を保存
];

header("Location: ../../public/student/student_home.php");
exit();
?>