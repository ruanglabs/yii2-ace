<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\db\Query;
use common\helpers\Url;
use yii\data\ActiveDataProvider;
use common\models\UserShop;
use  common\models\Cash;
class CashController extends \api\common\controllers\MemberController
{

    // 提现记录
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $query = Cash::find()->select(['id','order_sn','m_id','money','status','add_time'])->where(['m_id'=>$user->id]);
        $total_count=$query->count();
        $query=$query->orderBy('add_time  desc');
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
        $rows=$query->asArray()->all();
        $rows=$rows?$rows:[];
        if(count($rows)==0){
           return ['datas'=>[]];
        }
        $state_arr=['0'=>'未支付','1'=>'已完成'];
        foreach ($rows as $k => $v) {
            $rows[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            $rows[$k]['status_name']= $state_arr[$v['status']];
        }
        return ['datas'=>$rows,'total_page'=>$total_page]; 
    }

    //提现申请
    public function actionCreate()
    { 
        $user = Yii::$app->user->identity;
        $money=intval(Yii::$app->request->post('money')); 
        $cash=new Cash();
        $data=$cash->create($user,$money);
        if($data[0]){
           return ['errmsg'=>'添加成功'];
        }else{
            return ['errcode'=>501,'errmsg'=>$data[1]];
        }
         
    }
   
    //检查是否提现设置
    public function actionView()
    { 
        $user = Yii::$app->user->identity;
        $usershop=UserShop::find()->where(['uid'=>$user->id])->one();
         if(empty($usershop->cash_type) || empty($usershop->cash_code)){
             $isset=0;
         }else{
             $isset=1;
        }
        $data=[];
        $data['money']=$user->money;
        $data['isset']=$isset;
        return ['datas'=>$data];
    }




  


}