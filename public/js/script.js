document.addEventListener('DOMContentLoaded', () => {
 
    // 1. 年度選択 (Dropdown) 

    const yearSelector = document.querySelector('.year-selector');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    // 「年度」項目がクリックされたときに、ドロップダウンの表示/非表示を切り替える
    if (yearSelector && dropdownMenu) {
        yearSelector.addEventListener('click', (event) => {
            // hiddenクラスをトグル（あれば削除、なければ追加）して表示を切り替える
            dropdownMenu.classList.toggle('hidden');
            event.stopPropagation(); // ドキュメント全体へのイベント伝播を停止
        });
    }

    // ドロップダウン外をクリックしたときにドロップダウンを閉じる
    document.addEventListener('click', () => {
        if (dropdownMenu && !dropdownMenu.classList.contains('hidden')) {
            dropdownMenu.classList.add('hidden');
        }
    });


    // 2. 「追加」ボタン (テーブル行の追加) 

    const addButton = document.querySelector('.add-button');
    const studentTableBody = document.querySelector('.students-table tbody');

    /*
     新しい学生データ用のテーブル行 (<tr>) を生成する関数
    */
    function createNewRow() {
        const newRow = document.createElement('tr');
        // inputタグの初期値を空にして、新しいレコード入力用とする
        newRow.innerHTML = `
            <td><input type="checkbox"></td>
            <td><input type="text" value=""></td>
            <td><input type="text" value=""></td>
            <td><input type="text" value=""></td>
        `;
        return newRow;
    }

    // 「追加」ボタンがクリックされた時のイベント
    if (addButton && studentTableBody) {
        addButton.addEventListener('click', () => {
            const row = createNewRow();
            // テーブルの末尾に新しい行を追加
            studentTableBody.appendChild(row);
            console.log("新しい学生データ行が追加されました。");
        });
    }
});