<?php
/**
 * Created by PhpStorm.
 * User: acech_000
 * Date: 09/06/2015
 * Time: 12:52
 */

namespace ryred_co;


class cooldownHandler {

    private static $instance = null;

    private $cache = null;

    private function __construct()
    {

        \phpFastCache::setup("path", dirname(__FILE__));
        \phpFastCache::setup("securityKey", "cache");
        $this->cache = phpFastCache();

    }

    public static function getCooldown() {
        return (!isset( cooldownHandler::$instance ) or empty( cooldownHandler::$instance )) ? ( cooldownHandler::$instance = new cooldownHandler() ) : cooldownHandler::$instance;
    }

    public function isCooldown( $IP, $RID ) {

        if( !isset( $IP ) or empty( $IP ) ) return true;
        if( !isset( $RID ) or empty( $RID ) ) return;

        $coolDown = $this->cache->get( strtoupper( $IP ) . "_IP_COOLDOWN_" . strtoupper( $RID ) );
        return !empty($coolDown) and $coolDown;

    }

    public function cooldown( $IP, $RID ) {

        if( !isset( $IP ) or empty( $IP ) ) return;
        if( !isset( $RID ) or empty( $RID ) ) return;

        $this->cache->set( strtoupper( $IP ) . "_IP_COOLDOWN_" . strtoupper( $RID ), true, 600 );

    }

}