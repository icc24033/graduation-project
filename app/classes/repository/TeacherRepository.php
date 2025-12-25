<?php
// TeacherRepository.php
// 教員情報を取得するリポジトリクラス

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class TeacherRepository extends BaseRepository {

    /**
     * すべての教員一覧を取得する
     * 授業変更モーダルの担当教員選択用
     * @return array 教員情報の配列
     */
    public function getAllTeachers() {
        try {
            $sql = "SELECT teacher_id, teacher_name FROM teachers ORDER BY teacher_id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("TeacherRepository Error: " . $e->getMessage());
            return [];
        }
    }
}