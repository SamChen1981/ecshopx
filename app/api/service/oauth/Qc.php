<?php

namespace app\api\service\oauth;

class Qc
{
    const GET_AUTH_CODE_URL = "https://graph.qq.com/oauth2.0/authorize";
    const GET_ACCESS_TOKEN_URL = "https://graph.qq.com/oauth2.0/token";
    const GET_OPENID_URL = "https://graph.qq.com/oauth2.0/me";
    const GET_USER_INFO_URL = "https://graph.qq.com/user/get_user_info";

    private $appid;
    private $appkey;
    
    public function __construct($appid, $appkey)
    {
        $this->appid = $appid;
        $this->appkey = $appkey;
    }

    public function login($callback, $scope = 'get_user_info')
    {
        //-------构造请求参数列表
        $keysArr = array(
            "response_type" => "code",
            "client_id" => $this->appid,
            "redirect_uri" => $callback,
            "state" => md5(uniqid(rand(), true)),
            "scope" => $scope
        );

        $login_url = $this->combineUrl(self::GET_AUTH_CODE_URL, $keysArr);

        return $login_url;
    }

    public function get_access_token($callback)
    {
        //-------请求参数列表
        $keysArr = array(
            "grant_type" => "authorization_code",
            "client_id" => $this->appid,
            "redirect_uri" => $callback,
            "client_secret" => $this->appkey,
            "code" => $_GET['code']
        );

        //------构造请求access_token的url
        $token_url = $this->combineUrl(self::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = $this->get_distant_contents($token_url);
        if (strpos($response, "callback") !== false) {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
            $msg = json_decode($response);

            if (isset($this->error)) {
                return false;
            }
        }

        $params = array();
        parse_str($response, $params);

        return $params["access_token"];

    }

    public function combineUrl($baseurl, $arr)
    {
        $combined = $baseurl . "?";
        $value = array();
        foreach ($arr as $key => $val) {
            $value[] = "$key=$val";
        }
        $imstr = implode("&", $value);
        $combined .= ($imstr);
        return $combined;
    }

    public function get_distant_contents($url)
    {
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        curl_close($ch);
        if (empty($response)) {
            return false;
        }
        return $response;
    }

    public function get_openid($access_token)
    {

        //-------请求参数列表
        $keysArr = array(
            "access_token" => $access_token
        );
        $graph_url = $this->combineUrl(self::GET_OPENID_URL, $keysArr);

        $response = $this->get_distant_contents($graph_url);

        //--------检测错误是否发生
        if (strpos($response, "callback") !== false) {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }

        $user = json_decode($response);
        if (isset($user->error)) {
            return false;
        }
        return $user->openid;
    }

    public function get_user_info($access_token, $openid, $appid)
    {
        $keysArr = array(
            "oauth_consumer_key" => $appid,
            "access_token" => $access_token,
            "openid" => $openid
        );
        $url = $this->combineUrl(self::GET_USER_INFO_URL, $keysArr);
        $response = json_decode($this->get_distant_contents($url));
        $responseArr = $this->objToArr($response);
        //检查返回ret判断api是否成功调用
        if ($responseArr['ret'] == 0) {
            return $responseArr;
        } else {
            return false;
        }
    }

    public function objToArr($obj)
    {
        if (!is_object($obj) && !is_array($obj)) {
            return $obj;
        }
        $arr = array();
        foreach ($obj as $k => $v) {
            $arr[$k] = $this->objToArr($v);
        }
        return $arr;
    }
}
