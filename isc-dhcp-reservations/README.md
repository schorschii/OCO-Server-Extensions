# ISC DHCP Server Reservations Editor
This OCO extensions enables you to edit ISC DHCP server reservation configuration files from the OCO web interface.

**Current Version: 1.0**

## Installation
1. Copy all files from this directory into your OCO installation.

2. Set up a separate config file for the ISC DHCP server which only contains the reservation definitions and include it via `include "/etc/dhcp/reservations.conf";` in the ISC DHCP main configuration file `/etc/dhcp/dhcpd.conf`. 

3. Enter the path to this reservation file into the constant `RESERVATIONS_FILE` in `lib/lib.d/isc-dhcp-reservations.php`.

4. Allow the web server user to restart the ISC DHCP server by inserting `www-data ALL=(ALL:ALL) NOPASSWD:/usr/sbin/service isc-dhcp-server restart` into `/etc/sudoers`.

5. Allow the web server user to edit your `RESERVATIONS_FILE` via group membership.

6. "ISC DHCP Reservations" is now visible at the end of the left sidebar in the web interface.
