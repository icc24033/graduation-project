<?php
// MaintenanceRepository.php
// 自動処理の履歴を管理するリポジトリ

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class MaintenanceRepository extends BaseRepository {

    /**
     * 指定されたキーに基づいて削除された最新の年度を取得する
     * @return string 最新の削除年度（例: "2025"）
     */
    // 引数 $key で 'last_graduate_delete_year' や 'last_grade_increment_year' を受け取る
    public function getLatestYearByKey(string $key) {
        try {
            $sql = "SELECT setting_value 
                    FROM system_maintenance_history 
                    WHERE setting_key = ?";
                    
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$key]);

            return $stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("MaintenanceRepository Error: " . $e->getMessage());
        }
    }

    /**
     * 指定されたキーに基づいて削除年度を更新する
     */
    public function updateYearByKey(string $key, string $year) {
        try {
            $sql = "UPDATE system_maintenance_history
                    SET setting_value = ?
                    WHERE setting_key = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$year, $key]);

        } catch (PDOException $e) {
            error_log("MaintenanceRepository Error: " . $e->getMessage());
        }
    }
}