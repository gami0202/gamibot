<?php

class ChargeDao
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

      $sql = "SELECT * FROM charges WHERE squad_id='".$squadId."'";
      $stmt = $dbh->query($sql);
      return $stmt;

    } catch (PDOException $e) {
      echo 'データベースにアクセスできません！' . $e->getMessage();
    } finally {
      $dbh = null; //close
    }
  }

  function post($owner, $value, $target, $comment, $squadId) {
    try {
      $dbh = new PDO($this->dsn, $this->user, $this->password);

      $sql = "INSERT INTO charges (owner, value, target, comment, squad_id) VALUES ("
            . "'" . $owner . "', "
            . "'" . $value . "', "
            . "'" . $target . "', "
            . "'" . $comment . "', "
            . "'" . $squadId . "'"
            .")";
      $stmt = $dbh->query($sql);

    } catch (PDOException $e) {
      echo 'データベースにアクセスできません！' . $e->getMessage();
    } finally {
      $dbh = null; //close
    }
  }

  function delete($id, $squadId) {
    try {
      $dbh = new PDO($this->dsn, $this->user, $this->password);

      $sql = "DELETE FROM charges WHERE id=" . $id . " AND squad_id='" . $squadId . "'";
      $line = $dbh->exec($sql);

    } catch (PDOException $e) {
      echo 'データベースにアクセスできません！' . $e->getMessage();
    } finally {
      $dbh = null; //close
    }
    return $line;
  }

  function deleteAllBySquadId($squadId) {
    try {
      $dbh = new PDO($this->dsn, $this->user, $this->password);

      $sql = "DELETE FROM charges WHERE squad_id='{$squadId}'";
      $line = $dbh->query($sql);

    } catch (PDOException $e) {
      echo 'データベースにアクセスできません！' . $e->getMessage();
    } finally {
      $dbh = null; //close
    }
  }

  function deleteAll() {
    try {
      $dbh = new PDO($this->dsn, $this->user, $this->password);

      $sql = "DELETE FROM charges";
      $stmt = $dbh->query($sql);

      $sql = "SELECT setval ('charges_id_seq', 1, false)";
      $stmt = $dbh->query($sql);

    } catch (PDOException $e) {
      echo 'データベースにアクセスできません！' . $e->getMessage();
    } finally {
      $dbh = null; //close
    }
  }
}
