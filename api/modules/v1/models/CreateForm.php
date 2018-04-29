<?php

namespace api\modules\v1\models;

use Yii;
use yii\base\Model;
use QL\QueryList;
use common\models\Article;
use common\models\ArticleContent;
/**
 * ContactForm is the model behind the contact form.
 */
class CreateForm extends Model
{
    public $url;
    public $id=0;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // name, email, subject and body are required
            [['url'], 'required'],
            ['url', 'url', 'defaultScheme' => 'https'],
            ['id','integer'],
            //[['url'],'match','pattern'=>'/','message'=>'提示信息'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'url' => '文章链接',
        ];
    }


   public function save(){

    $transaction = Yii::$app->db->beginTransaction();
    try { 
           $arr = parse_url($this->url);
           $path=$arr['path'];
           $host=$arr['host'];
           $is_toutiao=strstr($host, 'm.toutiao') || strstr($host, 'www.toutiao');   
           if(!$is_toutiao &&  $host!='mp.weixin.qq.com'){
              throw new \Exception('目前只支持头条和微信的文章');
           }
           // 判断是否已经创建过该文章
           $art=Article::find()->where(['source'=>$path,'uid'=>Yii::$app->user->identity->id])->one();
           if($art){
              throw new \Exception('您已经创建过该文章,请在我的文章里面查看');
           }
           // 判断是否已经采集
           // $ac=ArticleContent::find()->where(['source'=>$path])->one();
           // if(!$ac){
              if($host=='mp.weixin.qq.com'){
                  $data=$this->get_wx_data($this->url);
              }else{
                  $data=$this->get_toutiao_data($this->url);
              }
              if(empty($data)){
                throw new \Exception('采集失败');
              }
              $ac=new ArticleContent();
              $ac->content=$data['content'];
              $ac->source= $path;
              if(!$ac->save()){
                $error =$ac->FirstErrors;
                throw new \Exception(current($error));
              }
          // }
           $article=new Article();
           $article->uid=Yii::$app->user->identity->id;
           $article->username=Yii::$app->user->identity->username;
           $article->source= $path;
           $article->is_hot=0;
           $article->ac_id=0;
           $article->from_id=0;
           $article->is_get=1;
           $article->desc='';
           $article->title=$data['title'];
           $article->pic=$data['pic'];
           $article->share_count=0;
           $article->visit_count=0;
           $article->expires_time=time()+24*3600*7;
           $article->state=0;
           $article->cid=$ac->id;
            if(!$article->save()){
              $error =$article->FirstErrors;
              throw new \Exception(current($error));
            }
            $transaction->commit();
            $this->id=$article->id;
            return true;
        }catch (\Exception $e) {
            $transaction->rollBack();
            $this->addError('url', $e->getMessage());
            return false;
        }
    }

     public function get_toutiao_data($url){

        $ret=[];
        $ql = QueryList::getInstance();
        $ql->bind('myHttp',function ($url){
            $html = file_get_contents($url);
            $this->setHtml($html);
            return $this;
        });
        $data = $ql->rules([
            'title'=>['h1','text'],
            'content' => ['div.article-content','html'],
         ])->myHttp($url)->query()->getData();
        $data=$data->all();
        $ret['title']=$data[0]['title'];
        $ret['content']=$data[0]['content'];
        $pql = QueryList::html($ret['content'])->rules(array('pic' => array('img','src')))->query()->getData();
        $pics=$pql->all();
        if(is_array($pics)){
          $ret['pic']=$pics[0]['pic'];
        }else{
          $ret['pic']='';
        }
        return $ret;
        // $ql = QueryList::getInstance();
        // $ql->bind('myHttp',function ($url){
        //     $html = file_get_contents($url);
        //     $this->setHtml($html);
        //     return $this;
        // });
        // $data = $ql->rules([
        //     'title'=>['h1','text'],
        //     'content' => ['div.article-content','html'],
        //  ])->myHttp($url)->query()->getData();
        // $data=$data->all();
        // return $data[0];
    }

    public function get_wx_data($url){
        $ret=[];
        $ql = QueryList::getInstance();
        $ql->bind('myHttp',function ($url){
             $html = file_get_contents($url);
             //$html = iconv('GBK','UTF-8', $html);
             $html = str_replace("<!--headTrap<body></body><head></head><html></html>-->", "", $html);
             $html=str_replace("<!--tailTrap<body></body><head></head><html></html>-->", "", $html);
             $this->setHtml($html);
             return $this;
        });
        $data = $ql->rules([
            'title'=>['#activity-name','text'],
            'content' => ['#js_content','html'],
         ])->myHttp($url)->query()->getData();
        $data=$data->all();
        $ret['title']=$data[0]['title'];
        $ret['content']=$data[0]['content'];
        $pql = QueryList::html($ret['content'])->rules(array('pic' => array('img','data-src')))->query()->getData();
        $pics=$pql->all();
        if(is_array($pics)){
          $ret['pic']=$pics[0]['pic'];
        }else{
          $ret['pic']='';
        }
        return $ret;
    }

   
}
