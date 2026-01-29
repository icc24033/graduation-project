// js/class_detail_edit.js

document.addEventListener('DOMContentLoaded', function() {
    // ============================================================
    // 1. 変数・要素の定義
    // ============================================================
    
    // API設定　現在地を表すパスと、相対パスによる文字列結合を利用
    const API_BASE_URL = '../../api/class_detail_edit/class_detail_api.php'; 

    // ドロップダウン用要素（IDを修正後のHTMLに合わせて取得）
    const gradeToggle = document.getElementById('gradeFilterToggle');
    const gradeMenu = document.getElementById('gradeFilterMenu');
    // const courseToggle = document.getElementById('courseFilterToggle');
    // const courseMenu = document.getElementById('courseFilterMenu');
    const subjectToggle = document.getElementById('subjectSelectorToggle');
    const subjectMenu = document.getElementById('subjectSelectorMenu');

    // カレンダー・モーダル用要素
    const monthElement = document.querySelector('.month');
    const leftArrowButton = document.querySelector('.left-arrow')?.closest('button');
    const rightArrowButton = document.querySelector('.right-arrow')?.closest('button');
    const calendarGrid = document.getElementById('calendarGrid');
    if (!calendarGrid) {
        console.error("【重要エラー】HTML内に id='calendarGrid' の要素が見つかりません。HTMLを確認してください。");
    }
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
    let selectedSlotKey = null; // "YYYY-MM-DD_1限" など、現在編集中のスロット識別子
    let sidebarData = {}; // サイドバー表示用データ
    let currentEditingSlot = null; // 現在編集中のスロットオブジェクト
    
    // ドロップダウン用状態
    let currentFilters = { grade: ""}; // フィルタ状態
    let currentSubject = null; // 現在選択中の科目オブジェクト {subject_id, course_id, ...}
    
    // 持ち物削除モード
    let isDeleteMode = false;
    let selectedItemsForDelete = []; 

    // PHPからのデータチェック
    if (typeof assignedClassesData === 'undefined') {
        console.error("assignedClassesData is not defined. PHP側を確認してください。");
        window.assignedClassesData = [];
    } else if (!Array.isArray(assignedClassesData)) {
        // PHPの連想配列がJSオブジェクトとして渡ってきた場合の対応
        if (assignedClassesData && typeof assignedClassesData === 'object') {
            console.warn("assignedClassesDataが配列ではありません。オブジェクトを配列に変換します。");
            window.assignedClassesData = Object.values(assignedClassesData);
        } else {
            window.assignedClassesData = [];
        }
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

        // コースリスト生成処理を削除
        // const courseMap = new Map();
        // assignedClassesData.forEach(d => courseMap.set(d.course_id, d.course_name));
        // renderCourseFilterList(courseMenu, courseToggle, courseMap);

        // 3-3. 科目リストの初期更新（全件表示 -> 先頭を選択）
        updateSubjectList();

        // 3-4. 開閉イベント設定
        setupDropdownToggle(gradeToggle, gradeMenu);

        // コースドロップダウンのイベント設定を削除
        // setupDropdownToggle(courseToggle, courseMenu);
        setupDropdownToggle(subjectToggle, subjectMenu);
    }

    /**
     * フィルタ条件に基づいて科目リストを再生成し、先頭を自動選択する
     */
    function updateSubjectList() {
        // 1. まずフィルタリング（学年・コースで絞り込み）
        const filteredRaw = assignedClassesData.filter(item => {
            const matchGrade = (currentFilters.grade === "") || (String(item.grade) === String(currentFilters.grade));
            
            // コースでの絞り込みを削除（常にtrue扱いや削除で対応）
            // const matchCourse = (currentFilters.course === "") || (String(item.course_id) === String(currentFilters.course));
            
            // 学年のみで判定
            return matchGrade;
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
            // ★ここは変更なし（コース名の表示は維持）
            const courseNames = group.courses.map(c => c.name).join(', ');
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
                selectSubjectGroup(group); 
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
    // ★修正：不要になったコースメニュー操作を削除（汎用処理なので変更不要の場合もありますが、念のため確認）
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

            // 【修正】PHPが空の結果を [] (配列) として返す場合の対策
            // JS側は連想配列(オブジェクト)を期待しているため、空配列なら空オブジェクトに変換
            if (Array.isArray(data) && data.length === 0) {
                data = {};
            }

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
    
    const courseIds = currentSubject.courses.map(c => c.id);
    const slotData = selectedSlotKey; // "1限" などの文字列

    const postData = {
        action: 'save',
        date: selectedDateKey,
        subject_id: currentSubject.subject_id,
        course_ids: courseIds,
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
            // 修正: 引数に content(lessonVal) と belongings(belongingsVal) を追加
            updateAllViews(selectedDateKey, slotData, status, statusText, lessonVal, belongingsVal, false);
            
            lessonModal.style.display = 'none';
            alert("一括保存しました");
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
    
    const slotData = selectedSlotKey; // "1限" などの文字列
    const courseIds = currentSubject.courses.map(c => c.id);

    const postData = {
        action: 'delete',
        date: selectedDateKey,
        slot: slotData,
        subject_id: currentSubject.subject_id,
        course_ids: courseIds
    };

    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            body: JSON.stringify(postData),
            headers: { 'Content-Type': 'application/json' }
        });
        const result = await response.json();

        if (result.success) {
            // 修正: 引数を合わせる (content, belongings は空文字、isDelete = true)
            updateAllViews(selectedDateKey, slotData, null, null, "", "", true);
            
            lessonModal.style.display = 'none';
            
            document.querySelector('.lesson-details-textarea').value = '';
            document.getElementById('detailsTextarea').value = '';
            if (itemInput) itemInput.value = '';
            
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
        
        const sidebarWrappers = document.querySelectorAll('.sidebar .lesson-status-wrapper');
        
        // 全てのサイドバー項目を一旦非表示にする
        sidebarWrappers.forEach(el => el.style.display = 'none');

        // 未来の日付のデータを抽出して表示
        let displayCount = 0;
        
        for (const key of sortedKeys) {
            const targetDate = new Date(key);
            // 今日以降を表示対象とする
            if (targetDate >= today) {
                const dayData = lessonData[key];
                if (!dayData || !dayData.slots) continue;

                // その日のスロットすべてを表示
                for (const slot of dayData.slots) {
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
                            
                            // サイドバーのボタンクリックイベント
                            // (古いイベントリスナーが残らないよう onclick プロパティを使用)
                            const clickHandler = (e) => {
                                if(e) e.stopPropagation();
                                openModalWithSlot(key, slot);
                            };
                            statusBtn.onclick = clickHandler;
                            wrapper.onclick = clickHandler;
                        }

                        wrapper.style.display = 'flex';
                        displayCount++;
                    }
                }
            }
            if (displayCount >= sidebarWrappers.length) break;
        }
    }

    /**
 * ローカルデータの更新と再描画
 * @param {string} dateKey YYYY-MM-DD
 * @param {string} targetSlotKey "1限" など
 * @param {string} status "in-progress" 等
 * @param {string} text "作成済" 等
 * @param {string} content 授業詳細
 * @param {string} belongings 持ち物
 * @param {boolean} isDelete 削除フラグ
 */
function updateAllViews(dateKey, targetSlotKey, status, text, content, belongings, isDelete = false) {
    // データが存在しない場合のガード
    if (!lessonData[dateKey]) {
        lessonData[dateKey] = { slots: [], circle_type: 'blue' };
    }
    
    const dayData = lessonData[dateKey];

    // 既存のスロット配列から、対象のスロット(targetSlotKey)を探す
    // もし配列になければ（新規作成など）、新しいオブジェクトとして追加する必要があるが、
    // 通常はカレンダー表示時点でスロット枠はあるはずなので、既存更新を優先する
    let targetSlotObj = dayData.slots.find(s => s.slot === targetSlotKey);

    if (isDelete) {
        // 削除の場合: ステータスを未作成に戻し、内容をクリア
        if (targetSlotObj) {
            targetSlotObj.status = "not-created";
            targetSlotObj.statusText = "未作成";
            targetSlotObj.content = "";
            targetSlotObj.belongings = "";
            targetSlotObj.is_change = false; // 必要であれば
        }
        // 赤丸・青丸の判定ロジックが必要ならここで再計算する（今回は簡易的にblueへ戻す例）
        // dayData.circle_type = 'blue'; 
    } else {
        // 保存の場合
        if (targetSlotObj) {
            // 既存スロットの更新
            targetSlotObj.status = status;
            targetSlotObj.statusText = text;
            targetSlotObj.content = content;
            targetSlotObj.belongings = belongings;
        } else {
            // 万が一スロットが見つからない場合（レアケース）は新規追加
            // periodの数値化などは簡易処理
            dayData.slots.push({
                slot: targetSlotKey,
                period: parseInt(targetSlotKey) || 0, 
                status: status,
                statusText: text,
                content: content,
                belongings: belongings
            });
            // 時限順にソート
            dayData.slots.sort((a, b) => a.period - b.period);
        }
    }

    // サイドバーとカレンダーの再描画
    refreshSidebar();
    renderCalendar(currentYear, currentMonth);
}

    function renderCalendar(year, month) {
        if (!calendarGrid) return;

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

        // 日付キー生成 (YYYY-MM-DD) ※ゼロ埋めあり
        const mStr = String(month).padStart(2, '0');
        const dStr = String(dayNum).padStart(2, '0');
        const dateKey = `${year}-${mStr}-${dStr}`;

        // データ取得
        const dayData = lessonData[dateKey]; // { circle_type: 'red'|'blue', slots: [...] }

        // --- 丸の表示判定 ---
        let dateNumClass = "date-num";
        if (!isOutOfMonth && dayData) {
            cell.classList.add('has-data');
            
            if (dayData.circle_type === 'red') {
                dateNumClass += " circle-red"; // 赤丸クラス
            } else {
                dateNumClass += " circle-blue"; // 青丸クラス
            }
        }
        
        cell.innerHTML = `<span class="${dateNumClass}">${dayNum}</span>`;

        // --- 授業ラベル（ボタン）の生成 ---
        if (!isOutOfMonth && dayData && dayData.slots) {
            // スロットを格納するコンテナ
            const slotsContainer = document.createElement('div');
            slotsContainer.className = 'slots-container'; // CSSで縦並びにする

            dayData.slots.forEach(slot => {
                const btn = document.createElement('button');
                // クラス: status-button + 状態(creatingなど)
                btn.className = `status-button ${slot.status}`;
                btn.textContent = `${slot.slot} ${slot.statusText}`;
                
                // ボタンクリックでモーダルを開く
                btn.addEventListener('click', (e) => {
                    e.stopPropagation(); // セルのクリックを無効化
                    openModalWithSlot(dateKey, slot);
                });

                slotsContainer.appendChild(btn);
            });
            cell.appendChild(slotsContainer);
        }
        
        return cell;
    }
    /**
     * 特定の授業スロットでモーダルを開く
     * @param {string} dateKey YYYY-MM-DD
     * @param {object} slotData slotオブジェクト
     */
    function openModalWithSlot(dateKey, slotData) {
        selectedDateKey = dateKey;
        selectedSlotKey = slotData.slot;
        
        // ※重要: 保存処理のために「現在どのスロットを編集しているか」を記録する
        // グローバル変数 currentEditingSlot を定義するか、lessonDataに一時保存する等の工夫が必要ですが
        // とりあえずフォームに値をセットします
        
        // DOM要素
        const lessonDetailsText = document.querySelector('.lesson-details-textarea');
        const belongingsText = document.getElementById('detailsTextarea');
        
        // 値をセット
        lessonDetailsText.value = slotData.content || "";
        belongingsText.value = slotData.belongings || "";
        
        // タイトル更新
        const [y, m, d] = dateKey.split('-').map(Number);
        const dateObj = new Date(y, m - 1, d);
        const w = ['日','月','火','水','木','金','土'][dateObj.getDay()];
        
        const modalTitle = document.querySelector('.modal-date');
        if(modalTitle) {
            modalTitle.textContent = `${m}月${d}日(${w}) ${slotData.slot}`;
        }

        const modal = document.getElementById('lessonModal');
        if(modal) modal.style.display = 'flex';
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