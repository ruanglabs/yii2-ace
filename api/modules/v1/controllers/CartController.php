<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\db\Query;
use common\helpers\Url;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\CartForm;
use common\models\UserGoods;
use common\models\Cart;
class CartController extends \api\common\controllers\MemberController
{

    // 购物车
    public function actionIndex()
    {
       $shop_id=intval(Yii::$app->request->post('shop_id'));
       $user = Yii::$app->user->identity;
       $data=Cart::find()->where(['bid'=>$user->id,'sid'=>$shop_id])->indexBy('gid')->all(); 
       $total=0;
       $rows=[];
       foreach ($data as $key => $v) {
          $rows[]=$v;
          $total+=$v['gprice']*$v['gnum'];
       }
       return ['datas'=>['list'=>$rows,'total'=>$total]];
    }

    //添加购物车
    public function actionCreate()
    { 
        $id=intval(Yii::$app->request->post('id'));
        $num=intval(Yii::$app->request->post('num'));
        $model=new CartForm($id,$num,Yii::$app->user->identity);
        if ($model->save()) {
            return ['errmsg'=>'添加成功'];
        }else {
           return $model;
        }
    }

    // 加减
    public function actionUpdate()
    { 
        $user = Yii::$app->user->identity;
       $id=intval(Yii::$app->request->post('cart_id'));
       $num=intval(Yii::$app->request->post('num'));
       if($num<1){
          return ['errcode'=>501,'errmsg'=>'参数错误'];
       }
       $cart=Cart::find()->where(['bid'=>$user->id,'id'=>$id])->one();
       if(!$cart){
          return ['errcode'=>501,'errmsg'=>'参数错误'];
       }
       $goods=UserGoods::find()->where(['id'=>$cart->gid,'state'=>1])->one();
       if(!$goods){
          return ['errcode'=>501,'errmsg'=>'参数错误'];
       }
       if($num>$goods->num){
         return ['errcode'=>501,'errmsg'=>'库存不足'];
       }
       $cart->gnum=$num;
       if(!$cart->save()){
          return ['errcode'=>501,'errmsg'=>'操作失败'];
       }
       return ['datas'=>['num'=>$cart->gnum,'cart_id'=>$cart->id]];
    }

    //删除购物车
    public function actionDelete()
    { 
        $user = Yii::$app->user->identity;
        $id=intval(Yii::$app->request->post('cart_id'));
        $cart=Cart::find()->where(['bid'=>$user->id,'id'=>$id])->one();
        if(!$cart){
           return ['errcode'=>501,'errmsg'=>'参数错误']; 
        }
        if(!$cart->delete()){
            return ['errcode'=>501,'errmsg'=>'删除失败']; 
        }
        return ['errmsg'=>'删除成功']; 
    }


    //购物车数量
    public function actionCount()
    { 
        $user = Yii::$app->user->identity;
        $shop_id=intval(Yii::$app->request->post('shop_id'));
        $count=Cart::find()->where(['bid'=>$user->id,'sid'=>$shop_id])->count();
         return ['datas'=>$count];
    }





   




  


}