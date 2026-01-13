<?php
// TeacherLoginRepository.php
// 先生のログイン情報を管理するリポジトリ

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/BaseRepository.php';

class TeacherLoginRepository extends BaseRepository {

    /**
     * 新しい先生のログイン情報を追加する
     * @param int $teacher_id
     */
    public function addTeacherLogin($teacher_id) {
        try {
            // teacher_id が既に存在するかチェックし、存在しない場合のみ INSERT する
            // user_grade は 'teacher@icc_ac.jp' で固定
            $sql = "INSERT INTO teacher_login_table (teacher_id, user_grade) 
                    SELECT ?, 'teacher@icc_ac.jp' 
                    FROM (SELECT 1) AS tmp 
                    WHERE NOT EXISTS (
                        SELECT 1 FROM teacher_login_table WHERE teacher_id = ?
                    ) LIMIT 1;";
    
            $stmt = $this->pdo->prepare($sql);
            
            // プレースホルダ(?)が2つあるので、teacher_id を2回渡します
            $stmt->execute([$teacher_id, $teacher_id]);
            
        } catch (PDOException $e) {
            // エラーログの出力
            error_log("TeacherLoginRepository Error: " . $e->getMessage());
        }
    }
}