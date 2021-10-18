<?php

require_once '/usr/local/lib/php/os.php';
require_once "config.php";

/* usio - Single-board Input/Output througth standard GPIO Linux subsystem */
class Sbio {
    private $debug_output_states = [];

    public function relay_set_state($port, $state)
    {
        if (DISABLE_HW) {
            perror("FAKE: sbio.relay_set_state %d to %d\n", $port, $state);
            $this->debug_output_states[$port] = $state;
            return '0';
        }

        $gpio = conf_io()['outputs_gpio'][$port - 1];
        $rc = file_put_contents(sprintf("/sys/class/gpio/gpio%d/value", $gpio), "" . $state);
        if (!$rc) {
            perror("Can't write new GPIO%d state", $gpio);
            return -EBUSY;
        }
        return 0;
    }

    public function relay_get_state($port)
    {
        if (DISABLE_HW) {
            perror("FAKE: sbio.relay_get_state %d\n", $port);
            return isset($this->debug_output_states[$port]) ? $this->debug_output_states[$port] : '0';
        }

        $gpio = conf_io()['outputs_gpio'][$port - 1];
        $state = file_get_contents(sprintf("/sys/class/gpio/gpio%d/value", $gpio));
        if (!$state || (!strlen($state))) {
            perror("Can't read GPIO%d state", $gpio);
            return -EBUSY;
        }

        return (int)$state;
    }

    public function input_get_state($port)
    {
        if (DISABLE_HW) {
            perror("FAKE: sbio.input_get_state %d\n", $port);
            return '0';
        }

        $gpio = conf_io()['inputs_gpio'][$port - 1];
        $state = file_get_contents(sprintf("/sys/class/gpio/gpio%d/value", $gpio));
        if (!$state || (!strlen($state))) {
            perror("Can't read GPIO%d state", $gpio);
            return -EBUSY;
        }

        return !(int)$state;
    }

    public function wdt_reset()
    {
        $this->send_cmd("wdt_reset\n");
    }

    public function wdt_on()
    {
        $ret = $this->send_cmd("wdt_on\n");
        if ($ret == "ok")
            return 0;

        msg_log(LOG_ERR, sprintf("USIO: can't wdt on: %s\n", $ret));
        return -EBUSY;
    }

    public function wdt_off()
    {
        $ret = $this->send_cmd("wdt_off\n");
        if ($ret == "ok")
            return 0;

        msg_log(LOG_ERR, sprintf("USIO: can't wdt off : %s\n", $ret));
        return -EBUSY;
    }

    public function get_temperatures()
    {
        $result = [];
        $path = "/sys/bus/w1/devices";
        $devices = get_list_files($path);
        if (!$devices || !count($devices))
            return [];

        foreach ($devices as $device) {
            $ret = preg_match("/\d{2}-/", $device);
            if (!$ret)
                continue;
            $content = file_get_contents(sprintf("%s/%s/w1_slave", $path, $device));
            preg_match_all("/t=([\d-]+)/", $content, $matches);
            $temperature = (float)$matches[1][0] / 1000.0;
            $result[] = ['name' => $device, "temperature" => $temperature];
        }
        return $result;
    }
}


function sbio()
{
    static $sbio = NULL;
    if ($sbio)
        return $sbio;

    $sbio = new Sbio();
    return $sbio;
}

