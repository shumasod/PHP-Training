<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>シンプルパチンコゲーム</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        #game-container {
            text-align: center;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        #pachinko-board {
            width: 300px;
            height: 400px;
            background-color: #4a86e8;
            margin: 20px auto;
            position: relative;
            border-radius: 10px;
        }
        .pin {
            width: 10px;
            height: 10px;
            background-color: #fff;
            border-radius: 50%;
            position: absolute;
        }
        #launch-button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }
        #credits, #score {
            font-size: 18px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div id="game-container">
        <h1>シンプルパチンコゲーム</h1>
        <div id="credits">クレジット: <span id="credits-value">{{ $credits }}</span></div>
        <div id="score">スコア: <span id="score-value">0</span></div>
        <div id="pachinko-board">
            <!-- ピンの配置 -->
            @php
                for ($i = 0; $i < 50; $i++) {
                    $left = rand(10, 290);
                    $top = rand(10, 390);
                    echo "<div class='pin' style='left: {$left}px; top: {$top}px;'></div>";
                }
            @endphp
        </div>
        <button id="launch-button">発射 (10クレジット)</button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            let credits = {{ $credits }};
            
            $('#launch-button').click(function() {
                if (credits >= 10) {
                    $.ajax({
                        url: '{{ route("launchBall") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            launchPower: Math.random(),
                            credits: credits
                        },
                        success: function(response) {
                            if (response.success) {
                                credits = response.newCredits;
                                $('#credits-value').text(credits);
                                $('#score-value').text(response.score);
                                animateBall();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function() {
                            alert('エラーが発生しました。');
                        }
                    });
                } else {
                    alert('クレジットが不足しています。');
                }
            });

            function animateBall() {
                const ball = $('<div>').css({
                    width: '20px',
                    height: '20px',
                    backgroundColor: 'red',
                    borderRadius: '50%',
                    position: 'absolute',
                    top: '0',
                    left: Math.random() * 280 + 'px'
                });
                $('#pachinko-board').append(ball);
                
                ball.animate({ top: '400px' }, 2000, function() {
                    ball.remove();
                });
            }
        });
    </script>
</body>
</html>
