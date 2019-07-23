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

        $this->handleExtension('default');
    }

    /**
     * 处理主题模板
     * @param $theme
     */
    protected function handleExtension($theme)
    {
        $path = public_path('themes/' . $theme . '/html/*/');
        $list = $this->getFilesByType($path, 'view.php');

        foreach ($list as $item) {
            $content = file_get_contents($item);
            $content = $this->replaceRules($content);
            file_put_contents($item, $content);
        }

        $this->handleStyle($theme);
    }

    /**
     * 处理主题样式
     * @param $theme
     */
    protected function handleStyle($theme)
    {

    }

    /**
     * 获取文件列表
     * @param $path
     * @param $type
     * @return array|false
     */
    protected function getFilesByType($path, $type)
    {
        return glob(rtrim($path, '/') . '/*.' . ltrim($type, '.'));
    }

    /**
     * 替换模板标签
     * @param $content
     * @return string|string[]|null
     */
    protected function replaceRules($content)
    {
        $label = [
            /**
             * variable label
             * {$name} => <?php echo $name;?>
             * {$user['name']} => <?php echo $user['name'];?>
             * {$user.name}    => <?php echo $user['name'];?>
             */
            /*'/{(\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)}/i' => "<?php echo $1; ?>",
            '/\$(\w+)\.(\w+)\.(\w+)\.(\w+)/is' => "\$\\1['\\2']['\\3']['\\4']",
            '/\$(\w+)\.(\w+)\.(\w+)/is' => "\$\\1['\\2']['\\3']",
            '/\$(\w+)\.(\w+)/is' => "\$\\1['\\2']",*/

            /**
             * constance label
             * {CONSTANCE} => <?php echo CONSTANCE;?>
             */
            // '/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s' => "\\1/",

            /**
             * include label
             * {include file="test"}
             */
            '/\<\!-- \#BeginLibraryItem "(.+?)\.lbi" --\>.*?\<\!-- \#EndLibraryItem --\>/m' => '{include file="\\1"}',

            '/\s+heq\s+/' => '===',
            '/\s+nheq\s+/' => '!==',
            '/\s+eq\s+/' => '==',
            '/\s+neq\s+/' => '!=',
            '/\s+egt\s+/' => '>=',
            '/\s+gt\s+/' => '>',
            '/\s+elt\s+/' => '<=',
            '/\s+lt\s+/' => '<',

            '/\{foreach\s+from=\$(\S+?)\s+item=(\S+?)\s+key=(\S+?)\}/' => "{foreach $\\1 as $\\3 => $\\2}",
            '/\{foreach\s+from=\$(\S+?)\s+item=(\S+?)\s+name=(\S+?)\}/' => "{foreach $\\1 as $\\3 => $\\2}",
            '/\{foreach\s+from=\$(\S+?)\s+item=(\S+?)\}/' => "{foreach $\\1 as $\\2}",

            '/\{insert_scripts\s+files=\'(.+?)\'}/is' => '{load href="\\1" /}',
            //'/\{insert\s+name=\'ads\'\s+id=$ads_id num=$ads_num}/is' => '',
        ];

        foreach ($label as $key => $value) {
            $content = preg_replace($key, $value, $content);
        }

        return $content;
    }
}
