import React, { useRef, useEffect, useState, useCallback, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Slider } from '@/components/ui/slider';

const CANVAS_WIDTH = 400;
const CANVAS_HEIGHT = 600;
const BALL_RADIUS = 5;
const PIN_RADIUS = 3;
const GRAVITY = 0.2;
const FRICTION = 0.8;
const LAUNCH_COST = 10;
const BOUNCINESS = 0.7;

const POCKETS = [
{ multiplier: 50, width: 80, color: '#3498db' },
{ multiplier: 100, width: 80, color: '#2ecc71' },
{ multiplier: 500, width: 80, color: '#f39c12' },
{ multiplier: 100, width: 80, color: '#2ecc71' },
{ multiplier: 50, width: 80, color: '#3498db' }
];

class Ball {
constructor(x, y, dx, dy) {
this.x = x;
this.y = y;
this.dx = dx;
this.dy = dy;
this.radius = BALL_RADIUS;
this.id = Math.random().toString(36).substring(7);
this.landed = false;
this.score = 0;
}

update() {
if (this.landed) return;

this.x += this.dx;
this.y += this.dy;
this.dy += GRAVITY;

// 壁との衝突
if (this.x - this.radius <= 0) {
  this.x = this.radius;
  this.dx = Math.abs(this.dx) * FRICTION;
} else if (this.x + this.radius >= CANVAS_WIDTH) {
  this.x = CANVAS_WIDTH - this.radius;
  this.dx = -Math.abs(this.dx) * FRICTION;
}

// 速度制限
const maxSpeed = 15;
if (Math.abs(this.dx) > maxSpeed) this.dx = Math.sign(this.dx) * maxSpeed;
if (Math.abs(this.dy) > maxSpeed) this.dy = Math.sign(this.dy) * maxSpeed;

// 摩擦
this.dx *= 0.998;

}

draw(ctx) {
if (this.landed) return;

ctx.beginPath();
ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
ctx.fillStyle = '#e74c3c';
ctx.fill();
ctx.strokeStyle = '#c0392b';
ctx.lineWidth = 1;
ctx.stroke();
ctx.closePath();

}

isOutOfBounds() {
return this.y > CANVAS_HEIGHT;
}

checkPocketLanding() {
if (this.y >= CANVAS_HEIGHT - 30 && !this.landed) {
const pocketIndex = Math.floor(this.x / 80);
if (pocketIndex >= 0 && pocketIndex < POCKETS.length) {
this.landed = true;
this.score = POCKETS[pocketIndex].multiplier;
return this.score;
}
}
return 0;
}

collideWithPin(pin) {
const dx = this.x - pin.x;
const dy = this.y - pin.y;
const distance = Math.sqrt(dx * dx + dy * dy);

if (distance < this.radius + pin.radius) {
  // 衝突応答
  const angle = Math.atan2(dy, dx);
  const targetX = pin.x + Math.cos(angle) * (this.radius + pin.radius);
  const targetY = pin.y + Math.sin(angle) * (this.radius + pin.radius);
  
  this.x = targetX;
  this.y = targetY;
  
  // 速度の変更
  const force = BOUNCINESS;
  this.dx += Math.cos(angle) * force;
  this.dy += Math.sin(angle) * force;
  
  // ランダムな要素を追加
  this.dx += (Math.random() - 0.5) * 2;
  this.dy += (Math.random() - 0.5) * 1;
}

}
}

class Pin {
constructor(x, y) {
this.x = x;
this.y = y;
this.radius = PIN_RADIUS;
}

draw(ctx) {
ctx.beginPath();
ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
ctx.fillStyle = '#95a5a6';
ctx.fill();
ctx.strokeStyle = '#7f8c8d';
ctx.lineWidth = 1;
ctx.stroke();
ctx.closePath();
}
}

const PurePachinko2D = () => {
const canvasRef = useRef(null);
const animationRef = useRef(null);
const ballsRef = useRef([]);
const pinsRef = useRef([]);
const [credits, setCredits] = useState(1000);
const [ballsInPlay, setBallsInPlay] = useState(0);
const [launchPower, setLaunchPower] = useState([50]);
const [isLaunching, setIsLaunching] = useState(false);
const [lastScore, setLastScore] = useState(0);
const [totalScore, setTotalScore] = useState(0);

// ピンの初期化
useEffect(() => {
const pins = [];
// ピンをジグザグパターンで配置
for (let row = 0; row < 12; row++) {
const yPos = 100 + row * 40;
const pinsInRow = row % 2 === 0 ? 8 : 7;
const startX = row % 2 === 0 ? 50 : 75;

  for (let col = 0; col < pinsInRow; col++) {
    const xPos = startX + col * 50;
    if (xPos > 0 && xPos < CANVAS_WIDTH) {
      pins.push(new Pin(xPos, yPos));
    }
  }
}
pinsRef.current = pins;

}, []);

// 描画関数
const draw = useCallback((ctx) => {
// 背景をクリア
ctx.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

// 背景グラデーション
const gradient = ctx.createLinearGradient(0, 0, 0, CANVAS_HEIGHT);
gradient.addColorStop(0, '#1a1a2e');
gradient.addColorStop(1, '#16213e');
ctx.fillStyle = gradient;
ctx.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

// ポケットを描画
const pocketY = CANVAS_HEIGHT - 30;
POCKETS.forEach((pocket, index) => {
  const x = index * 80;
  const width = pocket.width;
  
  // ポケットの背景
  ctx.fillStyle = pocket.color;
  ctx.fillRect(x, pocketY, width, 30);
  
  // ポケットの境界線
  ctx.strokeStyle = '#2c3e50';
  ctx.lineWidth = 2;
  ctx.strokeRect(x, pocketY, width, 30);
  
  // 倍率の表示
  ctx.fillStyle = '#ffffff';
  ctx.font = '12px Arial';
  ctx.textAlign = 'center';
  ctx.fillText(`×${pocket.multiplier}`, x + width / 2, pocketY + 20);
});

// ピンを描画
pinsRef.current.forEach(pin => pin.draw(ctx));

// ボールを描画
ballsRef.current.forEach(ball => ball.draw(ctx));

// 発射器を描画
ctx.fillStyle = '#34495e';
ctx.fillRect(CANVAS_WIDTH / 2 - 10, 0, 20, 50);

// 発射器の先端
ctx.beginPath();
ctx.arc(CANVAS_WIDTH / 2, 50, 8, 0, Math.PI * 2);
ctx.fillStyle = '#e74c3c';
ctx.fill();
ctx.closePath();

}, []);

// ゲームループ
const gameLoop = useCallback(() => {
const canvas = canvasRef.current;
if (!canvas) return;

const ctx = canvas.getContext('2d');

// ボールを更新
ballsRef.current = ballsRef.current.filter(ball => {
  ball.update();
  
  // ピンとの衝突チェック
  pinsRef.current.forEach(pin => {
    ball.collideWithPin(pin);
  });
  
  // ポケットチェック
  const score = ball.checkPocketLanding();
  if (score > 0) {
    const newCredits = credits + score;
    setCredits(newCredits);
    setLastScore(score);
    setTotalScore(prev => prev + score);
    setBallsInPlay(prev => prev - 1);
    return false; // ボールを削除
  }
  
  // 画面外チェック
  if (ball.isOutOfBounds()) {
    setBallsInPlay(prev => prev - 1);
    return false; // ボールを削除
  }
  
  return true; // ボールを保持
});

draw(ctx);
animationRef.current = requestAnimationFrame(gameLoop);

}, [credits, draw]);

// アニメーション開始
useEffect(() => {
const canvas = canvasRef.current;
if (canvas) {
const ctx = canvas.getContext('2d');
draw(ctx);
animationRef.current = requestAnimationFrame(gameLoop);
}

return () => {
  if (animationRef.current) {
    cancelAnimationFrame(animationRef.current);
  }
};

}, [gameLoop, draw]);

const launchBall = useCallback(() => {
if (credits >= LAUNCH_COST && !isLaunching) {
const newCredits = credits - LAUNCH_COST;
setCredits(newCredits);

  setIsLaunching(true);
  setBallsInPlay(prev => prev + 1);
  setLastScore(0);

  const newBall = new Ball(
    CANVAS_WIDTH / 2 + (Math.random() - 0.5) * 20,
    50,
    (Math.random() - 0.5) * (launchPower[0] / 25),
    2 + launchPower[0] / 25
  );

  ballsRef.current.push(newBall);

  setTimeout(() => setIsLaunching(false), 500);
}

}, [credits, isLaunching, launchPower]);

const resetGame = () => {
ballsRef.current = [];
setCredits(1000);
setBallsInPlay(0);
setLastScore(0);
setTotalScore(0);
};

return (
<Card className="w-[450px] mx-auto mt-8">
<CardHeader>
<CardTitle className="text-center">🎌 パチンコゲーム 🎌</CardTitle>
</CardHeader>
<CardContent className="space-y-4">
<div className="text-center space-y-2">
<div className="flex justify-between items-center">
<span className="text-lg">クレジット:</span>
<span className="text-2xl font-bold text-green-600">{credits}</span>
</div>
<div className="flex justify-between items-center">
<span className="text-sm">プレイ中:</span>
<span className="text-sm">{ballsInPlay} 球</span>
</div>
<div className="flex justify-between items-center">
<span className="text-sm">総獲得:</span>
<span className="text-sm font-bold text-blue-600">{totalScore}</span>
</div>
{lastScore > 0 && (
<div className="text-center">
<span className="text-sm bg-yellow-100 text-yellow-800 px-2 py-1 rounded animate-pulse">
+{lastScore} 獲得！
</span>
</div>
)}
</div>

    <canvas 
      ref={canvasRef} 
      width={CANVAS_WIDTH} 
      height={CANVAS_HEIGHT} 
      className="border border-gray-300 rounded shadow-md bg-gray-900"
    />
    
    <div className="space-y-3">
      <div>
        <label className="text-sm font-medium">発射パワー: {launchPower[0]}%</label>
        <Slider
          value={launchPower}
          onValueChange={setLaunchPower}
          max={100}
          min={10}
          step={5}
          className="mt-2"
        />
      </div>
      
      <div className="flex gap-2">
        <Button 
          onClick={launchBall} 
          disabled={isLaunching || credits < LAUNCH_COST}
          className="flex-1"
          size="lg"
        >
          {isLaunching ? '発射中...' : `🚀 発射 (${LAUNCH_COST})`}
        </Button>
        
        <Button 
          onClick={resetGame}
          variant="outline"
          size="lg"
        >
          リセット
        </Button>
      </div>
    </div>
    
    <div className="text-xs text-gray-500 text-center">
      ピンに当たってランダムに弾む！高倍率のポケットを狙おう！
    </div>
  </CardContent>
</Card>

);
};

export default PurePachinko2D;