<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }
        
        .ball {
            width: 12px;
            height: 12px;
            background: radial-gradient(circle at 30% 30%, #ff6b6b, #e74c3c);
            border-radius: 50%;
            position: absolute;
            z-index: 2;
            box-shadow: 0 2px 6px rgba(0,0,0,0.4);
            transition: transform 0.1s ease;
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
                <div class="stat-value credits-value" id="credits-value">1000</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">スコア</div>
                <div class="stat-value score-value" id="score-value">0</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">玉数</div>
                <div class="stat-value" id="balls-count">0</div>
            </div>
        </div>
        
        <div id="pachinko-board">
            <!-- ピンは動的に生成 -->
            <!-- ポケット -->
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
                       min="20" max="100" value="50" step="5">
            </div>
            
            <button id="launch-button">🚀 発射 (10クレジット)</button>
        </div>
        
        <div id="message" class="message" style="display: none;"></div>
    </div>

    <script>
        class PachinkoGame {
            constructor() {
                this.credits = 1000;
                this.score = 0;
                this.ballsInPlay = 0;
                this.launchPower = 50;
                this.isLaunching = false;
                this.balls = [];
                this.pins = [];
                this.animationId = null;
                
                this.initializeGame();
                this.setupEventListeners();
                this.generatePins();
                this.startGameLoop();
            }
            
            initializeGame() {
                this.updateDisplay();
            }
            
            setupEventListeners() {
                const launchButton = document.getElementById('launch-button');
                const powerSlider = document.getElementById('power-slider');
                const powerValue = document.getElementById('power-value');
                
                launchButton.addEventListener('click', () => this.launchBall());
                
                powerSlider.addEventListener('input', (e) => {
                    this.launchPower = parseInt(e.target.value);
                    powerValue.textContent = this.launchPower;
                });
                
                // キーボードサポート
                document.addEventListener('keydown', (e) => {
                    if (e.code === 'Space') {
                        e.preventDefault();
                        this.launchBall();
                    }
                });
            }
            
            generatePins() {
                const board = document.getElementById('pachinko-board');
                const boardRect = board.getBoundingClientRect();
                const boardWidth = 320;
                const boardHeight = 400;
                
                // 構造化されたピン配置
                for (let row = 0; row < 12; row++) {
                    for (let col = 0; col < 8; col++) {
                        const x = 20 + col * 35 + (row % 2 === 0 ? 0 : 17);
                        const y = 40 + row * 25;
                        
                        if (x < boardWidth - 20 && y < boardHeight - 60) {
                            const pin = document.createElement('div');
                            pin.className = 'pin';
                            pin.style.left = x + 'px';
                            pin.style.top = y + 'px';
                            board.appendChild(pin);
                            
                            this.pins.push({ x: x + 4, y: y + 4, radius: 4 });
                        }
                    }
                }
            }
            
            launchBall() {
                if (this.credits < 10 || this.isLaunching) {
                    if (this.credits < 10) {
                        this.showMessage('クレジットが不足しています！', 'error');
                    }
                    return;
                }
                
                this.credits -= 10;
                this.isLaunching = true;
                this.ballsInPlay++;
                
                const board = document.getElementById('pachinko-board');
                const ball = document.createElement('div');
                ball.className = 'ball';
                
                const startX = 150 + (Math.random() - 0.5) * 40;
                const startY = 10;
                
                ball.style.left = startX + 'px';
                ball.style.top = startY + 'px';
                board.appendChild(ball);
                
                const ballData = {
                    element: ball,
                    x: startX,
                    y: startY,
                    dx: (Math.random() - 0.5) * (this.launchPower / 30),
                    dy: 1 + this.launchPower / 50,
                    radius: 6
                };
                
                this.balls.push(ballData);
                this.updateDisplay();
                
                setTimeout(() => {
                    this.isLaunching = false;
                }, 500);
            }
            
            updateBall(ball) {
                // 物理演算
                ball.x += ball.dx;
                ball.y += ball.dy;
                ball.dy += 0.15; // 重力
                
                // 壁との衝突
                if (ball.x <= ball.radius) {
                    ball.x = ball.radius;
                    ball.dx = Math.abs(ball.dx) * 0.8;
                } else if (ball.x >= 320 - ball.radius) {
                    ball.x = 320 - ball.radius;
                    ball.dx = -Math.abs(ball.dx) * 0.8;
                }
                
                // ピンとの衝突
                this.pins.forEach(pin => {
                    const dx = ball.x - pin.x;
                    const dy = ball.y - pin.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    const minDistance = ball.radius + pin.radius;
                    
                    if (distance < minDistance && distance > 0) {
                        const normalX = dx / distance;
                        const normalY = dy / distance;
                        
                        ball.x = pin.x + normalX * minDistance;
                        ball.y = pin.y + normalY * minDistance;
                        
                        const dotProduct = ball.dx * normalX + ball.dy * normalY;
                        ball.dx = ball.dx - 1.5 * dotProduct * normalX;
                        ball.dy = ball.dy - 1.5 * dotProduct * normalY;
                        
                        ball.dx *= 0.8;
                        ball.dy *= 0.8;
                    }
                });
                
                // 速度制限
                const maxSpeed = 12;
                if (Math.abs(ball.dx) > maxSpeed) ball.dx = Math.sign(ball.dx) * maxSpeed;
                if (Math.abs(ball.dy) > maxSpeed) ball.dy = Math.sign(ball.dy) * maxSpeed;
                
                // 位置更新
                ball.element.style.left = ball.x + 'px';
                ball.element.style.top = ball.y + 'px';
                
                // 底に到達チェック
                if (ball.y >= 380) {
                    this.handleBallLanding(ball);
                    return false;
                }
                
                return true;
            }
            
            handleBallLanding(ball) {
                const pocketIndex = Math.floor(ball.x / 64);
                const pocketValues = [50, 100, 500, 100, 50];
                const earnedPoints = pocketValues[pocketIndex] || 0;
                
                this.credits += earnedPoints;
                this.score += earnedPoints;
                this.ballsInPlay--;
                
                // ジャックポット判定
                if (pocketIndex === 2 && Math.random() < 0.15) {
                    const jackpotBonus = 1000;
                    this.credits += jackpotBonus;
                    this.score += jackpotBonus;
                    this.showMessage(`🎉 JACKPOT! +${earnedPoints + jackpotBonus}ポイント！`, 'jackpot');
                } else if (earnedPoints > 0) {
                    this.showMessage(`+${earnedPoints}ポイント獲得！`, 'success');
                }
                
                ball.element.remove();
                this.balls = this.balls.filter(b => b !== ball);
                this.updateDisplay();
            }
            
            startGameLoop() {
                const gameLoop = () => {
                    this.balls = this.balls.filter(ball => this.updateBall(ball));
                    this.animationId = requestAnimationFrame(gameLoop);
                };
                gameLoop();
            }
            
            updateDisplay() {
                document.getElementById('credits-value').textContent = this.credits;
                document.getElementById('score-value').textContent = this.score;
                document.getElementById('balls-count').textContent = this.ballsInPlay;
                
                const launchButton = document.getElementById('launch-button');
                launchButton.disabled = this.credits < 10 || this.isLaunching;
            }
            
            showMessage(text, type = 'success') {
                const messageEl = document.getElementById('message');
                messageEl.textContent = text;
                messageEl.className = `message ${type}`;
                messageEl.style.display = 'block';
                
                setTimeout(() => {
                    messageEl.style.display = 'none';
                }, 3000);
            }
        }
        
        // ゲーム開始
        document.addEventListener('DOMContentLoaded', () => {
            new PachinkoGame();
        });
    </script>
</body>
</html>
