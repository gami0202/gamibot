<?php

include 'ChargeDao.php';

class ChargeList
{
  public $chargeList = array();

  // DBのデータをChargeクラスのリストにロード
  function __construct($squadId) {
    $chargeDao = new ChargeDao();
    $dbCharges = $chargeDao->get($squadId);
    $this->chargeList = $this->parce($dbCharges);
  }

  // line表示用文字列に変換
  function display() {
  	$str = "id, 支払者, 金額, 立替先, コメント\n";
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
