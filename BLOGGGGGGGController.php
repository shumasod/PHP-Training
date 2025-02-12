public function generatePdf(Request $request)
{
    try {
        // 文字コードをUTF-8に統一
        // ここでグッと踏ん張って文字化けを防ぎます
        $content = mb_convert_encoding(
            $request->content,
            'UTF-8',
            'ASCII,JIS,UTF-8,EUC-JP,SJIS'
        );
        
        $pdf = PDF::loadView('pdf.template', [
            'content' => $content
        ]);
        
        return $pdf->stream('document.pdf');
        
    } catch (\Exception $e) {
        // エラーが出たらログに残しておく
        // デバッグの時に役立ちます
        \Log::error('PDFでエラーが...', [
            'message' => $e->getMessage()
        ]);
        
        return response()->json([
            'error' => 'PDFの生成に失敗しました...'
        ], 500);
    }
}
