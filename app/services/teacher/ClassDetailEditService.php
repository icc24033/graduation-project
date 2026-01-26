<?php
// ClassDetailEditService.php
// 授業詳細編集に関するサービスクラス
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';

class ClassDetailEditService {

    /**
     * getAssignedSubjects
     * 概要：先生が担当している科目リスト（ドロップダウン用）を取得
     * 引数：
     * @param int $teacherId
     * 戻り値：
     * @return array
     */
    public function getAssignedSubjects($teacherId) {
        $repo = RepositoryFactory::getSubjectInChargesRepository();
        // ※SubjectInChargesRepositoryに findSubjectsByTeacherId のようなメソッドが必要です
        // 既存になければ追加実装が必要ですが、ここではある前提で進めます
        // return $repo->findSubjectsByTeacherId($teacherId);
        
        // 仮の実装（動作確認用ダミー）
        return [
            ['subject_id' => 1, 'subject_name' => '数学Ⅰ', 'course_id' => 1, 'course_name' => '1年A組'],
            ['subject_id' => 2, 'subject_name' => '物理', 'course_id' => 1, 'course_name' => '1年A組'],
        ];
    }

    /**
     * カレンダー表示用のデータを生成
     * 基本時間割、変更情報、保存済み詳細をマージして返す
     */
    public function getCalendarData($teacherId, $subjectId, $courseId, $year, $month) {
        // 1. 保存済みの授業詳細データを取得
        $infoRepo = RepositoryFactory::getClassDailyInfoRepository();
        $savedInfos = $infoRepo->findByMonthAndSubject($year, $month, $subjectId, $courseId);
        
        // 検索しやすいように日付をキーにしたマップに変換
        $savedMap = [];
        foreach ($savedInfos as $info) {
            $savedMap[$info['date']] = $info;
        }

        // 2. その月のカレンダー日付を生成＆判定
        // ここでは簡易的に「基本時間割でその科目が設定されている曜日」を特定する必要があります。
        // 本来は TimeTableRepository から「このクラスのこの科目は月曜1限」などの情報を引きます。
        
        // ※実装の複雑さを下げるため、まずは「保存データがある日」と
        // 「固定の曜日（仮）」でデータを構築するロジックの枠組みを作ります。
        
        $calendarData = [];
        $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $timestamp = strtotime($dateStr);
            $dayOfWeek = date('w', $timestamp); // 0(日)~6(土)
            
            // 判定ロジック（実際はDBから取得した時間割と照合）
            // 例: 月曜(1)か水曜(3)なら授業があるとする
            // TODO: ここを TimetableDetailsRepository を使って動的にする
            $isLessonDay = ($dayOfWeek == 1 || $dayOfWeek == 3); 

            // TODO: TimetableChangeRepository で変更/休講をチェック
            
            if ($isLessonDay) {
                // デフォルト状態
                $status = 'not-created';
                $statusText = '未作成';
                $details = '';
                $belongings = '';
                $slot = '1限'; // 仮

                // 保存済みデータがあれば上書き
                if (isset($savedMap[$dateStr])) {
                    $row = $savedMap[$dateStr];
                    $slot = $row['period'] . '限';
                    $details = $row['content'];
                    $belongings = $row['belongings'];
                    
                    if ($row['status_type'] == 1) {
                        $status = 'creating'; // 一時保存
                        $statusText = '作成中';
                    } elseif ($row['status_type'] == 2) {
                        $status = 'in-progress'; // 完了（JSのクラス名に合わせて調整）
                        $statusText = '作成済';
                    }
                }

                $calendarData[$dateStr] = [
                    'slot' => $slot,
                    'status' => $status,
                    'statusText' => $statusText,
                    'details' => $details, // JSで復元用
                    'belongings' => $belongings // JSで復元用
                ];
            }
        }

        return $calendarData;
    }

    /**
     * 授業詳細の保存処理
     */
    public function saveClassDetail($data) {
        $repo = RepositoryFactory::getClassDailyInfoRepository();
        // status文字列を数値コードに変換
        $statusType = ($data['status'] === 'in-progress' || $data['status'] === '作成済み') ? 2 : 1;

        $saveData = [
            'date' => $data['date'],
            'period' => str_replace('限', '', $data['slot']), // "1限" -> 1
            'course_id' => $data['course_id'],
            'subject_id' => $data['subject_id'],
            'teacher_id' => $data['teacher_id'],
            'content' => $data['content'],
            'belongings' => $data['belongings'],
            'status_type' => $statusType
        ];

        return $repo->save($saveData);
    }
    
    /**
     * 授業詳細の削除処理
     */
    public function deleteClassDetail($date, $slot, $courseId) {
        $repo = RepositoryFactory::getClassDailyInfoRepository();
        $period = str_replace('限', '', $slot);
        return $repo->delete($date, $period, $courseId);
    }
}