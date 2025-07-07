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

// ポケットの設定
const POCKETS = [
  { multiplier: 50, width: 80 },
  { multiplier: 100, width: 80 },
  { multiplier: 500, width: 80 },
  { multiplier: 100, width: 80 },
  { multiplier: 50, width: 80 }
];

class Ball {
  constructor(x, y, dx, dy) {
    this.x = x;
    this.y = y;
    this.dx = dx;
    this.dy = dy;
    this.radius = BALL_RADIUS;
    this.id = Math.random().toString(36).substring(7);
  }

  update() {
    this.x += this.dx;
    this.y += this.dy;
    this.dy += GRAVITY;

    // 左右の境界チェック（改良版）
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
  }

  // 改良された衝突検出
  checkCollisionWithPin(pin) {
    const dx = this.x - pin.x;
    const dy = this.y - pin.y;
    const distance = Math.sqrt(dx * dx + dy * dy);
    const minDistance = this.radius + PIN_RADIUS;

    if (distance < minDistance && distance > 0) {
      // 正規化されたベクトルで押し戻し
      const normalX = dx / distance;
      const normalY = dy / distance;
      
      // 玉をピンから離す
      this.x = pin.x + normalX * minDistance;
      this.y = pin.y + normalY * minDistance;
      
      // 反射ベクトルの計算
      const dotProduct = this.dx * normalX + this.dy * normalY;
      this.dx = this.dx - 2 * dotProduct * normalX;
      this.dy = this.dy - 2 * dotProduct * normalY;
      
      // エネルギーの減衰
      this.dx *= 0.8;
      this.dy *= 0.8;
      
      return true;
    }
    return false;
  }

  draw(ctx) {
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

  getPocketScore() {
    const pocketIndex = Math.floor(this.x / 80);
    // 境界チェックを追加
    if (pocketIndex >= 0 && pocketIndex < POCKETS.length) {
      return POCKETS[pocketIndex].multiplier;
    }
    return 0; // 範囲外の場合は0点
  }
}

const PurePachinko2D = () => {
  const canvasRef = useRef(null);
  const animationRef = useRef(null);
  const ballsRef = useRef([]);
  
  const [credits, setCredits] = useState(1000);
  const [ballsInPlay, setBallsInPlay] = useState(0);
  const [launchPower, setLaunchPower] = useState(50);
  const [isLaunching, setIsLaunching] = useState(false);
  const [lastScore, setLastScore] = useState(0);

  // ピンの生成（メモ化）
  const pins = useMemo(() => {
    const pinsArray = [];
    for (let row = 0; row < 15; row++) {
      for (let col = 0; col < 10; col++) {
        const x = row % 2 === 0 ? 20 + col * 40 : 40 + col * 40;
        const y = 100 + row * 30;
        
        // キャンバス内に収まるピンのみ追加
        if (x >= PIN_RADIUS && x <= CANVAS_WIDTH - PIN_RADIUS) {
          pinsArray.push({ x, y });
        }
      }
    }
    return pinsArray;
  }, []);

  // 描画関数（メモ化）
  const drawBackground = useCallback((ctx) => {
    ctx.fillStyle = '#ecf0f1';
    ctx.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
  }, []);

  const drawPins = useCallback((ctx) => {
    pins.forEach(pin => {
      ctx.beginPath();
      ctx.arc(pin.x, pin.y, PIN_RADIUS, 0, Math.PI * 2);
      ctx.fillStyle = '#34495e';
      ctx.fill();
      ctx.strokeStyle = '#2c3e50';
      ctx.lineWidth = 1;
      ctx.stroke();
      ctx.closePath();
    });
  }, [pins]);

  const drawPockets = useCallback((ctx) => {
    POCKETS.forEach((pocket, i) => {
      const x = i * pocket.width;
      
      // ポケットの形状
      ctx.beginPath();
      ctx.moveTo(x, CANVAS_HEIGHT);
      ctx.lineTo(x + 20, CANVAS_HEIGHT - 30);
      ctx.lineTo(x + 60, CANVAS_HEIGHT - 30);
      ctx.lineTo(x + pocket.width, CANVAS_HEIGHT);
      ctx.fillStyle = '#3498db';
      ctx.fill();
      ctx.strokeStyle = '#2980b9';
      ctx.lineWidth = 2;
      ctx.stroke();
      ctx.closePath();
      
      // スコア表示
      ctx.fillStyle = '#fff';
      ctx.font = '12px Arial';
      ctx.textAlign = 'center';
      ctx.fillText(
        `×${pocket.multiplier}`, 
        x + pocket.width / 2, 
        CANVAS_HEIGHT - 10
      );
    });
  }, []);

  // アニメーションループ
  const animate = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // 背景とゲーム要素の描画
    ctx.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
    drawBackground(ctx);
    drawPins(ctx);
    drawPockets(ctx);

    // 玉の更新と描画
    const ballsToRemove = [];
    
    ballsRef.current.forEach((ball, index) => {
      ball.update();
      
      // ピンとの衝突検出
      pins.forEach(pin => {
        ball.checkCollisionWithPin(pin);
      });
      
      ball.draw(ctx);
      
      // 玉が画面外に出た場合
      if (ball.isOutOfBounds()) {
        const score = ball.getPocketScore();
        setCredits(prev => prev + score);
        setLastScore(score);
        setBallsInPlay(prev => Math.max(0, prev - 1));
        ballsToRemove.push(index);
      }
    });
    
    // 画面外の玉を削除
    ballsToRemove.reverse().forEach(index => {
      ballsRef.current.splice(index, 1);
    });
    
    animationRef.current = requestAnimationFrame(animate);
  }, [pins, drawBackground, drawPins, drawPockets]);

  // アニメーション開始
  useEffect(() => {
    animate();
    
    // クリーンアップ
    return () => {
      if (animationRef.current) {
        cancelAnimationFrame(animationRef.current);
      }
    };
  }, [animate]);

  const launchBall = useCallback(() => {
    if (credits >= LAUNCH_COST && !isLaunching) {
      setCredits(prev => prev - LAUNCH_COST);
      setIsLaunching(true);
      setBallsInPlay(prev => prev + 1);
      setLastScore(0);

      const newBall = new Ball(
        CANVAS_WIDTH / 2 + (Math.random() - 0.5) * 20, // わずかなランダム性
        10,
        (Math.random() - 0.5) * (launchPower / 25),
        2 + launchPower / 25
      );

      ballsRef.current.push(newBall);

      // ローンチクールダウン
      setTimeout(() => setIsLaunching(false), 500);
    }
  }, [credits, isLaunching, launchPower]);

  return (
    <Card className="w-[450px] mx-auto mt-8">
      <CardHeader>
        <CardTitle className="text-center">パチンコゲーム</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="text-center space-y-2">
          <div className="flex justify-between items-center">
            <span className="text-lg">クレジット:</span>
            <span className="text-2xl font-bold text-green-600">{credits}</span>
          </div>
          <div className="flex justify-between items-center">
            <span>プレイ中の玉:</span>
            <span className="font-semibold">{ballsInPlay}</span>
          </div>
          {lastScore > 0 && (
            <div className="text-center">
              <span className="text-sm bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                +{lastScore} 獲得！
              </span>
            </div>
          )}
        </div>
        
        <canvas 
          ref={canvasRef} 
          width={CANVAS_WIDTH} 
          height={CANVAS_HEIGHT} 
          className="border border-gray-300 rounded shadow-md"
        />
        
        <div className="space-y-2">
          <div className="flex justify-between items-center">
            <label className="text-sm font-medium">発射力:</label>
            <span className="text-sm font-bold">{launchPower}%</span>
          </div>
          <Slider
            value={[launchPower]}
            onValueChange={(value) => setLaunchPower(value[0])}
            min={10}
            max={100}
            step={5}
            className="w-full"
          />
        </div>
        
        <Button 
          onClick={launchBall} 
          disabled={isLaunching || credits < LAUNCH_COST}
          className="w-full"
          size="lg"
        >
          {isLaunching ? '発射中...' : `発射 (${LAUNCH_COST}クレジット)`}
        </Button>
        
        {credits < LAUNCH_COST && (
          <p className="text-sm text-red-500 text-center">
            クレジットが不足しています
          </p>
        )}
      </CardContent>
    </Card>
  );
};

export default PurePachinko2D;
