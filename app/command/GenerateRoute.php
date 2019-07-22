<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class GenerateRoute extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('generate:route');
        // 设置参数
    }

    protected function execute(Input $input, Output $output)
    {
        $this->getModule('console');
        $this->getModule('shop');
        // 指令输出
        $output->writeln('generate route done');
    }

    /**
     * @param $name
     */
    protected function getModule($name)
    {
        $list = glob(app_path($name . '/controller/*.php'));

        $c = '<?php';
        foreach ($list as $item) {
            $controller = basename($item, '.php');
            $uri = parse_name($controller);
            $c .= "\nRoute::any('{$uri}.php', '{$name}/{$controller}/index');";
        }

        file_put_contents(base_path('route/' . $name . '.php'), $c);
    }
}
