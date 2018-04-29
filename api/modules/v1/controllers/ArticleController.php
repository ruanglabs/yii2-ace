<?php

namespace api\modules\v1\controllers;
use Yii;
use yii\db\Query;
use common\helpers\Url;
use yii\data\ActiveDataProvider;
use common\models\Article;
use common\models\ArticleClass;
class ArticleController extends \api\common\controllers\MemberController
{

    // 统计文章数量
    public function actionArticleCount(){
        $data['l_count']=Article::find()->where(['uid'=>Yii::$app->user->identity->id,'state'=>'1'])->count(); 
        $data['d_count']=Article::find()->where(['uid'=>Yii::$app->user->identity->id,'state'=>'0'])->count(); 
        return ['datas'=>$data];
    }
    // 我的文章
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $query = (new Query())
           ->select(['title','pic','add_time','share_count','visit_count','copy_count','expires_time','id'])
          ->from('article')
          ->where(['uid'=>$user->id,'state'=>1]);
        $total_count=$query->count();
        $query=$query->orderBy('id  desc');
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
        foreach ($rows as $k => $v) {
            $rows[$k]['add_time']=date('Y-m-d ',$v['add_time']);
            $rows[$k]['is_expires']=($v['expires_time']<time())?0:1;
        }
        return ['datas'=>$rows]; 
    }

    // 我的文章草稿
    public function actionDelList()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $query = (new Query())->select(['title','pic','add_time','share_count','visit_count','copy_count','expires_time','id'])
          ->from('article')
          ->where(['uid'=>$user->id,'state'=>0]);
        $total_count=$query->count();
        $query=$query->orderBy('id  desc');
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
        foreach ($rows as $k => $v) {
            $rows[$k]['add_time']=date('Y-m-d h:i:s',$v['add_time']);
            $rows[$k]['is_expires']=($v['expires_time']<time())?0:1;
        }
        return ['datas'=>$rows]; 
    }
    // 编辑详情
    public function actionView()
    {
        $user = Yii::$app->user->identity;
        $id=intval(Yii::$app->request->post('id'));
        $info=(new Query())->select(['a.title','b.content','a.add_time','a.share_count','a.visit_count','a.copy_count','a.id'])
          ->from('article a')
          ->leftJoin('article_content b', 'a.cid = b.id')
          ->where(['a.uid'=>$user->id,'a.state'=>0,'a.id'=>$id])->one();
        return ['datas'=>['article'=>$info]];
    }

    //发布
    public function actionPulish()
    {
        $user = Yii::$app->user->identity;
        $id=intval(Yii::$app->request->post('id'));
        $article=Article::find()->where(['uid'=>$user->id,'id'=>$id,'state'=>0])->one();
        if(!$article){
           return ['errcode'=>501,'errmsg'=>'参数错误']; 
        }
        $article->state=1;
        if(!$article->save()){
            return ['errcode'=>501,'errmsg'=>'发布失败']; 
        }
        return ['errmsg'=>'发布成功']; 
    } 

    // 删除
    public function actionDelete()
    {
        $user = Yii::$app->user->identity;
        $id=intval(Yii::$app->request->post('id'));
        $article=Article::find()->where(['uid'=>$user->id,'id'=>$id])->one();
        if(!$article){
           return ['errcode'=>501,'errmsg'=>'参数错误']; 
        }
        if(!$article->delete()){
            return ['errcode'=>501,'errmsg'=>'保存失败']; 
        }
        return ['errmsg'=>'删除成功']; 
    }

    //谁查看我统计 
    public function actionListCount()
    {
         $user = Yii::$app->user->identity;
         $start = strtotime(date('Y-m-d 00:00:00', time()));   
         $end = strtotime(date('Y-m-d 23:59:59', time()));
         // 今日统计
         $today_sum_visit= (new Query())->from('article_log')->where(['author_id'=>$user->id,'type'=>1])->andWhere(["between",'stime',$start,$end])->count();
         $today_sum_copy= (new Query())->from('article_log')->where(['author_id'=>$user->id,'type'=>3])->andWhere(["between",'stime',$start,$end])->count();
         $today_sum_share= (new Query())->from('article_log')->where(['author_id'=>$user->id,'type'=>2])->andWhere(["between",'stime',$start,$end])->count();
          // 累计
         $all_sum_visit=(new Query())->from('article_log')->where(['author_id'=>$user->id,'type'=>1])->count();
         $all_sum_share= (new Query())->from('article_log')->where(['author_id'=>$user->id,'type'=>2])->count();
         $all_sum_copy=(new Query())->from('article_log')->where(['author_id'=>$user->id,'type'=>3])->count();
         $data['all_sum']=$all_sum_visit+$all_sum_share+$today_sum_copy;
         $data['today_sum']=$today_sum_visit+$today_sum_share+$all_sum_copy;
         return ['datas'=>$data]; 
    }
     // 鉴权
    public function actionCheckVip(){
        $user = Yii::$app->user->identity;
        $is_vip=($user->vip_endtime<time())?0:1;
        $data['is_vip']=$is_vip;
        return ['datas'=>$data]; 
    }

    // 谁查看我 列表
    public function actionList()
    {
        $user = Yii::$app->user->identity;
        // 判断是否是vip
        $is_vip=($user->vip_endtime<time())?0:1;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $query = (new Query())->select(['title','pic','id','share_count','visit_count','copy_count'])
        ->from('article')
        ->where(['uid'=>$user->id,'state'=>1])
        ->orderBy('id  desc');
        $total_count=$query->count();
        $total_page= intval(($total_count+$pagesize-1)/$pagesize);
        if($page>$total_page){
           return [];
        }
        if($page<1){
          $page=1;
          $query= $query->limit($pagesize);
        }else{
          $query= $query->limit($pagesize)->offset(($page-1)*$pagesize);
        }
        $rows=$query->all();
        $id_arr=array_column($rows,'id');
        $id_arr=array_values($id_arr);
        // // 查询出所有访问者
        $q=(new Query())->select(['a.aid','a.uid','b.headimgurl'])->from('article_log a')
         ->leftJoin('user b','a.uid=b.id')->where(['a.aid'=>$id_arr])->groupBy(['a.aid','a.uid']);
        $visitor=$q->all();
        // 是否vip 进行处理
        foreach ($rows as $key => $value) {
            $rows[$key]['visitor']=[];
            $rows[$key]['is_vip']=$is_vip;
            foreach ($visitor as $k => $v) {
                if($v['aid']==$value['id']){
                    $rows[$key]['visitor'][]=$v;
                }
            }
            $rows[$key]['vcount']=count($rows[$key]['visitor']);
        }
        return ['datas'=>$rows]; 
    }

    // 访问记录列表
    public function actionLogView()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $id=intval($post['id']);
         $info=(new Query())->select(['title','pic','add_time','share_count','visit_count','copy_count','id'])
          ->from('article')
          ->where(['uid'=>$user->id,'state'=>1,'id'=>$id])->one();
        return ['datas'=>['article'=>$info]];
    }

    // 访问记录列表
    public function actionLogList()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $page=(isset($post['page']))?intval($post['page']):1;
        $pagesize=(isset($post['pagesize']))?intval($post['pagesize']):10;
        $offset=($page)*$pagesize;
        $id=intval($post['id']);
        $query = (new Query())->select(['a.uid','a.stime','a.etime','a.type','b.headimgurl','b.username'])->from('article_log a')
         ->leftJoin('user b','a.uid=b.id')->where(['a.aid'=>$id,'author_id'=>$user->id])->orderBy('a.etime desc');
        $total_count=$query->count();
        $total_page= intval(($total_count+$pagesize-1)/$pagesize);
        if($page>$total_page){
           return [];
        }
        if($page<1){
          $page=1;
          $query= $query->limit($pagesize);
        }else{
          $query= $query->limit($pagesize)->offset(($page-1)*$pagesize);
        }
        $rows=$query->all();
        foreach ($rows as $key => $value) {
           $rows[$key]['stoptime']=$this->praseTtime($value['etime']-$value['stime']);
           $rows[$key]['stime']=$this->praseTtimeLeft($value['stime']);
           unset($rows[$key]['etime']);
        }
        return ['datas'=>$rows]; 
    }

    // 用户访问统计
    public function actionLogCount()
    {
        $user = Yii::$app->user->identity;
        $post=Yii::$app->request->post();
        $id=intval($post['id']);
        $user_info=(new Query())->select(['id','headimgurl','username'])->from('user')->where(['id'=>$id])->one();
        $sum_visit=(new Query())->from('article_log')->where(['author_id'=>$user->id,'type'=>1,'uid'=>$id])->count();
        $sum_share= (new Query())->from('article_log')->where(['author_id'=>$user->id,'type'=>2,'uid'=>$id])->count();
        $sum_copy= (new Query())->from('article_log')->where(['author_id'=>$user->id,'type'=>3,'uid'=>$id])->count();
        $last=(new Query())->select(['stime'])->from('article_log')->where(['author_id'=>$user->id,'type'=>1,'uid'=>$id])->orderBy('stime desc')->one();
        $data['visit']=$sum_visit;
        $data['share']= $sum_share;
        $data['copy']= $sum_copy;
        $data['lasttime']=date('Y-m-d H:i:s',$last['stime']);
        $data['id']= $user_info['id'];
        $data['headimgurl']= $user_info['headimgurl'];
        $data['username']= $user_info['username'];
        return ['datas'=>$data]; 
    }

    public function praseTtime($t)
    {
       if($t<60){
         return $t.'秒';
       }else{
          return intval($t/60).'分钟';
       }
    }

    public function praseTtimeLeft($t)
    {
      $str='';
      $v=abs(time()-$t);
      if($v>86400){
         //$a=intval($v/86400);
         //$str.= $a."天前";
         return date('Y-m-d H:i:s',$t);
      }
      if($v>3600){
        $str=intval($v/3600)."小时前";
        return $str;
      }
      if($v>60){
          $str=intval($v/60)."分钟前";
           return $str;
      }
      $str=$v."秒前";
      return $str;
    }


}