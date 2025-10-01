import React, { useState, useEffect, useRef, useCallback, useReducer, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Slider } from '@/components/ui/slider';
import { Moon, Sun, Star, RotateCcw } from 'lucide-react';

// ========================================
// 定数定義
// ========================================
const GAME_CONFIG = {
  CANVAS_WIDTH: 300,
  CANVAS_HEIGHT: 400,
  INITIAL_CREDITS: 1000,
  LAUNCH_COST: 10,
  INITIAL_JACKPOT: 10000,
  JACKPOT_CHANCE: 0.1,
  GRAVITY: 0.15,
  BOUNCE_DAMPING: 0.7,
  COLLISION_RESPONSE: 1.5,
  MAX_SPEED: 12,
  BALL_RADIUS: 4,
  PIN_RADIUS: 3,
  TRAIL_LENGTH: 8,
  LAUNCH_COOLDOWN: 800,
  POCKET_VALUES: [50, 100, 500, 100, 50],
  POCKET_COLORS: ['#e67e22', '#27ae60', '#f39c12', '#27ae60', '#e67e22'],
};

// ========================================
// ユーティリティ関数
// ========================================
const generatePins = () => {
  const pins = [];
  for (let row = 0; row < 12; row++) {
    for (let col = 0; col < 8; col++) {
      const offsetX = row % 2 === 0 ? 0 : 15;
      const x = 30 + col * 35 + offsetX;
      const y = 60 + row * 25;
      if (x > 15 && x < GAME_CONFIG.CANVAS_WIDTH - 15) {
        pins.push({ 
          x, 
          y, 
          radius: GAME_CONFIG.PIN_RADIUS 
        });
      }
    }
  }
  return pins;
};

// ========================================
// Ballクラス（コンポーネント外で定義）
// ========================================
class Ball {
  constructor(x, y, dx, dy, id) {
    this.x = x;
    this.y = y;
    this.dx = dx;
    this.dy = dy;
    this.radius = GAME_CONFIG.BALL_RADIUS;
    this.id = id || Math.random().toString(36).substring(7);
    this.trail = [];
  }

  update(pins) {
    // 軌跡の記録
    this.trail.push({ x: this.x, y: this.y });
    if (this.trail.length > GAME_CONFIG.TRAIL_LENGTH) {
      this.trail.shift();
    }

    // 物理演算
    this.x += this.dx;
    this.y += this.dy;
    this.dy += GAME_CONFIG.GRAVITY;

    // 左右の境界
    if (this.x <= this.radius) {
      this.x = this.radius;
      this.dx = Math.abs(this.dx) * GAME_CONFIG.BOUNCE_DAMPING;
    } else if (this.x >= GAME_CONFIG.CANVAS_WIDTH - this.radius) {
      this.x = GAME_CONFIG.CANVAS_WIDTH - this.radius;
      this.dx = -Math.abs(this.dx) * GAME_CONFIG.BOUNCE_DAMPING;
    }

    // ピンとの衝突判定
    pins.forEach(pin => {
      const dx = this.x - pin.x;
      const dy = this.y - pin.y;
      const distance = Math.sqrt(dx * dx + dy * dy);
      const minDistance = this.radius + pin.radius;

      if (distance < minDistance && distance > 0) {
        const normalX = dx / distance;
        const normalY = dy / distance;
        
        this.x = pin.x + normalX * minDistance;
        this.y = pin.y + normalY * minDistance;
        
        const dotProduct = this.dx * normalX + this.dy * normalY;
        this.dx = (this.dx - GAME_CONFIG.COLLISION_RESPONSE * dotProduct * normalX) * 0.8;
        this.dy = (this.dy - GAME_CONFIG.COLLISION_RESPONSE * dotProduct * normalY) * 0.8;
      }
    });

    // 速度制限
    if (Math.abs(this.dx) > GAME_CONFIG.MAX_SPEED) {
      this.dx = Math.sign(this.dx) * GAME_CONFIG.MAX_SPEED;
    }
    if (Math.abs(this.dy) > GAME_CONFIG.MAX_SPEED) {
      this.dy = Math.sign(this.dy) * GAME_CONFIG.MAX_SPEED;
    }
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
    return this.y > GAME_CONFIG.CANVAS_HEIGHT;
  }

  getPocket() {
    return Math.floor(this.x / 60);
  }
}

// ========================================
// Reducer（状態管理の一元化）
// ========================================
const initialState = {
  credits: GAME_CONFIG.INITIAL_CREDITS,
  ballsInPlay: 0,
  jackpot: GAME_CONFIG.INITIAL_JACKPOT,
  isLaunching: false,
  launchPower: 50,
  nightMode: false,
  lastWin: 0,
  jackpotWin: false,
  totalWins: 0,
  totalLaunches: 0,
  highScore: 0,
};

const gameReducer = (state, action) => {
  switch (action.type) {
    case 'LAUNCH_BALL':
      return {
        ...state,
        credits: state.credits - GAME_CONFIG.LAUNCH_COST,
        isLaunching: true,
        ballsInPlay: state.ballsInPlay + 1,
        lastWin: 0,
        totalLaunches: state.totalLaunches + 1,
      };
    
    case 'END_LAUNCH':
      return {
        ...state,
        isLaunching: false,
      };
    
    case 'BALL_LANDED':
      const newCredits = state.credits + action.score;
      return {
        ...state,
        credits: newCredits,
        lastWin: action.score,
        ballsInPlay: Math.max(0, state.ballsInPlay - 1),
        totalWins: action.score > 0 ? state.totalWins + 1 : state.totalWins,
        highScore: Math.max(state.highScore, newCredits),
      };
    
    case 'JACKPOT_WIN':
      return {
        ...state,
        credits: state.credits + action.amount,
        jackpot: Math.max(1000, state.jackpot - action.amount + 500),
        jackpotWin: true,
      };
    
    case 'CLEAR_JACKPOT_WIN':
      return {
        ...state,
        jackpotWin: false,
      };
    
    case 'SET_POWER':
      return {
        ...state,
        launchPower: action.power,
      };
    
    case 'TOGGLE_NIGHT_MODE':
      return {
        ...state,
        nightMode: !state.nightMode,
      };
    
    case 'RESET_GAME':
      return {
        ...initialState,
        nightMode: state.nightMode,
        highScore: state.highScore,
      };
    
    default:
      return state;
  }
};

// ========================================
// メインコンポーネント
// ========================================
const PachinkoMachine = () => {
  const [state, dispatch] = useReducer(gameReducer, initialState);
  
  const canvasRef = useRef(null);
  const animationRef = useRef(null);
  const ballsRef = useRef([]);
  const pinsRef = useRef([]);
  const nextBallIdRef = useRef(0);

  // ピンの生成（メモ化）
  const pins = useMemo(() => generatePins(), []);

  // マシンの描画（メモ化）
  const drawMachine = useCallback((ctx) => {
    // 背景
    const bgGradient = ctx.createLinearGradient(0, 0, 0, GAME_CONFIG.CANVAS_HEIGHT);
    if (state.nightMode) {
      bgGradient.addColorStop(0, '#2c3e50');
      bgGradient.addColorStop(1, '#34495e');
    } else {
      bgGradient.addColorStop(0, '#ecf0f1');
      bgGradient.addColorStop(1, '#bdc3c7');
    }
    ctx.fillStyle = bgGradient;
    ctx.fillRect(0, 0, GAME_CONFIG.CANVAS_WIDTH, GAME_CONFIG.CANVAS_HEIGHT);

    // ピンの描画
    pins.forEach(pin => {
      ctx.beginPath();
      ctx.arc(pin.x, pin.y, pin.radius, 0, Math.PI * 2);
      ctx.fillStyle = state.nightMode ? '#95a5a6' : '#34495e';
      ctx.fill();
      ctx.strokeStyle = state.nightMode ? '#ecf0f1' : '#2c3e50';
      ctx.lineWidth = 0.5;
      ctx.stroke();
    });

    // ポケットの描画
    for (let i = 0; i < 5; i++) {
      const x = i * 60;
      
      ctx.beginPath();
      ctx.moveTo(x, GAME_CONFIG.CANVAS_HEIGHT);
      ctx.lineTo(x + 15, 370);
      ctx.lineTo(x + 45, 370);
      ctx.lineTo(x + 60, GAME_CONFIG.CANVAS_HEIGHT);
      ctx.closePath();
      
      const pocketGradient = ctx.createLinearGradient(x, 370, x, GAME_CONFIG.CANVAS_HEIGHT);
      pocketGradient.addColorStop(0, GAME_CONFIG.POCKET_COLORS[i]);
      pocketGradient.addColorStop(1, state.nightMode ? '#2c3e50' : '#34495e');
      ctx.fillStyle = pocketGradient;
      ctx.fill();
      
      ctx.strokeStyle = state.nightMode ? '#ecf0f1' : '#2c3e50';
      ctx.lineWidth = 2;
      ctx.stroke();
      
      ctx.fillStyle = '#fff';
      ctx.font = 'bold 12px Arial';
      ctx.textAlign = 'center';
      ctx.fillText(GAME_CONFIG.POCKET_VALUES[i].toString(), x + 30, 390);
      
      if (i === 2) {
        ctx.fillStyle = '#f1c40f';
        ctx.font = 'bold 8px Arial';
        ctx.fillText('JACKPOT', x + 30, 378);
      }
    }
  }, [state.nightMode, pins]);

  // アニメーションループ
  const animate = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    drawMachine(ctx);
    
    const ballsToRemove = [];
    ballsRef.current.forEach((ball, index) => {
      ball.update(pinsRef.current);
      ball.draw(ctx, state.nightMode);
      
      if (ball.isOutOfBounds()) {
        ballsToRemove.push(index);
        
        const pocket = ball.getPocket();
        const score = pocket >= 0 && pocket < GAME_CONFIG.POCKET_VALUES.length 
          ? GAME_CONFIG.POCKET_VALUES[pocket] 
          : 0;
        
        dispatch({ type: 'BALL_LANDED', score });
        
        // ジャックポット判定
        if (pocket === 2 && Math.random() < GAME_CONFIG.JACKPOT_CHANCE) {
          const jackpotAmount = Math.min(state.jackpot, 5000);
          dispatch({ type: 'JACKPOT_WIN', amount: jackpotAmount });
          setTimeout(() => dispatch({ type: 'CLEAR_JACKPOT_WIN' }), 3000);
        }
      }
    });
    
    ballsToRemove.reverse().forEach(index => {
      ballsRef.current.splice(index, 1);
    });
    
    animationRef.current = requestAnimationFrame(animate);
  }, [state.nightMode, state.jackpot, drawMachine]);

  // 初期化とクリーンアップ
  useEffect(() => {
    pinsRef.current = pins;
    animate();
    
    return () => {
      if (animationRef.current) {
        cancelAnimationFrame(animationRef.current);
      }
      ballsRef.current = [];
    };
  }, [animate, pins]);

  // 玉発射
  const launchBall = useCallback(() => {
    if (state.credits >= GAME_CONFIG.LAUNCH_COST && !state.isLaunching) {
      dispatch({ type: 'LAUNCH_BALL' });

      const newBall = new Ball(
        150 + (Math.random() - 0.5) * 20,
        10,
        (Math.random() - 0.5) * (state.launchPower / 25),
        2 + state.launchPower / 50,
        nextBallIdRef.current++
      );

      ballsRef.current.push(newBall);

      setTimeout(() => {
        dispatch({ type: 'END_LAUNCH' });
      }, GAME_CONFIG.LAUNCH_COOLDOWN);
    }
  }, [state.credits, state.isLaunching, state.launchPower]);

  // キーボードサポート
  useEffect(() => {
    const handleKeyPress = (e) => {
      if (e.code === 'Space') {
        e.preventDefault();
        launchBall();
      }
    };

    window.addEventListener('keydown', handleKeyPress);
    return () => window.removeEventListener('keydown', handleKeyPress);
  }, [launchBall]);

  // 統計計算
  const winRate = state.totalLaunches > 0 
    ? ((state.totalWins / state.totalLaunches) * 100).toFixed(1)
    : '0.0';

  return (
    <Card className="w-[350px] mx-auto mt-8 relative overflow-hidden">
      {state.jackpotWin && (
        <div className="absolute inset-0 bg-yellow-400 bg-opacity-20 flex items-center justify-center z-10 animate-pulse">
          <div className="bg-yellow-500 text-white px-4 py-2 rounded-lg font-bold text-lg flex items-center gap-2 shadow-lg">
            <Star className="h-5 w-5" />
            JACKPOT!
            <Star className="h-5 w-5" />
          </div>
        </div>
      )}
      
      <CardHeader>
        <CardTitle className="flex justify-between items-center">
          <span className={state.nightMode ? 'text-yellow-400' : 'text-gray-800'}>
            🎰 パチンコ台
          </span>
          <div className="flex gap-2">
            <Button 
              variant="ghost" 
              size="icon" 
              onClick={() => dispatch({ type: 'RESET_GAME' })}
              title="リセット"
            >
              <RotateCcw className="h-4 w-4" />
            </Button>
            <Button 
              variant="ghost" 
              size="icon" 
              onClick={() => dispatch({ type: 'TOGGLE_NIGHT_MODE' })}
              className={state.nightMode ? 'text-yellow-400 hover:text-yellow-300' : ''}
              title={state.nightMode ? '昼モード' : '夜モード'}
            >
              {state.nightMode ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
            </Button>
          </div>
        </CardTitle>
      </CardHeader>
      
      <CardContent className="space-y-4">
        {/* ステータス表示 */}
        <div className="grid grid-cols-2 gap-2 text-sm">
          <div className="bg-green-50 p-2 rounded">
            <div className="text-gray-600 text-xs">クレジット</div>
            <div className="text-xl font-bold text-green-600">{state.credits}</div>
          </div>
          <div className="bg-yellow-50 p-2 rounded">
            <div className="text-gray-600 text-xs">ジャックポット</div>
            <div className="text-lg font-bold text-yellow-600">{state.jackpot}</div>
          </div>
          <div className="bg-blue-50 p-2 rounded">
            <div className="text-gray-600 text-xs">プレイ中</div>
            <div className="text-lg font-semibold">{state.ballsInPlay}個</div>
          </div>
          <div className="bg-purple-50 p-2 rounded">
            <div className="text-gray-600 text-xs">最高記録</div>
            <div className="text-lg font-semibold text-purple-600">{state.highScore}</div>
          </div>
        </div>

        {/* 獲得表示 */}
        {state.lastWin > 0 && (
          <div className="text-center animate-bounce">
            <span className="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full font-bold">
              +{state.lastWin} 獲得！
            </span>
          </div>
        )}

        {/* 統計 */}
        <div className="text-xs text-gray-600 flex justify-between px-2">
          <span>プレイ回数: {state.totalLaunches}</span>
          <span>勝率: {winRate}%</span>
        </div>
        
        {/* キャンバス */}
        <div className="relative">
          <canvas 
            ref={canvasRef} 
            width={GAME_CONFIG.CANVAS_WIDTH} 
            height={GAME_CONFIG.CANVAS_HEIGHT} 
            className="border border-gray-300 rounded shadow-lg w-full"
            style={{
              background: state.nightMode 
                ? 'linear-gradient(to bottom, #2c3e50, #34495e)' 
                : 'linear-gradient(to bottom, #ecf0f1, #bdc3c7)'
            }}
            role="img"
            aria-label="パチンコ盤面"
          />
        </div>
        
        {/* パワースライダー */}
        <div className="space-y-2">
          <div className="flex justify-between items-center">
            <span className="text-sm font-medium">発射力:</span>
            <span className="text-sm font-bold">{state.launchPower}%</span>
          </div>
          <Slider
            value={[state.launchPower]}
            onValueChange={(value) => dispatch({ type: 'SET_POWER', power: value[0] })}
            min={20}
            max={100}
            step={5}
            className="w-full"
            disabled={state.isLaunching}
          />
        </div>
        
        {/* 発射ボタン */}
        <Button 
          onClick={launchBall} 
          disabled={state.isLaunching || state.credits < GAME_CONFIG.LAUNCH_COST}
          className="w-full"
          size="lg"
        >
          {state.isLaunching ? '発射中...' : `🚀 発射 (${GAME_CONFIG.LAUNCH_COST}クレジット)`}
        </Button>
        
        {/* エラーメッセージ */}
        {state.credits < GAME_CONFIG.LAUNCH_COST && (
          <p className="text-sm text-red-500 text-center font-medium">
            ⚠️ クレジットが不足しています
          </p>
        )}

        {/* ヘルプ */}
        <p className="text-xs text-gray-500 text-center">
          💡 スペースキーでも発射できます
        </p>
      </CardContent>
    </Card>
  );
};

export default PachinkoMachine;
