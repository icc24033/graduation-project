<?php
// ClassDetailEditService.php
// 授業詳細編集に関するサービスクラス
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../classes/repository/class_detail/ClassDailyInfoRepository.php';

class ClassDetailEditService {

    public function getSubjectsArayMarge($assignedClasses, $substituteClasses) {
        // もしassignedClassesで取得していない科目があれば '代理' を追加
            foreach ($substituteClasses as &$subClass) {
                $isAlreadyAssigned = false;
                foreach ($assignedClasses as $assignClass) {
                    if ($assignClass['subject_id'] === $subClass['subject_id']) {
                        $isAlreadyAssigned = true;
                        break;
                    }
                }
                if (!$isAlreadyAssigned) {
                    $subClass['subject_name'] .= ' (代理)';
                }
            }

            // 配列を結合
            $mergedClasses = array_merge($assignedClasses, $substituteClasses);

            // 重複を削除 (course_id と subject_id の組み合わせでユニークにする)
            $uniqueMap = [];
            foreach ($mergedClasses as $class) {
                // 一意なキーを作成
                $key = $class['course_id'] . '-' . $class['subject_id'];
                // まだ登録されていなければ追加（上書きしないことで正規担当を優先、といってもデータは同じなのでどちらでも良い）
                if (!isset($uniqueMap[$key])) {
                    $uniqueMap[$key] = $class;
                }
            }
            
            // インデックス付き配列に戻し、学年・クラス・科目順などでソートし直す
            $assignedClasses = array_values($uniqueMap);
            
            // 表示順序を整える（学年昇順 > コースID昇順 > 科目ID昇順）
            // SQLのORDER BYで取得していますが、マージしたため念のため再ソート
            // ただし、'代理'は必ず最後に来るようにする
            usort($assignedClasses, function ($a, $b) {
                if ($a['grade'] !== $b['grade']) {
                    return $a['grade'] <=> $b['grade'];
                }
                if ($a['course_id'] !== $b['course_id']) {
                    return $a['course_id'] <=> $b['course_id'];
                }
                if (strpos($a['subject_name'], '代理') !== false && strpos($b['subject_name'], '代理') === false) {
                    return 1; // aが代理なら後ろ
                }
                if (strpos($a['subject_name'], '代理') === false && strpos($b['subject_name'], '代理') !== false) {
                    return -1; // bが代理なら前
                }
                return $a['subject_id'] <=> $b['subject_id'];
            });

        return $assignedClasses;
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
        $basicTimetables = $timetableRepo->getBasicTimetablesByCourseIds($courseIds);
        $changes = $timetableRepo->getTimetableChangesByPeriod($courseIds, $startDate, $endDate);
        
        // 保存済み情報
        $savedInfos = [];
        foreach ($courseIds as $cId) {
            $infos = $dailyInfoRepo->findByMonthAndSubject($year, $month, $subjectId, $cId);
            foreach ($infos as $info) {
                $key = $info['date'] . '_' . $info['period'] . '_' . $info['course_id'];
                $savedInfos[$key] = $info;
            }
        }

        // 3. 変更情報を検索しやすい形に整形
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
            $dayOfWeek = date('N', $currentDate);

            // ここ（日付ごとのループの先頭）で初期化しないと、前のループのデータが残ったり、
            // 内側のループで初期化すると最後の授業しか残らなくなります。
            $daySlots = []; 
            $hasChangeClass = false;

            // 各コースごとに判定
            foreach ($courseIds as $courseId) {
                // ★注意：ここで $daySlots = [] をしないこと！

                // 1限〜6限をスキャン
                for ($period = 1; $period <= 6; $period++) {
                    // ★注意：ここで $daySlots = [] をしないこと！

                    $targetSubjectId = null;
                    $isChange = false;

                    // キー生成
                    $changeKey = $dateStr . '_' . $period . '_' . $courseId;

                    if (isset($changesMap[$changeKey])) {
                        // A. 変更情報がある場合
                        $ch = $changesMap[$changeKey];
                        $targetSubjectId = $ch['subject_id'];
                        $isChange = true;
                    } else {
                        // B. 変更がない場合 -> 基本時間割
                        foreach ($basicTimetables as $bt) {
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

                    // 対象科目のみリストに追加
                    if ($targetSubjectId == $subjectId) {
                        
                        $saveKey = $dateStr . '_' . $period . '_' . $courseId;
                        $savedInfo = $savedInfos[$saveKey] ?? null;

                        $status = 'not-created';
                        $statusText = '未作成';
                        
                        if ($savedInfo) {
                            if ($savedInfo['status_type'] == 1) {
                                $status = 'creating';
                                $statusText = '作成中';
                            } elseif ($savedInfo['status_type'] == 2) {
                                $status = 'in-progress';
                                $statusText = '作成済';
                            }
                        }

                        // ★修正：配列に追加（上書きではない）
                        $daySlots[] = [
                            'period' => $period,
                            'slot' => $period . '限',
                            'course_id' => $courseId,
                            'status' => $status,
                            'statusText' => $statusText,
                            'is_change' => $isChange,
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
                    'circle_type' => $hasChangeClass ? 'red' : 'blue'
                ];
            }

            $currentDate = strtotime('+1 day', $currentDate);
        }

        return $calendarData;
    }
}