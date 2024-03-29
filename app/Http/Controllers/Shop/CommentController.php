<?php

namespace app\shop\controller;

/**
 * 提交用户评论
 */
class Comment extends Init
{
    public function index()
    {
        if (!isset($_REQUEST['cmt']) && !isset($_REQUEST['act'])) {
            /* 只有在没有提交评论内容以及没有act的情况下才跳转 */
            return $this->redirect('/');

        }
        $_REQUEST['cmt'] = isset($_REQUEST['cmt']) ? json_str_iconv($_REQUEST['cmt']) : '';

        $result = array('error' => 0, 'message' => '', 'content' => '');

        if (empty($_REQUEST['act'])) {
            /*
             * act 参数为空
             * 默认为添加评论内容
             */
            $cmt = json_decode($_REQUEST['cmt']);
            $cmt->page = 1;
            $cmt->id = !empty($cmt->id) ? intval($cmt->id) : 0;
            $cmt->type = !empty($cmt->type) ? intval($cmt->type) : 0;

            if (empty($cmt) || !isset($cmt->type) || !isset($cmt->id)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['invalid_comments'];
            } elseif (!is_email($cmt->email)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['error_email'];
            } else {
                if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
                    /* 检查验证码 */

                    $validator = new captcha();
                    if (!$validator->check_word($cmt->captcha)) {
                        $result['error'] = 1;
                        $result['message'] = $GLOBALS['_LANG']['invalid_captcha'];
                    } else {
                        $factor = intval($GLOBALS['_CFG']['comment_factor']);
                        if ($cmt->type == 0 && $factor > 0) {
                            /* 只有商品才检查评论条件 */
                            switch ($factor) {
                                case COMMENT_LOGIN:
                                    if ($_SESSION['user_id'] == 0) {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_login'];
                                    }
                                    break;

                                case COMMENT_CUSTOM:
                                    if ($_SESSION['user_id'] > 0) {
                                        $sql = "SELECT o.order_id FROM " . $GLOBALS['ecs']->table('order_info') . " AS o " .
                                            " WHERE user_id = '" . $_SESSION['user_id'] . "'" .
                                            " AND (o.order_status = '" . OS_CONFIRMED . "' or o.order_status = '" . OS_SPLITED . "') " .
                                            " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') " .
                                            " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') " .
                                            " LIMIT 1";


                                        $tmp = $GLOBALS['db']->getOne($sql);
                                        if (empty($tmp)) {
                                            $result['error'] = 1;
                                            $result['message'] = $GLOBALS['_LANG']['comment_custom'];
                                        }
                                    } else {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_custom'];
                                    }
                                    break;
                                case COMMENT_BOUGHT:
                                    if ($_SESSION['user_id'] > 0) {
                                        $sql = "SELECT o.order_id" .
                                            " FROM " . $GLOBALS['ecs']->table('order_info') . " AS o, " .
                                            $GLOBALS['ecs']->table('order_goods') . " AS og " .
                                            " WHERE o.order_id = og.order_id" .
                                            " AND o.user_id = '" . $_SESSION['user_id'] . "'" .
                                            " AND og.goods_id = '" . $cmt->id . "'" .
                                            " AND (o.order_status = '" . OS_CONFIRMED . "' or o.order_status = '" . OS_SPLITED . "') " .
                                            " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') " .
                                            " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') " .
                                            " LIMIT 1";
                                        $tmp = $GLOBALS['db']->getOne($sql);
                                        if (empty($tmp)) {
                                            $result['error'] = 1;
                                            $result['message'] = $GLOBALS['_LANG']['comment_brought'];
                                        }
                                    } else {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_brought'];
                                    }
                            }
                        }

                        /* 无错误就保存留言 */
                        if (empty($result['error'])) {
                            $this->add_comment($cmt);
                        }
                    }
                } else {
                    /* 没有验证码时，用时间来限制机器人发帖或恶意发评论 */
                    if (!isset($_SESSION['send_time'])) {
                        $_SESSION['send_time'] = 0;
                    }

                    $cur_time = gmtime();
                    if (($cur_time - $_SESSION['send_time']) < 30) { // 小于30秒禁止发评论
                        $result['error'] = 1;
                        $result['message'] = $GLOBALS['_LANG']['cmt_spam_warning'];
                    } else {
                        $factor = intval($GLOBALS['_CFG']['comment_factor']);
                        if ($cmt->type == 0 && $factor > 0) {
                            /* 只有商品才检查评论条件 */
                            switch ($factor) {
                                case COMMENT_LOGIN:
                                    if ($_SESSION['user_id'] == 0) {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_login'];
                                    }
                                    break;

                                case COMMENT_CUSTOM:
                                    if ($_SESSION['user_id'] > 0) {
                                        $sql = "SELECT o.order_id FROM " . $GLOBALS['ecs']->table('order_info') . " AS o " .
                                            " WHERE user_id = '" . $_SESSION['user_id'] . "'" .
                                            " AND (o.order_status = '" . OS_CONFIRMED . "' or o.order_status = '" . OS_SPLITED . "') " .
                                            " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') " .
                                            " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') " .
                                            " LIMIT 1";


                                        $tmp = $GLOBALS['db']->getOne($sql);
                                        if (empty($tmp)) {
                                            $result['error'] = 1;
                                            $result['message'] = $GLOBALS['_LANG']['comment_custom'];
                                        }
                                    } else {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_custom'];
                                    }
                                    break;

                                case COMMENT_BOUGHT:
                                    if ($_SESSION['user_id'] > 0) {
                                        $sql = "SELECT o.order_id" .
                                            " FROM " . $GLOBALS['ecs']->table('order_info') . " AS o, " .
                                            $GLOBALS['ecs']->table('order_goods') . " AS og " .
                                            " WHERE o.order_id = og.order_id" .
                                            " AND o.user_id = '" . $_SESSION['user_id'] . "'" .
                                            " AND og.goods_id = '" . $cmt->id . "'" .
                                            " AND (o.order_status = '" . OS_CONFIRMED . "' or o.order_status = '" . OS_SPLITED . "') " .
                                            " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') " .
                                            " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') " .
                                            " LIMIT 1";
                                        $tmp = $GLOBALS['db']->getOne($sql);
                                        if (empty($tmp)) {
                                            $result['error'] = 1;
                                            $result['message'] = $GLOBALS['_LANG']['comment_brought'];
                                        }
                                    } else {
                                        $result['error'] = 1;
                                        $result['message'] = $GLOBALS['_LANG']['comment_brought'];
                                    }
                            }
                        }
                        /* 无错误就保存留言 */
                        if (empty($result['error'])) {
                            $this->add_comment($cmt);
                            $_SESSION['send_time'] = $cur_time;
                        }
                    }
                }
            }
        } else {
            /*
             * act 参数不为空
             * 默认为评论内容列表
             * 根据 _GET 创建一个静态对象
             */
            $cmt = new stdClass();
            $cmt->id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
            $cmt->type = !empty($_GET['type']) ? intval($_GET['type']) : 0;
            $cmt->page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
        }

        if ($result['error'] == 0) {
            $comments = assign_comment($cmt->id, $cmt->type, $cmt->page);

            $this->assign('comment_type', $cmt->type);
            $this->assign('id', $cmt->id);
            $this->assign('username', $_SESSION['user_name']);
            $this->assign('email', $_SESSION['email']);
            $this->assign('comments', $comments['comments']);
            $this->assign('pager', $comments['pager']);

            /* 验证码相关设置 */
            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
                $this->assign('enabled_captcha', 1);
                $this->assign('rand', mt_rand());
            }

            $result['message'] = $GLOBALS['_CFG']['comment_check'] ? $GLOBALS['_LANG']['cmt_submit_wait'] : $GLOBALS['_LANG']['cmt_submit_done'];
            $result['content'] = $GLOBALS['smarty']->fetch("library/comments_list");
        }

        echo json_encode($result);
    }
    /*------------------------------------------------------ */
    //-- PRIVATE FUNCTION
    /*------------------------------------------------------ */

    /**
     * 添加评论内容
     *
     * @access  public
     * @param object $cmt
     * @return  void
     */
    private function add_comment($cmt)
    {
        /* 评论是否需要审核 */
        $status = 1 - $GLOBALS['_CFG']['comment_check'];

        $user_id = empty($_SESSION['user_id']) ? 0 : $_SESSION['user_id'];
        $email = empty($cmt->email) ? $_SESSION['email'] : trim($cmt->email);
        $user_name = empty($cmt->username) ? $_SESSION['user_name'] : '';
        $email = htmlspecialchars($email);
        $user_name = htmlspecialchars($user_name);

        /* 保存评论内容 */
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('comment') .
            "(comment_type, id_value, email, user_name, content, comment_rank, add_time, ip_address, status, parent_id, user_id) VALUES " .
            "('" . $cmt->type . "', '" . $cmt->id . "', '$email', '$user_name', '" . $cmt->content . "', '" . $cmt->rank . "', " . gmtime() . ", '" . real_ip() . "', '$status', '0', '$user_id')";

        $result = $GLOBALS['db']->query($sql);
        clear_cache_files('comments_list');
        /*if ($status > 0)
        {
            add_feed($GLOBALS['db']->insert_id(), COMMENT_GOODS);
        }*/
        return $result;
    }
}
