#!/bin/sh

CONF=/etc/config/qpkg.conf
QPKG_NAME="dnsmasq"
DIR=$(/sbin/getcfg $QPKG_NAME Install_Path -d "" -f $CONF)
CRONTAB="/etc/config/crontab"
CRONJOB="50 23 * * * $DIR/logrotate.sh"
PIDFILE="/var/run/dnsmasq.pid"
# limit the interface to eth0
OPTIONS="-k -C $DIR/dnsmasq.conf \
			--dhcp-hostsfile=$DIR/dnsmasq_dhcphosts.conf \
			--addn-hosts=$DIR/dnsmasq_hostmap.conf \
			--dhcp-leasefile=$DIR/dnsmasq.leases \
			--pid-file=$PIDFILE \
			--log-facility=$DIR/dnsmasq.log \
			"
output () {
	MSG="$@"
	# send message to web gui for user to see
	/sbin/notice_log_tool -a "$MSG"
	# log to system log
	/sbin/log_tool -a "$MSG"
	/bin/echo $MSG
}

#output "STARTING with argument: $@"

case "$1" in
	start)
		if [ -d "$DIR" ]; then
			if [ -z "`/bin/pidof $QPKG_NAME`" ]; then
				
				# CRON
				# ensure that the cronjob is installed into crontab
				# every reboot clears out crontab
				if [ ! -n "`/bin/grep -i dnsmasq $CRONTAB`" ]; then
					/bin/echo "$CRONJOB" >> $CRONTAB
					/usr/bin/crontab $CRONTAB
				fi

				output "Starting $QPKG_NAME"
				# Run executable with options
				output $DIR/$QPKG_NAME $OPTIONS
				PID=`$DIR/$QPKG_NAME $OPTIONS > /dev/null 2>&1 & /bin/echo $!`

				if [ -z "$PID" ]; then
					output "$QPKG_NAME FAILED TO START!"
				else
					output "$QPKG_NAME [$PID] is now running."
					/sbin/setcfg $QPKG_NAME Enable TRUE -f $CONF
					
				fi
			else
				output "$QPKG_NAME process still running"
			fi
		else
			output "$DIR is invalid"
		fi
	;;

	stop)
		if [ -f $PIDFILE ]; then

			PID=`cat $PIDFILE`

			if [ -z "$PID" ]; then
				output "PIDFILE at $PIDFILE is invalid, no PID therin. No action taken."
				exit 1
			fi

			if [ -z "`/bin/ps | /bin/grep ${PID}`" ]; then
				output "[$PID] $QPKG_NAME was not found. Nothing to do."
			else
				output "Stopping [$PID] $QPKG_NAME"
				/bin/kill -SIGTERM $PID
				/bin/rm -f $PIDFILE
				/sbin/setcfg $QPKG_NAME Enable FALSE -f $CONF
			fi

		else
			output "PIDFILE NOT FOUND AT LOCATION ($PIDFILE)"
		fi
	;;

	restart)
		$0 stop
		sleep 3
		$0 start
	;;

	*)
		/bin/echo "Usage: $0 {start|stop|restart}"
		exit 1
	;;
esac

exit 0
