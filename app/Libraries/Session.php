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
        if (!empty($GLOBALS['ecs'])) {
            $this->db->query('DELETE FROM ' . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '" . $this->get_session_id() . "' AND user_id='0'");
        }

        return session(null);
    }

    public function get_session_id()
    {
        return session_id();
    }

    public function get_users_count()
    {
        return 0; // TODO BY LANCE
    }
}
