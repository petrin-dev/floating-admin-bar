<?php
/**
 * Settings page for choosing which admin bar items to hide from the floating widget.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FAB_Settings {

	const OPTION = 'fab_hidden_items';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Top-level admin bar node IDs the settings page allows hiding, keyed to their
	 * user-facing label.
	 */
	private function hideable_items() {
		return array(
			'wp-logo'  => __( 'About WordPress', 'floating-admin-bar' ),
			'comments' => __( 'Comments', 'floating-admin-bar' ),
		);
	}

	public function get_hidden_items() {
		$hidden = get_option( self::OPTION, array() );

		return is_array( $hidden ) ? $hidden : array();
	}

	public function add_settings_page() {
		add_options_page(
			__( 'Floating Admin Bar', 'floating-admin-bar' ),
			__( 'Floating Admin Bar', 'floating-admin-bar' ),
			'manage_options',
			'floating-admin-bar',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'fab_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			'fab_main_section',
			__( 'Menu Items', 'floating-admin-bar' ),
			'__return_false',
			'floating-admin-bar'
		);

		add_settings_field(
			'fab_hidden_items',
			__( 'Hide from the floating bar', 'floating-admin-bar' ),
			array( $this, 'render_hidden_items_field' ),
			'floating-admin-bar',
			'fab_main_section'
		);
	}

	public function sanitize( $value ) {
		$allowed = array_keys( $this->hideable_items() );
		$value   = is_array( $value ) ? $value : array();

		return array_values( array_intersect( $value, $allowed ) );
	}

	public function render_hidden_items_field() {
		$hidden = $this->get_hidden_items();

		foreach ( $this->hideable_items() as $id => $label ) {
			printf(
				'<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="%1$s[]" value="%2$s" %3$s /> %4$s</label>',
				esc_attr( self::OPTION ),
				esc_attr( $id ),
				checked( in_array( $id, $hidden, true ), true, false ),
				esc_html( $label )
			);
		}
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Floating Admin Bar', 'floating-admin-bar' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'fab_settings_group' );
				do_settings_sections( 'floating-admin-bar' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
