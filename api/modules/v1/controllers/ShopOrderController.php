<?php
namespace api\modules\v1\controllers;
use yii\db\Query;
use Yii;
use yii\data\ActiveDataProvider;
use common\models\User;
use common\models\Order;
use common\models\OrderGoods;
use common\models\OrderLogic;
class ShopOrderController extends \api\common\controllers\MemberController
{
    // 商家中心
    // 订单列表
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $type=intval($post['type']);
        $query = (new Query())->select(['id','order_sn','add_time','buyer_name','buyer_id','amount','state'])->from('order')->where(['shop_id'=>$user->id]);
        if($type==1){
             $query = $query->andWhere(['state'=>'20']);
        }else if($type==2){
            $query = $query->andWhere(['state'=>'30']);
        }else{
            $query = $query->andWhere(['state'=>'40']);
        }
        $total_count=$query->count();
        $query=$query->orderBy('id  desc');
        $total_page= intval(($total_count+$pagesize-1)/$pagesize);
        if($page>$total_page){
           return ['datas'=>[]];
        }
        if($page<1){
          $page=1;
          $query= $query->limit($pagesize);
        }else{
          $query= $query->limit($pagesize)->offset(($page-1)*$pagesize);
        }
        $rows=$query->all();
        $rows=$rows?$rows:[];
        if(count($rows)==0){
           return ['datas'=>[]];
        }
        $state_arr=['10'=>'待支付','20'=>'待发货','30'=>'待确认','40'=>'已完成'];
        // 查询商品
        $orderids=array_values(array_column($rows,'id'));
        $ordergoods= (new Query())->select(['goods_id','order_id','goods_name','goods_price','goods_num','goods_pic'])->from('order_goods')->where(['order_id'=>$orderids])->all();
        foreach ($rows as $k => $v) {
            $goods=[];
            foreach ($ordergoods as $i => $g) {
                 if($g['order_id']==$v['id']){
                        $goods[]=$g;
                 }
            }
            $rows[$k]['goods']=$goods;
            $rows[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            $rows[$k]['state_name']= $state_arr[$v['state']];
        }

        return ['datas'=>$rows,'total_page'=>$total_page]; 
    }

    // 订单详情
    public function actionView()
    { 
        $id=intval(Yii::$app->request->post('id'));
        $user = Yii::$app->user->identity;
        $order=[];
        $state_arr=['10'=>'待支付','20'=>'待发货','30'=>'待确认','40'=>'已完成'];
        $order=Order::find()->joinWith('orderGoods')->where(['order.shop_id'=>$user->id,'order.id'=>$id])->asArray()->one();
        $order['add_time']=date('Y-m-d H:i:s',$order['add_time']);
        $order['pay_time']=date('Y-m-d H:i:s',$order['pay_time']);
        $order['send_time']=date('Y-m-d H:i:s',$order['send_time']);
        $order['finnshed_time']=date('Y-m-d H:i:s',$order['finnshed_time']);
        $order['state_name']= $state_arr[$order['state']];
        $order['reciver_info']= unserialize($order['reciver_info']);
        return ['datas'=>['order'=>$order]];
    }
    // 订单发货
    public function actionSend() {
        $user     = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $id = intval(Yii::$app->request->post('id', 0));
        $order    = Order::find()->where(['shop_id' => $user->id, 'id' => $id])->one();
        if (!$order) {
            return ['errcode' => 510, 'errmsg' => '参数错误'];
        }
        $state =   $order->state == 20;
        if(!$state){
          return ['errcode' => 510, 'errmsg' => '无权操作'];
        }
        $result = OrderLogic::changeOrderSend($order,'seller', $user->username,$post);
        if(!$result['state']) {
           return ['errcode' => 510, 'errmsg' => $result['msg']];
        } else {
            return ['errcode' => 0, 'errmsg' => '操作成功'];
        }
    }
    // 取消订单
    public function actionCancel()
    { 
        $id=intval(Yii::$app->request->post('id',0));
        $user     = Yii::$app->user->identity;
        $reason =trim(Yii::$app->request->post('reason'));
        $order    = Order::find()->where(['shop_id' => $user->id, 'id' => $id])->one();
        if (!$order) {
            return ['errcode' => 510, 'errmsg' => '参数错误'];
        }
        if($order->state !=10){
           return ['errcode' => 510, 'errmsg' => '无权操作'];
        }
        $result = OrderLogic::changeOrderStateCancel($order,'seller', $user->username, $reason );
        if(!$result['state']) {
            return ['errcode' => 510, 'errmsg' => $result['msg']];
        } else {
          return ['errcode' => 0, 'errmsg' => '操作成功'];
        }
    }
   

    
}
