<?php
/*
Plugin Name: Informers
Description: Displays informers from fleetly.net
Author: DeliveryNetwork
Version: 1.1.0
Author URI: http://fleetly.net
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define('FT_TEXTDOMAIN', 'fleetly');
define('FT_VERSTION', '1.1.0');

include_once "src/Client.php";
include_once "src/CustomCache.php";
include_once "src/ClientException.php";
require_once 'widget.php';

add_action('widgets_init', 'ft_widgets_init');
function ft_widgets_init(){
        register_widget('FT_Informer_Widget');
}

add_action( 'plugins_loaded', 'ft_load_textdomain' );
function ft_load_textdomain() {
       load_plugin_textdomain( FT_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ )) . '/languages' );
}

function ft_informer_shortcode( $atts ) {
	return '<div class="ft-informer-shortcode">'.informers_client($url, false, false).'</div>';
}
add_shortcode( 'fleetly-informer', 'ft_informer_shortcode' );

function informers_client( $url = '', $debug_mode = false, $echo = true ) {
    $url    =  esc_url( $url );
    
    $siteId = get_option('ft_site_id');
    $apiKey = get_option('ft_api_key'); 
    $apiUrl = get_option('ft_api_url'); 
    
    try{

        $client = new \informers\client\Client(
            array(
                "site_id" => $siteId,
                "api_key" => $apiKey,
                "api_url" => $apiUrl,
                "api_max_execution_time" => 0.4,
                "api_max_connection_time" => 0.5,
                'cache'   => new \informers\client\CustomCache(
                    function($key){
                        $result = wp_cache_get($key);
                        return $result;
                    },

                    function($key, $value, $period){
                        return wp_cache_set($key, $value, '', $period);
                    }, 3600, '')
            )
        );
        
        if( ! $url )
                $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        else
                $url = trailingslashit( $url );
                
        
        $answer = $client->render($url);
        
        if( $answer )
                $result = $answer;
        elseif( $debug_mode && ! $answer )
                $result = __('No informer', FT_TEXTDOMAIN);
        
        if( $echo )
                echo $result;
        else
                return $result;
        
    } catch (\informers\client\ClientException $e) {}

}

// create custom plugin settings menu
add_action('admin_menu', 'ft_create_menu');

function ft_create_menu() {
        
//	add_menu_page('Fleetly Informer', 'Fleetly Settings', 'administrator', __FILE__, 'ft_settings_page');
        
        add_submenu_page( 'options-general.php', __('Fleetly Informer', FT_TEXTDOMAIN), __('Fleetly Settings', FT_TEXTDOMAIN), 'administrator', FT_TEXTDOMAIN, 'ft_settings_page' );
	add_action( 'admin_init', 'ft_register_settings' );
}


function ft_register_settings() {
	
        $settings = array(
             'ft_site_id'
            ,'ft_api_key'
            ,'ft_api_url'
        );
        
        foreach( $settings as $option )
                register_setting ('ft-settings', $option);
        
}

function ft_settings_page() {
        
        $url = ( esc_url( $_POST['ft_test_url'] ) ) ? trailingslashit( esc_url( $_POST['ft_test_url'] ) ) : '';
?>
<div class="wrap" id="ft-wrapper">
<h2><?php _e('Fleetly Settings', FT_TEXTDOMAIN); ?></h2>

<form method="post" action="options.php">
    <?php settings_fields( 'ft-settings' ); ?>
    <table class="form-table ft-settings">
        <tr valign="top">
        <th scope="row"><?php _e('Site ID', FT_TEXTDOMAIN); ?></th>
        <td class="ft-second">
                <input type="text" name="ft_site_id" value="<?php echo get_option('ft_site_id'); ?>" />
                <p class="description"><?php _e('For example 34', FT_TEXTDOMAIN); ?></p>
        </td>
        <td rowspan="3">
                <div class="ft-description"><?php _e('You can ask your manager for values of these fields or copy them from fleetly.net on informer settings page.', FT_TEXTDOMAIN); ?></div>
        </td>
        </tr>
        <tr valign="top">
        <th scope="row"><?php _e('API Key', FT_TEXTDOMAIN); ?></th>
        <td><input type="text" class="regular-text code" name="ft_api_key" value="<?php echo get_option('ft_api_key'); ?>" />
        <p class="description"><?php _e('For example 3826ecef747411ed45654637bc61b0a3', FT_TEXTDOMAIN); ?></p></td>
        </tr>
        <tr valign="top">
        <th scope="row"><?php _e('API URL', FT_TEXTDOMAIN); ?></th>
        <td><input type="text" class="regular-text code" name="ft_api_url" value="<?php echo get_option('ft_api_url'); ?>" />
        <p class="description"><?php _e('For example http://distributor.fleetly.net/api', FT_TEXTDOMAIN); ?></p></td>
        </tr>
    </table>
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes', FT_TEXTDOMAIN) ?>" />
    </p>

</form>

<form method="post" action="">
        <table class="form-table ft-check">
        <tr valign="top">
                <td class="ft-fist-td">
                        <h4><label for="ft-check-url"><?php _e('Check informer for the page:', FT_TEXTDOMAIN); ?></label></h4>
                        <input id="ft-check-url" type="text" class="regular-text code" name="ft_test_url" value="<?php echo $url; ?>" placeholder="<?php _e( 'For example http://example.com/mypage', FT_TEXTDOMAIN); ?>" />
                <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Check', FT_TEXTDOMAIN);?>" />
                </p>
                </td>
                <td>
                        <h4><?php _e('Result:', FT_TEXTDOMAIN); ?></h4>
                        <div class="fr-informer-result">
                                <?php 
                                
                                        if( $url ){
                                                informers_client( $url, true );
                                        }
                                ?>
                        </div>
                </td>
        </tr>       
        </table>
        <div class="ft-shortcode-info">
                <?php _e('You can display informer through widget or add it anywhere with shortcode <code>[fleetly-informer]</code>', FT_TEXTDOMAIN); ?>
        </div>
</form>
</div>
<?php }

add_action( 'admin_enqueue_scripts', 'ft_admin_enqueue_scripts' );

function ft_admin_enqueue_scripts( $hook_suffix ) {
        
	if ( false === strpos( $hook_suffix, 'settings_page_fleetly' ) )
		return;
        
	wp_enqueue_style( 'ft-admin', plugin_dir_url( __FILE__ ).'css/style.css', array(), FT_VERSTION, 'all' );
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );

function add_action_links ( $links ) {
        $ftlinks = array(
        '<a href="' . admin_url( 'options-general.php?page=fleetly' ) . '">'.__('Settings').'</a>',
        );
        
        return array_merge( $ftlinks, $links );
}
