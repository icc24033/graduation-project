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
                        c.course_id,
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

    /**
     * 授業科目の生データ一覧を取得する
     * @return array 授業科目の生データ一覧
     */
    public function getRawClassSubjectData() {
        try {
            $sql = "SELECT * FROM subject_in_charges ORDER BY course_id ASC, grade ASC;";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("ClassSubjectsRepository Error: " . $e->getMessage());
            return [];
        }
    }


    /**
     * getSubjectDefinitionsByCourse
     * 概要：指定されたコース（と学年）で定義されている「科目・担当教員・教室」の組み合わせを取得する
     * 目的：時間割り作成画面で、特定のコースに割り当てらた科目情報を取得し、ドロップダウンリストの選択肢として表示するため
     * * @param int $courseId コースID
     * @param int|null $grade 学年（省略可能）
     * @return array
     */
    public function getSubjectDefinitionsByCourse($courseId, $grade = null) {
        try {
            // subject_in_charge テーブルをベースに、各マスタテーブルを結合して名称を取得
            
            $sql = "
                SELECT 
                    sic.subject_id,
                    s.subject_name,
                    sic.teacher_id,
                    t.teacher_name,
                    sic.room_id,
                    r.room_name,
                    sic.course_id
                FROM subject_in_charges sic
                INNER JOIN subjects s ON sic.subject_id = s.subject_id
                LEFT JOIN teacher t ON sic.teacher_id = t.teacher_id
                LEFT JOIN room r ON sic.room_id = r.room_id
                WHERE sic.course_id = :courseId
            ";

            // もし学年(grade)による絞り込みが必要なテーブル構造なら、ここでAND条件を追加します
            if (!is_null($grade)) {
                $sql .= " AND sic.grade = :grade";
            }
            
            $sql .= " ORDER BY s.subject_name ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':courseId', $courseId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("SubjectInChargesRepository Error: " . $e->getMessage());
            return [];
        }
    }
}