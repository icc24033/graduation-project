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
        // データの取得
        $rawClassSubjectData = $this->service->getRawClassSubjectData();
        $courseList = $this->service->getCourseList();
        $teacherList = $this->service->getTeacherList();
        $roomList = $this->service->getRoomList();
        $courseInfo = $this->service->getCourseInfoMaster();

        // 1. 学年・コースでフィルタリングされた生リストを取得
        $classSubjectList = $this->service->getFilteredClassSubjects($search_grade, $search_course);

        // 2. 【今回追加】表示用に科目名でグルーピングしたリストを作成
        $subjects = $this->service->getGroupedSubjectList($classSubjectList, $courseInfo);

        // 3. 【今回追加】ビューの検索窓で使う grade_val を作成
        $grade_val = ($search_grade === '1年生') ? 1 : (($search_grade === '2年生') ? 2 : null);

        RepositoryFactory::closePdo();

        // extractでビューに渡す（$subjects と $grade_val も含まれる）
        extract($rawClassSubjectData);
        extract($courseList);
        extract($teacherList);
        extract($roomList);
        
        require_once '../tuika.php';
    }

    /**
     * 授業科目削除画面
     */
    public function index_delete($search_grade, $search_course) {
        // データの取得
        $rawClassSubjectData = $this->service->getRawClassSubjectData();
        $courseList = $this->service->getCourseList();
        $teacherList = $this->service->getTeacherList();
        $roomList = $this->service->getRoomList();
        
        // --- 修正箇所：ハードコード版ではなく DB版を取得 ---
        $courseInfo = $this->service->getCourseInfoMaster(); 
    
        // フィルタリングされた生リストを取得
        $classSubjectList = $this->service->getFilteredClassSubjects($search_grade, $search_course);
    
        // グルーピング処理を実行
        $subjects = $this->service->getGroupedSubjectListForDelete($classSubjectList, $courseInfo);
    
        RepositoryFactory::closePdo();
    
        // ビューへ渡す
        extract($rawClassSubjectData);
        extract($courseList);
        extract($teacherList);
        extract($roomList);
        
        require_once '../sakuzyo.php';
    }
}