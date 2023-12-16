
This is a minor bug fix and new function release. Main changes are adding
previous/next jobids to the job output and changes in the SQL queries that
use prepared statements.

From the bug fixes side we fixed the API specification and the volume use
duration parameter settings.

Changes:

 - Add prev jobid and next jobid to job object properties
 - Stop disabling emulated prepared statements for PHP >= 8.1
 - Add optional suffix parameter to SQL prepared statement parameters
 - Fix #5 adapt API doc to OpenAPI 3.0 specification
 - Fix volume use duration property settings
 - Fix prev/next job property if used restricted account with console ACLs
 - Remove manual double starting PHP session
 - Remove no longer needed action step
 - Update php-cs-fixer config
