use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::get('/credits', function () {
        return response()->json(['credits' => 1000]);  // 初期クレジット
    });

    Route::post('/update-credits', function (Request $request) {
        $credits = $request->input('credits');
        // クレジットの更新処理をここに記述
        return response()->json(['success' => true, 'newCredits' => $credits]);
    });
});