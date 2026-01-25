/**
 * 授業科目編集用JavaScript
 * subject_edit.js
 */

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
        const grade = parseInt(gradeVal);
        for (let id in allCourseInfo) { // idはcourse_idになる
            if (allCourseInfo[id].grade === grade) {
                let opt = document.createElement('option');
                opt.value = id; // ここが数値(course_id)になる
                opt.text = allCourseInfo[id].name;
                courseSelect.appendChild(opt);
            }
        }
    }
}

function openDetail(data) {
    currentData = data;
    document.getElementById('m-title').innerText = data.title;
    
    // 講師表示 (既存通り)
    let tDisplay = '未設定';
    if (data.teachers && data.teachers.length > 0 && data.teachers[0] !== '未設定') {
        tDisplay = data.teachers.map(t => t + " 先生").join(' / ');
    }
    document.getElementById('m-teacher').innerText = tDisplay;

    // --- 修正箇所：教室・コース表示 ---
    // innerText には表示用の room_name を使用
    document.getElementById('m-room').innerText = data.room_name || '未設定'; 
    document.getElementById('m-courses').innerText = data.courses.join(' / ');

    // 選択ボックス初期化
    document.getElementById('sel-teacher').selectedIndex = 0;
    // value (選択値) には ID である room_id をセット
    document.getElementById('sel-room').value = data.room_id || "";

    // コース追加用
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

    // コース削除用
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

    // 講師削除用ドロップダウンの初期化
    const teacherRemSel = document.getElementById('sel-teacher-remove');
    teacherRemSel.innerHTML = '<option value="" disabled selected>解除する講師を選択</option>';
    
    if (data.teacher_ids && data.teacher_ids.length > 0) {
        data.teacher_ids.forEach((id, index) => {
            // teacher_idが0(未設定)でない場合のみリストに追加
            if(id != 0) {
                let opt = document.createElement('option');
                opt.value = id;
                opt.text = data.teachers[index] + " 先生";
                teacherRemSel.appendChild(opt);
            }
        });
    }
}

// 講師を一人削除する関数を新規追加
function removeSingleTeacher() {
    const tId = document.getElementById('sel-teacher-remove').value;
    if (!tId) return alert("講師を選択してください");

    if (confirm("選択した講師の担当を解除しますか？")) {
        // currentData.course_keys[0] など、対象のIDを確実に取得する
        const targetKey = (currentData.course_keys && currentData.course_keys.length > 0) 
                          ? currentData.course_keys[0] : null;

        if(!targetKey) return alert("コース情報が特定できません");

        ajax({
            action: 'update_field',
            field: 'teacher',
            mode: 'remove_single', // PHP側でこれを判定に使う
            teacher_id: tId,
            course_key: targetKey, // ここで送信
            grade: currentData.grade
        });
    }
}

function toggleArea(id) {
    const el = document.getElementById('area-' + id);
    const isVisible = el.style.display === 'block';
    document.querySelectorAll('.selector-area').forEach(e => e.style.display = 'none');
    el.style.display = isVisible ? 'none' : 'block';
}

function saveField(field, mode) {
    const selectEl = document.getElementById('sel-' + field);
    const val = selectEl.value; // roomの場合はIDが入る
    
    if(field === 'room' && (val === "" || val === null)) {
        return alert("教室を選択してください");
    }
    if(!val) return alert("選択してください");
    
    // 科目に関連する全てのコースに適用するため、
    // course_keyは送るが、PHP側でsubject_idをメインに処理させる
    const targetKey = currentData.course_keys && currentData.course_keys.length > 0 ? currentData.course_keys[0] : null;

    ajax({
        action: 'update_field', 
        field: field, 
        value: val, // roomの場合は数値IDが飛ぶ
        mode: mode, 
        grade: currentData.grade,
        course_key: targetKey
    });
}

function clearField(field) {
    let message = "";
    
    if (field === 'teacher') {
        message = "担当教師を全員解除して『未設定』にしますか？";
    } else if (field === 'room') {
        message = "実施教室の設定を解除して『未設定』にしますか？";
    } else {
        message = "設定を解除しますか？";
    }

    if (confirm(message)) {
        const targetKey = currentData.course_keys && currentData.course_keys.length > 0 ? currentData.course_keys[0] : null;
        if (!targetKey) return alert("コース情報を特定できませんでした");

        ajax({
            action: 'update_field', 
            field: field, 
            value: '未設定', 
            mode: 'overwrite', 
            grade: currentData.grade,
            course_key: targetKey
        });
    }
}

function updateCourse(action) {
    const type = (action === 'add_course') ? 'add' : 'remove';
    // IDがHTMLと合っているか確認してください (例: id="sel-course-add")
    const courseSelect = document.getElementById('sel-course-' + type) || document.getElementById('add_course');
    const courseKey = courseSelect.value;
    
    if (!courseKey) return alert("コースを選択してください");

    let teacherIds = [];
    if (action === 'add_course') {
        // 1. 現在表示中の科目に紐付いている講師IDをすべて取得 (既存の1人目など)
        if (currentData && currentData.teacher_ids) {
            // 数値配列としてコピー
            teacherIds = currentData.teacher_ids.map(id => parseInt(id)).filter(id => id > 0);
        }
        
        // 2. モーダルで新しく選択された講師IDを取得
        const tSelect = document.getElementById('sel-teacher-add');
        const newTeacherId = tSelect ? parseInt(tSelect.value) : 0;
        
        // 3. 新しい講師をリストに追加 (重複していなければ)
        if (newTeacherId > 0 && !teacherIds.includes(newTeacherId)) {
            teacherIds.push(newTeacherId);
        }
        
        // 4. 講師が一人もいない場合は 0 (未設定) を送る
        if (teacherIds.length === 0) teacherIds = [0];
    }

    ajax({
        action: action, 
        course_key: courseKey, 
        teacher_ids: teacherIds.join(','), // "101,102" 形式の文字列で送信
        grade: currentData.grade
    });
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