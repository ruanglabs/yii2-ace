<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\db\Query;
use common\helpers\Url;
use api\modules\v1\models\CreateForm;
use api\modules\v1\models\SendCodeForm;
use api\modules\v1\models\AuthForm;
use api\modules\v1\models\EditForm;
use common\models\Article;
use common\models\ArticleLog;
class MemberController extends \api\common\controllers\MemberController
{
    // 个人中心数据
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $data=[];
        $data['id']= $user->id;
        $data['username']= $user->username;
        $data['headimgurl']=$user->headimgurl;
        $data['is_auth']=$user->is_auth;
        $data['is_shop']=$user->is_shop;
        $time=$user->vip_endtime-time();
        $vip=$time>0?1:0;
        $day=($time>0)?intval($time/(24*3600)):0;
        $data['is_vip']= $vip;
        $data['a_count']=(new Query())->select(['aid'])->from('article')->where(['uid'=>$user->id,'state'=>1])->count();
        $data['order_count']=(new Query())->select(['id'])->from('order')->where(['buyer_id'=>$user->id,'state'=>['10','20','30']])->count();
        if(empty($user->des) || empty($user->qrurl) || empty($user->qrurl)){
           $data['is_set']=0;  
        }else{
            $data['is_set']=1;  
        }
        $data['d_count']=$day; 
        return ['datas'=>$data];
    }

    // 用户编辑页面用户信息
    public function actionView()
    {
         $user = Yii::$app->user->identity;
         $data=[];
         $data['username']=$user->username;
         $data['headimgurl']=$user->headimgurl;
         $data['sex']=$user->sex;
         $data['is_auth']=$user->is_auth;
         $data['mobile']=$user->mobile?$user->mobile:'';
         $data['country']=$user->country?$user->country:'';
         $data['province']=$user->province?$user->province:'';
         $data['city']=$user->city?$user->city:'';
         $data['qrurl']=$user->qrurl?$user->qrurl:'';
         $data['des']=$user->des?$user->des:'';
         return  ['datas'=>$data];
    }
    // 修改用户信息
    public function actionEdit()
    {
       $model = new EditForm(Yii::$app->user->identity);
       $model->load(Yii::$app->request->post(),'');
       if ($model->save()) {
            return ['errmsg'=>'修改成功'];
       }else {
           return $model;
       }
    }
    // 设置用户头像/二维码
    public function actionAvatar()
    {
        $user = Yii::$app->user->identity;
        $base64=Yii::$app->request->post('image');
        $type=trim(Yii::$app->request->post('type'));
        if($type!='avatar' && $type!='qrcode'){
           return ['errcode'=>501,'errmsg'=>'参数错误']; 
        }
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/',$base64,$result)){
                $extension = $result[2];
                $filepath =Yii::getAlias("@upload").'/'.$type.'/';
                if(!file_exists($filepath))
                {
                   mkdir($filepath,0700);
                }
                $name=time();
                $filepath = $filepath.$name.".{$extension}";
                $filename='/'.$type.'/'.$name.".{$extension}";
                if(file_put_contents($filepath, base64_decode(str_replace($result[1],'', $base64)))){
                    $urlname=Yii::$app->params['host_domain'].Yii::$app->params['upload_url'].$filename;
                    if($type=='avatar'){
                       $user->headimgurl=$urlname;
                    }else{
                       $user->qrurl=$urlname;
                    }
                    if($user->save(false)){
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

    //发送短信验证码
    public function actionSendCode()
    {
       $model = new SendCodeForm(Yii::$app->user->identity);
       $model->load(Yii::$app->request->post(),'');
       if ($model->send()) {
            return ['errmsg'=>'发送成功'];
        }else {
           return $model;
        }
    }

    //认证
    public function actionAuth()
    {
       $model = new AuthForm(Yii::$app->user->identity);
       $model->load(Yii::$app->request->post(),'');
       if ($model->auth()) {
            return ['errmsg'=>'发送成功'];
        }else {
           return $model;
        }
    }
    //创建文章
    public function actionCreate()
    {
       $model = new CreateForm();
       $model->load(Yii::$app->request->post(),'');
       if ($model->validate() && $model->save()) {
            return ['errmsg'=>'创建成功','datas'=>$model->id];
       }else {
          return $model;
        }
    }
    
    //复制文章
    public function actionCopy()
    {
       $user = Yii::$app->user->identity;
       $id=intval(Yii::$app->request->post('id'));
       $au=Article::findOne($id);
       
       $model=Article::find()->where(['cid'=>$au->cid,'uid'=>$user->id])->one();
       if($model){
           return ['errmsg'=>'复制成功','datas'=>['id'=>$model->id]];
       }else{
            $model=new Article();
            $model->uid=Yii::$app->user->identity->id;
            $model->username=Yii::$app->user->identity->username;
            $model->source='';
            $model->is_hot=0;
            $model->ac_id=$au->ac_id;
            $model->from_id=$au->id;
            $model->is_get=0;
            $model->desc=$au->desc;
            $model->title=$au->title;
            $model->pic=$au->pic;
            $model->share_count=0;
            $model->visit_count=0;
            $model->expires_time=time()+24*3600*7;
            $model->state=1;
            $model->cid=$au->cid;
            if(!$model->save()){
              $error =$model->FirstErrors;
              throw new \Exception(current($error));
            }
            // 记录复制信息
            $al=new ArticleLog();
            $al->auid=$id;
            $al->aid=$au->id;
            $al->author_id=$au->uid;
            $al->uid=$user->id;
            $al->type=3;
            $al->stime=time();
            $al->etime=time();
            if(!$al->save()){
              $error =$al->FirstErrors;
              throw new \Exception(current($error));
              
            }
            Article::updateAllCounters(['copy_count' => 1], ['id' => $id]);   
           if($model->save()){
              return ['errmsg'=>'复制成功','datas'=>['id'=>$model->id]];
           }else{
             return $model;
           }
       }
    }


}