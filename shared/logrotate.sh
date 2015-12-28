#!/bin/sh

CONF=/etc/config/qpkg.conf
QPKG_NAME="dnsmasq"
DIR=$(/sbin/getcfg $QPKG_NAME Install_Path -d "" -f $CONF)
PIDFILE="/var/run/dnsmasq.pid"
PID=`/bin/cat $PIDFILE`
LOGDAYS="30"

# sanity check for DIR
if [ -d "$DIR" ]; then
	# create logdir if it doesn't exist
	if [ ! -d "$DIR/log" ]; then
		/bin/mkdir "$DIR/log"
	fi
	# move old log , create new log , send SIGUSR2
	/bin/mv $DIR/dnsmasq.log $DIR/log/dnsmasq.`date +%s`.log && /bin/touch $DIR/dnsmasq.log && /bin/kill -12 $PID

	# check permissions !
	/bin/chmod 666 $DIR/dnsmasq.log
	# remove log entries older than $LOGDAYS
	/usr/bin/find $DIR/log/ -mtime +$LOGDAYS | /usr/bin/xargs rm -f
fi
