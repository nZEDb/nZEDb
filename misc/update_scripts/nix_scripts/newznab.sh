#!/bin/sh
# do not forget to change NEWZNAB.. vars =)

set -e

export NEWZNAB_PATH="/usr/local/www/newznab/misc/update_scripts"
export NEWZNAB_BINUP="update_binaries.php"
export NEWZNAB_RELUP="update_releases.php"
export NEWZNAB_SLEEP_TIME="600" # in seconds . 10sec is good for 100s of groups. 600sec might be a good start for fewer.
export NEWZNAB_PID_PATH="/var/run/" # don't forget the trailing slash . need r/w on it


export PATH="${PATH}:/usr/sbin:/usr/bin:/usr/games:/usr/local/sbin:/usr/local/bin"
PIDFILE="newznab_binup.pid"

case "$1" in
  start)
	[ -f ${NEWZNAB_PID_PATH}${PIDFILE} ] && { echo "$0 is already running."; false; }
        echo -n "Starting Newznab binaries update"
        cd ${NEWZNAB_PATH}
        (while (true);do cd ${NEWZNAB_PATH} && php ${NEWZNAB_BINUP}  2>&1 > /dev/null && php ${NEWZNAB_RELUP}  2>&1 > /dev/null ; sleep ${NEWZNAB_SLEEP_TIME} ;done) &
        PID=`echo $!`
        echo $PID > ${NEWZNAB_PID_PATH}${PIDFILE}
        ;;
  stop)
        echo -n "Stopping Newznab binaries update"
        kill -9 `cat ${NEWZNAB_PID_PATH}${PIDFILE}`
        ;;

  *)
        echo "Usage: $0 [start|stop]"
        exit 1
esac