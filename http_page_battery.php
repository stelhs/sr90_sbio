#!/usr/bin/php
<?php
require_once '/usr/local/lib/php/common.php';
require_once '/usr/local/lib/php/os.php';
require_once 'common_lib.php';
require_once 'sbio_lib.php';


function main($argv)
{
    $step = 12.93/3089;
    $status = 'ok';
    $error_msg = '';


    $f = fopen('/dev/ttyUSB0', 'r');
    if (!$f) {
        echo json_encode(['status' => 'error',
                          'error_msg' => 'ttyUSB not opened']);
        return 0;
    }

    while (!feof($f)) {
        $line = fgets($f);
        $rc = preg_match('CH3:([0-9]+)', $line, $matches);
        if (!$rc)
            continue;
        $voltage = $matches[1] * $step;
        break;
    }
    fclose($f);
    $current = 0;

    echo json_encode(['voltage' => $voltage,
                      'current' => $current,
                      'status' => $status,
                      'error_msg' => $error_msg]);
    return 0;
}

exit(main($argv));