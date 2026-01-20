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
     * 条件（学年・コース）に基づいてフィルタリングされた授業科目一覧を取得
     */
    public function getFilteredClassSubjects($search_grade, $search_course) {

        // 共通のマスタデータメソッドから取得
        $courseInfo = $this->getCourseInfoMaster();
        
        // 学年の判定
        if ($search_grade === '1年生') {
            $search_grade_val = 1;
        } elseif ($search_grade === '2年生') {
            $search_grade_val = 2;
        } else {
            $search_grade_val = null;
        }

        // 1. $search_course（文字列キー）を対応する course_id に変換
        $target_course_id = null;
        if ($search_course !== 'all' && isset($courseInfo[$search_course])) {
            $target_course_id = $courseInfo[$search_course]['course_id'];
        }

        // 元となる全データを取得
        $data = $this->getClassSubjectData();
        $classSubjectList = $data['classSubjectList'];

        // 2. 配列をフィルタリング
        $classSubjectList = array_filter($classSubjectList, function ($item) use ($search_grade_val, $target_course_id, $courseInfo, $search_course) {
            
            // 条件A: 学年フィルタリング
            if ($search_grade_val !== null) {
                if ($item['grade'] !== (int)$search_grade_val) {
                    return false;
                }
            }

            // 条件B: コースフィルタリング
            if ($target_course_id !== null) {
                $target_name = $courseInfo[$search_course]['name'];
                if (strpos($item['course_name'], $target_name) === false) {
                    return false;
                }
            }

            return true;
        });

        // 3. 配列の添字を振り直して返す
        return array_values($classSubjectList);
    }

    /**
     * コースの基本情報を取得（マスタデータ）
     */
    public function getCourseInfoMaster() {
        return [   
            'itikumi'       => ['table' => 'itikumi',         'name' => '1年1組', 'grade' => 1, 'course_id' => 7],
            'nikumi'        => ['table' => 'nikumi',          'name' => '1年2組', 'grade' => 1, 'course_id' => 8],
            'iphasu'        => ['table' => 'iphasu_itiran',   'name' => 'ITパスポートコース', 'grade' => 1, 'course_id' => 6],
            'kihon'         => ['table' => 'kihon_itiran',    'name' => '基本情報コース', 'grade' => 1, 'course_id' => 5],
            'applied-info'  => ['table' => 'ouyou_itiran',    'name' => '応用情報コース', 'grade' => 1, 'course_id' => 4],
            'multimedia'    => ['table' => 'mariti_itiran',   'name' => 'マルチメディアOAコース', 'grade' => 2, 'course_id' => 3],
            'system-design' => ['table' => 'sisutemu_itiran', 'name' => 'システムデザインコース', 'grade' => 2, 'course_id' => 1],
            'web-creator'   => ['table' => 'web_itiran',      'name' => 'Webクリエイターコース', 'grade' => 2, 'course_id' => 2]
        ];
    }

    /**
     * 表示用に科目名でグルーピングしたリストを作成する
     */
    public function getGroupedSubjectList($classSubjectList, $courseInfo) {
        $subjects = [];
        $total_course_count = count($courseInfo); // 全コース数

        foreach ($classSubjectList as $row) {
            $id = $row['subject_name']; 
                
            if (!isset($subjects[$id])) {
                $subjects[$id] = [
                    'grade'       => $row['grade'], 
                    'title'       => $row['subject_name'],
                    'teachers'    => [], 
                    'room'        => $row['room_name'] ?? '未設定', 
                    'courses'     => [], 
                    'course_keys' => [] 
                ];
            }

            // 講師名の追加（重複防止）
            if (!empty($row['teacher_name']) && $row['teacher_name'] !== '未設定') {
                if (!in_array($row['teacher_name'], $subjects[$id]['teachers'])) {
                    $subjects[$id]['teachers'][] = $row['teacher_name'];
                }
            }

            // 表示用のコース名を追加
            if (!in_array($row['course_name'], $subjects[$id]['courses'])) {
                $subjects[$id]['courses'][] = $row['course_name'];
            }

            // course_id からキーを逆引き
            foreach ($courseInfo as $key => $info) {
                if ($info['course_id'] == $row['course_id']) {
                    if (!in_array($key, $subjects[$id]['course_keys'])) {
                        $subjects[$id]['course_keys'][] = $key;
                    }
                    break;
                }
            }
        }

        // 全コース対象かどうかの判定
        foreach ($subjects as $id => $data) {
            $subjects[$id]['is_all'] = (count($data['course_keys']) === $total_course_count);
        }

        return $subjects;
    }
}