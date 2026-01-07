<?php
// StudentGradeRepository.php
// 学生の学年情報を管理するリポジトリクラス

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class StudentGradeRepository extends BaseRepository {

    /**
     * すべての学年の一覧を取得する
     * ドロップダウンリスト表示用
     * @return array コース情報の配列
     */
    public function getAllGrades() {
        try {
            // SQLの準備
            $sql = "SELECT * FROM student_grade ORDER BY grade ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("StudentGradeRepository Error: " . $e->getMessage());
            // エラー時は空配列を返して画面が止まらないようにする
            return [];
        }
    }
}