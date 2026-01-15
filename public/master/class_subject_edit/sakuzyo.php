<?php
// 1. データベース接続設定
$host = 'localhost'; $dbname = 'itira'; $user = 'root'; $password = 'root'; 

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) { die("DB接続エラー: " . $e->getMessage()); }

// 有効なコース情報を取得
$courseInfo = [
    'kihon'         => ['table' => 'kihon_itiran',    'name' => '基本情報'],
    'multimedia'    => ['table' => 'mariti_itiran',   'name' => 'マルチメディア'],
    'applied-info'  => ['table' => 'ouyou_itiran',    'name' => '応用情報'],
    'system-design' => ['table' => 'sisutemu_itiran', 'name' => 'システムデザイン'],
    'web-creator'   => ['table' => 'web_itiran',      'name' => 'Webクリエイター'],
    'itikumi'       => ['table' => 'itikumi',         'name' => '1年1組'],
    'nikumi'        => ['table' => 'nikumi',          'name' => '1年2組']
];

// 【削除処理ロジック - 変更なし】
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_title = $_POST['subject_name'];
    $target_grade = (int)$_POST['grade'];
    $action = $_POST['action'];
    try {
        if ($action === 'delete_single') {
            $courseKey = $_POST['course_key'];
            if (isset($courseInfo[$courseKey])) {
                $table = $courseInfo[$courseKey]['table'];
                $sql = "DELETE FROM `$table` WHERE subject_name = ? AND grade = ?";
                $pdo->prepare($sql)->execute([$target_title, $target_grade]);
            }
        } elseif ($action === 'delete_all') {
            foreach ($courseInfo as $info) {
                $sql = "DELETE FROM `{$info['table']}` WHERE subject_name = ? AND grade = ?";
                $pdo->prepare($sql)->execute([$target_title, $target_grade]);
            }
        }
        header("Location: sakuzyo.php?" . http_build_query($_GET));
        exit;
    } catch (PDOException $e) { $db_error = "削除エラー: " . $e->getMessage(); }
}

// 2. 検索条件・データ取得
$search_grade = $_GET['search_grade'] ?? 'all';
$search_course = $_GET['search_course'] ?? 'all';

$subjects = [];
foreach ($courseInfo as $key => $info) {
    if ($search_course !== 'all' && $search_course !== $key) continue;
    $sql = "SELECT grade, subject_name FROM `{$info['table']}`";
    if ($search_grade !== 'all') {
        $stmt = $pdo->prepare($sql . " WHERE grade = ?");
        $stmt->execute([$search_grade]);
    } else { $stmt = $pdo->query($sql); }

    while ($row = $stmt->fetch()) {
        $id = $row['grade'] . "_" . $row['subject_name'];
        if (!isset($subjects[$id])) {
            $subjects[$id] = ['grade' => $row['grade'], 'title' => $row['subject_name'], 'courses' => [], 'course_keys' => []];
        }
        $subjects[$id]['courses'][] = $info['name'];
        $subjects[$id]['course_keys'][] = $key;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>科目削除システム</title>
    <link rel="stylesheet" href="style.css"> </head>
<body class="sakuzyo-page"> <header class="header"><h1>科目の削除</h1></header>
    <div class="container">
        <nav class="sidebar">
            <form action="sakuzyo.php" method="GET">
                <div class="sidebar-section">
                    <label class="sidebar-title">実施学年検索</label>
                    <select class="sidebar-select" name="search_grade" onchange="this.form.submit()">
                        <option value="all" <?= $search_grade === 'all' ? 'selected' : '' ?>>すべて</option>
                        <option value="1" <?= $search_grade === '1' ? 'selected' : '' ?>>1年生</option>
                        <option value="2" <?= $search_grade === '2' ? 'selected' : '' ?>>2年生</option>
                    </select>
                </div>
                <div class="sidebar-section">
                    <label class="sidebar-title">実施コース検索</label>
                    <select class="sidebar-select" name="search_course" onchange="this.form.submit()">
                        <option value="all" <?= $search_course === 'all' ? 'selected' : '' ?>>すべて</option>
                        <?php foreach ($courseInfo as $key => $info): ?>
                        <option value="<?= $key ?>" <?= $search_course === $key ? 'selected' : '' ?>><?= $info['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <div class="sidebar-section">
                <label class="sidebar-title">メニュー</label>
                <ul class="sidebar-nav">
                    <li><a href="tuika.php">科目の編集</a></li>
                    <li><a href="sakuzyo.php" class="active">科目の削除</a></li>
                </ul>
            </div>
        </nav>

        <main class="subject-list">
            <?php foreach ($subjects as $s): ?>
            <div class="subject-card" onclick='openDeleteModal(<?= json_encode($s) ?>)'>
                <div class="card-header"><?= htmlspecialchars($s['grade']) ?>年生</div>
                <div class="card-body"><div class="card-title"><?= htmlspecialchars($s['title']) ?></div></div>
                <div class="card-footer"><?= htmlspecialchars(implode('、', $s['courses'])) ?></div>
            </div>
            <?php endforeach; ?>
        </main>
    </div>

    <div id="deleteModal" class="modal-overlay">
        <div class="modal-content">
            <h3 class="modal-title">科目の削除</h3>
            <div class="info-box">
                <div class="info-row"><span class="info-label">科目名:</span><span id="m-title" class="info-value"></span></div>
                <div class="info-row"><span class="info-label">学年:</span><span id="m-grade" class="info-value"></span></div>
            </div>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="subject_name" id="f-title">
                <input type="hidden" name="grade" id="f-grade">
                <input type="hidden" name="action" id="f-action">
                <div class="sidebar-section" style="padding:0; margin-bottom:20px;">
                    <label class="sidebar-title">削除するコースを選択</label>
                    <select name="course_key" id="f-course" class="sidebar-select"></select>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn-delete" onclick="setAction('delete_single')">選択したコースから削除</button>
                    <button type="submit" class="btn-delete-all" onclick="setAction('delete_all')">すべてのテーブルからこの科目を消去</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">キャンセル</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDeleteModal(data) {
            document.getElementById('m-title').innerText = data.title;
            document.getElementById('m-grade').innerText = data.grade + "年生";
            document.getElementById('f-title').value = data.title;
            document.getElementById('f-grade').value = data.grade;
            const sel = document.getElementById('f-course');
            sel.innerHTML = "";
            data.course_keys.forEach((key, i) => {
                let opt = document.createElement('option');
                opt.value = key; opt.text = data.courses[i];
                sel.appendChild(opt);
            });
            document.getElementById('deleteModal').style.display = 'flex';
        }
        function setAction(action) {
            document.getElementById('f-action').value = action;
            const msg = action === 'delete_all' ? '本当にすべてのテーブルからこの科目を消去しますか？' : '選択したコースから削除しますか？';
            if (!confirm(msg)) event.preventDefault();
        }
        function closeModal() { document.getElementById('deleteModal').style.display = 'none'; }
        window.onclick = (e) => { if (e.target.className === 'modal-overlay') closeModal(); }
    </script>
</body>
</html>