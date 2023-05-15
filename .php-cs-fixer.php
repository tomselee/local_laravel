<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    
    'class_attributes_separation'                => [
        'elements' => [
            'const'    => 'one',
            'method'   => 'one',
            'property' => 'one',
        ],
    ],
    '@Symfony'                                   => true,
    'standardize_not_equals'                     => false, //使用 <> 代替 !=；
    'array_syntax'                               => [
        'syntax' => 'short',
    ],
    'ordered_imports'                            => [
        'sort_algorithm' => 'length',
    ],
    '@PSR2'                                      => true,
    'single_quote'                               => true, //简单字符串应该使用单引号代替双引号；
    'no_unused_imports'                          => true, //删除没用到的use
    'no_singleline_whitespace_before_semicolons' => true, //禁止只有单行空格和分号的写法；
    'self_accessor'                              => true, //在当前类中使用 self 代替类名；
    'no_empty_statement'                         => true, //多余的分号
    'no_superfluous_phpdoc_tags'                 => false, // param
    'phpdoc_no_empty_return'                     => false, // void
    'no_blank_lines_after_class_opening'         => true, //类开始标签后不应该有空白行；
    'include'                                    => true, //include 和文件路径之间需要有一个空格，文件路径不需要用括号括起来；
    'no_trailing_comma_in_list_call'             => true, //删除 list 语句中多余的逗号；
    'no_leading_namespace_whitespace'            => true, //命名空间前面不应该有空格；
    'concat_space'                               => ['spacing' => 'one'], //点连接符左右两边有一个的空格；
    'binary_operator_spaces'                     => [
        //            'default' => 'align_single_space_minimal', //等号对齐、数字箭头符号对齐
        'default' => 'align_single_space',
    ],
];

$finder = Finder::create()->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])->name('*.php')->notName('*.blade.php')->ignoreDotFiles(true)->ignoreVCS(true);

return (new Config())->setFinder($finder)->setRules($rules)->setRiskyAllowed(true)->setUsingCache(true);
