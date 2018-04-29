<?php

namespace api\modules\v1\models;

use common\models\UserShop;
use yii\base\Model;
use Yii;
// 修改信息
class CashSetForm extends Model
{
    public $cash_type;
    public $cash_name;
    public $cash_code;
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
            [['cash_type','cash_name','cash_code'], 'filter','filter' =>'trim'],
            [['cash_type','cash_name','cash_code'], 'required'],
 
            [['cash_type','cash_name','cash_code'],'string','max'=>100],
        ];
    }
    public function attributeLabels()
    {
        return [
            'cash_type' => '提现方式',
            'cash_name' => '提现用户',
            'cash_code' => '提现账号',
        ];
    }
    // 认证
    public function save()
    {
        if(!$this->validate()) {
             return false;
        }
        $this->_usershop->cash_type=$this->cash_type;
        $this->_usershop->cash_name=$this->cash_name;
        $this->_usershop->cash_code=$this->cash_code;
        if($this->_usershop->save(false)){
            return true;
        }
        return $this->_usershop;
    }
}
