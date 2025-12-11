<?php
// AuthRepository_class.php
// studentまたはteacher(master)を判別するプロパティ
// 戻り値として、Student_login_class.php, Teacher_login_classのコンストラクタの値を返す
require_once __DIR__ . '/Student_login_class.php';
require_once __DIR__ . '/Teacher_login_class.php';

class AuthRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * メールアドレスからユーザーを検索し、LoginUserオブジェクトを返す
     * @return LoginUser|null
     */
    public function findUserByEmail(string $email): ?LoginUser {
        
        // ---------------------------------------------------------
        // 1. 生徒テーブル検索
        // ---------------------------------------------------------
        // student_login_table と student を結合し、
        // student テーブルの student_mail カラムで検索します。
        $sql = "
            SELECT 
                s.student_id,
                sl.user_grade,
                s.course_id
            FROM student_login_table sl
            INNER JOIN student s ON sl.student_id = s.student_id
            WHERE s.student_mail = :email
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $studentData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($studentData) {
            // 取得した grade と course_id、student_id をコンストラクタに渡す
            return new StudentLogin(
                $studentData['student_id'], 
                $studentData['user_grade'], 
                $studentData['course_id']
            );
        }

        // ---------------------------------------------------------
        // 2. 先生テーブル検索
        // ---------------------------------------------------------
        // teacher_login_table と teacher を結合し、
        // teacher テーブルの teacher_mail カラムで検索します。
        // また、権限情報 (user_grade) は teacher_login_table から取得します。
        $sql = "
            SELECT 
                t.teacher_id, 
                tl.user_grade 
            FROM teacher_login_table tl
            INNER JOIN teacher t ON tl.teacher_id = t.teacher_id
            WHERE t.teacher_mail = :email
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $teacherData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teacherData) {
            // TeacherLoginクラスのコンストラクタに、取得した user_grade を渡す
            return new TeacherLogin($teacherData['teacher_id'], $teacherData['user_grade']);
        }

        return null;
    }
}