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
    
    // 講師表示
    let tDisplay = '未設定';
    if (data.teachers && data.teachers.length > 0 && data.teachers[0] !== '未設定') {
        tDisplay = data.teachers.map(t => t + " 先生").join(' / ');
    }
    document.getElementById('m-teacher').innerText = tDisplay;

    // 教室・コース表示
    document.getElementById('m-room').innerText = data.room || '未設定';
    document.getElementById('m-courses').innerText = data.courses.join(' / ');

    // 選択ボックス初期化
    document.getElementById('sel-teacher').selectedIndex = 0;
    document.getElementById('sel-room').value = data.room;

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
    const val = document.getElementById('sel-' + field).value;
    if(!val) return alert("選択してください");
    
    const targetKey = currentData.course_keys && currentData.course_keys.length > 0 ? currentData.course_keys[0] : null;
    if(!targetKey) return alert("コース情報を特定できませんでした");

    ajax({
        action: 'update_field', 
        field: field, 
        value: val, 
        mode: mode, 
        grade: currentData.grade,
        course_key: targetKey
    });
}

function clearField(field) {
    if(confirm("担当教師を全員解除して『未設定』にしますか？ 実施教室と実施コースも全て解除されます。")) {
        const targetKey = currentData.course_keys && currentData.course_keys.length > 0 ? currentData.course_keys[0] : null;
        if(!targetKey) return alert("コース情報を特定できませんでした");

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
    const courseKey = document.getElementById('sel-course-' + type).value;
    
    // 追加時は講師セレクトボックスの値も取得（HTML側にID=sel-teacher-addを用意）
    let teacherId = 0;
    if (action === 'add_course') {
        const tSelect = document.getElementById('sel-teacher-add');
        teacherId = tSelect ? tSelect.value : 0;
    }

    if (!courseKey) return alert("コースを選択してください");
    
    ajax({
        action: action, 
        course_key: courseKey, 
        teacher_id: teacherId, // 講師IDを追加
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