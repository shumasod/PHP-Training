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

    if (this.x - this.radius <= 0) {
      this.x = this.radius;
      this.dx = Math.abs(this.dx) * FRICTION;
    } else if (this.x + this.radius >= CANVAS_WIDTH) {
      this.x = CANVAS_WIDTH - this.radius;
      this.dx = -Math.abs(this.dx) * FRICTION;
    }

    const maxSpeed = 15;
    if (Math.abs(this.dx) > maxSpeed) this.dx = Math.sign(this.dx) * maxSpeed;
    if (Math.abs(this.dy) > maxSpeed) this.dy = Math.sign(this.dy) * maxSpeed;
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
    if (pocketIndex >= 0 && pocketIndex < POCKETS.length) {
      return POCKETS[pocketIndex].multiplier;
    }
    return 0;
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

  useEffect(() => {
    fetch('/api/credits')
      .then(response => response.json())
      .then(data => setCredits(data.credits));
  }, []);

  const updateCredits = (newCredits) => {
    fetch('/api/update-credits', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ credits: newCredits }),
    });
  };

  const launchBall = useCallback(() => {
    if (credits >= LAUNCH_COST && !isLaunching) {
      const newCredits = credits - LAUNCH_COST;
      setCredits(newCredits);
      updateCredits(newCredits);

      setIsLaunching(true);
      setBallsInPlay(prev => prev + 1);
      setLastScore(0);

      const newBall = new Ball(
        CANVAS_WIDTH / 2 + (Math.random() - 0.5) * 20,
        10,
        (Math.random() - 0.5) * (launchPower / 25),
        2 + launchPower / 25
      );

      ballsRef.current.push(newBall);

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
        <Button 
          onClick={launchBall} 
          disabled={isLaunching || credits < LAUNCH_COST}
          className="w-full"
          size="lg"
        >
          {isLaunching ? '発射中...' : `発射 (${LAUNCH_COST}クレジット)`}
        </Button>
      </CardContent>
    </Card>
  );
};

export default PurePachinko2D;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::get('/credits', function () {
        return response()->json(['credits' => 1000]);  // 初期クレジット
    });

    Route::post('/update-credits', function (Request $request) {
        $credits = $request->input('credits');
        // クレジットの更新処理をここに記述
        return response()->json(['success' => true, 'newCredits' => $credits]);
    });
});