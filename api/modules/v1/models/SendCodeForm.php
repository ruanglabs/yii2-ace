<?php

namespace api\modules\v1\models;

use common\models\SmsLog;
use common\models\User;
use common\components\SendSms;
use yii\base\Model;
use Yii;

class SendCodeForm extends Model
{
    const TYPE_Auth = 1; //认证
    const TYPE_LOGIN = 2; //登录
    const TYPE_FIND = 3; // 找密码
    public $mobile;
    public $type;
    private $_smslog;
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
            [['mobile','type'], 'required'],
            [['mobile'],'filter','filter' => 'trim','skipOnArray' => true],
           // ['sec_val', 'validateCaptcha'],
            //['sec_key', 'string'],
            [['type'],'integer'],
            ['type','in','range' => [self::TYPE_Auth,self::TYPE_LOGIN,self::TYPE_FIND]],
            ['mobile','match','pattern'=>'/^1[2-9][0-9]\d{4,8}$/','message'=>'手机号码格式不正确'],
            ['mobile', 'validateMobile'],

        ];
    }
    public function attributeLabels()
    {
        return [
            'mobile' => '手机',
            //'sec_val' => '图片验证码',
            'usage' => '类型',
        ];
    }
    public function validateMobile($attribute,$params)
    {
        $value=$this->$attribute;
        switch ($this->type) {
            case self::TYPE_Auth:
                if (User::findByMobile($value)) {
                  $this->addError('mobile', '手机号码已存在');
                }
                return false;
                break;
            case self::TYPE_LOGIN:
            case self::TYPE_FIND:
                if (!User::findByMobile($value)) {
                    $this->addError('mobile','手机号码未注册');
                }
                return false;
                break;
            default:
               return false;
                break;
        }
    }
    public function send()
    {
      if (!$this->validate()){
         return false;
      }
      $sms= SmsLog::find()->where(['log_phone'=>$this->mobile,'log_type'=>$this->type])->orderBy('id desc')->one();
      if(!$sms){
          $code = rand(1000,9999);
          $sms=new SmsLog();
      }
      $time=time()-$sms->add_time;
      if($time<60){
          $this->addError('mobile','请不要重复发送,一分钟内只能发送1次');  
          return false;  
      }
      if($time>180){
          $code =(String) rand(1000,9999);
      }else{
          $code=(String) $sms->log_captcha;  
      }
      $sms->log_phone=$this->mobile;
      $sms->add_time=time();
      $sms->log_captcha=$code;
      $sms->log_type=$this->type;
      $sms->log_ip=Yii::$app->request->userIP;
      $sms->member_id=$this->_user->id;
      $sms->member_name=$this->_user->username;
      $resulet=$sms->save();
      if(!$resulet){
            $this->addError('mobile','发送失败');  
            return false;  
      }
      $send=new SendSms();
      $result=$send->sendSMS($this->mobile,'您的验证码是：'.$code."。请不要把验证码泄露给其他人。");
      if($result['SubmitResult']['code']!=2){
           $this->addError('mobile', $result['SubmitResult']['msg']);  
           return false;  
      }
      $this->_user->mobile=$this->mobile;
      $this->_user->save(false);
      return  true;
    }
}
