<?php

namespace api\modules\v1\models;

use Yii;
use yii\base\Model;
use QL\QueryList;
use common\models\UserGoods;
use common\models\UserGoodsClass;

class GoodsUpdateForm extends Model
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
    private $goods;
    public function __construct($id, $config = [])
    {

        $this->goods=UserGoods::findOne($id);
        if (!$this->goods) {
            throw new InvalidParamException('商品数据错误');
        }
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
        $this->goods->title=$this->title;
        $this->goods->gc_id=$this->gc_id;
        $this->goods->gc_name=$gcname->title;
        $this->goods->desc=$this->desc;
        $this->goods->pic=$this->pic;
        $this->goods->mprice=$this->mprice;
        $this->goods->price=$this->price;
        $this->goods->imgs=$this->imgs;
        $this->goods->body=$this->body;
        $this->goods->num=$this->num;
        $this->goods->update_at=time();
        if($this->goods->save()){
            return true;
        }else{
          $error =$this->goods->FirstErrors;
          $this->addError('title',current($error));
        }
    }

 
   
}
