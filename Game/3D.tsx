import React, { useRef, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Slider } from '@/components/ui/slider';

const CANVAS_WIDTH = 400;
const CANVAS_HEIGHT = 600;
const BALL_RADIUS = 5;
const PIN_RADIUS = 3;

class Ball {
  constructor(x, y, dx, dy) {
    this.x = x;
    this.y = y;
    this.dx = dx;
    this.dy = dy;
    this.radius = BALL_RADIUS;
  }

  update() {
    this.x += this.dx;
    this.y += this.dy;
    this.dy += 0.2; // Gravity

    // Boundary check
    if (this.x - this.radius < 0 || this.x + this.radius > CANVAS_WIDTH) {
      this.dx = -this.dx * 0.8;
    }
  }

  draw(ctx) {
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
    ctx.fillStyle = '#e74c3c';
    ctx.fill();
    ctx.closePath();
  }
}

const PurePachinko2D = () => {
  const canvasRef = useRef(null);
  const [credits, setCredits] = useState(1000);
  const [ballsInPlay, setBallsInPlay] = useState(0);
  const [launchPower, setLaunchPower] = useState(50);
  const [isLaunching, setIsLaunching] = useState(false);
  const [balls, setBalls] = useState([]);

  useEffect(() => {
    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    const pins = [];

    // Create pins
    for (let i = 0; i < 10; i++) {
      for (let j = 0; j < 15; j++) {
        if (j % 2 === 0) {
          pins.push({ x: 20 + i * 40, y: 100 + j * 30 });
        } else {
          pins.push({ x: 40 + i * 40, y: 100 + j * 30 });
        }
      }
    }

    const drawBackground = () => {
      ctx.fillStyle = '#ecf0f1';
      ctx.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
    };

    const drawPins = () => {
      pins.forEach(pin => {
        ctx.beginPath();
        ctx.arc(pin.x, pin.y, PIN_RADIUS, 0, Math.PI * 2);
        ctx.fillStyle = '#34495e';
        ctx.fill();
        ctx.closePath();
      });
    };

    const drawPockets = () => {
      for (let i = 0; i < 5; i++) {
        ctx.beginPath();
        ctx.moveTo(i * 80, CANVAS_HEIGHT);
        ctx.lineTo(i * 80 + 40, CANVAS_HEIGHT - 30);
        ctx.lineTo(i * 80 + 80, CANVAS_HEIGHT);
        ctx.fillStyle = '#3498db';
        ctx.fill();
        ctx.closePath();
      }
    };

    const animate = () => {
      ctx.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
      drawBackground();
      drawPins();
      drawPockets();

      // Update and draw balls
      balls.forEach((ball, index) => {
        ball.update();
        ball.draw(ctx);

        // Check for collisions with pins
        pins.forEach(pin => {
          const dx = ball.x - pin.x;
          const dy = ball.y - pin.y;
          const distance = Math.sqrt(dx * dx + dy * dy);

          if (distance < ball.radius + PIN_RADIUS) {
            const angle = Math.atan2(dy, dx);
            const targetX = pin.x + Math.cos(angle) * (PIN_RADIUS + ball.radius);
            const targetY = pin.y + Math.sin(angle) * (PIN_RADIUS + ball.radius);
            ball.dx = (ball.x - targetX) * 0.1;
            ball.dy = (ball.y - targetY) * 0.1;
          }
        });

        // Check if ball is out of bounds
        if (ball.y > CANVAS_HEIGHT) {
          const pocket = Math.floor(ball.x / 80);
          const score = [50, 100, 500, 100, 50][pocket];
          setCredits(prev => prev + score);
          setBallsInPlay(prev => prev - 1);
          setBalls(prev => prev.filter((_, i) => i !== index));
        }
      });

      requestAnimationFrame(animate);
    };

    animate();
  }, [balls]);

  const launchBall = () => {
    if (credits >= 10 && !isLaunching) {
      setCredits(prev => prev - 10);
      setIsLaunching(true);
      setBallsInPlay(prev => prev + 1);

      const newBall = new Ball(
        CANVAS_WIDTH / 2,
        10,
        (Math.random() - 0.5) * launchPower / 50,
        2 + launchPower / 25
      );

      setBalls(prev => [...prev, newBall]);

      setTimeout(() => setIsLaunching(false), 1000);
    }
  };

  return (
    <Card className="w-[450px] mx-auto mt-8">
      <CardHeader>
        <CardTitle>パチンコゲーム</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="text-center mb-4">
          <p className="text-2xl font-bold">クレジット: {credits}</p>
          <p>プレイ中の玉: {ballsInPlay}</p>
        </div>
        <canvas 
          ref={canvasRef} 
          width={CANVAS_WIDTH} 
          height={CANVAS_HEIGHT} 
          className="mb-4 border border-gray-300"
        />
        <div className="mb-4">
          <p>発射力: {launchPower}</p>
          <Slider
            value={[launchPower]}
            onValueChange={(value) => setLaunchPower(value[0])}
            max={100}
            step={1}
          />
        </div>
        <Button 
          onClick={launchBall} 
          disabled={isLaunching || credits < 10}
          className="w-full"
        >
          発射 (10クレジット)
        </Button>
      </CardContent>
    </Card>
  );
};

export default PurePachinko2D;
