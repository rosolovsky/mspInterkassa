<?php
define('MODX_API_MODE', true);
require dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/index.php';

$modx->getService('error','error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('FILE');

/* @var miniShop2 $miniShop2 */
$miniShop2 = $modx->getService('minishop2');
$miniShop2->loadCustomClasses('payment');

if (!class_exists('Interkassa')) {exit('Error: could not load payment class "Interkassa".');}
$context = '';
$params = array();

/* @var msPaymentInterface|Interkassa $handler */
$handler = new Interkassa($modx->newObject('msOrder'));

if (!empty($_REQUEST['ik_sign']) && !empty($_REQUEST['ik_pm_no']) && empty($_REQUEST['action'])) {
	if ($order = $modx->getObject('msOrder', $_REQUEST['ik_pm_no'])) {
		$handler->receive($order, $_REQUEST);
	}
	else {
		$modx->log(modX::LOG_LEVEL_ERROR, '[miniShop2:Interkassa] Could not retrieve order with id '.$_REQUEST['ik_pm_no']);
	}
}

if (!empty($_REQUEST['ik_pm_no'])) {$params['msorder'] = $_REQUEST['ik_pm_no'];}

$success = $failure = $modx->getOption('site_url');
if ($id = $modx->getOption('ms2_payment_ik_success_id', null, 0)) {
	$success = $modx->makeUrl($id, $context, $params, 'full');
}
if ($id = $modx->getOption('ms2_payment_ik_failure_id', null, 0)) {
	$failure = $modx->makeUrl($id, $context, $params, 'full');
}

$redirect = !empty($_REQUEST['action']) && $_REQUEST['action'] == 'success' ? $success : $failure;
header('Location: ' . $redirect);