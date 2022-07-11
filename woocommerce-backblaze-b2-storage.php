<?php
/**
 * Plugin Name: WooCommerce Backblaze B2 Storage
 * Description: Store your downloadable products on Backblaze B2 Cloud offering faster downloads for your customers and more security for your product.
 * Version: 1.0.0
 * Author: Michel Moraes
 * Author URI: https://michelmoraes.dev
 * Requires at least: 3.8
 * Tested up to: 5.5
 * WC tested up to: 4.4
 * WC requires at least: 2.6
 * Text Domain: wc_backblaze_b2
 * Woo: 18663:473bf6f221b865eff165c97881b473bb
 *
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package woocommerce-backblaze-b2-storage
 */

// Plugin init hook.
add_action( 'plugins_loaded', 'wc_backblaze_b2_init' );

/**
 * Initialize plugin.
 */
function wc_backblaze_b2_init() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wc_backblaze_b2_woocommerce_deactivated' );
		return;
	}

	define( 'WC_BACKBLAZE_B2_STORAGE_VERSION', '1.0.0' );

	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'wc_backblaze_b2', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

	/**
	 * WooCommerce_Backblaze_B2_Storage class
	 **/
	if ( ! class_exists( 'WooCommerce_Backblaze_B2_Storage' ) ) {

		class WooCommerce_Backblaze_B2_Storage {

			/**
			 * Class Variables
			 **/
			public $settings_name = 'woo_backblaze_b2_storage';
			public $credentials = array();
			var $disable_ssl;

			/**
			 * Constructor
			 **/
			function __construct() {
				// Load admin settings.
				$admin_settings = get_option( $this->settings_name );
				$this->credentials['key'] = $admin_settings['backblaze_access_key'];
				$this->credentials['secret'] = $admin_settings['backblaze_access_secret'];
				$this->disable_ssl = ( ! empty( $admin_settings['amazon_disable_ssl'] ) ? $admin_settings['amazon_disable_ssl'] : 0 );

				// Create Menu under WooCommerce Menu
				add_action( 'admin_menu', array( $this, 'register_menu' ) );
				add_filter( 'woocommerce_downloadable_product_name', array( $this, 'wc2_product_download_name' ), 10, 4 );
				add_filter( 'woocommerce_file_download_path', array( $this, 'wc2_product_download' ), 1, 3 );

				add_shortcode( 'backblaze_b2', array( $this, 'backblaze_shortcode' ) );
				add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
				add_action( 'init', array( $this, 'includes' ) );
			}

			public function includes() {
				require_once( __DIR__ . '/woocommerce-backblaze-b2-storage-privacy.php' );
			}

			/**
			 * Add custom plugin action links.
			 *
			 * @since 1.0.0
			 *
			 * @param array $links Links.
			 *
			 * @return array Links.
			 */
			public function plugin_action_links( $links ) {
				$plugin_links = array(
					'<a href="' . admin_url( 'admin.php?page=woo_backblaze_b2_storage' ) . '">' . __( 'Settings', 'wc_backblaze_b2' ) . '</a>',
				);

				return array_merge( $plugin_links, $links );
			}

			public function register_menu() {
				add_submenu_page( 'woocommerce', __( 'WooCommerce Blackblaze B2 Storage', 'wc_backblaze_b2' ), __( 'Blackblaze B2 Storage', 'wc_backblaze_b2' ), 'manage_woocommerce', 'woo_backblaze_b2_storage', array( &$this, 'menu_setup' ) );
			}

			function menu_setup() {
				$admin_options = get_option( $this->settings_name );

				// Save values if submitted
				if ( isset( $_POST['woo_backblaze_access_key'] ) ) {
					$admin_options['backblaze_access_key'] = $_POST['woo_backblaze_access_key'];
				}
				if ( isset( $_POST['woo_backblaze_access_secret'] ) ) {
					$admin_options['backblaze_access_secret'] = $_POST['woo_backblaze_access_secret'];
				}
				if ( isset( $_POST['woo_backblaze_url_period'] ) ) {
					$admin_options['backblaze_url_period'] = $_POST['woo_backblaze_url_period'];
				}
				$this->credentials['key'] = $admin_options['backblaze_access_key'];
				$this->credentials['secret'] = $admin_options['backblaze_access_secret'];
				update_option( $this->settings_name, $admin_options );

				include_once 'templates/settings.php';
			}

			public function wc2_product_download( $file_path, $product_id, $download_id ) {
				// Only run do_shortcode when it is a shortcode and on the front-end, or when it is REST only for GET and context != edit
				$is_shortcode = '[' === substr( $file_path, 0, 1 ) && ']' === substr( $file_path, -1 );
				$is_rest = defined( 'REST_REQUEST' );

				if ( $is_shortcode && (
					( ! $is_rest && ! is_admin() ) ||
					( $is_rest && 'GET' === strtoupper( $_SERVER['REQUEST_METHOD'] ) &&
						( ! isset( $_GET['context'] ) || 'edit' !== $_GET['context'] ) ) ) ) {
					return do_shortcode( $file_path );
				}

				return $file_path;
			}

			/**
			 * Filters the name so the amazon tag or any of its parts don't show - we just want the file name if possible
			 */
			public function wc2_product_download_name($name, $product, $download_id, $file_number ) {
				if ( strpos( $name, '[backblaze_b2' ) === false ) {
					return $name;
				}

				$name = str_replace( '[backblaze_b2 ', "[backblaze_b2 return='name' ", $name );
				return do_shortcode( $name );
			}

			// Kept around for older versions not using wp_remote_get, setting removed
			function set_ssl( $amazon_s3_object ) {
				if ( '1' === $this->disable_ssl ) {
					$amazon_s3_object->disable_ssl_verification();
				}
			}

			public function backblaze_shortcode($atts ) {
				require_once 'backblaze-s3-api.php';

				extract( shortcode_atts( array(
					'bucket' => '',
					'object' => '',
					'return' => 'url',
					'region' => '',
				), $atts ) );

				if ( 'name' === $return ) {
					return $object;
				}

				$object = str_replace( array( '+', ' ' ), '%20', $object );

				if ( ! empty( $bucket ) && ! empty( $object ) ) {
					$admin_options = get_option( $this->settings_name );
					$period = 60;
					// Check if we should make URL only valid for certain period
					if ( ! empty( $admin_options['backblaze_url_period'] ) ) {
						// send time through as seconds
						$period = ( $admin_options['backblaze_url_period'] * 60 );
					}

					$s3 = new BackblazeS3( $this->credentials );
					$amazon_url = $s3->get_object_url( $bucket, $object, $period, $region );

					if ( ! empty( $amazon_url ) ) {
						return $amazon_url;
					} else {
						$error = __( 'A download failed due to connection problems with Backblaze B2, please check your settings.', 'wc_backblaze_b2' );
						if ( defined( 'WC_VERSION' ) && WC_VERSION ) {
							wc_add_notice( $error, 'error' );
						} else {
							global $woocommerce;
							$woocommerce->add_error( $error );
						}
					}
				}
			}
		}
	} // End if().
	global $WooCommerce_Backblaze_B2_Storage;
	$WooCommerce_Backblaze_B2_Storage = new WooCommerce_Backblaze_B2_Storage();
}

/**
 * WooCommerce Deactivated Notice.
 */
function wc_backblaze_b2_woocommerce_deactivated() {
	/* translators: %s: WooCommerce link */
	echo '<div class="error"><p>' . sprintf( esc_html__( 'WooCommerce Backblaze B2 Storage requires %s to be installed and active.', 'wc_backblaze_b2' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</p></div>';
}
