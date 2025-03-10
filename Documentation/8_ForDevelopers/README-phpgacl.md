             Hints for Using phpGACL with LibreHealth EHR
             by Rod Roark <rod at sunsetsystems dot com>

Installation Instructions

phpGACL access controls are embedded and installed by default in LibreHealth EHR
versions 2.9.0.3 or later.  The administration of the access controls is
within LibreHealth EHR in the admin->acl menu. The library/acl.inc file can be
easily modified to turn off phpGACL or to use an external version.


Upgrading Instructions

After you have upgraded to a new version of LibreHealth EHR, you should
run the acl_upgrade.php program using your web browser
(e.g. http://libreehr.location/acl_upgrade.php). This will ensure your
phpGACL database contains all the required LibreHealth EHR Access Control
Objects.


For Developers

If you add a new Access Control Object to the LibreHealth EHR codebase, then
also add it to the following three sites:
1. Header notes of the library/acl.inc file
2. acl_setup.php file
3. acl_upgrade.php file


Miscellaneous Information
(the below information is only applicable
to LibreHealth EHR versions less than 2.9.0.3 or to users who choose to
install an external version of phpGACL)

If you are using an LibreHealth EHR version previous to 2.9.0.3, then phpGACL
has not been automatically installed.  Setting it up takes some careful
study, planning and a bit of time.  If you don't have the time then you
should hire an experienced person to set things up for you.  Helpful
installation and configuration instructions can be found on the wiki at the
librehealth.io site.

Alternatively, it's possible to set up your own access rules without
using phpGACL by customizing the code in library/acl.inc.  See that
module for more information.

phpGACL is available from http://phpgacl.sourceforge.net/.  Read
its documentation and install it according to its instructions.
Helpful installation and configuration instructions can also be
found on the wiki at the librehealth.io site.

The admin GUI needs to be protected, so add something like this
to your Apache configuration:

  <Directory  "/var/www/phpgacl/admin">
    AuthType Basic
    AuthName "ACL Administrators"
    AuthUserFile  /var/www/phpgacl/admin/.htpasswd
    Require valid-user
  </Directory>

And of course make an associated .htpasswd file at the corresponding
location.  See "man htpasswd2" if you have Apache 2.  Yes, it's quite
odd that the phpGACL GUI does not control access to itself!

Note that LibreHealth EHR does not use AXOs, so ignore the AXO Group Admin
tab and other references to AXOs.

After you have installed phpGACL and modified library/acl.inc
appropriately, you should run the acl_setup.php program using your
web browser (e.g. http://libreehr.location/acl_setup.php).  This will
create some required and sample objects in the phpGACL database for
LibreHealth EHR.

acl_setup.php creates required Access Control Objects (ACOs, the
things to be protected), several sample Access Request Object (AROs
are the users requesting access), and their corresponding sections.
You may also create such objects yourself using the "ACL Admin"
tab of the phpGACL GUI.

The Access Control Objects (ACOs) for LibreHealth EHR have a very specific
structure.  This is described in the file library/acl.inc, which
you must also modify in order to enable use of phpGACL.

You must manually create an ARO in this "users" section for each
LibreHealth EHR user (except for the "admin" user which the setup program
creates).  The Value column must be the user's LibreHealth EHR login name,
and the Name column can (should) be their full name.

By the way, values in the "Order" columns do not seem to be important.
I just plug in "10" for everything.  Names are cosmetic but should be
meaningful to you.  What really matters is the Value column.

Then you should define or modify groups and assign users (AROs) to
them using the "ARO Group Admin" tab of the GUI.  These can be
structured any way you like.  Here is one example of a group
heirarchy for a clinic setting:

  Users
    Accounting
    Administrators
    Assistants
      Front Office
      Lab
      Medical
    Nurses
    Physicians

To see your access rules, click the "ACL List" tab.  To make corrections
click the corresponding Edit link on the right side; this will open the
ACL Admin tab with the corresponding selections already made, which you
can then change as desired and resubmit.  Note that the ACL List page
also provides for deleting entries.

The ACL Admin tab is also used to assign permissions.  This is
a really confusing "write only" interface, but thankfully you won't
have to use it every day!  Mostly what you will do here is highlight
a group from the box on the right, and also select some ACOs from the
top section by highlighting them and clicking the ">>" button.
Then if "write" or "wsome" or "addonly" access applies, key in that
as the return value, otherwise a return value is not required.  Then
click the Submit button to save that particular access rule.  Repeat
until all your ACL rules are defined.
