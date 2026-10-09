<?php
/**
 * Captures WordPress's admin bar node tree and renders it as a floating widget instead.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FAB_Loader {

	private static $instance = null;

	private $menu_tree = array();

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_bar_menu', array( $this, 'capture_nodes' ), 999 );
		add_action( 'init', array( $this, 'suppress_native_render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * Stop WordPress from printing the native #wpadminbar markup on the front end only —
	 * the back end keeps its native admin bar. We still let `admin_bar_menu` fire (see
	 * force_admin_bar_build()) so every other plugin's add_node() calls keep working. Users
	 * who've opted out of the floating bar (see FAB_Settings) keep the native front-end bar.
	 */
	public function suppress_native_render() {
		if ( ! FAB_Settings::instance()->get_user_preference() ) {
			return;
		}

		remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
	}

	/**
	 * Marks <body> so our stylesheet can cancel core's `html { margin-top }` admin bar bump
	 * (see the comment above that rule in floating-admin-bar.css) — only when we're actually
	 * the ones rendering a bar, so users who opted for the native bar keep its spacing intact.
	 */
	public function add_body_class( $classes ) {
		if ( is_admin_bar_showing() && FAB_Settings::instance()->get_user_preference() ) {
			$classes[] = 'floating-admin-bar';
		}

		return $classes;
	}

	public function capture_nodes( $wp_admin_bar ) {
		$tree   = $this->build_tree( $wp_admin_bar->get_nodes() );
		$hidden = FAB_Settings::instance()->get_hidden_items();

		if ( ! empty( $hidden ) ) {
			$tree = array_values(
				array_filter(
					$tree,
					function ( $node ) use ( $hidden ) {
						return ! in_array( $node['id'], $hidden, true );
					}
				)
			);
		}

		$this->menu_tree = $tree;
	}

	/**
	 * `admin_bar_menu` only fires from inside wp_admin_bar_render(), which core normally
	 * calls late (wp_footer/admin_footer). We call it ourselves, early, inside an output
	 * buffer so we can capture the populated node tree in time to localize it — while
	 * discarding the HTML it would have printed.
	 */
	private function force_admin_bar_build() {
		global $wp_admin_bar;

		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! is_object( $wp_admin_bar ) ) {
			_wp_admin_bar_init();
		}

		if ( ! is_object( $wp_admin_bar ) ) {
			return;
		}

		ob_start();
		wp_admin_bar_render();
		ob_end_clean();
	}

	public function enqueue() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! FAB_Settings::instance()->get_user_preference() ) {
			return;
		}

		$this->force_admin_bar_build();

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'floating-admin-bar', FAB_PLUGIN_URL . 'assets/css/floating-admin-bar.css', array( 'dashicons' ), FAB_VERSION );
		wp_enqueue_script( 'floating-admin-bar', FAB_PLUGIN_URL . 'assets/js/floating-admin-bar.js', array(), FAB_VERSION, true );

		$colors = FAB_Settings::instance()->get_colors();
		wp_add_inline_style(
			'floating-admin-bar',
			sprintf(
				'#fab-widget { --fab-bg: %s; --fab-text: %s; }',
				esc_html( $colors['bg'] ),
				esc_html( $colors['text'] )
			)
		);

		wp_localize_script(
			'floating-admin-bar',
			'fabData',
			array(
				'menu'    => $this->menu_tree,
				'iconMap' => $this->icon_map(),
			)
		);
	}

	/**
	 * Flattens WP_Admin_Bar's node list into a nested tree rooted at 'root'. Group nodes
	 * (pure layout containers like 'top-secondary') are transparent: their children are
	 * hoisted up to the group's own parent instead of being nested under the group.
	 */
	private function build_tree( $nodes ) {
		if ( empty( $nodes ) ) {
			return array();
		}

		$by_parent = array();

		foreach ( $nodes as $node ) {
			$parent                 = $node->parent ? $node->parent : 'root';
			$by_parent[ $parent ][] = $node;
		}

		return $this->collect_children( 'root', $by_parent );
	}

	private function collect_children( $parent_id, $by_parent ) {
		$result = array();

		if ( empty( $by_parent[ $parent_id ] ) ) {
			return $result;
		}

		foreach ( $by_parent[ $parent_id ] as $node ) {
			$children = $this->collect_children( $node->id, $by_parent );

			if ( ! empty( $node->group ) ) {
				$result = array_merge( $result, $children );
				continue;
			}

			$meta  = (array) $node->meta;
			$html  = ! empty( $meta['html'] ) ? $meta['html'] : '';
			$label = html_entity_decode( wp_strip_all_tags( $node->title ), ENT_QUOTES );

			if ( '' === $label && '' === $html && empty( $children ) && empty( $node->href ) ) {
				continue;
			}

			// Core builds hrefs/titles already HTML-escaped for inline markup (e.g. "&amp;"
			// in query strings). We hand these to JS as data, not markup, so there's no HTML
			// parser to decode them back — decode now or links like edit.php?post=1&action=edit
			// break on the undecoded "&amp;".
			$result[] = array(
				'id'       => $node->id,
				'label'    => $label,
				'href'     => $node->href ? esc_url_raw( html_entity_decode( $node->href, ENT_QUOTES ) ) : '',
				'target'   => ! empty( $meta['target'] ) ? $meta['target'] : '',
				'title'    => ! empty( $meta['title'] ) ? html_entity_decode( wp_strip_all_tags( $meta['title'] ), ENT_QUOTES ) : '',
				'html'     => $html,
				'children' => $children,
			);
		}

		return $result;
	}

	private function icon_map() {
		return array(
			'wp-logo'      => 'dashicons-wordpress',
			'site-name'    => 'dashicons-admin-home',
			'view-site'    => 'dashicons-admin-home',
			'my-sites'     => 'dashicons-networking',
			'updates'      => 'dashicons-update',
			'comments'     => 'dashicons-admin-comments',
			'new-content'  => 'dashicons-plus-alt',
			'new-post'     => 'dashicons-edit',
			'new-media'    => 'dashicons-admin-media',
			'new-link'     => 'dashicons-admin-links',
			'new-page'     => 'dashicons-admin-page',
			'new-user'     => 'dashicons-admin-users',
			'my-account'   => 'dashicons-admin-users',
			'user-info'    => 'dashicons-admin-users',
			'edit-profile' => 'dashicons-id',
			'logout'       => 'dashicons-migrate',
			'search'       => 'dashicons-search',
			'customize'    => 'dashicons-admin-customizer',
			'edit'         => 'dashicons-edit',
			'view'         => 'dashicons-visibility',
			'dashboard'    => 'dashicons-dashboard',
		);
	}
}
