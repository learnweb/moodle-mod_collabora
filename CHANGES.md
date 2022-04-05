moodle-mod_collabora
====================

Changes
-------

### v4.0-beta-3

* 2022-04-04 -  Ensure LastModifiedTime is in UTC (#32)

### v4.0-beta-2

* 2022-03-06 - removed wrong reference in backup

### v3.11-r2

* 2022-03-05 - replace travis by github actions and apply coding style

### v3.11-r1

* 2021-10-26 - define NO_MOODLE_COOKIES in callback.php
* 2021-10-26 - Add option to unblock discovery url (See: $CFG->curlsecurityblockedhosts)
* 2021-10-26 - Load the discovery xml only into the cache if it is valid.
* 2021-10-26 - Add download button outside the collabora iframe to be able to load the last document even if it's broken.
* 2021-10-26 - Add a repair feature to connect the document with a new collabora process if it is broken.
* 2021-10-26 - Add an icon for text documents (*.txt)
* 2021-10-26 - Add png icons for installations without svg support.
* 2021-10-26 - Optimize layout for open in new register.

### v3.10-r3

* 2021-04-12 - stop dnd-api (drag-and-drop) while deactivated

### v3.10-r2

* 2020-11-25 - fix iframe height to show office status bar (#27)
* 2020-11-25 - update README.md

### v3.10-r1

* 2020-10-13 - adjust .travis.yml to check moodle 3.10 properly

### v3.9-r5

* 2020-10-12 - add support for fullscreen without using 'requestFullscreen' (#26)

### v3.9-r4

* 2020-10-03 - add simple multi lang support (#24)

### v3.9-r3

* 2020-08-11 - Fix getting groupmode from cm (PR #22)

### v3.9-r2

* 2020-08-11 - added drag-and-drop support on course pages (PR #19)
* 2020-08-11 - removed German language pack as translations are kept in AMOS

### v3.9-r1

* 2020-06-24 - updated version.php, README-md & CHANGES.md
* 2020-06-24 - merged PR #17: ready for Moodle 3.9
* 2020-06-19 - merged PR #14: updated travis for Moodle 3.9 (Thanks to rtschu for the PR's)

### v3.8-r2

* 2020-05-16 - Fix fullscreen for firefox (PR #12)
* 2020-05-16 - Added a check in the validation method for the update instance in mod_form.php (PR #13)
  (Thanks to Andreas Grabs for the PR's)

### v3.8-r1

* 2019-11-21 - Fix part of Privacy API implementation
* 2019-10-14 - Implement Privacy API

### v3.7-r1

* 2019-04-28 - Show custom course module icon that depends on the type of its file

### v3.5-r1

* 2019-03-22 - add CHANGES.md & README.md
* 2019-03-21 - add travis ci
* 2019-03-21 - Initial plugin commit
