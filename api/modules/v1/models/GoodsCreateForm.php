<?php

namespace api\modules\v1\models;

use Yii;
use yii\base\Model;
use QL\QueryList;
use common\models\UserGoods;
use common\models\UserGoodsClass;

class GoodsCreateForm extends Model
{
    public $title;
    public $gc_id;
    public $uid;
    public $pic;
    public $num;
    public $mprice;
    public $price;
    public $body;
    public $desc;
    public $imgs;
    private $_user;
    public function __construct($user, $config = [])
    {
        if (empty($user)) {
            throw new InvalidParamException('用户数据错误');
        }
        $this->_user = $user;
        parent::__construct($config);
    }
    public function rules()
    {
        return [
            [[ 'title','gc_id','pic','body','num','mprice', 'price' ], 'required'],
            [['gc_id','num',], 'integer'],
            [['mprice', 'price'], 'number'],
            [['body'], 'string'],
            [['title','desc'], 'string', 'max' => 100],
            [['pic', 'imgs'], 'string', 'max' => 1000],
        ];
    }
      /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'title' => '商品名称',
            'gc_id' => '商品分类',
            'uid' => 'Uid',
            'desc' => '商品简介',
            'pic' => '商品图片',
            'mprice' => '市场价',
            'price' => '价格',
            'imgs' => '图片',
            'num' => '库存',
            'body' => '商品内容',
        ];
    }


   public function save(){

        if(!$this->validate()) {
             return false;
        }
        $gcname=UserGoodsClass::findOne($this->gc_id);
        $goods=new UserGoods();
        $goods->title=$this->title;
        $goods->gc_id=$this->gc_id;
        $goods->gc_name=$gcname->title;
        $goods->uid=$this->_user->id;
        $goods->desc=$this->desc;
        $goods->pic=$this->pic;
        $goods->mprice=$this->mprice;
        $goods->price=$this->price;
        $goods->imgs=$this->imgs;
        $goods->body=$this->body;
        $goods->num=$this->num;
        $goods->state=1;
        $goods->view=0;
        $goods->sale=0;
        $goods->created_at=time();
        $goods->update_at=time();
        if($goods->save()){
            return true;
        }else{
          $error =$goods->FirstErrors;
          $this->addError('title',current($error));
        }
    }

 
   
}
