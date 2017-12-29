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

  function delete($id) {
    if (!is_numeric($id)) {
  		$response_format_text = [
  			"type" => "text",
  			"text" => "半角数字を入力してください"
  		];
    } else {
      $index = 0;
      $hitFlag = false;
      foreach($this->chargeList as $charge) {
        if ($id == $charge->id) {
          array_splice($this->chargeList, $index, 1);
          $hitFlag = true;
          break;
        }
        $index++;
      }

      if (!$hitFlag) {
        $response_format_text = [
    			"type" => "text",
    			"text" => "該当のIDが存在しませんでした。"
    		];
      } else {
        $this->addDbForce();
        $response_format_text =  [
    			"type" => "text",
    			"text" => $id . "を削除しました。"
    		];
      }
    }
    return $response_format_text;
  }

  function addDbForce() {
    $file = 'charges.txt';
    file_put_contents($file, "");
    foreach ($this->chargeList as $charge) {
      file_put_contents($file, $charge->toCsv(), FILE_APPEND);
    }
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

  	$id = '';	$owner = '';	$charge = '';	$target = ''; $comment = '';
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
  				$target = $dbElement;
          break;
  			case 0:
          $comment = $dbElement;
  				array_push($charges, new Charge($id, $owner, $charge, $target, $comment));
  				break;
  		}
  		$count++;
  	}
  	return $charges;
  }
}
