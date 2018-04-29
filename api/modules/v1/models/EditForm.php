<?php

namespace api\modules\v1\models;

use common\models\User;
use yii\base\Model;
use Yii;
// 修改信息
class EditForm extends Model
{
    public $username;
    public $address;
    public $des;
    public $sex;
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
            [['username','sex','address','des'], 'filter','filter' =>'trim'],
            [['username'], 'required'],
            ['des', 'string','max'=>100],
            ['username', 'string','min'=>3,'max'=>50],
            ['username','match','pattern'=>'/^.*[^\d].*$/','message'=>'{attribute}不能全是数字'],
            ['username','exist', 'targetClass' =>'common\models\User'],
            ['sex','integer'],
            ['address','string','max'=>100],
        ];
    }
    public function attributeLabels()
    {
        return [
            'username' => '用户昵称',
            'address' => '地址',
            'des' => '个人简介',
            'sex' => '性别',
        ];
    }
     /**
     * 解析全地址( 北京 北京市 东城区)成省ID 市ID 区ID
     * @param string|array $fullArea
     * @return array
     */
    public static function parseFullArea($fullArea)
    {
        if (is_string($fullArea)) {
            $fullArea = explode(' ', $fullArea);
        }
        list($province, $city, $area) = $fullArea;
        return [
           $province,
           $city,
           $area
        ];

    }
    // 认证
    public function save()
    {
        if(!$this->validate()) {
             return false;
        }
        $this->_user->username=$this->username;
        $this->_user->sex=$this->sex;
        $this->_user->des=$this->des;
        $arr=self::parseFullArea($this->address);
        $this->_user->country=$arr[0];
        $this->_user->province=$arr[1];
        $this->_user->city=$arr[2];
        $this->_user->updated_at=time();
        if($this->_user->save(false)){
            return true;
        }
        return $this->_user;
    }
}
