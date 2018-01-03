#!/usr/bin/php
<?php
require_once '/usr/local/lib/php/common.php';
require_once '/usr/local/lib/php/os.php';
require_once '/usr/local/lib/php/database.php';

require_once 'config.php';
require_once 'sbio_lib.php';
$utility_name = $argv[0];

function print_help()
{
    global $utility_name;
    perror("Usage: $utility_name <command> <args>\n" .
             "\tcommands:\n" .
                 "\t\t relay_set: set relay output state. Args: <port_num>\n" .
                 "\t\t\texample: $utility_name relay_set 4 1\n" .
                 "\t\t relay_get: get relay state. Args: <port_num>\n" .
                 "\t\t\texample: $utility_name relay_get 3\n" .
                 "\t\t input: get input state. Args: <port_num>\n" .
                 "\t\t\texample: $utility_name input 3\n" .
                 "\t\t make_action: Generate I/O action. Args: <port_num> <port_state>\n" .
                 "\t\t\texample: $utility_name make_action 2 0\n" .
                 "\t\t gpio_init: Initialize all needed GPIOs\n" .
                 "\t\t\texample: $utility_name gpio_init\n" .

             "\n\n");
}



function main($argv)
{
    if (!isset($argv[1]))
        return -EINVAL;

    $cmd = $argv[1];

    switch ($cmd) {
    case 'relay_set':
        if (!isset($argv[3])) {
            perror("Invalid arguments: command arguments is not set\n");
            return -EINVAL;
        }

        $port = $argv[2];
        $state = $argv[3];

        if ($port < 1 || $port > 11) {
            perror("Invalid arguments: port is not correct. port > 0 and port <= 11\n");
            return -EINVAL;
        }

        if ($state < 0 || $state > 1) {
            perror("Invalid arguments: state is not correct. state may be 0 or 1\n");
            return -EINVAL;
        }

        $rc = sbio()->relay_set_state($port, $state);
        if ($rc < 0) {
            perror("Can't set relay state\n");
        }
        return 0;

    case 'relay_get':
        if (!isset($argv[2])) {
            perror("Invalid arguments: command arguments is not set\n");
            return -EINVAL;
        }

        $port = $argv[2];

        if ($port < 1 || $port > 11) {
            perror("Invalid arguments: port is not correct. port > 0 and port <= 11\n");
            return -EINVAL;
        }

        $rc = sbio()->relay_get_state($port);
        if ($rc < 0) {
            perror("Can't get relay state\n");
        }
        perror("Relay port %d = %d\n", $port, $rc);
        return 0;

    case 'input':
        if (!isset($argv[2])) {
            perror("Invalid arguments: command arguments is not set\n");
            return -EINVAL;
        }

        $port = $argv[2];

        if ($port < 1 || $port > 12) {
            perror("Invalid arguments: port is not correct. port > 0 and port <= 12\n");
            return -EINVAL;
        }

        $rc = sbio()->input_get_state($port);
        if ($rc < 0) {
            perror("Can't get input state\n");
        }
        perror("Input port %d = %d\n", $port, $rc);
        return 0;

    case 'make_action':
        $port = $argv[2];
        $state = $argv[3];

        if ($port < 1 || $port > 10) {
            perror("port number must be in interval [1:12]\n");
            return -EINVAL;
        }

        if ($state < 0 || $state > 1) {
            perror("state must be 0 or 1\n");
            return -EINVAL;
        }

        $content = file_get_contents(sprintf("http://%s:%s/ioserver" .
                                             "?io=%s&port=%d&state=%d",
                                             conf_io()['server']['ip'],
                                             conf_io()['server']['port'],
                                             conf_io()['name'],
                                             $port,
                                             $state));
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
        return 0;

    case 'gpio_init':
        foreach (conf_io()['outputs_gpio'] as $gpio) {
            file_put_contents("/sys/class/gpio/export", $gpio);
            file_put_contents(sprintf("/sys/class/gpio/gpio%d/direction", $gpio), "out");
            file_put_contents(sprintf("/sys/class/gpio/gpio%d/value", $gpio), "0");
        }

        foreach (conf_io()['inputs_gpio'] as $gpio) {
            file_put_contents("/sys/class/gpio/export", $gpio);
            file_put_contents(sprintf("/sys/class/gpio/gpio%d/direction", $gpio), "in");
            file_put_contents(sprintf("/sys/class/gpio/gpio%d/edge", $gpio), "both");
        }
        pnotice("GPIOs init - ok\n");
        return 0;

    case 'gpio_triggered_inputs':
        $gpio_list = [];
        foreach (conf_io()['triggers_input_ports'] as $port)
            $gpio_list[] = conf_io()['inputs_gpio'][$port - 1];

        echo array_to_string($gpio_list, ',');
        return 0;

    case 'gpio_outputs':
        $gpio_list = [];
        foreach (conf_io()['outputs_ports'] as $port)
            $gpio_list[] = conf_io()['outputs_gpio'][$port - 1];

        echo array_to_string($gpio_list, ',');
        return 0;
    }
}


$rc = main($argv);
if ($rc) {
    print_help();
    exit($rc);
}
