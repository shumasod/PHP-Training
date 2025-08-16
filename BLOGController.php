public function generatePdf(Request $request)
{
    try {
        // 入力検証
        $request->validate([
            'content' => 'required|string|max:50000', // 50KB程度まで
            'filename' => 'nullable|string|max:255',
            'orientation' => 'nullable|in:portrait,landscape'
        ]);

        // ファイル名のサニタイズ
        $filename = $this->sanitizeFilename(
            $request->input('filename', 'document')
        );

        // 文字コードをUTF-8に統一（より安全な方法）
        $content = $this->normalizeEncoding($request->input('content'));

        // 空のコンテンツをチェック
        if (empty(trim($content))) {
            return response()->json([
                'error' => 'コンテンツが空です'
            ], 422);
        }

        // PDF設定
        $orientation = $request->input('orientation', 'portrait');
        
        // PDFを生成
        $pdf = PDF::loadView('pdf.template', [
            'content' => $content,
            'title' => $filename
        ])->setPaper('A4', $orientation)
          ->setOptions([
              'isHtml5ParserEnabled' => true,
              'isPhpEnabled' => true,
              'defaultFont' => 'DejaVu Sans'
          ]);

        // レスポンスヘッダーを設定
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];

        return response($pdf->output(), 200, $headers);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // バリデーションエラー
        return response()->json([
            'error' => '入力データが無効です',
            'details' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        // 詳細なエラーログ
        \Log::error('PDF生成エラー', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id() ?? 'guest',
            'request_data' => $request->only(['filename', 'orientation'])
        ]);

        // 本番環境では詳細なエラー情報を隠す
        $errorMessage = app()->environment('production') 
            ? 'PDFの生成に失敗しました' 
            : 'PDF生成エラー: ' . $e->getMessage();

        return response()->json([
            'error' => $errorMessage
        ], 500);
    }
}

/**
 * 文字エンコーディングを安全に正規化
 */
private function normalizeEncoding(string $content): string
{
    // 現在のエンコーディングを検出
    $detected = mb_detect_encoding(
        $content, 
        ['UTF-8', 'SJIS', 'EUC-JP', 'JIS', 'ASCII'], 
        true
    );

    // UTF-8でない場合は変換
    if ($detected && $detected !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $detected);
    }

    // 無効な文字を除去
    $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
    
    return $content;
}

/**
 * ファイル名をサニタイズ
 */
private function sanitizeFilename(string $filename): string
{
    // 危険な文字を除去
    $filename = preg_replace('/[^\w\-_.()]/', '', $filename);
    
    // 長さ制限
    $filename = substr($filename, 0, 50);
    
    // 空の場合はデフォルト名
    return empty($filename) ? 'document' : $filename;
}
