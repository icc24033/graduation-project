<?php
// ClassDetailEditorsController.php
// 授業詳細編集コントローラー
require_once __DIR__ . '/../../../classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../../classes/repository/home/HomeRepository.php';
require_once __DIR__ . '/../../../services/teacher/ClassDetailEditService.php';
require_once __DIR__ . '/../../../classes/helper/dropdown/ViewHelper.php';
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

class ClassDetailEditorsController
{
    private $service;

    public function __construct() {
        $this->service = new ClassDetailEditService();
    }

    /**
     * index
     * 概要：メイン画面表示処理 (GET)
     */
    public function index()
    {
        HomeRepository::session_resetting();
        SecurityHelper::requireLogin();

        $teacherId = $_SESSION['user_id'] ?? null;

        $assignedClasses = [];
        if ($teacherId) {
            // 1. 正規の担当科目を取得
            $sicRepo = RepositoryFactory::getSubjectInChargesRepository();
            $assignedClasses = $sicRepo->getAssignedClassesByTeacherId($teacherId);

            // 2. 代理（授業変更）で担当する科目を取得してマージ
            $dailyRepo = RepositoryFactory::getClassDailyInfoRepository();
            $substituteClasses = $dailyRepo->getSubstituteClassesByTeacherId($teacherId);

            // 配列を結合
            $mergedClasses = array_merge($assignedClasses, $substituteClasses);

            // 重複を削除 (course_id と subject_id の組み合わせでユニークにする)
            $uniqueMap = [];
            foreach ($mergedClasses as $class) {
                // 一意なキーを作成
                $key = $class['course_id'] . '-' . $class['subject_id'];
                // まだ登録されていなければ追加（上書きしないことで正規担当を優先、といってもデータは同じなのでどちらでも良い）
                if (!isset($uniqueMap[$key])) {
                    $uniqueMap[$key] = $class;
                }
            }
            
            // インデックス付き配列に戻し、学年・クラス・科目順などでソートし直す
            $assignedClasses = array_values($uniqueMap);
            
            // 表示順序を整える（学年昇順 > コースID昇順 > 科目ID昇順）
            // SQLのORDER BYで取得していますが、マージしたため念のため再ソート
            usort($assignedClasses, function ($a, $b) {
                if ($a['grade'] !== $b['grade']) {
                    return $a['grade'] <=> $b['grade'];
                }
                if ($a['course_id'] !== $b['course_id']) {
                    return $a['course_id'] <=> $b['course_id'];
                }
                return $a['subject_id'] <=> $b['subject_id'];
            });
        }

        // --- ビュー（class_detail_edit.php）に合わせてデータを整形 ---
        
        // 1. 学年リストの作成 (単純な ID => 名前 の配列にします)
        $grades = [];
        foreach ($assignedClasses as $row) {
            if (isset($row['grade'])) {
                $g = $row['grade'];
                // Viewでの foreach ($gradeList as $id => $name) に合わせる
                $grades[$g] = $g . '年生'; 
            }
        }
        ksort($grades); // 学年順にソート

        // 2. コースリストの作成
        $courses = [];
        foreach ($assignedClasses as $row) {
            if (isset($row['course_id']) && isset($row['course_name'])) {
                // Viewでの foreach ($courseList as $id => $name) に合わせる
                $courses[$row['course_id']] = $row['course_name'];
            }
        }
        ksort($courses);

        // 3. 科目リストの作成
        $subjects = [];
        foreach ($assignedClasses as $row) {
            if (isset($row['subject_id']) && isset($row['subject_name'])) {
                // Viewでの foreach ($subjectList as $id => $name) に合わせる
                $subjects[$row['subject_id']] = $row['subject_name'];
            }
        }
        ksort($subjects);

        // Viewに渡す変数を連想配列でまとめる
        $viewData = [
            'teacherId'   => $teacherId,
            'gradeList'   => $grades,
            'courseList'  => $courses,
            'subjectList' => $subjects
        ];

        extract($viewData);

        // Viewの読み込み
        

         // ユーザーアイコン表示用
        $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
        extract($data);

        $smartcampus_picture = '../images/smartcampus.png';
        
        require_once __DIR__ . '/../../../../public/teacher/class_detail_edit/class_detail_edit.php';
    }

    /**
     * カレンダーデータ取得API (AJAX GET)
     */
    public function getCalendarData() {
        SecurityHelper::requireLogin();
        header('Content-Type: application/json');

        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('n');
        $subjectId = $_GET['subject_id'] ?? null;
        $teacherId = 24004; // 仮

        // input['course_ids'] を配列として受け取る
        // URLパラメータ ?course_ids[]=1&course_ids[]=2 の場合、$_GET['course_ids'] は配列になる
        $courseIds = $_GET['course_ids'] ?? [];
        if (!is_array($courseIds)) {
            $courseIds = [$courseIds]; // 単一なら配列化
        }

        if (!$subjectId || empty($courseIds)) {
            echo json_encode([]);
            return;
        }

        // サービスへ配列ごと渡す
        $data = $this->service->getCalendarData($teacherId, $subjectId, $courseIds, $year, $month);
        echo json_encode($data);
    }

    /**
     * 保存API (AJAX POST)
     */
    public function save() {
        SecurityHelper::requireLogin();
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $input['teacher_id'] = 24004; // 仮

        // input['course_ids'] をそのままサービスへ渡す
        $result = $this->service->saveClassDetail($input);
        
        echo json_encode(['success' => $result]);
    }

    /**
     * 削除API (AJAX DELETE)
     */
    public function delete() {
        SecurityHelper::requireLogin();
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // ★ 削除も複数コース一括で行う
        $result = $this->service->deleteClassDetail(
            $input['date'], 
            $input['slot'], 
            $input['course_ids'] // 配列
        );
        
        echo json_encode(['success' => $result]);
    }   
}