#!/usr/bin/php
<?php
require_once '/usr/local/lib/php/common.php';
require_once '/usr/local/lib/php/os.php';
require_once 'config.php';

define("MSG_LOG_LEVEL", LOG_NOTICE);

/* Calling by Sbio daemon */
function main($argv) {
    chdir(dirname($argv[0]));
    if (count($argv) < 3) {
        perror("incorrect arguments\n");
        return;
    }

    $action_time = time();
    $action_port = $argv[1];
    $action_state = $argv[2];

    $query = sprintf("http://%s:%d/ioserver" .
                     "?io=%s&port=%d&state=%d",
                     conf_io()['server']['ip'],
                     conf_io()['server']['port'],
                     conf_io()['name'],
                     $action_port,
                     $action_state);

    $content = file_get_contents($query);
    if ($content === FALSE || !$content) {
        file_put_contents(conf_io()['errors_log_file'],
                          sprintf("%s: Can't send IO action: port %d stay to %d\n",
                                  $action_time, $action_port, $action_state),
                          FILE_APPEND);
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