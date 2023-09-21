<?php
/**
 * Plugin Name: Recently Registered
 * Plugin URI: http://halfelf.org/plugins/recently-registered/
 * Description: Add a sortable column to the users list to show registration date.
 * Version: 3.6
 * Author: Mika Epstein
 * Author URI: http://halfelf.org/
 * Text Domain: recently-registered
 * Network: true
 *
 * Copyright 2009-2022 Mika Epstein (email: ipstenu@halfelf.org)
 *
 * This file is part of Recently Registered, a plugin for WordPress.
 *
 * Recently Registered is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * Recently Registered is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WordPress.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package recently-registered
 */

// phpcs:disable Generic.Commenting.DocComment.MissingShort
/** @noinspection SpellCheckingInspection */
/** @noinspection AutoloadingIssuesInspection */
// phpcs:enable Generic.Commenting.DocComment.MissingShort

/**
 * Main plugin class.
 */
class RRHE {

	/**
	 * Let's get this party started
	 *
	 * @since  3.4
	 * @access public
	 */
	public function __construct() {
		add_action( 'admin_init', [ &$this, 'admin_init' ] );
	}

	/**
	 * All init functions
	 *
	 * @since  3.4
	 * @access public
	 */
	public function admin_init(): void {
		if ( is_admin() ) {
			add_filter( 'manage_users_columns', [ $this, 'users_columns' ] );
			add_action( 'manage_users_custom_column', [ $this, 'users_custom_column' ], 10, 3 );
			add_filter( 'manage_users_sortable_columns', [ $this, 'users_sortable_columns' ] );
			add_filter( 'request', [ $this, 'users_orderby_column' ] );
			add_action( 'plugins_loaded', [ $this, 'load_this_textdomain' ] );
			add_filter( 'plugin_row_meta', [ $this, 'donate_link' ], 10, 2 );
		}
	}

	/**
	 * Registers column for display
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param array $columns Columns.
	 */
	public function users_columns( array $columns ): array {
		$columns['registerdate'] = _x( 'Registered', 'user', 'recently-registered' );

		return $columns;
	}

	/**
	 * Handles the registered date column output.
	 *
	 * This uses the same code as column_registered, which is why
	 * the date isn't filterable.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param string $value       Column value.
	 * @param string $column_name Column name.
	 * @param int    $user_id     User id.
	 *
	 * @return string
	 */
	public function users_custom_column( string $value, string $column_name, int $user_id ): string {
		if ( 'registerdate' !== $column_name ) {
			return $value;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$list_mode = empty( $_REQUEST['mode'] ) ? 'list' : sanitize_text_field( wp_unslash( $_REQUEST['mode'] ) );

		$user = get_userdata( $user_id );

		if ( ( 'list' === $list_mode ) && is_multisite() ) {
			$formatted_date = __( 'Y/m/d', 'recently-registered' );
		} else {
			$formatted_date = __( 'Y/m/d g:i:s a', 'recently-registered' );
		}

		$registered = strtotime( get_date_from_gmt( $user->user_registered ) );

		// If the date is negative or in the future, then something's wrong, so we'll be unknown.
		if ( ( false === $registered ) || ( $registered <= 0 ) || ( time() <= $registered ) ) {
			$register_date = '<span class="recently-registered invalid-date">' . __( 'Unknown', 'recently-registered' ) . '</span>';
		} else {
			$register_date = '<span class="recently-registered valid-date">' . date_i18n( $formatted_date, $registered ) . '</span>';
		}

		return $register_date;
	}

	/**
	 * Makes the column sortable
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $columns Columns.
	 */
	public static function users_sortable_columns( array $columns ): array {
		$custom = [
			// Meta column id => orderby value used in query.
			'registerdate' => 'registered',
		];

		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Calculate the order if we sort by date.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $vars Sort parameters.
	 */
	public static function users_orderby_column( array $vars ): array {
		if ( isset( $vars['orderby'] ) && 'registerdate' === $vars['orderby'] ) {
			$new_vars = [
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key' => 'registerdate',
				'orderby'  => 'meta_value',
			];

			$vars = array_merge( $vars, $new_vars );
		}

		return $vars;
	}

	/**
	 * Internationalization - We're just going to use the language packs for this.
	 *
	 * @since  3.4
	 * @access public
	 */
	public function load_this_textdomain(): void {
		load_plugin_textdomain( 'recently-registered' );
	}

	/**
	 * Slap a donate link back into the plugin links. Show some love
	 *
	 * @since  2.x
	 * @access public
	 *
	 * @param array|mixed $links Links.
	 * @param string      $file  Plugin file.
	 */
	public function donate_link( $links, string $file ): array {
		$links = (array) $links;

		if ( plugin_basename( __FILE__ ) === $file ) {
			$donate_link = '<a href="https://ko-fi.com/A236CEN/">' . __( 'Donate', 'recently-registered' ) . '</a>';
			$links[]     = $donate_link;
		}

		return $links;
	}
}

new RRHE();
