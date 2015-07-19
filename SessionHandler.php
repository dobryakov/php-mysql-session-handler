<?php

/**

A PHP session handler to keep session data within a MySQL database.

CREATE TABLE `session_handler_table` (
    `id` varchar(255) NOT NULL,
    `data` mediumtext NOT NULL,
    `timestamp` int(255) NOT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

*/

class SessionHandler{

  protected $dbConnection;
  protected $dbTable;

  public function setDbDetails($dbHost, $dbUser, $dbPassword, $dbDatabase, $dbTable){
    $this->dbTable = $dbTable;
    $this->dbConnection = mysql_connect($dbHost, $dbUser, $dbPassword);
    mysql_select_db($dbDatabase, $this->dbConnection);
  }

  protected function query($sql) {
    return mysql_query($sql, $this->dbConnection);
  }

  public function open() {
    //delete old session handlers
    $limit = time() - (60 * 60 * 24);
    $sql = sprintf("DELETE FROM %s WHERE timestamp < %s", $this->dbTable, $limit);
    return $this->query($sql);
  }

  public function close() {
    return mysql_close($this->dbConnection);
  }

  public function escape_string($s) {
    return mysql_escape_string($s);
  }

  public function read($id) {
    $sql = sprintf("SELECT data FROM %s WHERE id = '%s' LIMIT 1", $this->dbTable, $this->escape_string($id));
    if ($result = $this->query($sql)) {
        return mysql_fetch_assoc($result);
    }
  }
  
  public function write($id, $data) {
    $sql = sprintf("REPLACE INTO %s VALUES('%s', '%s', '%s')",
		   $this->dbTable, 
                   $this->escape_string($id),
                   $this->escape_string($data),
                   time());
    return $this->query($sql);
  }

  public function destroy($id) {
    $sql = sprintf("DELETE FROM %s WHERE `id` = '%s'", $this->dbTable, $this->escape_string($id));
    return $this->query($sql);
  }

  public function gc($max) {
    $sql = sprintf("DELETE FROM %s WHERE `timestamp` < '%s'", $this->dbTable, time() - intval($max));
    return $this->query($sql);
  }

}

$session = new SessionHandler();

$session->setDbDetails('localhost', 'login', 'password', 'database', 'table');

session_set_save_handler(array($session, 'open'),
                         array($session, 'close'),
                         array($session, 'read'),
                         array($session, 'write'),
                         array($session, 'destroy'),
                         array($session, 'gc'));

register_shutdown_function('session_write_close');

session_start();

