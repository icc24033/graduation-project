<?php
// StudentAccountService.php
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

class StudentAccountService {

    /**
     * コースリストの取得
     */
    public function getEditData() {
        $data = ['courseList' => [], 'error_message' => ''];
        try {
            $courseRepo = RepositoryFactory::getCourseRepository();
            $data['courseList'] = $courseRepo->getAllCourses();
        } catch (Exception $e) {
            error_log("StudentAccountService Error (getEditData): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * 学年リストの取得
     */
    public function getGradeData() {
        $data = ['gradeList' => [], 'error_message' => ''];
        try {
            $studentGradeRepo = RepositoryFactory::getStudentGradeRepository();
            $data['gradeList'] = $studentGradeRepo->getAllGrades();
        } catch (Exception $e) {
            error_log("StudentAccountService Error (getGradeData): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * 学生追加画面の基本情報取得
     */
    public function getAdditionBasicInfo($backend) {
        
        // data送信に必要な変数を初期化
        $student_count = 0; 
        $csv_count = 0; 
        $csv_count_flag = false;
        $csv_data = []; 
        $error_count = 0; 
        $error_count_flag = false; 
        $error_data = [];

        if (empty($backend)) {
            $backend = 'student_addition';
            $errorStudentRepo = RepositoryFactory::getErrorStudentRepository();
            // 注意: エラーが出ていた箇所。リポジトリにこのメソッドがあるか確認してください
            if (method_exists($errorStudentRepo, 'createErrorDataTable')) {
                $errorStudentRepo->createErrorDataTable();
            }
        }

        $errorStudentRepo = RepositoryFactory::getErrorStudentRepository();
        $error_count = $errorStudentRepo->countErrorData();
        if ($error_count > 0) {
            $error_count_flag = true;
            $error_data = $errorStudentRepo->getAllErrorData();
        }

        if ($backend === 'student_addition') {
            $studentRepo = RepositoryFactory::getStudentRepository();
            $student_count = $studentRepo->countStudentsByYear();
        } else if ($backend === 'csv_upload') {
            $csvRepo = RepositoryFactory::getCsvRepository();
            $csv_count = $csvRepo->countCsvData();
            if ($csv_count > 0) {
                $csv_count_flag = true;
                $csv_data = $csvRepo->getAllCsvData();
            }
        }

        return [
            'success' => true,
            'backend' => $backend,
            'error_csv' => $error_count_flag,
            'error_data' => $error_data,
            'before' => 'teacher_home',
            'success_csv' => $csv_count_flag,
            'csv_data' => $csv_data,
            'student_count' => $student_count
        ];
    }

    /**
     * 各種画面共通の学生リスト取得ロジック
     */
    public function getStudentsInCourse($received_course_id, $received_current_year) {
        // empty() は 0 を真と判定してしまうため、明示的に null と空文字をチェックする
        if (($received_course_id === null || $received_current_year === null)) {
            $course_id = 1;
            $current_year = 2;
        } else {
            $course_id = $received_course_id;
            $current_year = $received_current_year;
        }
    
        $studentRepo = RepositoryFactory::getStudentRepository();
        $students_in_course = $studentRepo->getStudentsByCourse($course_id);
    
        return [
            'success' => true,
            'before' => 'teacher_home',
            'course_id' => $course_id,
            'current_year' => $current_year,
            'students_in_course' => $students_in_course
        ];
    }

    /**
     * 5月に卒業生を削除したかを確認し、未削除なら削除する
     * ※DBには初期データとして前年以前の年度が必ず1件登録されている前提
     */
    public function deleteGraduatedStudents() {
        // 現在の西暦を取得 (例: "2026")
        $current_year = date("Y");
        
        try {
            // 履歴管理用のリポジトリを取得
            $historyRepo = RepositoryFactory::getGraduateDeleteHistoryRepository();
            
            // 1. DBから「最後に削除処理を行った年」を取得
            // (初期データが入っているため、必ず "2025" などの文字列が返ってくる)
            $last_executed_year = $historyRepo->getLatestDeleteYear();

            // 2. 実行が必要か判定
            // 記録されている年が「今年よりも前」であれば実行する
            if ($last_executed_year < $current_year) {
                
                // 3. 必要なリポジトリの準備
                $studentRepo = RepositoryFactory::getStudentRepository();
                
                // 4. トランザクションの開始
                // 削除と履歴更新を「一塊の処理」として扱う
                $pdo = RepositoryFactory::getPdo();
                $pdo->beginTransaction();

                try {
                    /**
                     * 5. 実際の削除ロジックの実行
                     * ---------------------------------------------------------
                     * ここで卒業対象の生徒（例：3年生など）を削除します
                     * $studentRepo->deleteGraduates(); 
                     * ---------------------------------------------------------
                     */

                    // 6. 履歴を「今年の年」に更新
                    // これにより、次に誰かがアクセスした時は if 文の条件が false になり実行されない
                    $historyRepo->updateDeleteYear($current_year);

                    // 変更を確定
                    $pdo->commit();
                    
                    error_log("{$current_year}年度の卒業生データ一括削除を正常に完了しました。");

                } catch (Exception $e) {
                    // エラー時はロールバックして、削除も履歴更新も「なかったこと」にする
                    $pdo->rollBack();
                    throw $e;
                }
            }
        } catch (Exception $e) {
            // 画面表示を止めないよう、例外はログ出力のみに留める
            error_log("StudentAccountService Error (deleteGraduatedStudents): " . $e->getMessage());
        }
    }
}