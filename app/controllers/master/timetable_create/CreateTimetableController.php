<?php
// CreateTimetableController.php
// 時間割作成画面のコントローラー

require_once __DIR__ . '/../../../classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../../classes/repository/home/HomeRepository.php';
require_once __DIR__ . '/../../../services/master/timetable_create/TimetableService.php'; // Serviceクラスの読み込み

class CreateTimetableController extends HomeRepository
{
    public function index()
    {
        parent::session_resetting();
        SecurityHelper::requireLogin();
        
        $user_picture = $_SESSION['user_picture'] ?? 'images/default_icon.png';
        
        // Serviceを使って時間割データを一括取得
        $timetableService = new TimetableService();
        $savedTimetables = $timetableService->getAllTimetableData();

        // コースのドロップダウンオプションをコース配列として取得
        $courseList = $timetableService->getCourseDropdownOptions();

        // 時間割り作成に必要なマスタデータ（科目・教員・教室の紐づけ）を取得
        $masterSubjectData = $timetableService->getAllCourseMasterData();
        
        extract([
            'savedTimetables' => $savedTimetables,
            'courseList' => $courseList, 
            'masterSubjectData' => $masterSubjectData
        ]);

        // Viewにデータを渡す（requireすることで変数がView内で使えるようになります）
        require_once __DIR__ . '/../../../../public/master/timetable_create/create_timetable.php';
    }
}