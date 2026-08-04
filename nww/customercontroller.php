<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /** 1 ページあたりの件数 */
    private const PER_PAGE = 20;

    public function index(): View
    {
        // Customer::all() は全件をメモリに載せる。件数が増えるとそのまま
        // メモリ枯渇につながるのでページネーションを使う。
        $customers = Customer::query()
            ->orderBy('id')
            ->paginate(self::PER_PAGE);

        return view('dashboard', compact('customers'));
    }

    public function search(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($search): void {
                $pattern = '%' . $this->escapeLike($search) . '%';

                // orWhere をトップレベルに置くと、他の絞り込み条件
                // (論理削除やテナント条件など) を OR がまたいで
                // 打ち消してしまう。検索条件はグループにまとめる。
                $query->where(function ($query) use ($pattern): void {
                    $query->where('name', 'like', $pattern)
                        ->orWhere('email', 'like', $pattern);
                });
            })
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('dashboard', compact('customers'));
    }

    /**
     * LIKE のワイルドカードをエスケープする。
     *
     * バインドは SQL インジェクションを防ぐが、値の中の % と _ は
     * ワイルドカードとして解釈されたままになる。
     * 例えば search=% を送ると全件がヒットし、
     * %a%b%c%d%e%... のような入力は前方一致が効かず全表スキャンになる
     * （ReDoS ならぬ LIKE による DoS）。
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
