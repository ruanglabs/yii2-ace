<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=127.0.0.1;dbname=aaa',
            'username' => '1',
            'password' => '1',
            'charset' => 'utf8',
        ],
        'formatter' => [
                'class' => 'yii\i18n\Formatter',
                'dateFormat' => 'php:Y-m-d',
                'datetimeFormat' => 'php:Y-m-d H:i:s',
                'timeFormat' => 'php:H:i:s',
        ],
    ],
    'language' => 'zh-CN',
    'timeZone'=>'Asia/Shanghai',
];
