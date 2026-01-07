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
            $sql = "SELECT student_id, student_name FROM student ORDER BY student_id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("StudentRepository Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 指定されたコースIDの学生一覧を取得する
     * @param int $course_id コースID
     * @return array 学生情報の配列
     */
    public function getStudentsByCourse($course_id) {
        try {
            $sql = ("SELECT 
                        S.student_id,
                        S.student_name,
                        S.course_id,
                        S.grade,
                        C.course_name
                    FROM
                        student AS S
                    INNER JOIN
                        course AS C 
                    ON
                        S.course_id = C.course_id
                    WHERE
                        S.course_id = ?;"
                    );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$course_id]);

            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("StudentRepository Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 今の年に基づいて学生数をカウントする
     * @return int 学生数
     */
    public function countStudentsByYear() {
        try {
            $sql = "SELECT COUNT(*) FROM student WHERE LEFT(student_id, 2) = ?;";

            // 現在の年の下2桁を取得
            $current_year = date("Y");
            $current_year = substr($current_year, -2);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$current_year]);

            $result = $stmt->fetch();
            return (int)$result['COUNT(*)'];

        } catch (PDOException $e) {
            error_log("StudentRepository Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 新しい学生を追加する
     */
    public function addStudent($student_id, $student_mail, $student_name, $course_id, $grade) {
        try {
            $sql = 
                "INSERT IGNORE INTO 
                    student (student_id, student_mail, student_name, course_id, grade) 
                 VALUES 
                    (?, ?, ?, ?, ?);";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$student_id, $student_mail, $student_name, $course_id, $grade]);
        } catch (PDOException $e) {
            error_log("StudentRepository Error: " . $e->getMessage());
        }
    }
}
