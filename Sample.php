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
        $this->storageFile = __DIR__ . '/messages.json';
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
            'ip' => $_SERVER['REMOTE_ADDR']
        ];

        array_unshift($this->messages, $newMessage);
        $this->messages = array_slice($this->messages, 0, MAX_MESSAGES);

        return file_put_contents($this->storageFile, json_encode($this->messages)) !== false;
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

        $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
        $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING));
        
        $errors = validateInput($name, $message);
        
        if (empty($errors)) {
            if ($storage->saveMessage($name, $message)) {
                $_SESSION['flash_message'] = 'Message posted successfully!';
                header('Location: ' . htmlspecialchars($_SERVER['PHP_SELF']));
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
