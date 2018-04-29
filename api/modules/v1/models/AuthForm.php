<?php

namespace api\modules\v1\models;

use common\models\SmsLog;
use common\models\User;
use common\models\UserShop;
use yii\base\Model;
use Yii;
// 手机认证
class AuthForm extends Model
{
    const TYPE_Auth = 1; //认证
    public $mobile;
    public $code;
    private $_user;
    public function __construct($user, $config = [])
    {
        if (empty($user)) {
            throw new InvalidParamException('用户数据错误');
        }
        $this->_user = $user;
        parent::__construct($config);
    }
    public function rules()
    {
        return [
            [['mobile','code'], 'filter','filter' =>'trim'],
            [['mobile','code'], 'required'],
            ['code', 'string', 'min' => 4,'max'=>6],
            ['code','common\validators\SmscodeValidator','type'=>self::TYPE_Auth,'phoneAttribute' =>'mobile','expireTime' =>300],
            ['mobile','match','pattern'=>'/^1[2-9][0-9]\d{4,8}$/','message'=>'{attribute}格式不正确'],
            ['mobile','exist', 'targetClass' =>'common\models\User'],
        ];
    }
    public function attributeLabels()
    {
        return [
            'mobile' => '手机',
            'code' => '验证码',
        ];
    }
    // 认证
    public function Auth()
    {
        if(!$this->validate()) {
             return false;
        }
        $this->_user->mobile=$this->mobile;
        $this->_user->is_auth=1;
        $this->_user->is_shop=2;
        if($this->_user->save(false)){
            // 认证以后 自动开通微店
            $usershop=new UserShop();
            $usershop->uid=$this->_user->id;
            $usershop->shop_name=$this->_user->username;
            $usershop->mobile=$this->_user->mobile;
            $usershop->add_time=time();
            $usershop->vip_time=time()+86400*365;
            $usershop->state=2;
            $usershop->save();
            return true;
        }
        return $this->_user;
    }
}
