<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パチンコゲーム</title>
    <style>
        canvas {
            border: 1px solid #000;
        }
    </style>
</head>
<body>
    <h1>パチンコゲーム</h1>
    <div>
        <p>クレジット: <span id="credits">{{ $credits }}</span></p>
        <p>発射力: <span id="launch-power">50</span></p>
        <input type="range" id="power-slider" min="1" max="100" value="50">
    </div>
    <canvas id="pachinko-canvas" width="400" height="600"></canvas>
    <button id="launch-button">発射 (10クレジット)</button>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // JavaScriptのゲームロジックはここに記述します
    </script>
</body>
</html>
