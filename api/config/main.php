<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/params.php')
);
return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' =>'api\common\controllers',
    'components' => [
        'request' => [
            'enableCookieValidation' => false
        ],
        'response' => [
            'format' => 'json',
            'on afterSend' => function ($event) {
            },
            'on beforeSend' => function($event) {
                $response = $event->sender;
                if ($response->data !== null) {
                    if (!$response->isSuccessful) {
                        $result = $response->data;
                        if ($response->statusCode == 422) {
                            $response->data = [
                                'errcode' => $response->statusCode,
                                'errmsg' => $result[0]['message'],
                            ];
                        } else {
                            $response->data = [
                                'errcode' => isset($result['status']) ? $result['status'] : $response->statusCode,
                                'errmsg' => $result['message'],
                            ];
                        }
                        $response->statusCode = 200;
                    } else {
                        $result = $response->data;
                        $response->data = array_merge([
                            'errcode' => 0,
                            'errmsg' => 'ok',
                        ], $result);

                    }
                }
            }
        ],
        'user' => [
            'identityClass' => 'api\common\models\Member',
            'enableAutoLogin' => false,
            'enableSession'=>false,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
  
    ],
    'modules' => [
        'v1' => [
            'class' => '\api\modules\v1\Module'
        ],
    ],
    'params' => $params,
];
