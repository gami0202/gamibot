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

  function get($squadId) {
    try {
      $dbh = new PDO($this->dsn, $this->user, $this->password);

      $sql = "SELECT * FROM users WHERE squad_id='".$squadId."'";
      $stmt = $dbh->query($sql);
      return $stmt;

    } catch (PDOException $e) {
      echo 'データベースにアクセスできません！' . $e->getMessage();
    } finally {
      $dbh = null; //close
    }
  }

  function post($userId, $userName, $squadId) {
    try {
      $dbh = new PDO($this->dsn, $this->user, $this->password);

      $sql = "INSERT INTO users (user_id, user_name, squad_id) VALUES ("
            . "'" . $userId . "', "
            . "'" . $userName . "', "
            . "'" . $squadId . "'"
            .")";
      $stmt = $dbh->query($sql);

    } catch (PDOException $e) {
      echo 'データベースにアクセスできません！' . $e->getMessage();
    } finally {
      $dbh = null; //close
    }
  }

  function deleteAllBySquadId($squadId) {
    try {
      $dbh = new PDO($this->dsn, $this->user, $this->password);

      $sql = "DELETE FROM users WHERE squad_id='{$squadId}'";
      $stmt = $dbh->query($sql);

    } catch (PDOException $e) {
      echo 'データベースにアクセスできません！' . $e->getMessage();
    } finally {
      $dbh = null; //close
    }
  }

  function deleteAll() {
    try {
      $dbh = new PDO($this->dsn, $this->user, $this->password);

      $sql = "DELETE FROM users";
      $stmt = $dbh->query($sql);

      $sql = "SELECT setval ('users_id_seq', 1, false)";
      $stmt = $dbh->query($sql);

    } catch (PDOException $e) {
      echo 'データベースにアクセスできません！' . $e->getMessage();
    } finally {
      $dbh = null; //close
    }
  }
}
