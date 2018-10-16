#!/usr/bin/php
<?php
require_once '/usr/local/lib/php/common.php';
require_once '/usr/local/lib/php/os.php';
require_once 'common_lib.php';
require_once 'sbio_lib.php';

function main($argv)
{
    $status = 'ok';
    $error_msg = '';

    $trigger_err = [];
    @$trigger_log = file_get_contents(conf_io()['errors_log_file']);
    if ($trigger_log !== FALSE && $trigger_log) {
        unlink(conf_io()['errors_log_file']);
        $trigger_err = [];
        $rows = string_to_rows($trigger_log);
        foreach ($rows as $row) {
            $cols = split_string_by_separators($row, ":");
            if (!$cols || !count($cols))
                continue;

            $time = $cols[0];
            $log = strchr($row, ':');
            $trigger_err[$time] = $log;
        }
    }

    $pids = get_pid_list_by_command("/usr/bin/python3.5m");
    if (!isset($pids[0])) {
        $error_msg = "Trigger not started\n";
        $status = 'error';
    }

    $ret = run_cmd('uptime');
    preg_match('/up (.+),/U', $ret['log'], $mathes);
    $uptime = trim($mathes[1]);

    echo json_encode(['trigger_log' => $trigger_err,
                      'termo_sensors' => sbio()->get_temperatures(),
                      'status' => $status,
                      'error_msg' => $error_msg,
                      'uptime' => $uptime]);
    return 0;
}

exit(main($argv));
