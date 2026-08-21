<?php

declare(strict_types=1);

/**
 * ログ出力の薄いラッパー。
 *
 * InputValidator と SessionManager は「どこでも使える汎用クラス」として
 * 書かれているのに、中で CodeIgniter のグローバル関数 log_message() を
 * 直接呼んでいた。CodeIgniter 以外の環境では
 *
 *     Error: Call to undefined function log_message()
 *
 * で落ちる。しかも呼ばれるのは「バリデーション失敗」「セッション
 * タイムアウト」といった例外的な経路なので、正常系のテストでは
 * 気付かず、本番で異常が起きた瞬間に別のエラーへすり替わる。
 *
 * 利用できる出力先を実行時に選び、無ければ error_log() へ落とす。
 */
final class Logger
{
    /**
     * @param string $level 'info' | 'warning' | 'error' | 'debug'
     */
    public static function write(string $level, string $message): void
    {
        // CodeIgniter 環境ならそのログ機構に乗せる
        if (function_exists('log_message')) {
            log_message($level, $message);
            return;
        }

        // Laravel 環境なら Log ファサードに乗せる
        if (class_exists(\Illuminate\Support\Facades\Log::class)) {
            \Illuminate\Support\Facades\Log::log($level, $message);
            return;
        }

        // どちらでもなければ PHP 標準のエラーログへ
        error_log(sprintf('[%s] %s', strtoupper($level), $message));
    }

    public static function info(string $message): void
    {
        self::write('info', $message);
    }

    public static function warning(string $message): void
    {
        self::write('warning', $message);
    }

    public static function error(string $message): void
    {
        self::write('error', $message);
    }
}
