document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------------------------
    // 1. ドロップダウンメニューの制御 (テーブル内 + モーダル内)
    // ----------------------------------------------------------------------

    // 全てのドロップダウンメニュー要素 (テーブル内: .dropdown-menu と モーダル内: .modal-dropdown-menu を両方取得)
    const allDropdownMenus = document.querySelectorAll('.dropdown-menu, .modal-dropdown-menu');
    
    // 全てのドロップダウン表示要素 (テーブル内とモーダル内を統合)
    const allDisplayInputs = document.querySelectorAll('.subject-display[data-dropdown-for], .course-display[data-dropdown-for], .modal-course-display[data-dropdown-for], .subject-item[data-dropdown-for]');
    // 現在開いているドロップダウンを追跡する変数
    let currentOpenMenu = null;    // 開いているメニュー要素 (例: #courseDropdownMenu, #modalInfoDropdownMenu)
    let currentTableInput = null;  // クリックされた表示要素 (例: .course-display, .modal-course-display)

    /**
     * すべてのドロップダウンを閉じる関数
     */
    const closeAllDropdowns = () => {
        if (currentOpenMenu) {
            currentOpenMenu.classList.remove('is-open');
            // 授業名ドロップダウン用にカスタムクラスを削除 (CSS調整用)
            currentOpenMenu.classList.remove('is-open-subject-dropdown'); 
            // スタイルをリセット
            currentOpenMenu.style.position = '';
            currentOpenMenu.style.top = '';
            currentOpenMenu.style.left = '';
            currentOpenMenu.style.minWidth = '';
            currentOpenMenu.style.zIndex = '';
        }
        if (currentTableInput) {
             // 関連するCSSクラスを削除
             currentTableInput.classList.remove('is-open-course-dropdown');
             currentTableInput.classList.remove('is-open-subject-dropdown'); 
        }

        currentOpenMenu = null;
        currentTableInput = null;
    };

    // ----------------------------------------------------------------------
    // 授業名/コース表示要素のクリックイベント
    // ----------------------------------------------------------------------
    
    /** * クリックされた表示要素にドロップダウンを表示する共通ハンドラ 
     * @param {Event} event - イベントオブジェクト
     */
    const handleDisplayInputClick = (event) => {
        const input = event.currentTarget; // クリックされた要素 (.subject-item, .course-displayなど)
        event.stopPropagation(); // documentクリックイベント発火防止
        
        const targetMenuId = input.dataset.dropdownFor;
        const targetMenu = document.getElementById(targetMenuId);

        if (!targetMenu) return;

        // 開閉処理: クリックされた要素が既に開いているものと同じなら閉じる
        const isOpening = currentTableInput !== input || !targetMenu.classList.contains('is-open');
        
        closeAllDropdowns(); // 念のため全て閉じる

        if (isOpening) {
            // ★【追記】削除・編集対象の特定のため、is-selectedクラスを更新
            // モーダル内の subject-item がクリックされた場合のみ処理
            if (input.classList.contains('subject-item')) {
                // 他の subject-item の選択状態をリセット
                document.querySelectorAll('#modalSubjectList .subject-item').forEach(item => {
                    item.classList.remove('is-selected');
                });
                // 今回クリックされた要素を選択状態にする
                input.classList.add('is-selected'); 
            }
            
            // ドロップダウンを開く
            targetMenu.classList.add('is-open');
            currentOpenMenu = targetMenu;
            currentTableInput = input;

            // モーダル内のドロップダウンかどうかで処理を分ける
            if (input.classList.contains('modal-course-display') || input.classList.contains('subject-item')) {
                
                // ★【追記】subject-itemがクリックされた場合、トリガー要素にも開いているクラスを付与
                if (input.classList.contains('subject-item')) {
                     input.classList.add('is-open-subject-dropdown');
                }
                
                // subject-itemがクリックされた場合、位置を計算し、fixedで画面を基準に配置する
                const inputRect = input.getBoundingClientRect();
                
                targetMenu.style.position = 'fixed';
                
                // top: クリックされた要素の上端と合わせる
                targetMenu.style.top = `${inputRect.top}px`;
                
                // left: クリックされた要素の右端に隣接させる
                targetMenu.style.left = `${inputRect.right}px`;
                
                targetMenu.style.minWidth = ''; 
                targetMenu.style.zIndex = '1010'; 

                // ドロップダウンが画面右端をはみ出さないように調整 (任意)
                const viewportWidth = window.innerWidth;
                // 注意: offsetWidthはdisplay: none;の時は0になるため、事前にCSSで設定された幅を使うか、開いてから調整が必要
                // ここでは開いてから調整する前提で、念のためoffsetWidthを取得します
                const menuWidth = targetMenu.offsetWidth || 250; // CSSのデフォルト幅250pxを使用

                if (inputRect.right + menuWidth > viewportWidth - 20) { // 右端から20pxの余裕
                    // はみ出る場合、左側に表示する (リストの左端にドロップダウンの右端を合わせる)
                    targetMenu.style.left = `${inputRect.left - menuWidth}px`;
                }

            } else {
                // テーブル内のドロップダウン
                
                // コース名と授業名でクラスを分ける (CSSでスタイルを調整可能にする)
                if (input.classList.contains('course-display')) {
                    input.classList.add('is-open-course-dropdown');
                } else if (input.classList.contains('subject-display')) {
                    input.classList.add('is-open-subject-dropdown');
                    targetMenu.classList.add('is-open-subject-dropdown'); 
                }

                // 位置を設定 (テーブル内の入力フィールドの直下に画面固定で表示)
                const inputRect = input.getBoundingClientRect();
                
                targetMenu.style.position = 'fixed';
                targetMenu.style.top = `${inputRect.bottom}px`;
                targetMenu.style.left = `${inputRect.left}px`;
                targetMenu.style.minWidth = `${inputRect.width}px`; 
                targetMenu.style.zIndex = '1010'; // 他の要素より手前に表示
            }
        }
    };
    
    // ドロップダウンリスナーの初期登録
    allDisplayInputs.forEach(input => {
        input.addEventListener('click', handleDisplayInputClick);
    });

    // ----------------------------------------------------------------------
    // ドロップダウンメニュー内の選択イベント
    // ----------------------------------------------------------------------

    allDropdownMenus.forEach(menu => {
        menu.addEventListener('click', (event) => {
            // li > a 要素がクリックされたかを確認
            const link = event.target.closest('li a');
            if (link && currentTableInput) {
                event.preventDefault();
                
                // 選択されたテキストを取得
                const selectedText = link.textContent.trim();

                // テーブル内またはモーダル内の表示要素に反映
                currentTableInput.textContent = selectedText;

                // 関連付けられているデータ属性があれば、それも更新する
                const selectedData = link.dataset.infoCategory || link.dataset.course;
                if (selectedData) {
                    currentTableInput.dataset.selectedValue = selectedData;
                }

                // 選択状態のクラスを更新
                menu.querySelectorAll('li').forEach(li => li.classList.remove('is-selected'));
                link.closest('li').classList.add('is-selected');

                // ドロップダウンを閉じる
                closeAllDropdowns();
            }
        });
    });
    
    // ----------------------------------------------------------------------
    // 画面のどこかをクリックしたら閉じる (バブリングを利用)
    // ----------------------------------------------------------------------

    document.addEventListener('click', (event) => {
        // ドロップダウンメニュー自体またはそのトリガー要素がクリックされた場合は処理しない
        if (currentOpenMenu && !currentOpenMenu.contains(event.target) && !event.target.closest('[data-dropdown-for]')) {
            closeAllDropdowns();
        }
    });


    // ----------------------------------------------------------------------
    // 2. チェックボックスの制御 (全選択・行ハイライトの準備)
    // ----------------------------------------------------------------------
    
    const allRowCheckboxes = document.querySelectorAll('.table-row .column-check input[type="checkbox"]');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            const isChecked = selectAllCheckbox.checked;
            allRowCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                const row = checkbox.closest('.table-row');
                if (row) {
                    if (isChecked) {
                        row.classList.add('is-checked');
                    } else {
                        row.classList.remove('is-checked');
                    }
                }
            });
        });
    }

    allRowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const row = checkbox.closest('.table-row');
            if (row) {
                if (checkbox.checked) {
                    row.classList.add('is-checked');
                } else {
                    row.classList.remove('is-checked');
                }
            }
            
            if (selectAllCheckbox) {
                const allChecked = Array.from(allRowCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
        });
    });    


    // ----------------------------------------------------------------------
    // 3. teacher_delete.html 固有の削除モーダル・チェックボックス処理 
    // ----------------------------------------------------------------------

    if (document.body.id === 'teacher_delete') {
        const modal = document.getElementById('deleteModal');
        const openButton = document.getElementById('deleteActionButton'); 
        const cancelButton = document.getElementById('cancelDeleteButton');
        const teacherListContainer = document.getElementById('selectedTeacherList'); 
        
        const deleteCountDisplay = modal ? modal.querySelector('.modal-body p') : null;
        const confirmDeleteButton = document.getElementById('confirmDeleteButton');

        if (openButton && modal && deleteCountDisplay) {
            openButton.addEventListener('click', () => {
                const selectedTeachers = [];
                
                allRowCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const row = checkbox.closest('.table-row');
                        if (row) {
                            const nameInput = row.querySelector('.column-name input');
                            const mailInput = row.querySelector('.column-mail input');
                            
                            const nameValue = nameInput ? nameInput.value.trim() : '氏名不明';
                            const mailValue = mailInput ? mailInput.value.trim() : 'メールアドレス不明';
                            
                            selectedTeachers.push({ name: nameValue, mail: mailValue });
                        }
                    }
                });

                if (selectedTeachers.length === 0) {
                    alert('削除するアカウントを選択してください。');
                    return;
                }
                
                teacherListContainer.innerHTML = '';
                
                selectedTeachers.forEach(teacher => {
                    const item = document.createElement('div');
                    item.classList.add('deleted-item');
                    item.textContent = `${teacher.name} (${teacher.mail})`;
                    teacherListContainer.appendChild(item);
                });
                
                deleteCountDisplay.textContent = `以下の ${selectedTeachers.length} 件のアカウントを削除してもよろしいですか？`;
                
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
        
    }
    
    // ----------------------------------------------------------------------
    // 4. table-row クリックでモーダル表示 (class.html 用)
    // ----------------------------------------------------------------------

    const detailsModal = document.getElementById('teacherDetailsModal');
    const modalTeacherName = document.getElementById('modalTeacherName');
    const modalTeacherDisplayName = detailsModal ? detailsModal.querySelector('.teacher-display-name-modal') : null; 
    const confirmButton = document.getElementById('confirmButton');
    const addButton = detailsModal ? detailsModal.querySelector('.add-subject-button') : null;
    // ★【追記】削除ボタンを取得
    const deleteButton = detailsModal ? detailsModal.querySelector('.delete-subject-button') : null;

    const tableRows = document.querySelectorAll('.master-grant-table .table-row'); 

    // モーダル要素が存在する場合に有効化
    if (detailsModal) {
        
        tableRows.forEach(row => {
            
            const nameDisplay = row.querySelector('.column-name span');
            const name = nameDisplay ? nameDisplay.textContent.trim() : '';

            if (name === '') {
                return;
            }

            row.addEventListener('click', (event) => {
                // チェックボックス、ドロップダウン表示要素 (.course-display/.subject-display) 内でのクリックは無視
                if (event.target.closest('input')) return;
                if (event.target.closest('.course-display')) return;
                if (event.target.closest('.subject-display')) return;

                // モーダルコンテンツの動的更新
                const currentName = name; 
                modalTeacherName.textContent = `${currentName} 先生`; 
                
                if (modalTeacherDisplayName) {
                    // モーダル左側の「講師名」欄を更新
                    modalTeacherDisplayName.textContent = `${currentName}`; 
                }
                
                // モーダルを表示
                detailsModal.style.display = 'flex';
                closeAllDropdowns(); // テーブル内のドロップダウンが開いていたら閉じる
            });
        });
        
        // モーダルのオーバーレイをクリックした時の処理 (モーダルを閉じる)
        detailsModal.addEventListener('click', (e) => {
            if (e.target === detailsModal) {
                detailsModal.style.display = 'none';
                closeAllDropdowns(); // モーダル内のドロップダウンも閉じる
            }
        });
        
        // 確定ボタン（モーダル内の「確定」）が押された時の処理
        if (confirmButton) {
            confirmButton.addEventListener('click', () => {
                alert('アカウント情報の変更を確定しました。');
                detailsModal.style.display = 'none';
                closeAllDropdowns();
            });
        }
        
        // 追加ボタン (+) が押された時の処理
        if (addButton) {
            addButton.addEventListener('click', (e) => {
                e.preventDefault(); 
                
                const subjectList = document.getElementById('modalSubjectList');
                if (subjectList) {
                    // 1. 新しい subject-item 要素を作成
                    const newItem = document.createElement('div');
                    newItem.classList.add('subject-item');
                    // ドロップダウン制御に必要な属性を付与
                    newItem.setAttribute('data-dropdown-for', 'modalSubjectDropdown');
                    
                    // 2. 表示テキストを設定 (選択肢がないため、仮のテキストを設定)
                    newItem.textContent = '新規授業'; 
                    
                    // 3. リストの末尾に挿入
                    subjectList.appendChild(newItem);

                    // 4. 新しい項目にドロップダウンのクリックイベントを付与 (共通ハンドラを使用)
                    newItem.addEventListener('click', handleDisplayInputClick);
                }
            });
        }
        
        // 削除ボタン (-) が押された時の処理
        if (deleteButton) {
            deleteButton.addEventListener('click', (e) => {
                e.preventDefault(); 
                
                const subjectList = document.getElementById('modalSubjectList');
                if (subjectList) {
                    // 現在選択されている (is-selected クラスを持つ) 項目を検索
                    const selectedItem = subjectList.querySelector('.subject-item.is-selected');
                    
                    if (selectedItem) {
                        // 選択された項目をリストから削除
                        subjectList.removeChild(selectedItem);
                        
                        // 項目が削除されたため、ドロップダウンを閉じる（念のため）
                        closeAllDropdowns();
                        
                    } else {
                        // 選択されている項目がない場合
                        alert('削除する授業項目を選択してください。');
                    }
                }
            });
        }
        
    }
    // ----------------------------------------------------------------------
    // 5. teacher_addition.php 固有の行追加・削除処理
    // ----------------------------------------------------------------------
    if (document.body.id === 'teacher_addition') {
        const addRowButton = document.getElementById('addRowButton');
        const container = document.getElementById('teacherInputContainer');

        if (addRowButton && container) {
            // --- 行の追加処理 ---
            addRowButton.addEventListener('click', () => {
                const newRow = document.createElement('div');
                newRow.classList.add('table-row');

                // 削除ボタンを含めたHTMLを生成
                newRow.innerHTML = `
                    <div class="column-name">
                        <input type="text" name="teacher_names[]" placeholder="氏名" required>
                    </div>
                    <div class="column-mail">
                        <input type="email" name="teacher_emails[]" placeholder="メールアドレス" required>
                    </div>
                    <div class="column-action">
                        <button type="button" class="remove-row-button">
                            <span class="material-symbols-outlined">remove_circle</span>
                        </button>
                    </div>
                `;

                container.appendChild(newRow);
            });

            // --- 行の削除処理 (イベント委譲) ---
            // コンテナ全体でクリックを監視し、削除ボタンが押された時だけ反応させる
            container.addEventListener('click', (event) => {
                const removeBtn = event.target.closest('.remove-row-button');
                if (removeBtn) {
                    const row = removeBtn.closest('.table-row');
                    
                    // 全ての行を消してしまうと困る場合は、件数をチェックする
                    const rowCount = container.querySelectorAll('.table-row').length;
                    if (rowCount > 1) {
                        row.remove();
                    } else {
                        alert('少なくとも1つの入力欄が必要です。');
                    }
                }
            });
        }
    }

    // ----------------------------------------------------------------------
    // 6. teacher_info 固有のバリデーション処理 (ドメインチェック)
    // ----------------------------------------------------------------------
    if (document.body.id === 'teacher_info') {
        const updateForm = document.getElementById('updateForm');
        const allowedDomain = '@isahaya-cc.ac.jp';

        if (updateForm) {
            updateForm.addEventListener('submit', (event) => {
                // チェックされている行のチェックボックスを取得
                const checkedIndices = document.querySelectorAll('input[name="update_indices[]"]:checked');

                // 1. 何も選択されていない場合のチェック
                if (checkedIndices.length === 0) {
                    alert('変更するアカウントを左側のチェックボックスで選択してください。');
                    event.preventDefault();
                    return;
                }

                // 2. ドメインのチェック
                for (let checkbox of checkedIndices) {
                    const index = checkbox.value; // 行のインデックスを取得
                    // インデックスを元に対象行のメールアドレス入力欄を特定
                    const mailInput = document.querySelector(`input[name="teacher_data[${index}][mail]"]`);
                    
                    if (mailInput) {
                        const emailValue = mailInput.value.trim();

                        // ドメインが一致するか確認
                        if (!emailValue.endsWith(allowedDomain)) {
                            alert(`エラー: 「${emailValue}」は許可されていないドメインです。\nメールアドレスは ${allowedDomain} である必要があります。`);
                            mailInput.focus(); // エラー箇所にフォーカス
                            mailInput.style.backgroundColor = '#fff0f0'; // 視覚的に警告
                            
                            event.preventDefault(); // 送信を中止
                            return; // 最初の1件で見つかれば終了
                        }
                    }
                }

                // 全てOKなら確認ダイアログ
                if (!confirm(`${checkedIndices.length} 件のアカウント情報を更新します。よろしいですか？`)) {
                    event.preventDefault();
                }
            });
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'success') {
                // アラートを表示
                alert('アカウント情報の更新が完了しました。');
                
                // URLからパラメータを消去（リロードした時に何度も出ないようにする）
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }
        }
    }
});