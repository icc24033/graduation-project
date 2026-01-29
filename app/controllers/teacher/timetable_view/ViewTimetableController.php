<?php
// ViewTimetableController.php
// 時間割閲覧画面のコントローラー

require_once __DIR__ . '/../../../classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../../classes/repository/home/HomeRepository.php';
require_once __DIR__ . '/../../../services/master/timetable_create/TimetableService.php'; // Serviceクラスの読み込み

class ViewTimetableController extends HomeRepository
{
    public function index()
    {
        parent::session_resetting();
        SecurityHelper::requireLogin();
        
        $user_picture = $_SESSION['user_picture'] ?? 'images/default_icon.png';

        // Serviceインスタンス生成
        $timetableService = new TimetableService();

        // 1. 保存されている全時間割データを取得
        // （作成機能と同じ形式のJSONデータが返ってきます）
        $savedTimetables = $timetableService->getAllTimetableData();

        // 2. コース一覧（生データ）を取得
        // サイドバーの生成や優先度リストの作成に使用します
        $sidebarCourseList = $timetableService->getSidebarCourseListHtml();

        // CSRFトークンを生成
        $csrfToken = SecurityHelper::generateCsrfToken();

         // ユーザーアイコン表示用
        $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
        extract($data);

        $smartcampus_picture = '../images/smartcampus.png';
        
        // 変数を展開してViewに渡す
        extract([
            'csrfToken'       => $csrfToken,
            'user_picture'    => $user_picture,
            'savedTimetables' => $savedTimetables,
            'sidebarCourseList'   => $sidebarCourseList
        ]);

        // Viewにデータを渡す（requireすることで変数がView内で使えるようになります）
        require_once __DIR__ . '/../../../../public/teacher/timetable_view/timetable_view.php';
    }
}