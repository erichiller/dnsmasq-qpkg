# Release v0.1f - 28 Dec 2015

## Addition
* log rotate
* update dnsmasq.sh to notify user of status; ie. statup / config settings change / fail
* Add confirmation that your changes took upon clicking submit in config, leases, hosts; and server restarted
* Added ARM build arm-x09 (please test for me!)
* Added ARM build arm-x19 (please test for me!)
* Administration Tab - View process running status / Live Refresh on tab view
* Administration Tab - Start & Stop Process with result / confirmation
* Administration Tab - Download ZIP backup configuration
* Administration Tab - Upload ZIP backup config, Restore & Reboot dnsmasq
* Removed jQuery form plugin dependency
* Configurations now staged and tested before being commited and dnsmasq rebooted
* Test for presence of IPV6
* Can now configure what interface dnsmasq is bound to
* Can Enable/Disabled DHCP server
* Better process restart checking on config change & dnsmasq reboot
* Added ability to configure IPv6 DHCP range
* Cleaned up alerts / do not use javascript `alert()`

## BugFix
* reverse log listing -- newest @ Top
* no <tr> for blank lines in the log file
* proper parsing of IPv6 lease log entries on web interface
* Current Leases should read "Expiring" not "assigned" for Time
* Fix lease time // adjust for mismatched timezone in web ui
* Following from timezone correction - correct lease expiration time now shown
* removed confusing prepended scriptname output in message
* Explain that you have to click "Enter" in the tables for leases, hosts for the changes to take
* Fixed Bug in WebUI that crashed QPKG main config read in certain instances.
* Fixed bug in uninstall
* Fixed several bugs in installation

# Information
See: <http://hiller.pro/dnsmasq-qpkg/>

