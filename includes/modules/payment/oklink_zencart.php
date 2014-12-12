<?php

// Oklink payment plugin
class oklink_zencart {
  
  var $code;
  var $title;
  var $description;
  var $enabled;
  
  // Constructor
  function oklink_zencart() {
    
    $this->code = 'oklink_zencart';
    $this->title = MODULE_PAYMENT_OKLINK_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_OKLINK_TEXT_DESCRIPTION;
    $this->enabled = ((MODULE_PAYMENT_OKLINK_STATUS == 'True') ? true : false);
    $this->sort_order = MODULE_PAYMENT_OKLINK_SORT_ORDER;
    $this->order_status = MODULE_PAYMENT_OKLINK_PROCESSING_STATUS_ID;
  }
  
  /**
   * JS validation which does error-checking of data-entry if this module is selected for use
    */
  function javascript_validation() {
    return false;
  }
  /**
   * Evaluate the Credit Card Type for acceptance and the validity of the Credit Card Number & Expiration Date
   */
  function pre_confirmation_check() {
    return false;
  }
  /**
   * Display Credit Card Information on the Checkout Confirmation Page
    */
  function confirmation() {
    return false;
  }
  
  function selection() {
    return array('id' => $this->code,
                 'module' => MODULE_PAYMENT_OKLINK_TEXT_CHECKOUT
                 );
  }
  
  function process_button() {
    return false;
  }
  
  function before_process() {
    return false;
  }
  
  function after_process() {
    global $insert_id, $db, $order;
    
    $info = $order->info;
    
    $name = "Order #" . $insert_id;
    $custom = $insert_id;
    $currencyCode = $info['currency'];
    $total = $info['total'];
    $callback = zen_href_link('oklink_callback.php', $parameters='', $connection='NONSSL', $add_session_id=true, $search_engine_safe=true, $static=true );
    $params = array (
      'name' => $name,
      'price' => floatval($total),
      'price_currency' => $currencyCode,
      'custom'  => $insert_id,
      'callback_url' => $callback . "?type=" . MODULE_PAYMENT_OKLINK_CALLBACK_SECRET,
      'success_url' => $callback . "?type=success",      
    );
    
    require_once(dirname(__FILE__) . '/oklink/Oklink.php');
    
    $client = Oklink::withApiKey(MODULE_PAYMENT_OKLINK_APIKEY,MODULE_PAYMENT_OKLINK_APISECRET);
    
    try {
      
        $button = $client->buttonsButton($params)->button;
    } catch (Exception $f) {
        $this->tokenFail($f->getMessage());
    }
    
    $_SESSION['cart']->reset(true);
    $_SESSION['oklink_order_id'] = $insert_id;
    zen_redirect(OklinkBase::WEB_BASE."merchant/mPayOrderStemp1.do?buttonid=".$button->id);
    
    return false;
  }
  
  function tokenFail($msg) {
    
    global $db;
    // $db->Execute("update ". TABLE_CONFIGURATION. " set configuration_value = '' where configuration_key = 'MODULE_PAYMENT_OKLINK_OAUTH'");
    $db->Execute("update ". TABLE_CONFIGURATION. " set configuration_value = '' where configuration_key = 'MODULE_PAYMENT_OKLINK_APIKEY'");
    $db->Execute("update ". TABLE_CONFIGURATION. " set configuration_value = '' where configuration_key = 'MODULE_PAYMENT_OKLINK_APISECRET'");
    throw new Exception("No account is connected, or the current account is not working. You need to connect a merchant account in ZenCart Admin. $msg");
  }
  
  function check() {
    global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_OKLINK_STATUS'");
      $this->_check = $check_query->RecordCount();
    }
    return $this->_check;
  }
  
  function install() {
    global $db, $messageStack;
    if (defined('MODULE_PAYMENT_OKLINK_STATUS')) {
      $messageStack->add_session('Oklink module already installed.', 'error');
      zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=oklink', 'NONSSL'));
      return 'failed';
    }
    
    $callbackSecret = md5('zencart_' . mt_rand());
  
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Oklink Module', 'MODULE_PAYMENT_OKLINK_STATUS', 'True', 'Enable the Oklink bitcoin plugin?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_OKLINK_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '8', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Pending Notification Status', 'MODULE_PAYMENT_OKLINK_PROCESSING_STATUS_ID', '" . DEFAULT_ORDERS_STATUS_ID .  "', 'Set the status of orders made with this payment module that are not yet completed to this value<br />(\'Pending\' recommended)', '6', '5', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_OKLINK_COMPLETE_STATUS_ID', '2', 'Set the status of orders made with this payment module that have completed payment to this value<br />(\'Processing\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    // $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Merchant Account', 'MODULE_PAYMENT_OKLINK_OAUTH', '', '', '6', '6', 'oklink_oauth_set(', 'oklink_oauth_use', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('API Key', 'MODULE_PAYMENT_OKLINK_APIKEY', '', '', '6', '6', now(), 'oklink_censor_use')");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('API Secret', 'MODULE_PAYMENT_OKLINK_APISECRET', '', '', '6', '6', now(), 'oklink_censor_use')");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Callback Secret Key (do not edit)', 'MODULE_PAYMENT_OKLINK_CALLBACK_SECRET', '$callbackSecret', '', '6', '6', now(), 'oklink_censor_use')");
  }
  
  function remove() {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key LIKE 'MODULE\_PAYMENT\_OKLINK\_%'");
  }
  
  function keys() {
    return array(
      'MODULE_PAYMENT_OKLINK_STATUS',
      'MODULE_PAYMENT_OKLINK_SORT_ORDER',
      'MODULE_PAYMENT_OKLINK_PROCESSING_STATUS_ID',
      'MODULE_PAYMENT_OKLINK_COMPLETE_STATUS_ID',
      'MODULE_PAYMENT_OKLINK_APIKEY',
      'MODULE_PAYMENT_OKLINK_APISECRET',
      'MODULE_PAYMENT_OKLINK_CALLBACK_SECRET',
    );
  }

}

function oklink_censor_use($value) {
  return "(hidden for security reasons)";
}
