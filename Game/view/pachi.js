const canvas = document.getElementById('pachinko-canvas');
const ctx = canvas.getContext('2d');
const BALL_RADIUS = 5;
const PIN_RADIUS = 3;

let balls = [];
let pins = [];

// ピンの配置
for (let i = 0; i < 10; i++) {
    for (let j = 0; j < 15; j++) {
        if (j % 2 === 0) {
            pins.push({ x: 20 + i * 40, y: 100 + j * 30 });
        } else {
            pins.push({ x: 40 + i * 40, y: 100 + j * 30 });
        }
    }
}

function drawBackground() {
    ctx.fillStyle = '#ecf0f1';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
}

function drawPins() {
    pins.forEach(pin => {
        ctx.beginPath();
        ctx.arc(pin.x, pin.y, PIN_RADIUS, 0, Math.PI * 2);
        ctx.fillStyle = '#34495e';
        ctx.fill();
    });
}

function drawPockets() {
    for (let i = 0; i < 5; i++) {
        ctx.beginPath();
        ctx.moveTo(i * 80, canvas.height);
        ctx.lineTo(i * 80 + 40, canvas.height - 30);
        ctx.lineTo(i * 80 + 80, canvas.height);
        ctx.fillStyle = '#3498db';
        ctx.fill();
    }
}

function drawBalls() {
    balls.forEach(ball => {
        ctx.beginPath();
        ctx.arc(ball.x, ball.y, BALL_RADIUS, 0, Math.PI * 2);
        ctx.fillStyle = '#e74c3c';
        ctx.fill();
    });
}

function updateBalls() {
    balls.forEach((ball, index) => {
        ball.x += ball.dx;
        ball.y += ball.dy;
        ball.dy += 0.2; // 重力

        // 壁との衝突
        if (ball.x - BALL_RADIUS < 0 || ball.x + BALL_RADIUS > canvas.width) {
            ball.dx = -ball.dx * 0.8;
        }

        // ピンとの衝突
        pins.forEach(pin => {
            const dx = ball.x - pin.x;
            const dy = ball.y - pin.y;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < BALL_RADIUS + PIN_RADIUS) {
                const angle = Math.atan2(dy, dx);
                ball.dx = Math.cos(angle) * 2;
                ball.dy = Math.sin(angle) * 2;
            }
        });

        // 画面外に出たボールの処理
        if (ball.y > canvas.height) {
            balls.splice(index, 1);
        }
    });
}

function gameLoop() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    drawBackground();
    drawPins();
    drawPockets();
    drawBalls();
    updateBalls();
    requestAnimationFrame(gameLoop);
}

gameLoop();

$('#launch-button').click(function() {
    const launchPower = $('#power-slider').val();
    const credits = parseInt($('#credits').text());

    $.ajax({
        url: '/launch-ball',
        method: 'POST',
        data: {
            launchPower: launchPower,
            credits: credits,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                $('#credits').text(response.newCredits);
                balls.push({
                    x: canvas.width / 2,
                    y: 10,
                    dx: (Math.random() - 0.5) * launchPower / 50,
                    dy: 2 + launchPower / 25
                });
            } else {
                alert(response.message);
            }
        }
    });
});

$('#power-slider').on('input', function() {
    $('#launch-power').text($(this).val());
});
