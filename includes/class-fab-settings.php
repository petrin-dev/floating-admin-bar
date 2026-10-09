<?php
/**
 * Settings page for choosing which admin bar items to hide from the floating widget.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FAB_Settings {

	const OPTION       = 'fab_hidden_items';
	const COLOR_OPTION = 'fab_colors';
	const USER_META    = 'fab_use_floating_bar';

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_profile_script' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_assets' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_preference' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_preference' ) );
	}

	/**
	 * Default bg/text colors, matching the native admin bar's dark palette.
	 */
	public function default_colors() {
		return array(
			'bg'   => '#1d2327',
			'text' => '#eeeeee',
		);
	}

	public function get_colors() {
		$colors = get_option( self::COLOR_OPTION, array() );
		$colors = is_array( $colors ) ? $colors : array();

		return wp_parse_args( $colors, $this->default_colors() );
	}

	public function enqueue_settings_assets( $hook_suffix ) {
		if ( 'settings_page_floating-admin-bar' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery( function ( $ ) { $( ".fab-color-field" ).wpColorPicker(); } );' );
	}

	/**
	 * Whether the given user (defaults to the current user) wants the floating bar. Defaults
	 * to true when unset so existing installs keep their current behavior. This only matters
	 * while "Show Toolbar when viewing site" is also on — if that's off, neither bar renders.
	 */
	public function get_user_preference( $user_id = 0 ) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		$value   = get_user_meta( $user_id, self::USER_META, true );

		return '' === $value || '1' === $value;
	}

	public function save_user_preference( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- core already verified the profile form's nonce before firing this action.
		update_user_meta( $user_id, self::USER_META, isset( $_POST[ self::USER_META ] ) ? '1' : '0' );
	}

	/**
	 * Injects a "Use Floating Toolbar" checkbox directly below core's own "Show Toolbar when
	 * viewing site" row on the profile screen. Core doesn't expose a hook at that exact spot
	 * (the next one, `personal_options`, fires much further down the form), so we insert it
	 * with JS instead of trying to recreate the row server-side in the wrong place.
	 */
	public function enqueue_profile_script( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'profile.php', 'user-edit.php' ), true ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only; just picks which profile's data to localize for display.
		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : get_current_user_id();

		wp_enqueue_script(
			'fab-profile',
			FAB_PLUGIN_URL . 'assets/js/fab-profile.js',
			array(),
			FAB_VERSION,
			true
		);

		wp_localize_script(
			'fab-profile',
			'fabProfileData',
			array(
				'fieldName' => self::USER_META,
				'label'     => __( 'Use the floating toolbar instead of the classic admin bar', 'floating-admin-bar' ),
				'checked'   => $this->get_user_preference( $user_id ),
			)
		);
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

		register_setting(
			'fab_settings_group',
			self::COLOR_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_colors' ),
				'default'           => $this->default_colors(),
			)
		);

		add_settings_field(
			'fab_colors',
			__( 'Colors', 'floating-admin-bar' ),
			array( $this, 'render_colors_field' ),
			'floating-admin-bar',
			'fab_main_section'
		);
	}

	public function sanitize( $value ) {
		$allowed = array_keys( $this->hideable_items() );
		$value   = is_array( $value ) ? $value : array();

		return array_values( array_intersect( $value, $allowed ) );
	}

	public function sanitize_colors( $value ) {
		$defaults = $this->default_colors();
		$value    = is_array( $value ) ? $value : array();
		$colors   = array();

		foreach ( $defaults as $key => $default ) {
			$color          = isset( $value[ $key ] ) ? sanitize_hex_color( $value[ $key ] ) : '';
			$colors[ $key ] = $color ? $color : $default;
		}

		return $colors;
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

	public function render_colors_field() {
		$colors   = $this->get_colors();
		$defaults = $this->default_colors();
		$fields   = array(
			'bg'   => __( 'Background', 'floating-admin-bar' ),
			'text' => __( 'Text', 'floating-admin-bar' ),
		);

		foreach ( $fields as $key => $label ) {
			printf(
				'<p><label for="fab_colors_%1$s" style="display:block;margin-bottom:4px;">%2$s</label>' .
				'<input type="text" id="fab_colors_%1$s" name="%3$s[%1$s]" value="%4$s" class="fab-color-field" data-default-color="%5$s" /></p>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( self::COLOR_OPTION ),
				esc_attr( $colors[ $key ] ),
				esc_attr( $defaults[ $key ] )
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
