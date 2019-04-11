# ![moodle-mod_collabora](pix/icon.png) Activity Module: Collabora

[![Build Status](https://travis-ci.org/learnweb/moodle-mod_collabora.svg?branch=master)](https://travis-ci.org/learnweb/moodle-mod_collabora)

This activity module enables Moodle users to create documents (simple text files, word, spreadsheet and presentation documents or upload a document) via a selfhosted Collabora Online Server i.e. [CODE](https://www.collaboraoffice.com/code/) using the so called WOPI protocol and work collaborative on this documents.

This plugin is originaly written by Davo Smith from Synergy Learning in 2019 and maintained by [Jan Dageförde](https://github.com/Dagefoerde) from the University of Muenster and [Michael Wuttke](https://github.com/moodlebeuth) from the Beuth University of Applied Sciences in Berlin.

## Requirements
- Collabora Online Server (Version 4.0.1 or later) and Moodle Server (Version 3.5 or later) with PHP 7.0 or later.

## Tested Versions
- Collabora Online Server: 4.0.3
- Moodle: 3.5.5

## Installation
This plugin should go into mod/collabora. Upon installation, several default settings need to be defined for this activity (see Settings).

## Administrative Settings of the activity module
![collabora_admin_settings](https://user-images.githubusercontent.com/2102425/55971535-f73cbc00-5c81-11e9-844b-26cd08fbb65e.png)

- the Collabora URL (the URL of the Collabora Online Server)
- the default format (File upload, Specified text, Spreadsheet, Wordprocessor document or Presentation)
- the default display (current tab or new tab)
- the default display name
- the default display description

## choose the activity Collaborative Document
![collabora_add_activity](https://user-images.githubusercontent.com/2102425/55971859-93ff5980-5c82-11e9-9a8d-9f813b50d921.png)

## define the settings of the Collaborative Document
![collabora_settings](https://user-images.githubusercontent.com/2102425/55972098-2273db00-5c83-11e9-9c8d-7f715efe8c1b.png)

## View of a word document
![collabora_doc](https://user-images.githubusercontent.com/2102425/55972181-54853d00-5c83-11e9-8b95-4044e54646f7.png)

## View of a spreedsheet document
![collabora_spreadsheet](https://user-images.githubusercontent.com/2102425/55972240-6ebf1b00-5c83-11e9-8cda-554bc5699e8d.png)

## View of a presentation document
![collabora_presentation](https://user-images.githubusercontent.com/2102425/55972302-8e564380-5c83-11e9-9152-b7ea6edeb5a9.png)

