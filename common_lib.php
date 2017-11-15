<?php

require_once 'config.php';


function sbio_reboot($method, $user_id = NULL)
{
    // TODO: notify server
    if(DISABLE_HW)
        return;
    run_cmd('halt');
    for(;;);
}


function get_global_status()
{
    $ret = run_cmd('uptime');
    preg_match('/up (.+),/U', $ret['log'], $mathes);
    $uptime = $mathes[1];

    return ['uptime' => $uptime];
}


