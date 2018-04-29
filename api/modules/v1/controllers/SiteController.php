<?php
namespace api\modules\v1\controllers;
use yii\db\Query;
use Yii;
use yii\data\ActiveDataProvider;
use common\models\User;
use common\models\Article;
use common\models\ArticleLog;
use common\models\ArticleClass;
class SiteController extends \api\common\controllers\MemberController
{
    // 发现列表
    public function actionIndex()
    {

        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $ac_id=intval($post['ac_id']);
        $query = (new Query())->select(['title','pic','id','share_count','visit_count','copy_count','(share_count+visit_count) as acount','username as author_name'])
          ->from('article')
          ->where(['state'=>1])->andWhere(['>','expires_time',time()]);
        if($ac_id>0){
             $query = $query->andWhere(['ac_id'=>$ac_id]);
              $total_count=$query->count();
              $query=$query->orderBy('acount  desc');
        }else{
            $query = $query->andWhere(['from_id'=>'0']);
            $total_count=$query->count();
            $query=$query->orderBy('add_time  desc');
        }
        $total_page= intval(($total_count+$pagesize-1)/$pagesize);
        if($page>$total_page){
           return ['datas'=>[]];
        }
        if($page<1){
          $page=1;
          $query= $query->limit($pagesize);
        }else{
          $query= $query->limit($pagesize)->offset(($page-1)*$pagesize);
        }
        $rows=$query->all();
        $rows=$rows?$rows:[];
        if(count($rows)==0){
           return ['datas'=>[]];
        }
        return ['datas'=>$rows]; 
    }


    // 搜索列表
    public function actionSearch()
    {

        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $keyword=trim($post['keyword']);
        $query = (new Query())->select(['title','pic','id','share_count','visit_count','username as author_name'])
          ->from('article')
          ->where(['state'=>1])->andWhere(['>','expires_time',time()]);
        if(!empty($keyword)){
             $query = $query->andwhere(['or',['like','username',$keyword],['like','title',$keyword],['like','desc',$keyword]]);
            $total_count=$query->count();
            $query=$query->orderBy('add_time  desc');
        }else{
            $total_count=$query->count();
            $query=$query->orderBy('add_time  desc');
        }
        $total_count=$query->count();
        $query=$query->orderBy('add_time  desc');
        $total_page= intval(($total_count+$pagesize-1)/$pagesize);
        if($page>$total_page){
           return ['datas'=>[]];
        }
        if($page<1){
          $page=1;
          $query= $query->limit($pagesize);
        }else{
          $query= $query->limit($pagesize)->offset(($page-1)*$pagesize);
        }
        $rows=$query->all();
        $rows=$rows?$rows:[];
        if(count($rows)==0){
           return ['datas'=>[]];
        }
        return ['datas'=>$rows]; 
    }



     //顶部tab
    public function actionTab()
    {
        $user = Yii::$app->user->identity;
        $data=[];
        $data[]=['id'=>0,'name'=>'最新'];
        $rows=ArticleClass::find()->select(['id','name'])->asArray()->all();
        foreach ($rows as $key => $value) {
          $data[]=$value;
        }
        return ['datas'=>$data]; 
    }
    
    // 文章详情
    public function actionView()
    { 
        $id=intval(Yii::$app->request->post('id'));
        $user = Yii::$app->user->identity;
        $article=(new Query())->select(['a.id','a.uid as author_id','a.title','a.add_time','a.pic','a.desc','b.content','c.username','c.headimgurl','c.is_auth','c.mobile','c.country','c.province','c.city','c.vip_endtime','c.is_shop','c.qrurl'])
        ->from('article a')
        ->leftJoin('article_content b', 'a.cid = b.id')
        ->leftJoin('user c', 'a.uid = c.id')
        ->where(['a.id'=>$id,'state'=>1])
        ->andWhere(['>','a.expires_time',time()])->one();
        if(empty($article)){
            return ['errcode'=>501,'errmsg'=>'文章已过期或已下架']; 
        }
        if(!empty($article['qrurl'])){
            $article['is_qr']=1;
        }else{
          $article['is_qr']=0;
        }
        $article['add_time']=date('Y-m-d',$article['add_time']);    
        if($user->id!=$article['author_id']){
            Article::updateAllCounters(['visit_count' => 1], ['id' => $id]);   
            // 记录访问记录
            if($article['author_id']>1){
                $article['is_self']=0;
                $al=new ArticleLog();
                $al->auid=$id;
                $al->aid=$id;
                $al->author_id=$article['author_id'];
                $al->uid=$user->id;
                $al->type=1;
                $al->stime=time();
                $al->etime=time()+rand(1,5);
                $al->save();
                $article['log_id']=$al->id;
            }else{
                $article['is_self']=0;
                $article['log_id']=0;
            }
           
        }else{
            $article['is_self']=1;
        }
        return ['datas'=>['article'=>$article]];
    }

    // 分享记录
    public function actionShareCount(){
        $user = Yii::$app->user->identity;
        $id=intval(Yii::$app->request->post('id'));//auid
        //$alog=ArticleLog::find()->where(['auid'=>$id,'uid'=>$user->id,'type'=>2])->one();
        //if(!$alog){
            $article=Article::find()->where(['id'=>$id,'state'=>1])->andWhere(['>','expires_time',time()])->one();
            if($article){
                if($article->uid!=$user->id){ // 其他人分享才记录
                    Article::updateAllCounters(['share_count' => 1], ['id' => $id]); 
                    $al=new ArticleLog();
                    $al->auid=$id;
                    $al->aid=$article->id;
                    $al->author_id=$article->uid;
                    $al->uid=$user->id;
                    $al->type=2;
                    $al->stime=time();
                    $al->etime=time();
                    $al->save();
                }
            }
       // }
        return [];
    }

    // 访问记录
    public function actionVisitCount(){
        $user = Yii::$app->user->identity;
        $log_id=intval(Yii::$app->request->post('log_id'));
        $alog=ArticleLog::find()->where(['id'=>$log_id,'type'=>1])->one();
        if($alog){
              if(($alog->etime-$alog->stime)<60){
                  $alog->etime=$alog->etime+mt_rand(1,5);
              }else{
                 $alog->etime=$alog->etime+mt_rand(25,29);
              }
              $alog->save();
        }
        return [];
    }

    
}
