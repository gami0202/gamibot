<?php

class UserDao
{
  public $dsn;
  public $user;
  public $password;

  function __construct() {
    $this->dsn = getenv('DSN');
    $this->user = getenv('DBUSER');
    $this->password = getenv('DBPASS');
  }

  function get() {
    try {
      $dbh = new PDO($this->dsn, $this->user, $this->password);

      $sql = "SELECT * FROM users";
      $stmt = $dbh->query($sql);
      return $stmt;

    } catch (PDOException $e) {
      echo 'データベースにアクセスできません！' . $e->getMessage();
    } finally {
      $dbh = null; //close
    }
  }

  function post($userId, $userName) {
    try {
      $dbh = new PDO($this->dsn, $this->user, $this->password);

      $sql = "INSERT INTO users (user_id, user_name) VALUES ("
            . "'" . $userId . "', "
            . "'" . $userName . "'"
            .")";
      $stmt = $dbh->query($sql);

    } catch (PDOException $e) {
      echo 'データベースにアクセスできません！' . $e->getMessage();
    } finally {
      $dbh = null; //close
    }
  }
}
