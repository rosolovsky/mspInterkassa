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
		'value' => 'F3773478-AEE0-13B3-D4B9-BAE553F2EC9D',
	),
	'secret_key' => array(
		'xtype' => 'text-password',
		'value' => 'mm3L9ULUKgJHwGzS',
	),
	'success_id' => array(
		'xtype' => 'numberfield',
		'value' => 50,

	),
	'failure_id' => array(
		'xtype' => 'numberfield',
		'value' => 51,
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