<?php
// ====================================================================
// 1. 問題のあった元のコード（エラー発生版）
// ====================================================================

/**
 * UserController.php - 問題が発生していた元のコード
 * TypeError: Argument #2 ($id) must be of type int, null given
 */
class UserController extends CI_Controller
{
    public function getUserOptions()
    {
        // セッションからユーザーIDを取得（ここでnullが返される可能性）
        $userId = $_SESSION['user_id'] ?? null;
        
        // 型宣言があるメソッドにnullを直接渡してしまう
        $options = $this->OptionService_model->getUserDetail('param1', $userId);
        
        // 結果を返す
        return $this->output->set_content_type('application/json')
                          ->set_output(json_encode($options));
    }
}
