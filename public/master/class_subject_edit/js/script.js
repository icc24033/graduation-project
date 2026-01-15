document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------------------------
    // 1. ドロップダウンメニューの制御 (サイドバー & テーブル内)
    // ----------------------------------------------------------------------

    // 必要な要素を全て取得
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    const dropdownMenus = document.querySelectorAll('.dropdown-menu');
    
    // data-dropdown-forを持つ全ての要素を取得 (詳細モーダル内のコース表示に使用)
    const allDropdownTiedInputs = document.querySelectorAll('[data-dropdown-for]'); 
    
    // 現在開いているドロップダウンを追跡する変数
    let currentOpenToggle = null;
    let currentOpenMenu = null;
    // テーブル/モーダル内のどの要素に紐づいているかを追跡
    let currentTiedInput = null; 
    // 編集中の授業科目カード要素を追跡する変数
    let currentEditingCard = null;
    
    // ======================================================================
    // ★追加★: 新規追加モードを追跡するフラグ (true: 新規追加, false: 既存編集)
    let isNewAdditionMode = false;
    // ======================================================================

    /**
     * すべてのドロップダウンを閉じる関数
     */
    const closeAllDropdowns = () => {
        // 1. サイドバーのメニューを閉じる
        document.querySelectorAll('.dropdown-toggle[aria-expanded="true"]').forEach(openToggle => {
            openToggle.setAttribute('aria-expanded', 'false');
            // .nav-itemの子要素である .dropdown-menu を探して閉じる
            const openMenu = openToggle.closest('.nav-item')?.querySelector('.dropdown-menu');
            if (openMenu) openMenu.classList.remove('is-open');
        });

        // 2. テーブル/モーダルのコースドロップダウンを閉じる
        if (currentTiedInput) {
            currentTiedInput.classList.remove('is-open-course-dropdown');
            
            // 関連するサイドバーのメニューを非表示にする
            const menuId = currentTiedInput.getAttribute('data-dropdown-for');
            const menu = document.getElementById(menuId);
            if (menu) {
                menu.classList.remove('is-open');
                // 位置指定をリセット (テーブル/モーダル用設定を解除)
                menu.style.left = '';
                menu.style.top = '';
                menu.style.position = ''; 
            }
        }
        
        // 追跡変数をリセット
        currentOpenToggle = null;
        currentOpenMenu = null;
        currentTiedInput = null; 
    };

    // --- 1-1. サイドバードロップダウンの開閉制御 ---
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (event) => {
            // 親の .nav-item を見つける
            const navItem = toggle.closest('.nav-item');
            // 同じ親の中にある .dropdown-menu を見つける
            const menu = navItem ? navItem.querySelector('.dropdown-menu') : null;

            if (menu) {
                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                
                closeAllDropdowns(); // まず全て閉じる

                if (!isExpanded) { // クリックした要素が閉じている場合のみ開く
                    toggle.setAttribute('aria-expanded', 'true');
                    menu.classList.add('is-open');

                    // 【メニューの位置設定】
                    const rect = toggle.getBoundingClientRect();
                    
                    // サイドバーのドロップダウンは、トグルの右側 + 5px、ボタンの下に配置
                    menu.style.left = `${rect.right + 5}px`;
                    menu.style.top = `${rect.top + rect.height}px`;
                    menu.style.position = 'fixed'; 

                    currentOpenToggle = toggle;
                    currentOpenMenu = menu;
                }
            }
            event.stopPropagation(); // documentクリックイベントの発火を防ぐ
        });
    });

    // --- 1-2. テーブル/モーダルのコースドロップダウン開閉制御 (修正箇所) ---
    /**
     * 新しく生成された要素に対応するため、ドロップダウン開閉処理を関数化
     * ★修正: クリック時に選択状態(.is-selected)を切り替える処理を追加
     */
    function handleDropdownTiedInputClick(event) {
        const input = event.currentTarget; // クリックされた要素 (.subject-item.course-display など)
        const menuId = input.getAttribute('data-dropdown-for');
        const menu = document.getElementById(menuId);

        // --- 【追加】選択状態の制御 ---
        // 同じリスト内(兄弟要素)の選択状態を一度すべて解除する
        if (input.parentNode) {
            const siblings = input.parentNode.children;
            for (let i = 0; i < siblings.length; i++) {
                siblings[i].classList.remove('is-selected');
            }
        }
        // クリックされた要素自体を選択状態にする
        input.classList.add('is-selected');
        // --- 【追加終了】 ---

        if (menu) {
            const isOpened = input.classList.contains('is-open-course-dropdown');
            
            closeAllDropdowns(); // まず全て閉じる

            // メニューが開いていなかった場合のみ開く
            // (閉じている場合でも is-selected は上で付与されているため削除可能になる)
            if (!isOpened) { 
                // closeAllDropdownsでis-selectedが消えるわけではないが、
                // ドロップダウンが開いている目印としてクラスを付与
                input.classList.add('is-open-course-dropdown'); 
                // ※ inputは上で既に is-selected になっています

                menu.classList.add('is-open'); // メニューを表示
                menu.style.position = 'fixed'; 

                const rect = input.getBoundingClientRect();
                
                // 位置設定
                menu.style.left = `${rect.right + 5}px`;
                menu.style.top = `${rect.top + rect.height}px`;
                
                currentTiedInput = input; // 現在の紐づけ要素を設定
            }
        }
        event.stopPropagation(); 
    }

    // 初期ロード時の要素にイベントリスナーを設定
    allDropdownTiedInputs.forEach(input => {
        // 念のため、過去のイベントリスナーがあれば削除してから追加
        input.removeEventListener('click', handleDropdownTiedInputClick); 
        input.addEventListener('click', handleDropdownTiedInputClick);
    });

    // --- 2. メニュー項目の選択処理 ---
    dropdownMenus.forEach(menu => {
        const links = menu.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const selectedValue = e.target.textContent;

                // A. サイドバーのドロップダウンだった場合 (currentOpenToggleが設定されている)
                if (currentOpenToggle) {
                    const currentValueSpan = currentOpenToggle.querySelector('.current-value');
                    if (currentValueSpan) {
                       currentValueSpan.textContent = selectedValue;
                    }
                } 
                // B. テーブル/モーダルのコースドロップダウンだった場合 (currentTiedInputが設定されている)
                //    モーダル内のコース名変更の要求に対応するための修正
                else if (currentTiedInput) {
                    // 選択されたコース名を選択元要素に反映し、即座にドロップダウンを閉じます。
                    currentTiedInput.textContent = selectedValue;
                }
                
                closeAllDropdowns(); // 選択後、全て閉じる
            });
        });
    }); 

    // --- 3. どこかをクリックしたらメニューを閉じる ---
    document.addEventListener('click', (event) => {
        const detailsModal = document.getElementById('subjectDetailsModal'); // 詳細モーダルを取得
        // 詳細モーダルが開いている場合、モーダル外クリックでドロップダウンが閉じないようにする
        const isClickInsideDetailsModal = detailsModal && detailsModal.classList.contains('is-open-modal') && event.target.closest('#subjectDetailsModal');

        // ドロップダウンの親要素 (.has-dropdown) またはコース表示要素 (.course-display) 
        // および、詳細モーダル内でのクリックではない場合に閉じる
        if (!event.target.closest('.has-dropdown') && !event.target.closest('.course-display') && !isClickInsideDetailsModal) {
            closeAllDropdowns();
        }
    });

    // ----------------------------------------------------------------------
    // 4. 授業科目カードの選択状態の制御 (list_of_couses_delete.html用)
    // ----------------------------------------------------------------------
    
    const subjectCards = document.querySelectorAll('.subject-card');
    
    // list_of_couses_delete.html のみでカードの複数選択を有効にする
    if (document.body.id === 'list_of_couses_delete') {
        subjectCards.forEach(card => {
            card.addEventListener('click', () => { 
                card.classList.toggle('is-selected');
                updateDeleteModalContent(); 
            });
        });
    }

    /**
     * list_of_couses_delete.html 固有のモーダルコンテンツを更新する関数
     */
    function updateDeleteModalContent() {
        // 削除モーダルのコンテンツ更新ロジック (省略)
        const selectedCards = document.querySelectorAll('.subject-card.is-selected');
        const deleteListContainer = document.getElementById('selectedcard-title'); 
        
        if (deleteListContainer) {
             deleteListContainer.innerHTML = '';
            
            if (selectedCards.length > 0) {
                selectedCards.forEach(card => {
                    const cardTitle = card.querySelector('.card-title').textContent;
                    const item = document.createElement('div');
                    item.classList.add('deleted-item');
                    item.textContent = cardTitle;
                    deleteListContainer.appendChild(item);
                });
            }
        }
    }

    // ----------------------------------------------------------------------
    // 5. list_of_couses_delete.html 固有の削除モーダル・チェックボックス処理
    // ----------------------------------------------------------------------

    if (document.body.id === 'list_of_couses_delete') { 
        // 削除モーダル関連の処理 (省略)
        const modal = document.getElementById('deleteModal');
        const deleteButton = document.querySelector('.delete-button'); 
        const cancelButton = document.getElementById('cancelDeleteButton');
        const confirmDeleteButton = document.getElementById('confirmDeleteButton');

        if (deleteButton && modal) {
            deleteButton.addEventListener('click', () => {
                const selectedCards = document.querySelectorAll('.subject-card.is-selected');
                if (selectedCards.length === 0) {
                    alert('削除する授業科目を選択してください。');
                    return;
                }
                updateDeleteModalContent(); 
                modal.style.display = 'flex';
            });
        }
        
        if (cancelButton && modal) {
            cancelButton.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        if (confirmDeleteButton && modal) {
            confirmDeleteButton.addEventListener('click', () => {
                alert('削除を実行しました。 (※実際にはこの後にサーバー処理が必要です)');
                modal.style.display = 'none';
                
                document.querySelectorAll('.subject-card.is-selected').forEach(card => {
                    card.classList.remove('is-selected');
                });
            });
        }
    }

    // ----------------------------------------------------------------------
    // 6. 詳細表示モーダル（ポップアップ）の制御 (list_of_subjects_addition.html用)
    // ----------------------------------------------------------------------

    const detailsModal = document.getElementById('subjectDetailsModal'); // <--- IDを修正
    const detailsSubjectNameInput = document.getElementById('detailsSubjectName'); // 授業科目名入力欄
    const confirmDetailsButton = document.getElementById('confirmDetailsButton'); // 確定ボタン

    const subjectCardsForAddition = document.querySelectorAll('#list_of_subjects_addition .subject-card'); // 編集画面のカード
    const addButton = document.querySelector('.add-button'); // 追加ボタン
    const subjectListContainer = document.querySelector('.subject-list'); // カードを追加するコンテナ

    /**
     * 詳細モーダルを開き、内容を初期化または設定する関数
     * @param {string} title - モーダルに表示する授業科目名
     * @param {boolean} isNew - 新規追加モードか
     */
    function openDetailsModal(title, isNew) {
        if (detailsModal) {
            // ★修正★: フラグを設定
            isNewAdditionMode = isNew;

            // 1. タイトルを設定 (カードクリック時はその科目のタイトル、追加ボタン時は空欄)
            detailsSubjectNameInput.value = title || '新規授業科目';
            
            // 2. 担当講師・教室・コースを初期値に戻す（今回はダミーデータを使用）
            // 既存のリスト項目をクリアし、初期項目を設定するロジックが必要だが、今回はDOMが固定のため、省略し、新規追加時にのみ反映されるよう後述の関数に任せる。
            
            // 3. モーダルを表示
            detailsModal.style.display = 'flex';
            closeAllDropdowns(); // ドロップダウンが開いていたら閉じる
        }
    }
    
    // ======================================================================
    // ★追加★: 授業科目カードを生成し、リストに追加する関数
    /**
     * モーダルの内容に基づき、新しい授業科目カードをリストに追加する
     */
    function addSubjectCardToList() {
        const subjectName = detailsSubjectNameInput.value || '名称未設定';
        
        // 実施学年はサイドバーの選択値から取得する (今回はダミーで固定)
        const gradeValueSpan = document.getElementById('gradeDropdownToggle').querySelector('.current-value');
        const grade = gradeValueSpan ? gradeValueSpan.textContent.trim() : '学年未定';

        // 新しいカード要素を作成
        const newCard = document.createElement('div');
        newCard.classList.add('subject-card');
        
        newCard.innerHTML = `
            <p class="card-grade">${grade}</p>
            <p class="card-title">${subjectName}</p>
        `;
        
        // カードをリストの末尾に追加
        if (subjectListContainer) {
            subjectListContainer.appendChild(newCard);
            
            // ★重要★: 新しいカードにもクリックイベントリスナーを設定する
            newCard.addEventListener('click', (event) => {
                const cardTitle = newCard.querySelector('.card-title').textContent;
                // 既存カードの編集モードとして開く (isNew: false)
                openDetailsModal(cardTitle, false); 
            });
        }
    }
    // ======================================================================


    // モーダル要素が存在する場合、かつ list_of_subjects_addition ページの場合に有効化
    if (detailsModal && document.body.id === 'list_of_subjects_addition') {
        
        // --- 6-1. 授業科目カードのクリック処理 ---
        subjectCardsForAddition.forEach(card => {
            card.addEventListener('click', (event) => {
                const cardTitle = card.querySelector('.card-title').textContent;
                // 既存カードの編集モードとして開く (isNew: false)
                openDetailsModal(cardTitle, false); 
                
                // 編集対象のカードとしてマーク（必要であれば）
                // currentEditingCard = card; 
            });
        });

        // --- 6-2. 「追加」ボタンのクリック処理 ---
        if (addButton) {
            addButton.addEventListener('click', () => {
                // 新規追加としてモーダルを開く (タイトルは空、isNew: true)
                openDetailsModal('', true); 
            });
        }

        // --- 6-3. モーダルの閉じる処理 (オーバーレイ/確定ボタン) ---
        
        // モーダルのオーバーレイをクリックした時の処理 (モーダルを閉じる)
        detailsModal.addEventListener('click', (e) => {
            if (e.target === detailsModal) {
                detailsModal.style.display = 'none';
                closeAllDropdowns(); // モーダル内のドロップダウンも閉じる
            }
        });
        
        // 確定ボタン（モーダル内の「確定」）が押された時の処理
        if (confirmDetailsButton) { // IDを list_of_subjects_addition.html のものに修正
            confirmDetailsButton.addEventListener('click', () => {
                const subjectName = detailsSubjectNameInput.value;
                
                if (isNewAdditionMode) {
                    // ★修正★: 新規追加モードの場合、カードを追加する
                    addSubjectCardToList(); 
                    alert(`「${subjectName}」を新規追加しました。`);
                } else {
                    // 既存編集モードの場合
                    alert(`「${subjectName}」の情報を確定（編集）しました。`);
                }
                
                detailsModal.style.display = 'none';
                closeAllDropdowns();
                // フラグをリセット
                isNewAdditionMode = false;
            });
        }

    }
    // ----------------------------------------------------------------------
    // 7. 詳細表示モーダル（ポップアップ）内のリスト項目追加機能
    // ----------------------------------------------------------------------

    // ... (7-1, 7-2, 7-3 のロジックは変更なし) ...
    // --- 7-1. 担当講師リストの追加 ---
    const addTeacherButton = detailsModal ? detailsModal.querySelector('.add-teacher-button') : null;
    const teacherListContainer = document.getElementById('modalTeacherist'); 

    if (addTeacherButton && teacherListContainer) {
        addTeacherButton.addEventListener('click', () => {
            addNewListItem(
                teacherListContainer, 
                'teacher-item', 
                'teacher-display', 
                'detailsTeacherDropdownMenu', 
                '講師未定' // 初期テキスト
            );
        });
    }

    // --- 7-2. 実施教室リストの追加 ---
    const addRoomButton = detailsModal ? detailsModal.querySelector('.add-room-button') : null;
    const roomListContainer = document.getElementById('modalRoomList');

    if (addRoomButton && roomListContainer) {
        addRoomButton.addEventListener('click', () => {
            addNewListItem(
                roomListContainer, 
                'room-item', 
                'room-display', 
                'detailsRoomDropdownMenu', 
                '教室未定' // 初期テキスト
            );
        });
    }

    // --- 7-3. 実施コースリストの追加 ---
    const addCousesButton = detailsModal ? detailsModal.querySelector('.add-couses-button') : null;
    const courseListContainer = document.getElementById('modalCourseList');

    if (addCousesButton && courseListContainer) {
        addCousesButton.addEventListener('click', () => {
            addNewListItem(
                courseListContainer, 
                'couses-item', 
                'course-display', 
                'detailsCourseDropdownMenu', 
                'コース未定' // 初期テキスト
            );
        });
    }

    /**
     * モーダル内のリストに新しい項目を追加する汎用関数
     * @param {HTMLElement} listContainer - 項目を追加する ul/div コンテナ (e.g., #modalTeacherist)
     * @param {string} itemClassName - 追加する項目のCSSクラス名 (e.g., 'teacher-item')
     * @param {string} displayClassName - ドロップダウン開閉のためのクラス名 (e.g., 'teacher-display')
     * @param {string} dropdownMenuId - 紐づけるドロップダウンメニューのID (e.g., 'detailsTeacherDropdownMenu')
     * @param {string} defaultText - 新しい項目の初期表示テキスト
     */
    function addNewListItem(listContainer, itemClassName, displayClassName, dropdownMenuId, defaultText) {
        if (!listContainer) return;

        // 1. 新しい項目要素を作成
        const newItem = document.createElement('div');
        newItem.classList.add(itemClassName);
        newItem.classList.add(displayClassName); // ドロップダウン制御に必要
        newItem.setAttribute('data-dropdown-for', dropdownMenuId);
        newItem.textContent = defaultText;

        // 2. リストコンテナに追加
        listContainer.appendChild(newItem);

        // 3. 新しい項目にドロップダウン開閉のイベントリスナーを設定
        //    (既存の handleDropdownTiedInputClick 関数を再利用)
        newItem.addEventListener('click', handleDropdownTiedInputClick);
    }

    // ----------------------------------------------------------------------
    // 8. 詳細表示モーダル（ポップアップ）内のリスト項目削除機能
    // ----------------------------------------------------------------------
    /**
     * モーダル内のリストから選択された項目を削除する汎用関数
     * @param {HTMLElement} listContainer - 項目を含む ul/div コンテナ (e.g., #modalTeacherist)
     */
    function deleteSelectedListItem(listContainer) {
        if (!listContainer) return;

        const selectedItem = listContainer.querySelector('.is-selected');
        
        if (selectedItem) {
            // 選択された項目をリストから削除
            listContainer.removeChild(selectedItem);
            // 削除後、ドロップダウンが開いている可能性のある要素をリセット
            closeAllDropdowns(); 
        } else {
            alert('削除する項目を選択してください。');
        }
    }

    // --- 8-2. 担当講師リストの削除 (「ー」ボタン) ---
    const deleteTeacherButton = detailsModal ? detailsModal.querySelector('.delete-teacher-button') : null;
    const teacherListContainerDelete = document.getElementById('modalTeacherist'); 

    if (deleteTeacherButton && teacherListContainerDelete) {
        deleteTeacherButton.addEventListener('click', () => {
            deleteSelectedListItem(teacherListContainerDelete);
        });
    }

    // --- 8-3. 実施教室リストの削除 (「ー」ボタン) ---
    const deleteRoomButton = detailsModal ? detailsModal.querySelector('.delete-room-button') : null;
    const roomListContainerDelete = document.getElementById('modalRoomList');

    if (deleteRoomButton && roomListContainerDelete) {
        deleteRoomButton.addEventListener('click', () => {
            deleteSelectedListItem(roomListContainerDelete);
        });
    }

    // --- 8-4. 実施コースリストの削除 (「ー」ボタン) ---
    // 注意: .add-couses-buttonが重複しているため、ここで .delete-couses-button に修正
    const deleteCousesButton = detailsModal ? detailsModal.querySelector('.delete-couses-button') : null;
    const courseListContainerDelete = document.getElementById('modalCourseList');

    if (deleteCousesButton && courseListContainerDelete) {
        deleteCousesButton.addEventListener('click', () => {
            deleteSelectedListItem(courseListContainerDelete);
        });
    }

});

        const allCourseInfo = <?= json_encode($courseInfo) ?>;
        let currentData = {};

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
                courseBox.style.opacity = "1";
                const grade = parseInt(gradeVal);
                for (let key in allCourseInfo) {
                    if (allCourseInfo[key].grade === grade) {
                        let opt = document.createElement('option');
                        opt.value = key;
                        opt.text = allCourseInfo[key].name;
                        courseSelect.appendChild(opt);
                    }
                }
            }
        }

        function openDetail(data) {
            currentData = data;
            document.getElementById('m-title').innerText = data.title;
            
            // 講師表示：複数対応
            const tArray = data.teacher.split('、');
            const tDisplay = data.teacher === '未設定' ? '未設定' : tArray.join(' 先生、') + " 先生";
            document.getElementById('m-teacher').innerText = tDisplay;
            
            document.getElementById('m-room').innerText = data.room;
            document.getElementById('m-courses').innerText = data.courses.join(' / ');

            const teacherSel = document.getElementById('sel-teacher');
            teacherSel.selectedIndex = 0;

            const roomSel = document.getElementById('sel-room');
            roomSel.value = data.room;

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
            ajax({action: 'update_field', field: field, value: val, mode: mode, grade: currentData.grade});
        }

        function clearField(field) {
            if(confirm("解除して『未設定』にしますか？")) {
                ajax({action: 'update_field', field: field, value: '未設定', mode: 'overwrite', grade: currentData.grade});
            }
        }

        function updateCourse(action) {
            const type = (action === 'add_course') ? 'add' : 'remove';
            const courseKey = document.getElementById('sel-course-' + type).value;
            if (!courseKey) return alert("コースを選択してください");
            ajax({action: action, course_key: courseKey, grade: currentData.grade});
        }

        function ajax(data) {
            const fd = new FormData();
            for(let k in data) fd.append(k, data[k]);
            fd.append('subject_name', currentData.title);
            fetch('tuika.php', {method: 'POST', body: fd})
            .then(res => res.json())
            .then(res => { if(res.success) location.reload(); })
            .catch(err => alert("通信エラーが発生しました"));
        }

        window.onclick = (e) => { if(e.target.classList.contains('modal-overlay')) e.target.style.display = 'none'; }
    