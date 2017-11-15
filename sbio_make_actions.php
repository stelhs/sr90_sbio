#!/usr/bin/php
<?php
require_once '/usr/local/lib/php/common.php';
require_once '/usr/local/lib/php/os.php';
require_once 'config.php';

define("MSG_LOG_LEVEL", LOG_NOTICE);

/* Calling by Sbio daemon */
function main($argv) {
    if (count($argv) < 3) {
        perror("incorrect arguments\n");
        return;
    }

    $action_port = $argv[1];
    $action_state = $argv[2];

    $content = file_get_contents(sprintf("http://%s:%d/ioserver" .
                                         "?io=%s&port=%d&state=%d",
                                         conf_io()['server']['ip'],
                                         conf_io()['server']['port'],
                                         conf_io()['name'],
                                         $action_port,
                                         $action_state));
    if (!$content) {
        perror("Returned content is empty\n");
        return -ECONNFAIL;
    }

    $ret = json_decode($content, true);
    if (!$ret) {
        perror("Can't JSON decoded returned content\n");
        return -EPARSE;
    }

    if ($ret['status'] != 'ok') {
        pnotice("Error: %s", $ret['reason']);
        return $ret['status'];
    }
    pnotice("%s\n", $ret['log']);
}


exit(main($argv));