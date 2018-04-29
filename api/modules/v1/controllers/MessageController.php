<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\db\Query;
use common\helpers\Url;
use common\models\Message;
class MessageController extends \api\common\controllers\MemberController
{


   public function actionIndex()
   {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $query = Message::find()->where(['t_id'=>$user->id]);
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
        foreach ($rows as $k => $v) {
            $rows[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            //$rows[$k]['read']= '';
        }
        Message::updateAllCounters(['read' =>1],['t_id' => $user->id,'read'=>0]);  
        return ['datas'=>$rows]; 
    }


    public function actionDelete()
    {
      $id = intval(Yii::$app->request->post('id',0));
      if(!$id){
         return  ['errcode' =>510,'errmsg' => '参数错误'];
      }
      $model=Message::find()->where(['id'=>$id,'t_id'=>Yii::$app->user->identity->id])->one();
      if($model && $model->delete()){
           return  ['errcode' =>0,'errmsg' => '删除成功'];
       }else{
          return  ['errcode' =>510,'errmsg' => '删除失败'];
       }
         
    }
  
}