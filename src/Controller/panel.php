<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\CmsUsers;
use App\Entity\General;
use App\Entity\Articles;
use App\Entity\Goods;
use App\Entity\Users;
use App\Entity\Orders;

/*
Alexander Patiukov
Copyright, 2018
Keyhole CMS 
Founded 14-VIII/18
*/

/*
We use: 
	snake_case for database names
	camelCase for variables et cetera 
*/


class panel extends AbstractController
{
public function login()
    {
		// Checking if user is already logged in
		@session_start();
		if(!isset($_SESSION['panel_login'])) {
		header('location: /panel');
		exit();
		}
	}
/**
* @Route("/panel")
*/
	  public function loginpage()
    {
		@session_start();
		$_SESSION['enabled']=true;
		return $this->render('panelLogin.html.twig', array(
		'error' => @$_GET['err'],
		));
	}
/**
* @Route("/panel/login")
*/
	  public function panel()
    {
		@session_start();
		if(!@$_SESSION['enabled']) {
			header('/panel?err=1');
			exit();
		}
		if(!@$_SESSION['trials'] || (time()-@$_SESSION['last']) > 900) {	// Instead of captcha
		$_SESSION['trials']=1;
		$_SESSION['last']=time();
		}
		if($_SESSION['trials']<5) {
			$_SESSION['trials']++;
			$_SESSION['last']=time();
		} else {
		header('Location: /panel?err=1');
		exit();
		}
		if(!@$_POST['login'] || !@$_POST['password']) {
		header('Location: /panel?err=2');
		exit();
		}
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => mb_substr(htmlspecialchars($_POST['login'], ENT_QUOTES), 0, 32)]);
		
		// На случай поломки
		//if(mb_substr(htmlspecialchars($_POST['password'], ENT_QUOTES), 0, 32)==$user->getPassword()) {
			//Строку ниже-- закомментировать
			if(password_verify(mb_substr(htmlspecialchars($_POST['password'], ENT_QUOTES), 0, 32),$user->getPassword())) {
			$_SESSION['panel_login']=$user->getLogin();
			$user->setLastLogin((date('H:i:s d.m', time())));
			$entityManager->persist($user);
			$entityManager->flush();
			header('Location: /panel/main');
			exit();
		} else {
		header('Location: /panel?err=2');
		exit();
		}
	
	}
/**
* @Route("/panel/main")
*/
	  public function mainpage()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		
		//$user->setRights('{"general":true,"art":true,"cmsUsers":true,"users":true,"goods":true,"orders":true}');
		//$entityManager->persist($user);
		//$entityManager->flush();
		
		$rights = json_decode($user->getRights(), TRUE);
		return $this->render('panelIndex.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		));
	}
/**
* @Route("/panel/cmsusers")
*/
	  public function cmsuserslist()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['cmsUsers']) {
		header('Location: /panel/main');
		}
		if(!@$_GET['page'] || @$_GET['page']==1) {
		$from = 0;
		$page = 1;
		} else {
		$page = $_GET['page'];
		$from = ($page-1) * 30;
		}
		$to = $from + 30;
		$users = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findBy([],['id'=>'DESC'], $to, $from);
		
		
		return $this->render('panelCmsUsersList.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		'users' => @$users,
		'page' => $page,
		));
	}
/**
* @Route("/panel/cmsusers/new")
*/
	  public function cmsusersnew()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!@$rights['cmsUsers']) {
		header('Location: /panel/main');
		}
		
		if(@$_POST['login'] && @$_POST['password']){
		$userNew = new CmsUsers();
		$userNew->setLogin($_POST['login']);
		$userNew->setPassword(password_hash($_POST['password'], PASSWORD_BCRYPT));
		$rightsNew = array();
		foreach(@$_POST['rights'] as $right) {
		$rightsNew[$right] = true;
		}
		$final = json_encode($rightsNew);
		$userNew->setRights($final);
		$userNew->setLastLogin(time());
		$entityManager->persist($userNew);
		$entityManager->flush();
		header('location: /panel/cmsusers');
		exit();
		}
		
		return $this->render('panelCmsUsersNew.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		));
	}
/**
* @Route("/panel/cmsusers/pass/{id}/{pass}")
*/
	  public function cmsuserspass($id, $pass)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!@$rights['cmsUsers']) {
		exit('1');
		} else {
		$pass = json_decode($pass, TRUE);
		$pass = $pass[0];
		$userNew = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->find($id);
		$userNew->setPassword(password_hash($pass, PASSWORD_BCRYPT));
		$entityManager->persist($userNew);
		$entityManager->flush();
		exit('response:ok');
		}
		exit('3');
	}
/**
* @Route("/panel/cmsusers/rights/{id}")
*/
	  public function cmsusersrights($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!@$rights['cmsUsers']) {
		exit('reponse:err');
		} else {
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->find($id);
		$rights = json_decode($user->getRights(), TRUE);
		return $this->render('panelCmsUsersRights.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		'id' => $id,
		));
		}
	}
/**
* @Route("/panel/cmsusers/rights/change/{id}")
*/
	  public function cmsusersrightsedit($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!@$rights['cmsUsers']) {
		header('location: /panel/main');
		} else {
		$userNew = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->find($id);

		$rightsNew = array();
		foreach(@$_POST['rights'] as $right) {
		$rightsNew[$right] = true;
		}
		$final = json_encode($rightsNew);
		$userNew->setRights($final);
		$entityManager->persist($userNew);
		$entityManager->flush();
		header('location: /panel/cmsusers');
		exit();
		
		}
	}
/**
* @Route("/panel/general")
*/
	  public function generallist()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['general']) {
		header('Location: /panel/main');
		}
	
	// Для ручного введения данных 
	function setvar($name, $params, $entityManager) {
	$general = new General();
	$general->setEntity($name);
	$general->setParams($params);
	$entityManager->persist($general);
	$entityManager->flush();
	}

	
	$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();	
		
		return $this->render('panelGeneralObs.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'closed' => $general[3]->getParams(),
		'operators' => json_decode($general[4]->getParams()),
		));
	}
/**
* @Route("/panel/general/change/{form}")
*/
	  public function generalChange($form)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['general']) {
		header('Location: /panel/main');
		exit();
		}		
		return $this->render('panelGeneralChange.html.twig', array(
		'form' => $form,
		));
	}
/**
* @Route("/panel/general/changeEnt/{form}")
*/
	  public function generalChangeLogic($form)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['general']) {
		header('Location: /panel/main');
		exit();
		}
		if($form==1){
		$ent = $this->getDoctrine()
        ->getRepository(General::class)
        ->findOneBy(['entity' => 'title']);
		$ent->setParams($_POST['new']);
		$entityManager->persist($ent);
		$entityManager->flush();
		}
		if($form==3){
		$ent = $this->getDoctrine()
        ->getRepository(General::class)
        ->findOneBy(['entity' => 'closed']);
		$time = strtotime($_POST['time']);
		if(!strtotime($_POST['time'])) { 
		exit('::(('); 
		}
		$ent->setParams($time);
		$entityManager->persist($ent);
		$entityManager->flush();
		}
		if($form==2){
		$filename = uniqid('',true);
		preg_match('/^.*\.(.+)$/', $_FILES['logo']['name'], $match);
		$filename .= '.'.$match[1];
		
		copy($_FILES['logo']['tmp_name'],"static/".$filename);
		$ent = $this->getDoctrine()
        ->getRepository(General::class)
        ->findOneBy(['entity' => 'logo']);
		$ent->setParams($filename);
		$entityManager->persist($ent);
		$entityManager->flush();
		}
		if($form==4){
		$ent = $this->getDoctrine()
        ->getRepository(General::class)
        ->findOneBy(['entity' => 'telegram']);
		$ent->setParams(json_encode(explode(',',$_POST['new'])));
		$entityManager->persist($ent);
		$entityManager->flush();
		}
		if($form==5){
		$ent = $this->getDoctrine()
        ->getRepository(General::class)
        ->findOneBy(['entity' => 'name']);
		$ent->setParams($_POST['new']);
		$entityManager->persist($ent);
		$entityManager->flush();
		}
		header('location: /panel/general');
		exit();
	}
/**
* @Route("/panel/articles")
*/
	  public function articleslist()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['art']) {
		header('Location: /panel/main');
		}
	if(!@$_GET['page'] || @$_GET['page']==1) {
	$from = 0;
	$page = 1;
	} else {
	$page = $_GET['page'];
	$from = ($page-1) * 30;
	}
	$to = $from + 30;
	$articles = $this->getDoctrine()
        ->getRepository(Articles::class)
        ->findBy(array(),
		array('id' => 'DESC'),
		$to, $from);	
		
		return $this->render('panelArticlesList.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		'articles' => $articles,
		'page' => $page,
		));
	}
/**
* @Route("/panel/articles/new")
*/
	  public function articlesnew()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['art']) {
		header('Location: /panel/main');
		}

		return $this->render('panelArticlesNew.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		));
	}
/**
* @Route("/panel/articles/new/make")
*/
	  public function articlesnewmake()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['art']) {
		header('Location: /panel/main');
		}

		$article = new Articles();
		$article->setTitle(@$_POST['title']);
		$article->setText(@$_POST['text']);
		if(@$_POST['short']) {
			$article->setShort(@$_POST['short']);
		}
		if(@$_POST['main']) {
		$article->setSpecial(@$_POST['main']);
		}
		if(count(@$_FILES['img']['tmp_name']>0)) {
			$i = 0;
			$photos = array();
			while($i < count(@$_FILES['img']['tmp_name'])) {
				if(@$_FILES['img']['size'][$i]>0) {
			$filename = uniqid('',true);
			preg_match('/^.*\.(.+)$/', @$_FILES['img']['name'][$i], $match);
			$filename .= '.'.$match[1];
		
			copy(@$_FILES['img']['tmp_name'][$i],"static/".$filename);
			$photos[$i] = $filename;
			$i++;
			}
				}
			$photos = json_encode($photos);
			$article->setPhoto($photos);
		}
		$article->setTime(time());
		$entityManager->persist($article);
		$entityManager->flush();
		header('location: /panel/articles');
		exit();
	}
/**
* @Route("/panel/articles/edit/{id}")
*/
	  public function articlesedit($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['art']) {
		header('Location: /panel/main');
		}
		$article = $this->getDoctrine()
        ->getRepository(Articles::class)
        ->find($id);
		if($article->getPhoto()) {
		$photos = json_decode($article->getPhoto());
		}
		return $this->render('panelArticlesEdit.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		'id' => $id,
		'title' => $article->getTitle(),
		'text' => $article->getText(),
		'main' => $article->getSpecial(),
		'short' => $article->getShort(),
		'photos' => $photos,
		));
	}
/**
* @Route("/panel/articles/editlogic/{id}")
*/
	  public function articleseditlogic($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['art']) {
		header('Location: /panel/main');
		}

		$article = $this->getDoctrine()
        ->getRepository(Articles::class)
        ->find($id);
		$article->setTitle(@$_POST['title']);
		$article->setText(@$_POST['text']);
		if(@$_POST['short']) {
			$article->setShort(@$_POST['short']);
		}
		if(@$_POST['main']) {
		$article->setSpecial(@$_POST['main']);
		}
		$photos = json_decode($article->getPhoto(), true);
		if(@$_POST['delete']){
			foreach($_POST['delete'] as $delete) {
				unset($photos[$delete]);
			}
			$photos = array_values($photos);
			$photos = json_encode($photos);
			$article->setPhoto($photos);
		}
		$entityManager->persist($article);
		$entityManager->flush();
		header('location: /panel/articles');
		exit();
	}
/**
* @Route("/panel/articles/delete/{id}")
*/
	  public function articlesdelete($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['art']) {
		header('Location: /panel/main');
		}

		$article = $this->getDoctrine()
        ->getRepository(Articles::class)
        ->find($id);
		
		$entityManager->remove($article);
		$entityManager->flush();
		header('location: /panel/articles');
		exit();
	}
/**
* @Route("/panel/goods")
*/
	  public function goodslist()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['goods']) {
		header('Location: /panel/main');
		}
	if(!@$_GET['page'] || @$_GET['page']==1) {
	$from = 0;
	$page = 1;
	} else {
	$page = $_GET['page'];
	$from = ($page-1) * 30;
	}
	$to = $from + 30;
	if(!@$_GET['search']) {
	$query = [];
	} else {
	$query = ['article' => @$_GET['search']];
	}
	$goods = $this->getDoctrine()
        ->getRepository(Goods::class)
        ->findBy($query,
		array('id' => 'DESC'),
		$to, $from);	
		
		return $this->render('panelGoodsList.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		'goods' => $goods,
		'page' => $page,
		));
	}
/**
* @Route("/panel/goods/new")
*/
	  public function goodsnew()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['goods']) {
		header('Location: /panel/main');
		}

		return $this->render('panelGoodsNew.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		));
	}
/**
* @Route("/panel/goods/new/make")
*/
	  public function goodsnewmake()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['goods']) {
		header('Location: /panel/main');
		}

		$good = new Goods();
		$good->setName(@$_POST['name']);
		$good->setSize(@$_POST['size']);
		$good->setSex(@$_POST['sex']);
		$good->setCollection(@$_POST['collection']);
		$good->setType(@$_POST['type']);
		$good->setPrice(@$_POST['price']);
		$good->setQuant(@$_POST['quant']);
		$good->setState(@$_POST['state']);
		$good->setColor(@$_POST['color']);
		$good->setArticle(@$_POST['article']);
		if(@$_POST['short']) {
			$good->setShort(@$_POST['short']);
		}
		$good->setDescription(@$_POST['description']);

		if(count(@$_FILES['img']['tmp_name']>0)) {
			$i = 0;
			$photos = array();
			while($i < count(@$_FILES['img']['tmp_name'])) {
				if(@$_FILES['img']['size'][$i]>0) {
			$filename = uniqid('',true);
			preg_match('/^.*\.(.+)$/', @$_FILES['img']['name'][$i], $match);
			$filename .= '.'.$match[1];
		
			copy(@$_FILES['img']['tmp_name'][$i],"static/".$filename);
			$photos[$i] = $filename;
			$i++;
			}
				}
			$photos = json_encode($photos);
			$good->setPhoto($photos);
		}
		$entityManager->persist($good);
		$entityManager->flush();
		header('location: /panel/goods');
		exit();
	}
/**
* @Route("/panel/goods/edit/{id}")
*/
	  public function goodsedit($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['goods']) {
		header('Location: /panel/main');
		}
		$good = $this->getDoctrine()
        ->getRepository(Goods::class)
        ->find($id);
		if($good->getPhoto()) {
		$photos = json_decode($good->getPhoto());
		}
		
		return $this->render('panelGoodsEdit.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		'id' => $id,
		'name' => $good->getName(),
		'article' => $good->getArticle(),
		'size' => $good->getSize(),
		'sex' => $good->getSex(),
		'collection' => $good->getCollection(),
		'color' => $good->getColor(),
		'type' => $good->getType(),
		'price' => $good->getPrice(),
		'quant' => $good->getQuant(),
		'state' => $good->getState(),
		'description' => $good->getDescription(),
		'short' => $good->getShort(),
		'photos' => $photos,
		));
	}
/**
* @Route("/panel/goods/editlogic/{id}")
*/
	  public function goodsEditLogic($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['goods']) {
		header('Location: /panel/main');
		}

		$good = $this->getDoctrine()
        ->getRepository(Goods::class)
        ->find($id);
		$good->setName(@$_POST['name']);
		$good->setSize(@$_POST['size']);
		$good->setSex(@$_POST['sex']);
		$good->setCollection(@$_POST['collection']);
		$good->setType(@$_POST['type']);
		$good->setPrice(@$_POST['price']);
		$good->setQuant(@$_POST['quant']);
		$good->setState(@$_POST['state']);
		$good->setColor(@$_POST['color']);
		$good->setArticle(@$_POST['article']);
		$good->setDescription(@$_POST['description']);
		if(@$_POST['short']) {
			$good->setShort(@$_POST['short']);
		}
		$photos = json_decode($good->getPhoto(), true);
		if(@$_POST['delete']){
			foreach($_POST['delete'] as $delete) {
				unset($photos[$delete]);
			}
			$photos = array_values($photos);
			
		}
		if(count(@$_FILES['img']['tmp_name']>0)) {
			if(count($photos)==0 || !$photos) {
			$pos = 0;
			} else {
			$pos = count($photos);
			}
			$i=0;
			while($i < count(@$_FILES['img']['tmp_name'])) {
				if(@$_FILES['img']['size'][$i]>0) {
			$filename = uniqid('',true);
			preg_match('/^.*\.(.+)$/', @$_FILES['img']['name'][$i], $match);
			$filename .= '.'.$match[1];
		
			copy(@$_FILES['img']['tmp_name'][$i],"static/".$filename);
			$photos[$pos] = $filename;
			$i++;
				}
			}
			
			
		}
		$photos = json_encode($photos);
		$good->setPhoto($photos);
		$entityManager->persist($good);
		$entityManager->flush();
		header('location: /panel/goods');
		exit();
	}
/**
* @Route("/panel/goods/delete/{id}")
*/
	  public function goodsdelete($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['goods']) {
		header('Location: /panel/main');
		}

		$good = $this->getDoctrine()
        ->getRepository(Goods::class)
        ->find($id);
		
		$entityManager->remove($good);
		$entityManager->flush();
		header('location: /panel/goods');
		exit();
	}
/**
* @Route("/panel/users")
*/
	  public function userslist()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['users']) {
		header('Location: /panel/main');
		}
		
	if(!@$_GET['page'] || @$_GET['page']==1) {
	$from = 0;
	$page = 1;
	} else {
	$page = $_GET['page'];
	$from = ($page-1) * 30;
	}
	$to = $from + 30;
	$users = $this->getDoctrine()
        ->getRepository(Users::class)
        ->findBy(array(),
		array('id' => 'DESC'),
		$to, $from);	
		
		return $this->render('panelUsersList.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		'users' => $users,
		'page' => $page,
		'suc' => @$_GET['suc'],
		));
	}
/**
* @Route("/panel/users/action/{id}")
*/
	  public function usersaction($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['users']) {
		header('Location: /panel/main');
		}
		$user = $this->getDoctrine()
        ->getRepository(Users::class)
        ->find($id);
		if(!$user) {
		exit('response:err');
		}
		return $this->render('panelUsersActions.html.twig', array(
		'blocked' => $user->getBlocked(),
		'id' => $id,
		));
	}
/**
* @Route("/panel/users/delete/{id}")
*/
	  public function usersdelete($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['users']) {
		header('Location: /panel/main');
		}
		$user = $this->getDoctrine()
        ->getRepository(Users::class)
        ->find($id);
		$entityManager->remove($user);
		$entityManager->flush();
		header('Location: /panel/users?suc=1');
		exit();
	}
/**
* @Route("/panel/users/ban/{id}")
*/
	  public function usersban($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['users']) {
		header('Location: /panel/main');
		}
		$user = $this->getDoctrine()
        ->getRepository(Users::class)
        ->find($id);
		if(@$_GET['a']=='unban') {
		$user->setBlocked(false);
		} else {
		$user->setBlocked(true);
		}
		$entityManager->persist($user);
		$entityManager->flush();
		header('Location: /panel/users?suc=1');
		exit();
	}
/**
* @Route("/panel/users/info/{id}")
*/
	  public function usersinfo($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['users']) {
		header('Location: /panel/main');
		}
		$user = $this->getDoctrine()
        ->getRepository(Users::class)
        ->find($id);
		return $this->render('panelUsersInfo.html.twig', array(
		'adress' => $user->getAdress(),
		'contact' => $user->getContact(),
		));
	}
/**
* @Route("/panel/orders")
*/
	  public function orderslist()
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['orders']) {
		header('Location: /panel/main');
		}
		
	if(!@$_GET['page'] || @$_GET['page']==1) {
	$from = 0;
	$page = 1;
	} else {
	$page = $_GET['page'];
	$from = ($page-1) * 30;
	}
	$to = $from + 30;
	$orders = $this->getDoctrine()
        ->getRepository(Orders::class)
        ->findBy(array(),
		array('id' => 'DESC'),
		$to, $from);	
		
		return $this->render('panelOrdersList.html.twig', array(
		'rightsGeneral' => @$rights['general'],
		'rightsArt' => @$rights['art'],
		'rightsCmsUsers' => @$rights['cmsUsers'],
		'rightsUsers' => @$rights['users'],
		'rightsGoods' => @$rights['goods'],
		'rightsOrders' => @$rights['orders'],
		'orders' => $orders,
		'page' => $page,
		'suc' => @$_GET['suc'],
		));
	}
/**
* @Route("/panel/orders/info/{id}")
*/
	  public function ordersinfo($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['orders']) {
		header('Location: /panel/main');
		}
		$order = $this->getDoctrine()
        ->getRepository(Orders::class)
        ->find($id);
		$articles = json_decode($order->getArticles());
		$articlesInner = json_decode($order->getArticles(), true);
		$sum = 0;
		
		foreach($articlesInner as $article) {
			
				$sum = $sum+$article['price']*$article['q'];
			
		}
		$name = $order->getName();
		
		return $this->render('panelOrdersInfo.html.twig', array(
		'adress' => $order->getAdress(),
		'contact' => $order->getContact(),
		'comment' => $order->getComment(),
		'articles' => $articles,
		'sum' => $sum,
		'id' => $id,
		'name' => $name,
		));
	}
/**
* @Route("/panel/orders/delete/{id}")
*/
	  public function ordersdelete($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['users']) {
		header('Location: /panel/main');
		}
		$order = $this->getDoctrine()
        ->getRepository(Orders::class)
        ->find($id);
		$entityManager->remove($order);
		$entityManager->flush();
		header('Location: /panel/orders?suc=1');
		exit();
	}
/**
* @Route("/panel/orders/add/{id}")
*/
	  public function ordersadd($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['users']) {
		header('Location: /panel/main');
		}
		$order = $this->getDoctrine()
        ->getRepository(Orders::class)
        ->find($id);
		$order->setComment(@$_POST['add']);
		$entityManager->persist($order);
		$entityManager->flush();
		header('Location: /panel/orders?suc=1');
		exit();
	}
/**
* @Route("/panel/orders/state/{id}")
*/
	  public function ordersstate($id)
    {
		panel::login();
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(CmsUsers::class)
        ->findOneBy(['login' => $_SESSION['panel_login']]);
		$rights = json_decode($user->getRights(), TRUE);
		if(!$rights['users']) {
		header('Location: /panel/main');
		}
		$order = $this->getDoctrine()
        ->getRepository(Orders::class)
        ->find($id);
		$order->setState(@$_POST['state']);
		$entityManager->persist($order);
		$entityManager->flush();
		header('Location: /panel/orders?suc=1');
		exit();
	}
}