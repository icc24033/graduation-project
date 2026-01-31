<?php
// ClassDetailEditService.php
// 授業詳細編集に関するサービスクラス
require_once __DIR__ . '/../../classes/repository/RepositoryFactory.php';
require_once __DIR__ . '/../../classes/repository/class_detail/ClassDailyInfoRepository.php';

class ClassDetailEditService {

    /**
     * 授業詳細の保存処理
     * @param array $input JSONデコードされた入力データ
     * @return bool
     */
    public function saveClassDetail($input) {
        $repo = RepositoryFactory::getClassDailyInfoRepository();
        $timetableRepo = RepositoryFactory::getTimetableRepository(); // 追加

        $period = $input['period_id'] ?? null; 
        if (!$period && isset($input['slot'])) {
            $period = (int) filter_var($input['slot'], FILTER_SANITIZE_NUMBER_INT);
        }

        if (empty($input['date']) || empty($period) || empty($input['course_id']) || empty($input['subject_id'])) {
            error_log("SaveClassDetail Error: Missing fields.");
            return false;
        }

        // 1. 合同授業を行っている他のコースIDも検索する
        $targetCourseIds = $timetableRepo->getCooccurringCourses(
            $input['date'],
            $period,
            $input['subject_id'],
            $input['teacher_id']
        );

        // もし時間割マスタに見つからなくても、少なくとも現在編集中のコースは保存対象にする
        if (!in_array($input['course_id'], $targetCourseIds)) {
            $targetCourseIds[] = $input['course_id'];
        }

        // 2. 対象となる全てのコースに対して保存を実行
        $successCount = 0;
        foreach ($targetCourseIds as $courseId) {
            $data = [
                'date' => $input['date'],
                'period' => $period,
                'course_id' => $courseId, // ここをループごとのIDに変更
                'subject_id' => $input['subject_id'],
                'teacher_id' => $input['teacher_id'],
                'content' => $input['content'] ?? '',
                'belongings' => $input['belongings'] ?? '',
                'status_type' => ($input['status_code'] === 'in-progress') ? 2 : 1 
            ];

            if ($repo->updateOrCreate($data)) {
                $successCount++;
            }
        }

        // 1つでも成功すればOKとみなす（あるいは全成功チェックにするかは要件次第）
        return $successCount > 0;
    }

    /**
     * 授業詳細の削除処理
     * @param string $date 日付 (YYYY-MM-DD)
     * @param string $slotStr 時限文字列 (例: "1限") または数値
     * @param int $courseId コースID
     * @param int $subjectId 科目ID
     * @return bool
     */
    public function deleteClassDetail($date, $slotStr, $courseId, $subjectId) {
        $repo = RepositoryFactory::getClassDailyInfoRepository();
        $timetableRepo = RepositoryFactory::getTimetableRepository(); // 追加
        
        $period = (int) filter_var($slotStr, FILTER_SANITIZE_NUMBER_INT);

        // ログイン中の先生IDが必要になるため、セッションから取得するか、
        // あるいは「この時間・科目の全コース」を無条件で消すならteacher_idは不要ですが、
        // 安全のため「自分が担当している合同授業」を取得した方が良いです。
        $teacherId = $_SESSION['user_id'] ?? null; 

        if (!$teacherId) {
            // API側でチェックしているのでここに来ることは稀ですが念のため
            return false;
        }

        // 1. 合同授業のコースを取得
        $targetCourseIds = $timetableRepo->getCooccurringCourses($date, $period, $subjectId, $teacherId);
        
        if (!in_array($courseId, $targetCourseIds)) {
            $targetCourseIds[] = $courseId;
        }

        // 2. まとめて削除
        $successCount = 0;
        foreach ($targetCourseIds as $cId) {
            if ($repo->deleteDailyInfo($date, $period, $cId, $subjectId)) {
                $successCount++;
            }
        }

        return $successCount > 0;
    }

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
     * カレンダー表示用のデータ構築ロジック（表示機能１の核心）
     * 基本時間割 + 変更情報 + 保存済みステータス をマージする
     */
public function getCalendarData($teacherId, $subjectId, array $courseIds, $year, $month) {

        $timetableRepo = RepositoryFactory::getTimetableRepository();
        $dailyInfoRepo = RepositoryFactory::getClassDailyInfoRepository();

        // 1. 期間の定義
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $calendarData = [];

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
        $currentDate = strtotime($startDate);
        $endDateTs = strtotime($endDate);

        while ($currentDate <= $endDateTs) {
            $dateStr = date('Y-m-d', $currentDate);
            $dayOfWeek = date('N', $currentDate);

            $daySlots = []; 
            // ★修正ポイント1: この変数をここでリセットし、重複チェックに使います
            $processedPeriods = []; 
            $hasChangeClass = false;

            // 各コースごとに判定
            foreach ($courseIds as $courseId) {
                
                // 1限〜6限をスキャン
                for ($period = 1; $period <= 6; $period++) {

                    // ★修正ポイント2: すでにこの時限がリストに追加済みならスキップ
                    if (in_array($period, $processedPeriods)) {
                        continue;
                    }

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

                        // 配列に追加
                        $daySlots[] = [
                            'period' => $period,
                            'slot' => $period . '限',
                            'course_id' => $courseId, // 代表としてこのコースIDを使用
                            'subject_id' => $subjectId,
                            'status' => $status,
                            'statusText' => $statusText,
                            'is_change' => $isChange,
                            'content' => $savedInfo['content'] ?? '',
                            'belongings' => $savedInfo['belongings'] ?? ''
                        ];

                        // ★修正ポイント3: この時限を「処理済み」としてマーク
                        $processedPeriods[] = $period;

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