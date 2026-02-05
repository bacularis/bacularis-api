
We are pleased to announce the release of **Bacularis 5.11.0**. This version
introduces a number of improvements to the web interface aimed at making it
even more convenient to use, simplifying selected actions, and reducing the
number of steps required to perform them.

### Dashboard Enhancements

One of the most notable additions is the introduction of new tabs on the
 Dashboard page. These provide information about:

 * the most recently executed Bacula jobs,
 * jobs scheduled for today,
 * jobs scheduled for the next five days.

With these improvements, users can immediately see upcoming Bacula activity
right after logging into the web interface.

### Bulk Actions for Volumes

This release introduces a set of new bulk actions for the Volumes list. Users
can now perform operations on single or multiple volumes at once. Supported
bulk actions include:

 * setting volume status,
 * changing the volume pool,
 * updating data retention,
 * configuring recycle, inchanger, and enabled flags,
 * and more.

These enhancements significantly streamline volume management tasks.

### Bulk Actions for Other Resource Lists

New bulk actions have also been added to the following lists:

 * Jobs
 * Clients
 * Storage
 * Pools

All of them support performing actions on both individual items
and selected groups of items.

### Data Views for Jobs

Another long-awaited feature is the introduction of data views for the Jobs list.
This allows Bacula jobs to be organized into logical groups for easier management.
The Jobs list was one of the few remaining areas without support for data views,
and we are happy to finally bring this functionality there.

### API Updates

On the API side, we have added several new actions, particularly for the Pool and
Client endpoints. As usual, the API documentation has been updated accordingly.

### Bug Fixes

We have also implemented several fixes in the new tape storage wizard.

### Important Notice

If your Bacularis instance includes users with limited Bacula resource permissions
(using Console ACLs), and you would like them to see their scheduled Bacula jobs on
the Dashboard page, you need to add an additional CommandAcl directive to their
ConsoleACL configuration to allow the use of the status command, for example:

 CommandACL = status

If your Bacularis users do not use Console ACLs, you do not have non-administrator
users, or you do not wish to expose job schedules to users, no additional action is
 required.

For more details, please refer to the related Git commit:
https://github.com/bacularis/bacularis-web/commit/a91e8d95795aef74b2c4db24d412fc68215dc687

We wish everyone smooth installations and upgrades.

Enjoy using Bacularis!

— The Bacularis Team

### Main changes

**Bacularis API**

 * Add delete client endpoint
 * Add delete pool endpoint
 * Add new delete pool endpoint to OpenAPI specification
 * Add new delete client endpoint to OpenAPI specification
 * Add current time epoch to jobtotals result
 * Make client checking more accurate + refactor
 * Make pool checking more accurate + refactor
 * Update delete client endpoint description and error codes in OpenAPI specification
 * Update delete pool endpoint description and error codes in OpenAPI specification
 * Update OpenAPI specification

