<?php
// CreateTimetableController.php

// 必要なファイルの読み込み
require_once __DIR__ . '/../../../classes/security/SecurityHelper.php';
require_once __DIR__ . '/../../../classes/repository/home/HomeRepository.php';
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

class CreateTimetableController extends HomeRepository
{
    /**
     * メイン画面表示処理
     */
    public function index()
    {
        // 1. セッション設定（SSO維持など）
        // ※HomeRepositoryなどの共通クラスに依存
        parent::session_resetting();

        // 2. ログインチェック
        // 未ログインなら弾く処理はコントローラー（またはその親）の責務
        SecurityHelper::requireLogin();

        // 3. 表示に必要なデータの取得
        // 本来はここでRepositoryを使ってDBからデータを取ってきます。
        // 今はセッションからアイコンを取得する処理のみ記述します。
        $user_picture = $_SESSION['user_picture'] ?? 'images/default_icon.png';

        // コース情報の取得
        try {
            $courseRepository = RepositoryFactory::getCourseRepository();
            $courses = $courseRepository->getAllCourses();
        }
        catch (Exception $e) {
            error_log("CreateTimetableController Error: " . $e->getMessage());
            $courses = [];
        }

        // 時間割り情報の取得
        // foreachを使用し、各コースのすべて（現在・未来すべて）の時間割りデータを取得する
        try {
            $timetableRepository = RepositoryFactory::getTimetableRepository();
            $timetablesByCourse = [];
            foreach ($courses as $course) {
                $courseId = $course['course_id'];
                // コースごとの時間割（配列）を取得
                $timetables = $timetableRepository->getTimetablesByCourseId($courseId);
                
                // 取得した時間割りデータを結合する
                if (!empty($timetables)) {
                    $savedTimetables = array_merge($savedTimetables, $timetables);
                }
            }
        }
        catch (Exception $e) {
            error_log("CreateTimetableController Error: " . $e->getMessage());
            $timetablesByCourse = [];
        }
        // ビュー（画面）の読み込み
        // create_timetable.php を読み込む
        require_once __DIR__ . '/../../../../public/master/timetable_create/create_timetable.php';
    }
}