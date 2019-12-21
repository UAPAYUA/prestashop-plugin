<?php
/**
 * @name uapay_gateway 1.0
 * @description Модуль разработан в компании uapay предназначен для CMS Prestashop 1.7.0.x
 * @author https://uapay.ua/ru
 * @version 1.0
 */
 
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_'))
    exit;

class uapay extends PaymentModule
{
    public function __construct()
    {    
        $this->name = 'uapay_gateway';
        $this->tab = 'payments_gateways';
        $this->version = '1.0';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->currencies = true;
        $this->currencies_mode = 'radio';
        $this->author = 'uapay';

        parent::__construct();

        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('uapay');
        $this->description = $this->getTranslator()->trans('Does this and that', array(), 'Modules.uapay.Admin');
        $this->description = $this->l('Accepting payments by credit card quickly uapay');
        $this->confirmUninstall = $this->l('Are you sure you want to delete all the settings?');
        
        if (!$this->supportCurrencyUAPAY()) {
				$this->enabled = 'no';
			}
        require_once(__DIR__ . '/classes/UaPayApi.php');
        
    }

    public function install()
    {
        $this->hookCreateInvoiceTable();
        $ikStateHold  = new OrderState();
        foreach (Language::getLanguages() AS $language)
        { 
            $ikStateHold->name[$language['id_lang']] = 'On hold uapay';
        }
        $ikStateHold ->send_mail = 1;
        $ikStateHold ->template = "uapay";
        $ikStateHold ->invoice = 1;
        $ikStateHold ->color = "#6699cc";
        $ikStateHold ->unremovable = false;
        $ikStateHold ->logable = 1;
        $ikStateHold ->paid = 1;
        $ikStateHold ->add();
        $ikStateFailed  = new OrderState();
        
        foreach (Language::getLanguages() AS $language)
        { 
            $ikStateFailed->name[$language['id_lang']] = 'Failed uapay';
        }
        $ikStateFailed ->send_mail = 1;
        $ikStateFailed ->template = "uapay";
        $ikStateFailed ->invoice = 1;
        $ikStateFailed ->color = "#cc0000";
        $ikStateFailed ->unremovable = false;
        $ikStateFailed ->logable = 1;
        $ikStateFailed ->paid = 1;
        $ikStateFailed ->add();    
        
        $ikStateCanceled = new OrderState();
        
        foreach (Language::getLanguages() AS $language)
        {
            $ikStateCanceled->name[$language['id_lang']] = 'Canceled uapay';
        }
        $ikStateCanceled ->send_mail = 1;
        $ikStateCanceled ->template = "uapay";
        $ikStateCanceled ->invoice = 1;
        $ikStateCanceled ->color = "#ffffcc";
        $ikStateCanceled ->unremovable = false;
        $ikStateCanceled ->logable = 1;
        $ikStateCanceled ->paid = 1;
        $ikStateCanceled ->add();
        $ikStateRefunded = new OrderState();
        
        foreach (Language::getLanguages() AS $language)
        {
            $ikStateRefunded->name[$language['id_lang']] = 'Refunded of uapay';
        }
        $ikStateRefunded ->send_mail = 1;
        $ikStateRefunded ->template = "uapay";
        $ikStateRefunded ->invoice = 1;
        $ikStateRefunded ->color = "#ffccff";
        $ikStateRefunded ->unremovable = false;
        $ikStateRefunded ->logable = 1;
        $ikStateRefunded ->paid = 1;
        $ikStateRefunded ->add();
        $ikStateCompleted = new OrderState();
        
        foreach (Language::getLanguages() AS $language)
        {
            $ikStateCompleted->name[$language['id_lang']] = 'Completed uapay';
        }
        $ikStateCompleted ->send_mail = 1;
        $ikStateCompleted ->template = "uapay";
        $ikStateCompleted ->invoice = 1;
        $ikStateCompleted ->color = "#669900";
        $ikStateCompleted ->unremovable = false;
        $ikStateCompleted ->logable = 1;
        $ikStateCompleted ->paid = 1;
        $ikStateCompleted ->add();
        $ikStatePaid = new OrderState();
        
        foreach (Language::getLanguages() AS $language)
        {
            $ikStatePaid->name[$language['id_lang']] = 'Processing of uapay';
        }
        $ikStatePaid ->send_mail = 1;
        $ikStatePaid ->template = "uapay";
        $ikStatePaid ->invoice = 1;
        $ikStatePaid ->color = "#ffcc66";
        $ikStatePaid ->unremovable = false;
        $ikStatePaid ->logable = 1;
        $ikStatePaid ->paid = 1;
        $ikStatePaid ->add();

        if (!parent::install()
            OR !$this->registerHook('paymentOptions')
            OR !$this->registerHook('paymentReturn')
            OR !$this->registerHook('actionOrderStatusUpdate')
            OR !$this->registerHook('actionOrderReturn')
            OR !$this->registerHook('actionOrderSlipAdd')
            OR !$this->registerHook('actionOrderStatusPostUpdate')
           // OR !$this->registerHook('createInvoiceTable')
            OR !Configuration::updateValue('UAPAY_CLIENT_ID', '')
            OR !Configuration::updateValue('UAPAY_SECRET_KEY', '')
            OR !Configuration::updateValue('UAPAY_TYPE_OPERATION', '')
            OR !Configuration::updateValue('UAPAY_TEST_MODE', 'test')
            OR !Configuration::updateValue('UAPAY_PAY_TEXT', 'Pay with UAPAY')
            OR !Configuration::updateValue('UAPAY_PAID',$ikStatePaid->id)
            OR !Configuration::updateValue('UAPAY_HOLD',$ikStateHold->id)
            OR !Configuration::updateValue('UAPAY_CANCELED',$ikStateCanceled->id)
            OR !Configuration::updateValue('UAPAY_REFUNDED',$ikStateRefunded->id)
            OR !Configuration::updateValue('UAPAY_FAILED',$ikStateFailed->id)
            OR !Configuration::updateValue('UAPAY_COMPLETED',$ikStateCompleted->id)
        ) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        return (parent::uninstall()
            AND Configuration::deleteByName('UAPAY_CLIENT_ID')
            AND Configuration::deleteByName('UAPAY_SECRET_KEY')
            AND Configuration::deleteByName('UAPAY_TYPE_OPERATION')
            AND Configuration::deleteByName('UAPAY_TEST_MODE')
            AND Configuration::deleteByName('UAPAY_PAY_TEXT')
            AND Configuration::deleteByName('UAPAY_HOLD')
            AND Configuration::deleteByName('UAPAY_PAID')
            AND Configuration::deleteByName('UAPAY_REFUNDED')
            AND Configuration::deleteByName('UAPAY_FAILED')
            AND Configuration::deleteByName('UAPAY_COMPLETED')
            AND Configuration::deleteByName('UAPAY_CANCELED')
        );
    }

    public function hookAdminOptions(){
        			if ($this->supportCurrencyUAPAY()) { ?>
				<h3><?php _e('UAPAY', 'uapay'); ?></h3>
				<table class="form-table">
					<?php $this->generate_settings_html();?>
				</table>
				<?php
			} else { ?>
				<div class="inline error">
					<p>
						<strong><?php _e('Платежный шлюз отключен.', 'uapay'); ?></strong>: <?php _e('UAPAY не поддерживает валюту Вашего магазина!', 'uapay'); ?>
					</p>
				</div>
				<?php
			}
    }

    public function supportCurrencyUAPAY()
		{
			return true;
		}

    public function getContent()
    {
        global $cookie;

        if (Tools::isSubmit('submitUapay')) {
            if ($ua_text = Tools::getValue('uapay_pay_text')) Configuration::updateValue('UAPAY_PAY_TEXT', $ua_text);
            if ($ua_co_id = Tools::getValue('ua_co_id')) Configuration::updateValue('UAPAY_CLIENT_ID', $ua_co_id);
            if ($s_key = Tools::getValue('s_key')) Configuration::updateValue('UAPAY_SECRET_KEY', $s_key);
            if ($ua_test_mode = Tools::getValue('ua_test_mode')) Configuration::updateValue('UAPAY_TEST_MODE', $ua_test_mode);
            if ($ua_type_op = Tools::getValue('ua_type_op')) Configuration::updateValue('UAPAY_TYPE_OPERATION', $ua_type_op);
        }
        $html = '<div style="width:550px">
        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
          <fieldset>
          <legend><img width="20px" src="' . __PS_BASE_URI__ . 'modules/uapay/logo.png" />' . $this->l('Settings') . '</legend>
            <p>' . $this->l('Use the test mode to go directly to the test payment system, without the possibility of choice of other payment systems') . '
            </p>
            <label>
              ' . $this->l('Mode') . '
            </label>
            <div class="margin-form" style="width:110px;">
              <select name="ua_test_mode">
                <option value="live"' . (Configuration::get('UAPAY_TEST_MODE') == 'live' ? ' selected="selected"' : '') . '>' . $this->l('Work mode')
            . '&nbsp;&nbsp;
                </option>
                <option value="test"' . (Configuration::get('UAPAY_TEST_MODE') == 'test' ? ' selected="selected"' : '') . '>' . $this->l('Test mode')
            . '&nbsp;
                &nbsp;
                </option>
              </select>
            </div>
            <p>' . $this->l('ID cash desk, you can find in the settings of your cash desk next to its name') . '</p>
            <label>
              ' . $this->l('ID cash desk') . '
            </label>
            <div class="margin-form">
              <input type="text" name="ua_co_id" value="' . Tools::getValue('UAPAY_CLIENT_ID', Configuration::get('UAPAY_CLIENT_ID')) . '" />
            </div>
            <label>
              ' . $this->l('Secret key') . '
            </label>
            <div class="margin-form">
              <input type="text" name="s_key" value="' . trim(Tools::getValue('UAPAY_SECRET_KEY', Configuration::get('UAPAY_SECRET_KEY'))) . '" />
            </div> 
            <p>' . $this->l('Secret security key can be found in the Security tab in the settings of your cash desk') . '</p>' .'
            
            
            <label>
            ' . $this->l('The text of the form of payment') . '
            </label>
             <div class="margin-form" style="margin-top:5px">
               <input type="text" name="uapay_pay_text" value="' . Configuration::get('UAPAY_PAY_TEXT') . '">
             </div><br>
             <label>
             ' . $this->l('Preview') . '
             </label>
                  <div align="center">' . Configuration::get('UAPAY_PAY_TEXT') . '&nbsp&nbsp
                  <img width="100px" alt="Pay via Uapay" title="Pay via Uapay" src="' . __PS_BASE_URI__
            . 'modules/uapay/logo.png">
                    </div><br>
            <label>
              ' . $this->l('Type operation') . '
            </label>
            <div class="margin-form" style="width:110px;">
              <select name="ua_type_op">
                <option value="PAY"' . (Configuration::get('UAPAY_TYPE_OPERATION') == 'PAY' ? ' selected="selected"' : '') . '>' . $this->l('pay')
            . '&nbsp;&nbsp;
                </option>
                <option value="HOLD"' . (Configuration::get('UAPAY_TYPE_OPERATION') == 'HOLD' ? ' selected="selected"' : '') . '>' . $this->l('hold')
            . '&nbsp;
                &nbsp;
                </option>
              </select>
            </div>
            <div style="float:right;"><input type="submit" name="submitUapay" class="button btn btn-default pull-right" value="' . $this->l('Save') . '" /></div><div 
            class="clear"></div>
          </fieldset>
        </form>';
        return $html;
    }
    
     public function hookActionOrderSlipAdd($params)
    {
        $this->orderRefunded($params);
    }
    
    public function orderRefunded($params,$mode = ''){
            if($mode == 'status'){
                $payment = $params['newOrderStatus']->template;
            }
            else{
                $payment = $params['order']->payment;
            }
        	if($this->displayName == $payment) {
        	
        	  if($mode == 'status'){
                $order_id = $params['id_order'];
            }
            else{
                $order_id = $params['order']->id;
            }
			$cart_id  = $params['cart']->id;
			$id_order_slip = getParamsDataBase('order_slip','id_order_slip','id_order',$order_id);
			$id_order_detail = getParamsDataBase('order_slip_detail','id_order_detail','id_order_slip',$id_order_slip);
		    if($mode == 'status'){
                $status = $params['newOrderStatus']->name;
            }
            else{
                $status = $params['order']->current_state;
            }
			$amountHold = uploadDataInvoice($cart_id, 'amount_hold', 0, 'read')/100;
			if (!empty($amountHold)) {
				if($mode == 'status'){
                    $total_refunded = uploadDataInvoice($cart_id, 'amount_hold', 0, 'read')/100;
                }
                else{
                    $total_refunded = $params['productList'][$id_order_detail]['amount'] + Tools::getValue('partialRefundShippingCost');
                    
                }
                if($total_refunded > 0) {
						$uapay = new UapayApi(Configuration::get('UAPAY_CLIENT_ID'), Configuration::get('UAPAY_SECRET_KEY'));
			            $uapay->testMode(Configuration::get('UAPAY_TEST_MODE'));
						$uapay->setInvoiceId(uploadDataInvoice($cart_id, 'invoice_id', 0, 'read'));
						$uapay->setPaymentId(uploadDataInvoice($cart_id, 'payment_id', 0, 'read'));
						$amountRefund = UapayApi::formattedAmount($total_refunded);
						$amountRefund = $amountRefund/100;
						if ($amountRefund <= $amountHold) {
							$uapay->setDataAmount($total_refunded);
						}
						$result = $uapay->refundInvoice();
						if (!empty($result['status'])) {
							uploadDataInvoice($cart_id, 'invoice_refunded', 1, 'write');
							if ($status != Configuration::get('UAPAY_REFUNDED')) {
							    $history = new OrderHistory();
                                $history->id_order = (int)$order->id;
                                $history->changeIdOrderState(Configuration::get('UAPAY_REFUNDED'), (int)($order_id));
							}
						}
					} else {
					    die(Tools::displayError('Error! Сума возврата должна быть больше 0'));
					}
				} else {
				    die(Tools::displayError('Нельзя сменить статус, пока платеж не подтвержден со стороны UAPAY'));
				}
			 }
     
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        $payment_options = [
            $this->generateForm($params['cart']->id)
        ];
        return $payment_options;
    }
    
    public function hookActionOrderStatusUpdate($params){
      			$order = new Order($params['id_order']);
      			$cart = $params['cart']->id;
                $newStatus = $params['newOrderStatus']->id;
                $oldStatus = getOrderState($params['id_order']);
                $nameStatus = $params['newOrderStatus']->name;
                $statusArr = [];
                        $statusArr += array(Configuration::get('UAPAY_CANCELED') => 'Canceled uapay');
                        $statusArr += array(Configuration::get('UAPAY_REFUNDED') => 'Refunded of uapay');
                        $statusArr += array(Configuration::get('UAPAY_COMPLETED') => 'Completed uapay');
                        $statusArr += array(Configuration::get('UAPAY_HOLD') => 'On hold uapay');
                        $statusArr += array(Configuration::get('UAPAY_PROCESSING') => 'Processing of uapay');
                if($newStatus != $oldStatus){
					if($statusArr[$oldStatus] == 'On hold uapay' && $nameStatus == 'Failed uapay'){
						$paymentId = transaction_data_read($cart,'payment_id');
						if(!is_null($paymentId) || $paymentId != 'NULL') {
							uploadDataInvoice($cart, 'invoice_failed', 1, 'write');
							return false;
						}
					}
					$confirmed = is_null(uploadDataInvoice($cart, 'invoice_confirmed', 1, 'read')) ? 0 : 1;
					$invoiceFailed = is_null(uploadDataInvoice($cart, 'invoice_failed', 1, 'read')) ? 0 : 1;
					if ($invoiceFailed) {
					   die(Tools::displayError('Платеж UAPAY завершился Неудачей'));
					}

					$invoiceCanceled = is_null(uploadDataInvoice($cart, 'invoice_canceled', 1, 'read')) ? 0 : 1;
					if ($invoiceCanceled && $nameStatus !== 'Canceled uapay') {
					    die(Tools::displayError('UaPay:Платеж UAPAY уже был Отменен'));
					}

					$invoiceRefunded = is_null(uploadDataInvoice($cart, 'invoice_refunded', 1, 'read')) ? 0 : 1;
					if ($invoiceRefunded && $nameStatus !== 'Refunded of uapay') {
					    
                        die(Tools::displayError('Платеж UAPAY уже был Возвращен'));
					}
                 $uapay = new UapayApi(Configuration::get('UAPAY_CLIENT_ID'), Configuration::get('UAPAY_SECRET_KEY'));
			     $uapay->testMode(Configuration::get('UAPAY_TEST_MODE'));
 
				if ($nameStatus == 'Completed uapay' && !$confirmed) {
					$uapay->setInvoiceId(transaction_data_read($cart,'invoice_id'));
					$uapay->setPaymentId(transaction_data_read($cart,'payment_id'));
                    uploadDataInvoice($cart, 'invoice_confirmed', 1, 'write');
					$result = $uapay->confirmInvoice();
					if($result === false){
					    die(Tools::displayError('UAPAY Error: completed  ' . $uapay->messageError));
					}
				}
				if ($nameStatus == 'Canceled uapay') {
					$uapay->setInvoiceId(transaction_data_read($cart,'invoice_id'));
					$uapay->setPaymentId(transaction_data_read($cart,'payment_id'));
					$result = $uapay->cancelInvoice();
					if (!empty($result['status'])) {
					    uploadDataInvoice($cart, 'invoice_canceled', 1, 'write');
					    uploadDataInvoice($cart, 'amount_hold', 0, 'write');
					}
					if($result === false){
						die(Tools::displayError('UAPAY Error: canceled ' . $uapay->messageError));
					}
				}
				
				if($nameStatus == 'Refunded of uapay'){
				    $this->orderRefunded($params,'status');
				}
        }
        sleep(1);
    }
    
    public function generateForm($order_id)
		{
			global $cart, $customer;

			$order = new Order($order_id);
            $total = $cart->getOrderTotal();
            $uapay = new UapayApi(Configuration::get('UAPAY_CLIENT_ID'), Configuration::get('UAPAY_SECRET_KEY'));
			$uapay->testMode(Configuration::get('UAPAY_TEST_MODE'));
			echo '<pre>';
			$redirect = Tools::getHttpHost(true) . __PS_BASE_URI__ ."order-confirmation?key={$cart->secure_key}&id_cart=" . (int)($cart->id) ."&id_module="  . (int)($this->id);
            $uapay->setDataRedirectUrl($redirect);
			$uapay->setDataCallbackUrl(Tools::getHttpHost(true) . __PS_BASE_URI__ . 'modules/uapay/validation.php/?order_id=' . $order_id);
			$uapay->setDataOrderId($order_id);
			$uapay->setDataAmount($total);
			$uapay->setDataDescription("Order #{$order_id}");
			$uapay->setDataEmail($customer->email);
			$uapay->setDataReusability(0);
			$result = $uapay->createInvoice(Configuration::get('UAPAY_TYPE_OPERATION'));
			echo '</pre>';
            	if(!empty($result['paymentPageUrl'])) {
                    transaction_data_write($order_id, $result['id'],'');
				}
				if($result === false){
				    print_r($uapay->messageError);
				}

        $externalOption = new PaymentOption();
        $externalOption
            ->setCallToActionText($this->l(Configuration::get('UAPAY_PAY_TEXT')))
            ->setAction($result['paymentPageUrl'])
            ->setAdditionalInformation($this->context->smarty->assign(array(
               'action'=>$result['paymentPageUrl']
            ))->fetch('module:uapay/uapay_info.tpl'))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/logo.png'));
        return $externalOption;
		}

    public function hookPaymentReturn($params)
    {
        if(!empty($_POST)){
            $this->context->smarty->assign(array(
                'shop_name' => $this->context->shop->name,
                'total' => Tools::displayPrice(
                    $params['order']->getOrdersTotalPaid(),
                    new Currency($params['order']->id_currency),
                    false
                ),
                'status' => 'success',
                'reference' => $params['order']->reference,
                'contact_url' => $this->context->link->getPageLink('contact', true)
            ));
            return $this->fetch('module:uapay/uapay_notification.tpl');
        }
    }

    public function hookCreateInvoiceTable(){
       $config = require _PS_CACHE_DIR_ . 'appParameters.php';
        $pdo = new PDO('mysql:host='.$config['parameters']['database_host'].'; dbname='.$config['parameters']['database_name'].'; charset=utf8', $config['parameters']['database_user'], $config['parameters']['database_password']);
        $sth = $pdo->prepare('CREATE TABLE `'.$config['parameters']['database_prefix'].'invoice_uapay` ( `id` INT NOT NULL, `invoice_id` VARCHAR(255) NOT NULL , `payment_id` VARCHAR(255) NOT NULL,`invoice_confirmed` INT NULL DEFAULT NULL, `invoice_canceled` INT NULL DEFAULT NULL, `invoice_refunded` INT NULL DEFAULT NULL , `invoice_failed` INT NULL DEFAULT NULL,`amount_hold` DECIMAL NULL DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE = InnoDB');
        $sth->execute();
    }
}
    function transaction_data_read($id,$colum){
    $config = require _PS_CACHE_DIR_ . 'appParameters.php';
    $pdo = new PDO('mysql:host='.$config['parameters']['database_host'].'; dbname='.$config['parameters']['database_name'].'; charset=utf8', $config['parameters']['database_user'], $config['parameters']['database_password']);
            $sth = $pdo->prepare("SELECT $colum FROM ".$config['parameters']['database_prefix']."invoice_uapay WHERE id = $id");
            $sth->execute();
    $res = $sth->fetchAll();
    return $res[0][$colum];
}

    function read_amount_hold($id){
    $config = require _PS_CACHE_DIR_ . 'appParameters.php';
    $pdo = new PDO('mysql:host='.$config['parameters']['database_host'].'; dbname='.$config['parameters']['database_name'].'; charset=utf8', $config['parameters']['database_user'], $config['parameters']['database_password']);
            $sth = $pdo->prepare("SELECT total_paid FROM ".$config['parameters']['database_prefix']."orders WHERE id_order = $id");
            $sth->execute();
    $res = $sth->fetchAll();
    return $res[0]['total_paid'];
    }

    function transaction_data_write($id,$invoice_id){
        $config = require _PS_CACHE_DIR_ . 'appParameters.php';
        $pdo = new PDO('mysql:host='.$config['parameters']['database_host'].'; dbname='.$config['parameters']['database_name'].'; charset=utf8', $config['parameters']['database_user'], $config['parameters']['database_password']);
        $sth = $pdo->prepare('SELECT * FROM `'.$config['parameters']['database_prefix'].'invoice_uapay` WHERE id='. $id);
        $sth->execute();
        $res = $sth->fetchAll();
            
        if(!$res[0]){
           $config = require _PS_CACHE_DIR_ . 'appParameters.php';
           $pdo = new PDO('mysql:host='.$config['parameters']['database_host'].'; dbname='.$config['parameters']['database_name'].'; charset=utf8', $config['parameters']['database_user'], $config['parameters']['database_password']);
           $params = array( 'id' => $id, 'invoice_id' => "$invoice_id", 'payment_id' => '');
           $sth = $pdo->prepare("INSERT INTO `".$config['parameters']['database_prefix']."invoice_uapay` (`id`,`invoice_id`,`payment_id`) VALUES (:id,:invoice_id,:payment_id)");
           $sth->execute($params);
        }
        else{
           $config = require _PS_CACHE_DIR_ . 'appParameters.php';
           $pdo = new PDO('mysql:host='.$config['parameters']['database_host'].'; dbname='.$config['parameters']['database_name'].'; charset=utf8', $config['parameters']['database_user'], $config['parameters']['database_password']);
           $sth = $pdo->prepare("UPDATE  `".$config['parameters']['database_prefix']."invoice_uapay` SET `invoice_id` = '$invoice_id' WHERE `id` = $id");
           $sth->execute();
        }
    }
        
    function getParamsDataBase($table,$col,$field_name,$field_val){
        $config = require _PS_CACHE_DIR_ . 'appParameters.php';
        $pdo = new PDO('mysql:host='.$config['parameters']['database_host'].'; dbname='.$config['parameters']['database_name'].'; charset=utf8', $config['parameters']['database_user'], $config['parameters']['database_password']);
            $sth = $pdo->prepare("SELECT $col FROM ".$config['parameters']['database_prefix']."$table WHERE $field_name = $field_val");
            $sth->execute();
            $res = $sth->fetchAll();
            return $res[0][$col];
    }
        
    function uploadDataInvoice($id, $col, $val, $mode){
        $config = require _PS_CACHE_DIR_ . 'appParameters.php';
        $pdo = new PDO('mysql:host='.$config['parameters']['database_host'].'; dbname='.$config['parameters']['database_name'].'; charset=utf8', $config['parameters']['database_user'], $config['parameters']['database_password']);
        if($mode == 'read'){
            $sth = $pdo->prepare("SELECT $col FROM ".$config['parameters']['database_prefix']."invoice_uapay WHERE id = $id");
            $sth->execute();
            $res = $sth->fetchAll();
            return $res[0][$col];
        }
        else{
            $sth = $pdo->prepare("UPDATE  `".$config['parameters']['database_prefix']."invoice_uapay` SET `". $col ."` = ".$val." WHERE `id` = $id");
            $sth->execute();
        }
    }
    
    function getOrderState($id){
            $config = require _PS_CACHE_DIR_ . 'appParameters.php';
            $pdo = new PDO('mysql:host='.$config['parameters']['database_host'].'; dbname='.$config['parameters']['database_name'].'; charset=utf8', $config['parameters']['database_user'], $config['parameters']['database_password']);
            $sth = $pdo->prepare('SELECT * FROM `'.$config['parameters']['database_prefix'].'order_history` WHERE id_order='. $id);
            $sth->execute(); 
            $res = $sth->fetchAll();
            $res = array_reverse($res);
            return $res[0]['id_order_state'];
    }

