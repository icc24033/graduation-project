<?php
// StudentLoginRepository.php
// 生徒のログイン情報を管理するリポジトリ

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class StudentLoginRepository extends BaseRepository {

    /**
     * 新しい学生のログイン情報を追加する
     */
    public function addStudentLogin($student_id) {
        try {
            // student_id が既に存在するかチェックし、存在しない場合のみ INSERT する
            $sql = "INSERT INTO student_login_table (student_id, user_grade) 
                    SELECT ?, 'student@icc_ac.jp' 
                    FROM (SELECT 1) AS tmp 
                    WHERE NOT EXISTS (
                        SELECT 1 FROM student_login_table WHERE student_id = ?
                    ) LIMIT 1;";
    
            $stmt = $this->pdo->prepare($sql);
            // プレースホルダが2つあるので、student_id を2回渡します
            $stmt->execute([$student_id, $student_id]);
            
        } catch (PDOException $e) {
            error_log("StudentLoginRepository Error: " . $e->getMessage());
        }
    }
}