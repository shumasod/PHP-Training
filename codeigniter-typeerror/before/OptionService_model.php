<?php

/**
 * OptionService_model.php - 問題が発生していた元のコード
 */
class OptionService_model extends CI_Model
{
    // 厳密な型宣言により、nullが渡されるとTypeError発生
    public function getUserDetail($param1, int $id)
    {
        $query = $this->db->get_where('user_options', ['user_id' => $id]);
        return $query->result_array();
    }
}
