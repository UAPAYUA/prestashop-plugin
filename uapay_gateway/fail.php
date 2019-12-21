<?php
/**
 * @name uapay_gateway 1.0
 * @description Модуль разработан в компании uapay предназначен для CMS Prestashop 1.7.0.x
 * @author https://uapay.ua/
 * @version 1.0
 */

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/uapay.php');
    $uapay = new uapay();
	if(empty($_REQUEST['order_id'])) die('LOL! Bad Request!!!');

    $cart = new Cart((int)$_REQUEST['order_id']);
    $currency = new Currency((int)$cart->id_currency);

    $uapay = new UaPayApi(Configuration::get('UAPAY_CLIENT_ID'), Configuration::get('UAPAY_SECRET_KEY'));
	$uapay->testMode(Configuration::get('UAPAY_TEST_MODE'));
	$invoiceId = $_COOKIE['uapay_invoice_id'];
    $invoice = $uapay->getDataInvoice($invoiceId);
	$payment = $invoice['payments'][0];
    if($payment['paymentStatus'] == UaPayApi::STATUS_CANCELED || $payment['paymentStatus'] == UaPayApi::STATUS_REJECTED){
        $uapay->validateOrder($cart->id, _PS_OS_PAYMENT_, $payment['amount'], $uapay->displayName, NULL,array('transaction_id'=>$payment['invoiceId']));
        
    }elseif ($payment['paymentStatus'] == UaPayApi::STATUS_HOLDED){
        $uapay->validateOrder($cart->id, Tools::getHttpHost(true) . __PS_BASE_URI__ . 'modules/uapay/fail.php', $payment['amount'], $uapay->displayName, NULL,array('transaction_id'=>$payment['invoiceId']));
    }
    $order = new Order($uapay->currentOrder);
    Tools::redirectLink(__PS_BASE_URI__ . 'history');






