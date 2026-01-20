<?php
// sakuzyo.php 
// 冒頭のループ処理は削除されました。
// すでに Controller から $subjects, $courseInfo, $search_grade 等が渡されています。
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>授業科目一覧 - 削除</title>
    <link rel="stylesheet" href="../css/delete_style.css"> 
</head>
<body>
    <header class="header"><h1>授業科目一覧 (削除)</h1></header>

    <div class="container">
        <nav class="sidebar">
            <form action="delete_control.php" method="GET">
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
                <ul class="sidebar-nav">
                    <li><a href="addition_control.php">科目の編集</a></li>
                    <li><a href="delete_control.php" class="active">科目の削除</a></li>
                </ul>
            </div>
        </nav>

        <section class="subject-list">
            <?php foreach ($subjects as $row): ?>
                <div class="subject-card" onclick='openDeleteModal(<?= json_encode($row) ?>)'>
                    <div class="card-header"><?= $row['grade'] ?>年生</div>
                    <div class="card-body"><h3 class="card-title"><?= htmlspecialchars($row['title']) ?></h3></div>
                    <div class="card-footer"><?= implode(' / ', $row['courses']) ?></div>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <h2 style="font-size: 18px; margin-bottom: 5px;">科目の削除確認</h2>
            <div class="info-box">
                <p id="m-title" style="font-weight: bold; font-size: 1.2em; margin: 5px 0;"></p>
                <p id="m-grade" style="font-size: 0.9em; color: #666;"></p>
            </div>

            <form id="deleteForm" method="POST" action="..\..\..\..\app\master\class_subject_edit_backend\backend_subject_delete.php">
                <input type="hidden" name="subject_name" id="f-title">
                <input type="hidden" name="grade" id="f-grade">
                <input type="hidden" name="action" id="f-action" value="delete_single">
                
                <input type="hidden" name="query_string" value="<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>">

                <div id="single-delete-area">
                    <p style="font-size: 12px; color: #666;">削除するコースを選択してください：</p>
                    <select name="course_key" id="f-course" class="sidebar-select" style="margin-bottom: 10px;"></select>
                    <button type="submit" class="btn-delete-single" onclick="setAction('delete_single')">選択したコースから削除</button>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-delete-all" onclick="setAction('delete_all')">すべてのコースから完全に削除</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">キャンセル</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/subject_delete.js"></script>
</body>
</html>