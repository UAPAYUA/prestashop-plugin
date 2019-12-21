<?php
/**
 * @name uapay 1.0
 * @description Модуль разработан в компании uapay предназначен для CMS Prestashop 1.7.0.x
 * @author https://uapay.ua/
 * @version 1.0
 */
 
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/uapay.php');



$uapayModule = new uapay();
 
 

if(empty($_REQUEST['order_id'])) die('LOL! Bad Request!!!');
    
    $order_id = $_REQUEST['order_id'];
    $cart = new Cart($order_id);
    $currency = new Currency((int)$cart->id_currency);
		$uapay = new UaPayApi(Configuration::get('UAPAY_CLIENT_ID'), Configuration::get('UAPAY_SECRET_KEY'));
		$uapay->testMode(Configuration::get('UAPAY_TEST_MODE'));
		$invoiceId = data_read($order_id);

		$invoice = $uapay->getDataInvoice($invoiceId);
		$payment = $invoice['payments'][0];
            
        transaction_data_upload($cart->id, $payment['paymentId']);
        $order = new Order($uapayModule->currentOrder);
		switch($payment['paymentStatus']){
				case UaPayApi::STATUS_FINISHED:
				    uploadDataInvoice($cart->id,'invoice_confirmed', 1, 'write');
				    uploadDataInvoice($cart->id, 'amount_hold', $payment['amount'], 'write');
                    $uapayModule->validateOrder($cart->id,Configuration::get('UAPAY_PAID'), $payment['amount']/100, $uapayModule->displayName, NULL,array('transaction_id'=>$payment['paymentId']));
			        $red = __PS_BASE_URI__.'order-confirmation.php?id_cart='.$cart->id.'&id_module='.$uapayModule->id.'&id_order='.$uapayModule->currentOrder.'&key='.$order->secure_key;
                    Tools::redirectLink($red);
				    break;
				case UaPayApi::STATUS_HOLDED:
 				    if($payment['status'] == 'PAID') {
 				        uploadDataInvoice($cart->id, 'amount_hold', $payment['amount'], 'write');
 				        $uapayModule->validateOrder($cart->id,Configuration::get('UAPAY_HOLD'), $payment['amount']/100, $uapayModule->displayName, NULL,array('transaction_id'=>$payment['paymentId']));
				    }
					break;
				case UaPayApi::STATUS_CANCELED:
				case UaPayApi::STATUS_REJECTED:
                    $history = new OrderHistory();
                    $history->id_order = (int)$order->id;
                    $history->changeIdOrderState(Configuration::get('UAPAY_FAILED'), (int)($order->id));
					break;
			}
 $order = new Order($uapayModule->currentOrder);
exit;

function data_read($id){
    $config = require _PS_CACHE_DIR_ . 'appParameters.php';
    $pdo = new PDO('mysql:host='.$config['parameters']['database_host'].'; dbname='.$config['parameters']['database_name'].'; charset=utf8', $config['parameters']['database_user'], $config['parameters']['database_password']);
    $sth = $pdo->prepare("SELECT invoice_id FROM ".$config['parameters']['database_prefix']."invoice_uapay WHERE id = $id");
    $sth->execute();
    $res = $sth->fetchAll();
    return $res[0]['invoice_id'];
}

function transaction_data_upload($id, $payment_id){
    $config = require _PS_CACHE_DIR_ . 'appParameters.php';
    $pdo = new PDO('mysql:host='.$config['parameters']['database_host'].'; dbname='.$config['parameters']['database_name'].'; charset=utf8', $config['parameters']['database_user'], $config['parameters']['database_password']);
            $sth = $pdo->prepare("UPDATE  `".$config['parameters']['database_prefix']."invoice_uapay` SET `payment_id` = '$payment_id' WHERE `id` = $id");
            $sth->execute();
}

 
 
