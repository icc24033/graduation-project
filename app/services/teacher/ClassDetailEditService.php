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
    public function getCalendarData($teacherId, $subjectId, array $courseIds, $year, $month) {
        $infoRepo = RepositoryFactory::getClassDailyInfoRepository();
        
        // 複数コースのうち、代表1つ（最初の1つ）のデータを取得すれば、
        // 「同じ授業を行う」前提なら内容は同じはず。
        // ただし、もしコースごとに日付が違う（月曜クラスと火曜クラス）場合を考慮し、
        // 「対象コース全てのデータを取得してマージ」するのが安全です。
        
        $mergedData = [];

        foreach ($courseIds as $cId) {
            $savedInfos = $infoRepo->findByMonthAndSubject($year, $month, $subjectId, $cId);
            
            foreach ($savedInfos as $info) {
                $date = $info['date'];
                // 既にその日のデータがあれば上書きしない（あるいは最新を優先）
                if (!isset($mergedData[$date])) {
                    $mergedData[$date] = [
                        'slot' => $info['period'] . '限',
                        'status' => ($info['status_type'] == 2) ? 'in-progress' : 'creating',
                        'statusText' => ($info['status_type'] == 2) ? '作成済' : '作成中',
                        'content' => $info['content'],
                        'belongings' => $info['belongings']
                    ];
                }
            }
        }

        // ここに本来は「基本時間割(timetables)」から授業実施日を算出するロジックが入ります。
        // 今回は「保存データがある日だけ表示」する簡易実装とします。
        
        return $mergedData;
    }

    /**
     * 授業詳細の保存処理
     * @param array $data
     * @return bool
     */
    public function saveClassDetail($data) {
        $repo = RepositoryFactory::getClassDailyInfoRepository();
        $statusType = ($data['status'] === 'in-progress' || $data['status'] === '作成済み' || $data['status'] === 'created') ? 2 : 1;
        $courseIds = $data['course_ids'] ?? [];

        if (!is_array($courseIds)) $courseIds = [$courseIds];

        $success = true;
        foreach ($courseIds as $cId) {
            $saveData = [
                'date' => $data['date'],
                'period' => str_replace('限', '', $data['slot']),
                'course_id' => $cId, // ループごとのコースID
                'subject_id' => $data['subject_id'],
                'teacher_id' => $data['teacher_id'],
                'content' => $data['content'],
                'belongings' => $data['belongings'],
                'status_type' => $statusType
            ];
            
            // 1つでも失敗したらfalse扱いにする（トランザクション制御が望ましいが簡易的に）
            if (!$repo->save($saveData)) {
                $success = false;
            }
        }
        return $success;
    }
    
    /**
     * 授業詳細の削除処理
     * @param string $date
     * @param string $slot
     * @param array $courseIds
     * @return bool
     */
    public function deleteClassDetail($date, $slot, $courseIds) {
        $repo = RepositoryFactory::getClassDailyInfoRepository();
        $period = str_replace('限', '', $slot);
        
        if (!is_array($courseIds)) $courseIds = [$courseIds];

        $success = true;
        foreach ($courseIds as $cId) {
            if (!$repo->delete($date, $period, $cId)) {
                $success = false;
            }
        }
        return $success;
    }
}