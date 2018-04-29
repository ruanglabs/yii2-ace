<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\db\Query;
use common\helpers\Url;
use yii\data\ActiveDataProvider;
use common\models\UserShop;
use common\models\UserGoods;
use common\models\UserGoodsLog;
class MicroController extends \api\common\controllers\MemberController
{

    //店铺首页
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;

        $shop_id=intval(Yii::$app->request->post('id'));
        $usershop=UserShop::find()->where(['uid'=>$shop_id])->one();
        if(empty($usershop->slider_pic)){
               $slider=[];
        }else{
             $slider=explode(',',$usershop->slider_pic);   
        }
        $pic_list=[];
        if(!empty($slider)){

           foreach ($slider as $key => $v) {
               $pic_list[$key]['url']='#';
               $pic_list[$key]['pic']= $v;
               $pic_list[$key]['title']='';
           }
        }

        $data=[];
        $data['pic_list']=$pic_list;
        $data['shop_name']=$usershop->shop_name;
        $data['shop_pic']=$usershop->shop_pic?$usershop->shop_pic:'/wap/img/headbig.png';
        return ['datas'=>$data];
    }

    //商品列表
    public function actionGoodsList()
    { 
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['curpage']))?intval($post['curpage']):1;
        $pagesize=(isset($post['page']))?intval($post['page']):10;
        $id=$post['id'];
        $offset=($page)*$pagesize;
        $query = (new Query())->select(['id','title','pic','mprice','price','num','view','sale','gc_id','gc_name'])
        ->from('user_goods')->where(['uid'=>$id,'state'=>1])->orderBy('id  desc');
        $total_count=$query->count();
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

    //商品详情
    public function actionGoods()
    { 
        $user = Yii::$app->user->identity;
        $id=intval(Yii::$app->request->post('id'));
        $goods=UserGoods::find()->where(['id'=>$id])->one()->toArray();
        $goods['pic_list']=explode(',',  $goods['imgs']);
        unset($goods['imgs']);
        $goods['user_id']=$user->id;
        if($user->id!=$goods['uid']){
            // 记录访问记录
            $al=new UserGoodsLog();
            $al->guid=$goods['uid'];
            $al->gid=$id;
            $al->uid=$user->id;
            $al->type=1;
            $al->stime=time();
            $al->etime=time()+rand(1,5);
            $al->save();
            $goods['log_id']=$al->id;
            $goods['is_self']=0;
        }else{
            $goods['is_self']=1;
        }
        return ['datas'=>$goods];
    }

      // 访问记录
    public function actionVisitCount(){
        $user = Yii::$app->user->identity;
        $log_id=intval(Yii::$app->request->post('log_id'));
        $alog=UserGoodsLog::find()->where(['id'=>$log_id,'type'=>1])->one();
        if($alog){
              if(($alog->etime-$alog->stime)<60){
                  $alog->etime=$alog->etime+mt_rand(1,5);
              }else{
                 $alog->etime=$alog->etime+mt_rand(25,29);
              }
              $alog->save();
        }
        return [];
    }


  // 分享记录
    public function actionShareCount(){
        $user = Yii::$app->user->identity;
         $id=intval(Yii::$app->request->post('id'));//auid
         $alog=UserGoodsLog::find()->where(['gid'=>$id,'uid'=>$user->id,'type'=>2])->one();
       // if(!$alog){
            $goods=UserGoods::find()->where(['id'=>$id])->one();
            if($goods){
                if($goods->uid!=$user->id){ // 其他人分享才记录
                    $al=new UserGoodsLog();
                    $al->guid=$goods->uid;
                    $al->gid=$id;
                    $al->uid=$user->id;
                    $al->type=2;
                    $al->stime=time();
                    $al->etime=time()+rand(1,5);
                    $al->save();
                }
            }
       //}
        return [];
    }


  


}