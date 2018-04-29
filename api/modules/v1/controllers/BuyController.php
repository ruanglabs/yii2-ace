<?php

namespace api\modules\v1\controllers;
use Yii;
use common\models\UserGoods;
use common\models\Cart;
use common\models\BuyLogic;
use common\components\WxJsPay;
use common\models\Order;
class BuyController extends \api\common\controllers\MemberController
{

     /**
     * 购物车、直接购买第一步:选择收获地址和配置方式
     */
    public function actionStep1()
    {
        $user = Yii::$app->user->identity;
        $id =trim(Yii::$app->request->post('cart_id')); // 格式 id|num,id|num
        $ifcart=intval(Yii::$app->request->post('ifcart')); // 是否购物车 0 1
        $cart_id=explode(',',$id);
        $address_id=intval(Yii::$app->request->post('address_id'));
        $logic_buy = new BuyLogic();
        $result = $logic_buy->buyStep1($cart_id,$ifcart,$user->id);
        if(!$result['state']) {
           return  ['errcode' =>510,'errmsg' => $result['msg']];
        }else {
          $result = $result['data'];
        }
        return ['datas'=>$result];  
    }

    /**
     * 购物车、直接购买第二步:保存订单入库，产生订单号， 
     *
     */
    public function actionStep2() {
        $user = Yii::$app->user->identity;
        $param =[];
        $param['cart_id'] = explode(',',trim(Yii::$app->request->post('cart_id'))); // 格式 id|num,id|num
        $param['ifcart']=intval(Yii::$app->request->post('ifcart')); // 是否购物车 0 1
        $param['address_id']=intval(Yii::$app->request->post('address_id'));
        $buy_msg =trim(Yii::$app->request->post('msg'));
        $param['buy_msg'] =$buy_msg;
        $logic_buy = new BuyLogic();
        $result = $logic_buy->buyStep2($param,$user->id,$user);
        if(!$result['state']) {
           return  ['errcode' =>510,'errmsg' => $result['msg']];
        }
        $order_info = current($result['data']['order_info']);
        return ['datas'=>['order_id'=>$order_info['id'],'amount'=>$order_info['amount'] ]];  
    }

    // 支付
    public function actionPay()
    { 
        $user = Yii::$app->user->identity;
        $id=Yii::$app->request->post('order_id',0);
        if(!$id){
          return ['errcode'=>501,'errmsg'=>'参数错误']; 
        }
        $order=Order::find()->where(['buyer_id'=>$user->id,'id'=>$id,'state'=>'10'])->one();
        if($order){
             $openid=$user->openid;
             $body='商品购买';
             $attach='g';
             $order_sn=$order->order_sn;
             $order_id=$order->id;
             $total_fee=$order->amount*100;
             $wxjspay=new WxJsPay();
             $data=$wxjspay->getPrepayId($openid,$body,$attach,$order_sn,$total_fee);
             return ['datas'=>['api_js_params'=>$data,'order_id'=>$order_id,'order_sn'=>$order_sn]]; 
         }else{
             return ['errcode'=>501,'errmsg'=>'订单数据错误']; 
         }
    }


}