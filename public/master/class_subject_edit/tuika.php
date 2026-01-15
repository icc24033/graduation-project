<?php
// 1. データベース接続
$host = 'localhost'; $dbname = 'itira'; $user = 'root'; $password = 'root'; 
try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) { die("DB接続エラー: " . $e->getMessage()); }

// 2. コース設定
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

// 3. AJAX・フォーム処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $title = $_POST['subject_name'] ?? $_POST['title'] ?? '';
    $raw_grade = $_POST['grade'] ?? '';

    try {
        if ($action === 'update_field') {
            $field = $_POST['field']; 
            $new_val = $_POST['value'];
            $grade_int = (int)$raw_grade;
            $mode = $_POST['mode'] ?? 'overwrite'; // 追加モードか上書きモードか

            foreach ($courseInfo as $info) {
                $final_val = $new_val;

                // 講師の追加処理ロジック
                if ($field === 'teacher' && $mode === 'add') {
                    $stmt = $pdo->prepare("SELECT teacher FROM `{$info['table']}` WHERE `subject_name` = ? AND `grade` = ?");
                    $stmt->execute([$title, $grade_int]);
                    $current = $stmt->fetchColumn();

                    if ($current && $current !== '未設定') {
                        $existing = explode('、', $current);
                        if (!in_array($new_val, $existing)) {
                            $final_val = $current . '、' . $new_val;
                        } else {
                            $final_val = $current;
                        }
                    }
                }

                $sql = "UPDATE `{$info['table']}` SET `$field` = :val WHERE `subject_name` = :name AND `grade` = :grade";
                $pdo->prepare($sql)->execute([':val' => $final_val, ':name' => $title, ':grade' => $grade_int]);
            }
            echo json_encode(['success' => true]); exit;
        } 
        elseif ($action === 'insert_new' || $action === 'add_course') {
            $targets = [];
            if ($raw_grade === 'all') {
                $targets = $courseInfo;
            } elseif ($raw_grade === '1_all') {
                foreach($courseInfo as $k => $v) if($v['grade'] == 1) $targets[$k] = $v;
            } elseif ($raw_grade === '2_all') {
                foreach($courseInfo as $k => $v) if($v['grade'] == 2) $targets[$k] = $v;
            } else {
                $course_key = $_POST['course'] ?? $_POST['course_key'];
                if(isset($courseInfo[$course_key])) $targets[$course_key] = $courseInfo[$course_key];
            }

            foreach ($targets as $info) {
                $table = $info['table'];
                $course_name = $info['name'];
                $grade_val = $info['grade'];
                $sql = "INSERT INTO `$table` (couse, grade, subject_name, teacher, room) VALUES (?, ?, ?, '未設定', '未設定')";
                $pdo->prepare($sql)->execute([$course_name, $grade_val, $title]);
            }

            if ($action === 'insert_new') { header("Location: tuika.php"); exit; }
            echo json_encode(['success' => true]); exit;
        }
        elseif ($action === 'remove_course') {
            $table = $courseInfo[$_POST['course_key']]['table'];
            $grade_int = (int)$raw_grade;
            $sql = "DELETE FROM `$table` WHERE subject_name = ? AND grade = ?";
            $pdo->prepare($sql)->execute([$title, $grade_int]);
            echo json_encode(['success' => true]); exit;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]); exit;
    }
}

// 4. データ取得ロジック
$search_grade = $_GET['search_grade'] ?? 'all';
$search_course = $_GET['search_course'] ?? 'all';
$grade_val = ($search_grade === '1年生') ? 1 : (($search_grade === '2年生') ? 2 : null);

$subjects = [];
$total_course_count = count($courseInfo);

foreach ($courseInfo as $key => $info) {
    if ($search_course !== 'all' && $search_course !== $key) continue;
    $sql = "SELECT couse, grade, subject_name, teacher, room FROM `{$info['table']}`";
    if ($grade_val) {
        $stmt = $pdo->prepare($sql . " WHERE grade = ?");
        $stmt->execute([$grade_val]);
    } else {
        $stmt = $pdo->query($sql);
    }
    while ($row = $stmt->fetch()) {
        $id = $row['subject_name']; 
        if (!isset($subjects[$id])) {
            $subjects[$id] = [
                'grade' => $row['grade'], 
                'title' => $row['subject_name'],
                'teacher' => $row['teacher'], 
                'room' => $row['room'], 
                'courses' => [], 
                'course_keys' => []
            ];
        }
        $subjects[$id]['courses'][] = $row['couse'];
        $subjects[$id]['course_keys'][] = $key;
    }
}
foreach ($subjects as $id => $data) {
    $subjects[$id]['is_all'] = (count($data['course_keys']) === $total_course_count);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>授業科目一覧</title>
    <style>
        body { background-color: #f7f9fb; font-family: sans-serif; margin: 0; height: 100vh; display: flex; flex-direction: column; }
        .header { background-color: #c6e2ff; height: 60px; display: flex; justify-content: center; align-items: center; border-bottom: 1px solid #adcdec; flex-shrink: 0; }
        .header h1 { font-size: 20px; color: #333; margin: 0; }
        .container { display: flex; flex: 1; overflow: hidden; }
        .sidebar { width: 220px; background-color: #f0f5ff; border-right: 1px solid #e0e6ed; padding: 20px 0; overflow-y: auto; }
        .sidebar-section { padding: 0 20px; margin-bottom: 25px; }
        .sidebar-title { font-size: 13px; font-weight: bold; color: #666; margin-bottom: 10px; display: block; }
        .sidebar-select { width: 100%; padding: 8px; border: 1px solid #d0dbe9; border-radius: 4px; }
        .sidebar-nav { list-style: none; padding: 0; margin-bottom: 15px; }
        .sidebar-nav a { text-decoration: none; color: #333; font-size: 14px; padding: 10px 20px; display: block; }
        .sidebar-nav a.active { background-color: #d1e3ff; font-weight: bold; }
        .sidebar-add-btn { display: block; width: 180px; margin: 10px auto; background-color: #4e5d6c; color: #fff; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .subject-list { flex: 1; display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 20px; padding: 20px; align-content: flex-start; overflow-y: auto; }
        .subject-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; aspect-ratio: 1 / 1.1; cursor: pointer; transition: 0.2s; }
        .subject-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card-header { background-color: #edf5ff; padding: 10px 15px; font-weight: bold; font-size: 13px; border-radius: 12px 12px 0 0; }
        .card-body { flex: 1; display: flex; justify-content: center; align-items: center; padding: 15px; text-align: center; }
        .card-title { font-size: 18px; font-weight: 800; color: #333; margin: 0; }
        .card-footer { padding: 10px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #f0f0f0; word-break: break-all; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; padding: 25px; border-radius: 15px; width: 450px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
        .modal-title { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 25px; }
        .info-box { margin-bottom: 20px; text-align: center; }
        .info-header { background-color: #c6e2ff; color: #333; display: block; padding: 8px; font-size: 13px; font-weight: bold; border-radius: 4px 4px 0 0; }
        .info-value { border: 1px solid #e0e6ed; padding: 12px; border-radius: 0 0 4px 4px; background: #fff; margin-bottom: 8px; font-size: 14px; word-break: break-all; }
        .circle-btn-group { display: flex; justify-content: center; gap: 15px; margin-bottom: 10px; }
        .circle-btn { width: 32px; height: 32px; border-radius: 50%; border: 1px solid #d0dbe9; background: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .selector-area { display: none; background: #f9f9f9; padding: 10px; border-radius: 4px; border: 1px dashed #ccc; margin-top: 5px; }
        .btn-confirm { background-color: #4e5d6c; color: white; border: none; padding: 12px 40px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .update-btn { margin-top: 8px; padding: 5px 15px; cursor: pointer; background: #4e5d6c; color: white; border: none; border-radius: 4px; }
        .btn-danger { background:#d9534f !important; }
    </style>
</head>
<body>
    <header class="header"><h1>授業科目一覧</h1></header>
    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-section">
                <button class="sidebar-add-btn" onclick="openAddModal()">＋ 新規科目追加</button>
            </div>
            <hr style="border: 0; border-top: 1px solid #e0e6ed; margin: 0 20px 20px 20px;">
            <form action="tuika.php" method="GET" id="search-form">
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
                        <?php foreach($masterLists['teacher'] as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?> 先生</option>
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
                        <?php foreach($masterLists['room'] as $r): ?><option value="<?= $r ?>"><?= $r ?></option><?php endforeach; ?>
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
            <form action="tuika.php" method="POST">
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
            
            // 講師表示：複数対応
            const tArray = data.teacher.split('、');
            const tDisplay = data.teacher === '未設定' ? '未設定' : tArray.join(' 先生、') + " 先生";
            document.getElementById('m-teacher').innerText = tDisplay;
            
            document.getElementById('m-room').innerText = data.room;
            document.getElementById('m-courses').innerText = data.courses.join(' / ');

            const teacherSel = document.getElementById('sel-teacher');
            teacherSel.selectedIndex = 0;

            const roomSel = document.getElementById('sel-room');
            roomSel.value = data.room;

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

            const remSel = document.getElementById('sel-course-remove');
            remSel.innerHTML = "";
            data.course_keys.forEach((key, index) => {
                let opt = document.createElement('option');
                opt.value = key;
                opt.text = data.courses[index];
                remSel.appendChild(opt);
            });

            document.querySelectorAll('.selector-area').forEach(el => el.style.display = 'none');
            document.getElementById('detailModal').style.display = 'flex';
        }

        function toggleArea(id) {
            const el = document.getElementById('area-' + id);
            const isVisible = el.style.display === 'block';
            document.querySelectorAll('.selector-area').forEach(e => e.style.display = 'none');
            el.style.display = isVisible ? 'none' : 'block';
        }

        function saveField(field, mode) {
            const val = document.getElementById('sel-' + field).value;
            if(!val) return alert("選択してください");
            ajax({action: 'update_field', field: field, value: val, mode: mode, grade: currentData.grade});
        }

        function clearField(field) {
            if(confirm("解除して『未設定』にしますか？")) {
                ajax({action: 'update_field', field: field, value: '未設定', mode: 'overwrite', grade: currentData.grade});
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
            fetch('tuika.php', {method: 'POST', body: fd})
            .then(res => res.json())
            .then(res => { if(res.success) location.reload(); })
            .catch(err => alert("通信エラーが発生しました"));
        }

        window.onclick = (e) => { if(e.target.classList.contains('modal-overlay')) e.target.style.display = 'none'; }
    </script>
</body>
</html>