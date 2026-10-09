=== Floating Admin Bar ===
Contributors: robpetrin
Tags: admin bar, toolbar, dashboard, menu, accessibility
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.4
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
* A Settings page (under Settings → Floating Admin Bar) lets you hide the "About WordPress" and "Comments" items, and customize the background/text colors with one-click reset to the defaults.
* A "Use Floating Toolbar" option on each user's own Profile screen, right under "Show Toolbar when viewing site", lets any user opt back into the classic native bar instead.
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

= Can a user opt out of the floating bar? =

Yes. Each user's own Profile screen has a "Use Floating Toolbar" checkbox directly below "Show Toolbar when viewing site". Unchecking it restores the classic native admin bar on the front end for that user only; it's only available while "Show Toolbar when viewing site" is also checked.

= Can I change the colors? =

Yes. Settings → Floating Admin Bar has background and text color pickers; each has a built-in reset button to restore the default dark palette. Hover/active states automatically use the inverse of your chosen colors (background and text swapped), so contrast stays consistent with whatever you pick.

== Changelog ==

= 1.0.4 =
* Change: menu item hover/active colors are now derived automatically as the inverse of your chosen background/text colors, instead of a fixed hardcoded hover color.

= 1.0.3 =
* Add: background and text color pickers on the Settings page, with one-click reset to the defaults.

= 1.0.2 =
* Fix: the spacing WordPress core reserves for the admin bar wasn't actually being cancelled on the front end — the plugin targeted a `.wp-toolbar` class that core only ever applies inside wp-admin, never on the front end, so the real cancellation now targets core's actual (classless) `html { margin-top }` rule via a dedicated body class.
* Add: a per-user "Use Floating Toolbar" preference on the Profile screen, right below "Show Toolbar when viewing site", so individual users can opt back into the classic native bar.
* Add: a subtle 1px border on the handle and expanded panel/flyout for definition against busy page backgrounds.

= 1.0.1 =
* Fix: the handle lost its rounded shape when focused via keyboard in browsers that redraw native button focus chrome (notably Safari); it now keeps its circular shape with a custom focus style.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.4 =
Menu item hover colors now auto-invert from your chosen background/text colors.

= 1.0.3 =
Adds configurable background/text colors in Settings, with reset to defaults.

= 1.0.2 =
Fixes front-end admin bar spacing not actually being cancelled, and adds a per-user toggle to opt out of the floating bar.

= 1.0.1 =
Fixes a visual bug where keyboard focus squared off the handle's rounded corners.

= 1.0.0 =
Initial release.
