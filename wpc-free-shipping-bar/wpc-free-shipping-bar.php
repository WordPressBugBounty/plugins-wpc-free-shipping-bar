<?php
/**
 * Plugin Name: WPC Free Shipping Bar for WooCommerce
 * Plugin URI: https://wpclever.net/
 * Description: Encourage customers to increase their order value to be qualified for free shipping with a beautiful customizable bar.
 * Version: 1.4.4
 * Author: WPClever
 * Author URI: https://wpclever.net
 * Text Domain: wpc-free-shipping-bar
 * Domain Path: /languages/
 * Requires Plugins: woocommerce
 * Requires at least: 4.0
 * Tested up to: 6.8
 * WC requires at least: 3.0
 * WC tested up to: 9.8
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

! defined( 'WPCFB_VERSION' ) && define( 'WPCFB_VERSION', '1.4.4' );
! defined( 'WPCFB_LITE' ) && define( 'WPCFB_LITE', __FILE__ );
! defined( 'WPCFB_FILE' ) && define( 'WPCFB_FILE', __FILE__ );
! defined( 'WPCFB_URI' ) && define( 'WPCFB_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WPCFB_DIR' ) && define( 'WPCFB_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'WPCFB_REVIEWS' ) && define( 'WPCFB_REVIEWS', 'https://wordpress.org/support/plugin/wpc-free-shipping-bar/reviews/?filter=5' );
! defined( 'WPCFB_CHANGELOG' ) && define( 'WPCFB_CHANGELOG', 'https://wordpress.org/plugins/wpc-free-shipping-bar/#developers' );
! defined( 'WPCFB_DISCUSSION' ) && define( 'WPCFB_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-free-shipping-bar' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WPCFB_URI );

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/kit/wpc-kit.php';
include 'includes/hpos.php';

if ( ! function_exists( 'wpcfb_init' ) ) {
	add_action( 'plugins_loaded', 'wpcfb_init', 11 );

	if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
		add_action( 'admin_notices', 'wpcfb_notice_wc' );

		return;
	}

	function wpcfb_init() {
		if ( ! class_exists( 'WPCleverWpcfb' ) && class_exists( 'WC_Product' ) ) {
			class WPCleverWpcfb {
				protected static $settings = [];
				protected static $localization = [];
				protected static $instance = null;

				public static function instance() {
					if ( is_null( self::$instance ) ) {
						self::$instance = new self();
					}

					return self::$instance;
				}

				function __construct() {
					self::$settings     = (array) get_option( 'wpcfb_settings', [] );
					self::$localization = (array) get_option( 'wpcfb_localization', [] );

					add_action( 'init', [ $this, 'init' ] );
					add_action( 'admin_init', [ $this, 'register_settings' ] );
					add_action( 'admin_menu', [ $this, 'admin_menu' ] );
					add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 99 );
					add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

					// settings link
					add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
					add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

					if ( self::get_setting( 'show_mini_cart', 'yes' ) === 'yes' ) {
						// mini-cart
						add_action( 'woocommerce_widget_shopping_cart_before_buttons', [ $this, 'free_shipping_bar' ] );
					}

					// cart page
					$show_cart = self::get_setting( 'show_cart', 'yes' );

					switch ( $show_cart ) {
						case 'yes':
							add_action( 'woocommerce_proceed_to_checkout', [ $this, 'free_shipping_bar' ], 15 );
							break;
						case 'after_checkout':
							add_action( 'woocommerce_proceed_to_checkout', [ $this, 'free_shipping_bar' ], 25 );
							break;
						case 'before_cart_table':
							add_action( 'woocommerce_before_cart_table', [ $this, 'free_shipping_bar' ] );
							break;
						case 'after_cart_table':
							add_action( 'woocommerce_after_cart_table', [ $this, 'free_shipping_bar' ] );
							break;
						case 'before_cart_totals':
							add_action( 'woocommerce_before_cart_totals', [ $this, 'free_shipping_bar' ] );
							break;
						case 'after_cart_totals':
							add_action( 'woocommerce_after_cart_totals', [ $this, 'free_shipping_bar' ] );
							break;
						case 'before_cart':
							add_action( 'woocommerce_before_cart', [ $this, 'free_shipping_bar' ] );
							break;
						case 'after_cart':
							add_action( 'woocommerce_after_cart', [ $this, 'free_shipping_bar' ] );
							break;
					}

					// checkout page
					$show_checkout = self::get_setting( 'show_checkout', 'yes' );

					switch ( $show_checkout ) {
						case 'yes':
							add_action( 'woocommerce_review_order_before_submit', [ $this, 'free_shipping_bar' ] );
							break;
						case 'after_submit':
							add_action( 'woocommerce_review_order_after_submit', [ $this, 'free_shipping_bar' ] );
							break;
						case 'before_checkout_form':
							add_action( 'woocommerce_before_checkout_form', [ $this, 'free_shipping_bar' ] );
							break;
						case 'after_checkout_form':
							add_action( 'woocommerce_after_checkout_form', [ $this, 'free_shipping_bar' ] );
							break;
						case 'before_order_review':
							add_action( 'woocommerce_checkout_before_order_review', [ $this, 'free_shipping_bar' ] );
							break;
						case 'after_order_review':
							add_action( 'woocommerce_checkout_after_order_review', [ $this, 'free_shipping_bar' ] );
							break;
						case 'before_customer_details':
							add_action( 'woocommerce_checkout_before_customer_details', [
								$this,
								'free_shipping_bar'
							] );
							break;
						case 'after_customer_details':
							add_action( 'woocommerce_checkout_after_customer_details', [ $this, 'free_shipping_bar' ] );
							break;
					}
				}

				function init() {
					// load text-domain
					load_plugin_textdomain( 'wpc-free-shipping-bar', false, basename( WPCFB_DIR ) . '/languages/' );

					// shortcode
					add_shortcode( 'wpcfb', [ $this, 'shortcode' ] );
				}

				function enqueue_scripts() {
					wp_enqueue_style( 'wpcfb-frontend', WPCFB_URI . 'assets/css/frontend.css', false, WPCFB_VERSION );
				}

				function admin_enqueue_scripts( $hook ) {
					if ( strpos( $hook, 'wpcfb' ) ) {
						wp_enqueue_style( 'wp-color-picker' );
						wp_enqueue_script( 'wpcfb-backend', WPCFB_URI . 'assets/js/backend.js', [
							'jquery',
							'wp-color-picker'
						], WPCFB_VERSION );
					}
				}

				function shortcode() {
					ob_start();
					$this->free_shipping_bar();

					return ob_get_clean();
				}

				function register_settings() {
					// settings
					register_setting( 'wpcfb_settings', 'wpcfb_settings' );

					// localization
					register_setting( 'wpcfb_localization', 'wpcfb_localization' );
				}

				function admin_menu() {
					add_submenu_page( 'wpclever', esc_html__( 'WPC Free Shipping Bar', 'wpc-free-shipping-bar' ), esc_html__( 'Free Shipping Bar', 'wpc-free-shipping-bar' ), 'manage_options', 'wpclever-wpcfb', [
						$this,
						'admin_menu_content'
					] );
				}

				function admin_menu_content() {
					add_thickbox();
					$active_tab = sanitize_key( $_GET['tab'] ?? 'settings' );
					?>
                    <div class="wpclever_settings_page wrap">
                        <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Free Shipping Bar', 'wpc-free-shipping-bar' ) . ' ' . esc_html( WPCFB_VERSION ); ?></h1>
                        <div class="wpclever_settings_page_desc about-text">
                            <p>
								<?php printf( /* translators: stars */ esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'wpc-free-shipping-bar' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                                <br/>
                                <a href="<?php echo esc_url( WPCFB_REVIEWS ); ?>"
                                   target="_blank"><?php esc_html_e( 'Reviews', 'wpc-free-shipping-bar' ); ?></a> |
                                <a href="<?php echo esc_url( WPCFB_CHANGELOG ); ?>"
                                   target="_blank"><?php esc_html_e( 'Changelog', 'wpc-free-shipping-bar' ); ?></a> |
                                <a href="<?php echo esc_url( WPCFB_DISCUSSION ); ?>"
                                   target="_blank"><?php esc_html_e( 'Discussion', 'wpc-free-shipping-bar' ); ?></a>
                            </p>
                        </div>
						<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                            <div class="notice notice-success is-dismissible">
                                <p><?php esc_html_e( 'Settings updated.', 'wpc-free-shipping-bar' ); ?></p>
                            </div>
						<?php } ?>
                        <div class="wpclever_settings_page_nav">
                            <h2 class="nav-tab-wrapper">
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpcfb&tab=settings' ) ); ?>"
                                   class="<?php echo esc_attr( $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
									<?php esc_html_e( 'Settings', 'wpc-free-shipping-bar' ); ?>
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpcfb&tab=localization' ) ); ?>"
                                   class="<?php echo esc_attr( $active_tab === 'localization' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
									<?php esc_html_e( 'Localization', 'wpc-free-shipping-bar' ); ?>
                                </a>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>"
                                   class="nav-tab">
									<?php esc_html_e( 'Essential Kit', 'wpc-free-shipping-bar' ); ?>
                                </a>
                            </h2>
                        </div>
                        <div class="wpclever_settings_page_content">
							<?php if ( $active_tab === 'settings' ) {
								$show_mini_cart       = self::get_setting( 'show_mini_cart', 'yes' );
								$show_cart            = self::get_setting( 'show_cart', 'yes' );
								$show_checkout        = self::get_setting( 'show_checkout', 'yes' );
								$show_qualified       = self::get_setting( 'show_qualified', 'yes' );
								$disable_local_pickup = self::get_setting( 'disable_local_pickup', 'no' );
								$order_amount         = self::get_setting( 'order_amount', '' );
								$style                = self::get_setting( 'style', 'square' );
								$progress_animated    = self::get_setting( 'progress_animated', 'yes' );
								?>
                                <form method="post" action="options.php">
                                    <table class="form-table">
                                        <tr class="heading">
                                            <th colspan="2">
												<?php esc_html_e( 'General', 'wpc-free-shipping-bar' ); ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Show on mini-cart widget', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
                                                <label> <select name="wpcfb_settings[show_mini_cart]">
                                                        <option value="yes" <?php selected( $show_mini_cart, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="no" <?php selected( $show_mini_cart, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-free-shipping-bar' ); ?></option>
                                                    </select> </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Show on cart page', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
                                                <label> <select name="wpcfb_settings[show_cart]">
                                                        <option value="yes" <?php selected( $show_cart, 'yes' ); ?>><?php esc_html_e( 'Before checkout button', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="after_checkout" <?php selected( $show_cart, 'after_checkout' ); ?>><?php esc_html_e( 'After checkout button', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="before_cart_table" <?php selected( $show_cart, 'before_cart_table' ); ?>><?php esc_html_e( 'Before cart table', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="after_cart_table" <?php selected( $show_cart, 'after_cart_table' ); ?>><?php esc_html_e( 'After cart table', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="before_cart_totals" <?php selected( $show_cart, 'before_cart_totals' ); ?>><?php esc_html_e( 'Before cart totals', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="after_cart_totals" <?php selected( $show_cart, 'after_cart_totals' ); ?>><?php esc_html_e( 'After cart totals', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="before_cart" <?php selected( $show_cart, 'before_cart' ); ?>><?php esc_html_e( 'Before cart', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="after_cart" <?php selected( $show_cart, 'after_cart' ); ?>><?php esc_html_e( 'After cart', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="no" <?php selected( $show_cart, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-free-shipping-bar' ); ?></option>
                                                    </select> </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Show on checkout page', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
                                                <label> <select name="wpcfb_settings[show_checkout]">
                                                        <option value="yes" <?php selected( $show_checkout, 'yes' ); ?>><?php esc_html_e( 'Before submit button', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="after_submit" <?php selected( $show_checkout, 'after_submit' ); ?>><?php esc_html_e( 'After submit button', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="before_checkout_form" <?php selected( $show_checkout, 'before_checkout_form' ); ?>><?php esc_html_e( 'Before checkout form', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="after_checkout_form" <?php selected( $show_checkout, 'after_checkout_form' ); ?>><?php esc_html_e( 'After checkout form', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="before_order_review" <?php selected( $show_checkout, 'before_order_review' ); ?>><?php esc_html_e( 'Before order review', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="after_order_review" <?php selected( $show_checkout, 'after_order_review' ); ?>><?php esc_html_e( 'After order review', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="before_customer_details" <?php selected( $show_checkout, 'before_customer_details' ); ?>><?php esc_html_e( 'Before customer details', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="after_customer_details" <?php selected( $show_checkout, 'after_customer_details' ); ?>><?php esc_html_e( 'After customer details', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="no" <?php selected( $show_checkout, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-free-shipping-bar' ); ?></option>
                                                    </select> </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Shortcode', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
												<?php printf( /* translators: shortcode */ esc_html__( 'You can use shortcode %s to show the free shipping bar wherever you want.', 'wpc-free-shipping-bar' ), '<code>[wpcfb]</code>' ); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Show qualified message', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
                                                <label> <select name="wpcfb_settings[show_qualified]">
                                                        <option value="yes" <?php selected( $show_qualified, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="no" <?php selected( $show_qualified, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-free-shipping-bar' ); ?></option>
                                                    </select> </label>
                                                <span class="description"><?php esc_html_e( 'Show qualified message when reaching the free shipping amount.', 'wpc-free-shipping-bar' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Disable for Local pickup', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
                                                <label> <select name="wpcfb_settings[disable_local_pickup]">
                                                        <option value="yes" <?php selected( $disable_local_pickup, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="no" <?php selected( $disable_local_pickup, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-free-shipping-bar' ); ?></option>
                                                    </select> </label>
                                                <span class="description"><?php esc_html_e( 'Disable the free shipping bar when Local pickup was selected.', 'wpc-free-shipping-bar' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Minimum Order Amount', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
                                                <label>
                                                    <input type="number" min="0" name="wpcfb_settings[order_amount]"
                                                           value="<?php echo esc_attr( $order_amount ); ?>"/> <?php echo get_woocommerce_currency_symbol(); ?>
                                                    .
                                                </label>
                                                <span class="description"><?php esc_html_e( 'Priority using this amount to calculate free shipping.', 'wpc-free-shipping-bar' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr class="heading">
                                            <th colspan="2">
												<?php esc_html_e( 'Design', 'wpc-free-shipping-bar' ); ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Style', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
                                                <label> <select name="wpcfb_settings[style]">
                                                        <option value="square" <?php selected( $style, 'square' ); ?>><?php esc_html_e( 'Square', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="rounded" <?php selected( $style, 'rounded' ); ?>><?php esc_html_e( 'Rounded', 'wpc-free-shipping-bar' ); ?></option>
                                                    </select> </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Bar color', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
												<?php $wpcfb_bar_color_default = apply_filters( 'wpcfb_bar_color_default', '#ecd4e5' ); ?>
                                                <label>
                                                    <input type="text" name="wpcfb_settings[bar_color]"
                                                           value="<?php echo esc_attr( self::get_setting( 'bar_color', $wpcfb_bar_color_default ) ); ?>"
                                                           class="wpcfb_color_picker"/>
                                                </label>
                                                <span class="description"><?php printf( /* translators: color */ esc_html__( 'Choose the background color for the bar, default %s', 'wpc-free-shipping-bar' ), '<code>' . esc_html( $wpcfb_bar_color_default ) . '</code>' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Progress color', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
												<?php $wpcfb_progress_color_default = apply_filters( 'wpcfb_progress_color_default', '#95578a' ); ?>
                                                <label>
                                                    <input type="text" name="wpcfb_settings[progress_color]"
                                                           value="<?php echo esc_attr( self::get_setting( 'progress_color', $wpcfb_progress_color_default ) ); ?>"
                                                           class="wpcfb_color_picker"/>
                                                </label>
                                                <span class="description"><?php printf( /* translators: color */ esc_html__( 'Choose the background color for the progress, default %s', 'wpc-free-shipping-bar' ), '<code>' . esc_html( $wpcfb_progress_color_default ) . '</code>' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Animated', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
                                                <label> <select name="wpcfb_settings[progress_animated]">
                                                        <option value="yes" <?php selected( $progress_animated, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-free-shipping-bar' ); ?></option>
                                                        <option value="no" <?php selected( $progress_animated, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-free-shipping-bar' ); ?></option>
                                                    </select> </label>
                                                <span class="description"><?php esc_html_e( 'Add animation for progress bar.', 'wpc-free-shipping-bar' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr class="submit">
                                            <th colspan="2">
												<?php settings_fields( 'wpcfb_settings' ); ?><?php submit_button(); ?>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
							<?php } elseif ( $active_tab === 'localization' ) { ?>
                                <form method="post" action="options.php">
                                    <table class="form-table">
                                        <tr class="heading">
                                            <th scope="row"><?php esc_html_e( 'Localization', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
												<?php esc_html_e( 'Leave blank to use the default text and its equivalent translation in multiple languages.', 'wpc-free-shipping-bar' ); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Title', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
                                                <label>
                                                    <input type="text" name="wpcfb_localization[title]"
                                                           style="width: 100%"
                                                           value="<?php echo esc_attr( self::localization( 'title' ) ); ?>"
                                                           placeholder="<?php esc_attr_e( 'Free delivery on orders over {free_shipping_amount}', 'wpc-free-shipping-bar' ); ?>"/>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Message', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
                                                <label>
                                                    <input type="text" name="wpcfb_localization[message]"
                                                           style="width: 100%"
                                                           value="<?php echo esc_attr( self::localization( 'message' ) ); ?>"
                                                           placeholder="<?php esc_attr_e( 'Add at least {remaining} more to enjoy the free shipping!', 'wpc-free-shipping-bar' ); ?>"/>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Qualified message', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
                                                <label>
                                                    <input type="text" name="wpcfb_localization[qualified]"
                                                           style="width: 100%"
                                                           value="<?php echo esc_attr( self::localization( 'qualified' ) ); ?>"
                                                           placeholder="<?php esc_attr_e( 'Your order is qualified for free shipping!', 'wpc-free-shipping-bar' ); ?>"/>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php esc_html_e( 'Placeholder', 'wpc-free-shipping-bar' ); ?></th>
                                            <td>
												<?php esc_html_e( '{free_shipping_amount}: free shipping amount', 'wpc-free-shipping-bar' ); ?>
                                                <br/>
												<?php esc_html_e( '{remaining}: remaining amount', 'wpc-free-shipping-bar' ); ?>
                                                <br/>
												<?php esc_html_e( '{subtotal}: cart subtotal', 'wpc-free-shipping-bar' ); ?>
                                            </td>
                                        </tr>
                                        <tr class="submit">
                                            <th colspan="2">
												<?php settings_fields( 'wpcfb_localization' ); ?><?php submit_button(); ?>
                                            </th>
                                        </tr>
                                    </table>
                                </form>
							<?php } ?>
                        </div><!-- /.wpclever_settings_page_content -->
                        <div class="wpclever_settings_page_suggestion">
                            <div class="wpclever_settings_page_suggestion_label">
                                <span class="dashicons dashicons-yes-alt"></span> Suggestion
                            </div>
                            <div class="wpclever_settings_page_suggestion_content">
                                <div>
                                    To display custom engaging real-time messages on any wished positions, please
                                    install
                                    <a href="https://wordpress.org/plugins/wpc-smart-messages/" target="_blank">WPC
                                        Smart Messages</a> plugin. It's free!
                                </div>
                                <div>
                                    Wanna save your precious time working on variations? Try our brand-new free plugin
                                    <a href="https://wordpress.org/plugins/wpc-variation-bulk-editor/" target="_blank">WPC
                                        Variation Bulk Editor</a> and
                                    <a href="https://wordpress.org/plugins/wpc-variation-duplicator/" target="_blank">WPC
                                        Variation Duplicator</a>.
                                </div>
                            </div>
                        </div>
                    </div>
					<?php
				}

				function action_links( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wpcfb&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'wpc-free-shipping-bar' ) . '</a>';
						array_unshift( $links, $settings );
					}

					return (array) $links;
				}

				function row_meta( $links, $file ) {
					static $plugin;

					if ( ! isset( $plugin ) ) {
						$plugin = plugin_basename( __FILE__ );
					}

					if ( $plugin === $file ) {
						$row_meta = [
							'support' => '<a href="' . esc_url( WPCFB_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'wpc-free-shipping-bar' ) . '</a>',
						];

						return array_merge( $links, $row_meta );
					}

					return (array) $links;
				}

				public function free_shipping_bar() {
					if ( ! isset( WC()->cart ) || ! WC()->cart->needs_shipping() || ! WC()->cart->show_shipping() ) {
						return;
					}

					if ( apply_filters( 'wpcfb_ignore', false ) ) {
						return;
					}

					if ( ! apply_filters( 'wpcfb_local_pickup', self::get_setting( 'disable_local_pickup', 'no' ) === 'no' ) && $this->is_shipping_method( $this->get_shipping_method(), 'local_pickup' ) ) {
						return;
					}

					$is_qualified = '';

					if ( WC()->customer->has_shipping_address() && ( WC()->cart->get_shipping_total() <= 0 ) ) {
						// shipping fee zero
						$is_qualified = 'zero_fee';
					}

					$applied_coupons = WC()->cart->get_applied_coupons();

					foreach ( $applied_coupons as $coupon_code ) {
						$coupon = new WC_Coupon( $coupon_code );

						if ( $coupon->get_free_shipping() ) {
							// already free shipping
							$is_qualified = 'coupon';
							break;
						}
					}

					$free_shipping_min_amount = (float) self::get_setting( 'order_amount', '' );

					if ( ! empty( $free_shipping_min_amount ) ) {
						$cart_total = WC()->cart->get_displayed_subtotal();

						if ( $cart_total >= $free_shipping_min_amount ) {
							$is_qualified = 'order_amount';
						}
					} else {
						$free_shipping                  = $this->get_free_shipping();
						$free_shipping_min_amount       = $free_shipping['min_amount'] ?? 0;
						$free_shipping_ignore_discounts = $free_shipping['ignore_discounts'] ?? 'no';

						if ( ! $free_shipping_min_amount ) {
							return;
						}

						$cart_total          = WC()->cart->get_displayed_subtotal();
						$discount            = WC()->cart->get_discount_total();
						$discount_tax        = WC()->cart->get_discount_tax();
						$price_including_tax = WC()->cart->display_prices_including_tax();
						$price_decimal       = wc_get_price_decimals();

						if ( apply_filters( 'wpcfb_ignore_discounts', $free_shipping_ignore_discounts !== 'no' ) ) {
							$discount     = 0;
							$discount_tax = 0;
						}

						if ( $price_including_tax ) {
							$cart_total = round( $cart_total - ( $discount + $discount_tax ), $price_decimal );
						} else {
							$cart_total = round( $cart_total - $discount, $price_decimal );
						}

						if ( $cart_total >= $free_shipping_min_amount ) {
							$is_qualified = 'total';
						}
					}

					$title             = self::localization( 'title', esc_html__( 'Free delivery on orders over {free_shipping_amount}', 'wpc-free-shipping-bar' ) );
					$message           = self::localization( 'message', esc_html__( 'Add at least {remaining} more to enjoy the free shipping!', 'wpc-free-shipping-bar' ) );
					$qualified_message = self::localization( 'qualified', esc_html__( 'Your order is qualified for free shipping!', 'wpc-free-shipping-bar' ) );
					$show_qualified    = self::get_setting( 'show_qualified', 'yes' ) === 'yes';

					if ( empty( $is_qualified ) ) {
						$bar_color             = self::get_setting( 'bar_color', apply_filters( 'wpcfb_bar_color_default', '#ecd4e5' ) );
						$progress_color        = self::get_setting( 'progress_color', apply_filters( 'wpcfb_progress_color_default', '#95578a' ) );
						$remaining             = $free_shipping_min_amount - $cart_total;
						$percent               = 100 - ( $remaining / $free_shipping_min_amount ) * 100;
						$title                 = $this->placeholders( $title, $remaining, $free_shipping_min_amount );
						$message               = $this->placeholders( $message, $remaining, $free_shipping_min_amount );
						$qualified_message     = $this->placeholders( $qualified_message, $remaining, $free_shipping_min_amount );
						$wrap_class            = 'wpcfb-wrap wpc-free-shipping-bar wpcfb-style-' . self::get_setting( 'style', 'square' ) . ' ' . ( self::get_setting( 'progress_animated', 'yes' ) === 'yes' ? 'wpcfb-progress-animated' : '' );
						$wrap_attrs            = apply_filters( 'wpcfb_wrap_attrs', [], $remaining, $free_shipping_min_amount );
						$progress_bar_attrs    = apply_filters( 'wpcfb_progress_bar_attrs', [], $remaining, $free_shipping_min_amount );
						$progress_amount_attrs = apply_filters( 'wpcfb_progress_amount_attrs', [], $remaining, $free_shipping_min_amount );
						?>
                        <div class="<?php echo esc_attr( apply_filters( 'wpcfb_wrap_class', $wrap_class, 'default' ) ); ?>" <?php echo self::data_attributes( $wrap_attrs ); ?>>
							<?php do_action( 'wpcfb_before_shipping_bar' ); ?>
                            <div class="wpcfb-title"><?php echo $this->kses( $title ); ?></div>
                            <div class="wpcfb-progress-bar" <?php echo self::data_attributes( $progress_bar_attrs ); ?>
                                 style="background-color:<?php echo esc_attr( $bar_color ); ?>">
                                <span class="wpcfb-progress-amount" <?php echo self::data_attributes( $progress_amount_attrs ); ?>
                                      style="width:<?php echo esc_attr( $percent . '%' ); ?>; background-color:<?php echo esc_attr( $progress_color ); ?>"></span>
                            </div>
                            <div class="wpcfb-message"><?php echo $this->kses( $message ); ?></div>
							<?php do_action( 'wpcfb_after_shipping_bar' ); ?>
                        </div>
						<?php
					}

					if ( ! empty( $is_qualified ) && $show_qualified ) {
						$wrap_class        = 'wpcfb-wrap wpc-free-shipping-bar wpcfb-qualified-message';
						$qualified_message = apply_filters( 'wpcfb_qualified_message', $qualified_message, $is_qualified );
						?>
                        <div class="<?php echo esc_attr( apply_filters( 'wpcfb_wrap_class', $wrap_class, 'qualified' ) ); ?>">
							<?php do_action( 'wpcfb_before_qualified_message' ); ?>
                            <div class="wpcfb-message"><?php echo $this->kses( $qualified_message ); ?></div>
							<?php do_action( 'wpcfb_after_qualified_message' ); ?>
                        </div>
						<?php
					}
				}

				public function kses( $text ) {
					return wp_kses( $text, [
						'bdi'  => [],
						'span' => [ 'class' => [] ]
					] );
				}

				public function get_free_shipping() {
					$free_shipping        = [];
					$chosen_shipping_id   = $this->get_shipping_method();
					$is_flexible_shipping = $this->is_shipping_method( $chosen_shipping_id, 'flexible_shipping' );

					if ( $is_flexible_shipping ) {
						$option_name = 'woocommerce_' . str_replace( ':', '_', $chosen_shipping_id ) . '_settings';
						$option      = get_option( $option_name );
						$amount      = $option['method_free_shipping'] ?? null;

						return $amount ?: $this->get_shipping_method_min_amount( $chosen_shipping_id );
					}

					$packages = WC()->cart->get_shipping_packages();
					$package  = reset( $packages );
					$zone     = wc_get_shipping_zone( $package );

					foreach ( $zone->get_shipping_methods( true ) as $method ) {
						if ( $method->id === 'free_shipping' ) {
							$free_shipping['min_amount']       = $method->get_option( 'min_amount', 0 );
							$free_shipping['ignore_discounts'] = $method->get_option( 'ignore_discounts' );
						}
					}

					return apply_filters( 'wpcfb_get_free_shipping', $free_shipping );
				}

				public function get_shipping_method() {
					$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

					if ( ! $chosen_methods ) {
						return null;
					}

					return $chosen_methods[0];
				}

				public function get_shipping_method_min_amount( $shipping_id ) {
					$packages = WC()->shipping->get_packages();
					$amount   = null;

					foreach ( $packages as $package ) {
						if ( isset( $package['rates'][ $shipping_id ] ) ) {
							$rate = $package['rates'][ $shipping_id ];
							$meta = $rate->get_meta_data();

							if ( isset( $meta['_fs_method']['method_free_shipping'] ) ) {
								$amount = $meta['_fs_method']['method_free_shipping'] ?: null;
							}
						}
					}

					return $amount;
				}

				public function is_shipping_method( $string, $start_string ) {
					$len = strlen( $start_string );

					return ( substr( $string, 0, $len ) === $start_string );
				}

				public function placeholders( $input_string = '', $remaining = null, $free_shipping_min_amount = null ) {
					if ( $remaining ) {
						$input_string = str_replace( '{remaining}', wc_price( $remaining ), $input_string );
					}

					if ( $free_shipping_min_amount ) {
						$input_string = str_replace( '{free_shipping_amount}', wc_price( $free_shipping_min_amount ), $input_string );
					}

					return str_replace( '{subtotal}', WC()->cart->get_cart_subtotal(), $input_string );
				}

				public static function get_settings() {
					return apply_filters( 'wpcfb_get_settings', self::$settings );
				}

				public static function get_setting( $name, $default = false ) {
					if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
						$setting = self::$settings[ $name ];
					} else {
						$setting = get_option( 'wpcfb_' . $name, $default );
					}

					return apply_filters( 'wpcfb_get_setting', $setting, $name, $default );
				}

				public static function localization( $key = '', $default = '' ) {
					$str = '';

					if ( ! empty( $key ) && ! empty( self::$localization[ $key ] ) ) {
						$str = self::$localization[ $key ];
					} elseif ( ! empty( $default ) ) {
						$str = $default;
					}

					return apply_filters( 'wpcfb_localization_' . $key, $str );
				}

				public static function data_attributes( $attrs ) {
					$attrs_arr = [];

					foreach ( $attrs as $key => $attr ) {
						$attrs_arr[] = esc_attr( 'data-' . sanitize_title( $key ) ) . '="' . esc_attr( $attr ) . '"';
					}

					return implode( ' ', $attrs_arr );
				}
			}

			return WPCleverWpcfb::instance();
		}

		return null;
	}
}

if ( ! function_exists( 'wpcfb_notice_wc' ) ) {
	function wpcfb_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Free Shipping Bar</strong> require WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
