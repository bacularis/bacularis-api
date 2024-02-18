
This is a new feature and bug fix release. We added capability to set read-only,
read-write and no access permissions per Bacula configuration resource for API
user accounts. It makes Bacularis granular permission control even more powerful
and attractive. We also adapted Bacularis for a new coming Bacula 15 release that all
Bacula version 9.6, 11.x, 13.x and now 15.x users will be able to use Bacularis in
theirs environments. Besides of that we fixed a couple of bugs reported by Community.

Changes:
 - Add Bacula resource permission settings
 - Adapt Bacularis to use with Bacula (beta) 15.0
 - Add job parameter to filesets endpoint
 - Fix #7 editing API basic users via Web
 - Fix setting resource permissions for oauth2
