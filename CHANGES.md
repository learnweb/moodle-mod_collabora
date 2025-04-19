moodle-mod_collabora
====================

Changes
-------

### v5.0
* adjust code for Moodle 5.0 using Bootstrap 5.3

### v4.5.4
* 2025-03-16 -  If "Enable versioning" was set to "No", an error appeared
* 2025-03-16 -  Download of version files was not possible

-------
### v4.5.3
* 2025-03-04 -  Add feature to show user pictures in collabora editor - **thanks to** [Collabora Productivity](https://www.collaboraonline.com)
* 2025-03-04 -  Add feature to show the collabora server audit for siteadmins - **thanks to** [Collabora Productivity](https://www.collaboraonline.com)
* 2025-03-04 -  Optimize handling of tokens

### v4.5.2
* 2025-01-21 -  Since Moodle 4.5, you have to check an existing session by using the session handler (#45).
* 2025-01-21 -  Fix wrong type param in repair.php
* 2025-01-21 -  Adjust github workflow to be more restrictive

### v4.5.1
* 2024-11-04 -  Fix wrong sort param int get_area_files while loading the current group file.

### v4.5.0
* 2024-10-30 -  Add support for managing templates.

### v4.3.3
* 2024-02-12 -  Fix course without groups but in group mode 1 or 2

### v4.3.2
* 2024-02-01 -  Fix in 4.1 undefined constant PRIMARY_BUTTON in class single_button

### v4.3.1
* 2024-01-07 -  Adding postmessage support which brings features like version management or switching the user interface
* 2024-01-07 -  Compatibility to Moodle 4.3
* 2024-01-11 -  Update github actions

### v4.3

* 2023-06-23 -  Fix error in "collabora_get_coursemodule_info" throwing error in some situations (#40).

### v4.2-r2

* 2023-05-25 -  Optimize output and settings
* 2023-05-25 -  Now showing the file name above the document instead of the title (#39)

### v4.2-r1

* 2023-04-27 -  Optimize monologo for document depending icons
* 2023-04-27 -  Fix syntax of new \single_button(...) call.
* 2023-04-27 -  Optimize testing mode.

### v4.1-r1

* 2022-11-23 -  Optimize github actions workflow
* 2022-11-23 -  API has now an option to return result instead of throwing it through the output. This makes testing easier.
* 2022-11-19 -  Better access check. A logged out user can not share the iframe url anymore.
* 2022-11-19 -  New abstraction for filesystem which makes it easier to share the API code between mod_collabora and this plugin.

### v4.0-r5

* 2022-11-07 -  Fix event classes abstraction.

### v4.0-r4

* 2022-11-07 -  Fix wrongfully called require_sesskey (#37).

### v4.0-r3

* 2022-11-06 -  Optimize code and add more phpdoc documentations.

### v4.0-r2

* 2022-11-03 -  Add new capability for direct downloading (#34)

### v4.0-r1

* 2022-11-03 -  Open in new tab not working (#36)
* 2022-11-03 -  Add monologo icon

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
