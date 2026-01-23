<?php
// ClassSubjectEditService.php

// RepositoryFactoryの読み込み
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

class ClassSubjectEditService {

    /**
     * 授業科目一覧の取得
     */
    public function getClassSubjectData() {
        $data = ['classSubjectList' => [], 'error_message' => ''];
        try {
            $subjectInChargesRepo = RepositoryFactory::getSubjectInChargesRepository();
            $data['classSubjectList'] = $subjectInChargesRepo->getAllClassSubjects();
        } catch (Exception $e) {
            error_log("ClassSubjectEditService Error (getClassSubjectData): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * 未加工の授業科目一覧の取得
     */
    public function getRawClassSubjectData() {
        $data = ['rawClassSubjectList' => [], 'error_message' => ''];
        try {
            $subjectInChargesRepo = RepositoryFactory::getSubjectInChargesRepository();
            $data['rawClassSubjectList'] = $subjectInChargesRepo->getRawClassSubjectData();
        } catch (Exception $e) {
            error_log("ClassSubjectEditService Error (getRawClassSubjectData): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * コースリストの取得
     */
    public function getCourseList() {
        $data = ['courseList' => [], 'error_message' => ''];
        try {
            $courseRepo = RepositoryFactory::getCourseRepository();
            $data['courseList'] = $courseRepo->getAllCourses();
        } catch (Exception $e) {
            error_log("StudentAccountService Error (getCourseList): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * 先生一覧の取得
     */
    public function getTeacherList() {
        $data = ['teacherList' => [], 'error_message' => ''];
        try {
            $teacherRepo = RepositoryFactory::getTeacherRepository();
            $data['teacherList'] = $teacherRepo->getAllTeachers();
        } catch (Exception $e) {
            error_log("ClassSubjectEditService Error (getTeacherList): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * 教室一覧の取得
     */
    public function getRoomList() {
        $data = ['roomList' => [], 'error_message' => ''];
        try {
            $roomRepo = RepositoryFactory::getRoomRepository();
            $data['roomList'] = $roomRepo->getAllRooms();
        } catch (Exception $e) {
            error_log("ClassSubjectEditService Error (getRoomList): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * 授業科目追加・表示用コースの基本情報を取得（マスタデータ）
     * DBから取得した course_id をキーにした連想配列を返す
     */
    public function getCourseInfoMaster() {
        $courseRepo = RepositoryFactory::getCourseRepository();
        $courses = $courseRepo->getAllCoursesIncludedGrade();
    
        $master = [];
        foreach ($courses as $row) {
            $master[$row['course_id']] = [
                'id'    => (int)$row['course_id'],
                'name'  => $row['course_name'],
                'grade' => (int)$row['grade']
            ];
        }
        return $master;
    }

    /**
     * 条件（学年・コース）に基づいてフィルタリングされた授業科目一覧を取得
     */
    public function getFilteredClassSubjects($search_grade, $search_course) {
        $courseInfo = $this->getCourseInfoMaster();
        
        $search_grade_val = null;
        if ($search_grade === '1年生' || $search_grade === '1') {
            $search_grade_val = 1;
        } elseif ($search_grade === '2年生' || $search_grade === '2') {
            $search_grade_val = 2;
        } 

        // $search_course（数値ID）に対応する course_id を取得
        $target_course_id = null;
        if ($search_course !== 'all' && isset($courseInfo[$search_course])) {
            $target_course_id = (int)$courseInfo[$search_course]['id'];
        }

        $data = $this->getClassSubjectData();
        $classSubjectList = $data['classSubjectList'];

        $classSubjectList = array_filter($classSubjectList, function ($item) use ($search_grade_val, $target_course_id) {
            // 学年フィルタリング
            if ($search_grade_val !== null) {
                if ((int)$item['grade'] !== (int)$search_grade_val) {
                    return false;
                }
            }
            // コースフィルタリング（数値ID同士で比較）
            if ($target_course_id !== null) {
                if ((int)$item['course_id'] !== $target_course_id) {
                    return false;
                }
            }
            return true;
        });

        return array_values($classSubjectList);
    }

    /**
     * 表示用に科目名でグルーピングしたリストを作成する
     * 講師情報(IDと名前)も配列として集約します
     */
    public function getGroupedSubjectList($classSubjectList, $courseInfo) {
        $subjects = [];
        $total_course_count = count($courseInfo);

        foreach ($classSubjectList as $row) {
            // IDの作成 (学年_科目名)
            $id = $row['grade'] . "_" . $row['subject_name'];

            if (!isset($subjects[$id])) {
                $subjects[$id] = [
                    'grade'   => $row['grade'], 
                    'title'   => $row['subject_name'],
                    'courses' => [], 
                    'course_keys' => [],
                    'teachers' => [],    // 講師名の配列
                    'teacher_ids' => []  // 講師IDの配列
                ];
            }

            // コース名の追加（重複チェック）
            if (!in_array($row['course_name'], $subjects[$id]['courses'])) {
                $subjects[$id]['courses'][] = $row['course_name'];
            }

            // DBの course_id をそのまま course_keys 配列に追加
            $cid = $row['course_id'];
            if (isset($courseInfo[$cid])) {
                if (!in_array($cid, $subjects[$id]['course_keys'])) {
                    $subjects[$id]['course_keys'][] = $cid;
                }
            }

            // 講師情報の追加処理
            // teacher_id が 0(未設定) や null でない場合のみ配列に追加する
            if (!empty($row['teacher_id']) && $row['teacher_id'] != 0) {
                if (!in_array($row['teacher_id'], $subjects[$id]['teacher_ids'])) {
                    $subjects[$id]['teacher_ids'][] = $row['teacher_id'];
                    $subjects[$id]['teachers'][] = $row['teacher_name'];
                }
            }
        }

        // 全コース対象かどうかの判定（is_allフラグ）
        foreach ($subjects as $id => $data) {
            $subjects[$id]['is_all'] = (count($data['course_keys']) === $total_course_count);
        }

        return $subjects;
    }

    /**
     * 削除画面表示用に科目名でグルーピングしたリストを作成する
     */
    public function getGroupedSubjectListForDelete($classSubjectList, $courseInfo) {
        $subjects = [];
        foreach ($classSubjectList as $row) {
            // IDの作成 (学年_科目名)
            $id = $row['grade'] . "_" . $row['subject_name'];

            if (!isset($subjects[$id])) {
                $subjects[$id] = [
                    'grade'   => $row['grade'], 
                    'title'   => $row['subject_name'],
                    'courses' => [], 
                    'course_keys' => [] 
                ];
            }

            // コース名の追加と削除用キーの追加
            if (!in_array($row['course_name'], $subjects[$id]['courses'])) {
                $subjects[$id]['courses'][] = $row['course_name'];
            
                $cid = $row['course_id'];
                if (isset($courseInfo[$cid])) {
                    $subjects[$id]['course_keys'][] = $cid;
                }
            }
        }
        return $subjects;
    }
}