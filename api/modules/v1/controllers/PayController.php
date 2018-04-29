<?php
namespace api\modules\v1\controllers;

use Yii;
use yii\log\FileTarget;
use api\common\controllers\Controller;
use common\components\WxJsPay;
use common\models\VipOrder;
use common\models\User;
use common\models\VipLog;
use common\models\OrderLogic;
use common\models\Order;
class PayController extends Controller
{
    // 日志记录
    private function log($data, $type)
    {
        $log             = new FileTarget();
        $log->logFile    = Yii::$app->getRuntimePath() . '/logs/' . $type . '-' . date('Y-m-d', time()) . '.log';
        $log->messages[] = [$data, 1, 'application', microtime(true)];
        $log->export();
    }

    // 微信支付回调
    public function actionWxpayNotify()
    {
        $wxjsPay     = new WxJsPay();
        $xml       = file_get_contents("php://input");
        $arr       = $wxjsPay->xmlToArray($xml);
        $this->log($arr, 'wxpay');
        //验证签名
        if(!isset($arr['sign'])){
            $msg=['签名验证失败'];
            $this->log($msg, 'wxpay-error');
        }
        $wxsign=$arr['sign'];
        unset($arr['sign']);
        $sign=$wxjsPay->sign($arr);
        if($sign!=$wxsign){
           $msg=[];
           $msg['msg']=['签名验证失败'];
           $msg['wxsign']=$arr['sign'];
           $msg['checksing']=$sign;
           $this->log($msg, 'wxpay-error');
        }
        // 验证成功后 处理
        if ($arr['result_code'] == 'SUCCESS') {
            $payinfo = [
                'trade_no'     => $arr['out_trade_no'],
                'out_trade_no'     => $arr['transaction_id'],
                'status'       => 1,
                'paycode'      => 'wxpay',// arr['trade_type']
                'total_fee'    => intval($arr['total_fee']),
                'type'=>$arr['attach']
            ];
            $result = $this->payed($payinfo);
            if (!$result[0]) {
                $this->log($result, 'wxpay-error');
                $data['return_code'] = 'FALI';
                $data['return_msg']  = $result[1];
                echo $wxjsPay->arrayToXml($data);
                exit;
            } else {
                $data['return_code'] = 'SUCCESS';
                $data['return_msg']  = 'OK';
                echo $wxjsPay->arrayToXml($data);
                exit;
            }
        }
    }

    // 支付成功
    public function payed($payinfo)
    {
        $total_fee = $payinfo['total_fee'];
        $type=$payinfo['type'];
        if($type=='v'){
            $order= VipOrder::findOne(['order_sn' =>  $payinfo['trade_no']]);
            if (!$order) {
                return [false, '订单不存在'];
            }
            if ($order->state == 1) {
                return [true, '已支付成功'];
            }
            if ($order->amount * 100 != $total_fee) {
                return [false, '支付金额错误'];
            }
            $user = User::findOne($order->uid);
            if (!$user) {
                return [false, '用户信息错误'];
            }
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $order->state = 1;
                $order->pay_time  = time();
                $order->out_trade_no  = $payinfo['out_trade_no'];
                $log              = new VipLog();
                $log->uid   = $user->id;
                $log->username = $user->username;
                $log->days   = $order->days;
                $log->des   = '购买VIP'.$order->days.'天';
                $log->from   = 1;
                $log->from_id   = 0;
                $log->created_at    = time();
                $log->update_at    = time();
                $user->vip_days+= $order->days;
                if($user->vip_endtime>time()){
                     $user->vip_endtime+=$order->days*86400;
                }else{
                    $user->vip_endtime=time()+$order->days*86400;
                }
                if(!$user->save()) {
                     throw new \Exception('更新用户状态失败');
                }
                if (!$log->save()) {
                      $error =$log->FirstErrors;
                      throw new \Exception(current($error));
                     //throw new \Exception('更新用户日志失败');
                }
                if (!$order->save()) {
                    throw new \Exception('更新订单状态失败');
                }
                $transaction->commit();
                return [true, '支付成功'];
            } catch (Exception $e) {
                $transaction->rollback();
                return [false, $e->getMessage()];
            }
        }else{
            return OrderLogic::changeOrderReceivePay($payinfo['trade_no'], 'system', '系统', $payinfo);
        }
    }

   public function actionTest(){
         $payinfo = [
                'trade_no'     => '470571157754722016',
                'out_trade_no'     => '470571157754722016test',
                'status'       => 1,
                'paycode'      => 'wxpay',// arr['trade_type']
                'total_fee'    => 1,
                'type'=>'g'
            ];

             return OrderLogic::changeOrderReceivePay($payinfo['trade_no'], 'system', '系统', $payinfo);


   }





}
