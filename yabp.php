<?php
/*
    Plugin Name: Yet Another bol.com Plugin
    Plugin URI: http://tromit.nl/diensten/wordpress-plugins/
    Description: A powerful plugin to easily integrate bol.com products in your blog posts or at your pages to earn money with the bol.com Partner Program.
    Version: 1.0.3
    Author: Mitchel Troost
    Author URI: http://tromit.nl/
    License: GPL2
    Text Domain: yabp
*/

/*  
    Copyright 2014  Mitchel Troost  (email: mitchel.troost@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
    Since the GPL2 license is used, you are allowed to modify anything below at your own risk.
    However, this is not recommended. The developer may not give support on modified versions of this plugin.
    Instead, please contact the developer and submit a bug report or feature request. 
*/

global $wpdb;

function yabp_I18n() { load_plugin_textdomain( 'yabp', false, dirname(plugin_basename( __FILE__ )) . '/lang/'); }
add_action('plugins_loaded', 'yabp_I18n');

$yabp_version = "1.0.3";
$table_name_yabp = $wpdb->prefix . 'yabp';
$table_name_yabp_items = $wpdb->prefix . 'yabp_items';
$yabp_partnerlink_prefix = "https://partnerprogramma.bol.com/click/click?p=1&amp;t=url&amp;s=";
$yabp_impression_imglink_prefix = "http://partnerprogramma.bol.com/click/impression?p=1&amp;s=";
$yabp_open_api_link = "https://developers.bol.com/documentatie/aan-de-slag/";
$yabp_partnerprogram_link = "https://partnerprogramma.bol.com/partner/affiliate/account.do";
$yabp_bolcom_buy_button = "https://www.bol.com/nl/upload/partnerprogramma/promobtn/btn_promo_koop_dark_large.gif";
$yabp_bolcom_buy_button_alt = "https://www.bol.com/nl/upload/partnerprogramma/promobtn/btn_promo_koop_light_large.gif";
$yabp_bolcom_view_button = "https://www.bol.com/nl/upload/partnerprogramma/promobtn/btn_promo_bekijk_dark_large.gif";
$yabp_bolcom_view_button_alt = "https://www.bol.com/nl/upload/partnerprogramma/promobtn/btn_promo_bekijk_light_large.gif";
$yabp_add_item_item_count = 10;
$yabp_add_item_item_count_limit = 50;
$yabp_itemlist_count = 10;
$yabp_styling_item_fontsize_lowlimit = 5;
$yabp_styling_item_fontsize_highlimit = 30;
$yabp_item_textlink_text = __('Buy now', 'yabp');
$yabp_item_shortcode_format = "[yabp %entry_id%]";
$yabp_item_time_format = "Y-m-d H:i:s";
$yabp_cron_defaulttime = "08:00";
$api_server = 'api.bol.com';
$api_port = '443';

function yabp_menu() {    
    if(function_exists('add_menu_page')){
        add_menu_page(__('Options', 'yabp'), 'YAbP', 'manage_options', 'yabp', 'yabp_options');
    }
    if(function_exists('add_submenu_page')){
        $yabp_optionspage = add_submenu_page('yabp', __('Options', 'yabp'), __('Options', 'yabp'), 'manage_options', 'yabp', 'yabp_options');
        add_action('load-'.$yabp_optionspage, 'yabp_register_adminscripts_action');
    }
    if(function_exists('add_submenu_page')){
        add_submenu_page('yabp', __('Add product', 'yabp'), __('Add product', 'yabp'), 'manage_options', 'yabp-add-item', 'yabp_add_item');
    }
    if(function_exists('add_submenu_page')){
        add_submenu_page('yabp', __('Product list', 'yabp'), __('Product list', 'yabp'), 'manage_options', 'yabp-itemlist', 'yabp_itemlist');
    }
}

add_action('admin_menu', 'yabp_menu');

add_action('init', 'yabp_init');
        
function yabp_init(){
    global $yabp_version;    
    if ((isset($_GET['activate']) || isset($_GET['activate-multi'])) || get_option('yabp_version') != $yabp_version) { yabp_install(); }
    if (is_admin()) { yabp_forms(); }
}

function yabp_install(){
    global $wpdb, $table_name_yabp, $table_name_yabp_items;
        
    $sql = "CREATE TABLE IF NOT EXISTS ".$table_name_yabp."(
        entry_id INT auto_increment NOT NULL,
        entry_bolid BIGINT NOT NULL,
        entry_thumb INT(1) NOT NULL,
        entry_showthumb INT(1) NOT NULL,
        entry_showprice INT(1) NOT NULL,
        entry_showlistprice INT(1) NOT NULL,
        entry_showtitle INT(1) NOT NULL,
        entry_showsubtitle INT(1) NOT NULL,
        entry_showavailability INT(1) NOT NULL,
        entry_showrating INT(1) NOT NULL,
        entry_showbutton INT(1) NOT NULL,
        entry_updateinterval INT(1) NOT NULL,
        PRIMARY KEY(entry_id)) ENGINE=MyISAM  DEFAULT CHARSET=utf8";        
        
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);    

    $sql = "CREATE TABLE IF NOT EXISTS ".$table_name_yabp_items."(
        item_id INT auto_increment NOT NULL,
        entry_id INT NOT NULL,
        item_title VARCHAR(100) NOT NULL,
        item_subtitle VARCHAR(100),
        item_externalurl TEXT NOT NULL,
        item_afflink TEXT NOT NULL,
        item_xlthumb TEXT,
        item_lthumb TEXT,
        item_mthumb TEXT,
        item_sthumb TEXT,
        item_xsthumb TEXT,
        item_price VARCHAR(10) NOT NULL,
        item_listprice VARCHAR(10) NOT NULL,
        item_availability TEXT NOT NULL,
        item_availabilitycode INT NOT NULL,
        item_rating INT NOT NULL,
        item_ratingspan TEXT NOT NULL,
        time INT NOT NULL,
        PRIMARY KEY(item_id)) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
        
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);    
    
    update_option('yabp_version', $yabp_version);
    
    if (!get_option('yabp_add_item_item_count')) { update_option('yabp_add_item_item_count', $yabp_add_item_item_count); }
    if (!get_option('yabp_itemlist_count')) { update_option('yabp_itemlist_count', $yabp_itemlist_count); }
    if (!get_option('yabp_item_textlink_text')) { update_option('yabp_item_textlink_text', $yabp_item_textlink_text); }
    
    $intervals = array(1, 2, 3);
    foreach ($intervals as $interv) {
        if (yabp_cron_updateinterval_check_number($interv) >= 1) { yabp_cron_handle_eventstatus($interv, true); }
    }    
}

function yabp_api_dorequest($httpMethod, $url, $parameters, $content, $sessionId) {

    global $api_server, $api_port;
    
    $server = $api_server;
    $port = $api_port;
    $today = gmdate('D, d F Y H:i:s \G\M\T');

    if ($httpMethod == 'GET') { $contentType = 'application/xml';} 
    elseif ($httpMethod == 'POST') { $contentType = 'application/x-www-form-urlencoded'; }

    $headers = $httpMethod . " " . $url . $parameters . " HTTP/1.0\r\n";
    $headers .= "Content-type: " . $contentType . "\r\n";
    $headers .= "Host: " . $server . "\r\n";
    $headers .= "Content-length: " . strlen($content) . "\r\n";
    $headers .= "Connection: close\r\n";
    if (!is_null($sessionId)) {
        $headers .= "X-OpenAPI-Session-ID: " . $sessionId . "\r\n";
    }
        $headers .= "\r\n";

        $socket = fsockopen('ssl://' . $server, $port, $errno, $errstr, 30);
        if (!$socket) { echo "$errstr ($errno)<br />\n"; }
        fputs($socket, $headers);
        fputs($socket, $content);
        $ret = "";

        while (!feof($socket)) {
            $readLine = fgets($socket);
            $ret .= $readLine;
        }
        fclose($socket);

    return $ret;
}

function yabp_pagelinks($link,$showpages='4',$totalpage,$curpage){
    $nav = '';
    
    $prev_page = $curpage != 1 ? ($curpage - 1) : '';
    $next_page = $curpage != $totalpage ? ($curpage + 1) : '';

    if ($totalpage > 1 && $curpage != 1) { $nav .= ' <span style="padding: 5px;"><a href="'.$link.'1" title="'.__('Go to the first page', 'yabp').'">&laquo;</a></span>'; }
    if ($curpage > 1) { $nav .= '<span style="padding: 5px;"><a href="'.$link.''.$prev_page.'" title="'.__('Previous page', 'yabp').'">< '.__('Previous', 'yabp').'</a></span> '; }
    if ($totalpage > 1) {
        if ($totalpage > $showpages) { $showed = ($totalpage+1) - $showpages; }
        else { $showed = 1; }
        
        $last_half    = ceil($showpages/2);
        $first_half = $showpages - $last_half;
        
        for ($i = 1; $i<=$totalpage; $i++) {    
            if ($i+$first_half >= $curpage && $i <= $curpage+$last_half) {
                if ($i == $curpage) { $nav .= '<span style="padding: 5px; font-weight: bold;">'.$i.'</span>'; }
                else { $nav .= '<span style="padding: 5px;"><a href="'.$link.''.$i.'">'.$i.'</a></span>'; }
            }
        }
    }

    if ($curpage < $totalpage) { $nav .= ' <span style="padding: 5px;"><a href="'.$link.''.$next_page.'" title="'.__('Next page', 'yabp').'">'.__('Next', 'yabp').' ></a></span>'; }
    if ($totalpage > 1 && $curpage != $totalpage) { $nav .= '<span style="padding: 5px;"><a href="'.$link.''.$totalpage.'" title="'.__('Last page', 'yabp').'">&raquo;</a></span>'; }    
    if ($totalpage == 1) { $nav = '<span style="font-weight: bold; padding: 5px;">1</span>'; }
    
    $pagenav = '<div class="pagination">'.$nav.'</div>';    
    return $pagenav;
}

function yabp_validate_apikey($apikey) {
    if (isset($apikey) && $apikey != null) {        
        $output = yabp_api_dorequest('GET', '/catalog/v4/search', '?q=boek&apikey='.$apikey.'&format=xml&offset=0&limit=1&dataoutput=products', '', null);
        if (substr_count($output, "200 OK") > 0) { 
            update_option('yabp_apikey_valid', true); 
            return true;
        }
        else { 
            update_option('yabp_apikey_valid', null); 
            return false;
        }
    }
    else { 
        update_option('yabp_apikey_valid', null); 
        return false;    
    }
}

function yabp_forms() {
    global $wpdb, $yabp_add_item_item_count, $yabp_add_item_item_count_limit, $yabp_itemlist_count, $yabp_styling_item_fontsize_lowlimit, $yabp_styling_item_fontsize_highlimit, $yabp_item_textlink_text;
    
    if (isset($_POST['savetype']) && $_POST['savetype'] == 'saveoptions_yabp_options') {

        if (empty($_POST['yabp_apikey'])) { $_POST['yabp_apikey'] = null; }
        if ($_POST['yabp_apikey'] != get_option('yabp_apikey') || !get_option('yabp_apikey_valid')) { yabp_validate_apikey(trim($_POST['yabp_apikey'])); }
        update_option('yabp_apikey', trim($_POST['yabp_apikey']));
        if (!is_numeric($_POST['yabp_siteid'])) { $_POST['yabp_siteid'] = null; }
        update_option('yabp_siteid', trim($_POST['yabp_siteid']));

        if (!is_numeric($_POST['yabp_add_item_item_count']) || $_POST['yabp_add_item_item_count'] > $yabp_add_item_item_count_limit) { $_POST['yabp_add_item_item_count'] = $yabp_add_item_item_count; }
        else { $_POST['yabp_add_item_item_count'] = (int) $_POST['yabp_add_item_item_count']; }
        update_option('yabp_add_item_item_count', $_POST['yabp_add_item_item_count']);
        
        if (!is_numeric($_POST['yabp_itemlist_count'])) { $_POST['yabp_itemlist_count'] = $yabp_itemlist_count; }
        else { $_POST['yabp_itemlist_count'] = (int) $_POST['yabp_itemlist_count']; }        
        update_option('yabp_itemlist_count', $_POST['yabp_itemlist_count']);

        if (empty($_POST['yabp_item_textlink_text'])) { $_POST['yabp_item_textlink_text'] = $yabp_item_textlink_text; }
        update_option('yabp_item_textlink_text', $_POST['yabp_item_textlink_text']);

        if (isset($_POST['yabp_item_getimpressions'])) { update_option('yabp_item_getimpressions', '1'); }
        elseif (!isset($_POST['yabp_item_getimpressions'])) { update_option('yabp_item_getimpressions', '0'); }
        
        if (!is_numeric($_POST['yabp_styling_item_title_fontsize']) || $_POST['yabp_styling_item_title_fontsize'] < $yabp_styling_item_fontsize_lowlimit || $_POST['yabp_styling_item_title_fontsize'] > $yabp_styling_item_fontsize_highlimit) { $_POST['yabp_styling_item_title_fontsize'] = null; }
        else { $_POST['yabp_styling_item_title_fontsize'] = (int) $_POST['yabp_styling_item_title_fontsize']; }
        update_option('yabp_styling_item_title_fontsize', $_POST['yabp_styling_item_title_fontsize']);
        if (!is_numeric($_POST['yabp_styling_item_subtitle_fontsize']) || $_POST['yabp_styling_item_subtitle_fontsize'] < $yabp_styling_item_fontsize_lowlimit || $_POST['yabp_styling_item_subtitle_fontsize'] > $yabp_styling_item_fontsize_highlimit) { $_POST['yabp_styling_item_subtitle_fontsize'] = null; }
        else { $_POST['yabp_styling_item_subtitle_fontsize'] = (int) $_POST['yabp_styling_item_subtitle_fontsize']; }
        update_option('yabp_styling_item_subtitle_fontsize', $_POST['yabp_styling_item_subtitle_fontsize']);
        if (!is_numeric($_POST['yabp_styling_item_price_fontsize']) || $_POST['yabp_styling_item_price_fontsize'] < $yabp_styling_item_fontsize_lowlimit || $_POST['yabp_styling_item_price_fontsize'] > $yabp_styling_item_fontsize_highlimit) { $_POST['yabp_styling_item_price_fontsize'] = null; }
        else { $_POST['yabp_styling_item_price_fontsize'] = (int) $_POST['yabp_styling_item_price_fontsize']; }
        update_option('yabp_styling_item_price_fontsize', $_POST['yabp_styling_item_price_fontsize']);
        if (!is_numeric($_POST['yabp_styling_item_listprice_fontsize']) || $_POST['yabp_styling_item_listprice_fontsize'] < $yabp_styling_item_fontsize_lowlimit || $_POST['yabp_styling_item_listprice_fontsize'] > $yabp_styling_item_fontsize_highlimit) { $_POST['yabp_styling_item_listprice_fontsize'] = null; }
        else { $_POST['yabp_styling_item_listprice_fontsize'] = (int) $_POST['yabp_styling_item_listprice_fontsize']; }
        update_option('yabp_styling_item_listprice_fontsize', $_POST['yabp_styling_item_listprice_fontsize']);
        if (!is_numeric($_POST['yabp_styling_item_availability_fontsize']) || $_POST['yabp_styling_item_availability_fontsize'] < $yabp_styling_item_fontsize_lowlimit || $_POST['yabp_styling_item_availability_fontsize'] > $yabp_styling_item_fontsize_highlimit) { $_POST['yabp_styling_item_availability_fontsize'] = null; }
        else { $_POST['yabp_styling_item_availability_fontsize'] = (int) $_POST['yabp_styling_item_availability_fontsize']; }
        update_option('yabp_styling_item_availability_fontsize', $_POST['yabp_styling_item_availability_fontsize']);
        if (!is_numeric($_POST['yabp_styling_item_textlink_fontsize']) || $_POST['yabp_styling_item_textlink_fontsize'] < $yabp_styling_item_fontsize_lowlimit || $_POST['yabp_styling_item_textlink_fontsize'] > $yabp_styling_item_fontsize_highlimit) { $_POST['yabp_styling_item_textlink_fontsize'] = null; }
        else { $_POST['yabp_styling_item_textlink_fontsize'] = (int) $_POST['yabp_styling_item_textlink_fontsize']; }
        update_option('yabp_styling_item_textlink_fontsize', $_POST['yabp_styling_item_textlink_fontsize']);

        if (!preg_match('/#([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?\b/', "#".$_POST['yabp_styling_item_title_fontcolour'])) { $_POST['yabp_styling_item_title_fontcolour'] = null; }
        update_option('yabp_styling_item_title_fontcolour', $_POST['yabp_styling_item_title_fontcolour']);
        if (!preg_match('/#([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?\b/', "#".$_POST['yabp_styling_item_subtitle_fontcolour'])) { $_POST['yabp_styling_item_subtitle_fontcolour'] = null; }
        update_option('yabp_styling_item_subtitle_fontcolour', $_POST['yabp_styling_item_subtitle_fontcolour']);
        if (!preg_match('/#([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?\b/', "#".$_POST['yabp_styling_item_price_fontcolour'])) { $_POST['yabp_styling_item_price_fontcolour'] = null; }
        update_option('yabp_styling_item_price_fontcolour', $_POST['yabp_styling_item_price_fontcolour']);
        if (!preg_match('/#([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?\b/', "#".$_POST['yabp_styling_item_listprice_fontcolour'])) { $_POST['yabp_styling_item_listprice_fontcolour'] = null; }
        update_option('yabp_styling_item_listprice_fontcolour', $_POST['yabp_styling_item_listprice_fontcolour']);
        if (!preg_match('/#([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?\b/', "#".$_POST['yabp_styling_item_availability_fontcolour'])) { $_POST['yabp_styling_item_availability_fontcolour'] = null; }
        update_option('yabp_styling_item_availability_fontcolour', $_POST['yabp_styling_item_availability_fontcolour']);
        if (!preg_match('/#([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?\b/', "#".$_POST['yabp_styling_item_textlink_fontcolour'])) { $_POST['yabp_styling_item_textlink_fontcolour'] = null; }
        update_option('yabp_styling_item_textlink_fontcolour', $_POST['yabp_styling_item_textlink_fontcolour']);
        
        if (isset($_POST['yabp_styling_item_button_usealternative'])) { update_option('yabp_styling_item_button_usealternative', '1'); }
        elseif (!isset($_POST['yabp_styling_item_button_usealternative'])) { update_option('yabp_styling_item_button_usealternative', '0'); }
        if (isset($_POST['yabp_styling_item_button_useviewbutton'])) { update_option('yabp_styling_item_button_useviewbutton', '1'); }
        elseif (!isset($_POST['yabp_styling_item_button_useviewbutton'])) { update_option('yabp_styling_item_button_useviewbutton', '0'); }

        wp_redirect($_SERVER['PHP_SELF'].'?page=yabp&updated=1');

        die('Done');    
    }    
}

function yabp_options() {
    global $wpdb, $yabp_styling_item_fontsize_lowlimit, $yabp_styling_item_fontsize_highlimit, $yabp_open_api_link, $yabp_partnerprogram_link;
?>
    <div class="wrap">
    <h2>Yet Another bol.com Plugin</h2>
    <h3><?php _e('Options', 'yabp'); ?></h3>
    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1) { ?><div style="font-weight: bold; margin-bottom:10px; padding:5px; background:#B9FF9C; border:1px solid #ccc;"><p><?php _e('Options successfully saved.', 'yabp'); ?></p></div><?php } ?>
    <div style="padding:10px; background:#fff; border:1px solid #ccc;">
    <form method="post">                        
        <p><strong><?php _e('Settings', 'yabp'); ?></strong></p>
        <p><?php _e('bol.com Open API key (API Access Key)', 'yabp'); ?>: <input type="text" size="50" value="<?php echo get_option('yabp_apikey')?>" name="yabp_apikey" /> <?php if (get_option('yabp_apikey')) { if (get_option('yabp_apikey_valid')) { ?><span style="color: green;"><?php _e('Valid!', 'yabp'); ?></span><?php } else { ?><span style="color: red;"><?php _e('Invalid!', 'yabp'); ?></span><?php } ?><?php } if (!get_option('yabp_apikey') || !get_option('yabp_apikey_valid')) { ?> <a href="<?php echo $yabp_open_api_link; ?>"><?php _e('Get an Open API key', 'yabp'); ?></a><?php } ?></p>
        <p><?php _e('bol.com Partner Program siteid', 'yabp'); ?>: <input type="text" size="50" value="<?php echo get_option('yabp_siteid')?>" name="yabp_siteid" /><?php if (!get_option('yabp_siteid') || get_option('yabp_siteid') == "") { ?> <a href="<?php echo $yabp_partnerprogram_link; ?>"><?php _e('Retrieve your SiteId', 'yabp'); ?></a><?php } ?></p>
        <p><?php _e('Number of products shown on the \'Add product\'-page', 'yabp'); ?>: <input type="text" maxlength="3" size="3" value="<?php echo get_option('yabp_add_item_item_count')?>" name="yabp_add_item_item_count" /></p>
        <p><?php _e('Number of products shown on the \'Product list\'-page', 'yabp'); ?>: <input type="text" maxlength="3" size="3" value="<?php echo get_option('yabp_itemlist_count')?>" name="yabp_itemlist_count" /></p>
        <p><input type="checkbox" name="yabp_item_getimpressions" id="yabp_item_getimpressions" <?php if (get_option('yabp_item_getimpressions') == 1) { ?>checked <?php } ?>/> <label for="yabp_item_getimpressions"><?php _e('Record all impressions of your products in the bol.com Partner Program.', 'yabp'); ?></label></p>
        <p><?php _e('Text of the text link of the products', 'yabp'); ?>: <input type="text" maxlength="50" size="50" value="<?php echo get_option('yabp_item_textlink_text')?>" name="yabp_item_textlink_text" /></p>
        <p>&nbsp;</p>
        <p><strong><?php _e('Styling' , 'yabp'); ?></strong></p>
        <p style="font-style: italic;"><?php _e('The use of styling is optional. By default, the plugin uses the style from your current theme. When inserting font sizes and font colours below, you override the default style. The font size is the number in pixels, and the colours in hex code (eg. FF0000). You can pick hex codes by clicking on the input field. To delete a hex code, backspace it in its field. Don\'t forget to save!', 'yabp'); ?></p>
        <table>
            <tr><td><?php _e('Product title font size', 'yabp'); ?>:</td><td><input type="text" maxlength="2" size="2" value="<?php echo get_option('yabp_styling_item_title_fontsize')?>" name="yabp_styling_item_title_fontsize" /> <sub>(<?php echo $yabp_styling_item_fontsize_lowlimit."-".$yabp_styling_item_fontsize_highlimit; ?>)</sub></td></tr>
            <tr><td><?php _e('Product title font colour', 'yabp'); ?>:</td><td><input class="color {required:false,pickerClosable:true,pickerCloseText:'<?php _e('Close', 'yabp'); ?>'}" type="text" maxlength="6" size="6" value="<?php echo get_option('yabp_styling_item_title_fontcolour')?>" name="yabp_styling_item_title_fontcolour" /></td></tr>
            <tr><td><?php _e('Product subtitle font size', 'yabp'); ?>:</td><td><input type="text" maxlength="2" size="2" value="<?php echo get_option('yabp_styling_item_subtitle_fontsize')?>" name="yabp_styling_item_subtitle_fontsize" /> <sub>(<?php echo $yabp_styling_item_fontsize_lowlimit."-".$yabp_styling_item_fontsize_highlimit; ?>)</sub></td></tr>
            <tr><td><?php _e('Product subtitle font colour', 'yabp'); ?>:</td><td><input class="color {required:false,pickerClosable:true,pickerCloseText:'<?php _e('Close', 'yabp'); ?>'}" type="text" maxlength="6" size="6" value="<?php echo get_option('yabp_styling_item_subtitle_fontcolour')?>" name="yabp_styling_item_subtitle_fontcolour" /></td></tr>
            <tr><td><?php _e('Product price font size', 'yabp'); ?>:</td><td><input type="text" maxlength="2" size="2" value="<?php echo get_option('yabp_styling_item_price_fontsize')?>" name="yabp_styling_item_price_fontsize" /> <sub>(<?php echo $yabp_styling_item_fontsize_lowlimit."-".$yabp_styling_item_fontsize_highlimit; ?>)</sub></td></tr>
            <tr><td><?php _e('Product price font colour', 'yabp'); ?>:</td><td><input class="color {required:false,pickerClosable:true,pickerCloseText:'<?php _e('Close', 'yabp'); ?>'}" type="text" maxlength="6" size="6" value="<?php echo get_option('yabp_styling_item_price_fontcolour')?>" name="yabp_styling_item_price_fontcolour" /></td></tr>
            <tr><td><?php _e('Product list price font size', 'yabp'); ?>:</td><td><input type="text" maxlength="2" size="2" value="<?php echo get_option('yabp_styling_item_listprice_fontsize')?>" name="yabp_styling_item_listprice_fontsize" /> <sub>(<?php echo $yabp_styling_item_fontsize_lowlimit."-".$yabp_styling_item_fontsize_highlimit; ?>)</sub></td></tr>
            <tr><td><?php _e('Product list price font colour', 'yabp'); ?>:</td><td><input class="color {required:false,pickerClosable:true,pickerCloseText:'<?php _e('Close', 'yabp'); ?>'}" type="text" maxlength="6" size="6" value="<?php echo get_option('yabp_styling_item_listprice_fontcolour')?>" name="yabp_styling_item_listprice_fontcolour" /></td></tr>
            <tr><td><?php _e('Product availability font size', 'yabp'); ?>:</td><td><input type="text" maxlength="2" size="2" value="<?php echo get_option('yabp_styling_item_availability_fontsize')?>" name="yabp_styling_item_availability_fontsize" /> <sub>(<?php echo $yabp_styling_item_fontsize_lowlimit."-".$yabp_styling_item_fontsize_highlimit; ?>)</sub></td></tr>
            <tr><td><?php _e('Product availability font colour', 'yabp'); ?>:</td><td><input class="color {required:false,pickerClosable:true,pickerCloseText:'<?php _e('Close', 'yabp'); ?>'}" type="text" maxlength="6" size="6" value="<?php echo get_option('yabp_styling_item_availability_fontcolour')?>" name="yabp_styling_item_availability_fontcolour" /></td></tr>
            <tr><td><?php _e('Product text link font size', 'yabp'); ?>:</td><td><input type="text" maxlength="2" size="2" value="<?php echo get_option('yabp_styling_item_textlink_fontsize')?>" name="yabp_styling_item_textlink_fontsize" /> <sub>(<?php echo $yabp_styling_item_fontsize_lowlimit."-".$yabp_styling_item_fontsize_highlimit; ?>)</sub></td></tr>
            <tr><td><?php _e('Product text link font colour', 'yabp'); ?>:</td><td><input class="color {required:false,pickerClosable:true,pickerCloseText:'<?php _e('Close', 'yabp'); ?>'}" type="text" maxlength="6" size="6" value="<?php echo get_option('yabp_styling_item_textlink_fontcolour')?>" name="yabp_styling_item_textlink_fontcolour" /></td></tr>
        </table>
        <p><input type="checkbox" name="yabp_styling_item_button_usealternative" id="yabp_styling_item_button_usealternative" <?php if (get_option('yabp_styling_item_button_usealternative') == 1) { ?>checked <?php } ?>/> <label for="yabp_styling_item_button_usealternative"><?php _e('Use the alternative lighter coloured bol.com button.', 'yabp'); ?></label></p>
        <p><input type="checkbox" name="yabp_styling_item_button_useviewbutton" id="yabp_styling_item_button_useviewbutton" <?php if (get_option('yabp_styling_item_button_useviewbutton') == 1) { ?>checked <?php } ?>/> <label for="yabp_styling_item_button_useviewbutton"><?php _e('Use the \'View at bol.com\' button.', 'yabp'); ?></label></p>
        <p class="submit">
            <input type="hidden" name="savetype" value="saveoptions_yabp_options" />
            <input class="button-primary" name="save" type="submit" value="<?php _e('Save', 'yabp'); ?>" />
            <input type="hidden" name="action" value="save" />
        </p>
    </form>
    </div>
    <p>&nbsp;</p>
    <h3><?php _e('Message from the developer' , 'yabp'); ?></h3>
    <div style="padding:10px; background:#fff; border:1px solid #ccc;">
        <p><?php _e('Thank you for using this plugin! To support the developer for future development of it, a donation of any amount is really appreciated. If at any moment a pro version of this plugin is released, all supporters will get access to that version!' , 'yabp'); ?><br />
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="Z5Y8SDPMQK36A">
                <input type="image" src="https://www.paypalobjects.com/nl_NL/NL/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal, de veilige en complete manier van online betalen.">
                <img alt="" border="0" src="https://www.paypalobjects.com/nl_NL/i/scr/pixel.gif" width="1" height="1" />
            </form>
        </p>
        <p><?php _e('Take a look at the <a href="https://wordpress.org/plugins/yabp/">Plugin page on WordPress.org</a> for a step-by-step installation description and the FAQ. You may also find more information on <a href="http://tromit.nl/diensten/wordpress-plugins/">the homepage of the plugin</a>.' , 'yabp'); ?></p>
        <p><?php _e('This is not an official plugin from bol.com, but it is safe to use as bol.com\'s Open API v4 is being used. No personal data is saved or forwarded. The names and images in this plugin belong to their respective owners. By using this plugin, you agree to the following terms and conditions. The developer is not responsible for any errors or losses when using this plugin for earning money with the bol.com Partner Program. Your website has to comply with bol.com\'s terms and conditions at all times. You are responsible you use the correct Open API key and siteid with this plugin. At the moment, only the siteid cannot be checked automatically for its correctness.' , 'yabp'); ?></p>
    </div>

<?php
}

function yabp_add_item_new($bolid, $check=false, $returnid=false) {
    if (isset($bolid)) {
        global $wpdb, $table_name_yabp;
        if ($check) { 
            if ($wpdb->get_var("SELECT entry_id FROM `".$table_name_yabp."` WHERE entry_bolid = '".mysql_real_escape_string($bolid)."'")) { return true; }
            else { return false; }
        }
        else {         
            if ($wpdb->query("SELECT entry_id FROM `".$table_name_yabp."` WHERE entry_bolid = '".mysql_real_escape_string($bolid)."'")) { return false; }
            else { 
                if ($returnid) {
                    $wpdb->query("INSERT INTO `".$table_name_yabp."` (entry_id, entry_bolid, entry_thumb, entry_showthumb, entry_showprice, entry_showlistprice, entry_showtitle, entry_showsubtitle, entry_showavailability, entry_showrating, entry_showbutton, entry_updateinterval) VALUES ('', '".mysql_real_escape_string($bolid)."', '3', '1', '1', '1', '1', '1', '1', '1', '1', '3')");
                    return mysql_insert_id();                    
                }
                else { return $wpdb->query("INSERT INTO `".$table_name_yabp."` (entry_id, entry_bolid, entry_thumb, entry_showthumb, entry_showprice, entry_showlistprice, entry_showtitle, entry_showsubtitle, entry_showavailability, entry_showrating, entry_showbutton, entry_updateinterval) VALUES ('', '".mysql_real_escape_string($bolid)."', '3', '1', '1', '1', '1', '1', '1', '1', '1', '3')"); }                
            }
        }
    }
    else { return false; }
}

function yabp_cron_updateinterval_check_number($interval) {
    global $wpdb, $table_name_yabp;

    if (isset($interval) && is_numeric($interval)) {
        if ($wpdb->get_var("SELECT entry_id FROM `".$table_name_yabp."` WHERE entry_updateinterval = '".mysql_real_escape_string($interval)."'")) { return $wpdb->get_var("SELECT COUNT(entry_id) FROM `".$table_name_yabp."` WHERE entry_updateinterval = '".mysql_real_escape_string($interval)."'"); }
        else { return false; }        
    }
    else { return false; }
}

function yabp_entry_bolid_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 
        if ($wpdb->get_var("SELECT entry_bolid FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'")) { return $wpdb->get_var("SELECT entry_bolid FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'"); }
        else { return false; }                
    }
    else { return false; }
}

function yabp_entry_updateinterval_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 
        if ($wpdb->get_var("SELECT entry_updateinterval FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'")) { return $wpdb->get_var("SELECT entry_updateinterval FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'"); }
        else { return false; }                
    }
    else { return false; }
}

function yabp_entry_thumbsize_via_entry_id($entry_id, $backend=false) {
    global $wpdb, $table_name_yabp;
    
    if (isset($entry_id) && is_numeric($entry_id)) {
        
        if ($backend) {
            if ($wpdb->get_var("SELECT entry_thumb FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'")) {
                $thumb_size = $wpdb->get_var("SELECT entry_thumb FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'");
                switch ($thumb_size) {
                    case 1:
                        return "item_xsthumb";
                    case 2:
                        return "item_sthumb";
                    case 3:
                        return "item_mthumb";
                    case 4:
                        return "item_lthumb";
                    case 5:
                        return "item_xlthumb";
                }
                return false;
            }
            else { return false; }                
        }
        else {
            if ($wpdb->get_var("SELECT entry_thumb FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'")) { return $wpdb->get_var("SELECT entry_thumb FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'"); }
            else { return false; }                
        }
    }
    else { return false; }
}

function yabp_entry_showthumb_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 
        if ($wpdb->get_var("SELECT entry_showthumb FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'") == 1) { return true; }
        else { return false; }                
    }
    else { return false; }
}

function yabp_entry_showprice_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 
        if ($wpdb->get_var("SELECT entry_showprice FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'") == 1) { return true; }
        else { return false; }                
    }
    else { return false; }
}

function yabp_entry_showlistprice_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 
        if ($wpdb->get_var("SELECT entry_showlistprice FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'") == 1) { return true; }
        else { return false; }                
    }
    else { return false; }
}

function yabp_entry_showtitle_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 
        if ($wpdb->get_var("SELECT entry_showtitle FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'") == 1) { return true; }
        else { return false; }                
    }
    else { return false; }
}

function yabp_entry_showsubtitle_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 
        if ($wpdb->get_var("SELECT entry_showsubtitle FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'") == 1) { return true; }
        else { return false; }                
    }
    else { return false; }
}

function yabp_entry_showavailability_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 
        if ($wpdb->get_var("SELECT entry_showavailability FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'") == 1) { return true; }
        else { return false; }                
    }
    else { return false; }
}

function yabp_entry_showrating_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 
        if ($wpdb->get_var("SELECT entry_showrating FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'") == 1) { return true; }
        else { return false; }                
    }
    else { return false; }
}

function yabp_entry_showbutton_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 
        if ($wpdb->get_var("SELECT entry_showbutton FROM `".$table_name_yabp."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'") == 1) { return true; }
        else { return false; }                
    }
    else { return false; }
}

function yabp_item_title_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp_items;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 
        if ($wpdb->get_var("SELECT item_title FROM `".$table_name_yabp_items."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'")) { return $wpdb->get_var("SELECT item_title FROM `".$table_name_yabp_items."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'"); }
        else { return false; }                
    }
    else { return false; }
}

function yabp_item_value_via_column_name($entry_id, $column_name) {
    global $wpdb, $table_name_yabp_items;
    
    if (isset($entry_id) && is_numeric($entry_id) && isset($column_name) && ($column_name == 'item_title' || $column_name == 'item_subtitle' || $column_name == 'item_externalurl' || $column_name == 'item_afflink' || $column_name == 'item_xlthumb' || $column_name == 'item_lthumb' || $column_name == 'item_mthumb' || $column_name == 'item_sthumb' || $column_name == 'item_xsthumb' || $column_name == 'item_price' || $column_name == 'item_listprice' || $column_name == 'item_availability' || $column_name == 'item_availabilitycode' || $column_name == 'item_rating' || $column_name == 'item_ratingspan' || $column_name == 'time')) {
        if ($wpdb->get_var("SELECT ".mysql_real_escape_string($column_name)." FROM `".$table_name_yabp_items."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'")) { return $wpdb->get_var("SELECT ".mysql_real_escape_string($column_name)." FROM `".$table_name_yabp_items."` WHERE entry_id = '".mysql_real_escape_string($entry_id)."'"); }
        else { return false; }                
    }
    else { return false; }
}

function yabp_item_update_via_entry_id($entry_id) {
    global $wpdb, $table_name_yabp_items, $yabp_partnerlink_prefix;
    
    if (isset($entry_id) && is_numeric($entry_id)) { 

        if ((!get_option('yabp_apikey') || !get_option('yabp_siteid')) || !get_option('yabp_apikey_valid') || get_option('yabp_siteid') == "") { return false; }

        $bolid = yabp_entry_bolid_via_entry_id($entry_id);        
        $output = yabp_api_dorequest('GET', '/catalog/v4/products/'.$bolid, '?apikey=' . get_option('yabp_apikey') . '&format=xml&includeattributes=true', '', null);

        if (substr_count($output, "200 OK") > 0) {
            
            $xml = strstr($output, '<?xml');
            $phpobject = simplexml_load_string($xml);

            $i = 0;                        
            foreach ($phpobject->Products as $item) {
                
                $number++;                
                //useful data               
                $item_title = $item -> Title;
                $item_subtitle = $item -> Subtitle;
                $item_externalurl = $item -> Urls[0] -> Value;
                //$item_afflink
                $item_xlthumb = preg_replace("/^http:/i", "https:", $item -> Images[4]-> Url);
                $item_lthumb = preg_replace("/^http:/i", "https:", $item -> Images[3]-> Url);
                $item_mthumb = preg_replace("/^http:/i", "https:", $item -> Images[2]-> Url);
                $item_sthumb = preg_replace("/^http:/i", "https:", $item -> Images[1]-> Url);
                $item_xsthumb = preg_replace("/^http:/i", "https:", $item -> Images[0]-> Url);
                $item_price = doubleval($item -> OfferData -> Offers[0] -> Price);
                $item_listprice = doubleval($item -> OfferData -> Offers[0] -> ListPrice);
                $item_availability = $item -> OfferData -> Offers[0] -> AvailabilityDescription;
                $item_availabilitycode = $item -> OfferData -> Offers[0] -> AvailabilityCode;
                $item_rating = $item -> Rating;
                //$item_ratingspan
                $time = time();

                if (@GetImageSize($item_sthumb)) { } 
                else { $item_sthumb = "http://www.bol.com/nl/static/images/main/noimage_124x100default.gif"; }

                if ($item_rating != "") {
                    $nicerating = substr($item_rating, 0, 1);
                    $countrating = strlen($item_rating);
                    if ($countrating < 2) { $nicerating .= "_0"; } 
                    else { $nicerating .= '_' . substr($item_rating, -1); }
                    $altrating = str_replace("_", ".", $nicerating);
                    $item_ratingspan = '<span class="rating"><img alt="'.sprintf(__('Score %1$.1f out of 5 stars.', 'yabp'), $altrating).'" title="'.sprintf(__('Score %1$.1f out of 5 stars.', 'yabp'), $altrating).'" src="http://review.bol.com/7628-nl_nl/' . $nicerating . '/5/rating.gif"></span>';
                } 
                else { $item_ratingspan = ''; }
            }
        }
        else { return false; }

        if (substr_count($output, "404 Not Found") > 0 || substr_count($output, "403 Forbidden") > 0 || substr_count($output, "500 Internal Server Error") > 0 || substr_count($output, "405 Method Not Allowed") > 0 || substr_count($output, "400 Bad Request") > 0) { return false; }
                
        $item_afflink = $yabp_partnerlink_prefix.get_option('yabp_siteid')."&amp;f=TXL&amp;url=".urlencode($item_externalurl)."&amp;name=".urlencode(strtolower($item_title));
        
        if (yabp_item_title_via_entry_id($entry_id)) {            
            return $wpdb->query("UPDATE `".$table_name_yabp_items."` SET item_title = '".$item_title."', item_subtitle = '".$item_subtitle."', item_externalurl = '".$item_externalurl."', item_afflink = '".$item_afflink."', item_xlthumb = '".$item_xlthumb."', item_lthumb = '".$item_lthumb."', item_mthumb = '".$item_mthumb."', item_sthumb = '".$item_sthumb."', item_xsthumb = '".$item_xsthumb."', item_price = '".$item_price."', item_listprice = '".$item_listprice."', item_availability = '".$item_availability."', item_availabilitycode = '".$item_availabilitycode."', item_rating = '".$item_rating."', item_ratingspan = '".$item_ratingspan."', time = '".$time."' WHERE entry_id = '".mysql_real_escape_string($entry_id)."'");
        }
        else {
            return $wpdb->query("INSERT INTO `".$table_name_yabp_items."` (item_id, entry_id, item_title, item_subtitle, item_externalurl, item_afflink, item_xlthumb, item_lthumb, item_mthumb, item_sthumb, item_xsthumb, item_price, item_listprice, item_availability, item_availabilitycode, item_rating, item_ratingspan, time) VALUES ('', '".mysql_real_escape_string($entry_id)."', '".mysql_real_escape_string($item_title)."', '".mysql_real_escape_string($item_subtitle)."', '".mysql_real_escape_string($item_externalurl)."', '".mysql_real_escape_string($item_afflink)."', '".mysql_real_escape_string($item_xlthumb)."', '".mysql_real_escape_string($item_lthumb)."', '".mysql_real_escape_string($item_mthumb)."', '".mysql_real_escape_string($item_sthumb)."', '".mysql_real_escape_string($item_xsthumb)."', '".mysql_real_escape_string($item_price)."', '".mysql_real_escape_string($item_listprice)."', '".mysql_real_escape_string($item_availability)."', '".mysql_real_escape_string($item_availabilitycode)."', '".mysql_real_escape_string($item_rating)."', '".mysql_real_escape_string($item_ratingspan)."', '".mysql_real_escape_string($time)."')");
        }        
    }
    else { return false; }
}

function yabp_format_price($price) {
    if (isset($price) && is_numeric($price)) {
        
        if ($price == 0) { return __('Not available', 'yabp'); }
        
        if (substr_count($price, ".") < 1) { return "&euro;".$price.",-"; }
        else { 
            if (strlen(str_replace(".", "", strstr($price, "."))) == 1) { return "&euro;".str_replace(".", ",", $price)."0"; }
            else {
                return "&euro;".str_replace(".", ",", $price);
            }
        }
    }
    else { return false; }
}

function yabp_format_shortcode($entry_id) {
    global $yabp_item_shortcode_format;
    
    if (isset($entry_id) && is_numeric($entry_id)) {
        return str_replace("%entry_id%", $entry_id, $yabp_item_shortcode_format);
    }
    else { return false; }
}

function yabp_format_time($time) {
    global $yabp_item_time_format;
    
    if (isset($time) && is_numeric($time)) {
        return date($yabp_item_time_format, ($time+(get_option('gmt_offset') * 3600)));
    }
    else { return false; }
}

function yabp_format_updateinterval($updateinterval, $reverse=false, $backend=false, $cronfunction=false) {
    if (isset($updateinterval)) {
        if ($cronfunction) {
            switch ($updateinterval) {
                case 1:
                    return "yabp_cron_event_hourly";
                case 2:
                    return "yabp_cron_event_twicedaily";
                case 3:
                    return "yabp_cron_event_daily";
            }
            return false;
        }
        elseif ($backend) {
            switch ($updateinterval) {
                case 1:
                    return "hourly";
                case 2:
                    return "twicedaily";
                case 3:
                    return "daily";
            }
            return false;
        }
        elseif ($reverse) {
            $updateinterval = trim($updateinterval);
            switch ($updateinterval) {
                case __('hourly', 'yabp'):
                    return 1;
                case __('twice a day', 'yabp'):
                    return 2;
                case __('daily', 'yabp'):
                    return 3;
            }
            return false;
        }
        else {        
            switch ($updateinterval) {
                case 1:
                    return __('hourly', 'yabp');
                case 2:
                    return __('twice a day', 'yabp');
                case 3:
                    return __('daily', 'yabp');
            }
            return false;
        }
    }
    else { return false; }    
}

function yabp_format_thumbsize($thumbsize, $reverse=false) {
    if (isset($thumbsize)) {
        if ($reverse) {
            $thumbsize = trim($thumbsize);
            switch ($thumbsize) {
                case "XS":
                    return 1;
                case "S":
                    return 2;
                case "M":
                    return 3;
                case "L":
                    return 4;
                case "XL":
                    return 5;
            }
            return 3;
        }
        else {        
            switch ($thumbsize) {
                case 1:
                    return "XS";
                case 2:
                    return "S";
                case 3:
                    return "M";
                case 4:
                    return "L";
                case 5:
                    return "XL";
            }
            return false;
        }
    }
    else { return false; }    
}

function yabp_add_item() {
    global $wpdb, $yabp_add_item_item_count;
    
    if (!get_option('yabp_add_item_item_count')) { $item_count = $yabp_add_item_item_count; }
    else { $item_count = get_option('yabp_add_item_item_count'); }

    ?>
    <div class="wrap">
    <h2>Yet Another bol.com Plugin</h2>
    <h3><?php _e('Add product', 'yabp'); ?></h3>
    <?php    
    
    if ((!get_option('yabp_apikey') || !get_option('yabp_siteid')) || !get_option('yabp_apikey_valid') || get_option('yabp_siteid') == "") { ?><p><?php printf(__('Please enter both your valid bol.com Open API key and siteid at the <a href="%s">Options page</a> to continue.', 'yabp'), $_SERVER['PHP_SELF'].'?page=yabp'); ?></p><?php }
    else {                
        if (isset($_POST['yabp_add_items_reset'])) { unset($_POST['yabp_add_items_submit']); }
        elseif (isset($_POST['yabp_add_items_submit']) && !empty($_POST['yabp_add_items_array'])) {
            $i = 0;
            foreach($_POST['yabp_add_items_array'] as $add_item) {
                $trytoadd = yabp_add_item_new($add_item,false,true);
                if ($trytoadd) { 
                    yabp_item_update_via_entry_id($trytoadd);
                    $i++;                         
                }
            }
            if (yabp_cron_updateinterval_check_number(3) == 1) { yabp_cron_handle_eventstatus(3, true); }
            if ($i > 0) { echo '<div style="font-weight: bold; margin-bottom:10px; padding:5px; background:#B9FF9C; border:1px solid #ccc;"><p>'.printf(__('%1$d product(s) successfully added. <a href="%2$s">Click here</a> to retrieve the shortcodes.', 'yabp'), $i, $_SERVER['PHP_SELF'].'?page=yabp-itemlist').'</p></div>'; }
            else { echo '<div style="font-weight: bold; margin-bottom:10px; padding:5px; background:#FFBDB0; border:1px solid #ccc;"><p>'.__('The selected products are already found in the database, or an error occured. If you did not select products twice, please try again later.', 'yabp').'</p></div>'; }
        }                       
        elseif (isset($_POST['yabp_add_item_searchterm_submit']) && !empty($_POST['yabp_add_item_searchterm'])) {        
            $retry = false;
            $keyword = wp_strip_all_tags($_POST['yabp_add_item_searchterm']);
            $output = yabp_api_dorequest('GET', '/catalog/v4/search', '?q=' . urlencode($keyword) . '&apikey=' . get_option('yabp_apikey') . '&format=xml&offset=0&limit='.$item_count.'&includeattributes=true&dataoutput=products', '', null);

            if (substr_count($output, "200 OK") > 0) {
                $xml = strstr($output, '<?xml');
                $phpobject = simplexml_load_string($xml);
                $totalresults = $phpobject->TotalResultSize;
            
                if ($totalresults == 0) { $summary = sprintf(__('No products found for search term \'%s\'. Please use another search term.', 'yabp'), $keyword); $retry = true; $error = true; }
                elseif ($totalresults == 1) { $summary = sprintf(__('Displaying the first and only result for search term \'%s\'.', 'yabp'), $keyword); }
                else { $summary = sprintf(__('Displaying the first %1$d of total %2$d results for search term \'%3$s\'.', 'yabp'), count($phpobject->Products), $totalresults, $keyword); }
                $i = 0;
                        
                foreach ($phpobject->Products as $item) {                
                    $id = $item -> Id;
                    $thumbnailurl =  preg_replace("/^http:/i", "https:", $item -> Images[1]-> Url);
                    $title = $item -> Title;
                    $rating = $item -> Rating;
                    $price = doubleval($item -> OfferData -> Offers[0] -> Price);
                    $listprice = doubleval($item -> OfferData -> Offers[0] -> ListPrice);
                    $externalurl = $item -> Urls[0] -> Value;
                    $number++;                    

                    if (@GetImageSize($thumbnailurl)) { }
                    else { $thumbnailurl = "http://www.bol.com/nl/static/images/main/noimage_124x100default.gif"; }

                    if ($rating != "") {
                        $nicerating = substr($rating, 0, 1);
                        $countrating = strlen($rating);
                        if ($countrating < 2) { $nicerating .= "_0"; }                                                                                                                                                                                                      
                        else { $nicerating .= '_' . substr($rating, -1); }
                        $altrating = str_replace("_", ".", $nicerating);
                        $ratingspan = '<span class="rating"><img alt="'.sprintf(__('Score %1$.1f out of 5 stars.', 'yabp'), $altrating).'" title="'.sprintf(__('Score %1$.1f out of 5 stars.', 'yabp'), $altrating).'" src="http://review.bol.com/7628-nl_nl/' . $nicerating . '/5/rating.gif"></span>';
                    } 
                    else { $ratingspan = ''; }
                
                    if ($number == count($phpobject->Products)) { $resultlist .= '<tr><td><input type="checkbox" name="yabp_add_items_array[]" value="'.$id.'"'.(yabp_add_item_new($id, true)?' disabled="disabled" /><br /><br />('.__('product already in database', 'yabp').')':' />').'</td><td><a href="'.$externalurl.'"><img alt="'.$title.'" title="'.$title.'" src="'.$thumbnailurl.'" /></a></td><td><a href="'.$externalurl.'">'.$title.'</a><br /><br />(ID: '.$id.')</td><td>'.($listprice>0?'<span style="text-decoration: line-through;">'.yabp_format_price($listprice).'</span> ':'').yabp_format_price($price).'</td><td>' . $ratingspan . '</td></tr>'."\n"; }
                    else { $resultlist .= '<tr><td style="border-bottom: 1px solid grey;"><input type="checkbox" name="yabp_add_items_array[]" value="'.$id.'"'.(yabp_add_item_new($id, true)?' disabled="disabled" /><br /><br />('.__('product already in database', 'yabp').')':' />').'</td><td style="border-bottom: 1px solid grey;"><a href="'.$externalurl.'"><img alt="'.$title.'" title="'.$title.'" src="'.$thumbnailurl.'" /></a></td><td style="border-bottom: 1px solid grey;"><a href="'.$externalurl.'">'.$title.'</a><br /><br />(ID: '.$id.')</td><td style="border-bottom: 1px solid grey;">'.($listprice>0?'<span style="text-decoration: line-through;">'.yabp_format_price($listprice).'</span> ':'').yabp_format_price($price).'</td><td style="border-bottom: 1px solid grey;">'.$ratingspan.'</td></tr>'."\n"; }
                }
            }
            elseif (substr_count($output, "403 Forbidden") > 0) { $summary .= sprintf(__('Your bol.com Open API key is invalid. Please enter the correct one at the <a href="%s">Options page</a>.', 'yabp'), $_SERVER['PHP_SELF'].'?page=yabp'); update_option('yabp_apikey_valid', null); $error = true; }
            elseif (substr_count($output, "500 Internal Server Error") > 0 || substr_count($output, "503 Service Unavailable") > 0) { $summary .= __('An error occured. At the moment, the Open API is not working. Please try again later.', 'yabp'); $error = true; }
            elseif (substr_count($output, "400 Bad Request") > 0 || substr_count($output, "405 Method Not Allowed") > 0) { $summary .= __('The plugin cannot connect to the Open API at the moment. Please contact the developer of this plugin to fix this problem.', 'yabp'); $error = true; }
            elseif (substr_count($output, "404 Not Found") > 0) { $summary .= __('No products can be found. Please use another search term.', 'yabp'); $error = true; }
        
            if ($error) { ?><div style="font-weight: bold; margin-bottom:10px; padding:5px; background:#FFBDB0; border:1px solid #ccc;"><p><?php echo $summary; ?></p></div><?php }
            else { ?><div style="font-weight: bold; margin-bottom:10px; padding:5px; background:#B9FF9C; border:1px solid #ccc;"><p><?php echo $summary; ?></p></div><?php }
        
            if (isset($resultlist)) {
            ?>                            
            <form method="post">
                <table class="widefat comments fixed" cellspacing="0">
                <thead><tr><th></th><th><?php _e('Thumbnail', 'yabp'); ?></th><th><?php _e('Title', 'yabp'); ?></th><th><?php _e('Price', 'yabp'); ?></th><th><?php _e('Rating', 'yabp'); ?></th></tr></thead>
                <tbody>
                    <?php echo $resultlist; ?>
                </tbody>
                </table>            
                <p class="submit">
                    <input class="button-primary" name="yabp_add_items_submit" type="submit" value="<?php _e('Add selected', 'yabp'); ?>" />
                    <input class="button-secondary" name="yabp_add_items_reset" type="submit" value="<?php _e('Edit search terms', 'yabp'); ?>" />
                    <input type="hidden" name="yabp_add_items_previoussearchterm" value="<?php echo $keyword; ?>" />
                </p>
            </form>
            <?php
            }        
        }        
        if ((!isset($_POST['yabp_add_item_searchterm_submit']) && !isset($_POST['yabp_add_items_submit'])) || (isset($_POST['yabp_add_item_searchterm_submit']) && empty($_POST['yabp_add_item_searchterm'])) || $retry) {        
        ?>
        <?php if (isset($_POST['yabp_add_item_searchterm_submit']) && empty($_POST['yabp_add_item_searchterm'])) { ?><div style="font-weight: bold; margin-bottom:10px; padding:5px; background:#FFBDB0; border:1px solid #ccc;"><p><?php _e('Enter a search term to continue.', 'yabp'); ?></p></div><?php } ?>
        <form method="post">        
            <p><?php _e('Search terms', 'yabp'); ?>: <input type="text" size="80" name="yabp_add_item_searchterm"<?php if (isset($_POST['yabp_add_items_reset']) && isset($_POST['yabp_add_items_previoussearchterm'])) { ?> value="<?php echo $_POST['yabp_add_items_previoussearchterm']; ?>"<?php } ?> /></p>
            <p class="submit">
                <input class="button-primary" name="yabp_add_item_searchterm_submit" type="submit" value="<?php _e('Search', 'yabp'); ?>" />
            </p>
        </form>
        <?php
        }
    }
}

function yabp_itemlist() {
    global $wpdb, $table_name_yabp, $table_name_yabp_items, $yabp_itemlist_count;
    
    if (!get_option('yabp_itemlist_count')) { $perpage = $yabp_itemlist_count; }
    else { $perpage = get_option('yabp_itemlist_count'); }

    ?>
    <div class="wrap">
    <h2>Yet Another bol.com Plugin</h2>
    <h3><?php _e('Product list', 'yabp'); ?></h3>    
    <?php        
    
    $_page = isset($_GET['p']) && intval($_GET['p']) != '' ? $_GET['p'] : 1;    
    $gettotal = $wpdb->get_row("SELECT COUNT(entry_id) FROM `".$table_name_yabp."`",ARRAY_N);
    $totalpage = ceil( $gettotal[0] / (float) $perpage);
    $limit = ( ($_page-1) * $perpage).','.$perpage;
    
    if ($gettotal[0] == 0) {
        echo '<div style="font-weight: bold; margin-bottom:10px; padding:5px; background:#FFBDB0; border:1px solid #ccc;"><p>'.__('No products can be found.', 'yabp').'</p></div>'."\n";
        return;
    }
            
    if ($_page > $totalpage) {
        echo '<div style="font-weight: bold; margin-bottom:10px; padding:5px; background:#FFBDB0; border:1px solid #ccc;"><p>'.sprintf(__('Invalid page. <a href="%s">Click here</a> to go back.', 'yabp'), $_SERVER['PHP_SELF'].'?page=yabp-itemlist').'</p></div>';
        return;
    }

    $nav = yabp_pagelinks($_SERVER['PHP_SELF'].'?page=yabp-itemlist&amp;p=', 10, $totalpage, $_page);    
    $items_entries = $wpdb->get_results("SELECT entry_id FROM `".$table_name_yabp."` LIMIT ".$limit);
    
    ?>
    <script type="text/javascript" src="<?php echo get_bloginfo('wpurl').'/'.PLUGINDIR.'/'.basename(dirname(__FILE__)).'/'; ?>js/jquery.inplace.js"></script>
    <script type="text/javascript">
        function sure() {
            var ask = confirm('<?php _e('Are you sure?', 'yabp'); ?>');
            if (ask == true) { return true; }
            else { return false; }
        }

        jQuery(document).ready(function() {            
            jQuery(".update_intervals").editInPlace({
                url: "<?php echo $_SERVER['PHP_SELF']?>?page=yabp-itemlist&action=edititemupdateinterval",
                field_type: "select",
                select_options: "<?php _e('hourly', 'yabp'); ?>,<?php _e('twice a day', 'yabp'); ?>,<?php _e('daily', 'yabp'); ?>",
                default_text: "[<?php _e('Click to add', 'yabp'); ?>]",
                select_text: "<?php _e('Choose value', 'yabp'); ?>",
                save_button: '<input type="submit" class="inplace_save" value="<?php _e('Save', 'yabp'); ?>" />',
                cancel_button: '<input type="submit" class="inplace_cancel" value="<?php _e('Cancel', 'yabp'); ?>" />',                
                saving_image: "<?php echo get_bloginfo('wpurl').'/'.PLUGINDIR.'/'.basename(dirname(__FILE__)); ?>/img/loading_small.gif"
              });
            jQuery(".thumb_sizes").editInPlace({
                url: "<?php echo $_SERVER['PHP_SELF']?>?page=yabp-itemlist&action=edititemthumbsize",
                field_type: "select",
                select_options: "XS,S,M,L,XL",
                default_text: "[<?php _e('Click to add', 'yabp'); ?>]",
                select_text: "<?php _e('Choose value', 'yabp'); ?>",
                save_button: '<input type="submit" class="inplace_save" value="<?php _e('Save', 'yabp'); ?>" />',
                cancel_button: '<input type="submit" class="inplace_cancel" value="<?php _e('Cancel', 'yabp'); ?>" />',                
                saving_image: "<?php echo get_bloginfo('wpurl').'/'.PLUGINDIR.'/'.basename(dirname(__FILE__)); ?>/img/ajax_loading_small.gif"
              });
        });
        
    </script>
    <div style="margin-bottom:10px; padding:5px; background:#B5EBFF; border:1px solid #ccc;"><p><?php _e('You can add subids at your links by adding \'subid="your sub id"\' to the shortcodes. For example: [yabp 1 subid="homepage header"]. You can edit the update interval and the thumbnail size by clicking on it\'s current value. Update interval \'daily\' is recommended.', 'yabp'); ?></p></div>
    <table class="widefat comments fixed" cellspacing="0">
        <thead><tr><th># / <?php _e('Shortcode', 'yabp'); ?></th><th><?php _e('Thumbnail', 'yabp'); ?></th><th><?php _e('Title', 'yabp'); ?> / <?php _e('Last update', 'yabp'); ?></th><th><?php _e('Price', 'yabp'); ?> / <?php _e('Rating', 'yabp'); ?></th><th><?php _e('Options', 'yabp'); ?></th></tr></thead>
        <tbody>
    <?php
        $i = 0;
        foreach ($items_entries as $item_entry) {            
            $item = $wpdb->get_row("SELECT * FROM `".$table_name_yabp_items."` WHERE entry_id = '".mysql_real_escape_string($item_entry->entry_id)."'");            
            $i++;
            ?>
            <tr>
                <?php if (!yabp_item_title_via_entry_id($item_entry->entry_id)) { 
                    if ($i == count($items_entries)) { ?><td><?php echo ((($_page-1) * $perpage) + $i); ?></td><td colspan="3"><?php _e('Click \'Update now\' to retrieve this products\'s data.', 'yabp'); ?></td><td><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;action=update_item&amp;entry_id=".$item_entry->entry_id; ?>"><?php _e('Update now', 'yabp'); ?></a><br /><br /><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;action=delete_item&amp;entry_id=".$item_entry->entry_id; ?>" onclick="return sure()"><?php _e('Delete', 'yabp'); ?></a></td><?php }
                    else { ?><td style="border-bottom: 1px solid grey;"><?php echo ((($_page-1) * $perpage) + $i); ?></td><td colspan="3" style="border-bottom: 1px solid grey;"><?php _e('Click \'Update now\' to retrieve this products\'s data.', 'yabp'); ?></td><td style="border-bottom: 1px solid grey;"><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;action=update_item&amp;entry_id=".$item_entry->entry_id; ?>"><?php _e('Update now', 'yabp'); ?></a><br /><br /><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;action=delete_item&amp;entry_id=".$item_entry->entry_id; ?>" onclick="return sure()"><?php _e('Delete', 'yabp'); ?></a></td><?php }
                }
                else { 
                    if ($i == count($items_entries)) { ?><td><?php echo ((($_page-1) * $perpage) + $i); ?><br /><br /><input value="<?php echo yabp_format_shortcode($item->entry_id); ?>" size="<?php echo strlen(yabp_format_shortcode($item->entry_id)); ?>" /></td><td><a href="<?php echo $item->item_externalurl; ?>"><img alt="<?php echo $item->item_title; ?>" title="<?php echo $item->item_title; ?>" src="<?php echo $item->item_mthumb; ?>" /></a></td>
                        <td><a href="<?php echo $item->item_externalurl; ?>"><?php echo $item->item_title; ?></a><br /><br /><?php echo yabp_format_time($item->time); ?></td>
                        <td><?php echo ($item->item_listprice>0?"<span style=\"text-decoration: line-through;\">".yabp_format_price($item->item_listprice)."</span> ":"").yabp_format_price($item->item_price); ?><br /><br /><?php if (empty($item->item_ratingspan)) { _e('Not rated yet', 'yabp'); } else { echo $item->item_ratingspan; } ?></td>
                        <td><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;action=update_item&amp;entry_id=".$item->entry_id; ?>"><?php _e('Update now', 'yabp'); ?></a><br /><br />
                        <?php _e('Update interval', 'yabp'); ?>:<br /><span class="update_intervals" id="update_interval-<?php echo $item->entry_id; ?>" title="<?php _e('Edit value', 'yabp'); ?>"><?php echo yabp_format_updateinterval(yabp_entry_updateinterval_via_entry_id($item->entry_id)); ?></span><br /><br />
                        <?php _e('Thumbnail size', 'yabp'); ?>:<br /><span class="thumb_sizes" id="thumb_size-<?php echo $item->entry_id; ?>" title="<?php _e('Edit value', 'yabp'); ?>"><?php echo yabp_format_thumbsize(yabp_entry_thumbsize_via_entry_id($item->entry_id)); ?></span><br /><br />
                        <a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;action=delete_item&amp;entry_id=".$item->entry_id; ?>" onclick="return sure()"><?php _e('Delete', 'yabp'); ?></a></td>
                        </tr><tr><td colspan="5" style="text-align: center;"><?php if (yabp_entry_showthumb_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showthumb&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the thumbnail', 'yabp'); ?>"><?php _e('Hide thumbnail', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showthumb&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the thumbnail', 'yabp'); ?>"><?php _e('Show thumbnail', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showprice_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showprice&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the price', 'yabp'); ?>"><?php _e('Hide price', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showprice&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the price', 'yabp'); ?>"><?php _e('Show price', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showlistprice_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showlistprice&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the list price', 'yabp'); ?>"><?php _e('Hide list price', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showlistprice&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the list price', 'yabp'); ?>"><?php _e('Show list price', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showtitle_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showtitle&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the title', 'yabp'); ?>"><?php _e('Hide title', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showtitle&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the title', 'yabp'); ?>"><?php _e('Show title', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showsubtitle_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showsubtitle&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the subtitle', 'yabp'); ?>"><?php _e('Hide subtitle', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showsubtitle&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the subtitle', 'yabp'); ?>"><?php _e('Show subtitle', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showavailability_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showavailability&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the availability', 'yabp'); ?>"><?php _e('Hide availability', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showavailability&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the availability', 'yabp'); ?>"><?php _e('Show availability', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showrating_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showrating&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the rating', 'yabp'); ?>"><?php _e('Hide rating', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showrating&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the rating', 'yabp'); ?>"><?php _e('Show rating', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showbutton_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showbutton&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the button', 'yabp'); ?>"><?php _e('Hide button', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showbutton&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the button', 'yabp'); ?>"><?php _e('Show button', 'yabp'); ?></a><?php } ?></td><?php 
                    }
                    else { ?><td><?php echo ((($_page-1) * $perpage) + $i); ?><br /><br /><input value="<?php echo yabp_format_shortcode($item->entry_id); ?>" size="<?php echo strlen(yabp_format_shortcode($item->entry_id)); ?>" /></td><td><a href="<?php echo $item->item_externalurl; ?>"><img alt="<?php echo $item->item_title; ?>" title="<?php echo $item->item_title; ?>" src="<?php echo $item->item_mthumb; ?>" /></a></td>
                        <td><a href="<?php echo $item->item_externalurl; ?>"><?php echo $item->item_title; ?></a><br /><br /><?php echo yabp_format_time($item->time); ?></td>
                        <td><?php echo ($item->item_listprice>0?"<span style=\"text-decoration: line-through;\">".yabp_format_price($item->item_listprice)."</span> ":"").yabp_format_price($item->item_price); ?><br /><br /><?php if (empty($item->item_ratingspan)) { _e('Not rated yet', 'yabp'); } else { echo $item->item_ratingspan; } ?></td>
                        <td><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;action=update_item&amp;entry_id=".$item->entry_id; ?>"><?php _e('Update now', 'yabp'); ?></a><br /><br />
                        <?php _e('Update interval', 'yabp'); ?>:<br /><span class="update_intervals" id="update_interval-<?php echo $item->entry_id; ?>" title="<?php _e('Edit value', 'yabp'); ?>"><?php echo yabp_format_updateinterval(yabp_entry_updateinterval_via_entry_id($item->entry_id)); ?></span><br /><br />
                        <?php _e('Thumbnail size', 'yabp'); ?>:<br /><span class="thumb_sizes" id="thumb_size-<?php echo $item->entry_id; ?>" title="<?php _e('Edit value', 'yabp'); ?>"><?php echo yabp_format_thumbsize(yabp_entry_thumbsize_via_entry_id($item->entry_id)); ?></span><br /><br />
                        <a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;action=delete_item&amp;entry_id=".$item->entry_id; ?>" onclick="return sure()"><?php _e('Delete', 'yabp'); ?></a></td>
                        </tr><tr><td colspan="5" style="border-bottom: 1px solid grey; text-align: center;"><?php if (yabp_entry_showthumb_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showthumb&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the thumbnail', 'yabp'); ?>"><?php _e('Hide thumbnail', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showthumb&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the thumbnail', 'yabp'); ?>"><?php _e('Show thumbnail', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showprice_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showprice&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the price', 'yabp'); ?>"><?php _e('Hide price', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showprice&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the price', 'yabp'); ?>"><?php _e('Show price', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showlistprice_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showlistprice&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the list price', 'yabp'); ?>"><?php _e('Hide list price', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showlistprice&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the list price', 'yabp'); ?>"><?php _e('Show list price', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showtitle_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showtitle&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the title', 'yabp'); ?>"><?php _e('Hide title', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showtitle&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the title', 'yabp'); ?>"><?php _e('Show title', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showsubtitle_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showsubtitle&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the subtitle', 'yabp'); ?>"><?php _e('Hide subtitle', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showsubtitle&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the subtitle', 'yabp'); ?>"><?php _e('Show subtitle', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showavailability_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showavailability&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the availability', 'yabp'); ?>"><?php _e('Hide availability', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showavailability&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the availability', 'yabp'); ?>"><?php _e('Show availability', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showrating_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showrating&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the rating', 'yabp'); ?>"><?php _e('Hide rating', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showrating&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the rating', 'yabp'); ?>"><?php _e('Show rating', 'yabp'); ?></a><?php } ?> | <?php if (yabp_entry_showbutton_via_entry_id($item->entry_id)) { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showbutton&amp;value=false&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to hide the button', 'yabp'); ?>"><?php _e('Hide button', 'yabp'); ?></a><?php } else { ?><a href="<?php echo $_SERVER['PHP_SELF']."?page=yabp-itemlist&amp;do=entry_setup&amp;action=entry_showbutton&amp;value=true&amp;entry_id=".$item->entry_id; ?>" title="<?php _e('Click to show the button', 'yabp'); ?>"><?php _e('Show button', 'yabp'); ?></a><?php } ?></td><?php 
                    }
                }
                ?>
            </tr>
        <?php 
        }        

        if (empty($items_entries)) { ?><tr><td></td><td><?php _e('No products found.', 'yabp'); ?></td></tr><?php }
        ?>
        </tbody>
    </table>        
    <?php
    echo '<br />'.$nav;
}

function yabp_itemlist_init() {
    global $wpdb, $table_name_yabp, $table_name_yabp_items;

    if (is_admin()) {
        if (isset($_GET['page']) && $_GET['page'] == "yabp-itemlist" && isset($_GET['action']) && $_GET['action'] == 'update_item' && isset($_GET['entry_id']) && is_numeric($_GET['entry_id']) && yabp_entry_bolid_via_entry_id($_GET['entry_id'])) {
            $entry_id = (int) $_GET['entry_id'];            
            yabp_item_update_via_entry_id($entry_id);            
            wp_redirect($_SERVER['PHP_SELF'].'?page=yabp-itemlist');        
            die('Done');
        }    
        elseif (isset($_GET['page']) && $_GET['page'] == "yabp-itemlist" && isset($_GET['action']) && $_GET['action'] == 'delete_item' && isset($_GET['entry_id']) && is_numeric($_GET['entry_id']) && yabp_entry_bolid_via_entry_id($_GET['entry_id'])) {
            $entry_id = (int) $_GET['entry_id'];
            
            $oldvalue = yabp_entry_updateinterval_via_entry_id($entry_id);
            
            $wpdb->query("DELETE FROM `".$table_name_yabp."` WHERE entry_id = '".$entry_id."'");
            $wpdb->query("DELETE FROM `".$table_name_yabp_items."` WHERE entry_id = '".$entry_id."'");            

           if (yabp_cron_updateinterval_check_number($oldvalue) < 1) { yabp_cron_handle_eventstatus($oldvalue, false); }

            wp_redirect($_SERVER['PHP_SELF'].'?page=yabp-itemlist');        
            die('Done');
        }    
        elseif (isset($_GET['page']) && $_GET['page'] == "yabp-itemlist" && isset($_GET['do']) && $_GET['do'] == 'entry_setup' && isset($_GET['action']) && ($_GET['action'] == 'entry_showthumb' || $_GET['action'] == 'entry_showprice' || $_GET['action'] == 'entry_showlistprice' || $_GET['action'] == 'entry_showtitle' || $_GET['action'] == 'entry_showsubtitle' || $_GET['action'] == 'entry_showavailability' || $_GET['action'] == 'entry_showrating' || $_GET['action'] == 'entry_showbutton') && isset($_GET['value']) && ($_GET['value'] == 'true' || $_GET['value'] == 'false') && isset($_GET['entry_id']) && is_numeric($_GET['entry_id']) && yabp_entry_bolid_via_entry_id($_GET['entry_id'])) {
            $entry_id = (int) $_GET['entry_id'];            
            $column = $_GET['action'];            
            if ($_GET['value'] == 'true') { $value = 1; }
            else { $value = 0; }
            $wpdb->query("UPDATE `".$table_name_yabp."` SET ".mysql_real_escape_string($column)." = '".mysql_real_escape_string($value)."' WHERE entry_id = '".mysql_real_escape_string($entry_id)."'");                        
            wp_redirect($_SERVER['PHP_SELF'].'?page=yabp-itemlist');        
            die('Done');
        }    
        elseif (isset($_GET['page']) && $_GET['page'] == "yabp-itemlist" && isset($_GET['action']) && $_GET['action'] == 'edititemupdateinterval') {
            $getid = str_replace("update_interval-","",$_POST['element_id']);
            $entry_id = (int) $getid;
            if (!yabp_entry_bolid_via_entry_id($entry_id)) { die(__('Invalid product', 'yabp')); }            
            $updateinterval = trim($_POST['update_value']);            
            if ($updateinterval == "") { die(yabp_format_updateinterval(yabp_entry_updateinterval_via_entry_id($entry_id))); }            
            $updateinterval_formated = yabp_format_updateinterval($updateinterval, true);            
            
            $oldvalue = yabp_entry_updateinterval_via_entry_id($entry_id);
            $wpdb->query("UPDATE `".$table_name_yabp."` SET entry_updateinterval = '".mysql_real_escape_string($updateinterval_formated)."' WHERE entry_id = '".mysql_real_escape_string($entry_id)."'");
            if (yabp_cron_updateinterval_check_number($oldvalue) < 1) { yabp_cron_handle_eventstatus($oldvalue, false); }
            elseif (yabp_cron_updateinterval_check_number($updateinterval_formated) == 1) { yabp_cron_handle_eventstatus($updateinterval_formated, true); }

            die(trim($updateinterval));
        }    
        elseif (isset($_GET['page']) && $_GET['page'] == "yabp-itemlist" && isset($_GET['action']) && $_GET['action'] == 'edititemthumbsize') {
            $getid = str_replace("thumb_size-","",$_POST['element_id']);
            $entry_id = (int) $getid;
            if (!yabp_entry_bolid_via_entry_id($entry_id)) { die(__('Invalid product', 'yabp')); }
            $thumb_size = trim($_POST['update_value']);            
            if ($thumb_size == "") { die(yabp_format_thumbsize(yabp_entry_thumbsize_via_entry_id($entry_id))); }            
            $thumb_size_formated = yabp_format_thumbsize($thumb_size, true);            
            $wpdb->query("UPDATE `".$table_name_yabp."` SET entry_thumb = '".mysql_real_escape_string($thumb_size_formated)."' WHERE entry_id = '".mysql_real_escape_string($entry_id)."'");
            die(trim($thumb_size));
        }    
    }
}

add_action('init', 'yabp_itemlist_init');
add_shortcode('yabp', 'yabp_item_shortcode_execute');

function yabp_item_shortcode_execute($atts, $content = '') {
    
    global $yabp_bolcom_buy_button, $yabp_bolcom_buy_button_alt, $yabp_bolcom_view_button, $yabp_bolcom_view_button_alt, $yabp_impression_imglink_prefix;    
    $entry_id = $atts[0];
    
    if (isset($atts['subid'])) { $subid = urlencode($atts['subid']); }
    else { $subid = false; }
    
    $output = '<div class="yabp_item_wrapper">';    
    $title = yabp_item_value_via_column_name($entry_id,'item_title');
    
    if (yabp_entry_showthumb_via_entry_id($entry_id)) { 
        $thumburl = yabp_item_value_via_column_name($entry_id,yabp_entry_thumbsize_via_entry_id($entry_id, true));
        $output .= '<div class="yabp_item_img"><img alt="'.$title.'" title="'.$title.'" src="'.$thumburl.'" /></div>';
    }
    
    $output .= '<div class="yabp_item_info_left">';    
    
    if (yabp_entry_showtitle_via_entry_id($entry_id)) {
        $output .= '<span class="yabp_item_title"'.(get_option('yabp_styling_item_title_fontsize')||get_option('yabp_styling_item_title_fontcolour')?' style="'.(get_option('yabp_styling_item_title_fontsize')?'font-size: '.get_option('yabp_styling_item_title_fontsize').'px;':'').(get_option('yabp_styling_item_title_fontcolour')?'color: #'.get_option('yabp_styling_item_title_fontcolour').';':'').'"':'').'>'.$title.'</span>';
    }
    if (yabp_entry_showsubtitle_via_entry_id($entry_id) && yabp_item_value_via_column_name($entry_id,'item_subtitle') != "") {
        $subtitle = yabp_item_value_via_column_name($entry_id,'item_subtitle');
        $output .= '<br /><span class="yabp_item_subtitle"'.(get_option('yabp_styling_item_subtitle_fontsize')||get_option('yabp_styling_item_subtitle_fontcolour')?' style="'.(get_option('yabp_styling_item_subtitle_fontsize')?'font-size: '.get_option('yabp_styling_item_subtitle_fontsize').'px;':'').(get_option('yabp_styling_item_subtitle_fontcolour')?'color: #'.get_option('yabp_styling_item_subtitle_fontcolour').';':'').'"':'').'>'.$subtitle.'</span>';
    }

    if (yabp_entry_showrating_via_entry_id($entry_id) && yabp_item_value_via_column_name($entry_id,'item_rating') != 0) {
        $ratingspan = yabp_item_value_via_column_name($entry_id,'item_ratingspan');
        $output .= '<br /><span class="yabp_item_rating">'.$ratingspan.'</span>';
    }

    if (yabp_entry_showprice_via_entry_id($entry_id)) {
        $price = yabp_format_price(yabp_item_value_via_column_name($entry_id,'item_price'));
        if (yabp_entry_showlistprice_via_entry_id($entry_id) && yabp_item_value_via_column_name($entry_id,'item_listprice') > 0) {
            $listprice = yabp_format_price(yabp_item_value_via_column_name($entry_id,'item_listprice'));
            
            $output .= '<br /><span class="yabp_item_listprice"'.(get_option('yabp_styling_item_listprice_fontsize')||get_option('yabp_styling_item_listprice_fontcolour')?' style="'.(get_option('yabp_styling_item_listprice_fontsize')?'font-size: '.get_option('yabp_styling_item_listprice_fontsize').'px;':'').(get_option('yabp_styling_item_listprice_fontcolour')?'color: #'.get_option('yabp_styling_item_listprice_fontcolour').';':'').'"':'').'>'.$listprice.'</span>';
            $output .= '<span class="yabp_item_price"'.(get_option('yabp_styling_item_price_fontsize')||get_option('yabp_styling_item_price_fontcolour')?' style="'.(get_option('yabp_styling_item_price_fontsize')?'font-size: '.get_option('yabp_styling_item_price_fontsize').'px;':'').(get_option('yabp_styling_item_price_fontcolour')?'color: #'.get_option('yabp_styling_item_price_fontcolour').';':'').'"':'').'>'.$price.'</span>';        
        }
        else {
            $output .= '<br /><span class="yabp_item_price"'.(get_option('yabp_styling_item_price_fontsize')||get_option('yabp_styling_item_price_fontcolour')?' style="'.(get_option('yabp_styling_item_price_fontsize')?'font-size: '.get_option('yabp_styling_item_price_fontsize').'px;':'').(get_option('yabp_styling_item_price_fontcolour')?'color: #'.get_option('yabp_styling_item_price_fontcolour').';':'').'"':'').'>'.$price.'</span>';
        }
    }
    if (yabp_entry_showavailability_via_entry_id($entry_id)) {
        $availability = yabp_item_value_via_column_name($entry_id,'item_availability');
        $output .= '<br /><span class="yabp_item_availability"'.(get_option('yabp_styling_item_availability_fontsize')||get_option('yabp_styling_item_availability_fontcolour')?' style="'.(get_option('yabp_styling_item_availability_fontsize')?'font-size: '.get_option('yabp_styling_item_availability_fontsize').'px;':'').(get_option('yabp_styling_item_availability_fontcolour')?'color: #'.get_option('yabp_styling_item_availability_fontcolour').';':'').'"':'').'>'.$availability.'</span>';
    }
    
    if (yabp_entry_showbutton_via_entry_id($entry_id)) {
        $view = false;
        if (get_option('yabp_styling_item_button_usealternative') == 1) {
            if (get_option('yabp_styling_item_button_useviewbutton') == 1) { $view = true; $buttonurl = $yabp_bolcom_view_button_alt; }
            else { $buttonurl = $yabp_bolcom_buy_button_alt;}            
        }
        else {
            if (get_option('yabp_styling_item_button_useviewbutton') == 1) { $view = true; $buttonurl = $yabp_bolcom_view_button; }
            else { $buttonurl = $yabp_bolcom_buy_button; }            
        }        
        $output .= '<br /><a href="'.str_replace("&amp;f=TXL", "&amp;f=BTN", yabp_item_value_via_column_name($entry_id,'item_afflink')).($subid?'&amp;subid='.$subid:'').'" rel="nofollow"><img class="yabp_item_button" alt="'.($view?__('Click to view this product at bol.com', 'yabp'):__('Click to buy this product at bol.com', 'yabp')).'" title="'.($view?__('Click to view this product at bol.com', 'yabp'):__('Click to buy this product at bol.com', 'yabp')).'" src="'.$buttonurl.'" /></a>';
        
        if (get_option('yabp_item_getimpressions') == 1) { $output .= '<img src="'.$yabp_impression_imglink_prefix.get_option('yabp_siteid').'&amp;t=url&amp;f=BTN&amp;name='.urlencode(yabp_item_value_via_column_name($entry_id,'item_title')).'" width="1" height="1" />'; }
    }
    else {
        $output .= '<br /><a href="'.yabp_item_value_via_column_name($entry_id,'item_afflink').($subid?"&amp;subid=".$subid:"").'" rel="nofollow"><span class="yabp_item_textlink"'.(get_option('yabp_styling_item_textlink_fontsize')||get_option('yabp_styling_item_textlink_fontcolour')?' style="'.(get_option('yabp_styling_item_textlink_fontsize')?'font-size: '.get_option('yabp_styling_item_textlink_fontsize').'px;':'').(get_option('yabp_styling_item_textlink_fontcolour')?'color: #'.get_option('yabp_styling_item_textlink_fontcolour').';':'').'"':'').'>'.get_option('yabp_item_textlink_text').'</span></a>';
        if (get_option('yabp_item_getimpressions') == 1) { $output .= '<img src="'.$yabp_impression_imglink_prefix.get_option('yabp_siteid').'&amp;t=url&amp;f=TXL&amp;name='.urlencode(yabp_item_value_via_column_name($entry_id,'item_title')).'" width="1" height="1" />'; }
    }
    
    $output .= "</div></div>";
    return $output;
}

add_action('wp_enqueue_scripts', 'yabp_register_style', 12);

function yabp_register_style() {
    wp_register_style('yabp', plugin_dir_url(__FILE__).'yabp.css');
    wp_enqueue_style('yabp');
}

function yabp_register_adminscripts() { wp_enqueue_script('yabp_jscolor', plugin_dir_url(__FILE__).'js/jscolor/jscolor.js'); }
function yabp_register_adminscripts_action() { add_action('admin_enqueue_scripts', 'yabp_register_adminscripts'); }

function yabp_cron_handle_eventstatus($interval, $action) {
    global $yabp_cron_defaulttime;
    if (isset($interval) && is_numeric($interval)) {
        if ($action == true) {
            if (!wp_next_scheduled(yabp_format_updateinterval($interval, false, false, true))) {
                $crontime = strtotime(date("Y-m-d")." ".$yabp_cron_defaulttime);
                wp_schedule_event($crontime, yabp_format_updateinterval($interval, false, true), yabp_format_updateinterval($interval, false, false, true));
                return true;
            }
            else { return false; }          
        }
        elseif ($action == false) {
            if (wp_next_scheduled(yabp_format_updateinterval($interval, false, false, true))) {
                wp_clear_scheduled_hook(yabp_format_updateinterval($interval, false, false, true));
                return true;
            }
            else { return false; }                        
        }
        else { return false; }
    }    
}

add_action('yabp_cron_event_hourly', 'yabp_cron_event_hourly_do');
add_action('yabp_cron_event_twicedaily', 'yabp_cron_event_twicedaily_do');
add_action('yabp_cron_event_daily', 'yabp_cron_event_daily_do');

function yabp_cron_event_hourly_do() {
    global $wpdb, $table_name_yabp; 
    $interval = 1; 
    $entries = $wpdb->get_results("SELECT entry_id FROM `".$table_name_yabp."` WHERE entry_updateinterval = '".mysql_real_escape_string($interval)."'");
    foreach ($entries as $entry) {            
        yabp_item_update_via_entry_id($entry->entry_id);
    }
}

function yabp_cron_event_twicedaily_do() {
    global $wpdb, $table_name_yabp; 
    $interval = 2;   
    $entries = $wpdb->get_results("SELECT entry_id FROM `".$table_name_yabp."` WHERE entry_updateinterval = '".mysql_real_escape_string($interval)."'");
    foreach ($entries as $entry) {            
        yabp_item_update_via_entry_id($entry->entry_id);
    }      
}

function yabp_cron_event_daily_do() {
    global $wpdb, $table_name_yabp; 
    $interval = 3;
    $entries = $wpdb->get_results("SELECT entry_id FROM `".$table_name_yabp."` WHERE entry_updateinterval = '".mysql_real_escape_string($interval)."'");
    foreach ($entries as $entry) {            
        yabp_item_update_via_entry_id($entry->entry_id);
    }    
}

register_deactivation_hook(__FILE__, 'yabp_cron_handle_deactivate');

function yabp_cron_handle_deactivate() {
    wp_clear_scheduled_hook('yabp_cron_event_hourly');
    wp_clear_scheduled_hook('yabp_cron_event_twicedaily');
    wp_clear_scheduled_hook('yabp_cron_event_daily');
}
  
?>