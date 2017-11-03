<?php




// Please make sure you insert your merchant id in the OSC admin area

  class payu{

    var $code, $title, $description, $enabled,$_order_id;



// class constructor

    function payu() {

      global $order;

      $this->code = 'payu';

      $this->title = MODULE_PAYMENT_PAYU_TEXT_TITLE;

      
      $this->sort_order = MODULE_PAYMENT_PAYU_SORT_ORDER;

      $this->enabled = ((MODULE_PAYMENT_PAYU_STATUS == 'True') ? true : false);

	  if(MODULE_PAYMENT_PAYU_TESTMODE=='TEST')

		$this->form_action_url =  'https://test.payu.in/_payment.php';

	  else

		  $this->form_action_url =  'https://secure.payu.in/_payment.php';


    }



   


// class methods

   
    function javascript_validation() {

	
	 
	}



    function selection() {

      $selection = array('id' => $this->code,

                         'module' => $this->title);
      return $selection;

    }



    function pre_confirmation_check() {

	  return false;
	}



    function confirmation() {

		global $HTTP_POST_VARS;

	  $confirmation='';

      return $confirmation;

    }

 public function cleanString($string) {

        $string_step1 = strip_tags($string);
        $string_step2 = nl2br($string_step1);
        $string_step3 = str_replace("<br />", "<br>", $string_step2);
        $cleaned_string = str_replace("\"", " inch", $string_step3);
        return $cleaned_string;
    }


    function process_button() {
     
		
      global $HTTP_POST_VARS, $order,$order_total_modules,$currencies;
	  
		
	  //$temp=mysql_query("select value from currencies where code='INR'")or die(mysql_error());
	 // $currency_value=mysql_fetch_array($temp);
	  $products_ordered = '';
	  for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {  
	   $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
	  }
	  $products_ordered .= "\n";
	  $order_totals = $order_total_modules->process();

	   for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
		$products_ordered .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
	  }
   	  $hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
	$posted = array();
	//print_r($this->session->data['order_id']);
	$posted['txnid']=substr(hash('sha256', mt_rand() . microtime()), 0, 20);
	//$posted['txnid']=12345678;
		//$amt=number_format(($order->info['total'] * $currency_value[0]),2,'.','');
		$amt=$order->info['total'];
		
	$posted['amount']= $amt;
	$posted['firstname'] = $order->customer['firstname'];
    $posted['phone']=$order->customer['telephone'];
	$posted['key']= MODULE_PAYMENT_PAYU_MERCHANTID;
       
         $productInfo = array();
        $productInfo2 = array();
        foreach ($order->products as $item) {
            $sql = mysql_query("select products_description from products_description where products_id = '" . (int) $item[id] . "'");

            $result = mysql_fetch_array($sql);
            
            $productInfo['name'] = $this->cleanString($item[name]);
            $productInfo['description'] = $this->cleanString(substr($result[products_description],0,100));
            $productInfo['value'] = number_format($item[final_price], 0, '', '');
            $productInfo['isRequired'] = true;
            $productInfo['settlementEvent'] = "EmailConfirmation";
            $productInfo2[] = $productInfo;
        }
        $productIndoFilterData['paymentParts'] = $productInfo2;
       $jsonProductInfo = json_encode($productIndoFilterData);
    $posted['productinfo']=$jsonProductInfo;
        
	$posted['email']=$order->customer['email_address'];
	$posted['service_provider']='payu_paisa';
        $posted['udf2']=$posted['txnid'];
	$hashVarsSeq = explode('|', $hashSequence);
    $hash_string = '';
    foreach($hashVarsSeq as $hash_var) {
      $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] : '';
      $hash_string .= '|';
    }
    $hash_string .= MODULE_PAYMENT_PAYU_SALT;
	$hash = strtolower(hash('sha512', $hash_string));
	$posted['hash']=$hash;
       
    $process_button_string = tep_draw_hidden_field('key', $posted['key']) . 
	                           
                             tep_draw_hidden_field('amount',$posted['amount']).

		             tep_draw_hidden_field('productinfo',$posted['productinfo']).

                             tep_draw_hidden_field('firstname', $posted['firstname']) .
                             

                             tep_draw_hidden_field('email',$posted['email']) .
                             tep_draw_hidden_field('service_provider',$posted[service_provider]) .
                             tep_draw_hidden_field('udf2', $posted['udf2']).
                             tep_draw_hidden_field('phone', $posted['phone']) .

                             tep_draw_hidden_field('furl', tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL')) .

                             tep_draw_hidden_field('surl', tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL')).

                             tep_draw_hidden_field('lastname',$order->customer['lastname']) .

                             tep_draw_hidden_field('address1',$order->customer['street_address']) .

                             tep_draw_hidden_field('address2',$order->delivery['street_address']) .

							tep_draw_hidden_field('city', $order->customer['city']) .

                             tep_draw_hidden_field('state', $order->customer['state']) .

                             tep_draw_hidden_field('postal_code', $order->customer['postcode']) .

                             tep_draw_hidden_field('country', $order->customer['country']['iso_code_3']) .
            
            
            
            tep_draw_hidden_field('ship_name', $order->delivery['firstname']."". $order->delivery['lastname']) .
            tep_draw_hidden_field('ship_address', $order->delivery['street_address']) .
            tep_draw_hidden_field('ship_zipcode', $order->delivery['postcode']) .
            tep_draw_hidden_field('ship_city', $order->delivery['city']) .
            tep_draw_hidden_field('ship_state', $order->delivery['state']) .
            tep_draw_hidden_field('ship_country', $order->delivery['country']['iso_code_3']) .
            tep_draw_hidden_field('ship_phone', $order->delivery['postcode']) .
            

							 tep_draw_hidden_field('udf1', $udf1) .tep_draw_hidden_field('udf3', $udf3).

								 tep_draw_hidden_field('udf4', $udf4).tep_draw_hidden_field('udf5', $udf5).

									 tep_draw_hidden_field('txnid',$posted['txnid']).

								tep_draw_hidden_field('hash',$posted['hash']).
             tep_draw_hidden_field('website', tep_href_link(FILENAME_DEFAULT, '', 'SSL')).
							   
                             tep_draw_hidden_field('curl', tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL'));
	 
    return $process_button_string;

    }

    function before_process(){

    global $order;
	  if(!empty($_POST)) {
   
  foreach($_POST as $key => $value) {
    
    $txnRs[$key] = htmlentities($value, ENT_QUOTES);
  }
}
if($txnRs['status']=='success'){
    
       $order->info['cc_number']=$txnRs['udf2']; 
      $merc_hash_vars_seq = explode('|', "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10");
      //generation of hash after transaction is = salt + status + reverse order of variables
      $merc_hash_vars_seq = array_reverse($merc_hash_vars_seq);
      
      $merc_hash_string = MODULE_PAYMENT_PAYU_SALT . '|' . $txnRs['status'];
	
      foreach ($merc_hash_vars_seq as $merc_hash_var) {
        $merc_hash_string .= '|';
        $merc_hash_string .= isset($txnRs[$merc_hash_var]) ? $txnRs[$merc_hash_var] : '';
      
      }
     
      $merc_hash =strtolower(hash('sha512', $merc_hash_string));
      if($merc_hash!=$txnRs['hash']) {
          
         
        tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=data tampered', 'SSL',true,false));

      } 
      

        
}
else{
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=transaction failed', 'SSL',true,false));
}
}


  


    function after_process() {
        global $HTTP_POST_VARS, $order, $insert_id;

      
        $sql_data_array = array('orders_id' => (int)$insert_id, 
                                'orders_status_id' => (int)$order->info['order_status'], 
                                'date_added' => 'now()', 
                                'customer_notified' => '0',
                                'comments' => 'transaction failed');

        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
      

    }



    function get_error() {     



      $error = array('title' => MODULE_PAYMENT_EBS_TEXT_ERROR,

                     'error' => stripslashes(urldecode($_GET['error'])));

      return $error;

    }



    function check() {

       if (!isset($this->_check)) {



        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYU_STATUS'");



        $this->_check = tep_db_num_rows($check_query);



      }



      return $this->_check;

    }



    function install() {
	
	  
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable PayUMoney Payment Module', 'MODULE_PAYMENT_PAYU_STATUS', 'True', 'Do you want to accept PayUMoney payments?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");

      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_PAYU_MERCHANTID', 'JBZaLc', 'Your Merchant ID of PayUMoney', '5', '0', now())");

      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('SALT', 'MODULE_PAYMENT_PAYU_SALT', 'GQs7yium', 'Your SALT of PayUMoney', '6', '0', now())");	  

      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Test Mode', 'MODULE_PAYMENT_PAYU_TESTMODE', 'TEST', 'Test mode used for PayUMoney', '6', '0', 'tep_cfg_select_option(array(\'TEST\', \'LIVE\'), ', now())");
      
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display', 'MODULE_PAYMENT_PAYU_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '2', now())");

    }



    function remove() {
	
	  tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");

    }



    function keys() {

      return array('MODULE_PAYMENT_PAYU_STATUS', 'MODULE_PAYMENT_PAYU_MERCHANTID', 'MODULE_PAYMENT_PAYU_SALT', 'MODULE_PAYMENT_PAYU_TESTMODE');

    }

  }

?>