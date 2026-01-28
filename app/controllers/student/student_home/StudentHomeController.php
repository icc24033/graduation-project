<?php
// StudentHomeController.php
require_once __DIR__ . '/../../../services/student/StudentHomeService.php';
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../../classes/login/student_login_class.php';
require_once __DIR__ . '/../../../services/master/timetable_create/TimeTableService.php';

class StudentHomeController {
    private $service;
    private $serviceTimeTable;
    private $studentCourseId;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->service = new StudentHomeService();
        $this->serviceTimeTable = new TimeTableService();
        
        if (isset($_SESSION['user_id'], $_SESSION['user_grade'], $_SESSION['user_course'])) {
            $this->studentCourseId = new StudentLogin(
                (string)$_SESSION['user_id'],
                (string)$_SESSION['user_grade'],
                (string)$_SESSION['user_course']
            );
        } else {
            header('Location: ../login/login_control.php'); 
            exit;
        }
    }

    public function index() {
        // 1. パラメータの取得
        $sessionCourseId = $this->studentCourseId->getCourseId();
        $courseId = (int)($_POST['selected_course'] ?? $_GET['course_id'] ?? $sessionCourseId);
        $date = $_POST['search_date'] ?? $_GET['date'] ?? date('Y-m-d');

        // 2. 基礎データの取得（日付の日本語名やコースラベルを取得）
        $data = $this->service->getDashboardData($courseId, $date);
        $dayOfWeekKanji = $data['formatted_full_date'] ? mb_substr(explode(' ', $data['formatted_full_date'])[1], 1, 1) : ""; 
        // もしくは単純に:
        $dayOfWeekKanji = ["日", "月", "火", "水", "木", "金", "土"][(int)date('w', strtotime($date))];

        // 3. 全コース・全期間の時間割データを取得
        $allTimetables = $this->serviceTimeTable->ChangeConsideringAllTimetables();

        // 4. 【重要】「選択コース」かつ「日付が範囲内」のデータだけに絞り込む
        $targetTimetable = null;
        foreach ($allTimetables as $timetable) {
            if ((int)$timetable['courseId'] === $courseId) {
                // 日付が開始日と終了日の範囲内かチェック
                if ($date >= $timetable['startDate'] && $date <= $timetable['endDate']) {
                    $targetTimetable = $timetable;
                    break; // 条件に合う期間は1つのはずなので抜ける
                }
            }
        }

        // 5. 絞り込んだデータから「その曜日」の授業を抽出して整形
        $finalSchedule = [];
        if ($targetTimetable && isset($targetTimetable['data'])) {
            // まずは基本の曜日データをセット
            foreach ($targetTimetable['data'] as $detail) {
                if ($detail['day'] === $dayOfWeekKanji) {
                    $period = (int)$detail['period'];
                    $finalSchedule[$period] = [
                        'subject_name' => $detail['className'] ?? '',
                        'teacher_name' => $detail['teacherName'] ?? '',
                        'room_name'    => $detail['roomName'] ?? '',
                        'is_changed'   => false, // デフォルトは「変更なし」
                        'class_detail' => $data['schedule_by_period'][$period]['class_detail'] ?? '詳細情報はありません。',
                        'bring_object' => $data['schedule_by_period'][$period]['bring_object'] ?? '特になし',
                    ];
                }
            }

            // --- 変更データ（existing_changes）の適用部分 ---
            if (isset($targetTimetable['existing_changes'])) {
                foreach ($targetTimetable['existing_changes'] as $change) {
                    // TimetableService.php 98行目で 'date' というキーで整形されているため
                    if ($change['date'] === $date) {
                        $period = (int)$change['period'];
            
                        // 該当する時限のデータを上書き
                        $finalSchedule[$period] = [
                            // TimetableService.php 100-101行目で整形されているキー名に合わせる
                            'subject_name' => $change['subjectName'] ?? '',
                            
                            // 先生は複数いる可能性があるが、表示用には最初の1人を出すか、
                            // Service側で rooms[0]['name'] のように入っているはず
                            'teacher_name' => $change['teachers'][0]['name'] ?? '未定',
                            
                            // ★ここが重要！ Service 117行目で 'rooms' 配列として整形されている
                            'room_name'    => $change['rooms'][0]['name'] ?? '未定',
                            
                            'is_changed'   => true,
                            'class_detail' => $data['schedule_by_period'][$period]['class_detail'] ?? '詳細情報はありません。',
                            'bring_object' => $data['schedule_by_period'][$period]['bring_object'] ?? '特になし',
                        ];
                    }
                }
            }
        }
        
        // 6. ビューに渡すデータを整理
        $viewData = $data;
        $viewData['schedule_by_period'] = $finalSchedule; // フィルタリングしたデータで上書き
        $viewData['selected_course'] = $courseId;

        // デバッグ用（不要になったら消してください）
        $testdata = $targetTimetable; 

        extract($viewData);
        require_once '../student_home.php';
    }
}
 // ユーザーアイコン表示用
 $data['user_picture'] = $_SESSION['user_picture'] ?? 'images/default_icon.png';
 extract($data);

 $smartcampus_picture = '../images/smartcampus.png';