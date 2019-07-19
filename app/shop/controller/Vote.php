<?php

namespace app\shop\controller;

/**
 * 调查程序
 */
class Vote extends Init
{
    public function index()
    {
        if (!isset($_REQUEST['vote']) || !isset($_REQUEST['options']) || !isset($_REQUEST['type'])) {
            return $this->redirect('/');

        }

        $res = array('error' => 0, 'message' => '', 'content' => '');

        $vote_id = intval($_POST['vote']);
        $options = trim($_POST['options']);
        $type = intval($_POST['type']);
        $ip_address = real_ip();

        if ($this->vote_already_submited($vote_id, $ip_address)) {
            $res['error'] = 1;
            $res['message'] = $GLOBALS['_LANG']['vote_ip_same'];
        } else {
            $this->save_vote($vote_id, $ip_address, $options);

            $vote = get_vote($vote_id);
            if (!empty($vote)) {
                $GLOBALS['smarty']->assign('vote_id', $vote['id']);
                $GLOBALS['smarty']->assign('vote', $vote['content']);
            }

            $str = $GLOBALS['smarty']->fetch("library/vote.lbi");

            $pattern = '/(?:<(\w+)[^>]*> .*?)?<div\s+id="ECS_VOTE">(.*)<\/div>(?:.*?<\/\1>)?/is';

            if (preg_match($pattern, $str, $match)) {
                $res['content'] = $match[2];
            }
            $res['message'] = $GLOBALS['_LANG']['vote_success'];
        }


        echo json_encode($res);
    }

    /**
     * 检查是否已经提交过投票
     *
     * @access  private
     * @param integer $vote_id
     * @param string $ip_address
     * @return  boolean
     */
    private function vote_already_submited($vote_id, $ip_address)
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('vote_log') . " " .
            "WHERE ip_address = '$ip_address' AND vote_id = '$vote_id' ";

        return ($GLOBALS['db']->GetOne($sql) > 0);
    }

    /**
     * 保存投票结果信息
     *
     * @access  public
     * @param integer $vote_id
     * @param string $ip_address
     * @param string $option_id
     * @return  void
     */
    private function save_vote($vote_id, $ip_address, $option_id)
    {
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('vote_log') . " (vote_id, ip_address, vote_time) " .
            "VALUES ('$vote_id', '$ip_address', " . gmtime() . ")";
        $res = $GLOBALS['db']->query($sql);

        /* 更新投票主题的数量 */
        $sql = "UPDATE " . $GLOBALS['ecs']->table('vote') . " SET " .
            "vote_count = vote_count + 1 " .
            "WHERE vote_id = '$vote_id'";
        $GLOBALS['db']->query($sql);

        /* 更新投票选项的数量 */
        $sql = "UPDATE " . $GLOBALS['ecs']->table('vote_option') . " SET " .
            "option_count = option_count + 1 " .
            "WHERE " . db_create_in($option_id, 'option_id');
        $GLOBALS['db']->query($sql);
    }
}
