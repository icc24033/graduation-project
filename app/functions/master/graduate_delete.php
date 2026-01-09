<?php
// graduate_delete.php
// 毎年5月に卒業生データを削除する実行スクリプト

// 必要なリポジトリを取得するためのファクトリを読み込み
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

try {
    // リポジトリのインスタンスを作成
    $maintenanceRepo = RepositoryFactory::getMaintenanceRepository();
    $studentRepo = RepositoryFactory::getStudentRepository();

    // 1. 最新の削除完了年度を取得（例: "2024"）
    $key = 'last_graduate_delete_year'; // 卒業生削除用のキー
    $lastDeleteYear = $maintenanceRepo->getLatestYearByKey($key);
    $currentYear = date('Y');

    // 2. 「今年度」にまだ削除が行われていない場合のみ実行
    if ($lastDeleteYear !== $currentYear) {
        
        // 3. 卒業生データを削除（内部で grade = 3 を対象に削除）
        $studentRepo->deleteGraduatedStudents();

        // 4. 履歴を「今年度」に更新し、明日以降に再度実行されるのを防ぐ
        $maintenanceRepo->updateYearByKey($key, $currentYear);
    }

} catch (Exception $e) {
    // エラーが発生した場合はログに記録
    error_log("Graduate Delete Error: " . $e->getMessage());
}