document.addEventListener('DOMContentLoaded', function() {
    // --- 1. 変数・要素の定義 ---
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    // 初期データ：detailsプロパティを追加して各日の内容を空で定義
    let lessonData = {
        "2026-1-6": { slot: "1限", status: "not-created", statusText: "未作成", details: "" },
        "2026-1-21": { slot: "3限", status: "not-created", statusText: "未作成", details: "" },
        "2026-1-22": { slot: "3限", status: "not-created", statusText: "未作成", details: "" },
        "2026-1-23": { slot: "3限", status: "not-created", statusText: "未作成", details: "" },
        "2026-1-26": { slot: "3限", status: "not-created", statusText: "未作成", details: "" },
        "2026-1-28": { slot: "3限", status: "not-created", statusText: "未作成", details: "" }
    };

    const monthElement = document.querySelector('.month');
    const leftArrowButton = document.querySelector('.left-arrow').closest('button');
    const rightArrowButton = document.querySelector('.right-arrow').closest('button');
    const calendarGrid = document.getElementById('calendarGrid'); 
    const lessonModal = document.getElementById('lessonModal');
    const completeButton = document.querySelector('.complete-button');
    const tempSaveButton = document.querySelector('.temp-save-button');
    const deleteButton = document.querySelector('.delete-button');

    // 持ち物管理用要素
    const deleteIcon = document.querySelector('.delete-icon');
    const addBtn = document.querySelector('.add-button');
    const itemInput = document.querySelector('.add-item-input');
    const itemTagsContainer = document.querySelector('.item-tags');

    let currentYear = 2026;
    let currentMonth = 1;
    let selectedDateKey = null;
    let isDeleteMode = false;
    let selectedItemsForDelete = []; 

    // --- 2. 反映処理（サイドバー一括更新） ---
    // この関数を呼ぶと、lessonDataの状態に合わせてサイドバーの全リストを更新します
    function refreshSidebar() {
const sortedDates = Object.keys(lessonData).sort((a, b) => new Date(a) - new Date(b));
const sidebarItems = document.querySelectorAll('.sidebar .lesson-status-wrapper');

sidebarItems.forEach((targetWrapper, index) => {
const dateDisplay = targetWrapper.querySelector('.lesson-date-item');
const statusBtn = targetWrapper.querySelector('.status-button');

if (index < sortedDates.length) {
    const key = sortedDates[index];
    const data = lessonData[key];
    
    // --- ここから追加 ---
    // サイドバーの項目自体をクリックした時にモーダルを開く設定
    targetWrapper.style.cursor = 'pointer'; // カーソルを指マークにする
    targetWrapper.onclick = function() {
        openModalWithDate(key); // 指定した日付でモーダルを開く共通関数を呼び出す
    };
    // --- ここまで追加 ---

    const dateObj = new Date(key);
    const m = dateObj.getMonth() + 1;
    const d = dateObj.getDate();
    const dayOfWeek = dateObj.toLocaleDateString('ja-JP', { weekday: 'short' });

    if (dateDisplay) {
        dateDisplay.textContent = data.slot ? `${m}月${d}日(${dayOfWeek}) ${data.slot}` : `${m}月${d}日(${dayOfWeek})`;
    }
    if (statusBtn) {
        statusBtn.textContent = data.statusText || "未作成";
        statusBtn.className = `status-button ${data.status || 'not-created'}`;
    }
}
});
}

    // --- 2. 反映処理（カレンダー・サイドバー） ---
    /*  @function updateAllViews
        @description カレンダーのデータ（lessonData）を更新し、カレンダーとサイドバーの表示に即座に反映させます。
        【主な処理内容】
        1. 削除フラグ（isDelete）がtrueの場合：
            ・lessonDataの該当日は削除せず、時限やステータスのみを空にします（青丸を維持するため）。
            ・サイドバーのステータスを「未作成」に戻します。
        2. 通常保存（完了・一時保存）の場合：
            ・引数で渡された時限、ステータス、テキストをlessonDataに保存します。
            ・サイドバーの「日付」と「ステータス」を最新の状態に更新します。
        3. 最後にカレンダー全体を再描画（renderCalendar）して変更を反映させます。
            @param {string} dateKey - 操作対象の日付キー（例: "2026-1-6"）
            @param {string} slot - 時限（例: "1限"）
        @param {string} status - CSSクラス名用のステータス（例: "in-progress"）
            @param {string} text - 表示用のテキスト（例: "作成済み"）
            @param {boolean} isDelete - 削除処理かどうか（デフォルトはfalse）*/

    function updateAllViews(dateKey, slot, status, text, isDelete = false) {
        if (isDelete) {
            if (lessonData[dateKey]) {
                lessonData[dateKey].slot = "";
                lessonData[dateKey].status = "not-created";
                lessonData[dateKey].statusText = "";
            }
        } else {
            lessonData[dateKey] = { slot: slot, status: status, statusText: text };
        }
        refreshSidebar(); // サイドバーを更新
        renderCalendar(currentYear, currentMonth); // カレンダーを更新
    }

    // --- 3. ドロップダウン制御 & タイトル連動 ---
    dropdownToggles.forEach(toggle => {
        const dropdownMenu = toggle.nextElementSibling;

        toggle.addEventListener('click', function(event) {
            event.stopPropagation();
            const isExpanded = this.getAttribute('aria-expanded') === 'true' || false;
            this.setAttribute('aria-expanded', !isExpanded);
            dropdownMenu.classList.toggle('is-open');

            dropdownToggles.forEach(otherToggle => {
                if (otherToggle !== this) {
                    otherToggle.setAttribute('aria-expanded', 'false');
                    otherToggle.nextElementSibling.classList.remove('is-open');
                }
            });
            updateDropdownPosition(this, dropdownMenu);
        });

        dropdownMenu.querySelectorAll('a').forEach(item => {
            item.addEventListener('click', function(event) {
                event.preventDefault();
                const selectedValue = this.textContent;
                toggle.querySelector('.current-value').textContent = selectedValue;

                // タイトルへの反映
                const titleElements = document.querySelectorAll('.course-name-title');
                if (toggle.id === 'gradeDropdownToggle') {
                    if (titleElements[0]) titleElements[0].textContent = selectedValue;
                } else if (toggle.id === 'subjectDropdownToggle') {
                    if (titleElements[1]) titleElements[1].textContent = selectedValue;
                }

                toggle.setAttribute('aria-expanded', 'false');
                dropdownMenu.classList.remove('is-open');
            });
        });
    });

    // --- 4. カレンダー描画関数 ---
    /* @function renderCalendar
      指定された年月のカレンダーを計算し、グリッド（#calendarGrid）に日付セルを描画します。
     【主な処理内容】
     1. 既存のセルをクリア
     2. 月の初日の曜日、月の日数、前月の日数を取得
     3. 35マス（5週間分）のループを回し、前月・当月・翌月の日付を判定して生成
     4. 日曜・土曜のクラス付与と、月表示の更新
     @param {number} year  - 表示したい年（例: 2026）
     @param {number} month - 表示したい月（例: 1）
      @usage 
     // 2026年1月のカレンダーを表示する場合
     renderCalendar(2026, 1);
     // 前月・翌月ボタンのイベント等で動的に呼び出します */

    // 日付キーを指定してモーダルを開く共通関数
function openModalWithDate(dateKey) {
if (!lessonData[dateKey]) return;

selectedDateKey = dateKey;
const [year, month, dayNum] = dateKey.split('-').map(Number);

// localStorageからデータを取得
const storedData = JSON.parse(localStorage.getItem('lessonTextData') || '{}');
const dayData = storedData[dateKey] || { content: "", belongings: "" };

// 1. 各入力欄にデータをセット
const lessonDetailsText = document.querySelector('.lesson-details-textarea');
if (lessonDetailsText) lessonDetailsText.value = dayData.content;

const belongingsText = document.getElementById('detailsTextarea');
if (belongingsText) belongingsText.value = dayData.belongings;

// 2. モーダルのヘッダー日付を更新
const dateObj = new Date(year, month - 1, dayNum);
const dayOfWeek = dateObj.toLocaleDateString('ja-JP', { weekday: 'short' });
const modalTitleDate = document.querySelector('.modal-date');
if (modalTitleDate) modalTitleDate.textContent = `${month}月${dayNum}日(${dayOfWeek})`;

// 3. 表示
lessonModal.style.display = 'flex';
}





    function renderCalendar(year, month) {
        const oldCells = calendarGrid.querySelectorAll('.date-cell');
        oldCells.forEach(cell => cell.remove());
        const firstDay = new Date(year, month - 1, 1).getDay();
        const daysInMonth = new Date(year, month, 0).getDate();
        const prevMonthLastDay = new Date(year, month - 1, 0).getDate();

        for (let i = 0; i < 35; i++) {
            let dayNum, isOutOfMonth = false;
            if (i < firstDay) {
                dayNum = prevMonthLastDay - (firstDay - 1 - i);
                isOutOfMonth = true;
            } else if (i >= firstDay + daysInMonth) {
                dayNum = i - (firstDay + daysInMonth) + 1;
                isOutOfMonth = true;
            } else {
                dayNum = i - firstDay + 1;
            }
            const cell = createDateCell(dayNum, isOutOfMonth, year, month);
            if (i % 7 === 0) cell.classList.add('is-sunday');
            if (i % 7 === 6) cell.classList.add('is-saturday');
            calendarGrid.appendChild(cell);
        }
        monthElement.textContent = `${month}月`;
    }

    /* @function createDateCell
        @description カレンダーの個別の日付セル（1日分）を生成し、イベントを設定します。
        【主な処理内容】
        1. div要素を作成し、日付番号を挿入
        2. 今月以外の日に「is-out-of-month」クラスを付与
        3. lessonDataにデータがある場合、青丸（has-dataクラス）を表示
        4. 授業情報（時限・ステータス）がある場合はラベルを挿入
        5. クリック時にその日付の編集モーダルを開くイベントを設定
        @param {number} dayNum - 日付（1〜31）
        @param {boolean} isOutOfMonth - 今月の範囲外（前月・翌月）かどうか
        @param {number} year - 対象の年
        @param {number} month - 対象の月
        @returns {HTMLElement} 生成された日付セルのDOM要素 */

        function createDateCell(dayNum, isOutOfMonth, year, month) {
        const cell = document.createElement('div');
        cell.className = 'date-cell';
        if (isOutOfMonth) cell.classList.add('is-out-of-month');
        cell.innerHTML = `<span class="date-num">${dayNum}</span>`;
        const dateKey = `${year}-${month}-${dayNum}`;

        if (!isOutOfMonth && lessonData[dateKey]) {
            cell.classList.add('has-data');
            const data = lessonData[dateKey];
            if (data.slot) {
                cell.innerHTML += `
                    <span class="lesson-slot">${data.slot}</span>
                    <span class="status-button ${data.status}">${data.statusText}</span>
                `;
            }
        }

        // --- createDateCell関数内の cell.addEventListener('click', ...) 部分を以下に修正 ---
        cell.addEventListener('click', function() {
            if (isOutOfMonth || !lessonData[dateKey]) return;

            selectedDateKey = dateKey;

            // localStorageから保存データを取得
            const storedData = JSON.parse(localStorage.getItem('lessonTextData') || '{}');
            const dayData = storedData[dateKey] || { content: "", belongings: "" };

            // 1. 授業詳細テキストエリアに表示
            const lessonDetailsText = document.querySelector('.lesson-details-textarea');
            if (lessonDetailsText) {
                lessonDetailsText.value = dayData.content;
            }

            // 2. 持ち物テキストエリアに表示
            const belongingsText = document.getElementById('detailsTextarea');
            if (belongingsText) {
                belongingsText.value = dayData.belongings;
            }

            // --- 日付表示処理 ---
            const dateObj = new Date(year, month - 1, dayNum);
            const dayOfWeek = dateObj.toLocaleDateString('ja-JP', { weekday: 'short' });
            const modalTitleDate = document.querySelector('.modal-date');
            if (modalTitleDate) modalTitleDate.textContent = `${month}月${dayNum}日(${dayOfWeek})`;

            lessonModal.style.display = 'flex';
        });
        return cell;
    }

    // 位置調整関数
    function updateDropdownPosition(toggle, menu) {
        const toggleRect = toggle.getBoundingClientRect();
        const sidebarRect = document.querySelector('.sidebar').getBoundingClientRect();
        let topOffset = (toggle.id === 'gradeDropdownToggle') ? 10 : 0;
        menu.style.top = `${toggleRect.top - sidebarRect.top + topOffset}px`;
        menu.style.left = `${toggleRect.right - sidebarRect.left}px`;
    }

    // --- 5. 各種イベントリスナー ---
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.has-dropdown')) {
            dropdownToggles.forEach(toggle => {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.nextElementSibling.classList.remove('is-open');
            });
        }
    });

    // --- 4. 持ち物管理ロジック ---
    /* @description 持ち物リストの「削除モード」の切り替えを制御します。
      【主な処理内容】
        1. 削除モードの状態（isDeleteMode）を反転させる
        2. ゴミ箱アイコンの見た目をアクティブ状態に変更
        3. 「追加」ボタンのテキストを「削除」に変更し、スタイルを切り替える
        4. モード切り替え時に入力欄のリセットや、選択済みアイテムの解除を行う
        @usage 
        ゴミ箱アイコン（deleteIcon）をクリックすると発火します。
        削除したいアイテムがある時にこのモードをONにし、下のボタンで実行します。*/

    /* @description 削除モードの切り替えと、タイトルの動的変更 */
    deleteIcon.addEventListener('click', function() {
        // 1. モードの状態を反転
        isDeleteMode = !isDeleteMode;
        
        // 2. アイコンとボタンの見た目を切り替え
        this.classList.toggle('is-active', isDeleteMode);
        addBtn.textContent = isDeleteMode ? '削除' : '追加';
        addBtn.classList.toggle('is-delete-mode', isDeleteMode);

        // 3. IDを使ってタイトルを直接書き換える
        const templateTitle = document.getElementById('template-title');
        if (templateTitle) {
            templateTitle.textContent = isDeleteMode ? "削除するテンプレートを選択して下さい" : "よく使う持ち物テンプレート";
        }

        // 4. モード切り替え時のリセット処理
        document.querySelectorAll('.item-tag-container').forEach(tag => {
            tag.classList.remove('is-selected');
        });
        selectedItemsForDelete = [];
        itemInput.value = '';
    });

    /* @description 持ち物リスト（タグ）をクリックした際の挙動を制御します。
     【主な処理内容】
       1. クリックされた要素がタグ（.item-tag-container）であるか判定
       2. 削除モードの場合：
        ・タグの選択状態（is-selected）を切り替える
        ・削除予定リスト（selectedItemsForDelete）へ追加または削除
        ・入力欄に選択中のアイテム名を「、」区切りで表示
    　 3. 追加モード（通常モード）の場合：
        ・クリックしたアイテム名を入力欄の末尾に「、」で繋げて追加
        ・既に入力がある場合のみ「、」を挟む */

        /* @description よく使う持ち物をクリックした際、追加と削除を交互に切り替える（トグル動作） */
    itemTagsContainer.addEventListener('click', function(e) {
        const tagContainer = e.target.closest('.item-tag-container');
        if (!tagContainer) return;
        const itemName = tagContainer.querySelector('.item-tag').textContent;

        if (isDeleteMode) {
            // --- 削除モード（既存の挙動を維持） ---
            tagContainer.classList.toggle('is-selected');
            if (tagContainer.classList.contains('is-selected')) {
                selectedItemsForDelete.push(itemName);
            } else {
                const index = selectedItemsForDelete.indexOf(itemName);
                if (index > -1) {
                    selectedItemsForDelete.splice(index, 1);
                }
            }
            itemInput.value = selectedItemsForDelete.join('、');
        } else {
            // --- 追加モード：クリックごとに「追加 ⇔ 削除」を切り替える ---
            const detailsTextarea = document.getElementById('detailsTextarea');
            if (detailsTextarea) {
                let currentText = detailsTextarea.value.trim();
                // 現在の入力を「、」で分割して配列化（空の場合は空配列）
                let items = currentText === "" ? [] : currentText.split('、');

                const index = items.indexOf(itemName);
                if (index === -1) {
                    // 1. 一覧に存在しない場合：追加（1回目、3回目...）
                    items.push(itemName);
                } else {
                    // 2. 一覧に存在する場合：削除（2回目、4回目...）
                    items.splice(index, 1);
                }

                // 配列を「、」で結合してテキストエリアに戻す
                detailsTextarea.value = items.join('、');
            }
        }
    });
    /* @description 持ち物リストへの「追加実行」または「削除実行」を制御します。
    【主な処理内容】
    　1. 削除モード（isDeleteMode）がONの場合：
        ・選択状態（is-selected）のタグをすべて画面から削除（remove）します。
        ・削除完了後、入力欄と選択リストを空にリセットします。
      2. 削除モードがOFF（通常時）の場合：
        ・入力欄の空白を除去（trim）して取得し、空でなければ新しいタグ要素を作成します。
        ・作成したタグをリストに追加し、入力欄をクリアします。
     @usage 
        ・「追加」または「削除」ボタンをクリックした際に発火します。
        ・モードによって挙動が180度変わるため、ユーザーへの視覚的なフィードバック（ボタンの色変更など）とセットで使用されます。*/

        addBtn.addEventListener('click', function() {
        if (isDeleteMode) {
            document.querySelectorAll('.item-tag-container.is-selected').forEach(tag => tag.remove());
            itemInput.value = '';
            selectedItemsForDelete = [];
        } else {
            const val = itemInput.value.trim();
            if (val !== "") {
                // 入力された文字列を「、」で分割して配列にする
                const items = val.split('、'); 
                
                // 分割した項目ごとにループを回してタグを作成
                items.forEach(item => {
                    const trimmedItem = item.trim();
                    if (trimmedItem !== "") {
                        const newTag = document.createElement('div');
                        newTag.className = 'item-tag-container';
                        newTag.innerHTML = `<span class="item-tag">${trimmedItem}</span>`;
                        itemTagsContainer.appendChild(newTag); // 一つずつ独立した枠として追加
                    }
                });
                itemInput.value = ''; // 入力欄をクリア
            }
        }
    });
    
    // --- 保存用共通関数を新規作成 ---
    function saveTextDataToStorage(status, statusText) {
        if (!selectedDateKey) return;

        const lessonVal = document.querySelector('.lesson-details-textarea').value;
        const belongingsVal = document.getElementById('detailsTextarea').value;

        // 現在の保存データを読み込み、選択中の日付分のみ更新
        const storedData = JSON.parse(localStorage.getItem('lessonTextData') || '{}');
        storedData[selectedDateKey] = {
            content: lessonVal,
            belongings: belongingsVal
        };

        // localStorageへ保存
        localStorage.setItem('lessonTextData', JSON.stringify(storedData));

        // 既存のステータス更新処理を呼び出し
        updateAllViews(selectedDateKey, "1限", status, statusText);
        lessonModal.style.display = 'none';
    }

    // --- モーダル内の各ボタン処理 ---
    /* @description モーダル内の「完了」「一時保存」「削除」ボタンの挙動を制御します。
    【主な処理内容】
        1. 完了ボタン：
            ・選択された日付キー（selectedDateKey）が存在する場合、updateAllViews関数を呼び出し、状態を「作成済み」に更新します。
            ・モーダルを閉じます。
        2. 一時保存ボタン：
            ・選択された日付キーが存在する場合、updateAllViews関数を呼び出し、状態を「作成中」に更新します。
            ・モーダルを閉じます。
        3. 削除ボタン：
            ・選択された日付キーが存在する場合、updateAllViews関数を呼び出し、削除フラグを立ててデータをリセットします。
            ・モーダルを閉じます。
    */

    // 完了ボタンのイベントを書き換え
    completeButton.addEventListener('click', () => {
        // 判定条件：授業詳細または持ち物・準備物が空（空白除去後）であるか確認
        const lessonDetails = document.querySelector('.lesson-details-textarea');
        const belongingsDetails = document.getElementById('detailsTextarea');
        const lessonVal = lessonDetails.value.trim();
        const belongingsVal = belongingsDetails.value.trim();

        // 既存の警告文があれば一旦削除
        const existingAlerts = document.querySelectorAll('.required-alert');
        existingAlerts.forEach(alert => alert.remove());

        // いずれかが空の場合の制御
        if (lessonVal === "" || belongingsVal === "") {
            // ユーザーへの補足：赤字で「※必須入力」を表示
            const alertMsg = document.createElement('span');
            alertMsg.className = 'required-alert';
            alertMsg.textContent = ' ※必須入力です';
            alertMsg.style.color = '#ff0000';
            alertMsg.style.fontSize = '12px';
            alertMsg.style.marginLeft = '10px';

            // 授業詳細が空の場合、タイトル横に警告を表示
            if (lessonVal === "") {
                document.querySelector('.form-title').appendChild(alertMsg.cloneNode(true));
            }
            // 持ち物が空の場合、タイトル横に警告を表示
            if (belongingsVal === "") {
                const titles = document.querySelectorAll('.form-title');
                if (titles[1]) titles[1].appendChild(alertMsg.cloneNode(true));
            }
            
            return; // 処理を中断（反映させない）
        }

        saveTextDataToStorage("in-progress", "作成済み");
    });

    // 一時保存ボタンのイベントを書き換え
    tempSaveButton.addEventListener('click', () => {
        saveTextDataToStorage("creating", "作成中");
    });

    /* @description 削除ボタン押下時の最上部に追加するガード句 */
    deleteButton.addEventListener('click', () => {
    if (selectedDateKey) {
        // 1. 入力があるか判定（授業詳細 or 持ち物入力欄）
        const lessonVal = document.querySelector('.lesson-details-textarea').value.trim();
        const detailsVal = document.getElementById('detailsTextarea').value.trim();
        const hasContent = lessonVal !== "" || detailsVal !== "";

        // 2. 入力がある場合のみconfirmを表示、両方空なら確認なしでtrue（実行）
        const result = hasContent ? window.confirm("この日の授業詳細を削除してもよろしいですか？") : true;

        if (result) {
            // --- 実際の削除処理（ここから下は変更しない） ---
            // 1. localStorageから対象日のデータを削除
            const storedData = JSON.parse(localStorage.getItem('lessonTextData') || '{}');
            delete storedData[selectedDateKey];
            localStorage.setItem('lessonTextData', JSON.stringify(storedData));

            // 2. テキストエリアのリセット
            const lessonDetailsText = document.querySelector('.lesson-details-textarea');
            const belongingsText = document.getElementById('detailsTextarea');
            if (lessonDetailsText) lessonDetailsText.value = '';
            if (belongingsText) belongingsText.value = '';
            if (itemInput) itemInput.value = '';

            // 3. カレンダー表示の更新
            updateAllViews(selectedDateKey, null, null, null, true);
            lessonModal.style.display = 'none';
        }
    }
});

    leftArrowButton.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 1) { currentMonth = 12; currentYear--; }
        renderCalendar(currentYear, currentMonth);
    });

    rightArrowButton.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 12) { currentMonth = 1; currentYear++; }
        renderCalendar(currentYear, currentMonth);
    });

    // --- 授業詳細の文字数カウントアップ処理 ---
    // 授業詳細の入力エリア(textarea)を取得
    const lessonTextArea = document.querySelector('.lesson-details-textarea');
    // 文字数を表示する要素(pクラス名 char-count)を取得
    const charCountDisplay = document.querySelector('.char-count');

    if (lessonTextArea && charCountDisplay) {
        // 入力されるたびに実行されるイベントを設定
        lessonTextArea.addEventListener('input', function() {
            // 現在の入力文字数を取得
            const currentLength = lessonTextArea.value.length;
            // 表示を「現在の文字数/200文字」に書き換え
            charCountDisplay.textContent = `${currentLength}/200文字`;
        });
    }

    // --- ページ読み込み時の入力欄リセット処理 ---
    // 授業詳細のtextarea、持ち物のtextarea、テンプレート追加用のinputを取得
    const resetElements = [
        document.querySelector('.lesson-details-textarea'),
        document.getElementById('detailsTextarea'),
        document.querySelector('.add-item-input')
    ];

    // 取得した各要素の値を空にする
    resetElements.forEach(el => {
        if (el) el.value = '';
    });

    // 文字数カウント表示も初期状態（0/200文字）にリセット
    if (charCountDisplay) {
        charCountDisplay.textContent = '0/200文字';
    }

    // --- 6. 初期表示の実行 ---
    renderCalendar(currentYear, currentMonth);
    refreshSidebar(); // 起動時に青丸の日付をサイドバーに表示

    // 起動時に「テキスト1」の値を右上の「授業名」に反映
    const initialSubject = document.querySelector('#subjectDropdownToggle .current-value').textContent;
    const titleElements = document.querySelectorAll('.course-name-title');
    if (titleElements[1]) {
        titleElements[1].textContent = initialSubject;
    }
});