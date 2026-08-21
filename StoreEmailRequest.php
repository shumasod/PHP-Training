<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * トレイトの最小構成の使用例。
 *
 * 修正前は StoreRequest.php / StoreRequest1.php / StoreRequest2.php の
 * 3 ファイルがすべて `class StoreRequest` を定義していた。
 * 同時に読み込むと
 *   Fatal error: Cannot declare class StoreRequest, because the name is already in use
 * になるため、それぞれ役割の分かる名前へ変更した。
 */
class StoreEmailRequest extends FormRequest
{
    use FormRequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => $this->nullableEmailRule(),
        ];
    }
}
