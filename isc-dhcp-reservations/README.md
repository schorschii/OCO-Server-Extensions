# ISC DHCP Server Reservations Editor
This OCO extensions enables you to edit ISC DHCP server reservation configuration files from within the OCO web interface.

## Installation
0. Install the `php-ssh2` package/module on your server.

1. Move this extension directory into your OCO server's `extensions` directory **or** clone this repo into a separate directory on your server and create a symlink to the extension directory inside the OCO server's `extensions` directory.

2. Set up a separate ISC DHCP config file for the DHCP server which only contains the reservation definitions and include it via `include "/etc/dhcp/reservations.conf";` in the ISC DHCP main configuration file `/etc/dhcp/dhcpd.conf`.

3. Insert a configuration constant `ISC_DHCP_SERVER` at the end of your global OCO config file (`conf.php`) with the path to your DHCP reservation file (from step 2) and an appropriate command to reload you DHCP server. Adding multiple (remote) servers (via SSH) is also possible. Please have a look at the example file `isc-dhcp-reservations.conf.php.example`.

4. Allow the web server user (`www-data`) to edit your DHCP reservations file (step 2), e.g. via group membership.

5. Allow the web server user to restart the ISC DHCP server by inserting `www-data ALL=(ALL:ALL) NOPASSWD:/usr/sbin/service isc-dhcp-server restart` (respectively the command you defined in step 3) into `/etc/sudoers`.

6. Add the following permissions to your system users JSON role definition.
   ```
   "Models\\IscDhcpServer": {
        "*": {             <-- allow reading reservations of all servers in config array
            "read": true
        },
        "localhost": {     <-- allow writing reservations of server "localhost"
            "write": true
        }
    },
    ```

7. "ISC DHCP Reservations" is now visible at the end of the left sidebar in the web interface (if at least one server is defined in `ISC_DHCP_SERVER` and you have permission to it).
