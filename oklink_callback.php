<?php

require 'includes/application_top.php';
require_once(dirname(__FILE__) . "/includes/modules/payment/oklink/Oklink.php");

$type = $_GET['type'];

if($type == "success") {

  // Customer's browser - checkout was successful
  unset($_SESSION['oklink_order_id']);
  zen_redirect(zen_href_link('checkout_success'));
} 

if($type == MODULE_PAYMENT_OKLINK_CALLBACK_SECRET) {

  $client = Oklink::withApiKey(MODULE_PAYMENT_OKLINK_APIKEY,MODULE_PAYMENT_OKLINK_APISECRET);
  if( $client->checkCallback() ){
      $order = json_decode(file_get_contents('php://input'));
      if( $order ){
          $db->Execute("update ". TABLE_ORDERS. " set orders_status = " . MODULE_PAYMENT_OKLINK_COMPLETE_STATUS_ID . " where orders_id = ". intval($order->custom));
          header("HTTP/1.1 200 OK");
      }
  }

  header("HTTP/1.1 500 Internal Server Error");
}