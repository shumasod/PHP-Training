<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * storage/app/import.sql を流し込むマイグレーション。
 *
 * 修正前は create_table2.php というファイル名で class ImportSqlData を
 * 定義していた。Laravel はマイグレーションを
 *
 *     YYYY_MM_DD_HHMMSS_名前.php
 *
 * というファイル名で認識し、実行順もこの名前で決める。
 * 規約から外れた名前だと migrate に拾われない。
 *
 * Laravel 8 以降はクラス名を書かず無名クラスを返す形が推奨。
 * クラス名を付けると、他のマイグレーションと名前が衝突したときに
 * Cannot declare class で落ちる。
 */
return new class extends Migration
{
    public function up(): void
    {
        $filePath = storage_path('app/import.sql');

        if (!File::exists($filePath)) {
            throw new RuntimeException("SQLファイルが見つかりません: {$filePath}");
        }

        $sql = File::get($filePath);

        // 注意:
        //
        // DB::unprepared() はファイルの中身をそのままサーバへ送る。
        // プレースホルダを使わないため、import.sql の内容は
        // 完全に信用できるものに限ること。
        // アップロードされたファイルや、利用者が触れる場所に置かれた
        // ファイルをここへ渡してはいけない。
        //
        // また MySQL では CREATE TABLE / ALTER TABLE などの DDL が
        // 暗黙にコミットを起こすため、DDL を含む SQL では
        // このトランザクションは巻き戻せない。
        // それでも DML だけのファイルでは有効なので囲っておく。
        DB::transaction(function () use ($sql): void {
            DB::unprepared($sql);
        });
    }

    public function down(): void
    {
        // このマイグレーションは巻き戻せない。
        //
        // 修正前は down() が空だった。空だと migrate:rollback が
        // 「成功した」と見なして migrations テーブルから記録を消すため、
        // データは残ったまま「未適用」の状態になり、
        // 次の migrate で同じ SQL がもう一度流れる。
        //
        // 巻き戻せないことを明示して止める。
        throw new RuntimeException(
            'import.sql の取り込みは自動では巻き戻せません。手動で対応してください。'
        );
    }
};
