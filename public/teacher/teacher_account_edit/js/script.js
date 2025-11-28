document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------------------------
    // 1. ドロップダウンメニューの制御 (サイドバー & テーブル内)
    // ----------------------------------------------------------------------

    // 必要な要素を全て取得
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    const dropdownMenus = document.querySelectorAll('.dropdown-menu');
    // data-dropdown-forを持つテーブル内のコース表示要素を取得
    const tableCourseInputs = document.querySelectorAll('.course-display[data-dropdown-for]'); 
    
    // 現在開いているドロップダウンを追跡する変数
    let currentOpenToggle = null;
    let currentOpenMenu = null;
    // テーブル内のどの要素に紐づいているかを追跡
    let currentTableInput = null; 

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

        // 2. テーブルのコースドロップダウンを閉じる
        if (currentTableInput) {
            currentTableInput.classList.remove('is-open-course-dropdown');
            
            // 関連するサイドバーのメニューを非表示にする
            const menuId = currentTableInput.getAttribute('data-dropdown-for');
            const menu = document.getElementById(menuId);
            if (menu) {
                menu.classList.remove('is-open');
                // 位置指定をリセット (テーブル用設定を解除)
                menu.style.left = '';
                menu.style.top = '';
                menu.style.position = ''; // スタイルをリセット
            }
        }
        
        // 追跡変数をリセット
        currentOpenToggle = null;
        currentOpenMenu = null;
        currentTableInput = null; 
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
                    menu.style.position = 'fixed'; // 固定配置

                    currentOpenToggle = toggle;
                    currentOpenMenu = menu;
                }
            }
            event.stopPropagation(); // documentクリックイベントの発火を防ぐ
        });
    });

    // --- 1-2. テーブルのコースドロップダウン開閉制御 ---
    tableCourseInputs.forEach(input => {
        input.addEventListener('click', (event) => {
            const menuId = input.getAttribute('data-dropdown-for');
            // サイドバー（あるいは任意の場所）にあるドロップダウンメニュー要素を取得
            const menu = document.getElementById(menuId);

            if (menu) {
                const isOpened = input.classList.contains('is-open-course-dropdown');
                
                closeAllDropdowns(); // まず全て閉じる

                if (!isOpened) { // クリックした要素が閉じている場合のみ開く
                    input.classList.add('is-open-course-dropdown'); // 開いている状態をマーク (CSSで枠線適用など)
                    menu.classList.add('is-open'); // メニューを表示
                    menu.style.position = 'fixed'; // 固定配置

                    // 【メニューの位置設定】
                    const rect = input.getBoundingClientRect();
                    
                    // テーブルの入力フィールドの右側 + 5px、入力フィールドの下に配置
                    menu.style.left = `${rect.right + 5}px`;
                    menu.style.top = `${rect.top + rect.height}px`;
                    
                    currentTableInput = input; // 現在のテーブル入力を設定
                }
            }
            event.stopPropagation(); // documentクリックイベントの発火を防ぐ
        });
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
                // B. テーブルのコースドロップダウンだった場合 (currentTableInputが設定されている)
                else if (currentTableInput) {
                    currentTableInput.textContent = selectedValue;
                }
                
                closeAllDropdowns(); // 選択後、全て閉じる
            });
        });
    });

    // --- 3. どこかをクリックしたらメニューを閉じる ---
    document.addEventListener('click', (event) => {
        // ドロップダウンの親要素 (.has-dropdown) またはテーブルのコース表示要素 (.course-display) 
        // のどちらにも該当しない場所がクリックされた場合に閉じる
        if (!event.target.closest('.has-dropdown') && !event.target.closest('.course-display')) {
            closeAllDropdowns();
        }
    });

    // ----------------------------------------------------------------------
    // 4. student_delete.html 固有の削除モーダル・チェックボックス処理
    // ----------------------------------------------------------------------

    // student_delete.html 固有の処理
    if (document.body.id === 'student_delete') {
        const modal = document.getElementById('deleteModal');
        const openButton = document.getElementById('deleteActionButton'); 
        const cancelButton = document.getElementById('cancelDeleteButton');
        const studentListContainer = document.getElementById('selectedStudentList');
        // modal.querySelector は modal が存在する場合のみ実行
        const deleteCountDisplay = modal ? modal.querySelector('.modal-body p') : null;
        
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        const confirmDeleteButton = document.getElementById('confirmDeleteButton');

        // 削除ボタン (deleteActionButton) をクリックした時の処理
        if (openButton && modal && deleteCountDisplay) {
            openButton.addEventListener('click', () => {
                const selectedStudents = [];
                
                // チェックされた行のデータを取得
                rowCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const row = checkbox.closest('.table-row');
                        
                        const id = checkbox.getAttribute('data-student-id'); 
                        const name = checkbox.getAttribute('data-student-name');
                        
                        if (id && name) { // data属性から情報を取得
                            selectedStudents.push({ id, name });
                        } else if (row) {
                            // data属性がない場合は、行の入力フィールドから取得するフォールバック処理
                            // 注意: ここで input が見つからない可能性もある
                            const idInput = row.querySelector('.column-student-id input');
                            const nameInput = row.querySelector('.column-name input');
                            const idValue = idInput ? idInput.value : 'ID不明';
                            const nameValue = nameInput ? nameInput.value : '氏名不明';
                            selectedStudents.push({ id: idValue, name: nameValue });
                        }
                    }
                });

                // 選択された学生がいない場合はアラートを表示して中断
                if (selectedStudents.length === 0) {
                    alert('削除するアカウントを選択してください。');
                    return;
                }
                
                // モーダル内のリストをクリア
                studentListContainer.innerHTML = '';
                
                // 選択された学生の情報をモーダルに追加
                selectedStudents.forEach(student => {
                    const item = document.createElement('div');
                    item.classList.add('deleted-item');
                    // ID（学生番号）と氏名を表示
                    item.textContent = `${student.id}: ${student.name}`;
                    studentListContainer.appendChild(item);
                });
                
                // 削除件数の表示を更新
                deleteCountDisplay.innerHTML = `以下の**${selectedStudents.length}件**のアカウントを削除してもよろしいですか？`;

                // モーダルを表示
                modal.style.display = 'flex';
            });
        }

        // キャンセルボタンをクリックした時の処理 (モーダルを消す)
        if (cancelButton && modal) {
            cancelButton.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }

        // モーダルのオーバーレイをクリックした時の処理 (モーダルを消す)
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // 全選択/全解除の機能
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', () => {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                    // 行の背景色変更
                    const row = checkbox.closest('.table-row');
                    if (row) {
                        if (checkbox.checked) {
                            row.classList.add('is-checked');
                        } else {
                            row.classList.remove('is-checked');
                        }
                    }
                });
            });
        }
        
        // 個別チェックボックスのクリック時の行背景色変更
        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const row = checkbox.closest('.table-row');
                if (row) {
                    if (checkbox.checked) {
                        row.classList.add('is-checked');
                    } else {
                        row.classList.remove('is-checked');
                        // 一つでもチェックが外れたら、全選択チェックボックスを解除
                        if (selectAllCheckbox) {
                            selectAllCheckbox.checked = false;
                        }
                    }
                }
                
                // 全てのチェックボックスがチェックされたら全選択チェックボックスをチェック
                if (selectAllCheckbox) {
                    const allChecked = Array.from(rowCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }
            });
        });


        // 削除確認ボタン（モーダル内の「削除」）が押された時の処理
        if (confirmDeleteButton && modal) {
            confirmDeleteButton.addEventListener('click', () => {
                // *** ここにサーバーに削除リクエストを送る処理を記述します (Fetch APIなどを使用) ***
                
                // 例としてアラートを表示し、モーダルを閉じる
                alert('削除を実行しました。 (※実際にはこの後にサーバー処理が必要です)');
                modal.style.display = 'none';
                // 削除が成功したら、チェックされた行をDOMから削除する処理などを追加
            });
        }
    }
});
document.addEventListener('DOMContentLoaded', function() {
    const deleteActionButton = document.getElementById('deleteActionButton');
    const deleteModal = document.getElementById('deleteModal');
    const cancelDeleteButton = document.getElementById('cancelDeleteButton');
    
    // --- 1. サイドバーの「アカウントの削除」リンクの動作確認 ---
    // もしこのリンクがモーダルを表示させている場合、このリンクのイベントリスナーを見直す必要があります。
    // HTMLのリンク自体（<a href="student_delete.html">）はページ遷移をします。

    // --- 2. テーブル下の「削除」ボタンを押したときの処理 ---
    deleteActionButton.addEventListener('click', function() {
        // ここで、選択された学生リストのデータを取得・更新するロジックを挟む
        
        // モーダルを表示する
        deleteModal.classList.add('is-open-modal');
    });

    // --- 3. キャンセルボタンでモーダルを閉じる処理 ---
    cancelDeleteButton.addEventListener('click', function() {
        // モーダルを非表示にする
        deleteModal.classList.remove('is-open-modal');
    });
    
    // --- 4. モーダル外をクリックして閉じる処理 (オプション) ---
    deleteModal.addEventListener('click', function(event) {
        if (event.target.id === 'deleteModal') {
            deleteModal.classList.remove('is-open-modal');
        }
    });
    
    // 他のモーダル表示・非表示に関するJavaScriptがあれば、それらを修正する必要があります。
});
// ----------------------------------------------------------------------
    // 5. student_addition.html 固有のアカウント追加処理
    // ----------------------------------------------------------------------
    
    // student_addition.html 固有の処理
    if (document.body.id === 'student_addition') {
        const addButton = document.querySelector('.add-button');
        const tableContainer = document.querySelector('.account-table-container');

        if (addButton && tableContainer) {
            // 新しい行のHTMLテンプレート
            // 学生番号と氏名の value="氏名" は、新しい行なので空欄にするべきですが、
            // 既存のHTMLテンプレートに倣い、ここでは空の行のテンプレートを定義します。
            // 既存の行の value が "20001" や "氏名" になっていますが、
            // 新規追加なので、ここでは空のテンプレート `value=""` を使用します。
            const newRowTemplate = `
                <div class="table-row">
                    <div class="column-check"><input type="checkbox" class="row-checkbox" data-student-id="" data-student-name=""></div> 
                    <div class="column-student-id"><input type="text" value=""></div> 
                    <div class="column-name"><input type="text" value=""></div> 
                    <div class="column-course">
                        <span class="course-display" data-course-input data-dropdown-for="courseDropdownMenu">コース</span>
                    </div>
                </div>
            `;
            
            // 最新の学生番号を取得し、次の番号を推測する関数
            const getNextStudentId = () => {
                const studentIdInputs = tableContainer.querySelectorAll('.column-student-id input');
                let maxId = 0;
                
                // 既存の入力フィールドから最大値を探す
                studentIdInputs.forEach(input => {
                    const id = parseInt(input.value, 10);
                    if (!isNaN(id) && id > maxId) {
                        maxId = id;
                    }
                });
                
                // 最大値 + 1 を返す。もし行がなければ適当な初期値 (例: 20016)
                // 既存のHTMLが20015で終わっているので、20016を初期値とします。
                return maxId > 0 ? (maxId + 1).toString() : '20016';
            };


            addButton.addEventListener('click', () => {
                // 1. 新しい行の要素を作成
                const newRow = document.createElement('div');
                newRow.innerHTML = newRowTemplate.trim();
                const newRowElement = newRow.firstChild;
                
                // 2. 学生番号とdata属性を更新 (任意)
                // 新しい行は基本的に空欄ですが、もし自動採番したい場合はここで更新します。
                const nextId = getNextStudentId();
                const studentIdInput = newRowElement.querySelector('.column-student-id input');
                const checkbox = newRowElement.querySelector('.row-checkbox');
                
                if (studentIdInput) {
                    studentIdInput.value = nextId; // 次の学生番号を自動入力
                }
                if (checkbox) {
                    checkbox.setAttribute('data-student-id', nextId);
                    checkbox.setAttribute('data-student-name', '氏名'); // 初期値として仮の氏名を設定
                    checkbox.setAttribute('data-student-couse', 'コース');
                }

                // 3. テーブルコンテナの最後に追加
                tableContainer.appendChild(newRowElement);
                
                // 4. 追加された行のコース表示要素にイベントリスナーを再設定
                // 既存のイベントリスナー（1-2. テーブルのコースドロップダウン開閉制御）を新しい要素にも適用
                const newCourseInput = newRowElement.querySelector('.course-display[data-dropdown-for]');
                if (newCourseInput) {
                    // ドロップダウンのクリックイベントを再登録するための関数
                    setupCourseDropdown(newCourseInput);
                }
                
                // 5. 追加された行のチェックボックスにイベントリスナーを再設定 (オプション: 削除ページとの連携が必要なければ不要)
                // このページ (student_addition.html) ではチェックボックスの機能は不要ですが、
                // テンプレートとして残しておきます。
                // setupRowCheckbox(newRowElement.querySelector('.row-checkbox'));

                // 6. 追加された行までスクロール
                tableContainer.scrollTop = tableContainer.scrollHeight;
            });
            
            /**
             * 新しい行のコースドロップダウンにイベントリスナーを設定するヘルパー関数
             * (既存のコード 1-2 のロジックを再利用するため)
             */
            const setupCourseDropdown = (input) => {
                input.addEventListener('click', (event) => {
                    const menuId = input.getAttribute('data-dropdown-for');
                    const menu = document.getElementById(menuId);

                    if (menu) {
                        const isOpened = input.classList.contains('is-open-course-dropdown');
                        
                        // 既存の closeAllDropdowns 関数は既に定義されているものとする
                        closeAllDropdowns(); 

                        if (!isOpened) { 
                            input.classList.add('is-open-course-dropdown'); 
                            menu.classList.add('is-open'); 
                            menu.style.position = 'fixed'; 

                            const rect = input.getBoundingClientRect();
                            
                            menu.style.left = `${rect.right + 5}px`;
                            menu.style.top = `${rect.top + rect.height}px`;
                            
                            // currentTableInput は既存のスコープで定義されているものとする
                            currentTableInput = input; 
                        }
                    }
                    event.stopPropagation(); 
                });
            };
        }
    }
    document.addEventListener('DOMContentLoaded', () => {
    // 1. 要素の取得
    const addButton = document.querySelector('.button-group .add-button:last-child'); // 追加人数入力ボタン
    const modal = document.getElementById('addCountModal'); // モーダルオーバーレイ
    const cancelButton = document.getElementById('cancelAddCount'); // キャンセルボタン
    const confirmButton = document.getElementById('confirmAddCount'); // 追加ボタン
    const countInput = document.getElementById('studentCountInput'); // 人数入力フィールド

    // 2. モーダル表示ロジック
    if (addButton) {
        addButton.addEventListener('click', () => {
            modal.classList.add('is-open-modal');
            countInput.focus(); // 入力フィールドにフォーカスを当てる
        });
    }

    // 3. モーダル非表示ロジック
    const closeModal = () => {
        modal.classList.remove('is-open-modal');
    };

    // キャンセルボタン
    if (cancelButton) {
        cancelButton.addEventListener('click', closeModal);
    }

    // オーバーレイ（背景）クリック
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target.id === 'addCountModal') { // オーバーレイ自体がクリックされた場合
                closeModal();
            }
        });
    }

    // 4. 追加ボタンのロジック (ダミー処理)
    if (confirmButton) {
        confirmButton.addEventListener('click', () => {
            const count = parseInt(countInput.value, 10);
            
            if (isNaN(count) || count < 1) {
                alert('有効な人数（1以上）を入力してください。');
                return;
            }

            // 【ここに生徒アカウントを追加する処理を実装します】
            console.log(`${count} 名のアカウントを追加する処理を実行...`);
            alert(`${count} 名のアカウントを追加しました。（※この処理はダミーです）`);
            
            closeModal();
        });
    }
    
    // 以下、既存のロジックを続ける
    // ------------------------------------
    // ドロップダウンロジックなど...
});