<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\db\Query;
use common\helpers\Url;
use yii\data\ActiveDataProvider;
use common\models\PdLog;
class PdLogController extends \api\common\controllers\MemberController
{

    // 余额记录
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $query = PdLog::find()->select(['id','lg_type','lg_amount','lg_freeze_amount','lg_add_time','lg_desc'])->where(['lg_uid'=>$user->id]);
        $total_count=$query->count();
        $query=$query->orderBy('lg_add_time  desc');
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
        $state_arr=['sell'=>'订单销售','cash_apply'=>'申请提现','cash_pay'=>'提现成功','cash_del'=>'取消提现'];
        foreach ($rows as $k => $v) {
            $rows[$k]['lg_add_time']=date('Y-m-d H:i:s',$v['lg_add_time']);
            $rows[$k]['lg_type']= $state_arr[$v['lg_type']];
        }
        return ['datas'=>$rows,'total_page'=>$total_page]; 
    }

}