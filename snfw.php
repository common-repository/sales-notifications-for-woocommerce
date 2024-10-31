<?php
/**
 * Plugin Name: Sales Notifications for WooCommerce
 * Description: Increase store trust with social proof to build credibility.
 * Version: 1.0.3
 * Author: Royalz Toolkits
 * Author URI: http://royalztoolkits.com
 * Requires at least: 4.7
 * Tested up to: 4.9.5
 * Text Domain: snfw
*/

if ( ! class_exists( 'SNFW' ) ) :
	class SNFW {

		/**
		 * CONSTRUCT
		 *
		*/
		function __construct() {
			
			// LOAD THE PLUGIN'S TEXT DOMAIN
			add_action( 'init', 									array( $this, 'textdomain' ) );
			
			// ENQUEUE SCRIPTS AND STYLES
			add_action( 'init', 									array( $this, 'scripts' ) );

			// ADD SETTINGS 
			add_action( 'admin_init', 								array( $this, 'snfw_settings_init' ) );

			// ADD SETTINGS PAGE
			add_action( 'admin_menu', 								array( $this, 'snfw_options_page' ) );

			// AJAX FUNCTIONS
			add_action( 'wp_ajax_snfw_message', 					array( $this, 'display' ) );
			add_action( 'wp_ajax_nopriv_snfw_message', 				array( $this, 'display' ) );
			
		}


		/**
		 * LOAD THE PLUGIN'S TEXT DOMAIN
		 *
		 */
		public function textdomain() {
			load_plugin_textdomain( 'snfw', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}


		/**
		 * ENQUEUE SCRIPTS AND STYLES
		 *
		 */
		public function scripts() {

			// ENQUEUE STYLES AND SCRIPTS FOR FRONTEND
			if ( ! is_admin() ) {

				// ENQUEUE STYLES AND SCRIPTS
				wp_enqueue_style( 'snfw-style', snfw_plugin_url() . '/assets/css/style.min.css', '1.0' );
				wp_enqueue_script( 'animejs', snfw_plugin_url() . '/assets/js/anime.min.js', array( 'jquery' ), true );
				wp_enqueue_script( 'snfw-scripts', snfw_plugin_url() . '/assets/js/scripts.min.js', array( 'jquery' ), '1.0', true );

				// GET NECESSARY SETTINGS
				$snfw_settings['delays_initial'] 	= snfw_get_setting('delays_initial', '5');
				$snfw_settings['delays_display'] 	= snfw_get_setting('delays_display', '5');
				$snfw_settings['delays_time'] 		= snfw_get_setting('delays_time', '5');
				$snfw_settings['deisplay_desktop'] 	= snfw_get_setting('display_desktop', 'on');
				$snfw_settings['deisplay_mobile'] 	= snfw_get_setting('display_mobile', 'on');

				// LOCALIZE VARS
				wp_localize_script( 'snfw-scripts', 'snfw_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
				wp_localize_script( 'snfw-scripts', 'snfw_settings', $snfw_settings );

				// ADD BODY CLASSES
				add_filter('body_class', function($classes) {

					// HIDE ON DESKTOP IF DISABLED
					if ( $snfw_settings['deisplay_desktop'] != 'on' ) {
						$classes[] = 'snfw-message-desktop-hidden';
					}

					// HIDE ON MOBILE IF DISABLED
					if ( $snfw_settings['deisplay_mobile'] != 'on' ) {
						$classes[] = 'snfw-message-mobile-hidden';
					}

					return $classes;

				});
			}

		}


		/**
		 * DISPLAY 
		 *
		 */
		function display() {


			// SET MESSAGE DEFAULTS
			$message['exclude']		= array();
			$message['format']		= 'snfw-message-format-1';
			$message['type'] 		= 'order';


			// GET MESSAGE HISTORY
			$message['history'] 	= ( isset( $_POST['history'] ) ) ? json_decode( stripcslashes( sanitize_text_field( $_POST['history'] ) ), true )  : '';
			

			// GET MESSAGE SETTINGS
			$message_settings['privacy_names'] 		= snfw_get_setting('privacy_names', 'first');
			$message_settings['privacy_location'] 	= snfw_get_setting('privacy_location', 'country');
			$message_settings['display_desktop'] 	= snfw_get_setting('display_desktop', '');
			$message_settings['display_mobile'] 	= snfw_get_setting('display_mobile', '');


			// ORDER MESSAGE
			if ( $message['type'] == 'order' ) {

				// PUT TO HISTORY
				foreach ( $message['history'] as $key => $value ) {
					if ( $key == 'order' ) {
						$message['exclude'][] = $value;
					}
				}

				$orders = wc_get_orders( array( 'limit' => 1, 'orderby' => 'date', 'order' => 'DESC', 'exclude' => $message['exclude'], 'return' => 'ids', 'status' => 'completed' ) );

				// RETURN ERROR IF NO ORDERS FOUND
				if ( !count( $orders ) ) {
			        header('HTTP/1.1 200 OK');
			        echo 'error-empty-orders';
			        exit();
				}

				$order_id 	= $orders[0];
				$order 		= new WC_Order( $order_id );

				// ORDER PRODUCTS
				$order_products = $order->get_items();

				// RETURN ERROR IF ORDERS HAS NO PRODUCTS
				if ( !count( $order_products ) ) {
			        header('HTTP/1.1 200 OK');
			        echo 'error-empty-order-products';
			        exit();
				}

				$order_products_list = array();

				foreach ( $order->get_items() as $item_id => $item_data ) {
					$product = $item_data->get_product();
					$product_image = wp_get_attachment_url( $product->get_image_id() );
					$order_products_list[] = array( 'id' => $product->get_id(), 'name' => $product->get_name(), 'image' => $product_image, 'quantity' => $item_data->get_quantity(), 'total' => $item_total );
				}

				$order_product = $order_products_list[0];

				$order_info['product_link'] 	= get_permalink( $order_product['id'] );

				$order_info['product_image'] 	= ( isset( $order_product['image'] ) ) ? $order_product['image'] : '';

				$order_info['product_name'] 	= ( isset( $order_product['name'] ) ) ? $order_product['name'] : $order_product['id'];
				$order_info['product_name'] 	= ( strlen( $order_info['product_name'] ) > 40 ) ? substr( $order_info['product_name'], 0, 40 ) . '...' : $order_info['product_name'];
				$order_info['product_name'] 	= sprintf( '<span class="snfw-message-product">%s</span>', $order_info['product_name'] );



				// ORDER CUSTOMER
				$order_info['customer_first_name'] 	= $order->get_billing_first_name();
				$order_info['customer_first_name'] 	= ( !empty( $order_info['customer_first_name'] ) && $message_settings['privacy_names'] != 'none' ) ? $order_info['customer_first_name'] : esc_html__('Someone', 'snfw');

				$order_info['customer_last_name'] 	= $order->get_billing_last_name();
				$order_info['customer_last_name'] 	= ( !empty( $order_info['customer_last_name'] ) && $message_settings['privacy_names'] == 'full'  ) ? sprintf( ' %s', $order_info['customer_last_name'] ) : '';

				$order_info['customer'] 	= sprintf( '<span class="snfw-message-customer">%s %s</span>', $order_info['customer_first_name'], $order_info['customer_last_name'] );



				// ORDER LOCATION
				$order_location_city 		= $order->get_billing_city();
				$order_location_city 		= ( !empty( $order_location_city ) && $message_settings['privacy_location'] == 'full' ) 	? sprintf('%s, ', $order_location_city) : '';

				$order_location_country 	= WC()->countries->countries[ $order->get_billing_country() ];
				$order_location_country 	= ( !empty( $order_location_country ) && $message_settings['privacy_location'] != 'none' ) ? $order_location_country : '';

				$order_info['location'] 	= $order_location_city . $order_location_country;
				$order_info['location'] 	= ( !empty( $order_info['location'] ) ) ? sprintf( 'from <span class="snfw-message-location">%s</span>', $order_info['location'] ) : '';



				// ORDER TIME
				$order_info['time'] = sprintf( _x( '%s ago', '%s = human-readable time difference', 'snfw' ), human_time_diff( $order->get_date_created()->getTimestamp(), current_time( 'timestamp', true ) ) );



				// ASSEMBLE MESSAGE ELEMENTS
				$message['class'] 	 	= 'snfw-message-order';
				$message['icon'] 		= $order_info['product_image'];
				$message['id']  		= $order_id;
				$message['link_1'] 		= $order_info['product_link'];
				$message['link_text_1'] = esc_html__('View', 'snfw');
				$message['link_2'] 		= sprintf( '%s?add-to-cart=%s', home_url('/'), $order_product['id'] );//$order_product->add_to_cart_url();
				$message['link_text_2'] = esc_html__('Add to cart', 'snfw');
				$message['phrase'] 		= sprintf( '%s %s purchased %s', $order_info['customer'], $order_info['location'], $order_info['product_name'] );
				$message['time'] 		= $order_info['time'];
				
			}


			// HALT IF THERE IS NO PHRASE
			if ( ! isset( $message['phrase'] ) ) {
		        header('HTTP/1.1 200 OK');
		        echo 'error-empty-phrase';
		        exit();
			}


			// REASSEMBLE MESSAGE CLASS
			$message['class']		= sprintf('%s %s', $message['class'], $message['format']); // ADD MESSAGE FORMAT
			$message['class']		= sprintf('%s %s', $message['class'], ( $message_settings['display_desktop'] == 'on' ) ? 'message-desktop-hidden' : ''); // ADD DESKTOP CLASS
			$message['class']		= sprintf('%s %s', $message['class'], ( $message_settings['display_mobile'] == 'on' ) ? 'message-mobile-hidden' : ''); // ADD MOBILE CLASS


			// ASSEMBLE MESSAGE CONTENT
			$message_content  	 = '<!-- snfw -->';
			$message_content 	.= sprintf( '<div class="snfw-animation-3 snfw-message snfw-message-expand snfw-item %s" id="snfw-message" data-message-type="%s" data-message-id="%s"><div class="snfw-message-wrapper">', esc_attr($message['class']), esc_attr($message['type']), esc_attr($message['id']) );
			
			$message_content 	.= '<div class="snfw-message-close"></div>';

			$message_content 	.= ( isset( $message['link_1'] ) ) ? sprintf( '<a class="snfw-message-link" href="%s"></a>', esc_url($message['link_1']) ) : ''; 

			$message_content 	.= ( isset( $message['icon'] ) ) ? sprintf( '<div class="snfw-message-icon" style="background-image: url(%s)"></div>', esc_url($message['icon'])) : '<div class="snfw-message-icon"></div>'; // MESSAGE ICON
			
			$message_content 	.= '<div class="snfw-message-content"><div class="snfw-message-content-wrapper">'; 
			$message_content 	.= ( isset( $message['phrase'] ) ) ? sprintf( '<div class="snfw-message-phrase"><p>%s</p></div>', $message['phrase']) : ''; // MESSAGE PHRASE
			$message_content 	.= ( isset( $message['time'] ) ) ? sprintf( '<div class="snfw-message-time"><p>%s</p></div>', $message['time']) : ''; // MESSAGE TIME
			$message_content 	.= '</div></div><!-- .snfw-message-content -->';

			$message_content 	.= '</div><!-- .snfw-message -->'; 

			$message_content 	.= '<div class="snfw-message-buttons">';
			$message_content 	.= ( isset( $message['link_1'] ) ) ? sprintf( '<a href="%s" class="snfw-message-button">%s</a>', esc_url($message['link_1']), esc_html($message['link_text_1']) ) : '';
			$message_content 	.= ( isset( $message['link_2'] ) ) ? sprintf( '<a href="%s" class="snfw-message-button">%s</a>', esc_url($message['link_2']), esc_html($message['link_text_2']) ) : '';
			$message_content 	.= '</div><!-- .snfw-message-buttons -->';

			$message_content 	.= '</div><!-- .snfw-message -->'; 


			// DISPLAY MESSAGE
	        header('HTTP/1.1 200 OK');
	        echo $message_content;
	        echo $order_location_country;
	        exit();
		}


 
		/**
		 * ADD SETTINGS
		 *
		 */
		function snfw_settings_init() {

			// REGISTER SETTINGS
			register_setting( 'snfw', 'snfw_options' );


			// REGISTER DISPLAY SECTION
			add_settings_section( 

				// SECTION ID
				'snfw_section_display', 

				// SECTION TITLE
				esc_html__('Display', 'snfw'), 

				// SECTION CALLBACK
				function( $args ) {
					printf( '<p id="%s">%s</p>', esc_attr( $args['id'] ), esc_html__('You can control where the notifications are displayed.', 'snfw') );
				},

				// SECTION PAGE
				'snfw' );


			// REGISTER DISPLAY SECTION
			add_settings_section( 

				// SECTION ID
				'snfw_section_delays', 

				// SECTION TITLE
				esc_html__('Delays', 'snfw'), 

				// SECTION CALLBACK
				function( $args ) {
					printf( '<p id="%s">%s</p>', esc_attr( $args['id'] ), esc_html__('You can customize various settings for delays.', 'snfw') );
				},

				// SECTION PAGE
				'snfw' );


			// REGISTER DISPLAY SECTION
			add_settings_section( 

				// SECTION ID
				'snfw_section_privacy', 

				// SECTION TITLE
				esc_html__('Privacy', 'snfw'), 

				// SECTION CALLBACK
				function( $args ) {
					printf( '<p id="%s">%s</p>', esc_attr( $args['id'] ), esc_html__('The following options affect the privacy of information displayed on the frontend.', 'snfw') );
				},

				// SECTION PAGE
				'snfw' );


			// SETTINGS FIELD: PRIVACY NAMES
			add_settings_field( 

				// FIELD ID
				'snfw_field_privacy_names', 

				// FIELD TITLE
				esc_html__( 'Customer Names', 'snfw' ), 

				// FIELD CALLBACK
				function($args) {

					// GET THE VALUE OF THE SETTING WE'VE REGISTERED WITH REGISTER_SETTING()
					$options = get_option('snfw_options');

					// FIELD PROPERTIES
					$properties = array(
									'name' 			=> esc_html__('Show notifications on mobile devices.', 'snfw'),
									'placeholder' 	=> esc_html__('', 'snfw'),
									'description' 	=> esc_html__('', 'snfw'),
									'value' 		=> ( isset( $options[ $args['label_for'] ] ) ) ? $options[ $args['label_for'] ] : 'first' );

					// FIELD CHOICES
					$choices 	= array(
									array( 'value' => 'full', 'text' => 'Full name', 'selected' => ( $properties['value'] == 'full' ) ? 'selected' : '' ),
									array( 'value' => 'first', 'text' => 'First name', 'selected' => ( $properties['value'] == 'first' ) ? 'selected' : '' ),
									array( 'value' => 'none', 'text' => 'No names', 'selected' => ( $properties['value'] == 'none' ) ? 'selected' : '' ) );
					 	
					// OUTPUT THE FIELD
					printf( '<select id="%1$s" data-custom="%2$s" name="snfw_options[%1$s]">', esc_attr($args['label_for']), esc_attr($args['snfw_custom_data']) );
					foreach ($choices as $key => $choice) {
						printf( '<option value="%1$s" %3$s>%2$s</option>', esc_attr($choice['value']), esc_attr($choice['text']), esc_attr($choice['selected']) ); 
					}
					echo '</select>'; 
					
					// FIELD DESCRIPTION
					printf( '<p class="description">%s</p>',  esc_attr($properties['description']) );

				},

				// FIELD PAGE
				'snfw', 

				// FIELD SECTION
				'snfw_section_privacy', 

				// FIELD ARGS
				[ 'label_for' => 'snfw_field_privacy_names', 'class' => 'snfw_row', 'snfw_custom_data' => 'custom' ] 
			);


			// SETTINGS FIELD: PRIVACY NAMES
			add_settings_field( 

				// FIELD ID
				'snfw_field_privacy_location', 

				// FIELD TITLE
				esc_html__('Customer Location', 'snfw'), 

				// FIELD CALLBACK
				function($args) {

					// GET THE VALUE OF THE SETTING WE'VE REGISTERED WITH REGISTER_SETTING()
					$options = get_option( 'snfw_options' );

					// FIELD PROPERTIES
					$properties = array(
									'name' 			=> esc_html__('Show notifications on mobile devices.', 'snfw'),
									'placeholder' 	=> esc_html__('', 'snfw'),
									'description' 	=> esc_html__('', 'snfw'),
									'value' 		=> ( isset( $options[ $args['label_for'] ] ) ) ? $options[ $args['label_for'] ] : 'country' );

					// FIELD CHOICES
					$choices 	= array(
									array( 'value' => 'full', 'text' => 'City, Country', 'selected' => ( $properties['value'] == 'full' ) ? 'selected' : '' ),
									array( 'value' => 'country', 'text' => 'Country', 'selected' => ( $properties['value'] == 'country' ) ? 'selected' : '' ),
									array( 'value' => 'none', 'text' => 'No location', 'selected' => ( $properties['value'] == 'none' ) ? 'selected' : '' ) );
					 	
					// OUTPUT THE FIELD
					printf( '<select id="%1$s" data-custom="%2$s" name="snfw_options[%1$s]">', esc_attr($args['label_for']), esc_attr($args['snfw_custom_data']) );
					foreach ($choices as $key => $choice) {
						printf( '<option value="%1$s" %3$s>%2$s</option>', esc_attr($choice['value']), esc_attr($choice['text']), esc_attr($choice['selected']) ); 
					}
					echo '</select>'; 
					
					// FIELD DESCRIPTION
					printf( '<p class="description">%s</p>',  esc_attr($properties['description']) );

				},

				// FIELD PAGE
				'snfw', 

				// FIELD SECTION
				'snfw_section_privacy', 

				// FIELD ARGS
				[ 'label_for' => 'snfw_field_privacy_location', 'class' => 'snfw_row', 'snfw_custom_data' => 'custom' ] 
			);


			// SETTINGS FIELD: DISPLAY ON DESKTOP
			add_settings_field( 

				// FIELD ID
				'snfw_field_display_desktop', 

				// FIELD TITLE
				esc_html__('Desktop', 'snfw'), 

				// FIELD CALLBACK
				function($args) {

					// GET THE VALUE OF THE SETTING WE'VE REGISTERED WITH REGISTER_SETTING()
					$options = get_option('snfw_options');

					// FIELD PROPERTIES
					$properties = array(
									'name' 			=> esc_html__('Hide notifications on desktop browsers.', 'snfw'),
									'placeholder' 	=> esc_html__('', 'snfw'),
									'description' 	=> esc_html__('', 'snfw'),
									'class' 		=> esc_html__('', 'snfw'),
									'state' 		=> ( isset( $options[ $args['label_for'] ] ) ) ? checked( $options[ $args['label_for'] ], 'on', false ) : '' );
					 	
					// OUTPUT THE FIELD
					printf( '<fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><label for="%1$s"><input name="snfw_options[%1$s]" id="%1$s" type="checkbox" class="snfw-checkbox" %3$s>%2$s</label></fieldset>', esc_attr($args['label_for']), esc_attr($properties['name']), esc_attr($properties['state']) );

					// FIELD DESCRIPTION
					printf( '<p class="description">%s</p>',  esc_attr($properties['description']) );

				},

				// FIELD PAGE
				'snfw', 

				// FIELD SECTION
				'snfw_section_display', 

				// FIELD ARGS
				[ 'label_for' => 'snfw_field_display_desktop', 'class' => 'snfw-options-row', 'snfw_custom_data' => 'custom' ] 
			);


			// SETTINGS FIELD: DISPLAY ON MOBILE
			add_settings_field( 

				// FIELD ID
				'snfw_field_display_mobile', 

				// FIELD TITLE
				esc_html__('Mobile', 'snfw'), 

				// FIELD CALLBACK
				function($args) {

					// GET THE VALUE OF THE SETTING WE'VE REGISTERED WITH REGISTER_SETTING()
					$options = get_option('snfw_options');

					// FIELD PROPERTIES
					$properties = array(
									'name' => esc_html__('Hide notifications on mobile devices.', 'snfw'),
									'placeholder' => esc_html__('', 'snfw'),
									'description' => esc_html__('', 'snfw'),
									'state' => ( isset( $options[ $args['label_for'] ] ) ) ? checked( $options[ $args['label_for'] ], 'on', false ) : '' );

					 	
					// OUTPUT THE FIELD
					printf( '<fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><label for="%1$s"><input name="snfw_options[%1$s]" id="%1$s" type="checkbox" class="snfw-checkbox" %3$s>%2$s</label></fieldset>', esc_attr($args['label_for']), esc_attr($properties['name']), esc_attr($properties['state']) );

					// FIELD DESCRIPTION
					printf( '<p class="description">%s</p>',  esc_attr($properties['description']) );

				},

				// FIELD PAGE
				'snfw', 

				// FIELD SECTION
				'snfw_section_display', 

				// FIELD ARGS
				[ 'label_for' => 'snfw_field_display_mobile', 'class' => 'snfw-options-row', 'snfw_custom_data' => 'custom' ] 
			);


			// SETTINGS FIELD: DELAYS INITIAL
			add_settings_field( 

				// FIELD ID
				'snfw_field_delays_initial', 

				// FIELD TITLE
				esc_html__('Initail delay', 'snfw'), 

				// FIELD CALLBACK
				function($args) {

					// GET THE VALUE OF THE SETTING WE'VE REGISTERED WITH REGISTER_SETTING()
					$options = get_option('snfw_options');

					// FIELD PROPERTIES
					$properties = array(
									'name' 			=> esc_html__('Show notifications on mobile devices.', 'snfw'),
									'placeholder' 	=> esc_html__('', 'snfw'),
									'description' 	=> esc_html__('(in seconds) time before the first notification will be displayed.', 'snfw'),
									'min' 			=> '1',
									'max' 			=> '1200',
									'step' 			=> '1',
									'value' 		=> ( isset( $options[ $args['label_for'] ] ) ) ? $options[ $args['label_for'] ] : '5' );
					 	
					// OUTPUT THE FIELD
					printf( '<input name="snfw_options[%1$s]" id="%1$s" type="number" style="width: 80px;" value="%3$s" class="manage_stock_field" placeholder="%5$s" min="%4$s" step="%6$s">', esc_attr($args['label_for']), esc_attr($properties['name']), esc_attr($properties['value']), esc_attr($properties['min']), esc_attr($properties['max']), esc_attr($properties['step']), esc_attr($properties['placeholder']) );

					// FIELD DESCRIPTION
					printf( '<span class="description">%s</span>',  esc_attr($properties['description']) );

				},

				// FIELD PAGE
				'snfw', 

				// FIELD SECTION
				'snfw_section_delays', 

				// FIELD ARGS
				[ 'label_for' => 'snfw_field_delays_initial', 'class' => 'snfw-options-row', 'snfw_custom_data' => 'custom' ] 
			);


			// SETTINGS FIELD: DELAYS DISPLAY
			add_settings_field( 

				// FIELD ID
				'snfw_field_delays_display', 

				// FIELD TITLE
				esc_html__('Display delay', 'snfw'), 

				// FIELD CALLBACK
				function($args) {

					// GET THE VALUE OF THE SETTING WE'VE REGISTERED WITH REGISTER_SETTING()
					$options = get_option('snfw_options');

					// FIELD PROPERTIES
					$properties = array(
									'name' 			=> esc_html__('Show notifications on mobile devices.', 'snfw'),
									'placeholder' 	=> esc_html__('', 'snfw'),
									'description' 	=> esc_html__('(in seconds) time between notifications.', 'snfw'),
									'min' 			=> '1',
									'max' 			=> '1200',
									'step' 			=> '1',
									'value' 		=> ( isset( $options[ $args['label_for'] ] ) ) ? $options[ $args['label_for'] ] : '10' );
					 	
					// OUTPUT THE FIELD
					printf( '<input name="snfw_options[%1$s]" id="%1$s" type="number" style="width: 80px;" value="%3$s" class="manage_stock_field" placeholder="%5$s" min="%4$s" step="%6$s">', esc_attr($args['label_for']), esc_attr($properties['name']), esc_attr($properties['value']), esc_attr($properties['min']), esc_attr($properties['max']), esc_attr($properties['step']), esc_attr($properties['placeholder']) );

					// FIELD DESCRIPTION
					printf( '<span class="description">%s</span>',  esc_attr($properties['description']) );

				},

				// FIELD PAGE
				'snfw', 

				// FIELD SECTION
				'snfw_section_delays', 

				// FIELD ARGS
				[ 'label_for' => 'snfw_field_delays_display', 'class' => 'snfw-options-row', 'snfw_custom_data' => 'custom' ] 
			);


			// SETTINGS FIELD: DELAYS TIME
			add_settings_field( 

				// FIELD ID
				'snfw_field_delays_time', 

				// FIELD TITLE
				esc_html__('Display time', 'snfw'), 

				// FIELD CALLBACK
				function($args) {

					// GET THE VALUE OF THE SETTING WE'VE REGISTERED WITH REGISTER_SETTING()
					$options = get_option('snfw_options');

					// FIELD PROPERTIES
					$properties = array(
									'name' 			=> esc_html__('Show notifications on mobile devices.', 'snfw'),
									'placeholder' 	=> esc_html__('', 'snfw'),
									'description' 	=> esc_html__('(in seconds) time each notification will be displayed.', 'snfw'),
									'min' 			=> '1',
									'max' 			=> '1200',
									'step' 			=> '1',
									'value' 		=> ( isset( $options[ $args['label_for'] ] ) ) ? $options[ $args['label_for'] ] : '5' );
					 	
					// OUTPUT THE FIELD
					printf( '<input name="snfw_options[%1$s]" id="%1$s" type="number" style="width: 80px;" value="%3$s" class="manage_stock_field" placeholder="%5$s" min="%4$s" step="%6$s">', esc_attr($args['label_for']), esc_attr($properties['name']), esc_attr($properties['value']), esc_attr($properties['min']), esc_attr($properties['max']), esc_attr($properties['step']), esc_attr($properties['placeholder']) );

					// FIELD DESCRIPTION
					printf( '<span class="description">%s</span>',  esc_attr($properties['description']) );

				},

				// FIELD PAGE
				'snfw', 

				// FIELD SECTION
				'snfw_section_delays', 

				// FIELD ARGS
				[ 'label_for' => 'snfw_field_delays_time', 'class' => 'snfw-options-row', 'snfw_custom_data' => 'custom' ] 
			);

		}
 


		/**
		 * ADD SETTINGS PAGE 
		 *
		 */
		function snfw_options_page() {

			add_options_page( esc_html__('Sales Notifications Settings', 'snfw'), esc_html__('Sales Notifications', 'snfw'), 'manage_options', 'snfw', function() {

			 	// CHECK USER CAPABILITIES
			 	if ( ! current_user_can( 'manage_options' ) ) {
			 		return;
		 		}
			 
				// ADD ERROR/UPDATE MESSAGES
				// CHECK IF THE USER HAVE SUBMITTED THE SETTINGS WORDPRESS WILL ADD THE "SETTINGS-UPDATED" $_GET PARAMETER TO THE URL
				if ( isset( $_GET['settings-updated'] ) ) { 

					// ADD SETTINGS SAVED MESSAGE WITH THE CLASS OF "UPDATED"
					//add_settings_error( 'snfw_messages', 'snfw_message', __( 'Settings Saved', 'snfw' ), 'updated' );
				}
			 
			 	// SHOW ERROR/UPDATE MESSAGES
			 	settings_errors( 'snfw_messages' );

			 	// OUTPUT FORM OPEN
			 	echo '<div class="wrap">';
			 	echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
			 	echo '<form action="options.php" method="post">';

			 	// OUTPUT SECURITY FIELDS FOR THE REGISTERED SETTING "WPORG"
			 	settings_fields('snfw'); 

		 		// OUTPUT SETTING SECTIONS AND THEIR FIELDS
			 	// (SECTIONS ARE REGISTERED FOR "WPORG", EACH FIELD IS REGISTERED TO A SPECIFIC SECTION)
			 	do_settings_sections('snfw');

			 	// OUTPUT SAVE SETTINGS BUTTON 
			 	submit_button('Save Settings'); 

			 	// OUTPUT FORM CLOSE
			 	echo '</form></div>';
			 	
			});

			// ADD SETTINGS PAGE LINK TO PLUGIN LISTING 
			add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function ( $actions, $plugin_file, $plugin_data, $context ) {
				array_unshift( $actions, sprintf( '<a href="%s">%s</a>', menu_page_url( 'snfw', false ), esc_html__('Settings') ) );
				return $actions;
			}, 10, 4);


		}

	}
endif;
$GLOBALS['snfw'] = new SNFW(); 



/**
 * GET PLUGIN URL
 *
 */
function snfw_plugin_url( $path = '' ) {
	
	$url = plugins_url( $path, __FILE__ );

	if ( is_ssl() && 'http:' == substr( $url, 0, 5 ) ) {
		$url = 'https:' . substr( $url, 5 );
	}

	return $url;
}
 


/**
 * GET SETTING VALUE
 *
 */
function snfw_get_setting( $name, $default = '' ) {
	if ( empty( $name ) ) 
		return false;

	$options = get_option('snfw_options');
	$setting = ( isset( $options['snfw_field_' . $name] ) ) ? $options['snfw_field_' . $name] : '';
	$setting = ( empty( $setting ) && !empty( $default ) ) ? $default : $setting;

	return $setting;
} ?>