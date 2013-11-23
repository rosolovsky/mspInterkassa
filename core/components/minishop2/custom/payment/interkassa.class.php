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
		,'checkoutUrl' => $this->modx->getOption('ms2_payment_ik_url', null, 'https://www.interkassa.com/lib/payment.php', true)
		,'shop_id' => $this->modx->getOption('ms2_payment_ik_shop_id', null, 'F3773478-AEE0-13B3-D4B9-BAE553F2EC9D', true)
		,'secret_key' => $this->modx->getOption('ms2_payment_ik_secret_key', null, 'mm3L9ULUKgJHwGzS')
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
		$sum = number_format($order->get('cost'), 2, '.', '');
		$request = array(
			'url' => $this->config['checkoutUrl']
		,'ik_shop_id' => $this->config['shop_id']
		,'ik_payment_amount' => $sum
		,'ik_payment_id' => $id
		,'ik_payment_desc' => 'Payment #'.$id
		,'ik_paysystem_alias' =>''
		);
		$link = $this->config['checkoutUrl'] .'?'. http_build_query($request);
		return $link;
	}


	/* @inheritdoc} */
	public function receive(msOrder $order, $params = array()) {
		$id = $order->get('id');
		$sum = number_format($order->get('cost'), 2, '.', '');

		$crc1 = $_REQUEST['ik_shop_id'].':'.$_REQUEST['ik_payment_amount'].':'.$_REQUEST['ik_payment_id'].':'.$_REQUEST['ik_paysystem_alias'];
		$crc2 = $_REQUEST['ik_baggage_fields'].':'.$_REQUEST['ik_payment_state'].':'.$_REQUEST['ik_trans_id'].':'.$_REQUEST['ik_currency_exch'];
		$crc3 = $_REQUEST['ik_fees_payer'].':'.$this->config['secret_key'];
		$crc = md5($crc1.':'.$crc2.':'.$crc3);

		/*$crc = md5($sum.':'.$id.':'.$this->config['secret_key']);*/
		if (strtoupper($_REQUEST['ik_sign_hash']) == strtoupper($crc)) {
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