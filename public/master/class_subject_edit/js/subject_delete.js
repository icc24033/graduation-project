/**
 * 削除確認モーダルを開く
 * @param {Object} data PHPから渡された科目データ
 */
function openDeleteModal(data) {
    // 表示用テキストの設定
    document.getElementById('m-title').innerText = data.title;
    document.getElementById('m-grade').innerText = data.grade + "年生";
    
    // フォームのhidden項目への設定
    document.getElementById('f-title').value = data.title;
    document.getElementById('f-grade').value = data.grade;

    // コース選択セレクトボックスの生成
    const sel = document.getElementById('f-course');
    sel.innerHTML = "";
    data.course_keys.forEach((key, i) => {
        let opt = document.createElement('option');
        opt.value = key;
        opt.text = data.courses[i];
        sel.appendChild(opt);
    });

    // モーダルを表示
    document.getElementById('deleteModal').style.display = 'flex';
}

/**
 * 削除アクション（単一削除か全削除か）を設定し、確認ダイアログを表示する
 * @param {string} action 'delete_single' または 'delete_all'
 */
function setAction(action) {
    document.getElementById('f-action').value = action;
    const confirmMsg = action === 'delete_all' 
        ? '本当にすべてのテーブルからこの科目を消去しますか？' 
        : '選択したコースから削除しますか？';
    
    if (!confirm(confirmMsg)) {
        // インラインの onclick 内で event が参照可能なため event.preventDefault() を使用
        if (window.event) window.event.preventDefault();
    }
}

/**
 * モーダルを閉じる
 */
function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// モーダルの外側をクリックした時に閉じる設定
window.addEventListener('click', (e) => {
    if (e.target.id === 'deleteModal') {
        closeModal();
    }
});