# WOL/Shutdown Scheduler
This OCO extensions enables you to schedule computer startup via WOL (Wake On Lan) and shutdown via SSH/Windows RPC.

## Installation
0. Install the `php-ssh2` (for shutting down Linux machines via SSH) and `samba-common` (for shuttding down Windows machines using `net rpc shutdown`) package/module on your server.

1. Move this extension directory into your OCO server's `extensions` directory **or** clone this repo into a separate directory on your server and create a symlink to the extension directory inside the OCO server's `extensions` directory.

2. Import the database table schema which can be found in the `sql` directory.

3. Computers are shutted down via an OCO Agent shutdown flag: when the agent sends the next request to the server, the server responds with a shutdown flag which means that the agent should shut down. This requires at least agent version 0.15.4.

4. Ensure that the following permissions are set in your JSON role definition.
   ```
   "Models\\WolSchedule": {
        "create": true,
        "*": {
            "read": true,
            "write": true,
            "delete": true
        }
   },
   "Models\\WolPlan": {
        "create": true,
        "*": {
            "read": true,
            "write": true,
            "delete": true
        }
   },
   "Models\\WolGroup": {
       "create": true,
       "*": {
           "read": true,
           "write": true,
           "delete": true,
           "create": true
       }
   }
   ```

5. "WOL/Shutdown Scheduler" is now visible at the end of the left sidebar in the web interface.
   - You can now create a schedule and an assignment for this schedule to a computer group.
   - Note: expired schedules are automatically deleted via the housekeeping cron job.

6. Set up a cronjob executing `php console.php execplannedwolshutdown` every minute.

## Permission Notes
In order to create a new WOL/shutdown plan, the system user needs read permissions for the target computer group and schedule. It is also necessary to permit write permissions to the target WOL group.

In order to delete a WOL group it has to be empty and must contain no subgroups. Please remove all schedules, plans and subgroups first.

## Logging
The scheduler logs WOL and shutdown events into the OCO log table (user `WOL-SHUTDOWN-SCHEDULER`, actions `oco.wol_shutdown_scheduler.wol` and `oco.wol_shutdown_scheduler.shutdown`).

You can view the logs easily by creating a report:
```
SELECT timestamp, action, data FROM log WHERE action LIKE "oco.wol_shutdown_scheduler.shutdown" OR action LIKE "oco.wol_shutdown_scheduler.wol"
```
