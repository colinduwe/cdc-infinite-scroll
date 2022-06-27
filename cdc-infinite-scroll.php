<?php
/**
 * Plugin Name:       CDC Infinite Scroll
 * Description:       Provides an infinite scroll scroll effect for block-based themes.
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Colin Duwe
 * Author URI:		  https://www.colinduwe.com/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       cdc-infinite-scroll
 *
 * @package           create-block
 */

class CDC_Infinite_Scroll {

	private $infinite_scroll_options_default = '';
	private $masonry_options_default = '';
	private $query_class_default = '.wp-block-post-template';
	private $post_class_default = 'wp-block-post';
	private $enable_masonry_default = 'on';

	public function __construct(){
		add_action( 'init', array( $this, 'block_init' ) );
		add_filter( 'render_block', array( $this, 'enqueue_scripts' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'not_a_block_theme_notice' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_options_page' ) );

		$this->init_options();
	}

	function init_options(){
		if( file_exists( __DIR__ . '/assets/infinite-scroll-options.json' ) ){
			$this->infinite_scroll_options_default = file_get_contents( __DIR__ . '/assets/infinite-scroll-options.json' );
		}
		if( file_exists( __DIR__ . '/assets/masonry-options.json' ) ){
			$this->masonry_options_default = file_get_contents( __DIR__ . '/assets/masonry-options.json' );
		}
	}

	/**
	 * Registers the block using the metadata loaded from the `block.json` file.
	 * Behind the scenes, it registers also all assets so they can be enqueued
	 * through the block editor in the corresponding context.
	 *
	 * @see https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/writing-your-first-block-type/
	 */
	public function block_init() {
		register_block_type(
			__DIR__ ,
			array(
				'render_callback' => [ $this, 'render_block_infinite_scroll' ],
			)

		);
	}

	/**
	 * Renders the `cdc/infinite-scroll` block on the server.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block default content.
	 *
	 * @return string Returns the wrapper for the Query pagination.
	 */

	public function render_block_infinite_scroll( $attributes, $content ) {
		if ( empty( trim( $content ) ) ) {
			return '';
		}
		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes(),
			$content
		);
	}

	public function enqueue_scripts( $block_content, $block ){
		// Make sure we have the blockName.
		if ( empty( $block['blockName'] ) ) {
			return $block_content;
		}

		// If this is a pagination block, enqueue the pagination script.
		if (
			'core/query-pagination' === $block['blockName'] ||
			'core/query-pagination-next' === $block['blockName'] ||
			'core/query-pagination-previous' === $block['blockName'] ||
			'core/query-pagination-numbers' === $block['blockName']
		) {
			wp_enqueue_script( 'infinite-scroll', plugin_dir_url( __FILE__ ) . 'assets/infinite-scroll.pkgd.min.js', array(), true, true );
			if( get_option( 'cdc_infinite_scroll_options_masonry_checkbox', 'on' ) ){
				wp_enqueue_script( 'cdc-infinite-scroll', plugin_dir_url( __FILE__ ) . 'assets/cdc-infinite-scroll-front-end.js', array('infinite-scroll', 'masonry'), '0.1.0', true );
			} else {
				wp_enqueue_script( 'cdc-infinite-scroll', plugin_dir_url( __FILE__ ) . 'assets/cdc-infinite-scroll-front-end.js', array('infinite-scroll'), '0.1.0', true );
			}
			wp_localize_script( 'cdc-infinite-scroll', 'cdcInfiniteScrollSettings', array(
				'queryClass'	=> get_option( 'cdc_infinite_scroll_query_class', $this->query_class_default ),
				'postClass'		=> get_option( 'cdc_infinite_scroll_post_class', $this->post_class_default ),
				'enableMasonry' => get_option( 'cdc_infinite_scroll_options_masonry_checkbox', $this->enable_masonry_default ),
				'scrollOptions'	=> get_option( 'cdc_infinite_scroll_options_obj', $this->infinite_scroll_options_default ),
				'masonryOptions'=> get_option( 'cdc_infinite_scroll_masonry_options_obj', $this->masonry_options_default )
			) );
		}

		// Return the block content.
		return $block_content;
	}

	static function activate() {
		if( ! wp_is_block_theme() ){
			set_transient( 'cdc_infinite_scroll_activation_failure', true, 5 );
		}
	}

	public function not_a_block_theme_notice() {
		if( get_transient( 'cdc_infinite_scroll_activation_failure' ) ){
			deactivate_plugins( plugin_basename( __FILE__ ) );
			?>
			<div class="notice notice-error">
				<p>
					<?php _e( 'Uh oh! Your current theme isn\'t a block theme so the infinite scroll plugin won\'t work.', 'cdc-infinite-scroll' ); ?>
				</p>
			</div>
			<?php
		}
	}

	public function register_settings(){

		register_setting(
			'cdc_infinite_scroll_options_group',
			'cdc_infinite_scroll_query_class',
			array(
				'sanatize_callback' => 'sanatize_text_field',
				'default' => $this->query_class_default
			),
		);

		register_setting(
			'cdc_infinite_scroll_options_group',
			'cdc_infinite_scroll_post_class',
			array(
				'sanatize_callback' => 'sanatize_text_field',
				'default' => $this->post_class_default
			),
		);

		register_setting(
			'cdc_infinite_scroll_options_group',
			'cdc_infinite_scroll_options_obj',
			array(
				'sanatize_callback' => 'sanatize_textarea_field',
				'default' => esc_attr( $this->infinite_scroll_options_default ),
			),
		);

		register_setting(
			'cdc_infinite_scroll_options_group',
			'cdc_infinite_scroll_options_masonry_checkbox',
			array(
				'sanatize_callback' => array( $this, 'options_validation_cb' ),
				'default' => $this->enable_masonry_default
			),
		);

		register_setting(
			'cdc_infinite_scroll_options_group',
			'cdc_infinite_scroll_masonry_options_obj',
			array(
				'sanatize_callback' => 'sanatize_textarea_field',
				'default' => esc_attr( $this->masonry_options_default )
			),
		);

		add_settings_section(
			'cdc_infinite_scroll_scroll_settings_section',
			__('Configure Infinite Scroll'),
			array( $this, 'settings_section_cb'),
			'cdc-infinite-scroll'
		);

		add_settings_field(
			'cdc_infinite_scroll_query_class',
			__('Query Block Class'),
			array( $this, 'query_class_html' ),
			'cdc-infinite-scroll',
			'cdc_infinite_scroll_scroll_settings_section',
			array(
				'label_for' => 'cdc_infinite_scroll_query_class',
				'class' => 'cdc-infinite-scroll-input',
			)
		);

		add_settings_field(
			'cdc_infinite_scroll_post_class',
			__('Post Class'),
			array( $this, 'post_class_html' ),
			'cdc-infinite-scroll',
			'cdc_infinite_scroll_scroll_settings_section',
			array(
				'label_for' => 'cdc_infinite_scroll_post_class',
				'class' => 'cdc-infinite-scroll-input',
			)
		);

		add_settings_field(
			'cdc_infinite_scroll_options_obj',
			__('Infinite Scroll Options'),
			array( $this, 'infinite_scroll_options_obj_html' ),
			'cdc-infinite-scroll',
			'cdc_infinite_scroll_scroll_settings_section',
			array(
				'label_for' => 'cdc_infinite_scroll_options_obj',
				'class' => 'cdc-infinite-scroll-input',
			)
		);

		add_settings_field(
			'cdc_infinite_scroll_options_masonry_checkbox',
			__('Enable Masonry Effect'),
			array( $this, 'infinite_scroll_options_masonry_checkbox_html' ),
			'cdc-infinite-scroll',
			'cdc_infinite_scroll_scroll_settings_section',
			array(
				'label_for' => 'cdc_infinite_scroll_options_masonry_checkbox',
				'class' => 'cdc-infinite-scroll-input',
			)
		);

		add_settings_field(
			'cdc_infinite_scroll_masonry_options_obj',
			__('Masonry Options'),
			array( $this, 'masonry_options_obj_html' ),
			'cdc-infinite-scroll',
			'cdc_infinite_scroll_scroll_settings_section',
			array(
				'label_for' => 'cdc_infinite_scroll_masonry_options_obj',
				'class' => 'cdc-infinite-scroll-input',
			)
		);

	}

	public function options_validation_cb( $input ){

		$this->write_log ( $input );

		return $input;
	}

	public function settings_section_cb() {
		?>
<p>You can modify these values if your site does not use the default query and pagination block markup.</p>
		<?php
	}
	//The html input field’s name attribute must match $option_name in register_setting(), and value can be filled using get_option().
	public function query_class_html(){
		$query_class = get_option( 'cdc_infinite_scroll_query_class' );
		printf(
			'<input type="text" id="cdc_infinite_scroll_query_class" name="cdc_infinite_scroll_query_class" size="46" value="%s" data-default=".wp-block-post-template"><p>The <strong>Query Block Class</strong> tells the javascript which element on the page contains the list of posts.<br>It must have the leading period.</p>',
			$query_class
		);
	}

	public function post_class_html(){
		$post_class = get_option( 'cdc_infinite_scroll_post_class' );
		printf(
			'<input type="text" id="cdc_infinite_scroll_post_class" name="cdc_infinite_scroll_post_class" size="46" value="%s" data-default="wp-block-post"><p>The <strong>Post Class</strong> tells the javascript which elements inside the query block are posts.<br>It should not have the leading period.</p>',
			$post_class
		);
	}

	public function infinite_scroll_options_obj_html(){
		$scroll_optons_json = get_option( 'cdc_infinite_scroll_options_obj' );
		printf(
			'<textarea id="cdc_infinite_scroll_options_obj" name="cdc_infinite_scroll_options_obj" rows="12" cols="46" data-default="%s">%s</textarea><p>Please refer to the <a href="https://infinite-scroll.com/options.html">Infinite Scroll Options</a> documentation for complete details.<br>You likely want to remove "outlayer" : "msnry" option if you are not using the Masonry effect.</p>',
			esc_attr( json_encode( json_decode( $this->infinite_scroll_options_default ) ) ),
			$scroll_optons_json
		);
	}

	public function infinite_scroll_options_masonry_checkbox_html(){
		$masonry_enabled = get_option( 'cdc_infinite_scroll_options_masonry_checkbox' ) ? ' checked ' : '';
		echo '<input type="checkbox" id="cdc_infinite_scroll_options_masonry_checkbox" name="cdc_infinite_scroll_options_masonry_checkbox"' . $masonry_enabled . '><p>Tick the box to enable the Masonry effect when loading new posts into the page.</p>';
	}

	public function masonry_options_obj_html(){
		$masonry_optons_json = get_option( 'cdc_infinite_scroll_masonry_options_obj' );
		printf(
			'<textarea id="cdc_infinite_scroll_masonry_options_obj" name="cdc_infinite_scroll_masonry_options_obj" rows="12" cols="46" data-default="%s">%s</textarea><p>Refer to the <a href="https://masonry.desandro.com/options.html">Masonry Options</a> for details on how to configure it. But note that this plugin will calculate the column width and gutter if you use the default query block markup and styles.</p><p><strong>Note:</strong> The default masonry options for column width ("posts[columnsEndOfRow]") and gutter ("gutter") are transformed into javascript variables but you can use normal string selectors if you\'re not using the default markup.</p>',
			esc_attr( json_encode( json_decode( $this->masonry_options_default ) ) ),
			$masonry_optons_json
		);
	}


	public function register_options_page(){
		$options_page = add_options_page(
			'Infinite Scroll Options',
			'Infinite Scroll',
			'manage_options',
			'cdc-infinite-scroll',
			array( $this, 'options_page' )
		);
		// Load the JS conditionally
		add_action( 'load-' . $options_page, array( $this, 'load_admin_js' ) );
	}

	public function load_admin_js(){
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_js' ) );
	}

	public function enqueue_admin_js(){
		wp_enqueue_script( 'infinite-scroll-admin', plugin_dir_url( __FILE__ ) . 'assets/cdc-infinite-scroll-admin.js', array(), true, true );
	}

	public function options_page(){
    	//must check that the user has the required capability
	    if (!current_user_can('manage_options')){
	      wp_die( __('You do not have sufficient permissions to access this page.') );
	    }

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form id="cdc-infinite-scroll-option-form" method="post" action="options.php">
				<?php settings_fields( 'cdc_infinite_scroll_options_group' ); ?>
				<?php do_settings_sections( 'cdc-infinite-scroll' ); ?>
				<?php submit_button(
					'Save Settngs',
					'primary',
					'submit-btn'
				); ?>
				<?php submit_button(
					'Reset Settings',
					'secondary',
					'cdc-infinite-scroll-reset',
					true,
					array( 'id' => 'cdc-infinite-scroll-reset' )
				); ?>
			</form>
		</div>
		<?php
	}

	public function write_log ( $log )  {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}

}

$cdcInfiniteScroll = new CDC_Infinite_Scroll();

register_activation_hook( __FILE__, array( 'CDC_Infinite_Scroll', 'activate' ) );
