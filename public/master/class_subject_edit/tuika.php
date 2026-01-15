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

// 【処理ロジック - 変更なし】
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $title = $_POST['subject_name'] ?? $_POST['title'] ?? '';
    $raw_grade = $_POST['grade'] ?? '';
    try {
        if ($action === 'update_field') {
            $field = $_POST['field']; $new_val = $_POST['value']; $grade_int = (int)$raw_grade;
            foreach ($courseInfo as $info) {
                $final_val = $new_val;
                if ($field === 'teacher' && ($_POST['mode'] ?? 'overwrite') === 'add') {
                    $stmt = $pdo->prepare("SELECT teacher FROM `{$info['table']}` WHERE `subject_name` = ? AND `grade` = ?");
                    $stmt->execute([$title, $grade_int]);
                    $current = $stmt->fetchColumn();
                    if ($current && $current !== '未設定') {
                        $ex = explode('、', $current);
                        if (!in_array($new_val, $ex)) $final_val = $current . '、' . $new_val;
                        else $final_val = $current;
                    }
                }
                $sql = "UPDATE `{$info['table']}` SET `$field` = :val WHERE `subject_name` = :name AND `grade` = :grade";
                $pdo->prepare($sql)->execute([':val' => $final_val, ':name' => $title, ':grade' => $grade_int]);
            }
            echo json_encode(['success' => true]); exit;
        } elseif ($action === 'insert_new' || $action === 'add_course') {
            $targets = [];
            if ($raw_grade === 'all') $targets = $courseInfo;
            elseif ($raw_grade === '1_all') { foreach($courseInfo as $k => $v) if($v['grade'] == 1) $targets[$k] = $v; }
            elseif ($raw_grade === '2_all') { foreach($courseInfo as $k => $v) if($v['grade'] == 2) $targets[$k] = $v; }
            else { $ck = $_POST['course'] ?? $_POST['course_key']; if(isset($courseInfo[$ck])) $targets[$ck] = $courseInfo[$ck]; }

            foreach ($targets as $info) {
                $sql = "INSERT INTO `{$info['table']}` (couse, grade, subject_name, teacher, room) VALUES (?, ?, ?, '未設定', '未設定')";
                $pdo->prepare($sql)->execute([$info['name'], $info['grade'], $title]);
            }
            if ($action === 'insert_new') { header("Location: tuika.php"); exit; }
            echo json_encode(['success' => true]); exit;
        } elseif ($action === 'remove_course') {
            $table = $courseInfo[$_POST['course_key']]['table'];
            $pdo->prepare("DELETE FROM `$table` WHERE subject_name = ? AND grade = ?")->execute([$title, (int)$raw_grade]);
            echo json_encode(['success' => true]); exit;
        }
    } catch (Exception $e) { http_response_code(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]); exit; }
}

// データ取得ロジック
$search_grade = $_GET['search_grade'] ?? 'all';
$search_course = $_GET['search_course'] ?? 'all';
$grade_val = ($search_grade === '1年生') ? 1 : (($search_grade === '2年生') ? 2 : null);

$subjects = [];
foreach ($courseInfo as $key => $info) {
    if ($search_course !== 'all' && $search_course !== $key) continue;
    $sql = "SELECT couse, grade, subject_name, teacher, room FROM `{$info['table']}`";
    if ($grade_val) {
        $stmt = $pdo->prepare($sql . " WHERE grade = ?"); $stmt->execute([$grade_val]);
    } else { $stmt = $pdo->query($sql); }
    while ($row = $stmt->fetch()) {
        $id = $row['subject_name']; 
        if (!isset($subjects[$id])) {
            $subjects[$id] = ['grade' => $row['grade'], 'title' => $row['subject_name'], 'teacher' => $row['teacher'], 'room' => $row['room'], 'courses' => [], 'course_keys' => []];
        }
        $subjects[$id]['courses'][] = $row['couse']; $subjects[$id]['course_keys'][] = $key;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>科目編集システム</title>
    <link rel="stylesheet" href="style.css"> </head>
<body class="tuika-page"> <header class="header"><h1>科目の編集</h1></header>
    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-section"><button class="sidebar-add-btn" onclick="openAddModal()">＋ 新規科目追加</button></div>
            <hr style="border: 0; border-top: 1px solid #e0e6ed; margin: 0 20px 20px 20px;">
            <form action="tuika.php" method="GET">
                <div class="sidebar-section">
                    <label class="sidebar-title">実施学年検索</label>
                    <select class="sidebar-select" name="search_grade" onchange="this.form.submit()">
                        <option value="all" <?= $search_grade === 'all' ? 'selected' : '' ?>>すべて</option>
                        <option value="1年生" <?= $search_grade === '1年生' ? 'selected' : '' ?>>1年生</option>
                        <option value="2年生" <?= $search_grade === '2年生' ? 'selected' : '' ?>>2年生</option>
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

        <main class="subject-list">
            <?php foreach ($subjects as $s): ?>
            <div class="subject-card" onclick='openEditModal(<?= json_encode($s) ?>)'>
                <div class="card-header"><?= htmlspecialchars($s['grade']) ?>年生</div>
                <div class="card-body"><div class="card-title"><?= htmlspecialchars($s['title']) ?></div></div>
                <div class="card-footer"><?= htmlspecialchars($s['teacher']) ?> / <?= htmlspecialchars($s['room']) ?></div>
            </div>
            <?php endforeach; ?>
        </main>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <h3 class="modal-title">科目の詳細・編集</h3>
            <div class="info-box">
                <div class="info-row"><span class="info-label">科目名:</span><span id="m-title" class="info-value"></span></div>
                <div class="info-row"><span class="info-label">学年:</span><span id="m-grade" class="info-value"></span></div>
            </div>
            <button class="btn-close" onclick="closeModal()">閉じる</button>
        </div>
    </div>

    <script>
        let currentData = null;
        function openEditModal(data) {
            currentData = data;
            document.getElementById('m-title').innerText = data.title;
            document.getElementById('m-grade').innerText = data.grade + "年生";
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeModal() { document.getElementById('editModal').style.display = 'none'; }
        window.onclick = (e) => { if (e.target.className === 'modal-overlay') closeModal(); }
    </script>
</body>
</html>