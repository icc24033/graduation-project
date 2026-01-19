<?php
// ClassSubjectEditService.php

// RepositoryFactoryの読み込み
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

class ClassSubjectEditService {

    /**
     * 授業科目一覧の取得
     */
    public function getClassSubjectData() {
        $data = ['classSubjectList' => [], 'error_message' => ''];
        try {
            $subjectInChargesRepo = RepositoryFactory::getSubjectInChargesRepository();
            $data['classSubjectList'] = $subjectInChargesRepo->getAllClassSubjects();
        } catch (Exception $e) {
            error_log("ClassSubjectEditService Error (getClassSubjectData): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * 未加工の授業科目一覧の取得
     */
    public function getRawClassSubjectData() {
        $data = ['rawClassSubjectList' => [], 'error_message' => ''];
        try {
            $subjectInChargesRepo = RepositoryFactory::getSubjectInChargesRepository();
            $data['rawClassSubjectList'] = $subjectInChargesRepo->getRawClassSubjectData();
        } catch (Exception $e) {
            error_log("ClassSubjectEditService Error (getRawClassSubjectData): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * コースリストの取得
     */
    public function getCourseList() {
        $data = ['courseList' => [], 'error_message' => ''];
        try {
            $courseRepo = RepositoryFactory::getCourseRepository();
            $data['courseList'] = $courseRepo->getAllCourses();
        } catch (Exception $e) {
            error_log("StudentAccountService Error (getCourseList): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * 先生一覧の取得
     */
    public function getTeacherList() {
        $data = ['teacherList' => [], 'error_message' => ''];
        try {
            $teacherRepo = RepositoryFactory::getTeacherRepository();
            $data['teacherList'] = $teacherRepo->getAllTeachers();
        } catch (Exception $e) {
            error_log("ClassSubjectEditService Error (getTeacherList): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }

    /**
     * 教室一覧の取得
     */
    public function getRoomList() {
        $data = ['roomList' => [], 'error_message' => ''];
        try {
            $roomRepo = RepositoryFactory::getRoomRepository();
            $data['roomList'] = $roomRepo->getAllRooms();
        } catch (Exception $e) {
            error_log("ClassSubjectEditService Error (getRoomList): " . $e->getMessage());
            $data['error_message'] = "データの読み込みに失敗しました。";
        }
        return $data;
    }
}