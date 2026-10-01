=== Floating Admin Bar ===
Contributors: robpetrin
Tags: admin bar, toolbar, dashboard, menu, accessibility
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A compact, draggable, grid-based replacement for the WordPress admin bar on the front end.

== Description ==

Floating Admin Bar replaces the classic front-end admin bar with a small, draggable widget that expands into a grid of icons on hover or tap. It captures whatever WordPress core and other plugins register on the admin bar — via the standard `admin_bar_menu` hook — and re-renders it as a floating widget instead, so nothing needs to be re-registered or configured.

**Key features**

* Drag the handle anywhere on screen; its position is remembered per browser.
* Hover (desktop) or tap (touch) to expand the grid; nested items open in a flyout.
* Fully keyboard-operable: Tab to the handle, Enter/Space to open, Tab through items, Escape to close.
* Colors match the native admin bar's dark palette.
* A Settings page (under Settings → Floating Admin Bar) lets you hide the "About WordPress" and "Comments" items.
* The back end keeps WordPress's native admin bar untouched — only the front end gets the floating widget.

== Installation ==

1. Upload the `floating-admin-bar` folder to `/wp-content/plugins/`, or install it through the Plugins screen in your dashboard.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Visit the front end of your site while logged in to see the floating widget.
4. Optionally, visit Settings → Floating Admin Bar to hide "About WordPress" or "Comments".

== Frequently Asked Questions ==

= Does this affect the wp-admin dashboard? =

No. The native admin bar still renders as usual in wp-admin. Only the logged-in front end is replaced with the floating widget.

= Will items from other plugins show up automatically? =

Yes. Anything registered the standard way via `$wp_admin_bar->add_node()` during the `admin_bar_menu` hook appears in the floating widget automatically, nested the same way it would be in the native admin bar.

= Can I hide specific items? =

Settings → Floating Admin Bar currently offers toggles for "About WordPress" and "Comments".

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
