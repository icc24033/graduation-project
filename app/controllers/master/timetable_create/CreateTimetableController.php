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

        // 1. 保存されている時間割データをすべて取得
        $savedTimetables = $timetableService->getAllTimetableData();

        // 2. サイドバー用のコースのドロップダウンオプションをHTMLで取得
        $sidebarCourseList = $timetableService->getSidebarCourseListHtml();

        // 3. コースの生データ一覧を取得（JSON化してJSで使用する）
        $rawCourseData = $timetableService->getRawCourseData();

        // 4. 時間割り作成に必要なマスタデータ（科目・教員・教室の紐づけ）を取得
        $masterSubjectData = $timetableService->getAllCourseMasterData();

        // CSRFトークンを生成
        $csrfToken = SecurityHelper::generateCsrfToken();
        
        extract([
            'savedTimetables' => $savedTimetables,
            'sidebarCourseList' => $sidebarCourseList,
            'masterSubjectData' => $masterSubjectData,
            'rawCourseData' => $rawCourseData,
            'csrfToken' => $csrfToken
        ]);

        // Viewにデータを渡す（requireすることで変数がView内で使えるようになります）
        require_once __DIR__ . '/../../../../public/master/timetable_create/create_timetable.php';
    }
}
// ユーザーアイコン表示用
$data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
extract($data);