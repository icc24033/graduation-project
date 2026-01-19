<?php
// SubjectInChargesRepository.php
// 授業科目担当情報を取得するリポジトリクラス

// 自動的にBaseRepositoryを継承し、DB接続を確立する
require_once __DIR__ . '/../BaseRepository.php';

class SubjectInChargesRepository extends BaseRepository {

    /**
     * 授業科目の内容一覧を取得する
     * @return array 授業科目の内容一覧
     */
    public function getAllClassSubjects() {
        try {
            // INNER JOIN を使用して全ての関連情報を取得
            $sql = "SELECT
                        c.course_id,       -- ★これを追加
                        c.course_name, 
                        sic.grade, 
                        s.subject_name, 
                        t.teacher_name, 
                        r.room_name
                    FROM subject_in_charges sic
                    JOIN course c ON sic.course_id = c.course_id
                    JOIN subjects s ON sic.subject_id = s.subject_id
                    LEFT JOIN teacher t ON sic.teacher_id = t.teacher_id
                    LEFT JOIN room r ON sic.room_id = r.room_id
                    ORDER BY c.course_id ASC, sic.grade ASC;"; // コースと学年順に並べ替え

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("ClassSubjectsRepository Error: " . $e->getMessage());
            return [];
        }
    }
}