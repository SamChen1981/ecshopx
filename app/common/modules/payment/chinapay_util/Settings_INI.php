<?php
include('Settings.php');

class Settings_INI extends Settings
{
    public function load($file=null)
    {
        if (file_exists($file) == false) {
            return false;
        }
        $this->_settings = parse_ini_file($file, true);
    }
}
