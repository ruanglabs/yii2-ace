<?php

namespace api\common\controllers;
use api\common\auth\MemberParamAuth;
use yii\helpers\ArrayHelper;
class MemberController extends Controller
{

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => MemberParamAuth::className(),
            ]
        ]);
    }

}