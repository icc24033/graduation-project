<!DOCTYPE html>
<html lang="ja">
<head>
    <title>ICCスマートキャンパス</title>
    <meta charset="utf-8">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="roboots" content="nofollow,noindex">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Noto+Sans+JP:wght@100..900&display=swap" rel="stylesheet">
</head>
<body>
    <header class="page-header">
        <p>ICCスマートキャンパス</p>
        <div class="user-icon">
            <div class="i_head"></div>
            <div class="i_body"></div>
        </div>
    </header>
    <div class="action-buttons">
        <button class="date-btn">
            <p>日付</p>
        </button>
        <div class="dropdown-wrapper course-select-wrapper">
            <button class="course-select dropdown-toggle" id="course-toggle-button" aria-expanded="false">
                <span>コース選択</span>
                <img class="select button-icon" src="images/chevron-down.svg" alt="ドロップダウンアイコン">
            </button>
            <div class="dropdown-content course-content" id="course-content" aria-labelledby="course-toggle-button">
                <a href="#" class="dropdown-item">システムデザインコース</a>
                <a href="#" class="dropdown-item">Webクリエイタコース</a>
                <a href="#" class="dropdown-item">マルチメディアOAコース</a>
                <a href="#" class="dropdown-item">基本情報コース</a>
                <a href="#" class="dropdown-item">ITパスポートコース</a>
                <a href="#" class="dropdown-item">応用情報コース</a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">1年1組</a>
                <a href="#" class="dropdown-item">1年2組</a>
            </div>
        </div>
        <button class="search">
            <img class="search-img" src="images/search.svg" alt="検索アイコン">
            <p>検索</p>
        </button>
    </div>
    <p>2025/12/3 (火) の時間割</p>
    <main class="main-content" id="schedule-container">
        <div class="schedule-list">
            <section class="card">
                <div class="info">
                    <div class="subject-details">
                        <h2 class="subject">C#</h2>
                        <p class="room-name">プ実１２</p>
                    </div>
                    <div class="period-details">
                        <p class="period-time">１限（9:10 ~ 10:40）</p>
                        <div class="button-container">
                            <div class="dropdown-wrapper detail-dropdown-wrapper">
                                <button class="button dropdown-toggle detail-toggle" id="detail-toggle-button-1" aria-expanded="false">
                                    <p>授業詳細</p>
                                    <img class="button-icon detail-icon" src="images/arrow_right.svg" alt="矢印アイコン">
                                </button>
                                <div class="dropdown-content detail-content" id="detail-content-1" aria-labelledby="detail-toggle-button-1">
                                    <div class="detail-box"><div class="detail-title">課題</div><p class="detail-text">課題はありません。</p></div>
                                    <div class="detail-box"><div class="detail-title">授業詳細</div><p class="detail-text">今日の授業内容:<br>モグラたたきを作ります。<br>教科書:P309～</p></div>
                                </div>
                            </div>
                            <div class="dropdown-wrapper item-dropdown-wrapper">
                                <button class="button dropdown-toggle item-toggle" id="item-toggle-button-1" aria-expanded="false">
                                    <span class="button-text-container">
                                        <p>持ってくるもの</p>
                                        <img class="select-icon" src="images/chevron-down.svg" alt="ドロップダウンアイコン">
                                    </span>
                                    <img class="button-icon item-icon" src="images/arrow_right.svg" alt="矢印アイコン">
                                </button>
                                <div class="dropdown-content item-content" id="item-content-1" aria-labelledby="item-toggle-button-1">
                                    <ul class="item-list">
                                        <li>持ってくるもの<img class="button-icon item-icon" src="images/arrow_drop_down.svg" alt="ドロップダウンアイコン"></li>
                                        <li><input type="checkbox" id="csharp-textbook-1"><label for="csharp-textbook-1">C#教科書</label></li>
                                        <li><input type="checkbox" id="writing-tools-1"><label for="writing-tools-1">筆記用具</label></li>
                                        <li><input type="checkbox" id="item-c-1"><label for="item-c-1">USBメモリ</label></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="card">
                <div class="info">
                    <div class="subject-details">
                        <h2 class="subject">選択<br>科目</h2>
                        <p class="room-name">マルチ2,3,シス2</p>
                    </div>
                    <div class="period-details">
                        <p class="period-time">２限（10:50 ~ 12:20）</p>
                        <div class="button-container">
                            <div class="dropdown-wrapper detail-dropdown-wrapper">
                                <button class="button dropdown-toggle detail-toggle" id="detail-toggle-button-2" aria-expanded="false">
                                    <p>授業詳細</p>
                                    <img class="button-icon detail-icon" src="images/arrow_right.svg" alt="矢印アイコン">
                                </button>
                                <div class="dropdown-content detail-content" id="detail-content-2" aria-labelledby="detail-toggle-button-2">
                                    <div class="detail-box"><div class="detail-title">課題</div><p class="detail-text">課題はなし。</p></div>
                                    <div class="detail-box"><div class="detail-title">授業詳細</div><p class="detail-text">今日のテーマ：Webデザインの基本</p></div>
                                </div>
                            </div>
                            <div class="dropdown-wrapper item-dropdown-wrapper">
                                <button class="button dropdown-toggle item-toggle" id="item-toggle-button-2" aria-expanded="false">
                                    <span class="button-text-container">
                                        <p>持ってくるもの</p>
                                        <img class="select-icon" src="images/chevron-down.svg" alt="ドロップダウンアイコン">
                                    </span>
                                    <img class="button-icon item-icon" src="images/arrow_right.svg" alt="矢印アイコン">
                                </button>
                                <div class="dropdown-content item-content" id="item-content-2" aria-labelledby="item-toggle-button-2">
                                    <ul class="item-list">
                                        <li>持ってくるもの<img class="button-icon item-icon" src="images/arrow_drop_down.svg" alt="ドロップダウンアイコン"></li>
                                        <li><input type="checkbox" id="option-textbook-2"><label for="option-textbook-2">選択科目ノート</label></li>
                                        <li><input type="checkbox" id="option-pen-2"><label for="option-pen-2">赤ペン</label></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="card">
                <div class="info">
                    <div class="subject-details">
                        <h2 class="subject">卒業<br>研究</h2>
                        <p class="room-name">マルチ2</p>
                    </div>
                    <div class="period-details">
                        <p class="period-time">３限（13:10 ~ 14:40）</p>
                        <div class="button-container">
                            <div class="dropdown-wrapper detail-dropdown-wrapper">
                                <button class="button dropdown-toggle detail-toggle" id="detail-toggle-button-3" aria-expanded="false">
                                    <p>授業詳細</p>
                                    <img class="button-icon detail-icon" src="images/arrow_right.svg" alt="矢印アイコン">
                                </button>
                                <div class="dropdown-content detail-content" id="detail-content-3" aria-labelledby="detail-toggle-button-3">
                                    <div class="detail-box"><div class="detail-title">課題</div><p class="detail-text">次のミーティングまでに進捗報告書作成</p></div>
                                    <div class="detail-box"><div class="detail-title">授業詳細</div><p class="detail-text">中間発表の準備と指導</p></div>
                                </div>
                            </div>
                            <div class="dropdown-wrapper item-dropdown-wrapper">
                                <button class="button dropdown-toggle item-toggle" id="item-toggle-button-3" aria-expanded="false">
                                    <span class="button-text-container">
                                        <p>持ってくるもの</p>
                                        <img class="select-icon" src="images/chevron-down.svg" alt="ドロップダウンアイコン">
                                    </span>
                                    <img class="button-icon item-icon" src="images/arrow_right.svg" alt="矢印アイコン">
                                </button>
                                <div class="dropdown-content item-content" id="item-content-3" aria-labelledby="item-toggle-button-3">
                                    <ul class="item-list">
                                        <li>持ってくるもの<img class="button-icon item-icon" src="images/arrow_drop_down.svg" alt="ドロップダウンアイコン"></li>
                                        <li><input type="checkbox" id="grad-research-data-3"><label for="grad-research-data-3">研究データ</label></li>
                                        <li><input type="checkbox" id="grad-research-notes-3"><label for="grad-research-notes-3">進捗メモ</label></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="card">
                <div class="info">
                    <div class="subject-details">
                        <h2 class="subject">卒業<br>研究</h2>
                        <p class="room-name">マルチ2</p>
                    </div>
                    <div class="period-details">
                        <p class="period-time">４限（14:50 ~ 16:20）</p>
                        <div class="button-container">
                            <div class="dropdown-wrapper detail-dropdown-wrapper">
                                <button class="button dropdown-toggle detail-toggle" id="detail-toggle-button-4" aria-expanded="false">
                                    <p>授業詳細</p>
                                    <img class="button-icon detail-icon" src="images/arrow_right.svg" alt="矢印アイコン">
                                </button>
                                <div class="dropdown-content detail-content" id="detail-content-4" aria-labelledby="detail-toggle-button-4">
                                    <div class="detail-box"><div class="detail-title">課題</div><p class="detail-text">特になし</p></div>
                                    <div class="detail-box"><div class="detail-title">授業詳細</div><p class="detail-text">グループディスカッション</p></div>
                                </div>
                            </div>
                            <div class="dropdown-wrapper item-dropdown-wrapper">
                                <button class="button dropdown-toggle item-toggle" id="item-toggle-button-4" aria-expanded="false">
                                    <span class="button-text-container">
                                        <p>持ってくるもの</p>
                                        <img class="select-icon" src="images/chevron-down.svg" alt="ドロップダウンアイコン">
                                    </span>
                                    <img class="button-icon item-icon" src="images/arrow_right.svg" alt="矢印アイコン">
                                </button>
                                <div class="dropdown-content item-content" id="item-content-4" aria-labelledby="item-toggle-button-4">
                                    <ul class="item-list">
                                        <li>持ってくるもの<img class="button-icon item-icon" src="images/arrow_drop_down.svg" alt="ドロップダウンアイコン"></li>
                                        <li><input type="checkbox" id="grad-research-laptop-4"><label for="grad-research-laptop-4">ノートPC</label></li>
                                        <li><input type="checkbox" id="grad-research-charger-4"><label for="grad-research-charger-4">充電器</label></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <script>
      "use strict";
      document.addEventListener('DOMContentLoaded', function() {
          const toggleButtons = document.querySelectorAll('.dropdown-toggle');
  
          //ドロップダウンを閉じる共通関数
          function closeDropdown(wrapper) {
              const button = wrapper.querySelector('.dropdown-toggle');
              const contentElement = wrapper.querySelector('.dropdown-content');
  
              if (button.getAttribute('aria-expanded') === 'true') {
                  contentElement.classList.remove('show');
                  wrapper.classList.remove('active');
                  button.setAttribute('aria-expanded', 'false');
              }
          }
  
          //ドロップダウンを開閉する共通関数 (変更なしで対応可能)
          function toggleDropdown(button) {
              const wrapper = button.closest('.dropdown-wrapper');
              const isDetailButton = button.classList.contains('detail-toggle');
  
              const idParts = button.id ? button.id.split('-') : [];
              const idNumber = idParts.pop();
              
              let contentElement;
              if (isDetailButton) {
                  contentElement = document.getElementById(`detail-content-${idNumber}`);
              } else if (button.classList.contains('item-toggle')) {
                  contentElement = document.getElementById(`item-content-${idNumber}`);
              } else if (wrapper.classList.contains('course-select-wrapper')) {
                  // コース選択ドロップダウンの処理
                  contentElement = wrapper.querySelector('.dropdown-content');
              } else {
                  return; // 対象外のボタン
              }
              
              if (!contentElement || !wrapper) return;
  
              const isExpanded = button.getAttribute('aria-expanded') === 'true';
  
              if (isDetailButton || button.classList.contains('item-toggle')) {
                  // 授業カード内のドロップダウン（排他制御あり）
                  const card = button.closest('.card');
                  
                  // 同じカード内の他のドロップダウン（授業詳細 <=> 持ってくるもの）
                  const otherWrapperSelector = isDetailButton ? '.item-dropdown-wrapper' : '.detail-dropdown-wrapper';
                  const otherWrapper = card.querySelector(otherWrapperSelector);
  
                  // 他のドロップダウンが開いている場合は、それを閉じる
                  if (otherWrapper && otherWrapper.classList.contains('active')) {
                      closeDropdown(otherWrapper);
                  }
              } else if (wrapper.classList.contains('course-select-wrapper')) {
                  // コース選択ドロップダウン（他のドロップダウンをすべて閉じる）
                  document.querySelectorAll('.dropdown-wrapper').forEach(w => {
                      if (w !== wrapper) {
                          closeDropdown(w);
                      }
                  });
              }
  
              if (isExpanded) {
                  // 現在開いている場合は閉じる
                  closeDropdown(wrapper);
              } else {
                  // 現在閉じている場合は開く
                  wrapper.classList.add('active');
                  button.setAttribute('aria-expanded', 'true');
                  contentElement.classList.add('show');
              }
          }
  
          // 全てのドロップダウントグルボタンにイベントリスナーを設定
          toggleButtons.forEach(button => {
              button.addEventListener('click', function() {
                  toggleDropdown(this);
              });
          });
      });
  </script>
</body>
</html>