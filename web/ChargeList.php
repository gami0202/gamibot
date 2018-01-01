<?php

include 'ChargeDao.php';

class ChargeList
{
  public $chargeList = array();

  // DBのデータをChargeクラスのリストにロード
  function __construct() {
    $chargeDao = new ChargeDao();
    $dbCharges = $chargeDao->get();
    $this->chargeList = $this->parce($dbCharges);
  }

  // line表示用文字列に変換
  function display() {
  	$str = "";
  	foreach($this->chargeList as $charge) {
  		$str .= $charge->display();
  	}
  	return $str;
  }

  // DBデータをChargeクラスにパース
  private function parce($dbCharges) {
  	$charges = array();
  	foreach ($dbCharges as $dbCharge) {
				array_push($charges, new Charge($dbCharge['id'], $dbCharge['owner'], $dbCharge['value'], $dbCharge['target'], $dbCharge['comment']));
  	}
  	return $charges;
  }
}
