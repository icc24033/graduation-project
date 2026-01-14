document.addEventListener('DOMContentLoaded', function() {
    console.log("login.js loaded: Script started"); // 読み込み確認用ログ

    // --- 既存のコンテンツフェードイン処理 ---
    const fadeElements = document.querySelectorAll('.fade-in');
    const showElements = () => {
        fadeElements.forEach((element, index) => {
            setTimeout(() => {
                element.classList.add('active');
            }, index * 200 + 300); 
        });
    };
    showElements();


    // --- 背景動画トランジション処理 ---
    const video = document.querySelector('.bg-video');
    const overlay = document.getElementById('transition-overlay');
    
    // 動画リスト（現在は1つ）
    const videoSources = [
        "video/login.mp4" 
    ];
    let currentVideoIndex = 0;

    if(video) {
        console.log("Video element found"); // ビデオタグ検出確認

        // HTML属性だけでなくプロパティとしてもループを無効化
        video.loop = false;
        video.removeAttribute('loop');

        // 動画が終了した時のイベントリスナー
        video.addEventListener('ended', () => {
            console.log("Event: Video ended"); // イベント発火確認
            runTransitionEffect();
        });

        // 万が一、JS読み込み時にすでに動画が終わっていた場合の保険
        if (video.ended) {
            console.log("Video was already ended on load");
            runTransitionEffect();
        }

    } else {
        console.error("Video element (.bg-video) NOT found");
    }

    function runTransitionEffect() {
        console.log("Starting transition effect...");
        overlay.innerHTML = ''; // リセット
        
        // タイルのサイズ設定
        const tileSize = 50; 
        const cols = Math.ceil(window.innerWidth / tileSize);
        const rows = Math.ceil(window.innerHeight / tileSize);

        // CSS Grid設定
        overlay.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;

        // タイル生成と遅延計算
        const tiles = [];
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                const tile = document.createElement('div');
                tile.classList.add('transition-tile');

                // 右下から左上への遅延計算
                const delayIndex = (rows - r) + (cols - c);
                tile.style.animationDelay = `${delayIndex * 0.04}s`;

                overlay.appendChild(tile);
                tiles.push(tile);
            }
        }

        // 1. タイルを出現させる
        // requestAnimationFrameではなくsetTimeoutを使用して描画を確実に待つ
        setTimeout(() => {
            tiles.forEach(t => t.classList.add('active'));
        }, 50);

        // 画面が完全に隠れるまでの時間
        const maxDelay = (rows + cols) * 0.04;
        const animationDuration = 0.6;
        const totalDuration = (maxDelay + animationDuration) * 1000;

        // 2. 画面が隠れたら動画を切り替えて、タイルを消す
        setTimeout(() => {
            console.log("Switching video source...");
            
            // 動画切り替え
            currentVideoIndex = (currentVideoIndex + 1) % videoSources.length;
            video.src = videoSources[currentVideoIndex];
            
            // iPhone/Safari対策：playsinline属性を再確認して再生
            video.playsInline = true;
            video.play().catch(e => console.error("Play failed:", e));

            // タイルのアニメーションを「退場」に切り替え
            tiles.forEach(t => {
                t.classList.remove('active');
                t.classList.add('leave');
            });

            // 3. 掃除
            setTimeout(() => {
                overlay.innerHTML = '';
            }, totalDuration); 

        }, totalDuration); 
    }
});