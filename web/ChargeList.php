<?php
class ChargeList
{
  public $chargeList = array();

  // DBのデータをChargeクラスのリストにロード
  function __construct() {
    $file = 'charges.txt';
    $current = file_get_contents($file);
    $dbElements = str_getcsv($current);
    $this->chargeList = $this->parce($dbElements);
  }

  // line表示用文字列に変換
  function display() {
  	$str = "";
  	foreach($this->chargeList as $charge) {
  		$str .= $charge->display();
  	}
  	return $str;
  }

  function getNextId() {
    $lastId = '';
    foreach($this->chargeList as $charge) {
      $lastId = $charge->id;
    }
    return $lastId + 1;
  }

  // csvをChargeクラスにごり押しパース
  private function parce($dbElements) {
  	$charges = array();

  	$id = '';	$owner = '';	$charge = '';	$target = '';
  	$count = 1;
  	foreach ($dbElements as $dbElement) {
  		switch ($count % 5) {
  			case 1:
  				$id = $dbElement;
  				break;
  			case 2:
  				$owner = $dbElement;
  				break;
  			case 3:
  				$charge = $dbElement;
  				break;
        case 4:
          $comment = $dbElement;
          break;
  			case 0:
  				$target = $dbElement;
  				array_push($charges, new Charge($id, $owner, $charge, $target, $comment));
  				break;
  		}
  		$count++;
  	}
  	return $charges;
  }
}
