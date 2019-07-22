<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class ConvertTemplate extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('convert:template');
        // 设置参数

    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $output->writeln('convert template');
    }

    protected function handleExtension($theme)
    {

    }

    protected function handleStyle($theme)
    {

    }

    protected function zz()
    {
    }

    protected function getFilesByType($path, $type)
    {
        return glob($path . '*.' . $type);
    }

    protected function replaceRules()
    {
        return [
            '{foreach from=$pickout_goods item=goods name=goods}' => '',
            '{foreach from=$lang.js_languages item=item key=key}' => '',
            '{foreach from=$extend_info_list item=field}' => '',
            '{foreach from=$cat_goods item=goods}' => '',
            '<!-- #BeginLibraryItem "/library/page_header.lbi" --><!-- #EndLibraryItem -->' => '',
            '{insert_scripts files=\'common.js,index.js\'}' => '',
            '{insert name=\'ads\' id=$ads_id num=$ads_num}' => '',
            '' => '',
            '' => '',
            '' => '',
            '' => '',
        ];
    }
}
