<?php
// SubjectRepository.php
// 授業科目情報を取得するリポジトリクラス

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class SubjectRepository extends BaseRepository {

    /**
     * すべての授業科目一覧を取得する
     * 授業変更モーダルの科目選択用
     * @return array 科目情報の配列
     */
    public function getAllSubjects() {
        try {
            $sql = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("SubjectRepository Error: " . $e->getMessage());
            return [];
        }
    }
}