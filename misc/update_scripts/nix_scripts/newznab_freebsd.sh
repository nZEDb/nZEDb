#!/bin/sh
#
# $FreeBSD: newznabbinup.sh.in,v0.0 2010/06/11 00:00:00 $
#
# PROVIDE: newznabbinup
# REQUIRE: DAEMON
# KEYWORD: shutdown
#
# Add the fellowing line to /etc/rc.conf to enable newznab binaries updater:
#
# newznabbinup_enable (bool):       Set to "YES" to enable.
#                                   Default is "NO".
#


. /etc/rc.subr

NEWZNAB_PATH="/usr/local/www/newznab/misc/update_scripts"
NEWZNAB_BINUP="update_binaries.php"
NEWZNAB_SLEEP_TIME="600" # in seconds . 10sec is good for 100s of groups. 600sec might be a good start for fewer.
NEWZNAB_PID_PATH="/var/run/" # don't forget the trailing slash . need r/w on it

rcvar=newznabbinup_enable
start_cmd="newznabbinup_start"
stop_cmd="newznabbinup_stop"

newznabbinup_start()
{
	[ -f ${NEWZNAB_PID_PATH}${PIDFILE} ] && { echo "$0 is already running."; false; }
        echo -n "Starting Newznab binaries update"
        cd ${NEWZNAB_PATH}
        (while (true);do cd ${NEWZNAB_PATH} && php ${NEWZNAB_BINUP} ; sleep ${SLEEP_TIME} ;done) &
        PID=`echo $!`
        echo $PID > ${NEWZNAB_PID_PATH}${PIDFILE}
}

newznabbinup_stop()
{
        echo -n "Stopping Newznab binaries update"
        kill -9 `cat ${NEWZNAB_PID_PATH}${PIDFILE}`
}

: ${newznabbinup_enable="NO"}

run_rc_command "$1"