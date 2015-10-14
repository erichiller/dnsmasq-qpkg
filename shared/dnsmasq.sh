#!/bin/sh

CONF=/etc/config/qpkg.conf
QPKG_NAME="dnsmasq"
DIR=$(/sbin/getcfg $QPKG_NAME Install_Path -d "" -f $CONF)
export dnsmasqdir=$DIR
PIDFILE="/var/run/dnsmasq.pid"
# limit the interface to eth0
OPTIONS="-k -i eth0 -C $DIR/dnsmasq.conf \
			--dhcp-leasefile=$DIR/dnsmasq.leases \
			--addn-hosts=$DIR/dnsmasq_hostmap.conf \
			--dhcp-hostsfile=$DIR/dnsmasq_dhcphosts.conf \
			--pid-file=$PIDFILE \
			--log-facility=$DIR/dnsmasq.log \
			"

output () {
	MSG="[`caller`] $@"
	/sbin/log_tool -a "$MSG"
	/bin/echo $MSG
}

fix_x86_64_libs () {
	if [ -z "`grep x86_64 /etc/ld.so.conf`" ]; then
		output "adding x86_64 libs"
		echo "/share/CACHEDEV1_DATA/.qpkg/HD_Station/lib/x86_64-linux-gnu/" >> /etc/ld.so.conf
		/sbin/ldconfig
	fi
}

output "STARTING: $@"

case "$1" in
  start)
#    ENABLED=$(/sbin/getcfg $QPKG_NAME Enable -u -d FALSE -f $CONF)
#    if [ "$ENABLED" != "TRUE" ]; then
#        output "$QPKG_NAME is disabled."
#        exit 1
#    fi
    
    if [ -d "$DIR" ]; then
	   	if [ -z "`/bin/pidof $QPKG_NAME`" ]; then

	   		output "Starting $QPKG_NAME"
   			# Run executable with options
   			fix_x86_64_libs
			output $DIR/$QPKG_NAME $OPTIONS
			PID=`$DIR/$QPKG_NAME $OPTIONS > /dev/null 2>&1 & /bin/echo $!`

	   		if [ -z "$PID" ]; then
    			output "$QPKG_NAME FAILED TO START!"
	 	  	else
		   		output "$QPKG_NAME is now running."
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
esac


exit 0
