<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\db\Query;
use common\helpers\Url;
use common\models\Address;
class AddressController extends \api\common\controllers\MemberController
{


   public function actionIndex()
   {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $query = Address::find()->where(['uid'=>$user->id]);
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
        return ['datas'=>$rows]; 
    }

    public function actionCreate()
    {
        $info =Yii::$app->request->post();
        $info['uid']=Yii::$app->user->identity->id;
        $is_default=intval(Yii::$app->request->post('is_default'));
        if($is_default==1){
            $address= Address::find()->where(['is_default'=>'1','uid'=>Yii::$app->user->identity->id])->all();
            foreach($address as $m){
                $m->is_default = '0';
                $m->update(false); 
            }
        }
        $model = new Address();
        $model->attributes=$info;
        if($model->validate() && $model->save()) {
               return  ['errcode' =>0,'errmsg' => '添加成功'];
        }else{
            return $model;
        }
       
    }
    public function actionUpdate()
    {
        $id =Yii::$app->request->post('id');
        $info =Yii::$app->request->post();
        $is_default=intval(Yii::$app->request->post('is_default'));
        if($is_default==1){
            $address= Address::find()->where(['is_default'=>'1','uid'=>Yii::$app->user->identity->id])->all();
            foreach($address as $m){
                $m->is_default = 0;
                $m->update(false); 
            }
        }
        $model=Address::find()->where(['id'=>$id,'uid'=>Yii::$app->user->identity->id])->one();
        if(!$model){
          return  ['errcode' =>0,'errmsg' => '参数错误'];
        }
        $model->attributes=$info;
        if($model->validate() && $model->update()) {
            return  ['errcode' =>0,'errmsg' => '修改成功'];
        }else{
            return $model;
        }
    }
   
    public function actionView()
    {
        $id = intval(Yii::$app->request->post('id',0));
        if(!$id){
           return  ['errcode' =>510,'errmsg' => '参数错误'];
        }
        $address=Address::find()->where(['id'=>$id,'uid'=>Yii::$app->user->identity->id])->one();
        return ['datas'=>$address];   
    }
    // 删除
    public function actionDelete()
    {
      $id = intval(Yii::$app->request->post('id',0));
      if(!$id){
         return  ['errcode' =>510,'errmsg' => '参数错误'];
      }
      $model=Address::find()->where(['id'=>$id,'uid'=>Yii::$app->user->identity->id])->one();
      if($model && $model->delete()){
           return  ['errcode' =>0,'errmsg' => '删除成功'];
       }else{
          return  ['errcode' =>510,'errmsg' => '删除失败'];
       } 
    }
  
}