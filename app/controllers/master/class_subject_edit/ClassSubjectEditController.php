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
    public function index_addition() {
        $courseInfo = [   
            'itikumi'       => ['table' => 'itikumi',         'name' => '1年1組', 'grade' => 1],
            'nikumi'        => ['table' => 'nikumi',          'name' => '1年2組', 'grade' => 1],
            'kihon'         => ['table' => 'kihon_itiran',    'name' => '基本情報', 'grade' => 1],
            'applied-info'  => ['table' => 'ouyou_itiran',    'name' => '応用情報', 'grade' => 1],
            'multimedia'    => ['table' => 'mariti_itiran',   'name' => 'マルチメディア', 'grade' => 2],
            'system-design' => ['table' => 'sisutemu_itiran', 'name' => 'システムデザイン', 'grade' => 2],
            'web-creator'   => ['table' => 'web_itiran',      'name' => 'Webクリエイター', 'grade' => 2]
        ];
        
        $masterLists = [
            'teacher' => ['松野', '山本', '小田原', '永田', '渡辺', '内田', '川場','田川','森嵜','松浦','山田','船津'],
            'room'    => ['総合実習室', 'プログラム実習室1', 'プログラム実習室2', 'システム設計室2','マルチメディア室1','マルチメディア室2','マルチメディア室3','オープンシステム室','CR1','CR2', '未設定'],
        ];

        $classSubjectData = $this->service->getClassSubjectData();

        RepositoryFactory::closePdo();

        extract($classSubjectData);

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