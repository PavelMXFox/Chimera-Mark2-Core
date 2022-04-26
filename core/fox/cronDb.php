<?php namespace fox; 
use SQLite3;

/**
 *
 * Class fox\cronDb
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

class cronDb {
    protected $dbfile = "/dev/shm/crontab.db";
    protected SQLite3 $s;
    
    public function __construct() {
        try {
            $init = false;
            $init = (!file_exists($this->dbfile));
            
            $this->s=new SQLite3($this->dbfile);
            $this->s->busyTimeout(100000);
        } catch (\Exception $e) {
            $this->__destruct();
            throw $e;
        }
        if ($init) { $this->initialize();}
    }
    
    public function __destruct() {
        $this->s->close();
    }
    
    public function initialize() {
        
        try {
            $this->s->exec("drop table if exists `tasks`");
            
            $this->s->exec("create table if not exists `tasks` (
    `pid` INTEGER PRIMARY KEY NOT NULL,
    `hash` text default null,
    `startStamp` int default 0,
    `expireStamp` int default 0,
    `method` text default null
    )");
        } catch (\Exception $e) {
            $this->__destruct();
            throw $e;
        }
    }
    
    public function getRunningTasks() {
        $r=$this->s->query("select * from `tasks`");
        if (empty($r)) {
            trigger_error("Query failed select * from `tasks`");
            return null;
        }
        $rv=[];
        while ($row = $r->fetchArray(SQLITE3_ASSOC)) {
            array_push($rv, $row);;
        }
        
        return $rv;
    }
    
    public function addTask ($pid, $hash, $method, $TTL) {
        $st=$this->s->prepare('insert into `tasks` (pid,hash,startStamp, method,expireStamp) values (:pid, :hash,:startStamp,:method,:expireStamp) on conflict(pid) do update  set hash=:hash,startStamp=:startStamp, expireStamp=:expireStamp, method=:method');
        $st->bindValue(':pid', $pid,SQLITE3_INTEGER);
        $st->bindValue(':hash', $hash,SQLITE3_TEXT);
        $st->bindValue(':startStamp', time(),SQLITE3_INTEGER);
        $st->bindValue(':expireStamp', time()+($TTL),SQLITE3_INTEGER);
        $st->bindValue(':method', $method,SQLITE3_TEXT);
        $st->execute();
    }
    
    public function delTask($pid) {
        return $this->s->exec("delete from `tasks` where `pid`='".$pid."'");
    }
    
}
?>