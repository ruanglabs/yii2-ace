<?php


namespace common\components\rbac;

use Yii;

class RuleHelper
{
    public static function enums()
    {
        $ruleModels = Yii::$app->authManager->getRules();
        $rules = array_keys($ruleModels);
        return array_combine($rules, $rules);
    }
}