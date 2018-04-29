<?php

namespace api\common\controllers;
use api\common\behaviors\ValidateBehavior;
use yii\filters\Cors;

class Controller extends \yii\rest\Controller
{
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items'
    ];
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['contentNegotiator']);
        $behaviors['cors'] = [
            'class' => Cors::className(),
        ];
        $behaviors['validate'] = ValidateBehavior::className();
        return $behaviors;
    }
}