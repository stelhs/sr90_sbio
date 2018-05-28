<?php
require_once '/usr/local/lib/php/common.php';

define("CONFIG_PATH", "/etc/sr90_sbio/");
if (is_file('DISABLE_HW'))
    define("DISABLE_HW", 1);
else
    define("DISABLE_HW", 0);



function conf_io()
{
    static $sbio_name = NULL;
    static $server_addr = NULL;

    if (!$sbio_name) {
        @$sbio_name = trim(file_get_contents(".sbio_name"));
        if (!$sbio_name)
            $sbio_name = "sbio1";
    }

    if (!$server_addr) {
        @$server_addr = trim(file_get_contents(".server_addr"));
        if (!$server_addr)
            $server_addr = "192.168.10.240";
    }

    return ['name' => $sbio_name,
            'server' => ['ip' => $server_addr,
                         'port' => 400],
            'inputs_gpio' => [8,9,25,10,24,22,23,27,17,18,3,2],
            'outputs_gpio' => [21,26,20,19,16,13,6,12,5,7,11],
            'triggers_input_ports' => [1,2,3,4,5,6,7,8,9,10,11,12],
            'outputs_ports' => [1,2,3,4,5,6,7,8,9,10,11],
            'errors_log_file' => 'errors.log'
           ];
}


function conf_valves()
{
    return [
                'borehole_cleaning' => ['name' => 'borehole_cleaning',
                                        'open_out_port' => 5,
                                        'close_out_port' => 6,
                                        'open_in_port' => 7,
                                        'close_in_port' => 8],
    ];
}
