# ISC DHCP Server Reservations Editor
This OCO extensions enables you to edit ISC DHCP server reservation configuration files from the OCO web interface.

**Current Version: 1.2**

## Installation
1. Copy all files from this directory into your OCO installation **or** clone this repo into a separate directory on your server and create appropriate symlinks inside the OCO application directory. This method ensures that updates can be easily applied using `git pull` without copying all new files in place.

2. Set up a separate config file for the ISC DHCP server which only contains the reservation definitions and include it via `include "/etc/dhcp/reservations.conf";` in the ISC DHCP main configuration file `/etc/dhcp/dhcpd.conf`. 

3. Insert a configuration constant `ISC_DHCP_SERVER` at the end of your global OCO config file (`conf.php`) with the path to your DHCP reservation file (from step 2) and an appropriate command to reload you DHCP server. Adding multiple (remote) servers (via SSH) is also possible. Please have a look at the example file `isc-dhcp-reservations.conf.php.example`.

4. Allow the web server user (`www-data`) to edit your DHCP reservations file (step 2), e.g. via group membership.

5. Allow the web server user to restart the ISC DHCP server by inserting `www-data ALL=(ALL:ALL) NOPASSWD:/usr/sbin/service isc-dhcp-server restart` (respectively the command you defined in step 3) into `/etc/sudoers`.

6. Ensure that the permission `"dhcp_reservation_management": true` is set in your JSON role definition (OCO database table `system_user_role`).

7. "ISC DHCP Reservations" is now visible at the end of the left sidebar in the web interface.
