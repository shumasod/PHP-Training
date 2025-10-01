<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="シンプルなパチンコゲーム - JavaScript製">
    <title>シンプルパチンコゲーム</title>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        #game-container {
            text-align: center;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .credits-value {
            color: #28a745;
        }
        
        .score-value {
            color: #dc3545;
        }
        
        #pachinko-board {
            width: 100%;
            max-width: 320px;
            height: 400px;
            background: linear-gradient(to bottom, #3498db, #2980b9);
            margin: 20px auto;
            position: relative;
            border-radius: 15px;
            border: 4px solid #34495e;
            overflow: hidden;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.2);
        }
        
        .pin {
            width: 8px;
            height: 8px;
            background: radial-gradient(circle at 30% 30%, #ecf0f1, #bdc3c7);
            border-radius: 50%;
            position: absolute;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            z-index: 1;
            will-change: transform;
        }
        
        .ball {
            width: 12px;
            height: 12px;
            background: radial-gradient(circle at 30% 30%, #ff6b6b, #e74c3c);
            border-radius: 50%;
            position: absolute;
            z-index: 2;
            box-shadow: 0 2px 6px rgba(0,0,0,0.4);
            will-change: transform;
        }
        
        .pocket {
            position: absolute;
            bottom: 0;
            width: 60px;
            height: 30px;
            background: linear-gradient(to bottom, #f39c12, #e67e22);
            border: 2px solid #d35400;
            border-radius: 10px 10px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        .pocket.jackpot {
            background: linear-gradient(to bottom, #f1c40f, #f39c12);
            animation: jackpot-glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes jackpot-glow {
            from { box-shadow: 0 0 10px rgba(241, 196, 15, 0.5); }
            to { box-shadow: 0 0 20px rgba(241, 196, 15, 0.9); }
        }
        
        .controls {
            margin: 20px 0;
        }
        
        .power-control {
            margin: 15px 0;
        }
        
        .power-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .power-slider {
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: #ddd;
            outline: none;
            appearance: none;
        }
        
        .power-slider::-webkit-slider-thumb {
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #3498db;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        
        .power-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #3498db;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        
        #launch-button {
            padding: 15px 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            background: linear-gradient(145deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 50px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            width: 100%;
            max-width: 250px;
        }
        
        #launch-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        }
        
        #launch-button:active:not(:disabled) {
            transform: translateY(0);
        }
        
        #launch-button:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        
        .message {
            margin: 15px 0;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            min-height: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.jackpot {
            background: linear-gradient(45deg, #f1c40f, #f39c12);
            color: white;
            animation: jackpot-message 1s ease-in-out;
        }
        
        @keyframes jackpot-message {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .game-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: center;
        }

        .action-button {
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            transition: all 0.2s ease;
        }

        .action-button:hover {
            background: #5a6268;
        }

        .high-score {
            margin-top: 10px;
            padding: 10px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            font-size: 14px;
            color: #856404;
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            #game-container {
                padding: 20px;
            }
            
            #pachinko-board {
                height: 300px;
            }
            
            .stats {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div id="game-container">
        <h1>🎌 パチンコゲーム</h1>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-label">クレジット</div>
                <div class="stat-value credits-value" id="credits-value" aria-live="polite">1000</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">スコア</div>
                <div class="stat-value score-value" id="score-value" aria-live="polite">0</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">玉数</div>
                <div class="stat-value" id="balls-count" aria-live="polite">0</div>
            </div>
        </div>
        
        <div id="pachinko-board" role="img" aria-label="パチンコ盤面">
            <div class="pocket" style="left: 10px;" data-value="50">50</div>
            <div class="pocket" style="left: 75px;" data-value="100">100</div>
            <div class="pocket jackpot" style="left: 140px;" data-value="500">500</div>
            <div class="pocket" style="left: 205px;" data-value="100">100</div>
            <div class="pocket" style="left: 270px;" data-value="50">50</div>
        </div>
        
        <div class="controls">
            <div class="power-control">
                <label class="power-label" for="power-slider">
                    発射力: <span id="power-value">50</span>%
                </label>
                <input type="range" id="power-slider" class="power-slider" 
                       min="20" max="100" value="50" step="5"
                       aria-label="発射力を調整">
            </div>
            
            <button id="launch-button" aria-label="玉を発射する">
                🚀 発射 (10クレジット)
            </button>

            <div class="game-actions">
                <button class="action-button" id="reset-button" aria-label="ゲームをリセット">
                    🔄 リセット
                </button>
                <button class="action-button" id="add-credits-button" aria-label="クレジット追加">
                    💰 +1000
                </button>
            </div>
        </div>
        
        <div id="message" class="message" role="alert" aria-live="assertive" style="display: none;"></div>
        
        <div class="high-score" id="high-score-display">
            最高スコア: <span id="high-score-value">0</span>
        </div>
    </div>

    <script>
        'use strict';

        // ゲーム定数の定義
        const GAME_CONFIG = {
            INITIAL_CREDITS: 1000,
            LAUNCH_COST: 10,
            BOARD_WIDTH: 320,
            BOARD_HEIGHT: 400,
            BALL_RADIUS: 6,
            PIN_RADIUS: 4,
            GRAVITY: 0.15,
            BOUNCE_DAMPING: 0.8,
            COLLISION_RESPONSE: 1.5,
            MAX_SPEED: 12,
            JACKPOT_CHANCE: 0.15,
            JACKPOT_BONUS: 1000,
            LANDING_Y: 380,
            LAUNCH_COOLDOWN: 500,
            MESSAGE_DURATION: 3000,
            POCKET_VALUES: [50, 100, 500, 100, 50],
            STORAGE_KEY: 'pachinko_high_score'
        };

        /**
         * パチンコゲームクラス
         */
        class PachinkoGame {
            constructor() {
                this.credits = GAME_CONFIG.INITIAL_CREDITS;
                this.score = 0;
                this.ballsInPlay = 0;
                this.launchPower = 50;
                this.isLaunching = false;
                this.balls = [];
                this.pins = [];
                this.animationId = null;
                this.highScore = this.loadHighScore();
                
                this.board = document.getElementById('pachinko-board');
                
                this.initialize();
            }
            
            /**
             * ゲームの初期化
             */
            initialize() {
                try {
                    this.setupEventListeners();
                    this.generatePins();
                    this.updateDisplay();
                    this.startGameLoop();
                    console.log('ゲームを初期化しました');
                } catch (error) {
                    console.error('初期化エラー:', error);
                    this.showMessage('ゲームの初期化に失敗しました', 'error');
                }
            }
            
            /**
             * イベントリスナーの設定
             */
            setupEventListeners() {
                const launchButton = document.getElementById('launch-button');
                const powerSlider = document.getElementById('power-slider');
                const powerValue = document.getElementById('power-value');
                const resetButton = document.getElementById('reset-button');
                const addCreditsButton = document.getElementById('add-credits-button');
                
                if (!launchButton || !powerSlider || !powerValue) {
                    throw new Error('必要なDOM要素が見つかりません');
                }
                
                launchButton.addEventListener('click', () => this.launchBall());
                resetButton.addEventListener('click', () => this.resetGame());
                addCreditsButton.addEventListener('click', () => this.addCredits());
                
                powerSlider.addEventListener('input', (e) => {
                    this.launchPower = parseInt(e.target.value, 10);
                    powerValue.textContent = this.launchPower;
                });
                
                // キーボードサポート
                document.addEventListener('keydown', (e) => {
                    if (e.code === 'Space' && !e.repeat) {
                        e.preventDefault();
                        this.launchBall();
                    }
                });
            }
            
            /**
             * ピンの生成（構造化配置）
             */
            generatePins() {
                const fragment = document.createDocumentFragment();
                
                for (let row = 0; row < 12; row++) {
                    for (let col = 0; col < 8; col++) {
                        const offsetX = row % 2 === 0 ? 0 : 17;
                        const x = 20 + col * 35 + offsetX;
                        const y = 40 + row * 25;
                        
                        if (x < GAME_CONFIG.BOARD_WIDTH - 20 && 
                            y < GAME_CONFIG.BOARD_HEIGHT - 60) {
                            
                            const pin = this.createPin(x, y);
                            fragment.appendChild(pin);
                            
                            this.pins.push({ 
                                x: x + GAME_CONFIG.PIN_RADIUS, 
                                y: y + GAME_CONFIG.PIN_RADIUS, 
                                radius: GAME_CONFIG.PIN_RADIUS 
                            });
                        }
                    }
                }
                
                this.board.appendChild(fragment);
            }
            
            /**
             * ピン要素の作成
             */
            createPin(x, y) {
                const pin = document.createElement('div');
                pin.className = 'pin';
                pin.style.left = `${x}px`;
                pin.style.top = `${y}px`;
                return pin;
            }
            
            /**
             * 玉の発射
             */
            launchBall() {
                if (!this.canLaunch()) {
                    return;
                }
                
                this.credits -= GAME_CONFIG.LAUNCH_COST;
                this.isLaunching = true;
                this.ballsInPlay++;
                
                const ball = this.createBall();
                this.balls.push(ball);
                this.updateDisplay();
                
                setTimeout(() => {
                    this.isLaunching = false;
                }, GAME_CONFIG.LAUNCH_COOLDOWN);
            }
            
            /**
             * 発射可能かチェック
             */
            canLaunch() {
                if (this.credits < GAME_CONFIG.LAUNCH_COST) {
                    this.showMessage('クレジットが不足しています！', 'error');
                    return false;
                }
                
                if (this.isLaunching) {
                    return false;
                }
                
                return true;
            }
            
            /**
             * 玉の作成
             */
            createBall() {
                const element = document.createElement('div');
                element.className = 'ball';
                
                const startX = 150 + (Math.random() - 0.5) * 40;
                const startY = 10;
                
                element.style.left = `${startX}px`;
                element.style.top = `${startY}px`;
                this.board.appendChild(element);
                
                return {
                    element,
                    x: startX,
                    y: startY,
                    dx: (Math.random() - 0.5) * (this.launchPower / 30),
                    dy: 1 + this.launchPower / 50,
                    radius: GAME_CONFIG.BALL_RADIUS
                };
            }
            
            /**
             * 玉の物理演算と更新
             */
            updateBall(ball) {
                // 速度の更新
                ball.x += ball.dx;
                ball.y += ball.dy;
                ball.dy += GAME_CONFIG.GRAVITY;
                
                // 壁との衝突判定
                this.handleWallCollision(ball);
                
                // ピンとの衝突判定
                this.handlePinCollisions(ball);
                
                // 速度制限
                this.limitSpeed(ball);
                
                // 位置の更新
                ball.element.style.transform = `translate(${ball.x - ball.radius}px, ${ball.y - ball.radius}px)`;
                
                // 底に到達チェック
                if (ball.y >= GAME_CONFIG.LANDING_Y) {
                    this.handleBallLanding(ball);
                    return false;
                }
                
                return true;
            }
            
            /**
             * 壁との衝突処理
             */
            handleWallCollision(ball) {
                if (ball.x <= ball.radius) {
                    ball.x = ball.radius;
                    ball.dx = Math.abs(ball.dx) * GAME_CONFIG.BOUNCE_DAMPING;
                } else if (ball.x >= GAME_CONFIG.BOARD_WIDTH - ball.radius) {
                    ball.x = GAME_CONFIG.BOARD_WIDTH - ball.radius;
                    ball.dx = -Math.abs(ball.dx) * GAME_CONFIG.BOUNCE_DAMPING;
                }
            }
            
            /**
             * ピンとの衝突処理
             */
            handlePinCollisions(ball) {
                for (const pin of this.pins) {
                    const dx = ball.x - pin.x;
                    const dy = ball.y - pin.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    const minDistance = ball.radius + pin.radius;
                    
                    if (distance < minDistance && distance > 0) {
                        const normalX = dx / distance;
                        const normalY = dy / distance;
                        
                        // 位置補正
                        ball.x = pin.x + normalX * minDistance;
                        ball.y = pin.y + normalY * minDistance;
                        
                        // 速度の反射
                        const dotProduct = ball.dx * normalX + ball.dy * normalY;
                        ball.dx = (ball.dx - GAME_CONFIG.COLLISION_RESPONSE * dotProduct * normalX) * GAME_CONFIG.BOUNCE_DAMPING;
                        ball.dy = (ball.dy - GAME_CONFIG.COLLISION_RESPONSE * dotProduct * normalY) * GAME_CONFIG.BOUNCE_DAMPING;
                    }
                }
            }
            
            /**
             * 速度制限
             */
            limitSpeed(ball) {
                if (Math.abs(ball.dx) > GAME_CONFIG.MAX_SPEED) {
                    ball.dx = Math.sign(ball.dx) * GAME_CONFIG.MAX_SPEED;
                }
                if (Math.abs(ball.dy) > GAME_CONFIG.MAX_SPEED) {
                    ball.dy = Math.sign(ball.dy) * GAME_CONFIG.MAX_SPEED;
                }
            }
            
            /**
             * 玉が底に到達した時の処理
             */
            handleBallLanding(ball) {
                const pocketIndex = Math.floor(ball.x / 64);
                const earnedPoints = GAME_CONFIG.POCKET_VALUES[pocketIndex] || 0;
                
                this.credits += earnedPoints;
                this.score += earnedPoints;
                this.ballsInPlay--;
                
                // ジャックポット判定
                if (pocketIndex === 2 && Math.random() < GAME_CONFIG.JACKPOT_CHANCE) {
                    this.handleJackpot(earnedPoints);
                } else if (earnedPoints > 0) {
                    this.showMessage(`+${earnedPoints}ポイント獲得！`, 'success');
                }
                
                // ハイスコア更新
                if (this.score > this.highScore) {
                    this.highScore = this.score;
                    this.saveHighScore();
                }
                
                ball.element.remove();
                this.balls = this.balls.filter(b => b !== ball);
                this.updateDisplay();
            }
            
            /**
             * ジャックポット処理
             */
            handleJackpot(earnedPoints) {
                this.credits += GAME_CONFIG.JACKPOT_BONUS;
                this.score += GAME_CONFIG.JACKPOT_BONUS;
                const total = earnedPoints + GAME_CONFIG.JACKPOT_BONUS;
                this.showMessage(`🎉 JACKPOT! +${total}ポイント！`, 'jackpot');
            }
            
            /**
             * ゲームループの開始
             */
            startGameLoop() {
                const gameLoop = () => {
                    this.balls = this.balls.filter(ball => this.updateBall(ball));
                    this.animationId = requestAnimationFrame(gameLoop);
                };
                gameLoop();
            }
            
            /**
             * 表示の更新
             */
            updateDisplay() {
                const creditsEl = document.getElementById('credits-value');
                const scoreEl = document.getElementById('score-value');
                const ballsEl = document.getElementById('balls-count');
                const highScoreEl = document.getElementById('high-score-value');
                const launchButton = document.getElementById('launch-button');
                
                if (creditsEl) creditsEl.textContent = this.credits;
                if (scoreEl) scoreEl.textContent = this.score;
                if (ballsEl) ballsEl.textContent = this.ballsInPlay;
                if (highScoreEl) highScoreEl.textContent = this.highScore;
                
                if (launchButton) {
                    launchButton.disabled = this.credits < GAME_CONFIG.LAUNCH_COST || this.isLaunching;
                }
            }
            
            /**
             * メッセージ表示
             */
            showMessage(text, type = 'success') {
                const messageEl = document.getElementById('message');
                if (!messageEl) return;
                
                messageEl.textContent = text;
                messageEl.className = `message ${type}`;
                messageEl.style.display = 'block';
                
                setTimeout(() => {
                    messageEl.style.display = 'none';
                }, GAME_CONFIG.MESSAGE_DURATION);
            }
            
            /**
             * ゲームのリセット
             */
            resetGame() {
                // 全ての玉を削除
                this.balls.forEach(ball => ball.element.remove());
                this.balls = [];
                
                // ステータスをリセット
                this.credits = GAME_CONFIG.INITIAL_CREDITS;
                this.score = 0;
                this.ballsInPlay = 0;
                this.isLaunching = false;
                
                this.updateDisplay();
                this.showMessage('ゲームをリセットしました', 'success');
            }
            
            /**
             * クレジット追加（デバッグ用）
             */
            addCredits() {
                this.credits += 1000;
                this.updateDisplay();
                this.showMessage('+1000クレジット追加', 'success');
            }
            
            /**
             * ハイスコアの読み込み
             */
            loadHighScore() {
                try {
                    const saved = localStorage.getItem(GAME_CONFIG.STORAGE_KEY);
                    return saved ? parseInt(saved, 10) : 0;
                } catch (error) {
                    console.warn('ハイスコアの読み込みに失敗:', error);
                    return 0;
                }
            }
            
            /**
             * ハイスコアの保存
             */
            saveHighScore() {
                try {
                    localStorage.setItem(GAME_CONFIG.STORAGE_KEY, this.highScore.toString());
                } catch (error) {
                    console.warn('ハイスコアの保存に失敗:', error);
                }
            }
            
            /**
             * クリーンアップ
             */
            destroy() {
                if (this.animationId) {
                    cancelAnimationFrame(this.animationId);
                }
                this.balls.forEach(ball => ball.element.remove());
            }
        }
        
        // ゲームの開始
        let gameInstance = null;
        
        document.addEventListener('DOMContentLoaded', () => {
            try {
                gameInstance = new PachinkoGame();
            } catch (error) {
                console.error('ゲームの起動に失敗:', error);
                alert('ゲームの起動に失敗しました。ページを再読み込みしてください。');
            }
        });
        
        // ページ離脱時のクリーンアップ
        window.addEventListener('beforeunload', () => {
            if (gameInstance) {
                gameInstance.destroy();
            }
        });
    </script>
</body>
</html>
