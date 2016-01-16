# WW Migrate CMS made simple data to your Wordpress

A custom plugin for Wordpress, he helps you to migrate data from CMS Made simple to WordPress.
This plugin works with Wordpress 3.x version and 4.x.

You can run the script with the terminal with this line in the class folder : php class.import.php

# Configuration
- Give the right permissions to this log file : logs/logs.txt.
- Copy and past all your images from CMS made simple in a folder called 'uploads' at the root of your website.
- Put your CMS made simple database access in the file class/class.import.php.

  - $host_CMS_SIMPLE = 'host';
  - $user_CMS_SIMPLE = 'user';
  - $password_CMS_SIMPLE = 'password';
  - $database_CMS_SIMPLE = 'database';
