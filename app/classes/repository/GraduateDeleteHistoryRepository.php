<?php
// GraduateDeleteHistoryRepository.php
// 卒業生を削除した履歴を管理するリポジトリクラス

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class GraduateDeleteHistoryRepository extends BaseRepository {

    /**
     * 最新の卒業生削除履歴を取得する
     * @return string 最新の削除年度（例: "2023"）
     */
    public function getLatestDeleteYear() {
        try {
            // SQLの準備
            $sql = "SELECT setting_value 
                    FROM graduate_deletion_history
                    WHERE setting_key = 'last_graduate_delete_year'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            // fetchColumn() を使うと、最初のカラムの値を直接文字列で返してくれるので便利です
            $result = $stmt->fetchColumn();

            return $result;

        } catch (PDOException $e) {
            error_log("GraduateDeleteHistoryRepository Error: " . $e->getMessage());
        }
    }

    /**
     * 卒業生削除履歴を更新する
     */
    public function updateDeleteYear($year) {
        try {
            // SQLの準備
            $sql = "UPDATE graduate_deletion_history
                    SET setting_value = ?
                    WHERE setting_key = 'last_graduate_delete_year'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$year]);

        } catch (PDOException $e) {
            error_log("GraduateDeleteHistoryRepository Error: " . $e->getMessage());
        }
    }
}