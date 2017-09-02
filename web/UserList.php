<?php
class UserList{
  public $userList = array();

  // DBのデータをChargeクラスのリストにロード
  function __construct() {
    $file = 'users.txt';
    $current = file_get_contents($file);
    $dbElements = str_getcsv($current);
    $this->userList = $this->parce($dbElements);
  }

  // line表示用文字列に変換
  function display() {
  	$str = "";
  	foreach($this->userList as $user) {
  		$str .= $user->display();
  	}
  	return $str;
  }

  //ユーザーID一覧を配列で返却する
  function getIdArray() {
    $userIds = array();
    foreach ($this->userList as $user) {
      array_push($userIds, $user->id);
    }
    return $userIds;
  }

  // csvをUserクラスにごり押しパース
  private function parce($dbElements) {
  	$users = array();

  	$id = '';	$name = '';
  	$count = 1;
  	foreach ($dbElements as $dbElement) {
  		switch ($count % 2) {
  			case 1:
  				$id = $dbElement;
  				break;
  			case 0:
  				$name = $dbElement;
  				array_push($users, new User($id, $name));
  				break;
  		}
  		$count++;
  	}
  	return $users;
  }

}
