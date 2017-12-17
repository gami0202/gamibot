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

  // ユーザー数を返却
  function userNum() {
    return count($this->userList);
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

  function getNameArray() {
    $userNames = array();
    foreach ($this->userList as $user) {
      array_push($userNames, $user->name);
    }
    return $userNames;
  }

  function getName($userId) {
    foreach ($this->userList as $user) {
      if ($user->id == $userId) {
        return $user->name;
      }
    }
    return null;
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
