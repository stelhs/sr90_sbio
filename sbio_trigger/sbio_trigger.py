#!/usr/bin/python3.5m

import sys
import pigpio
import subprocess
import time

script_name = None

class input_port:
  def __init__(self, pi, id, gpio_num):
    self.gpio_num = gpio_num
    self.id = id
    print("init gpio %d" % gpio_num)
    pi.set_mode(gpio_num, pigpio.INPUT)
    pi.set_pull_up_down(gpio_num, pigpio.PUD_UP)
    pi.set_glitch_filter(gpio_num, 300)
    self.cb = pi.callback(gpio_num, pigpio.EITHER_EDGE, self.gpio_triggered)

  def gpio_triggered(self, gpio_num, state, time):
    global script_name
    print (self.id, state)
    if not script_name:
      return

    try:
      subprocess.check_output([script_name, str(self.id), str(int(not state))])
    except subprocess.CalledProcessError as e:
      print (e.output)

  def __del__(self):
    print("del gpio_num: %d" % gpio_num)
    self.cb.cancel()
    del cb

def main(argv):
  global script_name
  if len(argv) < 2:
    print("first argument must be list of triggered GPIO numbers")
    return -1

  if len(argv) > 2:
    script_name = argv[2] 

  pi = pigpio.pi()

  inputs = []
  id = 1
  gpio_list = argv[1].split(',')
  for gpio in gpio_list:
    gpio_num = int(gpio)
    if not gpio_num:
      continue

    inputs.append(input_port(pi, id, gpio_num))
    id += 1

  while True:
    time.sleep(1)

exit(main(sys.argv))
