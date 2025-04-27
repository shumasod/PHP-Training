<?php
// PHPカレンダーアプリ

// タイムゾーンを設定
date_default_timezone_set('Asia/Tokyo');

// パラメータから年月を取得。指定がなければ現在の年月を使用
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// 前月と翌月の計算
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// 月の初日と最終日を取得
$first_day = mktime(0, 0, 0, $month, 1, $year);
$last_day = mktime(0, 0, 0, $month + 1, 0, $year);

// 月の日数と初日の曜日を取得
$days_in_month = date('t', $first_day);
$week_day_first = date('w', $first_day);

// 月の名前を取得
$month_name = date('F', $first_day);

// HTMLヘッダー
$html = '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPカレンダー</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .calendar {
            max-width: 800px;
            margin: 0 auto;
        }
        .month-nav {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .today {
            background-color: #e6f7ff;
            font-weight: bold;
        }
        .other-month {
            color: #ccc;
        }
        .weekend {
            color: #ff6666;
        }
    </style>
</head>
<body>
    <div class="calendar">
        <h1>' . $year . '年 ' . $month . '月</h1>
        
        <div class="month-nav">
            <a href="?year=' . $prev_year . '&month=' . $prev_month . '">&lt; 前月</a>
            <a href="?year=' . date('Y') . '&month=' . date('n') . '">今月</a>
            <a href="?year=' . $next_year . '&month=' . $next_month . '">翌月 &gt;</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th class="weekend">日</th>
                    <th>月</th>
                    <th>火</th>
                    <th>水</th>
                    <th>木</th>
                    <th>金</th>
                    <th class="weekend">土</th>
                </tr>
            </thead>
            <tbody>';

// カレンダーの日付部分を生成
$day_count = 1;
$html .= '<tr>';

// 月の最初の日までの空セルを追加
for ($i = 0; $i < $week_day_first; $i++) {
    $html .= '<td class="other-month"></td>';
}

// 月の日数分のセルを追加
for ($day = 1; $day <= $days_in_month; $day++) {
    $current_day = mktime(0, 0, 0, $month, $day, $year);
    $is_today = (date('Y-m-d') == date('Y-m-d', $current_day));
    $is_weekend = (date('w', $current_day) == 0 || date('w', $current_day) == 6);
    
    // 今日の日付にはクラスを追加
    $class = $is_today ? ' class="today"' : ($is_weekend ? ' class="weekend"' : '');
    
    $html .= '<td' . $class . '>' . $day . '</td>';
    
    // 土曜日（6）なら行を閉じて新しい行を開始
    if (date('w', $current_day) == 6 && $day < $days_in_month) {
        $html .= '</tr><tr>';
    }
}

// 月の最後の日以降の空セルを追加
$last_day_of_week = date('w', $last_day);
if ($last_day_of_week < 6) {
    for ($i = $last_day_of_week + 1; $i < 7; $i++) {
        $html .= '<td class="other-month"></td>';
    }
}

$html .= '</tr>';
$html .= '</tbody></table>';

// イベント追加フォーム（オプション）
$html .= '
        <div style="margin-top: 20px;">
            <h3>イベント追加（開発中）</h3>
            <form action="#" method="post">
                <div style="margin-bottom: 10px;">
                    <label for="event_date">日付:</label>
                    <input type="date" id="event_date" name="event_date" required>
                </div>
                <div style="margin-bottom: 10px;">
                    <label for="event_title">タイトル:</label>
                    <input type="text" id="event_title" name="event_title" required>
                </div>
                <div style="margin-bottom: 10px;">
                    <label for="event_description">詳細:</label>
                    <textarea id="event_description" name="event_description" rows="3"></textarea>
                </div>
                <button type="submit">保存</button>
            </form>
        </div>
    </div>
</body>
</html>';

// HTML出力
echo $html;
?>
