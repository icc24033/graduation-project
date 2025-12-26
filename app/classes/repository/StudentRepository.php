<?php
// StudentRepository.php
// 学生情報を取得するリポジトリクラス
// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class StudentRepository extends BaseRepository {

    /**
     * すべての学生一覧を取得する
     * @return array 学生情報の配列
     */
    public function getAllStudents() {
        try {
            $sql = "SELECT student_id, student_name FROM students ORDER BY student_id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("StudentRepository Error: " . $e->getMessage());
            return [];
        }
    }

    
}