<?php

namespace api\modules\v1\models;

use Yii;
use yii\base\Model;
use QL\QueryList;
use common\models\UserGoods;
use common\models\Cart;
use common\models\User;
// 购物车
class CartForm extends Model
{
    public $id;
    public $num;
    public $goods;
    public $seller;
    public $buyer;
    public function __construct($id,$num,$user,$config = [])
    {
        $this->id=$id;
        $this->num=$num;
        if (empty($user)) {
            throw new InvalidParamException('用户数据错误');
        }
        $this->buyer = $user;
        $this->goods =UserGoods::find()->where(['id'=>$id,'state'=>1])->one();
        if (empty($this->goods)) {
            throw new InvalidParamException('商品数据错误');
        }
        $this->seller=User::findOne($this->goods->uid);
        if (empty($this->seller)) {
            throw new InvalidParamException('卖家数据错误');
        }
        parent::__construct($config);
    }
    public function rules()
    {
        return [
            [[ 'id','num' ], 'required'],
            [['id','num',], 'integer'],
        ];
    }
      /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '商品ID',
            'num' => '商品数量',
        ];
    }

    public function save(){

      $transaction = Yii::$app->db->beginTransaction();
      try {
           if($this->buyer->id==$this->goods->uid){
               throw new \Exception('不能购买自己的商品');
           }
           // 先查询是否有
           $cart=Cart::find()->where(['bid'=>$this->buyer->id,'gid'=>$this->id])->one();
           if($cart){
               $cart->gnum+=$this->num;
               if($cart->gnum>$this->goods->num) {
                  throw new \Exception('商品库存不足');
               }
           }else{
               if($this->num>$this->goods->num) {
                  throw new \Exception('商品库存不足');
               }
               $cart=new Cart();
               $cart->bid=$this->buyer->id;
               $cart->sid=$this->seller->id;
               $cart->sname=$this->seller->username;
               $cart->gid=$this->goods->id;
               $cart->gname=$this->goods->title;
               $cart->gprice=$this->goods->price;
               $cart->gnum=$this->num;
               $cart->gpic=$this->goods->pic;
           }
           if(!$cart->save()){
              $error =$cart->FirstErrors;
              throw new \Exception(current($error));
           }
           $transaction->commit();
           return $cart;
        }catch (\Exception $e) {
            $transaction->rollBack();
            $this->addError('id', $e->getMessage());
            return false;
        }
    }

 
   
}
