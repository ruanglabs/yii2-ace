<?php
namespace api\modules\v1\controllers;
use Yii;
use common\models\UploadForm;
use yii\web\UploadedFile;
class AuthController extends \api\common\controllers\Controller
{
    // 认证接口
    public  function actionAuth()
    {
        $return= trim(Yii::$app->request->post('return'));
        $return='https://ddwen.yunpusher.com/site/login?code=&state='.urlencode($return);
        $data=[];
        $data['url']=$return;
        return ['datas'=>$data];
    }
    // 分享签名接口
    public  function actionShare()
    {
        $url= trim(Yii::$app->request->post('url'));
        $data=Yii::$app->wechat->jsApiConfig(['jsApiList'=>['onMenuShareTimeline','onMenuShareAppMessage']],$url);
        return ['datas'=>$data,'url'=>$url];
    }

    public function actionPic(){
        $model = new UploadForm();
        $name= time().rand(0,9);
        $model->file = UploadedFile::getInstanceByName('filename');
        if ($model->upload($name,'goods')){
             return ['errmsg'=>'上传成功','datas'=>['filename'=>$model->filename,'url'=>$model->urlname]];
        }else{
          return ['errcode'=>501,'errmsg'=>'上传失败'];
        }
    }


    public function actionTest(){
                        // 发送消息
                $mes=new \common\models\Message();
                $order=\common\models\Order::find()->one();
                $mes->f_id=0;
                $mes->t_id=$order->shop_id;
                $mes->title='您有新的订单';
                $mes->msg='您有新的订单，已支付，单号:'.$order->order_sn.'已发货,<a href="my_order_info.html?id='.$order->id.'">点击查看<a/>.';
                $mes->pram=$mes->extra(['type'=>'order','id'=>$order->id,'order_sn'=>$order->order_sn]);
                $mes->add_time=time();
                $mes->read=0;
                if (!$mes->save()) {
                    throw new \Exception('保存失败[消息发送失败]');
                }
    }
}