<?php

require_once 'config.php';


function sbio_reboot_async()
{
    // TODO: notify server
    if(DISABLE_HW)
        return;
    run_cmd('sleep 1;reboot', true);
}


function get_global_status()
{
    $ret = run_cmd('uptime');
    preg_match('/up (.+),/U', $ret['log'], $mathes);
    $uptime = $mathes[1];

    return ['uptime' => $uptime];
}


