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

        $target_course_id = null;
        if ($search_course !== 'all' && isset($courseInfo[$search_course])) {
            $target_course_id = (int)$courseInfo[$search_course]['id'];
        }

        $data = $this->getClassSubjectData();
        $classSubjectList = $data['classSubjectList'];

        $filtered = array_filter($classSubjectList, function ($item) use ($search_grade_val, $target_course_id) {
            if ($search_grade_val !== null) {
                if ((int)$item['grade'] !== (int)$search_grade_val) return false;
            }
            if ($target_course_id !== null) {
                if ((int)$item['course_id'] !== $target_course_id) return false;
            }
            return true;
        });

        return array_values($filtered);
    }

    /**
     * 表示用に科目名でグルーピングしたリストを作成する
     */
    public function getGroupedSubjectList($classSubjectList, $courseInfo) {
        $subjects = [];
        $total_course_count = count($courseInfo);

        foreach ($classSubjectList as $row) {
            $id = $row['grade'] . "_" . $row['subject_name'];

            if (!isset($subjects[$id])) {
                $subjects[$id] = [
                    'grade'   => $row['grade'],
                    'title'   => $row['subject_name'],
                    'courses' => [],
                    'course_keys' => [],
                    'teacher_ids' => [],
                    'teachers' => [],
                    'room_id'  => null,
                    'room_name' => '未設定'
                ];
            }

            // --- 教室情報の保持ロジック (重要) ---
            // 1人目の講師の行に教室がなくても、2人目の行にあればそれを採用する
            if (!empty($row['room_id'])) {
                $subjects[$id]['room_id'] = $row['room_id'];
                $subjects[$id]['room_name'] = !empty($row['room_name']) ? $row['room_name'] : '未設定';
            }

            // コース名の追加
            if (!in_array($row['course_name'], $subjects[$id]['courses'])) {
                $subjects[$id]['courses'][] = $row['course_name'];
                $subjects[$id]['course_keys'][] = $row['course_id'];
            }

            // 講師名の追加 (重複排除)
            $t_id = isset($row['teacher_id']) ? (int)$row['teacher_id'] : null;
            $t_name = !empty($row['teacher_name']) ? $row['teacher_name'] : '未設定';

            $is_duplicate = false;
            foreach ($subjects[$id]['teacher_ids'] as $idx => $existing_id) {
                if ($existing_id === $t_id) {
                    // 小田原先生(ID=0)が複数いる場合も考慮
                    if ($t_id !== 0 || $subjects[$id]['teachers'][$idx] === $t_name) {
                        $is_duplicate = true;
                        break;
                    }
                }
            }

            if (!$is_duplicate) {
                $subjects[$id]['teacher_ids'][] = $t_id;
                $subjects[$id]['teachers'][] = $t_name;
            }
        }

        foreach ($subjects as $id => $data) {
            $subjects[$id]['is_all'] = (count($data['course_keys']) >= $total_course_count);
        }

        return $subjects;
    }

    public function getGroupedSubjectListForDelete($classSubjectList, $courseInfo) {
        $subjects = [];
        foreach ($classSubjectList as $row) {
            $id = $row['grade'] . "_" . $row['subject_name'];
            if (!isset($subjects[$id])) {
                $subjects[$id] = [
                    'grade'   => $row['grade'], 
                    'title'   => $row['subject_name'],
                    'courses' => [], 
                    'course_keys' => [] 
                ];
            }
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