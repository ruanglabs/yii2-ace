<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\db\Query;
use common\helpers\Url;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\GoodsCreateForm;
use api\modules\v1\models\GoodsUpdateForm;
use common\models\UserGoods;

class GoodsController extends \api\common\controllers\MemberController
{

    // 商品分类
    public function actionGoodsClass(){
        $rows = (new Query())->select(['title','id'])->from('user_goods_class')->where(['state'=>1])->orderBy('order desc')->all();
        return ['datas'=>$rows]; 
    }

    // 我的商品
    public function actionOnline()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $query = (new Query())->select(['id','title','pic','mprice','price','num','view','sale','gc_id','gc_name'])
        ->from('user_goods')->where(['uid'=>$user->id,'state'=>1])->orderBy('id  desc');
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
        return ['datas'=>$rows]; 
    }

    // 我的商品下架
    public function actionOffline()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $query = (new Query())->select(['id','title','pic','mprice','price','num','view','sale','gc_id','gc_name'])
        ->from('user_goods')->where(['uid'=>$user->id,'state'=>0])->orderBy('id  desc');
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
        return ['datas'=>$rows]; 
    }
   
  

    // 详情
    public function actionView()
    {
        $user = Yii::$app->user->identity;
        $id=intval(Yii::$app->request->post('id'));
        $gc = (new Query())->select(['title','id'])->from('user_goods_class')->where(['state'=>1])->orderBy('order desc')->all();
        $goods=UserGoods::find()->where(['id'=>$id])->asArray()->one();
        $goods['pic_list']=explode(',', $goods['imgs']);
        return ['datas'=>['goods'=>$goods,'gc'=> $gc]];
    }

  
    //发布
    public function actionCreate()
    {
       $model = new GoodsCreateForm(Yii::$app->user->identity);
       $model->load(Yii::$app->request->post(),'');
       if ($model->validate() && $model->save()) {
            return ['errmsg'=>'添加成功'];
       }else {
          return $model;
       }
    } 

    public function actionUpdate(){
       $id=intval(Yii::$app->request->post('id'));
       $model = new GoodsUpdateForm($id);
       $model->load(Yii::$app->request->post(),'');
       if ($model->validate() && $model->save()) {
            return ['errmsg'=>'修改成功'];
       }else {
          return $model;
       }
    }
    // 删除
    public function actionDelete()
    {
        $user = Yii::$app->user->identity;
        $id=intval(Yii::$app->request->post('id'));
        $goods=UserGoods::find()->where(['uid'=>$user->id,'id'=>$id,'state'=>0])->one();
        if(!$goods){
           return ['errcode'=>501,'errmsg'=>'参数错误']; 
        }
        if(!$goods->delete()){
            return ['errcode'=>501,'errmsg'=>'删除失败']; 
        }
        return ['errmsg'=>'删除成功']; 
    }
     // 下架
    public function actionOff()
    {
        $user = Yii::$app->user->identity;
        $id=intval(Yii::$app->request->post('id'));
        $goods=UserGoods::find()->where(['uid'=>$user->id,'id'=>$id,'state'=>1])->one();
        if(!$goods){
           return ['errcode'=>501,'errmsg'=>'参数错误']; 
        }
        $goods->state=0;
        if(!$goods->save()){
            return ['errcode'=>501,'errmsg'=>'操作失败']; 
        }
        return ['errmsg'=>'下架成功']; 
    }

    // 上架
    public function actionOn()
    {
        $user = Yii::$app->user->identity;
        $id=intval(Yii::$app->request->post('id'));
        $goods=UserGoods::find()->where(['uid'=>$user->id,'id'=>$id,'state'=>0])->one();
        if(!$goods){
           return ['errcode'=>501,'errmsg'=>'参数错误']; 
        }
        $goods->state=1;
        if(!$goods->save()){
            return ['errcode'=>501,'errmsg'=>'操作失败']; 
        }
        return ['errmsg'=>'上架成功']; 
    }
    // 访问记录列表
    public function actionLogList()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $id=intval($post['id']);
        $query = (new Query())->select(['a.uid','a.stime','a.etime','a.type','b.headimgurl','b.username'])->from('user_goods_log a')
         ->leftJoin('user b','a.uid=b.id')->where(['a.gid'=>$id,'guid'=>$user->id])->orderBy('a.etime desc');
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
        foreach ($rows as $key => $value) {
           $rows[$key]['stoptime']=$this->praseTtime($value['etime']-$value['stime']);
           $rows[$key]['stime']=$this->praseTtimeLeft($value['stime']);
           unset($rows[$key]['etime']);
        }
        return ['datas'=>$rows]; 
    }

    public function praseTtime($t)
    {
       if($t<60){
         return $t.'秒';
       }else{
          return intval($t/60).'分钟';
       }
    }

    public function praseTtimeLeft($t)
    {
      $str='';
      $v=abs(time()-$t);
      if($v>86400){
         //$a=intval($v/86400);
         //$str.= $a."天前";
         return date('Y-m-d h:i:s',$t);
      }
      if($v>3600){
        $str=intval($v/3600)."小时前";
        return $str;
      }
      if($v>60){
          $str=intval($v/60)."分钟前";
           return $str;
      }
      $str=$v."秒前";
      return $str;
    }


}