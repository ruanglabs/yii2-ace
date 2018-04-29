<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\db\Query;
use common\helpers\Url;
use yii\data\ActiveDataProvider;
use common\models\User;
use api\modules\v1\models\BuyForm;
use common\models\VipOrder;
use common\models\Vip;
use common\components\WxJsPay;
class VipController extends \api\common\controllers\MemberController
{

    // 充值vip商品
    public function actionIndex()
    { 
         $data=Vip::find()->asArray()->all(); 
         return ['datas'=>$data];
    }
     // 充值
    public function actionBuy()
    { 
        $user = Yii::$app->user->identity;
        $id=Yii::$app->request->post('id',0);
        if(!$id){
        	return ['errcode'=>501,'errmsg'=>'参数错误']; 
          return [0,'参数错误'];
        }
        $order=VipOrder::find()->where(['uid'=>$user->id,'vid'=>$id,'state'=>0])->one();
        if($order){
            $openid=$user->openid;
            $body='VIP会员购买';
            $attach='v';
            $order_sn=$order->order_sn;
            $order_id=$order->id;
            $total_fee=$order->amount*100;
            $wxjspay=new WxJsPay();
            $data=$wxjspay->getPrepayId($openid,$body,$attach,$order_sn,$total_fee);
            return ['datas'=>['api_js_params'=>$data,'order_id'=>$order_id,'order_sn'=>$order_sn]]; 
        }else{
            $model=new BuyForm($id,$user);
            $data=$model->generateOrder();
            if(!$data[0]){
               return ['errcode'=>501,'errmsg'=>$data[1]]; 
            }
            $order= $data[1];
            $openid=$user->openid;
            $body='VIP会员购买';
            $attach='v';
            $order_sn=$order->order_sn;
            $total_fee=$order->amount*100;
            $order_id=$order->id;
            $wxjspay=new WxJsPay();
            $data=$wxjspay->getPrepayId($openid,$body,$attach,$order_sn,$total_fee);
            return ['datas'=>['api_js_params'=>$data,'order_id'=>$order_id,'order_sn'=>$order_sn]]; 
        }
    }

    public function actionLogList(){
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $query = (new Query())->select(['des','created_at','days','from'])->from('vip_log')
        ->where(['uid'=>$user->id])
        ->orderBy('id  desc');
        $total_count=$query->count();
        $total_page= intval(($total_count+$pagesize-1)/$pagesize);
        if($page>$total_page){
           return [];
        }
        if($page<1){
          $page=1;
          $query= $query->limit($pagesize);
        }else{
          $query= $query->limit($pagesize)->offset(($page-1)*$pagesize);
        }
        $rows=$query->all();
        $rows=$rows?$rows:[];
        if(count($rows)>0){
            foreach ($rows as $key => $value) {
               $rows[$key]['created_at']=date('Y-m-d H:i:s',$value['created_at']); 
               $rows[$key]['from']=$value['from']; 
               $rows[$key]['days']='+'.$value['days']; 
           }  
        }
        return ['datas'=>$rows]; 
    }

  


}