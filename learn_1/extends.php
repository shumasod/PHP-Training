<?php

declare(strict_types=1);

/**
 * 継承 (extends) の練習。
 *
 * 抽象クラス (abstract) との違いは learn/abstract.php を参照。
 * こちらの BaseProduct は単独でもインスタンス化できる通常のクラス。
 */

// 親クラス
class BaseProduct {
    // 変数 関数
    public function echoProduct() {
        echo '親クラスです';
    }

    // オーバーライド（上書き）
    public function getProduct() {
        echo '親の関数です';
    }
}

// 子クラス
class Product extends BaseProduct {

    // アクセス修飾子
    //   private   : 自分のクラスのみ
    //   protected : 自分と、継承したクラス
    //   public    : どこからでも

    // 変数
    private $product = '';

    // 関数
    function __construct($product) {
        $this->product = $product;
    }

    public function getProduct() {
        // 画面へ出す値は必ずエスケープする。
        echo htmlspecialchars($this->product, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function addProduct($item) {
        $this->product .= $item;
    }

    public static function getStaticProduct($str) {
        echo htmlspecialchars((string) $str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

    // 親クラスのメソッド
    $instance->echoProduct();
    echo PHP_EOL;

    $instance->addProduct('追加分');
    $instance->getProduct();
    echo PHP_EOL;

    Product::getStaticProduct('静的');
    echo PHP_EOL;
}
