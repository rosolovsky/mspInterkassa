<?php
/**
 * Loads system settings into build
 *
 * @package mspinterkassa
 * @subpackage build
 */
$settings = array();

$tmp = array(
	'url' => array(
		'xtype' => 'textfield',
		'value' => 'https://www.interkassa.com/lib/payment.php',
	),
	'shop_id' => array(
		'xtype' => 'textfield',
		'value' => '',
	),
	'secret_key' => array(
		'xtype' => 'text-password',
		'value' => '',
	),
	'success_id' => array(
		'xtype' => 'numberfield',
		'value' => 0,

	),
	'failure_id' => array(
		'xtype' => 'numberfield',
		'value' => 0,
	),

);

foreach ($tmp as $k => $v) {
	/* @var modSystemSetting $setting */
	$setting = $modx->newObject('modSystemSetting');
	$setting->fromArray(array_merge(
		array(
			'key' => 'ms2_payment_ik_'.$k,
			'namespace' => 'minishop2',
			'area' => 'ms2_payment',
		), $v
	),'',true,true);

	$settings[] = $setting;
}

unset($tmp);
return $settings;