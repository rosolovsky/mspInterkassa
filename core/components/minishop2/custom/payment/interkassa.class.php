<?php

if (!class_exists('msPaymentInterface')) {
	require_once dirname(dirname(dirname(__FILE__))) . '/model/minishop2/mspaymenthandler.class.php';
}

class Interkassa extends msPaymentHandler implements msPaymentInterface {
	public $config;
	public $modx;

	function __construct(xPDOObject $object, $config = array()) {
		$this->modx = & $object->xpdo;

		$siteUrl = $this->modx->getOption('site_url');
		$assetsUrl = $this->modx->getOption('minishop2.assets_url', $config, $this->modx->getOption('assets_url').'components/minishop2/');
		$paymentUrl = $siteUrl . substr($assetsUrl, 1) . 'payment/interkassa.php';

		$this->config = array_merge(array(
			'paymentUrl' => $paymentUrl
			,'checkoutUrl' => $this->modx->getOption('ms2_payment_ik_url', null, 'https://sci.interkassa.com', true)
			,'kassa_id' => $this->modx->getOption('ms2_payment_ik_shop_id', null, '5294af0ebf4efc6549330a93', true)
			,'secret_key' => $this->modx->getOption('ms2_payment_ik_secret_key', null, 'mm3L9ULUKgJHwGzS', true)
			,'ik_currency' => $this->modx->getOption('ms2_payment_ik_currency', null, 'UAH', true)
			,'json_response' => false
		), $config);
	}


	/* @inheritdoc} */
	public function send(msOrder $order) {
		$link = $this->getPaymentLink($order);

		return $this->success('', array('redirect' => $link));
	}


	public function getPaymentLink(msOrder $order) {
		$id = $order->get('id');
		$ik_desc = 'Заказ №'.$id;
		$sum = number_format($order->get('cost'), 2, '.', '');
		$ik_sign = $sum.':'.$this->config['kassa_id'].':'.$this->config['ik_currency'].':'.$ik_desc.':'.$id.':'.$this->config['secret_key'];
		$ik_sign = base64_encode(md5($ik_sign, true));
		$request = array(
			'url' => $this->config['checkoutUrl']
			,'ik_co_id' => $this->config['kassa_id']
			,'ik_am' => $sum
			,'ik_cur' => $this->config['ik_currency']
			,'ik_pm_no' => $id
			,'ik_desc' => $ik_desc
			,'ik_sign' => $ik_sign
		);
		$link = $this->config['checkoutUrl'] .'?'. http_build_query($request);
		return $link;
	}


	/* @inheritdoc} */
	public function receive(msOrder $order, $params = array()) {
		$id = $order->get('id');
		$sum = number_format($order->get('cost'), 2, '.', '');
		$crc1 = $_REQUEST['ik_am'].':'.$_REQUEST['ik_co_id'].':'.$_REQUEST['ik_co_prs_id'].':'.$_REQUEST['ik_co_rfn'].':'.$_REQUEST['ik_cur'];
		$crc2 = $_REQUEST['ik_desc'].':'.$_REQUEST['ik_inv_crt'].':'.$_REQUEST['ik_inv_id'].':'.$_REQUEST['ik_inv_prc'].':'.$_REQUEST['ik_inv_st'];
		$crc3 = $_REQUEST['ik_pm_no'].':'.$_REQUEST['ik_ps_price'].':'.$_REQUEST['ik_pw_via'].':'.$_REQUEST['ik_trn_id'].':'.$this->config['secret_key'];
		$crc = $crc1.':'.$crc2.':'.$crc3;
		$crc = base64_encode(md5($crc, true));
		if ($_REQUEST['ik_sign'] == $crc) {
			/* @var miniShop2 $miniShop2 */
			$miniShop2 = $this->modx->getService('miniShop2');
			@$this->modx->context->key = 'mgr';
			$miniShop2->changeOrderStatus($order->get('id'), 2);
			exit('OK');
		}
		else {
			$this->paymentError('Err: wrong signature.', $params);
		}
	}


	public function paymentError($text, $request = array()) {
		$this->modx->log(modX::LOG_LEVEL_ERROR,'[miniShop2:Interkassa ' . $text . ', request: '.print_r($request,1));
		header("HTTP/1.0 400 Bad Request");

		die('ERR: ' . $text);
	}
}