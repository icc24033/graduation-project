<?php
// ClassSubjectEditController.php

// サービスの読み込み
require_once __DIR__ . '/../../../services/master/ClassSubjectEditService.php';

// RepositoryFactoryの読み込み
require_once __DIR__ . '/../../../classes/repository/RepositoryFactory.php';

// 授業科目編集コントローラー
class ClassSubjectEditController {
    private $service;

    public function __construct() {
        $this->service = new ClassSubjectEditService();
    }

    /**
     * 授業科目追加画面
     */
    public function index_addition($search_grade, $search_course) {
        $courseInfo = [   
            'itikumi'       => ['table' => 'itikumi',         'name' => '1年1組', 'grade' => 1, 'course_id' => 7],
            'nikumi'        => ['table' => 'nikumi',          'name' => '1年2組', 'grade' => 1, 'course_id' => 8],
            'iphasu'        => ['table' => 'iphasu_itiran',   'name' => 'ITパスポートコース', 'grade' => 1, 'course_id' => 6],
            'kihon'         => ['table' => 'kihon_itiran',    'name' => '基本情報コース', 'grade' => 1, 'course_id' => 5],
            'applied-info'  => ['table' => 'ouyou_itiran',    'name' => '応用情報コース', 'grade' => 1, 'course_id' => 4],
            'multimedia'    => ['table' => 'mariti_itiran',   'name' => 'マルチメディアOAコース', 'grade' => 2, 'course_id' => 3],
            'system-design' => ['table' => 'sisutemu_itiran', 'name' => 'システムデザインコース', 'grade' => 2, 'course_id' => 1],
            'web-creator'   => ['table' => 'web_itiran',      'name' => 'Webクリエイターコース', 'grade' => 2, 'course_id' => 2]
        ];
        
        $masterLists = [
            'teacher' => ['松野', '山本', '小田原', '永田', '渡辺', '内田', '川場','田川','森嵜','松浦','山田','船津'],
            'room'    => ['総合実習室', 'プログラム実習室1', 'プログラム実習室2', 'システム設計室2','マルチメディア室1','マルチメディア室2','マルチメディア室3','オープンシステム室','CR1','CR2', '未設定'],
        ];

        if ($search_grade === '1年生') {
            $search_grade_val = 1;
        } elseif ($search_grade === '2年生') {
            $search_grade_val = 2;
        } else {
            $search_grade_val = null;
        }

        $classSubjectData = $this->service->getClassSubjectData();

        RepositoryFactory::closePdo();

        extract($classSubjectData);

        // 1. $search_course（文字列）を対応する course_id に変換
        $target_course_id = null;
        if ($search_course !== 'all' && isset($courseInfo[$search_course])) {
            $target_course_id = $courseInfo[$search_course]['course_id'];
        }

        // 2. 配列をフィルタリング
        $classSubjectList = array_filter($classSubjectList, function ($item) use ($search_grade_val, $target_course_id, $courseInfo, $search_course) {
            
            // 条件A: 学年フィルタリング
            if ($search_grade_val !== null) {
                if ($item['grade'] !== $search_grade_val) {
                    return false;
                }
            }

            // 条件B: コースフィルタリング（修正ポイント）
            if ($target_course_id !== null) {
                // データ側に course_id がないので、courseInfo に定義された 'name' と
                // データの 'course_name' が部分一致するかどうかで判定します
                $target_name = $courseInfo[$search_course]['name'];
                
                // 完全一致だと「1年1組」と「応用情報コース」のように「コース」の有無でズレる可能性があるため
                // strpos を使って「含まれているか」で判定するのが安全です
                if (strpos($item['course_name'], $target_name) === false) {
                    return false;
                }
            }

            return true;
        });

        // 3. 配列の添字（0, 1, 2...）を振り直す
        $classSubjectList = array_values($classSubjectList);

        

        require_once '../tuika.php';
    }

    /**
     * 学年移動画面
     */
    public function index_transfer($course_id, $year) {
        $viewData = $this->service->getEditData();
        $gradeData = $this->service->getGradeData();
        $basic_data = $this->service->getStudentsInCourse($course_id, $year);
        
        RepositoryFactory::closePdo();

        extract($viewData);
        extract($basic_data);
        extract($gradeData);
        require_once '../student_grade_transfer.php';
    }
}