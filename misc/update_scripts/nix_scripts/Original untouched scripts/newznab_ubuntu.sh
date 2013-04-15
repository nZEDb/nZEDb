# ubuntu "init style" script for newznab binaries updater
# would be placed in /etc/init/newznab_binup.conf
# DO NOT RUN until you CHANGE PATH and UPDATE FILE to correct values

start on runlevel [2345]
stop on shutdown
respawn

exec sh -c "while true;do cd /PATH/TO/misc/update_scripts/ && php UPDATE_BINARIES_FILE.php; sleep 600 ;done