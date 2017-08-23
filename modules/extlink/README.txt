CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------
The External Links module is a very simple approach to adding icons to links
to external websites or e-mail addresses. It is a JavaScript-based
implementation, so the icons are only shown to users that have JavaScript
enabled.

External Links was written by Nathan Haug.
Built by Robots: http://www.lullabot.com

REQUIREMENTS
------------
No special requirements.

INSTALLATION
------------
Simply install External Links like you would install any other module.

1) Copy the extlink folder in the modules folder in your Drupal docroot.

2) Enable the module using Manage -> Extend (/admin/modules).

CONFIGURATION
-------------
No additional configuration is necessary though you may fine-tune settings at
Manage -> Configuration -> External Links
(/admin/config/user-interface/extlink).

A NOTE ABOUT THE CSS
--------------------
This module adds a CSS file that is only a few lines in length. You may choose
to move this CSS to your theme to prevent the file from needing to be loaded
separately. To do this:

1) Open the .info.yml file for your theme and add those lines of code to
   prevent the extlink.css file from loading:

    stylesheets-remove:
      - extlink.css

2) Open the extlink.css file within the extlink directory and copy all the code
   from the file into your theme's style.css file.
3) Copy the extlink.png and mailto.png files to your theme's directory.

Note that you DO NOT need to make an extlink.css file. Specifying the file
in the .info.yml file is enough to tell Drupal not to load the original file.

MAINTAINERS
-----------
Current maintainers:

- Lachlan Ennis (elachlan) - https://www.drupal.org/u/elachlan
- Nate Haug (quicksketch) - https://www.drupal.org/u/quicksketch
