#!/usr/bin/php
<?php
require_once '/usr/local/lib/php/common.php';
require_once '/usr/local/lib/php/os.php';
require_once 'common_lib.php';
require_once 'sbio_lib.php';


function main($argv)
{
    $voltage_step = 12.91 / 3089;
    $current_step = 3.5 / 820;
    $status = 'ok';
    $error_msg = '';


    @$f = fopen('/dev/ttyUSB0', 'r');
    if (!$f) {
        echo json_encode(['status' => 'error',
                          'error_msg' => 'ttyUSB not opened']);
        return 0;
    }

    $cnt = 0;
    $voltage_detected = false;
    $current_detected = false;
    while (!feof($f)) {
        $cnt ++;
        if ($cnt > 50) {
            $voltage = 0;
            $status = 'error';
            $error_msg = 'Can`t retrieve Channel 3 from ADC';
            break;
        }
        $line = fgets($f);
        $rc = preg_match('/CH3:([0-9]+)/', $line, $matches);
        if ($rc) {
            $voltage = round($matches[1] * $voltage_step, 2);
            $voltage_detected = true;
        }

        $rc = preg_match('/CH1:([0-9]+)/', $line, $matches);
        if ($rc) {
            $val = $matches[1] - 3000; // 3197; // 3225 - Current 0A
            $current = round($val * $current_step, 2);
            $current_detected = true;
        }

        if ($voltage_detected && $current_detected)
            break;
    }
    fclose($f);

    echo json_encode(['voltage' => $voltage,
                      'current' => $current,
                      'status' => $status,
                      'error_msg' => $error_msg]);
    return 0;
}

exit(main($argv));
