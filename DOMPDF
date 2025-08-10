<?php

/**
 * DOMPDF設定ファイル
 * 
 * 日本語PDF生成に最適化された設定
 * セキュリティとパフォーマンスを考慮した構成
 */

return [
    // DOMPDFの基本設定
    'default_paper_size' => env('PDF_DEFAULT_PAPER_SIZE', 'A4'),
    'default_paper_orientation' => env('PDF_DEFAULT_ORIENTATION', 'portrait'), // 'portrait'または'landscape'
    'default_font' => env('PDF_DEFAULT_FONT', 'ipag'),
    
    // セキュリティ設定（重要）
    'enable_php' => false, // セキュリティリスクのため必ずfalse
    'enable_remote' => env('PDF_ENABLE_REMOTE', false), // 本番環境ではfalse推奨
    'enable_javascript' => env('PDF_ENABLE_JAVASCRIPT', false), // 必要な場合のみtrue
    'enable_html5_parser' => true,
    
    // キャッシュ設定
    'enable_font_subsetting' => true, // フォントファイルサイズ削減
    'font_cache' => storage_path('app/pdf/fonts/'),
    'font_dir' => storage_path('app/pdf/fonts/'),
    'temp_dir' => storage_path('app/pdf/temp/'),
    
    // デバッグ設定（環境別）
    'debug_png' => env('PDF_DEBUG_PNG', false),
    'debug_layout' => env('PDF_DEBUG_LAYOUT', false),
    'debug_css' => env('PDF_DEBUG_CSS', false),
    'debug_layout_lines' => env('PDF_DEBUG_LAYOUT_LINES', false),
    'debug_layout_blocks' => env('PDF_DEBUG_LAYOUT_BLOCKS', false),
    'debug_layout_inline' => env('PDF_DEBUG_LAYOUT_INLINE', false),
    'debug_layout_padding_box' => env('PDF_DEBUG_LAYOUT_PADDING_BOX', false),
    
    // PDFのメタデータ設定
    'pdf_metadata' => [
        'title' => env('PDF_DEFAULT_TITLE', ''),
        'author' => env('PDF_DEFAULT_AUTHOR', ''),
        'subject' => env('PDF_DEFAULT_SUBJECT', ''),
        'keywords' => env('PDF_DEFAULT_KEYWORDS', ''),
        'creator' => env('PDF_CREATOR', 'Laravel DOMPDF'),
        'creation_date' => null, // 動的に設定される
    ],
    
    // フォント設定（日本語対応強化）
    'fonts' => [
        // 欧文フォント
        'sans-serif' => [
            'normal' => 'Helvetica',
            'bold' => 'Helvetica-Bold',
            'italic' => 'Helvetica-Oblique',
            'bold_italic' => 'Helvetica-BoldOblique'
        ],
        'serif' => [
            'normal' => 'Times-Roman',
            'bold' => 'Times-Bold',
            'italic' => 'Times-Italic',
            'bold_italic' => 'Times-BoldItalic'
        ],
        'monospace' => [
            'normal' => 'Courier',
            'bold' => 'Courier-Bold',
            'italic' => 'Courier-Oblique',
            'bold_italic' => 'Courier-BoldOblique'
        ],
        
        // 日本語フォント設定
        'ipag' => [
            'normal' => 'ipag.ttf',
            'bold' => 'ipag.ttf',
            'italic' => 'ipag.ttf',
            'bold_italic' => 'ipag.ttf'
        ],
        'ipagp' => [
            'normal' => 'ipagp.ttf',
            'bold' => 'ipagp.ttf',
            'italic' => 'ipagp.ttf',
            'bold_italic' => 'ipagp.ttf'
        ],
        'ipam' => [
            'normal' => 'ipam.ttf',
            'bold' => 'ipam.ttf',
            'italic' => 'ipam.ttf',
            'bold_italic' => 'ipam.ttf'
        ],
        'ipamp' => [
            'normal' => 'ipamp.ttf',
            'bold' => 'ipamp.ttf',
            'italic' => 'ipamp.ttf',
            'bold_italic' => 'ipamp.ttf'
        ],
        
        // Noto Fonts（Google製の高品質日本語フォント）
        'notosanscjkjp' => [
            'normal' => 'NotoSansCJKjp-Regular.otf',
            'bold' => 'NotoSansCJKjp-Bold.otf',
            'italic' => 'NotoSansCJKjp-Regular.otf',
            'bold_italic' => 'NotoSansCJKjp-Bold.otf'
        ],
        'notoserifcjkjp' => [
            'normal' => 'NotoSerifCJKjp-Regular.otf',
            'bold' => 'NotoSerifCJKjp-Bold.otf',
            'italic' => 'NotoSerifCJKjp-Regular.otf',
            'bold_italic' => 'NotoSerifCJKjp-Bold.otf'
        ]
    ],
    
    // フォントファミリーのエイリアス設定
    'font_family_aliases' => [
        // 日本語フォントエイリアス
        'japanese' => 'ipag',
        'japanese-gothic' => 'ipagp',
        'japanese-mincho' => 'ipam',
        'jp-gothic' => 'ipagp',
        'jp-mincho' => 'ipam',
        
        // Noto Fontsエイリアス
        'noto-sans-jp' => 'notosanscjkjp',
        'noto-serif-jp' => 'notoserifcjkjp',
        
        // 一般的なエイリアス
        'gothic' => 'ipagp',
        'mincho' => 'ipam',
        'sans-serif-jp' => 'ipag',
        'serif-jp' => 'ipam'
    ],
    
    // 文字コード設定
    'charset_encoding' => 'UTF-8',
    'default_encoding' => 'UTF-8',
    
    // レンダリング設定
    'dpi' => env('PDF_DPI', 96),
    'img_dpi' => env('PDF_IMG_DPI', 96),
    'font_height_ratio' => 1.1, // 行間調整
    'is_remote_enabled' => env('PDF_ENABLE_REMOTE', false),
    
    // パフォーマンス設定
    'font_size' => env('PDF_DEFAULT_FONT_SIZE', 12),
    'line_height' => env('PDF_DEFAULT_LINE_HEIGHT', 1.4),
    
    // メモリ設定
    'memory_limit' => env('PDF_MEMORY_LIMIT', '512M'),
    'time_limit' => env('PDF_TIME_LIMIT', 300),
    
    // CSS設定
    'css_style_sheet_limit' => 4096,
    'css_rules_limit' => 8192,
    
    // 画像設定
    'enable_css_float' => true,
    'enable_inline_css' => true,
    
    // 出力設定
    'compress' => env('PDF_COMPRESS', true),
    'attachment' => env('PDF_ATTACHMENT', false),
    
    // ログ設定
    'log_output_file' => storage_path('logs/dompdf.log'),
    'enable_log' => env('PDF_ENABLE_LOG', false),
    
    // 互換性設定
    'quirks_mode' => false,
    'default_media_type' => 'screen',
    
    // 警告とエラー設定
    'chroot' => '', // セキュリティのため空文字列を推奨
    'protocol_whitelist' => ['file://', 'http://', 'https://'],
    
    // 日本語特有の設定
    'japanese_font_fallback' => 'ipag', // 日本語文字が見つからない場合のフォールバック
    'enable_japanese_line_break' => true, // 日本語改行処理
    
    // 追加のセキュリティ設定
    'allowed_protocols' => [
        'file://' => ['rules' => []],
        'http://' => ['rules' => []],
        'https://' => ['rules' => []]
    ],
    
    // カスタム設定（アプリケーション固有）
    'custom' => [
        'default_margins' => [
            'top' => '20mm',
            'right' => '15mm',
            'bottom' => '20mm',
            'left' => '15mm'
        ],
        'header_footer' => [
            'enable' => false,
            'font_size' => 9,
            'font_family' => 'ipag'
        ]
    ]
];