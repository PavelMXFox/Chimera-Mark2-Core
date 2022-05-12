#!/usr/bin/php
<?php 

/**
 *
 * Script cron.php
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

if (php_sapi_name() != 'cli') {
    throw new Exception("This script can be run via CLI only");
}

use fox\moduleInfo;
use fox\cronDb;
use fox\sql;

require_once(getenv("FOX_WEBROOT")."/Autoloader.php");
$stamp=time();
$minutes = (floor($stamp/60));
$lcdate=date("Y-m-d H:i", $stamp);
$gmdate=gmdate("Y-m-d H:i", $stamp);
    
$modules = moduleInfo::getAll();

$pids=[];
$fork=false;

$crontab=[];
$db=new cronDb();
foreach ($runningTasks=$db->getRunningTasks() as $task) {
    
    if ($task["expireStamp"] < time()) {
        print "Task #".$task["pid"]." for ".$task["method"]." (".substr($task["hash"],0,8).") expired. Kill it.\n";
        if (posix_kill($task["pid"], SIGKILL) ===false) {
            $db->delTask($task["pid"]);
        }
    }
}

foreach ($modules as $mod) {
    if (array_search("cron",$mod->features)!==false) {
        $modClass=$mod->name."\module";
        if (property_exists($modClass, "crontab")) {
            foreach($modClass::$crontab as $cronItem) {
                
                $conditionMatch=false;
                if (!empty($cronItem["period"])) {
                    $conditionMatch=(fmod($minutes, $cronItem["period"])==0);
                } elseif (!empty($cronItem["regexp"])) {
                    if (!empty($cronItem["useLocalTZ"]) && $cronItem["useLocalTZ"]==true) {
                        $conditionMatch= (preg_match("/".$regexp."/", $lcdate));
                    } else {
                        $conditionMatch= (preg_match("/".$regexp."/", $gmdate));
                    }
                    
                }
                
                if ($conditionMatch) {
                    if (!empty($cronItem["TTL"])) {
                        $ttl=$cronItem["TTL"];
                    } else if (!empty($cronItem["period"])) {
                        $ttl=$cronItem["period"]*30;
                    } else {
                        $ttl=3600;
                    }

                    $cti=[
                        "instance"=>$mod->name,
                        "callback"=>$mod->namespace."\\".$cronItem["method"],
                        "args"=>array_key_exists("args", $cronItem)?$cronItem["args"]:null,
                        "TTL"=>$ttl,
                    ];
                    
                    $cti["hash"]=md5(json_encode($cti));
                    
                    $skipTask=false;
                    if (empty($cronItem["single"]) || $cronItem["single"]==true) {
                        foreach ($runningTasks as $task) {
                            if ($task["hash"]==$cti["hash"]) {
                                $skipTask=true;
                            }
                        }
                    }
                    
                    if (!$skipTask) {
                        $crontab[]=$cti;
                    }
                        
                }
            }
        }
    }
}


foreach ($crontab as $cronItem) {
    $pid=pcntl_fork();
    if ($pid == -1) {
        die('could not fork');
    } else if ($pid) {
        print("Process #".$pid." started for callback ".$cronItem["callback"]." (".substr($cronItem["hash"],0,8).") with TTL ".$cronItem["TTL"]."\n");
        
        $pids[$pid]=$cronItem["TTL"];
        $db->addTask($pid, $cronItem["hash"], $cronItem["callback"],$cronItem["TTL"]);
        
    } else {
        $fork=true;
        sql::flushConnections();
        call_user_func($cronItem["callback"],$cronItem["instance"], $cronItem["args"]);
        exit;
        break;
    }
    
}

if ($fork) { exit; }

$ttlc=0;

while(($xpid=pcntl_wait($status,WNOHANG)) >=0) {
    if ($xpid >0) {
        unset($pids[$xpid]);
        print "Process #".$xpid." Completed\n";
        $db->delTask($xpid);
    }
    
    foreach($pids as $pid=>&$ttl) {
        if ($ttl < $ttlc) {
            print "Kill PID ".$pid." by TTL\n";
            posix_kill($pid, SIGKILL);
            $ttl+=5;
        }
    }
    sleep(1);
    $ttlc++;
            
}


?>