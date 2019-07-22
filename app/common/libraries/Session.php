<?php

namespace app\common\libraries;

/**
 * SESSION 公用类库
 */
class Session
{
    public function delete_spec_admin_session($adminid)
    {
        if (!empty($GLOBALS['_SESSION']['admin_id']) && $adminid) {
            return $this->db->query('DELETE FROM ' . $this->session_table . " WHERE adminid = '$adminid'");
        } else {
            return false;
        }
    }

    public function destroy_session()
    {
        /* ECSHOP 自定义执行部分 */
        if (!empty($GLOBALS['ecs'])) {
            $this->db->query('DELETE FROM ' . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '$this->session_id' AND user_id='0'");
        }
        /* ECSHOP 自定义执行部分 */

        $this->db->query('DELETE FROM ' . $this->session_data_table . " WHERE sesskey = '" . $this->session_id . "' LIMIT 1");

        return $this->db->query('DELETE FROM ' . $this->session_table . " WHERE sesskey = '" . $this->session_id . "' LIMIT 1");
    }

    public function get_session_id()
    {
        return $this->session_id;
    }

    public function get_users_count()
    {
        return $this->db->getOne('SELECT count(*) FROM ' . $this->session_table);
    }
}
