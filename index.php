<?php

    require_once( "SQLChecker.php" );

    $checker = new \ryred_co\SQLChecker("localhost", "ryred_co", "Password123", "ryred_co");

    $port = $_REQUEST['PORT'];
    $RID = $_REQUEST['RID'];
    $UID = $_REQUEST['UID'];
    $NONCE = $_REQUEST['NONCE'];

    $ip = $_SERVER['REMOTE_ADDR'];

    header('Content-Type: application/json');

    $res = $checker->hello($UID, $RID, $NONCE, $port);
    if( $res['error'] && $res['message'] === "A field was null.." )
        header('Location: http://ryred.co/');

    echo( json_encode( $res ) );