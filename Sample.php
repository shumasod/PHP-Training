<?php
declare(strict_types=1);

session_start();

// 設定
const MAX_MESSAGES = 100;
const MAX_NAME_LENGTH = 50;
const MAX_MESSAGE_LENGTH = 1000;

// データベース接続のシミュレーション（実際の実装ではデータベースを使用）
class MessageStorage {
    private array $messages = [];
    private string $storageFile;

    public function __construct() {
        // 保存先はドキュメントルートの外に置く。
        //
        // 修正前は __DIR__ . '/messages.json' で、このスクリプトと
        // 同じ公開ディレクトリに置かれていた。つまり
        //     https://example.com/messages.json
        // を開くだけで全投稿が読める。しかも各レコードには投稿者の
        // IP アドレスが入っているため、単なる書き込み内容の露出では
        // 済まない。
        //
        // 公開ディレクトリ外に置けない環境では、最低限
        // .htaccess や nginx の location で拒否すること。
        $dataDir = getenv('GUESTBOOK_DATA_DIR') ?: dirname(__DIR__) . '/storage';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0700, true);
        }
        $this->storageFile = $dataDir . '/messages.json';
        $this->loadMessages();
    }

    private function loadMessages(): void {
        if (file_exists($this->storageFile)) {
            $content = file_get_contents($this->storageFile);
            if ($content !== false) {
                $this->messages = json_decode($content, true) ?? [];
            }
        }
    }

    public function saveMessage(string $name, string $message): bool {
        $newMessage = [
            'id' => uniqid('msg_', true),
            'name' => $name,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            // IP アドレスは個人情報にあたる。荒らし対策で残す場合でも
            // 生値ではなくソルト付きハッシュにしておけば、
            // 「同一人物かどうか」の判定はできる。
            'ip_hash' => hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', self::ipHashKey()),
        ];

        // 読み込み → 追加 → 書き込みを排他ロックの中で行う。
        //
        // 修正前はコンストラクタで読んだ $this->messages に追記して
        // 書き戻していた。2 人が同時に投稿すると、後から書いた側が
        // 先に書かれた投稿を含まない配列で丸ごと上書きするため、
        // 投稿が黙って消える。
        $handle = fopen($this->storageFile, 'c+');
        if ($handle === false) {
            return false;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }

            $content = stream_get_contents($handle);
            $messages = $content !== false && $content !== ''
                ? (json_decode($content, true) ?? [])
                : [];

            array_unshift($messages, $newMessage);
            $messages = array_slice($messages, 0, MAX_MESSAGES);

            $json = json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                return false;
            }

            rewind($handle);
            ftruncate($handle, 0);
            if (fwrite($handle, $json) === false) {
                return false;
            }
            fflush($handle);

            $this->messages = $messages;

            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * IP ハッシュ用の鍵。
     *
     * 環境変数が無い場合はデータディレクトリに 1 度だけ生成して保存する。
     * 固定文字列を鍵にすると、IP の総当たり（IPv4 は約 43 億通り）で
     * ハッシュから元の IP を復元できてしまうため。
     */
    private static function ipHashKey(): string {
        static $key = null;
        if ($key !== null) {
            return $key;
        }

        $fromEnv = getenv('GUESTBOOK_IP_HASH_KEY');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $key = $fromEnv;
        }

        $dataDir = getenv('GUESTBOOK_DATA_DIR') ?: dirname(__DIR__) . '/storage';
        $keyFile = $dataDir . '/ip_hash.key';

        if (is_readable($keyFile)) {
            return $key = (string) file_get_contents($keyFile);
        }

        $key = bin2hex(random_bytes(32));
        file_put_contents($keyFile, $key, LOCK_EX);
        chmod($keyFile, 0600);

        return $key;
    }

    public function getMessages(): array {
        return $this->messages;
    }
}

// CSRFトークンの生成と検証
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

// 入力値の検証
function validateInput(string $name, string $message): array {
    $errors = [];
    
    if (empty($name) || mb_strlen($name) > MAX_NAME_LENGTH) {
        $errors[] = "Name must be between 1 and " . MAX_NAME_LENGTH . " characters.";
    }
    
    if (empty($message) || mb_strlen($message) > MAX_MESSAGE_LENGTH) {
        $errors[] = "Message must be between 1 and " . MAX_MESSAGE_LENGTH . " characters.";
    }
    
    return $errors;
}

$storage = new MessageStorage();
$errors = [];
$success = false;

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
            throw new Exception('Invalid CSRF token.');
        }

        // FILTER_SANITIZE_STRING は PHP 8.1 で非推奨になった。
        //
        //     PHP Deprecated: Constant FILTER_SANITIZE_STRING is deprecated
        //
        // 挙動も直感的でなく、タグを削り引用符を実体参照に変えるため、
        // 「入力を壊すが XSS は防げない」という中途半端な結果になる。
        // 入力はそのまま保持し、出力時に htmlspecialchars() でエスケープする
        // （このファイルの表示側は既にそうなっている）。
        //
        // filter_input() は値が無いと null を返す。PHP 8.1 以降
        // trim(null) は Deprecated になるので (string) を挟む。
        $name = trim((string) ($_POST['name'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        
        $errors = validateInput($name, $message);
        
        if (empty($errors)) {
            if ($storage->saveMessage($name, $message)) {
                $_SESSION['flash_message'] = 'Message posted successfully!';

                // リダイレクト先に $_SERVER['PHP_SELF'] を使わない。
                // PHP_SELF には PATH_INFO が含まれるため、
                //   /Sample.php/<攻撃者が決めた文字列>
                // でアクセスされると、その文字列が Location に載る。
                // htmlspecialchars() は HTML 用のエスケープであって
                // URL には効かないので対策にもなっていない。
                // 自分自身へ戻すだけなので固定文字列でよい。
                header('Location: ' . basename(__FILE__), true, 303);
                exit;
            } else {
                throw new Exception('Failed to save message.');
            }
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// フラッシュメッセージの処理
$flashMessage = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guestbook</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Guestbook</h1>

        <?php if (!empty($flashMessage)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($flashMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                    Name:
                </label>
                <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    maxlength="<?= MAX_NAME_LENGTH ?>"
                    required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                >
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="message">
                    Message:
                </label>
                <textarea 
                    name="message" 
                    id="message" 
                    rows="4" 
                    maxlength="<?= MAX_MESSAGE_LENGTH ?>"
                    required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>

            <div class="flex items-center justify-between">
                <button 
                    type="submit" 
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                >
                    Submit
                </button>
            </div>
        </form>

        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8">
            <h2 class="text-xl font-bold mb-4">Messages</h2>
            <?php if ($messages = $storage->getMessages()): ?>
                <div class="space-y-4">
                    <?php foreach ($messages as $msg): ?>
                        <div class="border-b pb-4">
                            <div class="font-bold"><?= htmlspecialchars($msg['name']) ?></div>
                            <div class="text-gray-700"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                            <div class="text-sm text-gray-500 mt-1">
                                Posted on: <?= htmlspecialchars($msg['created_at']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-700">No messages yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
