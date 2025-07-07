import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Slider } from '@/components/ui/slider';
import { Moon, Sun, Star } from 'lucide-react';

const PachinkoMachine = () => {
  const [credits, setCredits] = useState(1000);
  const [ballsInPlay, setBallsInPlay] = useState(0);
  const [jackpot, setJackpot] = useState(10000);
  const [isLaunching, setIsLaunching] = useState(false);
  const [launchPower, setLaunchPower] = useState(50);
  const [nightMode, setNightMode] = useState(false);
  const [lastWin, setLastWin] = useState(0);
  const [jackpotWin, setJackpotWin] = useState(false);
  
  const canvasRef = useRef(null);
  const animationRef = useRef(null);
  const ballsRef = useRef([]);
  const pinsRef = useRef([]);

  // ピンの位置を生成（改良版）
  const generatePins = useCallback(() => {
    const pins = [];
    for (let row = 0; row < 12; row++) {
      for (let col = 0; col < 8; col++) {
        const offsetX = row % 2 === 0 ? 0 : 15;
        const x = 30 + col * 35 + offsetX;
        const y = 60 + row * 25;
        if (x > 15 && x < 285) { // キャンバス内に収める
          pins.push({ x, y, radius: 3 });
        }
      }
    }
    return pins;
  }, []);

  // 玉クラス（改良版）
  class Ball {
    constructor(x, y, dx, dy) {
      this.x = x;
      this.y = y;
      this.dx = dx;
      this.dy = dy;
      this.radius = 4;
      this.id = Math.random().toString(36).substring(7);
      this.trail = []; // 軌跡用
    }

    update() {
      // 軌跡の記録
      this.trail.push({ x: this.x, y: this.y });
      if (this.trail.length > 8) {
        this.trail.shift();
      }

      // 物理演算
      this.x += this.dx;
      this.y += this.dy;
      this.dy += 0.15; // 重力

      // 左右の境界
      if (this.x <= this.radius) {
        this.x = this.radius;
        this.dx = Math.abs(this.dx) * 0.7;
      } else if (this.x >= 300 - this.radius) {
        this.x = 300 - this.radius;
        this.dx = -Math.abs(this.dx) * 0.7;
      }

      // ピンとの衝突判定
      pinsRef.current.forEach(pin => {
        const dx = this.x - pin.x;
        const dy = this.y - pin.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        const minDistance = this.radius + pin.radius;

        if (distance < minDistance && distance > 0) {
          // 正規化ベクトル
          const normalX = dx / distance;
          const normalY = dy / distance;
          
          // 位置修正
          this.x = pin.x + normalX * minDistance;
          this.y = pin.y + normalY * minDistance;
          
          // 反射
          const dotProduct = this.dx * normalX + this.dy * normalY;
          this.dx = this.dx - 1.5 * dotProduct * normalX;
          this.dy = this.dy - 1.5 * dotProduct * normalY;
          
          // エネルギー減衰
          this.dx *= 0.8;
          this.dy *= 0.8;
        }
      });

      // 速度制限
      const maxSpeed = 12;
      if (Math.abs(this.dx) > maxSpeed) this.dx = Math.sign(this.dx) * maxSpeed;
      if (Math.abs(this.dy) > maxSpeed) this.dy = Math.sign(this.dy) * maxSpeed;
    }

    draw(ctx, nightMode) {
      // 軌跡描画
      this.trail.forEach((point, index) => {
        const alpha = (index + 1) / this.trail.length * 0.3;
        ctx.globalAlpha = alpha;
        ctx.beginPath();
        ctx.arc(point.x, point.y, this.radius * 0.5, 0, Math.PI * 2);
        ctx.fillStyle = nightMode ? '#e74c3c' : '#c0392b';
        ctx.fill();
      });

      // メイン玉
      ctx.globalAlpha = 1;
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      
      // グラデーション効果
      const gradient = ctx.createRadialGradient(
        this.x - 1, this.y - 1, 0,
        this.x, this.y, this.radius
      );
      gradient.addColorStop(0, nightMode ? '#ff6b6b' : '#e74c3c');
      gradient.addColorStop(1, nightMode ? '#c0392b' : '#8e44ad');
      
      ctx.fillStyle = gradient;
      ctx.fill();
      
      // 光沢効果
      ctx.beginPath();
      ctx.arc(this.x - 1, this.y - 1, this.radius * 0.3, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
      ctx.fill();
    }

    isOutOfBounds() {
      return this.y > 400;
    }

    getPocket() {
      return Math.floor(this.x / 60);
    }
  }

  // マシンの描画
  const drawMachine = useCallback((ctx) => {
    // 背景
    const bgGradient = ctx.createLinearGradient(0, 0, 0, 400);
    if (nightMode) {
      bgGradient.addColorStop(0, '#2c3e50');
      bgGradient.addColorStop(1, '#34495e');
    } else {
      bgGradient.addColorStop(0, '#ecf0f1');
      bgGradient.addColorStop(1, '#bdc3c7');
    }
    ctx.fillStyle = bgGradient;
    ctx.fillRect(0, 0, 300, 400);

    // ピンの描画
    pinsRef.current.forEach(pin => {
      ctx.beginPath();
      ctx.arc(pin.x, pin.y, pin.radius, 0, Math.PI * 2);
      ctx.fillStyle = nightMode ? '#95a5a6' : '#34495e';
      ctx.fill();
      ctx.strokeStyle = nightMode ? '#ecf0f1' : '#2c3e50';
      ctx.lineWidth = 0.5;
      ctx.stroke();
    });

    // ポケットの描画（改良版）
    const pocketColors = ['#e67e22', '#27ae60', '#f39c12', '#27ae60', '#e67e22'];
    const pocketValues = ['50', '100', '500', '100', '50'];
    
    for (let i = 0; i < 5; i++) {
      const x = i * 60;
      
      // ポケット本体
      ctx.beginPath();
      ctx.moveTo(x, 400);
      ctx.lineTo(x + 15, 370);
      ctx.lineTo(x + 45, 370);
      ctx.lineTo(x + 60, 400);
      ctx.closePath();
      
      const pocketGradient = ctx.createLinearGradient(x, 370, x, 400);
      pocketGradient.addColorStop(0, pocketColors[i]);
      pocketGradient.addColorStop(1, nightMode ? '#2c3e50' : '#34495e');
      ctx.fillStyle = pocketGradient;
      ctx.fill();
      
      ctx.strokeStyle = nightMode ? '#ecf0f1' : '#2c3e50';
      ctx.lineWidth = 2;
      ctx.stroke();
      
      // 値表示
      ctx.fillStyle = '#fff';
      ctx.font = 'bold 12px Arial';
      ctx.textAlign = 'center';
      ctx.fillText(pocketValues[i], x + 30, 390);
      
      // ジャックポット表示
      if (i === 2) {
        ctx.fillStyle = '#f1c40f';
        ctx.font = 'bold 8px Arial';
        ctx.fillText('JACKPOT', x + 30, 378);
      }
    }
  }, [nightMode]);

  // アニメーションループ
  const animate = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // 全体クリア
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // マシン描画
    drawMachine(ctx);
    
    // 玉の更新と描画
    const ballsToRemove = [];
    ballsRef.current.forEach((ball, index) => {
      ball.update();
      ball.draw(ctx, nightMode);
      
      if (ball.isOutOfBounds()) {
        ballsToRemove.push(index);
        
        // スコア計算
        const pocket = ball.getPocket();
        const scores = [50, 100, 500, 100, 50];
        const score = pocket >= 0 && pocket < scores.length ? scores[pocket] : 0;
        
        setCredits(prev => prev + score);
        setLastWin(score);
        setBallsInPlay(prev => Math.max(0, prev - 1));
        
        // ジャックポット判定
        if (pocket === 2 && Math.random() < 0.1) { // 10%の確率
          const jackpotWin = Math.min(jackpot, 5000);
          setCredits(prev => prev + jackpotWin);
          setJackpot(prev => Math.max(1000, prev - jackpotWin + 500));
          setJackpotWin(true);
          setTimeout(() => setJackpotWin(false), 3000);
        }
      }
    });
    
    // 完了した玉を削除
    ballsToRemove.reverse().forEach(index => {
      ballsRef.current.splice(index, 1);
    });
    
    animationRef.current = requestAnimationFrame(animate);
  }, [nightMode, jackpot, drawMachine]);

  // 初期化
  useEffect(() => {
    pinsRef.current = generatePins();
    animate();
    
    return () => {
      if (animationRef.current) {
        cancelAnimationFrame(animationRef.current);
      }
    };
  }, [animate, generatePins]);

  // 玉発射
  const launchBall = useCallback(() => {
    if (credits >= 10 && !isLaunching) {
      setCredits(prev => prev - 10);
      setIsLaunching(true);
      setBallsInPlay(prev => prev + 1);
      setLastWin(0);

      const newBall = new Ball(
        150 + (Math.random() - 0.5) * 20,
        10,
        (Math.random() - 0.5) * (launchPower / 25),
        2 + launchPower / 50
      );

      ballsRef.current.push(newBall);

      setTimeout(() => setIsLaunching(false), 800);
    }
  }, [credits, isLaunching, launchPower]);

  return (
    <Card className="w-[350px] mx-auto mt-8 relative overflow-hidden">
      {jackpotWin && (
        <div className="absolute inset-0 bg-yellow-400 bg-opacity-20 flex items-center justify-center z-10 animate-pulse">
          <div className="bg-yellow-500 text-white px-4 py-2 rounded-lg font-bold text-lg flex items-center gap-2">
            <Star className="h-5 w-5" />
            JACKPOT!
            <Star className="h-5 w-5" />
          </div>
        </div>
      )}
      
      <CardHeader>
        <CardTitle className="flex justify-between items-center">
          <span className={nightMode ? 'text-yellow-400' : 'text-gray-800'}>
            パチンコ台
          </span>
          <Button 
            variant="ghost" 
            size="icon" 
            onClick={() => setNightMode(!nightMode)}
            className={nightMode ? 'text-yellow-400 hover:text-yellow-300' : ''}
          >
            {nightMode ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
          </Button>
        </CardTitle>
      </CardHeader>
      
      <CardContent className="space-y-4">
        <div className="text-center space-y-2">
          <div className="flex justify-between items-center">
            <span>クレジット:</span>
            <span className="text-2xl font-bold text-green-600">{credits}</span>
          </div>
          <div className="flex justify-between items-center">
            <span>ジャックポット:</span>
            <span className="text-lg font-bold text-yellow-600">{jackpot}</span>
          </div>
          <div className="flex justify-between items-center">
            <span>プレイ中の玉:</span>
            <span className="font-semibold">{ballsInPlay}</span>
          </div>
          {lastWin > 0 && (
            <div className="text-center">
              <span className="text-sm bg-green-100 text-green-800 px-2 py-1 rounded">
                +{lastWin} 獲得！
              </span>
            </div>
          )}
        </div>
        
        <div className="relative">
          <canvas 
            ref={canvasRef} 
            width={300} 
            height={400} 
            className="border border-gray-300 rounded shadow-lg"
            style={{
              background: nightMode 
                ? 'linear-gradient(to bottom, #2c3e50, #34495e)' 
                : 'linear-gradient(to bottom, #ecf0f1, #bdc3c7)'
            }}
          />
        </div>
        
        <div className="space-y-2">
          <div className="flex justify-between items-center">
            <span className="text-sm font-medium">発射力:</span>
            <span className="text-sm font-bold">{launchPower}%</span>
          </div>
          <Slider
            value={[launchPower]}
            onValueChange={(value) => setLaunchPower(value[0])}
            min={20}
            max={100}
            step={5}
            className="w-full"
          />
        </div>
        
        <Button 
          onClick={launchBall} 
          disabled={isLaunching || credits < 10}
          className="w-full"
          size="lg"
        >
          {isLaunching ? '発射中...' : '発射 (10クレジット)'}
        </Button>
        
        {credits < 10 && (
          <p className="text-sm text-red-500 text-center">
            クレジットが不足しています
          </p>
        )}
      </CardContent>
    </Card>
  );
};

export default PachinkoMachine;
