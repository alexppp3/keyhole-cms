<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

class frontend extends AbstractController
{
public function customSQL($sql)
{   
    $em = $this->getDoctrine()->getManager();
    $stmt = $em->getConnection()->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}
public function close()
{
	@session_start();
	$isClosed = $this->getDoctrine()
        ->getRepository(General::class)
        ->findOneBy(['entity' => 'closed']);
	if($isClosed->getParams()>time()) {
		if(@$_SESSION['panel_login']) {
			echo('<p style="color: red" align="center"><b>Сайт закрыт на технические работы и не виден пользователям, не вошедшим в панель управления!</b></p>');
		} else {
			header('location: /closed');
			exit();
		}
	}
}
public function telegram($anytext, $id)
{
	$text = urlencode($anytext);
	$new = 'https://api.telegram.org/botid:token/sendMessage?chat_id='.$id.'&text='.$text;
	$req = file_get_contents($new);
}
/**
* @Route("/")
*/
	  public function index()
    {
		frontend::close();
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		$carousel = $this->getDoctrine()
        ->getRepository(Articles::class)
        ->findBy(['special' => 'main']);
		$articles = $this->getDoctrine()
        ->getRepository(Articles::class)
        ->findBy(['special' => 'submain'],
		['id' => 'DESC'],
		4, 0);
		return $this->render('index.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'carousel' => $carousel,
		'articles' => $articles,
		));
	}
/**
* @Route("/articles")
*/
	  public function articlesList()
    {
		frontend::close();
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		if(!@$_GET['page'] || @$_GET['page']==1) {
		$from = 0;
		$page = 1;
		} else {
		$page = $_GET['page'];
		$from = ($page-1) * 15;
		}
		$to = $from + 15;
		$articles = $this->getDoctrine()
        ->getRepository(Articles::class)
        ->findBy(['special' => ['none', 'submain']],
		['id' => 'DESC'],
		$to, $from);
		return $this->render('interArticlesList.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'page' => $page,
		'articles' => $articles,
		));
	}
/**
* @Route("/article/{id}")
*/
	  public function article($id)
    {
		frontend::close();
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		$article = $this->getDoctrine()
        ->getRepository(Articles::class)
        ->find($id);
		if(!$article || !is_numeric($id)) {
		header('location: /404');
		exit();
		}
		$car = json_decode($article->getPhoto());
		unset($car[0]);
		array_values($car);
		return $this->render('interArticle.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'carousel' => $car,
		'title' => $article->getTitle(),
		'time' => $article->getTime(),
		'text' => $article->getText(),
		));
	}
/**
* @Route("/goods/{sex}")
*/
	  public function goodsSelect($sex)
    {
		frontend::close();
		if($sex!='man' && $sex!='woman' && $sex!='u') {
		header('location: /');
		exit();
		}
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		$types = frontend::customSQL("SELECT DISTINCT `type` FROM `goods` WHERE `sex`='".$sex."' OR `sex`='u'");
		return $this->render('interGoodsSelect.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'types' => $types,
		'sex' => $sex,
		));
	}
/**
* @Route("/goods/{sex}/{params}")
*/
	  public function goodsList($sex, $params)
    {
		frontend::close();
		$trueParams = $params;
		if(@$_POST['size'] || @$_POST['col'] || @$_POST['color']) {
		$params = str_ireplace('+', ' ', urldecode($params));
		$type = json_decode($params, TRUE);
		if(!$type || !$sex) {
		header('location: /');
		exit();
		}
		$params = [];
		if(@$_POST['size']) {
		$params['size'] = $_POST['size'];
		}
		if(@$_POST['col']) {
		$params['col'] = $_POST['col'];
		}
		if(@$_POST['color']) {
		$params['color'] = $_POST['color'];
		}
		$params['type'] = $type['type'];
		$params = urlencode(json_encode($params, JSON_UNESCAPED_UNICODE));
		header('location: /goods/'.$sex.'/'.$params);
		exit();
		}
		if($sex!='man' && $sex!='woman' && $sex!='u') {
		header('location: /');
		exit();
		}
		$params = str_ireplace('+', ' ', urldecode($params));
		$params = json_decode($params, true);
		if(!$params) {
		header('location: /');
		exit();
		}
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		if(!@$_GET['page'] || @$_GET['page']==1) {
		$from = 0;
		$page = 1;
		} else {
		$page = $_GET['page'];
		$from = ($page-1) * 30;
		}
		$to = $from + 30;
		$goodparams = ['sex' => [$sex, 'u'], 'type' => $params['type']];
		if(@$params['size']) {
		$goodparams['size'] = $params['size'];
		
		}
		if(@$params['col']) {
		$goodparams['collection'] = $params['col'];
		}
		if(@$params['color']) {
		$goodparams['color'] = $params['color'];
		}
		$goodparams['state'] = '1';
		$goods = $this->getDoctrine()
        ->getRepository(Goods::class)
        ->findBy($goodparams,
		['price' => 'ASC'],
		$to, $from);
		$sizes = frontend::customSQL("SELECT DISTINCT `size` FROM `goods` WHERE `type`='".htmlspecialchars($params['type'], ENT_QUOTES)."'");
		$cols = frontend::customSQL("SELECT DISTINCT `collection` FROM `goods` WHERE `type`='".htmlspecialchars($params['type'], ENT_QUOTES)."'");
		$colors = frontend::customSQL("SELECT DISTINCT `color` FROM `goods` WHERE `type`='".htmlspecialchars($params['type'], ENT_QUOTES)."'");
		return $this->render('interGoodsList.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'goods' => $goods,
		'sex' => $sex,
		'sizes' => $sizes,
		'cols' => $cols,
		'colors' => $colors,
		'sex' => $sex,
		'params' => $trueParams,
		'page' => $page,
		));
	}
/**
* @Route("/good/{id}")
*/
	  public function good($id)
    {
		frontend::close();
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		$good = $this->getDoctrine()
        ->getRepository(Goods::class)
        ->find($id);
		if(!$good || !is_numeric($id)) {
		header('location: /404');
		exit();
		}
		$car = json_decode($good->getPhoto());
		unset($car[0]);
		array_values($car);
		return $this->render('interGood.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'carousel' => $car,
		'goodName' => $good->getName(),
		'price' => $good->getPrice(),
		'description' => $good->getDescription(),
		'size' => $good->getSize(),
		'collection' => $good->getCollection(),
		'color' => $good->getColor(),
		'q' => $good->getQuant(),
		'id' => $id,
		'state' => $good->getState(),
 		));
	}
/**
* @Route("/login")
*/
	  public function login()
    {
		frontend::close();
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		if(@$_SESSION['id']) {
		header('location: /profile');
		exit();
		}
		if(@$_GET['g'] && @$_GET['q']) {
		
		$_SESSION['g'] = $_GET['g'];
		$_SESSION['q'] = $_GET['q'];
		}
		if(@$_GET['cart']==true) {
		$_SESSION['c'] = true;
		}
		return $this->render('interLogin.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'error' => @$_GET['err'],
		));
	}
/**
* @Route("/register")
*/
	  public function reg()
    {
		frontend::close();
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		if(@$_SESSION['id']) {
		header('location: /profile');
		exit();
		}
		return $this->render('interReg.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'error' => @$_GET['err'],
		));
	}
/**
* @Route("/register/logic")
*/
	  public function regLogic()
    {
		frontend::close();
		$entityManager = $this->getDoctrine()->getManager();
		if(@$_SESSION['id']) {
		header('location: /profile');
		exit();
		}
		if(strlen($_POST['password'])>32 || strlen($_POST['email'])>32 || strlen($_POST['name'])>32 || strlen($_POST['adress'])>255 || strlen($_POST['contact'])>255 || !$_POST['password'] || !$_POST['email'] || !$_POST['name'] || !$_POST['contact']) {
		header('location: /register?err=1');
		exit();
		}
		$isUser = $this->getDoctrine()
        ->getRepository(Users::class)
        ->findOneBy(['email' => htmlspecialchars($_POST['email'], ENT_QUOTES)]);
		if($isUser) {
		header('location: /register?err=2');
		exit();
		}
		$user = new Users();
		$user->setEmail(htmlspecialchars($_POST['email'], ENT_QUOTES));
		$user->setName(htmlspecialchars($_POST['name'], ENT_QUOTES));
		$user->setPassword(password_hash($_POST['password'], PASSWORD_BCRYPT));
		$user->setAdress(htmlspecialchars($_POST['adress'], ENT_QUOTES));
		$user->setContact(htmlspecialchars($_POST['contact'], ENT_QUOTES));
		$entityManager->persist($user);
		$entityManager->flush();
		header('location: /login?err=3');
		exit();
	}
/**
* @Route("/login/logic")
*/
	  public function loginLogic()
    {
		frontend::close();
		if(@$_SESSION['id']) {
		header('location: /profile');
		exit();
		}
		if(strlen($_POST['password'])>32 || strlen($_POST['email'])>32 || !$_POST['password'] || !$_POST['email']) {
		header('location: /login?err=1');
		exit();
		}
		$user = $this->getDoctrine()
        ->getRepository(Users::class)
        ->findOneBy(['email' => $_POST['email']]);
		if(!$user) {
		header('location: /login?err=2');
		exit();
		}
		if(password_verify($_POST['password'], $user->getPassword())) {
		if($user->getBlocked()==true) {
		header('location: /login?err=2');
		exit();
		}
		
		$_SESSION['id'] = $user->getId();
		if(@$_SESSION['g']) {
		header('location: /buy/'.$_SESSION['g'].'/'.$_SESSION['q']);
		exit();
		}
		if(@$_SESSION['c']) {
		header('location: /cart');
		exit();
		}
		header('location: /profile');
		exit();
		} else {
		header('location: /login?err=2');
		exit();
		}
	}
/**
* @Route("/profile")
*/
	  public function profile()
    {
		frontend::close();
		if(!@$_SESSION['id']) {
		header('location: /login');
		exit();
		}
		$user = $this->getDoctrine()
        ->getRepository(Users::class)
        ->find($_SESSION['id']);
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		return $this->render('interProfile.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'username' => $user->getName(),
		'email' => $user->getEmail(),
		'adress' => $user->getAdress(),
		'contact' => $user->getContact(),
		'error' => @$_GET['err'],
		));
	}
/**
* @Route("/profile/edit")
*/
	  public function profileEdit()
    {
		frontend::close();
		if(!@$_SESSION['id']) {
		header('location: /login');
		exit();
		}
		if(!@$_POST['contact']) {
		header('location: /profile?err=1');
		exit();
		}
		if(mb_strlen(@$_POST['contact'])>500 || mb_strlen(@$_POST['adress'])>500) {
		header('location: /profile?err=2');
		exit();
		}
		$entityManager = $this->getDoctrine()->getManager();
		$user = $this->getDoctrine()
        ->getRepository(Users::class)
        ->find($_SESSION['id']);
		$user->setAdress(htmlspecialchars(@$_POST['adress'], ENT_QUOTES));
		$user->setContact(htmlspecialchars(@$_POST['contact'], ENT_QUOTES));
		$entityManager->persist($user);
		$entityManager->flush();
		header('location: /profile');
		exit();
	}
/**
* @Route("/order/withoutreg/proceed")
*/
	  public function orderWoReg()
    {
		frontend::close();
		
		if(@$_SESSION['ordid']) {
		$id = $_SESSION['ordid'];
		$q = $_SESSION['q'];
		}
		$entityManager = $this->getDoctrine()->getManager();
		if(@$_SESSION['id']) {
		header('location: /profile');
		exit();
		}
		if(strlen(@$_POST['name'])>32 || strlen(@$_POST['adress'])>255 || strlen(@$_POST['contact'])>255 || !@$_POST['name'] || !@$_POST['contact']) {
		header('location: /order/withoutreg?err=1');
		exit();
		}
		if(@$id && @$q) {
		$good = $this->getDoctrine()
        ->getRepository(Goods::class)
        ->find($id);
		if(!$good) {
		header('location: /');
		exit();
		}
		}
		if(@$_SESSION['cart']) {
		$ord = @$_SESSION['cart'];
		} else {
		$ord = array('a' => $good->getArticle(), 'q' => $q, 'price' => $good->getPrice());
		$ord = array($ord);
		$ord = json_encode($ord);
		}
		$order = new Orders();
		$order->setName(htmlspecialchars($_POST['name'], ENT_QUOTES));
		$order->setAdress(htmlspecialchars($_POST['adress'], ENT_QUOTES));
		$order->setContact(htmlspecialchars($_POST['contact'], ENT_QUOTES));
		$order->setTime(time());
		$order->setState(1);
		$order->setSum(0);
		$order->setArticles($ord);
		$entityManager->persist($order);
		$entityManager->flush();
		$recievers = $this->getDoctrine()
        ->getRepository(General::class)
        ->findOneBy(['entity' => 'telegram']);
		$message = 'Поступил заказ! ID:'.$order->getId();
		$sum = 0;
		$ord = json_decode($ord, true);
		foreach($ord as $o) {
		$message .= '[Артикул:'.$o['a'].'|Кол-во:'.$o['q'].'|Цена:'.$o['price'].'] ';
		$sum = $sum+($o['q']*$o['price']);
		}
		$message .= '[Сумма: '.$sum.'грн. ] Адрес: '.htmlspecialchars($_POST['adress'], ENT_QUOTES).';Контакты: '.htmlspecialchars($_POST['contact'], ENT_QUOTES).';Имя: '.htmlspecialchars($_POST['name'], ENT_QUOTES);
		$recievers = json_decode($recievers->getParams(), true);
		foreach($recievers as $rec) {
		frontend::telegram($message, $rec);
		}
		header('location: /ordersuc');
		exit();
	}
/**
* @Route("/buy/{id}/{q}")
*/
	  public function orderpage($id, $q)
    {
		frontend::close();
		$good = $this->getDoctrine()
        ->getRepository(Goods::class)
        ->find($id);
		if(!$good || $good->getState()==3) {
		header('location: /');
		}
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		return $this->render('interOrderClick.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'good' => $good->getName(),
		'price' => $good->getPrice(),
		'photo' => @json_decode($good->getPhoto(), true)[0],
		'quant' => $q,
		'goodId' => $id,
		'user' => @$_SESSION['id'],
		'state' => $good->getState(),
		));
	}
/**
* @Route("/order/withoutreg/{id}/{q}")
*/
	  public function orderWoRegForm($id, $q)
    {
		frontend::close();
		
		$_SESSION['ordid'] = $id;
		$_SESSION['q'] = $q;
		$good = $this->getDoctrine()
        ->getRepository(Goods::class)
        ->find($id);
		if(!$good) {
		header('location: /');
		}
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		return $this->render('interOrderWoReg.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'error' => @$_GET['err'],
		));
	}
/**
* @Route("/order/withoutreg/")
*/
	  public function orderWoRegFormCart()
    {
		frontend::close();
		
		if(!@$_SESSION['cart']) {
		header('location: /');
		}
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		return $this->render('interOrderWoReg.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'error' => @$_GET['err'],
		));
	}
/**
* @Route("/order/finish/{id}/{q}")
*/
	  public function orderFinish($id, $q)
    {
		frontend::close();
		
		$entityManager = $this->getDoctrine()->getManager();
		if(!@_SESSION['id']) {
		header('location: /buy/'.$id.'/'.$q);
		}
		$good = $this->getDoctrine()
        ->getRepository(Goods::class)
        ->find($id);
		$user = $this->getDoctrine()
        ->getRepository(Users::class)
        ->find($_SESSION['id']);
		if(!$good) {
		header('location: /');
		exit();
		}
		$ord = array('a' => $good->getArticle(), 'q' => $q, 'price' => $good->getPrice());
		$ord = array($ord);
		$order = new Orders();
		$order->setName($user->getName());
		$order->setUserId($user->getId());
		$order->setAdress($user->getAdress());
		$order->setContact($user->getContact());
		$order->setTime(time());
		$order->setState(1);
		$order->setSum(0);
		$order->setArticles(json_encode($ord));
		$entityManager->persist($order);
		$entityManager->flush();
		$recievers = $this->getDoctrine()
        ->getRepository(General::class)
        ->findOneBy(['entity' => 'telegram']);
		$message = 'Поступил заказ! ID:'.$order->getId().'[Артикул:'.$ord[0]['a'].'|Кол-во:'.$ord[0]['q'].'|Цена:'.$ord[0]['price'].'] [Сумма: '.$ord[0]['q']*$ord[0]['price'].'грн. ] Адрес: '.$user->getAdress().';Контакты: '.$user->getContact().';Имя: '.$user->getName();
		$recievers = json_decode($recievers->getParams(), true);
		foreach($recievers as $rec) {
		frontend::telegram($message, $rec);
		}
		header('location: /ordersuc');
		exit();
	}
/**
* @Route("/order/finish")
*/
	  public function orderFinishCart()
    {
		frontend::close();
		
		$entityManager = $this->getDoctrine()->getManager();
		if(!@_SESSION['id']) {
		header('location: /cart');
		}
		$user = $this->getDoctrine()
        ->getRepository(Users::class)
        ->find($_SESSION['id']);
		if(!$_SESSION['cart']) {
		header('location: /');
		exit();
		}
		$order = new Orders();
		$order->setName($user->getName());
		$order->setUserId($user->getId());
		$order->setAdress($user->getAdress());
		$order->setContact($user->getContact());
		$order->setTime(time());
		$order->setState(1);
		$order->setSum(0);
		$order->setArticles($_SESSION['cart']);
		$entityManager->persist($order);
		$entityManager->flush();
		$recievers = $this->getDoctrine()
        ->getRepository(General::class)
        ->findOneBy(['entity' => 'telegram']);
		$cart = json_decode($_SESSION['cart'], true);
		$message = 'Поступил заказ! ID:'.$order->getId();
		$sum = 0;
		foreach($cart as $o) {
		$message .= '[Артикул:'.$o['a'].'|Кол-во:'.$o['q'].'|Цена:'.$o['price'].'] ';
		$sum = $sum+($o['q']*$o['price']);
		}
		$message .= '[Сумма: '.$sum.'грн. ] Адрес: '.$user->getAdress().';Контакты: '.$user->getContact().';Имя: '.$user->getName();
		$recievers = json_decode($recievers->getParams(), true);
		foreach($recievers as $rec) {
			if(is_numeric($rec)) {
		frontend::telegram($message, $rec);
			}
		}
		header('location: /ordersuc');
		exit();
	}
/**
* @Route("/cart/add/{id}/{q}")
*/
	  public function cartAdd($id, $q)
    {
		frontend::close();
		
		if(!@$_SESSION['cart']) {
		$_SESSION['cart'] = '{}'; 
		}
		$good = $this->getDoctrine()
        ->getRepository(Goods::class)
        ->find($id);
		$cart = json_decode($_SESSION['cart'], true);
		$new = array('a' => $good->getArticle(), 'q' => $q, 'price' => $good->getPrice());
		array_push($cart, $new);
		$cart = json_encode($cart);
		$_SESSION['cart'] = $cart;
		exit();
	}
/**
* @Route("/cart")
*/
	  public function cartpage()
    {
		frontend::close();
		
		if(!@$_SESSION['cart']) {
		header('location: /');
		exit();
		}
		$cart = json_decode(@$_SESSION['cart'], true);
		$sum = 0;
		$goods = array();
		if(count($cart)>0){
		foreach($cart as $good) {
			$g = $this->getDoctrine()
			->getRepository(Goods::class)
			->findOneBy(['article' => $good['a']]);
			$g = array('photo' => $g->getPhoto(), 'name' => $g->getName(), 'price' => $good['price'], 'quant' => $good['q']);
			if($g) {
			$sum = $sum+($good['price']*$good['q']);
			array_push($goods, $g);
			}
		}
		}
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		return $this->render('interCart.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		'goods' => $goods,
		'sum' => $sum,
		'user' => @$_SESSION['id'],
		));
	}
/**
* @Route("/cart/delete")
*/
	  public function cartdelete()
    {
		frontend::close();
		
		if(!@$_SESSION['cart']) {
		header('location: /');
		exit();
		}
		$cart = json_decode(@$_SESSION['cart'], true);
		foreach(@$_POST['delete'] as $delete) {
			unset($cart[$delete]);
		}
		$cart = array_values($cart);
		$cart = json_encode($cart);
		$_SESSION['cart'] = $cart;
		header('location: /cart');
		exit();
	}
/**
* @Route("/ordersuc")
*/
	  public function ordersuc()
    {
		frontend::close();
		$general = $this->getDoctrine()
        ->getRepository(General::class)
        ->findAll();
		return $this->render('interOrderSuc.html.twig', array(
		'title' => $general[0]->getParams(),
		'name' => $general[1]->getParams(),
		'logo' => $general[2]->getParams(),
		));
	}
/**
* @Route("/closed")
*/
	  public function closed()
    {
		return $this->render('interClosed.html.twig');
	}
}
?>