#!/bin/bash

cd ~/git/rockparade
sudo git pull
sudo ~/composer.phar install
sudo bin/console ca:cl -e prod
sudo chown -R www-data /home/petr/git/rockparade