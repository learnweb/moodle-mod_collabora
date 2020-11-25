# ![moodle-mod_collabora](pix/icon.png) Activity Module: Collabora Online integration for Moodle

[![Build Status](https://travis-ci.org/learnweb/moodle-mod_collabora.svg?branch=master)](https://travis-ci.org/learnweb/moodle-mod_collabora)

This activity module enables Moodle users to create documents (simple text files, word, spreadsheet and presentation documents or upload a document) via a selfhosted Collabora Online Server i.e. [CODE](https://www.collaboraoffice.com/code/) using the so called WOPI protocol and work collaboratively on this documents.

This plugin is originally written by [Davo Smith](https://github.com/davosmith) from Synergy Learning in 2019 and maintained by [Michael Wuttke](https://github.com/moodlebeuth) from the Beuth University of Applied Sciences in Berlin and [Andreas Grabs](https://github.com/grabs) from Grabs EDV-Beratung.

## Requirements
- Collabora Online Server (Version 4.0.1 or later) and Moodle Server (Version 3.5 or later) with PHP 7.0 or later.

## Tested Versions
- Collabora Online Server: 6.4.0
- Moodle: 3.7.9
- Moodle: 3.8.6
- Moodle: 3.9.3
- Moodle: 3.10

## Installation
This plugin should go into mod/collabora. Upon installation, several default settings need to be defined for this activity (see Settings).

## Administrative Settings of the activity module
![collabora_admin_settings](https://user-images.githubusercontent.com/2102425/55971535-f73cbc00-5c81-11e9-844b-26cd08fbb65e.png)

- the Collabora URL (the URL of the Collabora Online Server)
- the default format (File upload, Specified text, Spreadsheet, Wordprocessor document or Presentation)
- the default display (current tab or new tab)
- the default display name
- the default display description

## Choose the activity Collaborative Document
![collabora_add_activity](https://user-images.githubusercontent.com/2102425/55971859-93ff5980-5c82-11e9-9a8d-9f813b50d921.png)

## Define the settings of the Collaborative Document
![collabora_settings](https://user-images.githubusercontent.com/2102425/55972098-2273db00-5c83-11e9-9c8d-7f715efe8c1b.png)

## View of a word document
![collabora_doc](https://user-images.githubusercontent.com/2102425/55972181-54853d00-5c83-11e9-8b95-4044e54646f7.png)

## View of a spreedsheet document
![collabora_spreadsheet](https://user-images.githubusercontent.com/2102425/55972240-6ebf1b00-5c83-11e9-8cda-554bc5699e8d.png)

## View of a presentation document
![collabora_presentation](https://user-images.githubusercontent.com/2102425/55972302-8e564380-5c83-11e9-9152-b7ea6edeb5a9.png)

## Testing the plugin

If you want to test the collabora activity plugin on a local Moodle installation and a local Collabora Online Server via docker then you may find the [Collabora-Config.md](https://github.com/learnweb/moodle-mod_collabora/blob/master/Collabora-Config.md) file helpful.

## Use of other Online Editors as Collabora Online, such as LibreOffice Online

This plugin should also work with the use of LibreOffice Online (LOOL) - which is the base product of Collabora Online - if you set up an LibreOffice Onliner server as described in the documentation of the [Document Foundation](https://wiki.documentfoundation.org/Development/BuildingOnline).

## Use of Collabora trademarks

The name "Collabora" is used to indicate that the plugin provides an integration facility for use of Collabora Online from within Moodle.
The name does not imply an endorsement by Collabora, nor does it indicate who develops and provides the plugin.
This plugin was created and is offered by members of the community.

Note that the plugin also makes use of icons that, some of which are trademarks of Collabora.
The icons are made available to you under conditions that differ from the rest of the plugin; see [pix/LICENSE](pix/LICENSE/).
