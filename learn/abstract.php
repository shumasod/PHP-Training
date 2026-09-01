<?php

declare(strict_types=1);

/**
 * 抽象クラスの練習。
 *
 * 修正前は abstract class ProductAbstract を宣言したあと、それを継承する
 * クラスが 1 つも無く、代わりに learn_1/extends.php の BaseProduct /
 * Product をそのまま複製していた。
 * つまり「抽象クラスの教材」でありながら、抽象クラスが一度も使われて
 * いなかった。継承する具象クラスを用意して、抽象クラスが何を強制するのかを
 * 実際に確かめられるようにした。
 *
 * 通常の継承 (extends) との違いは learn_1/extends.php を参照。
 */

/**
 * 抽象クラス。
 *
 * - それ自体をインスタンス化できない
 * - abstract を付けたメソッドは、継承先が必ず実装しなければならない
 * - 実装済みのメソッドを持つこともできる（インタフェースとの違い）
 */
abstract class ProductAbstract
{
    // 実装済みのメソッド。継承先はそのまま使える。
    public function echoProduct(): void
    {
        echo '親クラスです';
    }

    // 抽象メソッド。本体を書かない。
    // 継承先が実装しないと、その時点で Fatal error になる。
    abstract public function getProduct(): void;
}

/**
 * 抽象クラスを継承した具象クラス。
 */
class Product extends ProductAbstract
{
    // アクセス修飾子
    //   private   : 自分のクラスのみ
    //   protected : 自分と、継承したクラス
    //   public    : どこからでも
    //
    // 修正前のコメントは public に「自分・継承したクラス」という
    // 説明が付いていたが、それは protected の説明。
    private string $product;

    public function __construct(string $product)
    {
        $this->product = $product;
    }

    // abstract メソッドの実装。これが無いと Fatal error。
    public function getProduct(): void
    {
        // 画面へ出す値は必ずエスケープする。
        echo htmlspecialchars($this->product, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function addProduct(string $item): void
    {
        $this->product .= $item;
    }

    public static function getStaticProduct(string $str): void
    {
        echo htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// 使用例
//
// require しただけで実行されないよう、CLI から直接実行したときだけ動かす。
// 修正前はファイルスコープに置かれていたため、クラスを使いたいだけの
// include でも var_dump の出力が混ざっていた。
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $instance = new Product('テスト');

    $instance->getProduct();
    echo PHP_EOL;

    // 親クラス（抽象クラス）の実装済みメソッド
    $instance->echoProduct();
    echo PHP_EOL;

    $instance->addProduct('追加分');
    $instance->getProduct();
    echo PHP_EOL;

    Product::getStaticProduct('静的');
    echo PHP_EOL;

    // 抽象クラスは直接インスタンス化できない
    try {
        // @phpstan-ignore-next-line 意図的なエラーの実演
        new ProductAbstract();
    } catch (Error $e) {
        echo 'エラー: ' . $e->getMessage() . PHP_EOL;
    }
}
