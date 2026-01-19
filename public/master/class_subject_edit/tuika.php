<?php
// tuika.php

// --- デバッグ用：エラーを表示させる設定（解決したら削除してください） ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$courseInfo = [   
    'itikumi'       => ['name' => '1年1組', 'grade' => 1, 'course_id' => 7],
    'nikumi'        => ['name' => '1年2組', 'grade' => 1, 'course_id' => 8],
    'iphasu'        => ['name' => 'ITパスポートコース', 'grade' => 1, 'course_id' => 6],
    'kihon'         => ['name' => '基本情報コース', 'grade' => 1, 'course_id' => 5],
    'applied-info'  => ['name' => '応用情報コース', 'grade' => 1, 'course_id' => 4],
    'multimedia'    => ['name' => 'マルチメディアOAコース', 'grade' => 2, 'course_id' => 3],
    'system-design' => ['name' => 'システムデザインコース', 'grade' => 2, 'course_id' => 1],
    'web-creator'   => ['name' => 'Webクリエイターコース', 'grade' => 2, 'course_id' => 2]
];

// 4. データ取得ロジック（表示用のリスト作成）
$grade_val = ($search_grade === '1年生') ? 1 : (($search_grade === '2年生') ? 2 : null);

$subjects = []; // 科目名ごとに集約するための配列
$total_course_count = count($courseList); // 全コース数のカウント


foreach ($classSubjectList as $row) {
    $id = $row['subject_name']; 
        
    if (!isset($subjects[$id])) {
        $subjects[$id] = [
            'grade'   => $row['grade'], 
            'title'   => $row['subject_name'],
            'teachers' => [], // 'teacher' から 'teachers' (配列) に変更
            'room'    => $row['room_name'] ?? '未設定', 
            'courses' => [], 
            'course_keys' => [] 
        ];
    }

    // --- 講師名の追加（重複防止） ---
    if (!empty($row['teacher_name']) && $row['teacher_name'] !== '未設定') {
        if (!in_array($row['teacher_name'], $subjects[$id]['teachers'])) {
            $subjects[$id]['teachers'][] = $row['teacher_name'];
        }
    }

    // 表示用のコース名を追加
    if (!in_array($row['course_name'], $subjects[$id]['courses'])) { // 重複防止
        $subjects[$id]['courses'][] = $row['course_name'];
    }

   // course_id から 'itikumi' などのキーを逆引き
   $found_key = '';
   foreach ($courseInfo as $key => $info) {
       if ($info['course_id'] == $row['course_id']) {
           $found_key = $key;
           break;
       }
   }

   if ($found_key && !in_array($found_key, $subjects[$id]['course_keys'])) { // 重複防止
       $subjects[$id]['course_keys'][] = $found_key; // 数値ではなく識別キーを入れる
   }
}


// --- 全コース対象かどうかの判定 ---
foreach ($subjects as $id => $data) {
    $subjects[$id]['is_all'] = (count($data['course_keys']) === $total_course_count);
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>授業科目一覧</title>
    <link rel="stylesheet" href="../css/add_style.css"> 
</head>
<body>
    <header class="header"><h1>授業科目一覧</h1></header>
    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-section">
                <button class="sidebar-add-btn" onclick="openAddModal()">＋ 新規科目追加</button>
            </div>
            <hr style="border: 0; border-top: 1px solid #e0e6ed; margin: 0 20px 20px 20px;">
            <form action="addition_control.php" method="GET" id="search-form">
                <div class="sidebar-section">
                    <label class="sidebar-title">実施学年検索</label>
                    <select class="sidebar-select" name="search_grade" id="search_grade" onchange="this.form.submit()">
                        <option value="all" <?= $search_grade === 'all' ? 'selected' : '' ?>>すべて</option>
                        <option value="1年生" <?= $search_grade === '1年生' ? 'selected' : '' ?>>1年生</option>
                        <option value="2年生" <?= $search_grade === '2年生' ? 'selected' : '' ?>>2年生</option>
                    </select>
                </div>
                <div class="sidebar-section">
                    <label class="sidebar-title">実施コース検索</label>
                    <select class="sidebar-select" name="search_course" id="search_course" onchange="this.form.submit()">
                        <option value="all" <?= $search_course === 'all' ? 'selected' : '' ?>>すべて</option>
                        <?php foreach ($courseInfo as $key => $info): 
                            if ($grade_val && $info['grade'] !== $grade_val) continue;
                        ?>
                            <option value="<?= $key ?>" <?= $search_course === $key ? 'selected' : '' ?>><?= $info['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <div class="sidebar-section">
                <label class="sidebar-title">メニュー</label>
                <ul class="sidebar-nav">
                    <li><a href="tuika.php" class="active">科目の編集</a></li>
                    <li><a href="sakuzyo.php">科目の削除</a></li>
                </ul>
            </div>
        </nav>

        <section class="subject-list">
            <?php foreach ($subjects as $row): ?>
                <div class="subject-card" onclick='openDetail(<?= json_encode($row) ?>)'>
                    <div class="card-header">
                        <?= $row['is_all'] ? '全体' : $row['grade'] . '年生' ?>
                    </div>
                    <div class="card-body"><h3 class="card-title"><?= htmlspecialchars($row['title']) ?></h3></div>
                    <div class="card-footer"><?= implode(' / ', $row['courses']) ?></div>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal-content">
            <div id="m-title" class="modal-title"></div>
            
            <div class="info-box">
                <span class="info-header">現在の担当講師</span>
                <div class="info-value" id="m-teacher"></div>
                <div class="circle-btn-group">
                    <button class="circle-btn" title="講師を追加" onclick="toggleArea('teacher-add')">＋</button>
                    <button class="circle-btn" title="講師を解除" onclick="toggleArea('teacher-remove')">－</button>
                </div>
                <div id="area-teacher-add" class="selector-area">
                    <select id="sel-teacher" class="sidebar-select">
                        <?php foreach($teacherList as $t): ?>
                            <option value="<?= $t['teacher_name'] ?>"><?= $t['teacher_name'] ?> 先生</option>
                        <?php endforeach; ?>
                    </select>
                    <button class="update-btn" onclick="saveField('teacher', 'add')">講師を追加する</button>
                </div>
                <div id="area-teacher-remove" class="selector-area">
                    <button class="update-btn btn-danger" onclick="clearField('teacher')">全員解除（未設定にする）</button>
                </div>
            </div>

            <div class="info-box">
                <span class="info-header">現在の実施教室</span>
                <div class="info-value" id="m-room"></div>
                <div class="circle-btn-group">
                    <button class="circle-btn" onclick="toggleArea('room-add')">＋</button>
                    <button class="circle-btn" onclick="toggleArea('room-remove')">－</button>
                </div>
                <div id="area-room-add" class="selector-area">
                    <select id="sel-room" class="sidebar-select">
                        <?php foreach($roomList as $r): ?><option value="<?= $r['room_name'] ?>"><?= $r['room_name'] ?></option><?php endforeach; ?>
                    </select>
                    <button class="update-btn" onclick="saveField('room', 'overwrite')">教室を確定する</button>
                </div>
                <div id="area-room-remove" class="selector-area">
                    <button class="update-btn btn-danger" onclick="clearField('room')">教室を解除する</button>
                </div>
            </div>

            <div class="info-box">
                <span class="info-header">現在の実施コース</span>
                <div class="info-value" id="m-courses"></div>
                <div class="circle-btn-group">
                    <button class="circle-btn" onclick="toggleArea('course-add')">＋</button>
                    <button class="circle-btn" onclick="toggleArea('course-remove')">－</button>
                </div>
                <div id="area-course-add" class="selector-area">
                    <select id="sel-course-add" class="sidebar-select"></select>
                    <button class="update-btn" onclick="updateCourse('add_course')">コースを追加</button>
                </div>
                <div id="area-course-remove" class="selector-area">
                    <select id="sel-course-remove" class="sidebar-select"></select>
                    <button class="update-btn btn-danger" onclick="updateCourse('remove_course')">このコースから削除</button>
                </div>
            </div>
            <div style="text-align:center;"><button class="btn-confirm" onclick="location.reload()">完了して閉じる</button></div>
        </div>
    </div>

    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <h2 class="modal-title">新規科目追加</h2>
            <form action="..\..\..\..\app\master\class_subject_edit_backend\backend_subject_add.php" method="POST">
                <input type="hidden" name="action" value="insert_new">
                <div class="info-box">
                    <label class="sidebar-title">学年</label>
                    <select name="grade" id="add_grade" class="sidebar-select" onchange="updateAddModalCourses()">
                        <option value="1">1年生</option>
                        <option value="2">2年生</option>
                        <option value="1_all">1年全体</option>
                        <option value="2_all">2年全体</option>
                        <option value="all">全体</option>
                    </select>
                </div>
                <div class="info-box" id="course_select_box">
                    <label class="sidebar-title">実施コース</label>
                    <select name="course" id="add_course" class="sidebar-select" required></select>
                </div>
                <div class="info-box">
                    <label class="sidebar-title">科目名</label>
                    <input type="text" name="title" required style="width:100%; padding:8px; box-sizing:border-box; border:1px solid #d0dbe9; border-radius:4px;">
                </div>
                <div style="display:flex; justify-content:center; gap:10px; margin-top:20px;">
                    <button type="button" class="sidebar-add-btn" style="background:#ccc; margin:0;" onclick="document.getElementById('addModal').style.display='none'">戻る</button>
                    <button type="submit" class="btn-confirm">保存する</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const allCourseInfo = <?= json_encode($courseInfo) ?>;
        let currentData = {};

        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
            updateAddModalCourses();
        }

        function updateAddModalCourses() {
            const gradeVal = document.getElementById('add_grade').value;
            const courseSelect = document.getElementById('add_course');
            const courseBox = document.getElementById('course_select_box');
            courseSelect.innerHTML = '';
            if (gradeVal.includes('all')) {
                let opt = document.createElement('option');
                opt.value = "bulk_target";
                opt.text = "（対象の全コースに登録されます）";
                courseSelect.appendChild(opt);
                courseBox.style.opacity = "0.5";
            } else {
                courseBox.style.opacity = "1";
                const grade = parseInt(gradeVal);
                for (let key in allCourseInfo) {
                    if (allCourseInfo[key].grade === grade) {
                        let opt = document.createElement('option');
                        opt.value = key;
                        opt.text = allCourseInfo[key].name;
                        courseSelect.appendChild(opt);
                    }
                }
            }
        }

        function openDetail(data) {
            currentData = data;
            document.getElementById('m-title').innerText = data.title;
            
            // 講師表示：配列内の各名前に「 先生」を付与して結合
            let tDisplay = '未設定';
            if (data.teachers && data.teachers.length > 0 && data.teachers[0] !== '未設定') {
                tDisplay = data.teachers.map(t => t + " 先生").join(' / ');
            }
            document.getElementById('m-teacher').innerText = tDisplay;

            // 教室・コース表示
            document.getElementById('m-room').innerText = data.room || '未設定';
            document.getElementById('m-courses').innerText = data.courses.join(' / ');

            // 講師選択セレクトボックスの初期化
            const teacherSel = document.getElementById('sel-teacher');
            teacherSel.selectedIndex = 0;

            // 教室選択セレクトボックスの初期化
            const roomSel = document.getElementById('sel-room');
            roomSel.value = data.room;

            // コース追加用セレクトボックスの生成（その学年で、まだ登録されていないコースを抽出）
            const addSel = document.getElementById('sel-course-add');
            addSel.innerHTML = '<option value="" disabled selected>追加するコースを選択</option>';
            for (let key in allCourseInfo) {
                if (allCourseInfo[key].grade == data.grade && !data.course_keys.includes(key)) {
                    let opt = document.createElement('option');
                    opt.value = key;
                    opt.text = allCourseInfo[key].name;
                    addSel.appendChild(opt);
                }
            }

            // コース削除用セレクトボックスの生成
            const remSel = document.getElementById('sel-course-remove');
            remSel.innerHTML = "";
            data.course_keys.forEach((key, index) => {
                let opt = document.createElement('option');
                opt.value = key;
                opt.text = data.courses[index];
                remSel.appendChild(opt);
            });

            // 編集エリアの非表示初期化とモーダル表示
            document.querySelectorAll('.selector-area').forEach(el => el.style.display = 'none');
            document.getElementById('detailModal').style.display = 'flex';
        }

        function toggleArea(id) {
            const el = document.getElementById('area-' + id);
            const isVisible = el.style.display === 'block';
            document.querySelectorAll('.selector-area').forEach(e => e.style.display = 'none');
            el.style.display = isVisible ? 'none' : 'block';
        }

        // tuika.php の saveField 関数を修正
        function saveField(field, mode) {
            const val = document.getElementById('sel-' + field).value;
            if(!val) return alert("選択してください");
            
            // currentData.course_keys 配列の 0番目（最初のコース）を使用する
            const targetKey = currentData.course_keys && currentData.course_keys.length > 0 
                            ? currentData.course_keys[0] 
                            : null;

            if(!targetKey) return alert("コース情報を特定できませんでした");

            ajax({
                action: 'update_field', 
                field: field, 
                value: val, 
                mode: mode, 
                grade: currentData.grade,
                course_key: targetKey // ★ここで正しいキーが送られるようになります
            });
        }

        // tuika.php 内の clearField 関数を修正
        function clearField(field) {
            if(confirm("解除して『未設定』にしますか？")) {
                // 現在表示している科目の最初のコースキーを取得
                const targetKey = currentData.course_keys && currentData.course_keys.length > 0 
                                ? currentData.course_keys[0] 
                                : null;

                if(!targetKey) return alert("コース情報を特定できませんでした");

                ajax({
                    action: 'update_field', 
                    field: field, 
                    value: '未設定', 
                    mode: 'overwrite', 
                    grade: currentData.grade,
                    course_key: targetKey // ★ これを追加
                });
            }
        }

        function updateCourse(action) {
            const type = (action === 'add_course') ? 'add' : 'remove';
            const courseKey = document.getElementById('sel-course-' + type).value;
            if (!courseKey) return alert("コースを選択してください");
            ajax({action: action, course_key: courseKey, grade: currentData.grade});
        }

        function ajax(data) {
            const fd = new FormData();
            for(let k in data) fd.append(k, data[k]);
            fd.append('subject_name', currentData.title);
            fetch('..\\..\\..\\..\\app\\master\\class_subject_edit_backend\\json_process.php', {method: 'POST', body: fd})
            .then(res => res.json())
            .then(res => { if(res.success) location.reload(); })
            .catch(err => alert("通信エラーが発生しました"));
        }

        window.onclick = (e) => { if(e.target.classList.contains('modal-overlay')) e.target.style.display = 'none'; }
    </script>
</body>
</html>