<?php
/**
 * Created by PhpStorm.
 * User: acech_000
 * Date: 05/06/2015
 * Time: 17:36
 */

namespace ryred_co;

require_once( __DIR__ . "/vendor/autoload.php" );
require_once( "cooldownHandler.php" );

use \phpFastCache;

class SQLChecker {

    private $host, $user, $password, $database;

    private $link = null;

    private $cache = null;

    public function __construct( $host, $user, $password, $database )
    {

        \phpFastCache::setup("path", dirname(__FILE__));
        \phpFastCache::setup("securityKey", "cache");
        $this->cache = \phpFastCache();

        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;

    }

    public function getDatabase() {

        if( !empty($this->link) and $this->link->ping() )
            return $this->link;

        return ($this->link = $this->makeDatabase());

    }

    private function makeDatabase()
    {

        $db_link = mysqli_connect(
                $this->host,
                $this->user,
                $this->password,
                $this->database
            );

        if( !$db_link )
            throw new \ErrorException(
                    "Unable to connect to the database! " .
                    mysqli_error($db_link)
                );

        $res = $db_link->query(
            "CREATE TABLE IF NOT EXISTS `Servers` (
              `UUID` varchar(50) NOT NULL,
              `UID` int(15) NOT NULL,
              `RID` int(15) NOT NULL,
              `NONCE` varchar(500) NOT NULL,
              `SERVERS` text NOT NULL,
              `LAST_SCANNED` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY `NONCE` (`NONCE`),
              UNIQUE KEY `UUID` (`UUID`),
              KEY `UID` (`UID`,`RID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
        );

        if( !$res )
            throw new \ErrorException(
                    "An error occurred whilst creating the table.. " .
                    mysqli_error($db_link)
                );

        return $db_link;

    }

    public function hello( $UID, $RID, $NONCE, $IP, $port = null ) {

        if( empty( $UID ) or empty( $RID ) or empty( $NONCE ) or empty($IP) )
            return array( "error" => true, "message" => "A field was null.." );

        $UUID = $UID . "|" . $RID;

        $cooldownHandler = cooldownHandler::getCooldown();

        if( $cooldownHandler->isCooldown( $IP, $UUID ) )
            return array( "error" => true, "message" => "IP Address in cooldown." );

        $cooldownHandler->cooldown( $IP, $UUID );

        $ip_string = $IP;
        if( !empty( $port ) ) $ip_string = $ip_string . ":" . $port;
        $ip_string = $ip_string . ", ";

        // Check if the IP is already in the list..
        $stmnt1 = $this->getDatabase()->prepare("SELECT *
          FROM `Servers`
         WHERE `UUID`=? AND `NONCE`=? AND INSTR(`SERVERS`, ?) > 0");

        $stmnt1->bind_param( "sss", $UUID, $NONCE, $ip_string );

        if($stmnt1->execute()) {
            $result = $stmnt1->get_result();
            if( $result && $result->num_rows > 0 )  {
                return array( "error" => false, "message" => "All well and good - thanks gov!" );
            }
        }

        // Update/insert.
        $stmnt = $this->getDatabase()->prepare(
            "INSERT INTO `Servers`
              (`UUID`, `UID`, `RID`, `NONCE`, `SERVERS`, `LAST_SCANNED`)
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
              ON DUPLICATE KEY UPDATE `SERVERS` = concat(`SERVERS`, VALUES(`SERVERS`));"
        );

    $stmnt->bind_param("siiss", $UUID, $UID, $RID, $NONCE, $ip_string);
        $res = $stmnt->execute();
        if( !$res or $stmnt->affected_rows < 1 )
            return array( "error" => true, "message" => "The database didn't work right?\n" . $this->link->error );

        return array( "error" => false, "message" => "All well and good - thanks gov!" );

    }

}