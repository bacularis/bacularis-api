
This is a new feature and bug fix release. We prepared two new Bacularis plugins:
MySQL and MariaDB database backup plugins. Using them there is possible to do
the databases backup in various ways: dump backup (in three variants), binary physical
online backup, backup for Point-in-Time Recovery (PITR), file backup for crucial
database server files. This two plugin solution also introduces real incremental
and differential database backups for the dump backup method. We are very glad
that we provides these plugins for the Community. More information about
the database plugins you can find in the Bacularis documentation.

Besides new plugins, we also did some changes and small improvements in the
deployment process. At the end we fixed a couple of bugs reported by the Community.

**Changes**

 * Move API plugin base part to common module

