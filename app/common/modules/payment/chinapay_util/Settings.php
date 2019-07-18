<?php

class Settings
{
    public $_settings = array();

    /**
     * 获取某些设置的值
     *
     * @param unknown_type $var
     * @return unknown
     */
    public function get($var)
    {
        $var = explode('.', $var);
        $result = $this->_settings;
        foreach ($var as $key) {
            if (! isset($result[$key])) {
                return false;
            }
            $result = $result[$key];
        }
        return $result;
    }

    public function load()
    {
        trigger_error('Not yet implemented', E_USER_ERROR);
    }
}
