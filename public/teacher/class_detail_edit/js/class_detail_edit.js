// js/class_detail_edit.js

document.addEventListener('DOMContentLoaded', function() {
    // ============================================================
    // 1. 変数・要素の定義
    // ============================================================
    
    // API設定　現在地を表すパスと、相対パスによる文字列結合を利用
    const API_BASE_URL = location.pathname + '/../../../api/class_detail_edit/class_detail_api.php'; 

    // ドロップダウン用要素（IDを修正後のHTMLに合わせて取得）
    const gradeToggle = document.getElementById('gradeFilterToggle');
    const gradeMenu = document.getElementById('gradeFilterMenu');
    const courseToggle = document.getElementById('courseFilterToggle');
    const courseMenu = document.getElementById('courseFilterMenu');
    const subjectToggle = document.getElementById('subjectSelectorToggle');
    const subjectMenu = document.getElementById('subjectSelectorMenu');

    // カレンダー・モーダル用要素
    const monthElement = document.querySelector('.month');
    const leftArrowButton = document.querySelector('.left-arrow')?.closest('button');
    const rightArrowButton = document.querySelector('.right-arrow')?.closest('button');
    const calendarGrid = document.getElementById('calendarGrid'); 
    const lessonModal = document.getElementById('lessonModal');
    
    // ボタン類
    const completeButton = document.querySelector('.complete-button');
    const tempSaveButton = document.querySelector('.temp-save-button');
    const deleteButton = document.querySelector('.delete-button');

    // 持ち物管理用要素
    const deleteIcon = document.querySelector('.delete-icon');
    const addBtn = document.querySelector('.add-button');
    const itemInput = document.querySelector('.add-item-input');
    const itemTagsContainer = document.querySelector('.item-tags');

    // 状態管理変数
    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth() + 1;
    let selectedDateKey = null; // "YYYY-MM-DD"
    let lessonData = {}; // サーバーから取得した授業詳細データ
    
    // ドロップダウン用状態
    let currentFilters = { grade: "", course: "" }; // フィルタ状態
    let currentSubject = null; // 現在選択中の科目オブジェクト {subject_id, course_id, ...}
    
    // 持ち物削除モード
    let isDeleteMode = false;
    let selectedItemsForDelete = []; 

    // PHPからのデータチェック
    if (typeof assignedClassesData === 'undefined') {
        console.error("assignedClassesData is not defined. PHP側でデータを出力してください。");
        // エラー回避のため空配列を入れておく
        window.assignedClassesData = [];
    }

    // ============================================================
    // 2. 初期化処理
    // ============================================================
    
    initDropdowns(); // ドロップダウンを生成・初期化

    // ============================================================
    // 3. ドロップダウン & フィルタリング ロジック
    // ============================================================

    function initDropdowns() {
        // 3-1. 学年リストの生成（重複排除）
        const grades = [...new Set(assignedClassesData.map(d => d.grade))].sort();
        renderFilterList(gradeMenu, gradeToggle, grades, 'grade', '年生');

        // 3-2. コースリストの生成（重複排除）
        const courseMap = new Map();
        assignedClassesData.forEach(d => courseMap.set(d.course_id, d.course_name));
        renderCourseFilterList(courseMenu, courseToggle, courseMap);

        // 3-3. 科目リストの初期更新（全件表示 -> 先頭を選択）
        updateSubjectList();

        // 3-4. 開閉イベント設定
        setupDropdownToggle(gradeToggle, gradeMenu);
        setupDropdownToggle(courseToggle, courseMenu);
        setupDropdownToggle(subjectToggle, subjectMenu);
    }

    /**
     * フィルタ条件に基づいて科目リストを再生成し、先頭を自動選択する
     */
    function updateSubjectList() {
        // 1. まずフィルタリング（学年・コースで絞り込み）
        const filteredRaw = assignedClassesData.filter(item => {
            const matchGrade = (currentFilters.grade === "") || (String(item.grade) === String(currentFilters.grade));
            const matchCourse = (currentFilters.course === "") || (String(item.course_id) === String(currentFilters.course));
            return matchGrade && matchCourse;
        });

        // 2. 科目IDでグルーピング
        // Map構造: subject_id => { subject_name, course_ids: [], course_names: [] }
        const groupedSubjects = new Map();

        filteredRaw.forEach(item => {
            const sId = item.subject_id;
            if (!groupedSubjects.has(sId)) {
                groupedSubjects.set(sId, {
                    subject_id: sId,
                    subject_name: item.subject_name,
                    courses: [] // 対象となるコース情報を蓄積
                });
            }
            groupedSubjects.get(sId).courses.push({
                id: item.course_id,
                name: item.course_name
            });
        });

        // 3. リスト描画
        subjectMenu.innerHTML = '';
        
        if (groupedSubjects.size === 0) {
            subjectToggle.querySelector('.current-value').textContent = "該当なし";
            subjectMenu.innerHTML = '<li class="dropdown-item">該当する科目がありません</li>';
            currentSubject = null;
            lessonData = {};
            renderCalendar(currentYear, currentMonth);
            return;
        }

        // Mapの値（グルーピングされた科目オブジェクト）を配列にしてループ
        Array.from(groupedSubjects.values()).forEach(group => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = "#";
            
            // 表示名: "C# (システムデザイン, Webクリエイタ)" のようにコースを併記
            const courseNames = group.courses.map(c => c.name).join(', ');
            // 長すぎる場合は省略するなどの工夫も可能
            a.textContent = `${group.subject_name}`;
            
            // ホバー時にコース内訳を表示するなどしても親切
            a.title = `対象コース: ${courseNames}`;
            
            // コース名の補足を表示する（HTMLで構造化）
            const subText = document.createElement('span');
            subText.style.fontSize = '0.85em';
            subText.style.color = '#666';
            subText.style.display = 'block';
            subText.textContent = `対象: ${courseNames}`;
            a.appendChild(subText);

            li.appendChild(a);
            li.addEventListener('click', (e) => {
                e.preventDefault();
                selectSubjectGroup(group); // ★新しい選択関数を呼ぶ
                closeAllDropdowns();
            });
            subjectMenu.appendChild(li);
        });

        // リストの先頭を自動選択
        const firstGroup = groupedSubjects.values().next().value;
        selectSubjectGroup(firstGroup);
    }

    /**
     * 科目グループを選択したときの処理
     * @param {Object} group - { subject_id, subject_name, courses: [{id, name}, ...] }
     */
    function selectSubjectGroup(group) {
        currentSubject = group; // 現在の選択対象をグループオブジェクトにする
        
        // 表示更新
        subjectToggle.querySelector('.current-value').textContent = group.subject_name;
        
        // タイトル更新
        const displayTitle = document.getElementById('displayCourseName');
        if (displayTitle) displayTitle.textContent = group.subject_name;

        // コースIDのリストを作成（ログ確認用）
        const courseIds = group.courses.map(c => c.id);
        console.log(`科目選択(一括): ${group.subject_name}, CourseIDs: [${courseIds.join(', ')}]`);

        // データを取得
        fetchLessonData(currentYear, currentMonth);
    }

    // --- ドロップダウン生成ヘルパー ---

    function renderFilterList(menu, toggle, values, type, suffix) {
        // 「全〇〇」
        addFilterItem(menu, toggle, "全学年", "", type);
        // 各値
        values.forEach(val => {
            addFilterItem(menu, toggle, `${val}${suffix}`, val, type);
        });
    }

    function renderCourseFilterList(menu, toggle, map) {
        // 「全コース」
        addFilterItem(menu, toggle, "全コース", "", 'course');
        // 各コース
        map.forEach((name, id) => {
            addFilterItem(menu, toggle, name, id, 'course');
        });
    }

    function addFilterItem(menu, toggle, text, value, type) {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = "#";
        a.textContent = text;
        li.appendChild(a);
        
        li.addEventListener('click', (e) => {
            e.preventDefault();
            // 状態更新
            currentFilters[type] = value;
            toggle.querySelector('.current-value').textContent = text;
            
            // 科目リストを絞り込み直す
            updateSubjectList();
            closeAllDropdowns();
        });
        menu.appendChild(li);
    }

    function setupDropdownToggle(toggle, menu) {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = menu.classList.contains('is-open');
            closeAllDropdowns();
            if (!isOpen) {
                menu.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('is-open'));
        document.querySelectorAll('.dropdown-toggle').forEach(t => t.setAttribute('aria-expanded', 'false'));
    }
    document.addEventListener('click', closeAllDropdowns);


    // ============================================================
    // 4. サーバー通信処理 (Fetch)
    // ============================================================

    /**
     * サーバーからカレンダーデータを取得
     */
    async function fetchLessonData(year, month) {
        if (!currentSubject) return;

        // 対象となるコースIDをすべて配列化
        const courseIds = currentSubject.courses.map(c => c.id);

        try {
            // 配列を送信するためにPOSTまたは、GETの場合はパラメータを工夫する
            // ここではGETパラメータで course_ids[]=1&course_ids[]=2 の形式にする
            const params = new URLSearchParams();
            params.append('action', 'fetch_calendar');
            params.append('year', year);
            params.append('month', month);
            params.append('subject_id', currentSubject.subject_id);
            
            // 配列を追加
            courseIds.forEach(id => params.append('course_ids[]', id));

            const response = await fetch(`${API_BASE_URL}?${params.toString()}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            lessonData = data || {}; 

            renderCalendar(year, month);
            refreshSidebar();

        } catch (error) {
            console.error('Fetch Error:', error);
            lessonData = {};
            renderCalendar(year, month);
            refreshSidebar();
        }
    }

    /**
     * データの保存 (POST)
     */
    async function saveLessonDataToServer(status, statusText) {
        if (!selectedDateKey || !currentSubject) return;

        const lessonVal = document.querySelector('.lesson-details-textarea').value;
        const belongingsVal = document.getElementById('detailsTextarea').value;
        // const [year, month, day] = selectedDateKey.split('-').map(Number); // 不要なら削除

        const courseIds = currentSubject.courses.map(c => c.id);
        const slotData = lessonData[selectedDateKey]?.slot || "1限"; 

        // JSONで送信する
        const postData = {
            action: 'save',
            date: selectedDateKey,
            subject_id: currentSubject.subject_id,
            course_ids: courseIds, // 配列で送る
            slot: slotData,
            content: lessonVal,
            belongings: belongingsVal,
            status: status
        };

        try {
            const response = await fetch(API_BASE_URL, {
                method: 'POST',
                body: JSON.stringify(postData),
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            if (result.success) {
                updateAllViews(selectedDateKey, slotData, status, statusText);
                lessonModal.style.display = 'none';
                alert("一括保存しました"); // 文言変更
            } else {
                alert("保存に失敗しました: " + (result.message || "不明なエラー"));
            }
        } catch (error) {
            console.error(error);
            alert("通信エラーが発生しました");
        }
    }

    /**
     * データの削除 (POST)
     */
    async function deleteLessonDataOnServer() {
        if (!selectedDateKey || !currentSubject) return;
        
        const slotData = lessonData[selectedDateKey]?.slot || "1限";
        const courseIds = currentSubject.courses.map(c => c.id);

        const postData = {
            action: 'delete',
            date: selectedDateKey,
            slot: slotData,
            subject_id: currentSubject.subject_id,
            course_ids: courseIds // 配列で送る
        };

        try {
            const response = await fetch(API_BASE_URL, {
                method: 'POST',
                body: JSON.stringify(postData),
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();

            if (result.success) {
                updateAllViews(selectedDateKey, null, null, null, true);
                lessonModal.style.display = 'none';
                
                document.querySelector('.lesson-details-textarea').value = '';
                document.getElementById('detailsTextarea').value = '';
                itemInput.value = '';
                alert("一括削除しました");
            } else {
                alert("削除できませんでした");
            }
        } catch (e) {
            console.error(e);
            alert("通信エラー");
        }
    }


    // ============================================================
    // 5. カレンダー描画 & サイドバー更新
    // ============================================================

    function refreshSidebar() {
        // 今日の日付 (時分秒リセット)
        const today = new Date();
        today.setHours(0,0,0,0);

        // 日付キーをソート
        const sortedKeys = Object.keys(lessonData).sort();
        
        // サイドバーの「次回の授業」以降のコンテナを取得
        // HTML構造: <li class="is-group-label">次回の授業</li> の次にある div
        // ※より確実にするため、HTMLに id="sidebarNextLesson" 等を付与することを推奨しますが、
        // 現状のHTML構造に合わせてquerySelectorで取得します。
        
        const sidebarWrappers = document.querySelectorAll('.sidebar .lesson-status-wrapper');
        
        // 全てのサイドバー項目を一旦非表示にする
        sidebarWrappers.forEach(el => el.style.display = 'none');

        // 未来の日付のデータを抽出
        let displayCount = 0;
        
        for (const key of sortedKeys) {
            const targetDate = new Date(key);
            // 今日以降（当日含む）を表示対象とする
            if (targetDate >= today) {
                const dayData = lessonData[key];
                if (!dayData || !dayData.slots) continue;

                // その日のスロットすべてを表示
                dayData.slots.forEach(slot => {
                    if (displayCount < sidebarWrappers.length) {
                        const wrapper = sidebarWrappers[displayCount];
                        const dateDisplay = wrapper.querySelector('.lesson-date-item');
                        const statusBtn = wrapper.querySelector('.status-button');

                        // 日付フォーマット
                        const m = targetDate.getMonth() + 1;
                        const d = targetDate.getDate();
                        const w = ['日','月','火','水','木','金','土'][targetDate.getDay()];

                        if (dateDisplay) {
                            dateDisplay.textContent = `${m}月${d}日(${w}) ${slot.slot}`;
                        }
                        if (statusBtn) {
                            statusBtn.className = `status-button ${slot.status}`;
                            statusBtn.textContent = slot.statusText;
                            
                            // サイドバーのボタンクリックでもモーダルを開く
                            statusBtn.onclick = (e) => {
                                e.stopPropagation();
                                openModalWithSlot(key, slot);
                            };
                            // ラッパー自体のクリックも同様
                            wrapper.onclick = () => openModalWithSlot(key, slot);
                        }

                        wrapper.style.display = 'flex';
                        displayCount++;
                    }
                });
            }
            if (displayCount >= sidebarWrappers.length) break;
        }
    }

    function updateAllViews(dateKey, slot, status, text, isDelete = false) {
        if (isDelete) {
            if (lessonData[dateKey]) {
                lessonData[dateKey].status = "not-created";
                lessonData[dateKey].statusText = "未作成";
                lessonData[dateKey].content = "";
                lessonData[dateKey].belongings = "";
            }
        } else {
            if (!lessonData[dateKey]) lessonData[dateKey] = {};
            lessonData[dateKey].slot = slot;
            lessonData[dateKey].status = status;
            lessonData[dateKey].statusText = text;
        }
        refreshSidebar();
        renderCalendar(currentYear, currentMonth);
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

    function createDateCell(dayNum, isOutOfMonth, year, month) {
        const cell = document.createElement('div');
        cell.className = 'date-cell';
        if (isOutOfMonth) {
            cell.classList.add('is-out-of-month');
            cell.style.opacity = '0.5';
        }

        // 日付キー生成 (YYYY-MM-DD) ※PHP側と合わせる (ゼロ埋めあり)
        const mStr = String(month).padStart(2, '0');
        const dStr = String(dayNum).padStart(2, '0');
        const dateKey = `${year}-${mStr}-${dStr}`;

        // データ取得
        const dayData = lessonData[dateKey]; // { circle_type: 'red'|'blue', slots: [...] }

        // 日付数字の表示（丸の色クラスを付与）
        let dateNumClass = "date-num";
        if (!isOutOfMonth && dayData) {
            cell.classList.add('has-data');
            // 青丸または赤丸の判定
            if (dayData.circle_type === 'red') {
                dateNumClass += " circle-red"; // CSSで赤背景を定義してください
            } else {
                dateNumClass += " circle-blue"; // CSSで青背景(デフォルト)を定義
            }
        }
        
        cell.innerHTML = `<span class="${dateNumClass}">${dayNum}</span>`;

        // ラベル（スロット）の表示
        if (!isOutOfMonth && dayData && dayData.slots) {
            const slotsContainer = document.createElement('div');
            slotsContainer.className = 'slots-container';

            dayData.slots.forEach(slot => {
                // ラベルボタンの生成
                const btn = document.createElement('button');
                // class: status-button + (not-created / creating / in-progress)
                btn.className = `status-button ${slot.status}`;
                btn.textContent = `${slot.slot} ${slot.statusText}`;
                
                // 【重要】ラベルをクリックした時にその授業のモーダルを開く
                btn.addEventListener('click', (e) => {
                    e.stopPropagation(); // セルのクリックイベントを止める
                    openModalWithSlot(dateKey, slot);
                });

                slotsContainer.appendChild(btn);
            });
            cell.appendChild(slotsContainer);
        }

        // セル自体のクリック（空いている部分をクリックした場合など）誤操作防止のため何もしない
        return cell;
    }

    /**
     * 特定の授業スロットでモーダルを開く
     * @param {string} dateKey YYYY-MM-DD
     * @param {object} slotData slotオブジェクト
     */
    function openModalWithSlot(dateKey, slotData) {
        selectedDateKey = dateKey;
        // ※保存時にどのスロットに対する保存かを知るため、グローバル変数などで
        // 現在編集中の period や course_id を保持する必要があります
        currentEditingSlot = slotData; // グローバル変数を追加してください

        // 入力欄セット
        const lessonDetailsText = document.querySelector('.lesson-details-textarea');
        const belongingsText = document.getElementById('detailsTextarea');
        
        lessonDetailsText.value = slotData.content || "";
        belongingsText.value = slotData.belongings || "";
        
        // タイトル更新
        const [y, m, d] = dateKey.split('-').map(Number);
        const dateObj = new Date(y, m - 1, d);
        const w = ['日','月','火','水','木','金','土'][dateObj.getDay()];
        document.querySelector('.modal-date').textContent = `${m}月${d}日(${w}) ${slotData.slot}`;

        lessonModal.style.display = 'flex';
    }

    function openModalWithDate(dateKey) {
        selectedDateKey = dateKey;
        const [year, month, dayNum] = dateKey.split('-').map(Number);
        const data = lessonData[dateKey] || {};

        // 入力欄セット
        const lessonDetailsText = document.querySelector('.lesson-details-textarea');
        const belongingsText = document.getElementById('detailsTextarea');
        
        lessonDetailsText.value = data.content || "";
        belongingsText.value = data.belongings || "";
        itemInput.value = ''; // テンプレート入力はクリア

        // 日付タイトル更新
        const dateObj = new Date(year, month - 1, dayNum);
        const dayOfWeek = dateObj.toLocaleDateString('ja-JP', { weekday: 'short' });
        const modalTitle = document.querySelector('.modal-date');
        if (modalTitle) modalTitle.textContent = `${month}月${dayNum}日(${dayOfWeek})`;

        lessonModal.style.display = 'flex';
    }


    // ============================================================
    // 6. 持ち物管理・モーダル操作（既存UIロジック維持）
    // ============================================================

    // 完了・保存ボタン
    completeButton.addEventListener('click', () => {
        if(!validateInput()) return;
        saveLessonDataToServer("in-progress", "作成済み"); // CSSクラス名と表示名
    });

    tempSaveButton.addEventListener('click', () => {
        saveLessonDataToServer("creating", "作成中");
    });

    deleteButton.addEventListener('click', () => {
        const lessonVal = document.querySelector('.lesson-details-textarea').value.trim();
        const detailsVal = document.getElementById('detailsTextarea').value.trim();
        const hasContent = lessonVal !== "" || detailsVal !== "";
        
        // 何も入力がない場合は確認なしで閉じる、ある場合は確認
        if (!hasContent || window.confirm("この日の授業詳細を削除してもよろしいですか？")) {
            deleteLessonDataOnServer();
        }
    });

    function validateInput() {
        const lessonVal = document.querySelector('.lesson-details-textarea').value.trim();
        const belongingsVal = document.getElementById('detailsTextarea').value.trim();
        
        document.querySelectorAll('.required-alert').forEach(a => a.remove());

        if (lessonVal === "" || belongingsVal === "") {
            const msg = document.createElement('span');
            msg.className = 'required-alert';
            msg.textContent = ' ※必須入力です';
            msg.style.color = '#ff0000';
            msg.style.fontSize = '12px';
            msg.style.marginLeft = '10px';

            if (lessonVal === "") document.querySelector('.form-title').appendChild(msg.cloneNode(true));
            if (belongingsVal === "") {
                const titles = document.querySelectorAll('.form-title');
                if (titles[1]) titles[1].appendChild(msg.cloneNode(true));
            }
            return false;
        }
        return true;
    }

    // 持ち物タグ UI
    deleteIcon.addEventListener('click', function() {
        isDeleteMode = !isDeleteMode;
        this.classList.toggle('is-active', isDeleteMode);
        addBtn.textContent = isDeleteMode ? '削除' : '追加';
        addBtn.classList.toggle('is-delete-mode', isDeleteMode);
        
        document.querySelectorAll('.item-tag-container').forEach(tag => tag.classList.remove('is-selected'));
        selectedItemsForDelete = [];
        itemInput.value = '';
    });

    itemTagsContainer.addEventListener('click', function(e) {
        const tagContainer = e.target.closest('.item-tag-container');
        if (!tagContainer) return;
        const itemName = tagContainer.querySelector('.item-tag').textContent;

        if (isDeleteMode) {
            tagContainer.classList.toggle('is-selected');
            if (tagContainer.classList.contains('is-selected')) {
                selectedItemsForDelete.push(itemName);
            } else {
                const idx = selectedItemsForDelete.indexOf(itemName);
                if (idx > -1) selectedItemsForDelete.splice(idx, 1);
            }
            itemInput.value = selectedItemsForDelete.join('、');
        } else {
            // テキストエリアに追加/削除
            const detailsTextarea = document.getElementById('detailsTextarea');
            let txt = detailsTextarea.value.trim();
            let items = txt === "" ? [] : txt.split('、');
            const idx = items.indexOf(itemName);
            if (idx === -1) items.push(itemName);
            else items.splice(idx, 1);
            detailsTextarea.value = items.join('、');
        }
    });

    addBtn.addEventListener('click', function() {
        if (isDeleteMode) {
            document.querySelectorAll('.item-tag-container.is-selected').forEach(t => t.remove());
            itemInput.value = '';
            selectedItemsForDelete = [];
        } else {
            const val = itemInput.value.trim();
            if (val !== "") {
                const items = val.split('、');
                items.forEach(item => {
                    if (item.trim() !== "") {
                        const div = document.createElement('div');
                        div.className = 'item-tag-container';
                        div.innerHTML = `<span class="item-tag">${item.trim()}</span>`;
                        itemTagsContainer.appendChild(div);
                    }
                });
                itemInput.value = '';
            }
        }
    });

    // カレンダー月移動
    if (leftArrowButton) leftArrowButton.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 1) { currentMonth = 12; currentYear--; }
        fetchLessonData(currentYear, currentMonth);
    });
    if (rightArrowButton) rightArrowButton.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 12) { currentMonth = 1; currentYear++; }
        fetchLessonData(currentYear, currentMonth);
    });

    // 文字数カウント
    const lessonTextArea = document.querySelector('.lesson-details-textarea');
    const charCountDisplay = document.querySelector('.char-count');
    if (lessonTextArea && charCountDisplay) {
        lessonTextArea.addEventListener('input', () => {
            charCountDisplay.textContent = `${lessonTextArea.value.length}/200文字`;
        });
    }
});

// モーダル外クリック
const lessonModal = document.getElementById('lessonModal');
lessonModal.addEventListener('click', (e) => {
    if (e.target === lessonModal) lessonModal.style.display = 'none';
});