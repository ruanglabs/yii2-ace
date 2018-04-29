<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\db\Query;
use common\helpers\Url;
use yii\data\ActiveDataProvider;
use common\models\User;
use common\models\VipOrder;
use common\models\UserShop;
use api\modules\v1\models\InfoSetForm;
use api\modules\v1\models\CashSetForm;
class ShopController extends \api\common\controllers\MemberController
{

    // 商家中心数据
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $usershop=UserShop::find()->where(['uid'=>$user->id])->one();
        $data=[];
        $data['id']= $user->id;
        $data['is_auth']=$user->is_auth;
        $data['is_shop']=$user->is_shop;
        if($usershop){
            $data['shop_name']=$usershop->shop_name;
            $data['shop_pic']=$usershop->shop_pic;
        }
        $data['order_count']=(new Query())->select(['id'])->from('order')->where(['shop_id'=>$user->id,'state'=>['20','30']])->count();
        $data['msg_count']=(new Query())->select(['id'])->from('message')->where(['t_id'=>$user->id,'read'=>'0'])->count();
        $data['goods_count']=(new Query())->select(['id'])->from('user_goods')->where(['uid'=>$user->id,'state'=>'1'])->count();
        $data['money']=$user->money;
        return ['datas'=>$data];
    }

     //商家详情
    public function actionMoney()
    { 
        $user = Yii::$app->user->identity;
        $data['money']=$user->money;
        return ['datas'=>$data];
    }

    //商家详情
    public function actionView()
    { 
        $user = Yii::$app->user->identity;
        $usershop=UserShop::find()->where(['uid'=>$user->id])->one();
        $data=[];
        $data['id']= $user->id;
        $data['is_auth']=$user->is_auth;
        $data['is_shop']=$user->is_shop;
        if($usershop){
            $data['shop_name']=$usershop->shop_name;
            $data['shop_pic']=$usershop->shop_pic;
            $data['mobile']=$usershop->mobile;
            $data['address']=$usershop->address;
            $data['cash_type']=$usershop->cash_type;
            $data['cash_name']=$usershop->cash_name;
            $data['cash_code']=$usershop->cash_code;
        }
        return ['datas'=>$data];
    }

    //轮播图片
    public function actionSlider()
    { 
        $user = Yii::$app->user->identity;
        $usershop=UserShop::find()->where(['uid'=>$user->id])->one();
        if($usershop){
            if(empty($usershop->slider_pic)){
               $data=[];
            }else{
             $data=explode(',',$usershop->slider_pic);   
            }
        }
        return ['datas'=>$data];
    }

     //轮播图片保存
    public function actionSliderSave()
    { 
        $user = Yii::$app->user->identity;
        $pics=trim(Yii::$app->request->post('imgs'));
        if(empty($pics)){
           return ['errcode'=>501,'errmsg'=>'请上传图片'];  
        }
        $usershop=UserShop::find()->where(['uid'=>$user->id])->one();
        $usershop->slider_pic=$pics;
        if ($usershop->save()) {
            return ['errmsg'=>'设置成功'];
        }else {
          return ['errcode'=>501,'errmsg'=>'设置失败'];  
        }
    }

    //提现设置
    public function actionCashSet()
    { 
       $model = new CashSetForm(Yii::$app->user->identity->id);
       $model->load(Yii::$app->request->post(),'');
       if ($model->validate() && $model->save()) {
            return ['errmsg'=>'设置成功'];
       }else {
          return $model;
       }
    }
 
    //信息设置
    public function actionInfoSet()
    { 
       $model = new InfoSetForm(Yii::$app->user->identity->id);
       $model->load(Yii::$app->request->post(),'');
       if ($model->validate() && $model->save()) {
            return ['errmsg'=>'设置成功'];
       }else {
          return $model;
       }
    }


   // 设置商家图片
    public function actionPic()
    {
        $user = Yii::$app->user->identity;
        $base64=Yii::$app->request->post('image');
        $type=trim(Yii::$app->request->post('type'));
        if($type!='shop' && $type!='apply'){
           return ['errcode'=>501,'errmsg'=>'参数错误']; 
        }
        $type='shop';
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/',$base64,$result)){
                $extension = $result[2];
                $filepath =Yii::getAlias("@upload").'/'.$type.'/'.$user->id.'/';
                if(!file_exists($filepath))
                {
                   mkdir($filepath,0700);
                }
                $name=time();
                $filepath = $filepath.$name.".{$extension}";
                $filename='/'.$type.'/'.$user->id.'/'.$name.".{$extension}";
                if(file_put_contents($filepath, base64_decode(str_replace($result[1],'', $base64)))){
                    $urlname=Yii::$app->params['host_domain'].Yii::$app->params['upload_url'].$filename;
                    $usershop=UserShop::find()->where(['uid'=>$user->id])->one();
                    $usershop->shop_pic=$urlname;
                    if($usershop->save(false)){
                       return ['datas'=>$urlname];
                    }
                    return ['errcode'=>501,'errmsg'=>'保存失败']; 
                }else{
                    return ['errcode'=>501,'errmsg'=>'保存失败']; 
                }
        }else{
            return ['errcode'=>501,'errmsg'=>'参数错误']; 
        }
    }




  


}