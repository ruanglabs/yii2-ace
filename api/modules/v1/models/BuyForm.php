<?php
namespace api\modules\v1\models;
use Yii;
use yii\base\Model;
use common\models\VipOrder;
use common\models\Vip;
class BuyForm extends Model
{
    public $vip_id;
    private $_user;
    private $_vip;
    public function rules()
    {
        return [
            [['vip_id'], 'required'],
            [['vip_id'],'integer'],
        ];
    }
    public function __construct($id,$user,$config = [])
    {
        $this->_vip = Vip::findOne($id);
        $this->_user = $user;
        parent::__construct($config);
    }
    // 生成订单编号
    public function generateOrderSn($uid)
    {
         return mt_rand(10,99)
        . sprintf('%010d',time() - 946656000)
        . sprintf('%03d', (float) microtime() * 1000)
        . sprintf('%03d', (int) $uid % 1000);
    }
    // 支付号
    public function getPaySn($order_sn){
       return 'v_'.$order_sn;
    }
    // 生成充值订单
    public function generateOrder()
    {
        if(!$this->_vip) {
           return [false,'充值数据错误'];
        }
        if(!$this->_user) {
           return [false,'用户信息错误'];
        }
        $order=new VipOrder();
        $order->order_sn=$this->generateOrderSn($this->_user->id);
        $order->vid=$this->_vip->id;
        $order->days=$this->_vip->days;
        $order->amount=$this->_vip->price;
        $order->uid=$this->_user->id;
        $order->uname=$this->_user->username;
        $order->state=0;
        $order->add_time=time();
        if(!$order->save()){
              $error =$order->FirstErrors;
              return [false,'订单生成失败'];
        }
        return [true,$order];
    }

}
