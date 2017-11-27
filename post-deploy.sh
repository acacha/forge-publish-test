#!/usr/bin/env bash
echo "### git pull origin master on forge-publish"
cd /home/forge/forgepublish.sergitur.2dam.iesebre.com/forge-publish
git pull origin master
cd /home/forge/forgepublish.sergitur.2dam.iesebre.com
composer dumpautoload