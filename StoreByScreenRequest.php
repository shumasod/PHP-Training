<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 画面（ルート名）ごとにバリデーションルールを切り替える例。
 *
 * FormRequestTrait::rules() が validationContext を見て
 * rulesForXxx() を呼び分ける仕組みも用意してあるので、
 * そちらを使うなら prepareForValidation() で
 * setValidationContext() を呼ぶ形になる。
 */
class StoreByScreenRequest extends FormRequest
{
    use FormRequestTrait;

    /**
     * ルート名とコンテキストの対応。
     *
     * 修正前は if / elseif を並べたうえで == による緩い比較を使い、
     * どのルートにも当てはまらない場合に空配列を返していた。
     * 空配列は「検証すべき項目が無い」という意味になるため、
     * ルート名を 1 つ書き間違えるだけで、その画面の入力チェックが
     * まるごと無効になる。
     *
     * 対応表 + match にして、未知のルート名は例外で気付けるようにした。
     */
    private const SCREEN_ROUTES = [
        'screen_a_route_name' => 'ScreenA',
        'screen_b_route_name' => 'ScreenB',
        'screen_c_route_name' => 'ScreenC',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routeName = $this->route()?->getName();
        $screen = self::SCREEN_ROUTES[$routeName] ?? null;

        if ($screen === null) {
            // 想定外のルートから使われた場合は、共通ルール +
            // デフォルトルールで検証する（無検証にはしない）。
            return $this->mergeCommonRules($this->rulesForDefault());
        }

        return $this->mergeCommonRules($this->{'rulesFor' . $screen}());
    }
}
