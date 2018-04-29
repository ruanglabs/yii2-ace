<?php

namespace api\modules\v1\models;

use common\models\UserShop;
use yii\base\Model;
use Yii;
// 修改信息
class InfoSetForm extends Model
{
    public $shop_name;
    public $address;
    public $password;
    private $_usershop;
    public function __construct($id, $config = [])
    {
        if (!$id) {
            throw new InvalidParamException('用户数据错误');
        }
        $this->_usershop = UserShop::find()->where(['uid'=>$id])->one();
        if (empty($this->_usershop)) {
            throw new InvalidParamException('用户数据错误');
        }
        parent::__construct($config);
    }
    public function rules()
    {
        return [
            [['shop_name','address','password'], 'filter','filter' =>'trim'],
            [['shop_name'], 'required'],
            ['shop_name', 'string','min'=>3,'max'=>50],
            ['password', 'string','min'=>6,'max'=>32],
            ['address','string','max'=>100],
        ];
    }
    public function attributeLabels()
    {
        return [
            'shop_name' => '商家名字',
            'address' => '发货地址',
            'password' => '密码',
        ];
    }
    // 认证
    public function save()
    {
        if(!$this->validate()) {
             return false;
        }
        $this->_usershop->shop_name=$this->shop_name;
        $this->_usershop->address=$this->address;
        if(!empty($this->password)){
           $this->_usershop->setPassword($this->password);  
        }
        if($this->_usershop->save(false)){
            return true;
        }
        return $this->_usershop;
    }
}
