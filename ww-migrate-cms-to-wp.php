<?php
/*
Plugin Name: Migrate-CMS-Made-In-Simple
Plugin URI: http://www.web-wave.fr
Description: A plugin for migrate data from CMS Made simple website to WordPress.
Version: 1.3.4
Author: Web-Wave
Author URI: http://www.web-wave.fr
License: GPL2
*/

/***** Define variables *****/
define('MIGRATE_WW__PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('MIGRATE_WW__PLUGIN_DIR', plugin_dir_path( __FILE__ ));

/***** Include files *****/
require_once(MIGRATE_WW__PLUGIN_DIR.'/class/class.global.php');

/***** Create global object *****/
new MIGRATE_WW_Plugin();
