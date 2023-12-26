<?php

// 抽象クラス
abstract class ProductAbstract {
    // 変数 関数
    public function echoProduct() {
        echo '親クラスです';
    }

    // オーバーライド（上書き）
    abstract public function getProduct();
}

// 具象クラス、親クラス・基底クラス・スーパークラス
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

    // アクセス修飾子、private, protected, public(自分・継承したクラス), 

    // 変数
    private $product = '';

    // 関数
    function __construct($product) {
        $this->product = $product;
    }

    public function getProduct() {
        echo $this->product;
    }

    public function addProduct($item) {
        $this->product .= $item;
    }

    public static function getStaticProduct($str) {
        echo $str;
    }
}

$instance = new Product('テスト');

var_dump($instance);

$instance->getProduct();
echo '<br>';

// 親クラスのメソッド
$instance->echoProduct();
echo '<br>';

$instance->addProduct('追加分');
echo '<br>';

$instance->getProduct();
echo '<br>';

Product::getStaticProduct('静的');
echo '<br>';
