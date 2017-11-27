cd /home/forge/forgepublish.sergitur.2dam.iesebre.com
git pull origin master
cd /home/forge/forgepublish.sergitur.2dam.iesebre.com/forge-publish
git pull origin master
cd /home/forge/forgepublish.sergitur.2dam.iesebre.com
composer dumpautoload
echo "" | sudo -S service php7.1-fpm reload