import React, { useState, useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Slider } from '@/components/ui/slider';
import { Moon, Sun } from 'lucide-react';

const PachinkoMachine = () => {
  const [credits, setCredits] = useState(1000);
  const [ballsInPlay, setBallsInPlay] = useState(0);
  const [jackpot, setJackpot] = useState(10000);
  const [isLaunching, setIsLaunching] = useState(false);
  const [launchPower, setLaunchPower] = useState(50);
  const [nightMode, setNightMode] = useState(false);
  const canvasRef = useRef(null);

  useEffect(() => {
    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    const drawMachine = () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      
      // Draw machine body
      ctx.fillStyle = nightMode ? '#2c3e50' : '#ecf0f1';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      
      // Draw pins
      ctx.fillStyle = nightMode ? '#bdc3c7' : '#34495e';
      for (let i = 0; i < 10; i++) {
        for (let j = 0; j < 8; j++) {
          ctx.beginPath();
          ctx.arc(50 + i * 30, 50 + j * 40, 3, 0, Math.PI * 2);
          ctx.fill();
        }
      }
      
      // Draw pockets at the bottom
      for (let i = 0; i < 5; i++) {
        ctx.beginPath();
        ctx.moveTo(i * 60, canvas.height);
        ctx.lineTo(i * 60 + 30, canvas.height - 30);
        ctx.lineTo(i * 60 + 60, canvas.height);
        ctx.fillStyle = nightMode ? '#34495e' : '#3498db';
        ctx.fill();
      }
    };
    
    drawMachine();
  }, [nightMode]);

  const launchBall = () => {
    if (credits >= 10 && !isLaunching) {
      setCredits(credits - 10);
      setIsLaunching(true);
      setBallsInPlay(ballsInPlay + 1);
      
      const canvas = canvasRef.current;
      const ctx = canvas.getContext('2d');
      let x = canvas.width / 2;
      let y = 10;
      let dx = (Math.random() - 0.5) * launchPower / 10;
      let dy = 2;
      
      const animateBall = () => {
        ctx.clearRect(x-5, y-5, 10, 10);
        x += dx;
        y += dy;
        if (x < 5 || x > canvas.width - 5) dx = -dx;
        if (y > canvas.height - 5) {
          const pocket = Math.floor(x / 60);
          const score = [50, 100, 500, 100, 50][pocket];
          setCredits(credits => credits + score);
          setBallsInPlay(balls => balls - 1);
          setIsLaunching(false);
          if (pocket === 2) {
            setJackpot(jackpot => {
              const win = Math.min(jackpot, 10000);
              setCredits(credits => credits + win);
              return jackpot - win + 1000;
            });
          }
          return;
        }
        
        ctx.beginPath();
        ctx.arc(x, y, 5, 0, Math.PI * 2);
        ctx.fillStyle = nightMode ? '#e74c3c' : '#e74c3c';
        ctx.fill();
        requestAnimationFrame(animateBall);
      };
      
      animateBall();
    }
  };

  return (
    <Card className="w-[350px] mx-auto mt-8">
      <CardHeader>
        <CardTitle className="flex justify-between items-center">
          パチンコ台
          <Button variant="ghost" size="icon" onClick={() => setNightMode(!nightMode)}>
            {nightMode ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
          </Button>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="text-center mb-4">
          <p className="text-2xl font-bold">クレジット: {credits}</p>
          <p>ジャックポット: {jackpot}</p>
          <p>プレイ中の玉: {ballsInPlay}</p>
        </div>
        <canvas ref={canvasRef} width={300} height={400} className="mb-4 border border-gray-300" />
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

export default PachinkoMachine;
