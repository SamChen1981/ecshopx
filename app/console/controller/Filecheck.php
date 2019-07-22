<?php

namespace app\console\controller;

/**
 * 文件校验
 */
class Filecheck extends Init
{
    public function index()
    {


        /* 检查权限 */
        admin_priv('file_check');

        if (!$ecshopfiles = @file('./ecshopfiles.md5')) {
            sys_msg($GLOBALS['_LANG']['filecheck_nofound_md5file'], 1);
        }

        $step = empty($_REQUEST['step']) ? 1 : max(1, intval($_REQUEST['step']));

        if ($step == 1 || $step == 2) {
            $GLOBALS['smarty']->assign('step', $step);
            if ($step == 1) {
                $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['file_check']);
            }
            if ($step == 2) {
                $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['fileperms_verify']);
            }
            assign_query_info();
            $GLOBALS['smarty']->display('filecheck.htm');
        } elseif ($step == 3) {
            @set_time_limit(0);

            $md5data = array();
            $this->checkfiles('./', '\.php', 0);
            $this->checkfiles(ADMIN_PATH . '/', '\.php|\.htm|\.js|\.css|\xml');
            $this->checkfiles('api/', '\.php');
            $this->checkfiles('includes/', '\.php|\.html|\.js', 1, 'fckeditor');
            $this->checkfiles('js/', '\.js|\.css');
            $this->checkfiles('languages/', '\.php');
            $this->checkfiles('plugins/', '\.php');
            $this->checkfiles('wap/', '\.php|\.wml');
            // $this->checkfiles('mobile/', '\.php');
            /*
            $this->checkfiles('themes/default/', '\.dwt|\.lbi|\.css');
            $this->checkfiles('uc_client/', '\.php', 0);
            $this->checkfiles('uc_client/control/', '\.php');
            $this->checkfiles('uc_client/model/', '\.php');
            $this->checkfiles('uc_client/lib/', '\.php');
            */

            foreach ($ecshopfiles as $line) {
                $file = trim(substr($line, 34));
                $md5datanew[$file] = substr($line, 0, 32);
                if ($md5datanew[$file] != $md5data[$file]) {
                    $modifylist[$file] = $md5data[$file];
                }
                $md5datanew[$file] = $md5data[$file];
            }

            $weekbefore = time() - 604800;  //一周前的时间
            $addlist = @array_diff_assoc($md5data, $md5datanew);
            $dellist = @array_diff_assoc($md5datanew, $md5data);
            $modifylist = @array_diff_assoc($modifylist, $dellist);
            $showlist = @array_merge($md5data, $md5datanew);

            $result = $dirlog = array();
            foreach ($showlist as $file => $md5) {
                $dir = dirname($file);
                $statusf = $statust = 1;
                if (@array_key_exists($file, $modifylist)) {
                    $status = '<em class="edited">' . $GLOBALS['_LANG']['filecheck_modify'] . '</em>';
                    if (!isset($dirlog[$dir]['modify'])) {
                        $dirlog[$dir]['modify'] = '';
                    }
                    $dirlog[$dir]['modify']++;  //统计“被修改”的文件
                    $dirlog[$dir]['marker'] = substr(md5($dir), 0, 3);
                } elseif (@array_key_exists($file, $dellist)) {
                    $status = '<em class="del">' . $GLOBALS['_LANG']['filecheck_delete'] . '</em>';
                    if (!isset($dirlog[$dir]['del'])) {
                        $dirlog[$dir]['del'] = '';
                    }
                    $dirlog[$dir]['del']++;     //统计“被删除”的文件
                    $dirlog[$dir]['marker'] = substr(md5($dir), 0, 3);
                } elseif (@array_key_exists($file, $addlist)) {
                    $status = '<em class="unknown">' . $GLOBALS['_LANG']['filecheck_unknown'] . '</em>';
                    if (!isset($dirlog[$dir]['add'])) {
                        $dirlog[$dir]['add'] = '';
                    }
                    $dirlog[$dir]['add']++;     //统计“未知”的文件
                    $dirlog[$dir]['marker'] = substr(md5($dir), 0, 3);
                } else {
                    $status = '<em class="correct">' . $GLOBALS['_LANG']['filecheck_check_ok'] . '</em>';
                    $statusf = 0;
                }

                //对一周之内发生修改的文件日期加粗显示
                $filemtime = @filemtime(ROOT_PATH . $file);
                if ($filemtime > $weekbefore) {
                    $filemtime = '<b>' . date("Y-m-d H:i:s", $filemtime) . '</b>';
                } else {
                    $filemtime = date("Y-m-d H:i:s", $filemtime);
                    $statust = 0;
                }

                if ($statusf) {
                    $filelist[$dir][] = array('file' => basename($file), 'size' => file_exists(ROOT_PATH . $file) ? number_format(filesize(ROOT_PATH . $file)) . ' Bytes' : '', 'filemtime' => $filemtime, 'status' => $status);
                }
            }

            $result[$GLOBALS['_LANG']['result_modify']] = count($modifylist);
            $result[$GLOBALS['_LANG']['result_delete']] = count($dellist);
            $result[$GLOBALS['_LANG']['result_unknown']] = count($addlist);

            $GLOBALS['smarty']->assign('result', $result);
            $GLOBALS['smarty']->assign('dirlog', $dirlog);
            $GLOBALS['smarty']->assign('filelist', $filelist);
            $GLOBALS['smarty']->assign('step', $step);
            $GLOBALS['smarty']->assign('ur_here', $GLOBALS['_LANG']['filecheck_completed']);
            $GLOBALS['smarty']->assign('action_link', array('text' => $GLOBALS['_LANG']['filecheck_return'], 'href' => 'filecheck.php?step=1'));

            assign_query_info();
            $GLOBALS['smarty']->display('filecheck.htm');
        }
    }

    /**检查文件
     * @param string $currentdir //待检查目录
     * @param string $ext //待检查的文件类型
     * @param int $sub //是否检查子目录
     * @param string $skip //不检查的目录或文件
     */
    private function checkfiles($currentdir, $ext = '', $sub = 1, $skip = '')
    {
        $currentdir = ROOT_PATH . str_replace(ROOT_PATH, '', $currentdir);
        $dir = @opendir($currentdir);
        $exts = '/(' . $ext . ')$/i';
        $skips = explode(',', $skip);

        while ($entry = readdir($dir)) {
            $file = $currentdir . $entry;

            if ($entry != '.' && $entry != '..' && $entry != '.svn' && (preg_match($exts, $entry) || ($sub && is_dir($file))) && !in_array($entry, $skips)) {
                if ($sub && is_dir($file)) {
                    $this->checkfiles($file . '/', $ext, $sub, $skip);
                } else {
                    if (str_replace(ROOT_PATH, '', $file) != './md5.php') {
                        $md5data[str_replace(ROOT_PATH, '', $file)] = md5_file($file);
                    }
                }
            }
        }
    }
}
