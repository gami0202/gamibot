<?php

include 'UserDao.php';

class UserList
{
  public $userList = array();

  // DBのデータをChargeクラスのリストにロード
  function __construct($squadId) {
    $userDao = new UserDao();
    $dbUsers = $userDao->get($squadId);
    $this->userList = $this->parce($dbUsers);
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

  // userNameが見つからなかったときは、userIdをそのまま返します。
  function getNameById($userId) {
    foreach ($this->userList as $user) {
      if ($user->id == $userId) {
        return $user->name;
      }
    }
    return $userId;
  }

  // DBデータUserクラスにパース
  private function parce($dbUsers) {
  	$users = array();
  	foreach ($dbUsers as $dbUser) {
			array_push($users, new User($dbUser['user_id'], $dbUser['user_name']));
  	}
  	return $users;
  }

}
