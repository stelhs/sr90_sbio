#!/usr/bin/php
<?php
require_once '/usr/local/lib/php/common.php';
require_once '/usr/local/lib/php/os.php';
require_once 'common_lib.php';
require_once 'sbio_lib.php';

function main($argv)
{
    sbio_reboot();
    echo json_encode(['status' => 'ok']);
}

exit(main($argv));