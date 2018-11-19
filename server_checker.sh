#!/bin/bash

cd "$(dirname "$0")"
ping -c2 `cat .server_addr`
if [ $? -ne 0 ]
then
	reboot
fi
