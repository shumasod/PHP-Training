<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Syntax Corrector</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .input-section, .output-section {
            margin-bottom: 30px;
        }
        .sql-textarea {
            width: 100%;
            height: 200px;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 14px;
            resize: vertical;
        }
        .sql-textarea:focus {
            outline: none;
            border-color: #007bff;
        }
        .button-group {
            margin: 15px 0;
            text-align: center;
        }
        .btn {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .error-list {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .error-item {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #fff5f5;
            border-left: 4px solid #dc3545;
            border-radius: 0 4px 4px 0;
        }
        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            color: #155724;
        }
        .corrected-sql {
            background-color: #f8f9fa;
            border: 2px solid #28a745;
            border-radius: 4px;
            padding: 15px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            white-space: pre-wrap;
            margin-top: 15px;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .examples {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .example-sql {
            background-color: white;
            padding: 8px;
            margin: 5px 0;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .example-sql:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SQL Syntax Corrector</h1>
            <p>SQLのシンタックスエラーを自動的に検出・修正します</p>
        </div>

        <div class="input-section">
            <h3>SQLクエリを入力してください：</h3>
            <textarea id="sqlInput" class="sql-textarea" 
                placeholder="SELECT * FROM users WHERE id = 1&#10;&#10;例：&#10;SELECT FROM users&#10;INSERT INTO users VALUES&#10;UPDATE users WHERE id = 1"></textarea>
            
            <div class="button-group">
                <button class="btn btn-primary" onclick="correctSql()">エラーチェック・修正</button>
                <button class="btn btn-secondary" onclick="formatSql()">SQL整形</button>
                <button class="btn btn-secondary" onclick="clearAll()">クリア</button>
            </div>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>処理中...</p>
        </div>

        <div class="output-section" id="output" style="display: none;">
            <h3>解析結果：</h3>
            <div id="results"></div>
        </div>

        <div class="examples">
            <h4>テスト用SQL例（クリックで入力）：</h4>
            <div class="example-sql" onclick="setExample('SELECT FROM users')">SELECT FROM users</div>
            <div class="example-sql" onclick="setExample('SELECT * users WHERE id = 1')">SELECT * users WHERE id = 1</div>
            <div class="example-sql" onclick="setExample('SELECT name, FROM users')">SELECT name, FROM users</div>
            <div class="example-sql" onclick="setExample(`SELECT * FROM users WHERE name = John`)">SELECT * FROM users WHERE name = John</div>
            <div class="example-sql" onclick="setExample('INSERT INTO users VALUES')">INSERT INTO users VALUES</div>
            <div class="example-sql" onclick="setExample('UPDATE users WHERE id = 1')">UPDATE users WHERE id = 1</div>
        </div>
    </div>

    <script>
        // CSRFトークンの設定
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function correctSql() {
            const sql = document.getElementById('sqlInput').value.trim();
            
            if (!sql) {
                alert('SQLクエリを入力してください');
                return;
            }

            showLoading(true);
            
            try {
                const response = await fetch('/api/sql-corrector/correct', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ sql: sql })
                });

                const data = await response.json();
                
                if (data.success) {
                    displayResults(data.data);
                } else {
                    alert('エラー: ' + (data.error || '不明なエラーが発生しました'));
                }
            } catch (error) {
                alert('通信エラー: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        async function formatSql() {
            const sql = document.getElementById('sqlInput').value.trim();
            
            if (!sql) {
                alert('SQLクエリを入力してください');
                return;
            }

            showLoading(true);
            
            try {
                const response = await fetch('/api/sql-corrector/format', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ sql: sql })
                });

                const data = await response.json();
                
                if (data.success) {
                    displayFormatResult(data.data);
                } else {
                    alert('エラー: ' + (data.error || '不明なエラーが発生しました'));
                }
            } catch (error) {
                alert('通信エラー: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        function displayResults(data) {
            const resultsDiv = document.getElementById('results');
            let html = '';

            if (data.has_errors) {
                html += '<div class="error-list">';
                html += '<h4>検出されたエラー:</h4>';
                data.corrections.forEach(correction => {
                    html += `<div class="error-item">
                        <strong>${correction.description}</strong>
                        ${correction.suggestion ? `<br><small>提案: ${correction.suggestion}</small>` : ''}
                    </div>`;
                });
                html += '</div>';

                html += '<h4>修正されたSQL:</h4>';
                html += `<div class="corrected-sql">${escapeHtml(data.corrected_sql)}</div>`;
            } else {
                html += '<div class="success-message">';
                html += '<h4>✅ エラーは検出されませんでした！</h4>';
                html += '<p>入力されたSQLは正常な構文です。</p>';
                html += '</div>';
            }

            resultsDiv.innerHTML = html;
            document.getElementById('output').style.display = 'block';
        }

        function displayFormatResult(data) {
            const resultsDiv = document.getElementById('results');
            let html = '<h4>整形されたSQL:</h4>';
            html += `<div class="corrected-sql">${escapeHtml(data.formatted_sql)}</div>`;
            
            resultsDiv.innerHTML = html;
            document.getElementById('output').style.display = 'block';
        }

        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
            document.getElementById('output').style.display = show ? 'none' : document.getElementById('output').style.display;
        }

        function clearAll() {
            document.getElementById('sqlInput').value = '';
            document.getElementById('output').style.display = 'none';
            document.getElementById('results').innerHTML = '';
        }

        function setExample(sql) {
            document.getElementById('sqlInput').value = sql;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

