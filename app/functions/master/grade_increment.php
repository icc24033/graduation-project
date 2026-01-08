<?php
// grade_increment.php
// 毎年4月に学年を1つ上げる実行スクリプト

require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

try {
    $maintenanceRepo = RepositoryFactory::getMaintenanceRepository();
    $studentRepo = RepositoryFactory::getStudentRepository();

    // 進級処理用のキー
    $key = 'last_grade_increment_year'; 
    $lastYear = $maintenanceRepo->getLatestYearByKey($key);
    $currentYear = date('Y');

    // 今年度まだ実行されていなければ実行
    if ($lastYear !== $currentYear) {
        // 全生徒の学年を+1する処理 (1→2, 2→3)
        $studentRepo->incrementAllGrades();

        // 履歴を「今年度」に更新
        $maintenanceRepo->updateYearByKey($key, $currentYear);
        
        error_log("Grade Increment Success: " . $currentYear);
    }
} catch (Exception $e) {
    error_log("Grade Increment Error: " . $e->getMessage());
}