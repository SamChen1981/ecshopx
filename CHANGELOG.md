\/\*\*\n \* ECSHOP (.*)\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n\*\/
\/\*\*\n \* ECSHOP (.*)\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n \*\/
\/\*\*\n \* ECSHOP(.*)\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n\*\/
\/\*\*\n \* (.*)\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n\*\/
\/\*\*\n \* (.*)\n.*====.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n \*\/
\/\*\*\n \* (.*)\n.*====.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n.*\n\*\/
\/\*\*\n \* $1\n \*\/

[require|include].*ADMIN_PA.*lib_(.+).php.+
[require|include].*\./.*lib_(.+).php.+
load_helper\('$1', 'console'\);
