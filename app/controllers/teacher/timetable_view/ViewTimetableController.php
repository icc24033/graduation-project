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

        // CSRFトークンを生成
        $csrfToken = SecurityHelper::generateCsrfToken();
        
        extract([
            'csrfToken' => $csrfToken,
            'user_picture' => $user_picture
        ]);

        // Viewにデータを渡す（requireすることで変数がView内で使えるようになります）
        require_once __DIR__ . '/../../../../public/teacher/timetable_view/timetable_view.php';
    }
}