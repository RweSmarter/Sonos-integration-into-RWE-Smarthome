#!/bin/sh 
# PHP Script alle 5 Sekunden automatisiert durchführen on Raspberry PI
i=1
while i=1; do
   echo script alle 5 Sekunden automatisiert durchfuehren
   cd /var/www/sonos/
   php sonos.php
   sleep 1
done
