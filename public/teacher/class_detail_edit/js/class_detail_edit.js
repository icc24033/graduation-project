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

    let selectedCourseId = null;  // モーダルで編集中などのコースID
    let selectedSubjectId = null; // モーダルで編集中などの科目ID
    
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
        currentSubject = group;
        subjectToggle.querySelector('.current-value').textContent = group.subject_name;
        
        const displayTitle = document.getElementById('displayCourseName');
        if (displayTitle) displayTitle.textContent = group.subject_name;

        // displayCourse要素の内容を更新（コース名を併記）
        const displayCourse = document.getElementById('displayCourse');
        if (displayCourse) {
            const courseNames = group.courses.map(c => c.name).join(', ');
            displayCourse.textContent = courseNames;
        }

        // カレンダー用（表示中の年月）
        fetchLessonData(currentYear, currentMonth);
        
        // サイドバー用（現実の今月・来月）
        fetchSidebarData();
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
     * データの整形・検証用ヘルパー関数
     * APIから受け取ったデータ（または結合したデータ）を検証して返します。
     */
    function processAndMergeLessonData(data) {
        // データがnull/undefinedの場合
        if (!data) return {};
        
        // PHPから空の配列 [] が返ってきた場合の対応
        if (Array.isArray(data)) {
            return {};
        }
        
        // オブジェクトとして返ってきた場合はそのまま利用
        if (typeof data === 'object') {
            return data;
        }
        
        return {};
    }

    /**
     * サーバーからカレンダーデータを取得
     */
    async function fetchLessonData(year, month) {
        if (!currentSubject) return;

        const courseIds = currentSubject.courses.map(c => c.id);
        const params = new URLSearchParams();
        params.append('action', 'fetch_calendar');
        params.append('year', year);
        params.append('month', month);
        params.append('subject_id', currentSubject.subject_id);
        courseIds.forEach(id => params.append('course_ids[]', id));

        try {
            const response = await fetch(`${API_BASE_URL}?${params.toString()}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            let data = await response.json();
            
            if (Array.isArray(data) && data.length === 0) {
                data = {};
            }

            lessonData = processAndMergeLessonData(data); // マージ処理

            renderCalendar(year, month);
            // refreshSidebar(); // ★削除：カレンダー操作時にサイドバーは更新しない

        } catch (error) {
            console.error('Fetch Error:', error);
            lessonData = {};
            renderCalendar(year, month);
        }
    }

    /**
     * サイドバー用のデータを取得
     * 「今月」と「来月」の2ヶ月分を取得して結合することで月またぎに対応します
     */
    async function fetchSidebarData() {
        if (!currentSubject) return;

        const now = new Date();
        let loopY = now.getFullYear();
        let loopM = now.getMonth() + 1;

         const courseIds = currentSubject.courses.map(c => c.id);

        // API呼び出し用ヘルパー
        const fetchMonth = async (y, m) => {
            const params = new URLSearchParams();
            params.append('action', 'fetch_calendar');
            params.append('year', y);
            params.append('month', m);
            params.append('subject_id', currentSubject.subject_id);
            courseIds.forEach(id => params.append('course_ids[]', id));
    
            try {
                const res = await fetch(`${API_BASE_URL}?${params.toString()}`);
                if(!res.ok) return {};
                const d = await res.json();
                return (Array.isArray(d) && d.length === 0) ? {} : d;
            } catch (e) {
                return {};
            }
        };

        // 取得する月数の設定（ここでは向こう12ヶ月分としています）
        const monthsToFetch = 12; 
        const promises = [];

        for (let i = 0; i < monthsToFetch; i++) {
            promises.push(fetchMonth(loopY, loopM));
        
            // 次の月へ
            loopM++;
            if (loopM > 12) {
                loopM = 1;
                loopY++;
            }
        }

        try {
            // 並列取得実行
            const results = await Promise.all(promises);

            // 全ての結果を1つのオブジェクトに結合
            let rawMerged = {};
            results.forEach(res => {
                rawMerged = { ...rawMerged, ...res };
            });

            sidebarData = processAndMergeLessonData(rawMerged);
            refreshSidebar(); 

        } catch (error) {
            console.error('Sidebar Fetch Error:', error);
            sidebarData = {};
            refreshSidebar();
        }
    }

    /**
     * サーバーへデータを保存
     * @param {string} statusCode "creating" or "in-progress"
     * @param {string} statusText "作成中" or "作成済み"
     */
    async function saveLessonDataToServer(statusCode, statusText) {
        const content = document.querySelector('.lesson-details-textarea').value;
        const belongings = document.getElementById('detailsTextarea').value;

        // バリデーション
        if (!selectedDateKey || !selectedSlotKey || !selectedCourseId) {
            console.error("Missing IDs:", {selectedDateKey, selectedSlotKey, selectedCourseId});
            alert("保存に必要な情報が不足しています。");
            return;
        }

        const subjId = selectedSubjectId || (currentSubject ? currentSubject.subject_id : null);

        const payload = {
            action: 'save',
            date: selectedDateKey,
            slot: selectedSlotKey,
            course_id: selectedCourseId,
            subject_id: subjId,
            content: content,
            belongings: belongings,
            status_code: statusCode
        };

        try {
            const res = await fetch(API_BASE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            const data = await res.json();
            
            if (data.success) {
                // 成功したら画面（カレンダーとサイドバー）を更新
                // updateAllViews は JS内でデータを更新して再描画する関数
                updateAllViews(
                    selectedDateKey, 
                    selectedSlotKey, 
                    statusCode === 'in-progress' ? 'in-progress' : 'creating', // CSSクラス名
                    statusText, 
                    content, 
                    belongings,
                    false // 削除フラグ
                );
                
                // モーダルを閉じる
                document.getElementById('lessonModal').style.display = 'none';
            } else {
                alert('保存に失敗しました: ' + (data.message || 'Unknown error'));
            }

        } catch (e) {
            console.error('Error:', e);
            alert('通信エラーが発生しました。');
        }
    }

    /**
     * サーバーからデータを削除
     */
    async function deleteLessonDataOnServer() {
        if (!selectedDateKey || !selectedSlotKey || !selectedCourseId) return;

        const payload = {
            action: 'delete',
            date: selectedDateKey,
            slot: selectedSlotKey,
            course_id: selectedCourseId,
            subject_id: selectedSubjectId || (currentSubject ? currentSubject.subject_id : null)
        };

        try {
            const res = await fetch(API_BASE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            const data = await res.json();
            
            if (data.success) {
                // 成功したら画面を更新（未作成状態に戻す）
                updateAllViews(
                    selectedDateKey,
                    selectedSlotKey,
                    'not-created', // CSSクラス
                    '未作成',      // テキスト
                    '',            // content
                    '',            // belongings
                    true           // 削除フラグ
                );
                
                // モーダルを閉じる
                document.getElementById('lessonModal').style.display = 'none';
            } else {
                alert('削除に失敗しました: ' + (data.message || ''));
            }
        } catch (e) {
            console.error('Error:', e);
            alert('通信エラーが発生しました。');
        }
    }

    // ============================================================
    // 5. カレンダー描画 & サイドバー更新
    // ============================================================

    function refreshSidebar() {
        const nextList = document.getElementById('nextLessonList');
        const futureList = document.getElementById('futureLessonList');
        
        if (!nextList || !futureList) return;

        nextList.innerHTML = '';
        futureList.innerHTML = '';

        // 今日の日付 (時分秒リセット)
        const today = new Date();
        today.setHours(0,0,0,0);

        // 日付キーを昇順ソート
        const sortedKeys = Object.keys(sidebarData).sort();

        // ★修正ポイント1: フラグ(true/false)ではなく、「次回の授業日」の日付文字列を保持する変数にする
        let nextLessonDateKey = null;

        // アイテム生成ヘルパー
        const createItem = (dateKey, slot) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'lesson-status-wrapper';
            wrapper.style.display = 'flex';
            wrapper.style.alignItems = 'center';
            wrapper.style.justifyContent = 'space-between';
            wrapper.style.marginBottom = '8px';
            wrapper.style.cursor = 'pointer';
            
            const targetDate = new Date(dateKey);
            const m = targetDate.getMonth() + 1;
            const d = targetDate.getDate();
            const w = ['日','月','火','水','木','金','土'][targetDate.getDay()];

            // 左側：日付と時限
            const dateDisplay = document.createElement('div');
            dateDisplay.className = 'lesson-date-item';
            dateDisplay.textContent = `${m}月${d}日(${w}) ${slot.slot}`;
            wrapper.appendChild(dateDisplay);

            // 右側：ステータスボタン
            const statusBtn = document.createElement('button');
            statusBtn.className = `status-button ${slot.status}`;
            statusBtn.textContent = slot.statusText;
            statusBtn.style.minWidth = '80px'; 
            
            wrapper.onclick = (e) => {
                e.stopPropagation();
                openModalWithSlot(dateKey, slot);
            };
            statusBtn.onclick = (e) => {
                e.stopPropagation();
                openModalWithSlot(dateKey, slot);
            };

            wrapper.appendChild(statusBtn);
            return wrapper;
        };

        // ループ処理
        for (const key of sortedKeys) {
            const targetDate = new Date(key);
            // 過去の日付は無視
            if (targetDate < today) continue;

            const dayData = sidebarData[key];
            if (!dayData || !dayData.slots || dayData.slots.length === 0) continue;

            // ★修正ポイント2: まだ「次回の授業日」が決まっていなければ、この日（key）を「次回の授業日」として登録
            if (nextLessonDateKey === null) {
                nextLessonDateKey = key;
            }

            // 各日のスロットを処理
            dayData.slots.forEach(slot => {
                const item = createItem(key, slot);

                // ★修正ポイント3: 現在ループ中の日付(key)が「次回の授業日(nextLessonDateKey)」と同じなら、すべて「次回の授業」に入れる
                if (key === nextLessonDateKey) {
                    nextList.appendChild(item);
                } else {
                    // 日付が違う（＝もっと先の日付）なら「次回以降」へ
                    futureList.appendChild(item);
                }
            });
        }

        // データがない場合の表示
        if (nextList.children.length === 0) {
            nextList.innerHTML = '<div class="no-data-msg">予定なし</div>';
        }
        if (futureList.children.length === 0) {
            futureList.innerHTML = '<div class="no-data-msg">予定なし</div>';
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
		
		// lessonData(カレンダー用) と sidebarData(サイドバー用) の両方を更新する内部関数
		const updateDataSet = (dataSet) => {
			if (!dataSet) return;

			// まだその日のデータ枠がない場合は作成
			if (!dataSet[dateKey]) {
				dataSet[dateKey] = { slots: [], circle_type: 'blue' };
			}
			const dayData = dataSet[dateKey];
			
			// 対象のスロットを探す
			let targetSlotObj = dayData.slots.find(s => s.slot === targetSlotKey);

			if (isDelete) {
				// 削除処理（物理削除せず「未作成」状態に戻す仕様に合わせています）
				if (targetSlotObj) {
					targetSlotObj.status = "not-created";
					targetSlotObj.statusText = "未作成";
					targetSlotObj.content = "";
					targetSlotObj.belongings = "";
				}
			} else {
				// 保存/更新処理
				if (targetSlotObj) {
					// 既存データの更新
					targetSlotObj.status = status;
					targetSlotObj.statusText = text;
					targetSlotObj.content = content;
					targetSlotObj.belongings = belongings;
				} else {
					// 新規スロット追加（通常ここに来る前に枠はあるはずですが、念のため）
					dayData.slots.push({
						slot: targetSlotKey,
						period: parseInt(targetSlotKey) || 0, // "1限" -> 1
						status: status,
						statusText: text,
						content: content,
						belongings: belongings
					});
					// 時限順にソート
					dayData.slots.sort((a, b) => a.period - b.period);
				}
			}
		};

		// 両方のデータセットを更新
		updateDataSet(lessonData);
		updateDataSet(sidebarData);

		// 両方のビューを再描画
		refreshSidebar();
		renderCalendar(currentYear, currentMonth);
	}

    function renderCalendar(year, month) {
		if (!calendarGrid) return;

		// グリッドをクリア（ヘッダー行以外の動的生成セルのみ削除）
		const oldCells = calendarGrid.querySelectorAll('.date-cell');
		oldCells.forEach(cell => cell.remove());

		// カレンダー計算
		const firstDay = new Date(year, month - 1, 1).getDay(); // 1日の曜日
		const daysInMonth = new Date(year, month, 0).getDate(); // その月の日数
		const prevMonthLastDay = new Date(year, month - 1, 0).getDate(); // 先月の末日

		// ★修正：35(5週)だと、土曜始まりの31日月などで溢れるため42(6週)に変更
		const totalCells = 42;

		for (let i = 0; i < totalCells; i++) {
			let dayNum, isOutOfMonth = false;
			let currentY = year;
			let currentM = month;

			if (i < firstDay) {
				// 先月
				dayNum = prevMonthLastDay - (firstDay - 1 - i);
				isOutOfMonth = true;
				currentM = month - 1;
				if(currentM < 1) { currentM = 12; currentY--; }
			} else if (i >= firstDay + daysInMonth) {
				// 来月
				dayNum = i - (firstDay + daysInMonth) + 1;
				isOutOfMonth = true;
				currentM = month + 1;
				if(currentM > 12) { currentM = 1; currentY++; }
			} else {
				// 今月
				dayNum = i - firstDay + 1;
			}
			
			const cell = createDateCell(dayNum, isOutOfMonth, currentY, currentM);
			
			// 曜日ごとの色分け用クラス
			if (i % 7 === 0) cell.classList.add('is-sunday');
			if (i % 7 === 6) cell.classList.add('is-saturday');
			
			calendarGrid.appendChild(cell);
		}
		
		// ヘッダータイトルの更新（西暦を表示）
		if(monthElement) {
			monthElement.textContent = `${year}年 ${month}月`;
		}
	}

    function createDateCell(dayNum, isOutOfMonth, year, month) {
		const cell = document.createElement('div');
		cell.className = 'date-cell';
		if (isOutOfMonth) {
			cell.classList.add('is-out-of-month');
			cell.style.opacity = '0.5';
		}

		// 日付キー生成 (YYYY-MM-DD)
		const mStr = String(month).padStart(2, '0');
		const dStr = String(dayNum).padStart(2, '0');
		const dateKey = `${year}-${mStr}-${dStr}`;

		// 該当日のデータ取得
		const dayData = lessonData[dateKey] || null;

		// --- 日付数字と丸の表示判定 ---
		const dateNumSpan = document.createElement('span');
		dateNumSpan.className = 'date-num';
		dateNumSpan.textContent = dayNum;

		let hasLesson = false;
		// 授業データが存在する場合のみ処理
		if (!isOutOfMonth && dayData && dayData.slots && dayData.slots.length > 0) {
			hasLesson = true;
			cell.classList.add('has-data');
			
			// 丸の色判定（変更がある日等は赤、通常は青）
			if (dayData.circle_type === 'red') {
				dateNumSpan.classList.add('circle-red');
			} else {
				dateNumSpan.classList.add('circle-blue');
			}
		}
		cell.appendChild(dateNumSpan);

		// --- 授業ラベル（ボタン）の生成 ---
		if (hasLesson) {
                const slotsContainer = document.createElement('div');
                slotsContainer.className = 'slots-container';

                dayData.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    
                    // ボタン自体には色のクラス(not-created等)を付けない
                    // 常に白背景のラッパーとして機能させるため 'status-button' のみにする
                    btn.className = 'status-button'; 
                    
                    // HTML構造を変更
                    // 左に時限、右にステータスバッジ(ここに色クラスを付与)
                    btn.innerHTML = `
                        <span class="slot-label">${slot.slot}</span>
                        <span class="status-badge ${slot.status}">${slot.statusText}</span>
                    `;
                    
                    // ボタンクリック時のみモーダルを開く
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation(); // 親セルへの伝播を止める
                        openModalWithSlot(dateKey, slot);
                    });

                    slotsContainer.appendChild(btn);
                });
                cell.appendChild(slotsContainer);
            }
		
		// セル自体のクリックは何もしない（ボタンのみ有効）
		// ただし、もし「新規作成」などをセルクリックで行いたい場合はここを変更します
		cell.onclick = () => { return false; };

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
        
        // DB保存用にIDを保持しておく
        selectedCourseId = slotData.course_id;
        selectedSubjectId = slotData.subject_id || (currentSubject ? currentSubject.subject_id : null); 

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

        // アイテムタグ（削除モード解除など）の初期化
        if(typeof isDeleteMode !== 'undefined') {
            isDeleteMode = false;
            if(deleteIcon) deleteIcon.classList.remove('is-active');
            if(addBtn) {
                addBtn.textContent = '追加';
                addBtn.classList.remove('is-delete-mode');
            }
            // 既存のタグ選択状態を解除
            document.querySelectorAll('.item-tag-container').forEach(tag => tag.classList.remove('is-selected'));
        }

        const modal = document.getElementById('lessonModal');
        if(modal) {
            modal.style.display = 'flex';
            
            // この科目のテンプレートを読み込む
            loadTemplates(selectedSubjectId);
            
        }
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
    // テンプレート操作用 ヘルパー関数
    // ============================================================

    /**
     * テンプレート一覧の読み込み
     */
    async function loadTemplates(subjectId) {
        if (!subjectId) return;

        try {
            const params = new URLSearchParams();
            params.append('action', 'fetch_templates');
            params.append('subject_id', subjectId);

            const res = await fetch(`${API_BASE_URL}?${params.toString()}`);
            if (res.ok) {
                const templates = await res.json();
                renderTemplates(templates);
            }
        } catch (e) {
            console.error("テンプレート取得エラー", e);
        }
    }

    /**
     * テンプレート一覧の描画
     * ※既存のHTML構造 (.item-tag-container) に合わせて生成します
     */
    function renderTemplates(templates) {
        if (!itemTagsContainer) return;

        itemTagsContainer.innerHTML = ''; // 一旦クリア

        if (!Array.isArray(templates)) return;

        templates.forEach(tpl => {
            // 外側のdiv
            const div = document.createElement('div');
            div.className = 'item-tag-container';
            
            // ★重要: 削除用にIDを埋め込んでおく
            div.dataset.templateId = tpl.template_id; 

            // 中身のspan
            const span = document.createElement('span');
            span.className = 'item-tag';
            span.textContent = tpl.item_name;

            // ★修正点: ここにあった div.addEventListener は削除します。
            // 親要素のイベントリスナーに任せることで、以前の挙動（トグル動作）を維持できます。

            div.appendChild(span);
            itemTagsContainer.appendChild(div);
        });
    }

    /**
     * テンプレート単体保存（内部利用）
     */
    async function saveTemplateToDb(subjectId, name) {
        try {
            const res = await fetch(API_BASE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_template',
                    subject_id: subjectId,
                    item_name: name
                })
            });
            return await res.json();
        } catch (e) {
            console.error("保存エラー", e);
            return { success: false };
        }
    }

    /**
     * テンプレート削除API
     */
    async function deleteTemplate(templateId) {
        try {
            const res = await fetch(API_BASE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete_template',
                    template_id: templateId
                })
            });
            return await res.json();
        } catch (e) {
            console.error("削除エラー", e);
            return { success: false };
        }
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

    const templateTitle = document.getElementById('template-title'); // タイトル要素

    // 持ち物タグ UI
    deleteIcon.addEventListener('click', function() {
        isDeleteMode = !isDeleteMode;
        this.classList.toggle('is-active', isDeleteMode);
        addBtn.textContent = isDeleteMode ? '削除' : '追加';
        addBtn.classList.toggle('is-delete-mode', isDeleteMode);

        if (isDeleteMode) {
            templateTitle.textContent = "よく使う持ち物テンプレート（削除する項目をクリック）";
            templateTitle.style.color = "#ff4444"; // 警告色（赤）に変更
        } else {
            templateTitle.textContent = "よく使う持ち物テンプレート";
            templateTitle.style.color = ""; // 元の色に戻す
        }
        
        document.querySelectorAll('.item-tag-container').forEach(tag => tag.classList.remove('is-selected'));
        selectedItemsForDelete = [];
        itemInput.value = '';
    });

    itemTagsContainer.addEventListener('click', function(e) {
        // クリックされたのがタグ(.item-tag-container)かチェック
        const tagContainer = e.target.closest('.item-tag-container');
        if (!tagContainer) return;

        const itemName = tagContainer.querySelector('.item-tag').textContent;

        if (isDeleteMode) {
            // ==========================================
            // 【削除モード】見た目の選択状態を切り替えるだけ
            // ==========================================
            tagContainer.classList.toggle('is-selected');
            
            // 内部変数配列の管理（必要であれば）
            const idx = selectedItemsForDelete.indexOf(itemName);
            if (tagContainer.classList.contains('is-selected')) {
                if (idx === -1) selectedItemsForDelete.push(itemName);
            } else {
                if (idx > -1) selectedItemsForDelete.splice(idx, 1);
            }

            itemInput.value = selectedItemsForDelete.join('、');

        } else {
            // ==========================================
            // 【通常モード】テキストエリアに追加/削除（トグル）
            // ==========================================
            const detailsTextarea = document.getElementById('detailsTextarea');
            let txt = detailsTextarea.value.trim();
            
            // 「、」区切りの仕様
            let items = txt === "" ? [] : txt.split('、');
            
            const idx = items.indexOf(itemName);
            if (idx === -1) {
                // なければ追加
                items.push(itemName);
            } else {
                // あれば削除（トグル動作）
                items.splice(idx, 1);
            }
            
            detailsTextarea.value = items.join('、');
        }
    });

    // ---------------------------------------------------------
    // テンプレート追加・削除ボタンの処理（DB連携版）
    // ---------------------------------------------------------
    addBtn.addEventListener('click', async function() {
        const subjId = selectedSubjectId || (currentSubject ? currentSubject.subject_id : null);
        if (!subjId) {
            alert("科目が選択されていません。");
            return;
        }

        if (isDeleteMode) {
            // ==========================================
            // 【削除モード】選択されたタグを一括削除
            // ==========================================
            // 画面上で選択されている要素を取得
            const selectedEls = document.querySelectorAll('.item-tag-container.is-selected');
            
            if (selectedEls.length === 0) return;

            // ここで一度だけ確認アラートを出す
            if (!confirm(`${selectedEls.length}件のテンプレートを削除しますか？`)) {
                return;
            }

            // ループして削除APIを実行
            for (const el of selectedEls) {
                const tmplId = el.dataset.templateId; // datasetからID取得
                if (tmplId) {
                    await deleteTemplate(tmplId);
                }
            }

            // 削除完了後の後処理
            selectedItemsForDelete = []; // 配列クリア
            itemInput.value = '';        // 入力欄クリア（もし表示されていたら）
            
            // 最新リストを再取得して描画更新
            await loadTemplates(subjId);

        } else {
            // ==========================================
            // 【追加モード】入力値をDBに新規登録
            // ==========================================
            const val = itemInput.value.trim();
            
            if (val !== "") {
                const items = val.split('、');
                for (const item of items) {
                    if (item.trim() !== "") {
                        await saveTemplateToDb(subjId, item.trim());
                    }
                }
                itemInput.value = '';
                await loadTemplates(subjId);
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

    const templateAddBtn = document.getElementById('addTemplateBtn');
    if (templateAddBtn) {
        templateAddBtn.addEventListener('click', addTemplate);
    }
});

// モーダル外クリック
const lessonModal = document.getElementById('lessonModal');
lessonModal.addEventListener('click', (e) => {
    if (e.target === lessonModal) lessonModal.style.display = 'none';
});