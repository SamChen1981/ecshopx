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

    protected function replaceRules($content)
    {
        $label = [
            /**
             * variable label
             * {$name} => <?php echo $name;?>
             * {$user['name']} => <?php echo $user['name'];?>
             * {$user.name}    => <?php echo $user['name'];?>
             */
            '/{(\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)}/i' => "<?php echo $1; ?>",
            '/\$(\w+)\.(\w+)\.(\w+)\.(\w+)/is' => "\$\\1['\\2']['\\3']['\\4']",
            '/\$(\w+)\.(\w+)\.(\w+)/is' => "\$\\1['\\2']['\\3']",
            '/\$(\w+)\.(\w+)/is' => "\$\\1['\\2']",

            /**
             * constance label
             * {CONSTANCE} => <?php echo CONSTANCE;?>
             */
            '/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s' => "\\1/",

            /**
             * include label
             * {include file="test"}
             */
            '/\<\!-- \#BeginLibraryItem "(.+?)" --\>.*?\<\!-- \#EndLibraryItem --\>/s' => '{include file="$1"}',

            '/\s+heq\s+/' => '===',
            '/\s+nheq\s+/' => '!==',
            '/\s+eq\s+/' => '==',
            '/\s+neq\s+/' => '!=',
            '/\s+egt\s+/' => '>=',
            '/\s+gt\s+/' => '>',
            '/\s+elt\s+/' => '<=',
            '/\s+lt\s+/' => '<',

            '/\{foreach\s+from=\$(\S+?)\s+item=(\S+?)\}/' => "<?php \$n=1;if(is_array($\\1)) foreach($\\1 as $\\2) { ?>",
            '/\{foreach\s+from=\$(\S+?)\s+item=(\S+?)\s+key=(\S+?)\}/' => "<?php \$n=1; if(is_array($\\1)) foreach($\\1 as $\\3 => $\\2) { ?>",

            '{foreach from=$pickout_goods item=goods name=goods}' => '',
            '{foreach from=$lang.js_languages item=item key=key}' => '',
            '{foreach from=$extend_info_list item=field}' => '',
            '{foreach from=$cat_goods item=goods}' => '',
            '{insert_scripts files=\'common.js,index.js\'}' => '',
            '{insert name=\'ads\' id=$ads_id num=$ads_num}' => '',
        ];

        foreach ($label as $key => $value) {
            $content = preg_replace($key, $value, $content);
        }

        return $content;
    }
}
