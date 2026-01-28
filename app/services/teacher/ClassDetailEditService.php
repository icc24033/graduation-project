<?php
// ClassDetailEditService.php
// 授業詳細編集に関するサービスクラス
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../classes/repository/class_detail/ClassDailyInfoRepository.php';

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

    /**
     * カレンダー表示用のデータ構築ロジック（表示機能１の核心）
     * 基本時間割 + 変更情報 + 保存済みステータス をマージする
     */
    public function getCalendarData($teacherId, $subjectId, array $courseIds, $year, $month) {

        $timetableRepo = RepositoryFactory::getTimetableRepository();
        $dailyInfoRepo = RepositoryFactory::getClassDailyInfoRepository();

        // 1. 期間の定義
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        // 2. DBからデータ取得
        // A. 基本時間割 (曜日ごとのルール)
        $basicTimetables = $timetableRepo->getBasicTimetablesByCourseIds($courseIds);
        // B. 変更情報 (イレギュラー)
        $changes = $timetableRepo->getTimetableChangesByPeriod($courseIds, $startDate, $endDate);
        // C. 保存済み情報 (ステータス)
        // ※ course_ids は複数あるため、一旦すべて取得
        // findByMonthAndSubject は array($year, $month, $subjectId, $courseId) を想定
        // 今回は複数コース対応のため、ループまたはIN句対応のリポジトリ修正が望ましいが、
        // 既存の findByMonthAndSubject をループで呼ぶ形で実装します。
        $savedInfos = [];
        foreach ($courseIds as $cId) {
            $infos = $dailyInfoRepo->findByMonthAndSubject($year, $month, $subjectId, $cId);
            foreach ($infos as $info) {
                // 日付_時限_コースID をキーにして保存データを管理
                $key = $info['date'] . '_' . $info['period'] . '_' . $info['course_id'];
                $savedInfos[$key] = $info;
            }
        }

        // 3. 変更情報を検索しやすい形に整形
        // Key: "YYYY-MM-DD_Period_CourseID" => Value: change_record
        $changesMap = [];
        foreach ($changes as $ch) {
            $key = $ch['change_date'] . '_' . $ch['period'] . '_' . $ch['course_id'];
            $changesMap[$key] = $ch;
        }

        // 4. カレンダーデータの生成
        $calendarData = [];
        $currentDate = strtotime($startDate);
        $endDateTs = strtotime($endDate);

        while ($currentDate <= $endDateTs) {
            $dateStr = date('Y-m-d', $currentDate);
            $dayOfWeek = date('N', $currentDate); // 1(月)~7(日)

            // その日のスロットリスト（授業リスト）
            $daySlots = [];
            $hasChangeClass = false; // その日に変更による授業があるか（赤丸フラグ）

            // 各コースごとに判定
            foreach ($courseIds as $courseId) {
                // 1限〜8限くらいまでをスキャン（あるいは基本時間割にある時限のみ）
                // ここでは1-6限と仮定してループ、またはマスタから取得
                for ($period = 1; $period <= 6; $period++) {
                    
                    // --- 判定ロジック ---
                    $targetSubjectId = null;
                    $isChange = false; // 変更によってこの授業になったか

                    // キー生成
                    $changeKey = $dateStr . '_' . $period . '_' . $courseId;

                    if (isset($changesMap[$changeKey])) {
                        // A. 変更情報がある場合（優先）
                        $ch = $changesMap[$changeKey];
                        $targetSubjectId = $ch['subject_id']; // 変更後の科目ID（休講ならNULL等）
                        $isChange = true;
                    } else {
                        // B. 変更がない場合 -> 基本時間割を適用
                        foreach ($basicTimetables as $bt) {
                            // コース一致 AND 期間内 AND 曜日一致 AND 時限一致
                            if ($bt['course_id'] == $courseId &&
                                $dateStr >= $bt['start_date'] && 
                                $dateStr <= $bt['end_date'] &&
                                $bt['day_of_week'] == $dayOfWeek &&
                                $bt['period'] == $period) {
                                
                                $targetSubjectId = $bt['subject_id'];
                                break; 
                            }
                        }
                    }

                    // --- フィルタリング ---
                    // 「現在選択中の科目 ($subjectId)」と一致する場合のみリストに追加
                    if ($targetSubjectId == $subjectId) {
                        
                        // 保存済みデータの確認
                        $saveKey = $dateStr . '_' . $period . '_' . $courseId;
                        $savedInfo = $savedInfos[$saveKey] ?? null;

                        // ステータスの決定
                        $status = 'not-created'; // デフォルト: 未作成
                        $statusText = '未作成';
                        if ($savedInfo) {
                            if ($savedInfo['status_type'] == 1) {
                                $status = 'creating'; // 作成中（黄色）
                                $statusText = '作成中';
                            } elseif ($savedInfo['status_type'] == 2) {
                                $status = 'in-progress'; // 作成済み（赤/完了）
                                $statusText = '作成済';
                            }
                        }

                        $daySlots[] = [
                            'period' => $period,
                            'slot' => $period . '限',
                            'course_id' => $courseId,
                            'status' => $status,
                            'statusText' => $statusText,
                            'is_change' => $isChange, // これがTrueなら赤丸対象
                            // モーダル表示用データ（保存データがあればそれを使う）
                            'content' => $savedInfo['content'] ?? '',
                            'belongings' => $savedInfo['belongings'] ?? ''
                        ];

                        if ($isChange) {
                            $hasChangeClass = true;
                        }
                    }
                }
            }

            // データがある日だけ結果に追加
            if (!empty($daySlots)) {
                // 時限順にソート
                usort($daySlots, function($a, $b) {
                    return $a['period'] - $b['period'];
                });

                $calendarData[$dateStr] = [
                    'slots' => $daySlots,
                    'circle_type' => $hasChangeClass ? 'red' : 'blue' // 赤丸か青丸か
                ];
            }

            $currentDate = strtotime('+1 day', $currentDate);
        }

        return $calendarData;
    }
}