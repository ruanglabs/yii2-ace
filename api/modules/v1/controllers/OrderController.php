<?php
namespace api\modules\v1\controllers;
use yii\db\Query;
use Yii;
use yii\data\ActiveDataProvider;
use common\models\User;
use common\models\Order;
use common\models\OrderGoods;
use common\models\OrderLogic;
class OrderController extends \api\common\controllers\MemberController
{
    // 个人中心
    // 订单列表
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $type=intval($post['type']);
        $query = (new Query())->select(['id','order_sn','add_time','shop_name','shop_id','amount','state'])->from('order')->where(['buyer_id'=>$user->id,'delete_state'=>'0']);
        if($type==1){
             $query = $query->andWhere(['state'=>'10']);
        }else if($type==2){
            $query = $query->andWhere(['state'=>['20','30']]);
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
        $order=Order::find()->joinWith('orderGoods')->where(['order.buyer_id'=>$user->id,'order.id'=>$id])->asArray()->one();
        $order['add_time']=date('Y-m-d H:i:s',$order['add_time']);
        $order['pay_time']=date('Y-m-d H:i:s',$order['pay_time']);
        $order['send_time']=date('Y-m-d H:i:s',$order['send_time']);
        $order['finnshed_time']=date('Y-m-d H:i:s',$order['finnshed_time']);
        $order['state_name']= $state_arr[$order['state']];
        return ['datas'=>['order'=>$order]];
    }
    // 确认收货
    public function actionReceive()
    { 
        $user     = Yii::$app->user->identity;
        $id = intval(Yii::$app->request->post('id', 0));
        $order    = Order::find()->where(['buyer_id' => $user->id, 'id' => $id])->one();
        if (!$order) {
            return ['errcode' => 510, 'errmsg' => '参数错误'];
        }
        $state =     $order->state == 30;
        if(!$state){
          return ['errcode' => 510, 'errmsg' => '无权操作'];
        }
        $result = OrderLogic::changeOrderStateReceive($order,'buyer', $user->username, '签收了货物');
        if(!$result['state']) {
           return ['errcode' => 510, 'errmsg' => $result['msg']];
        } else {
            return ['errcode' => 0, 'errmsg' => '操作成功'];
        }
    }

    //删除
    public function actionDelete()
    { 
        $user     = Yii::$app->user->identity;
        $id = intval(Yii::$app->request->post('id', 0));
        $order    = Order::find()->where(['buyer_id' => $user->id, 'id' => $id, 'delete_state' => 0])->one();
        if (!$order) {
            return ['errcode' => 510, 'errmsg' => '参数错误'];
        }
        if($order->state!=0 && $order->state!=40){
            return ['state' => false,'msg'=>'只有完成的订单才能删除'];
        }
        $order->delete_state=1;
        if($order->save(false)){
             return ['errcode' => 0, 'errmsg' => '操作成功'];
        }else{
            return ['errcode' => 510, 'errmsg' => '操作失败'];
        }
    }

    // 取消订单
    public function actionCancel()
    { 
        $id=intval(Yii::$app->request->post('id',0));
        $user     = Yii::$app->user->identity;
        $order    = Order::find()->where(['buyer_id' => $user->id, 'id' => $id])->one();
        if (!$order) {
            return ['errcode' => 510, 'errmsg' => '参数错误'];
        }
        // if (time() - 86400 < $order->api_pay_time) {
        //     $_hour = ceil(($order->api_pay_time+86400-time())/3600);
        //      return ['errcode' => 510, 'errmsg' => '该订单曾尝试使用第三方支付平台支付，须在'.$_hour.'小时以后才可取消'];
        // }
        if($order->state !=10){
           return ['errcode' => 510, 'errmsg' => '无权操作'];
        }
        $result = OrderLogic::changeOrderStateCancel($order,'buyer', $user->username, '用户取消');
        if(!$result['state']) {
            return ['errcode' => 510, 'errmsg' => $result['msg']];
        } else {
           return ['errcode' => 0, 'errmsg' => '操作成功'];
        }
    }



    
}
