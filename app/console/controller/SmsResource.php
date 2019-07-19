<?php

namespace app\console\controller;

/**
 * 程序说明
 */
class SmsResource extends Init
{
    public function index()
    {


        define('SOURCE_ID', '620386');

        $smarty->assign('ur_here', $_LANG['sms_resource_here']);
        $data[] = base64_encode(SOURCE_ID);
        $data[] = get_certificate_info('passport_uid');
        $data[] = get_certificate_info('yunqi_code');
        $data[] = time();
        $data[] = getRandChar(6);
        $data[] = getRandChar(6);
        $source_str = implode('|', $data);
        $smarty->assign('resource_url', SMS_RESOURCE_URL . '/index.php?source=' . base64_encode($source_str));
        $smarty->display('sms_resource.htm');

        function getRandChar($length)
        {
            $str = null;
            $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
            $max = strlen($strPol) - 1;
            for ($i = 0; $i < $length; $i++) {
                $str .= $strPol[rand(0, $max)];
            }
            return $str;
        }
    }
}
