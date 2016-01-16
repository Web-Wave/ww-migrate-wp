<?php
/*
 *  Global constructor
*/

class MIGRATE_WW_Plugin{

    /***** Call the constructor *****/
    public function __construct(){

        /***** Add the menu *****/
        function MIGRATE_WW_create_menu(){
            /***** Add the settings page *****/
        	add_menu_page('Migrate CMS Plugin', 'Migrate CMS', 'administrator', 'migrate-cms', 'MIGRATE_WW_settings_page' , MIGRATE_WW__PLUGIN_URL.'/images/icon.png' );
        	add_action( 'admin_init', 'MIGRATE_WW_plugin_settings' );

            /***** Add the Debug page *****/
            if(get_option('MIGRATE_WW_debug_mode')){
                add_submenu_page('migrate-ww', 'Logs Migrate CMS', 'Logs', 'administrator', 'migrate-cms-debug', 'MIGRATE_WW_debug_page' );
            }
        }

        /***** Add the fields settings page *****/
        function MIGRATE_WW_plugin_settings(){
        	register_setting( 'Migrate-cms-settings-group', 'MIGRATE_WW_debug_mode' );
        }

        /***** Add the settings page *****/
        function MIGRATE_WW_settings_page(){
            $checked = " ";
			if(get_option('MIGRATE_WW_debug_mode')){
				$checked = " checked='checked' ";
			}
            echo '<div class="wrap">';
                echo '<h2>Migrate CMS</h2>';
                echo '<form method="post" action="options.php">';
                    settings_fields( 'Migrate-cms-settings-group' );
                    do_settings_sections( 'Migrate-cms-settings-group' );
                    echo '<table class="form-table">';
                        echo '<tr valign="top">';
                            echo '<th scope="row">Enable debug mode:</th>';
                            echo '<td><input type="checkbox" name="MIGRATE_WW_debug_mode" value="true" '.$checked.' /></td>';
                        echo '</tr>';
                    echo '</table>';
                    submit_button();
                echo '</form>';
            echo '</div>';
        }

        /***** Add the debug page *****/
        function MIGRATE_WW_debug_page() {
            echo '<div class="wrap">';
                echo '<h2>Debug Migrate CMS</h2>';
                echo '<form method="post" action="options.php">';
                    echo '<table class="form-table" style="max-height:100px;">';
                        echo '<tr valign="top">';
                            echo '<th scope="row">Logs:</th>';
                            echo '<td>';
                                $logs = fopen(MIGRATE_WW__PLUGIN_DIR.'logs/logs.txt', 'r');
                                if($logs){
                                    while(!feof($logs)){
                                        $content_logs = fgets($logs);
                                        echo $content_logs;
                                    }
                                    fclose($logs);
                                }else{
                                    echo '<p style="color:#D5202A;">[Warning] The logs.txt file has <strong>not found</strong> on the server.</p>';
                                }
                            echo '</td>';
                        echo '</tr>';
                    echo '</table>';
                echo '</form>';
            echo '</div>';
        }
        add_action('admin_menu', 'MIGRATE_WW_create_menu');
    }
}
