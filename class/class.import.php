<?php
/*
 *  Import functions
*/
header('Content-Type: text/html; charset=utf-8');
require_once('./../../../../wp-config.php');

class MIGRATE_WW_Plugin_Import{

    /***** Manage the logs.txt file *****/
    function MIGRATE_WW_parse_logstxt($logstxt){
        $open_parse_logstxt = fopen($logstxt, 'w+');
        fwrite($open_parse_logstxt,'<p style="color:#D5C220;">[Information] The logs file has been <strong>created</strong> on the server.</p>');
        fclose ($open_parse_logstxt);
        echo '[Information] The logs file has been created on the server.<br />';
    }

    /***** Take the data from the CMS Simple database *****/
    function MIGRATE_WW_get_data_CMS_SIMPLE($logstxt){
        $host_CMS_SIMPLE = 'host';
        $user_CMS_SIMPLE = 'user';
        $password_CMS_SIMPLE = 'password';
        $database_CMS_SIMPLE = 'database';
        $data_CMS_SIMPLE = array();
        $connect_CMS_SIMPLE = new mysqli($host_CMS_SIMPLE, $user_CMS_SIMPLE, $password_CMS_SIMPLE, $database_CMS_SIMPLE);

        if($connect_CMS_SIMPLE->connect_error){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#D5202A;">[Warning] We <strong>can\'t connect</strong> to the origin database.</p>');
            fclose ($open_parse_logstxt);
            echo '[Warning] We can\'t connect to the origin database.<br />';
            throw new Exception($connect_CMS_SIMPLE->connect_error, $connect_CMS_SIMPLE->connect_errno);
            exit;
        }else{
            $connect_CMS_SIMPLE->close();

            /***** Categories *****/
            $connect_CMS_SIMPLE = new mysqli($host_CMS_SIMPLE, $user_CMS_SIMPLE, $password_CMS_SIMPLE, $database_CMS_SIMPLE);
            $connect_CMS_SIMPLE->set_charset("utf8");
            $result_categories = $connect_CMS_SIMPLE->query("SELECT c.id, c.name
                                                        FROM cms_module_cgblog_categories c;");
            while($row = $result_categories->fetch_assoc()){
                $rowCategories[$row['id']] = $row['name'];
            }
            $connect_CMS_SIMPLE->close();
            $data_CMS_SIMPLE['categories'] = $rowCategories;

            /***** News *****/
            $connect_CMS_SIMPLE = new mysqli($host_CMS_SIMPLE, $user_CMS_SIMPLE, $password_CMS_SIMPLE, $database_CMS_SIMPLE);
            $connect_CMS_SIMPLE->set_charset("utf8");
            $result_news = $connect_CMS_SIMPLE->query("SELECT n.cgblog_title, n.summary, n.cgblog_data, n.cgblog_date, c.id
                                                        FROM cms_module_cgblog n
                                                        LEFT JOIN cms_module_cgblog_blog_categories rc ON rc.blog_id = n.cgblog_id
                                                        LEFT JOIN cms_module_cgblog_categories c ON c.id = rc.category_id
                                                        WHERE n.status = 'published'
                                                        ORDER BY n.cgblog_date ASC;");
            while($row = $result_news->fetch_assoc()){
                $singlenew = array();
                $singlenew = array($row['cgblog_title'],$row['summary'],$row['cgblog_data'],$row['cgblog_date'],$row['id']);
                $rowNews[] = $singlenew;
            }

            $connect_CMS_SIMPLE->close();
            $data_CMS_SIMPLE['news'] = $rowNews;

            /***** Pages *****/
            $connect_CMS_SIMPLE = new mysqli($host_CMS_SIMPLE, $user_CMS_SIMPLE, $password_CMS_SIMPLE, $database_CMS_SIMPLE);
            $connect_CMS_SIMPLE->set_charset("utf8");
            $result_pages = $connect_CMS_SIMPLE->query("SELECT c.content_name, cp.content
                                                        FROM cms_content c
                                                        LEFT JOIN cms_content_props cp ON cp.content_id = c.content_id
                                                        WHERE c.type = 'content';");
            while($row = $result_pages->fetch_assoc()){
                $singlepage = array();
                $singlepage = array($row['content_name'],$row['content']);
                $rowPages[] = $singlepage;
            }
            $connect_CMS_SIMPLE->close();
            $data_CMS_SIMPLE['pages'] = $rowPages;
        }

        if($rowCategories > 0 || $rowNews > 0 || $rowPages > 0){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#56b859;">[Success] The data from the CMS Simple has been <strong>found</strong>.</p>');
            fclose ($open_parse_logstxt);
            echo '[Success] The data from the CMS Simple has been found.<br />';
            return $data_CMS_SIMPLE;
        }else{
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#D5202A;">[Warning] The data from the CMS Simple has been <strong>not found</strong>.</p>');
            fclose ($open_parse_logstxt);
            echo '[Information] The data from the CMS Simple has been not found.<br />';
            exit;
        }
    }

    /***** Push categories data to wordpress *****/
    function MIGRATE_WW_push_cats_to_wordpress($logstxt,$data_CMS_SIMPLE){
        global $wpdb;
        $CategorieAddToWordpress = 0;
        $CategorieNotAddToWordpress = 0;
        $data_CMS_SIMPLE_categories = $data_CMS_SIMPLE['categories'];

        foreach($data_CMS_SIMPLE_categories as $key => $value){
            $id = $key;
            $title = $value;
            $slug = sanitize_title($title);
            $term_exists = term_exists( $title, 'category' );

            if(empty($term_exists)){
                if(wp_insert_term(
                    $title,
                    'category',
                    array(
                        'description'=> 'Category '.$title.' imported by WW Migrate plugin.',
                        'slug' => $slug
                    )
                )){
                    if(file_exists($logstxt)){
                        $open_parse_logstxt = fopen($logstxt, 'a+');
                        fwrite($open_parse_logstxt,'<p style="color:#56b859;">[Success] Categoy with the id : <strong>'.$id.'</strong> is pushed into the Wordpress Database.</p>');
                        fclose ($open_parse_logstxt);
                        echo '[Success] Category with the id : '.$id.' is pushed into the Wordpress Database.<br />';
                    }
                    $CategorieAddToWordpress++;
                }else{
                    $CategorieNotAddToWordpress++;
                }
            }else{
                $CategorieNotAddToWordpress++;
            }
        }

        if(file_exists($logstxt) && $CategorieAddToWordpress > 0){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#56b859;">[Success] '.$CategorieAddToWordpress.' categories are <strong>pushed</strong> into the Wordpress Database.</p>');
            fclose ($open_parse_logstxt);
            echo '[Success] '.$CategorieAddToWordpress.' categories are pushed into the Wordpress Database.<br />';
        }

        if(file_exists($logstxt) && $CategorieNotAddToWordpress > 0){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#D5C220;">[Information] '.$CategorieNotAddToWordpress.' categories are <strong>not updated</strong> into the Wordpress Database.</p>');
            fclose ($open_parse_logstxt);
            echo '[Information] '.$CategorieNotAddToWordpress.' categories are already updated into the Wordpress Database.<br />';
        }
    }

    /***** Push posts data to wordpress *****/
    function MIGRATE_WW_push_posts_to_wordpress($logstxt,$data_CMS_SIMPLE){
        global $wpdb;
        $PostsAddToWordpress = 0;
        $PostsNotAddToWordpress = 0;
        $PostsUpdateToWordpress = 0;
        $categories_wp = get_terms( 'category', 'orderby=count&hide_empty=0' );
        $categories_link_database = array();

        foreach($data_CMS_SIMPLE['categories'] as $category_id_imported => $category_name_imported){
            foreach($categories_wp as $category_wp){
                if($category_name_imported == $category_wp->name){
                    $categories_link_database[$category_id_imported] = $category_wp->term_id;
                }
            }
        }

        if(file_exists($logstxt)){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<br /><p style="color:#D5C220;">[Information] /***** <strong>Start Posts Import</strong> *****\</p>');
            fclose ($open_parse_logstxt);
            echo '<br />[Information] /***** Start Posts Import *****\<br />';
        }

        foreach($data_CMS_SIMPLE['news'] as $post){
            $id_cat = '';
            $data_news = '';
            $action = '';
            $excerpt = '';
            $content = '';
            $date = '';
            $title = str_replace("'","\'",$post[0]);
            $slug = str_replace("'","",$post[0]);
            $slug = str_replace("!","",$slug);
            $slug = str_replace("！","",$slug);
            $slug = str_replace(":","",$slug);
            $slug = str_replace("：","",$slug);
            $slug = preg_replace("/[\x{4e00}-\x{9fa5}]+/u", '', $slug);
            $slug = trim($slug);
            $slug = sanitize_title($slug);
            $checknews = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_title = '".$title."' AND post_type = 'post'");

            if(!empty($post[1])){
                $excerpt = str_replace("uploads/","/wp-content/uploads/",$post[1]);
            }

            if(!empty($post[2])){
                $content = str_replace("uploads/","/wp-content/uploads/",$post[2]);
            }

            if(!empty($post[3])){
                $date = $post[3];
            }else{
                $date = date('Y-m-d H:i:s');
            }

            foreach($categories_link_database as $category_id_imported => $category_id_wp){
                if($post[4] == $category_id_imported){
                    $id_cat = $category_id_wp;
                }
            }

            if(!empty($checknews)){
                $data_news = array(
                    'ID' => $checknews,
                    'post_title' => $title,
                    'post_name' => $slug,
                    'post_excerpt' => $excerpt,
                    'post_content' => $content,
                    'post_date' => $date,
                    'post_category' => array($id_cat),
                    'post_type' => 'post',
                    'post_status'   => 'publish',
                    'post_author'   => 2
                );
                $action = 'updated';
            }else{
                $data_news = array(
                    'post_title' => $title,
                    'post_name' => $slug,
                    'post_excerpt' => $excerpt,
                    'post_content' => $content,
                    'post_date' => $date,
                    'post_category' => array($id_cat),
                    'post_type' => 'post',
                    'post_status'   => 'publish',
                    'post_author'   => 2
                );
                $action = 'pushed';
            }
            if(wp_insert_post($data_news)){
                if(file_exists($logstxt)){
                    $open_parse_logstxt = fopen($logstxt, 'a+');
                    fwrite($open_parse_logstxt,'<p style="color:#56b859;">[Success] Post : <strong>'.$post[0].'</strong> is '.$action.' into the Wordpress Database.</p>');
                    fclose ($open_parse_logstxt);
                }
                if($action == 'pushed'){
                    $PostsAddToWordpress++;
                }else{
                    $PostsUpdateToWordpress++;
                }
            }else{
                if(file_exists($logstxt)){
                    $open_parse_logstxt = fopen($logstxt, 'a+');
                    fwrite($open_parse_logstxt,'<p style="color:#D5202A;">[Warning] Post : <strong>'.$post[0].'</strong> is not '.$action.' into the Wordpress Database.</p>');
                    fclose ($open_parse_logstxt);
                }
                $PostsNotAddToWordpress++;
            }
        }

        if(file_exists($logstxt) && $PostsAddToWordpress > 0){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#56b859;">[Success] '.$PostsAddToWordpress.' posts are <strong>pushed</strong> into the Wordpress Database.</p>');
            fclose ($open_parse_logstxt);
            echo '[Success] '.$PostsAddToWordpress.' posts are pushed into the Wordpress Database.<br />';
        }

        if(file_exists($logstxt) && $PostsUpdateToWordpress > 0){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#56b859;">[Success] '.$PostsUpdateToWordpress.' posts are <strong>updated</strong> into the Wordpress Database.</p>');
            fclose ($open_parse_logstxt);
            echo '[Success] '.$PostsUpdateToWordpress.' posts are updated into the Wordpress Database.<br />';
        }

        if(file_exists($logstxt) && $PostsNotAddToWordpress > 0){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#D5202A;">[Warning] '.$PostsNotAddToWordpress.' posts are <strong>not pushed</strong> into the Wordpress Database.</p>');
            fclose ($open_parse_logstxt);
            echo '[Warning] '.$PostsNotAddToWordpress.' posts are not pushed into the Wordpress Database.<br />';
        }

        if(file_exists($logstxt)){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#D5C220;">[Information] /***** <strong>End Posts Import</strong> *****\</p><br />');
            fclose ($open_parse_logstxt);
            echo '[Information] /***** End Posts Import *****\<br /><br />';
        }
    }

    /***** Push pages data to wordpress *****/
    function MIGRATE_WW_push_pages_to_wordpress($logstxt,$data_CMS_SIMPLE){
        global $wpdb;
        $PagesAddToWordpress = 0;
        $PagesNotAddToWordpress = 0;
        $PagesUpdateToWordpress = 0;

        if(file_exists($logstxt)){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<br /><p style="color:#D5C220;">[Information] /***** <strong>Start Pages Import</strong> *****\</p>');
            fclose ($open_parse_logstxt);
            echo '<br />[Information] /***** Start Pages Import *****\<br />';
        }

        foreach($data_CMS_SIMPLE['pages'] as $page){
            $action = '';
            $title = '';
            $content = '';
            $title = str_replace("'","\'",$page[0]);
            $slug = str_replace("'","",$page[0]);
            $slug = str_replace("!","",$slug);
            $slug = str_replace("！","",$slug);
            $slug = str_replace(":","",$slug);
            $slug = str_replace("：","",$slug);
            $slug = preg_replace("/[\x{4e00}-\x{9fa5}]+/u", '', $slug);
            $slug = trim($slug);
            $slug = sanitize_title($slug);
            $checkpages = $wpdb->get_var("SELECT $wpdb->posts.ID
                                            FROM $wpdb->posts
                                            WHERE $wpdb->posts.post_title = '$title'
                                            AND $wpdb->posts.post_type = 'page'");

            if(!empty($page[1])){
                $content = str_replace("uploads/","/wp-content/uploads/",$page[1]);
            }

            if(!empty($checkpages)){
                $data_pages = array(
                    'ID' => $checkpages,
                    'post_title' => $title,
                    'post_name' => $slug,
                    'post_content' => $content,
                    'post_type' => 'page',
                    'post_status'   => 'publish',
                    'post_author'   => 2
                );
                $action = 'updated';
            }else{
                $data_pages = array(
                    'post_title' => $title,
                    'post_name' => $slug,
                    'post_content' => $content,
                    'post_type' => 'page',
                    'post_status'   => 'publish',
                    'post_author'   => 2
                );
                $action = 'pushed';
            }
            if(wp_insert_post($data_pages)){
                if(file_exists($logstxt)){
                    $open_parse_logstxt = fopen($logstxt, 'a+');
                    fwrite($open_parse_logstxt,'<p style="color:#56b859;">[Success] Page : <strong>'.$post[0].'</strong> is '.$action.' into the Wordpress Database.</p>');
                    fclose ($open_parse_logstxt);
                }
                if($action == 'pushed'){
                    $PagesAddToWordpress++;
                }else{
                    $PagesUpdateToWordpress++;
                }
            }else{
                if(file_exists($logstxt)){
                    $open_parse_logstxt = fopen($logstxt, 'a+');
                    fwrite($open_parse_logstxt,'<p style="color:#D5202A;">[Warning] Page : <strong>'.$post[0].'</strong> is not '.$action.' into the Wordpress Database.</p>');
                    fclose ($open_parse_logstxt);
                }
                $PagesNotAddToWordpress++;
            }
        }

        if(file_exists($logstxt) && $PagesAddToWordpress > 0){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#56b859;">[Success] '.$PagesAddToWordpress.' pages are <strong>pushed</strong> into the Wordpress Database.</p>');
            fclose ($open_parse_logstxt);
            echo '[Success] '.$PagesAddToWordpress.' pages are pushed into the Wordpress Database.<br />';
        }

        if(file_exists($logstxt) && $PagesUpdateToWordpress > 0){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#56b859;">[Success] '.$PagesUpdateToWordpress.' pages are <strong>updated</strong> into the Wordpress Database.</p>');
            fclose ($open_parse_logstxt);
            echo '[Success] '.$PagesUpdateToWordpress.' pages are updated into the Wordpress Database.<br />';
        }

        if(file_exists($logstxt) && $PagesNotAddToWordpress > 0){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#D5202A;">[Warning] '.$PagesNotAddToWordpress.' pages are <strong>not pushed</strong> into the Wordpress Database.</p>');
            fclose ($open_parse_logstxt);
            echo '[Warning] '.$PagesNotAddToWordpress.' pages are not pushed into the Wordpress Database.<br />';
        }

        if(file_exists($logstxt)){
            $open_parse_logstxt = fopen($logstxt, 'a+');
            fwrite($open_parse_logstxt,'<p style="color:#D5C220;">[Information] /***** <strong>End Pages Import</strong> *****\</p><br />');
            fclose ($open_parse_logstxt);
            echo '[Information] /***** End Pages Import *****\<br /><br />';
        }
    }
}

/***** Import Variables *****/
$logstxt = './../logs/logs.txt';

/***** Create import object *****/
$MIGRATE_WW_Plugin_Import = new MIGRATE_WW_Plugin_Import();

/***** Import works *****/
$MIGRATE_WW_Plugin_Import->MIGRATE_WW_parse_logstxt($logstxt);
$data_CMS_SIMPLE = $MIGRATE_WW_Plugin_Import->MIGRATE_WW_get_data_CMS_SIMPLE($logstxt);
$MIGRATE_WW_Plugin_Import->MIGRATE_WW_push_cats_to_wordpress($logstxt,$data_CMS_SIMPLE);
$MIGRATE_WW_Plugin_Import->MIGRATE_WW_push_posts_to_wordpress($logstxt,$data_CMS_SIMPLE);
$MIGRATE_WW_Plugin_Import->MIGRATE_WW_push_pages_to_wordpress($logstxt,$data_CMS_SIMPLE);
