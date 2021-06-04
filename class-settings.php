<?php
namespace Debug_WP_Redirect;

/**
 * The class used for settings related functionality.
 *
 * @since 2.0
 */
class Settings {

	/**
	 * The class instance.
	 *
	 * @since 2.0
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Setup the class instance and returns it.
	 *
	 * @since 2.0
	 *
	 * @return self The class instance.
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup the class and add hooks.
	 *
	 * @since 2.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'hook' ] );
	}

	/**
	 * Add hooks for the functionality.
	 *
	 * @since 2.0
	 */
	public function hook() {
		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'action_admin_menu' ] );
			add_action( 'admin_init', [ $this, 'action_admin_init' ] );

			if ( $this->network_activated() ) {
				add_action( 'network_admin_menu', [ $this, 'action_network_admin_menu' ] );
				add_action( 'network_admin_edit_debug-wp-redirect', [ $this, 'action_network_settings_edit' ] );
			}
		}
	}

	/**
	 * Add hooks for the functionality.
	 *
	 * @since 2.0
	 */
	public function unhook() {
		if ( is_admin() ) {
			remove_action( 'admin_menu', [ $this, 'action_admin_menu' ] );
			remove_action( 'admin_init', [ $this, 'action_admin_init' ] );

			if ( $this->network_activated() ) {
				remove_action( 'network_admin_menu', [ $this, 'network_admin_menu' ] );
				remove_action( 'network_admin_edit_debug-wp-redirect', [ $this, 'network_settings_save' ] );
			}
		}
	}

	/**
	 * Register settings for the plugin.
	 *
	 * @since 2.0
	 */
	public function action_admin_init() {
		register_setting( 'debug-wp-redirect-settings-group', 'debug_wp_redirect_enable_frontend' );
		register_setting( 'debug-wp-redirect-settings-group', 'debug_wp_redirect_enable_admin' );
		register_setting( 'debug-wp-redirect-settings-group', 'debug_wp_redirect_enable_logged_in_admin' );
		register_setting( 'debug-wp-redirect-settings-group', 'debug_wp_redirect_enable_logged_in' );

		add_settings_section( 'debug-wp-redirect-settings-general', __( 'Enable Redirect Debugging', 'debug-wp-redirect' ), null, 'debug-wp-redirect' );

		add_settings_field( 'debug_wp_redirect_enable_frontend', __( 'Frontend debugging', 'debug-wp-redirect' ), [
			$this,
			'field',
		], 'debug-wp-redirect', 'debug-wp-redirect-settings-general', [
			'label_for' => 'debug_wp_redirect_enable_frontend',
			'name' => 'debug_wp_redirect_enable_frontend',
			'type' => 'checkbox',
			'description' => __( 'Enabling debugging on the frontend affects ALL submission forms like comments too.', 'debug-wp-redirect' ),
		] );

		add_settings_field( 'debug_wp_redirect_enable_admin', __( 'Admin dashboard debugging', 'debug-wp-redirect' ), [
			$this,
			'field',
		], 'debug-wp-redirect', 'debug-wp-redirect-settings-general', [
			'label_for' => 'debug_wp_redirect_enable_admin',
			'name' => 'debug_wp_redirect_enable_admin',
			'type' => 'checkbox',
			'description' => __( 'Enabling debugging in the admin area affects ALL submission forms except for this settings page. Admin functionality will be broken from redirect debugging until it is turned back off.', 'debug-wp-redirect' ),
		] );

		add_settings_section( 'debug-wp-redirect-settings-visibility', __( 'Visibility Settings', 'debug-wp-redirect' ), null, 'debug-wp-redirect' );

		add_settings_field( 'debug_wp_redirect_enable_logged_in_admin', __( 'Admins only', 'debug-wp-redirect' ), [
			$this,
			'field',
		], 'debug-wp-redirect', 'debug-wp-redirect-settings-visibility', [
			'label_for' => 'debug_wp_redirect_enable_logged_in_admin',
			'name' => 'debug_wp_redirect_enable_logged_in_admin',
			'type' => 'checkbox',
			'description' => __( 'If this is checked and the person is not logged in or an admin, they will be disallowed from seeing debug information.', 'debug-wp-redirect' ),
		] );

		add_settings_field( 'debug_wp_redirect_enable_logged_in', __( 'Logged in users only', 'debug-wp-redirect' ), [
			$this,
			'field',
		], 'debug-wp-redirect', 'debug-wp-redirect-settings-visibility', [
			'label_for' => 'debug_wp_redirect_enable_logged_in',
			'name' => 'debug_wp_redirect_enable_logged_in',
			'type' => 'checkbox',
			'description' => __( 'If this is checked and the person is not logged in, they will be disallowed from seeing debug information. If the admin-only option is enabled then this will be disregarded.', 'debug-wp-redirect' ),
		] );
	}

	/**
	 * Render the field for a setting.
	 *
	 * @since 2.0
	 *
	 * @param array $args List of field arguments.
	 */
	public function field( $args ) {
		if ( empty( $args['type'] ) || empty( $args['name'] ) ) {
			return;
		}

		$is_network_admin = is_multisite() && is_network_admin();

		$value = $is_network_admin ? get_site_option( $args['name'] ) : get_option( $args['name'] );

		if ( 'checkbox' === $args['type'] ) {
			echo sprintf(
				'<input
					type="checkbox"
					name="%s"
					id="%s"
					value="1"
					%s
				/>',
				esc_attr( $args['name'] ),
				esc_attr( $args['name'] ),
				checked( 1 === (int) $value, true, false )
			);
		}

		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . wp_kses_post( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Determine whether the plugin is network activated.
	 *
	 * @since 2.0
	 *
	 * @return bool Whether the plugin is network activated.
	 */
	public function network_activated() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! is_multisite() ) {
			return false;
		}

		return is_plugin_active_for_network( plugin_basename( DEBUG_WP_REDIRECT_PLUGIN_FILE ) );
	}

	/**
	 * Add menu item.
	 *
	 * @since 2.0
	 */
	public function action_admin_menu() {
		add_options_page( __( 'Debug wp_redirect()', 'debug-wp-redirect' ), __( 'Debug wp_redirect()', 'debug-wp-redirect' ), 'manage_options', 'debug-wp-redirect', array(
			$this,
			'settings_page',
		) );
	}

	/**
	 * Add network menu item.
	 *
	 * @since 2.0
	 */
	public function action_network_admin_menu() {
		add_submenu_page( 'settings.php', __( 'Debug wp_redirect()', 'debug-wp-redirect' ), __( 'Debug wp_redirect()', 'debug-wp-redirect' ), 'manage_network_options', 'debug-wp-redirect', array(
			$this,
			'settings_page',
		) );
	}

	/**
	 * Save network settings.
	 *
	 * @since 2.0
	 */
	public function action_network_settings_edit() {
		check_admin_referer( 'debug-wp-redirect' );

		$settings = get_registered_settings();

		foreach ( $settings as $field => $setting ) {
			if ( false === strpos( $field, 'debug_wp_redirect_' ) ) {
				continue;
			}

			if ( ! empty( $_POST[ $field ] ) ) {
				update_site_option( $field, sanitize_text_field( $_POST[ $field ] ) );
			} else {
				delete_site_option( $field );
			}
		}

		debug_wp_redirect_disable();

		wp_redirect( 'settings.php?page=debug-wp-redirect&settings-updated=1' );
		die();
	}

	/**
	 * Output the admin settings page.
	 *
	 * @since 2.0
	 */
	public function settings_page() {
		$is_network_admin = is_multisite() && is_network_admin();

		$action = 'options.php';

		if ( $is_network_admin ) {
			$action = 'edit.php?action=debug-wp-redirect';
		}

		if ( $is_network_admin && isset( $_GET['settings-updated'] ) ) {
			?>
			<div id="message" class="updated"><p><strong><?php esc_html_e( 'Settings saved.' ); ?></strong></p></div>
			<?php
		}
		?>
		<div class="wrap">
			<?php
				if ( $is_network_admin ) {
			?>
				<h2><?php esc_html_e( 'Debug wp_redirect() Network Settings', 'debug-wp-redirect' ); ?></h2>
			<?php } else { ?>
				<h2><?php esc_html_e( 'Debug wp_redirect() Settings', 'debug-wp-redirect' ); ?></h2>
			<?php } ?>

			<p>
				<?php esc_html_e( 'This plugin allows debugging wp_redirect() calls on your site.', 'debug-wp-redirect' ); ?>

				<?php
					if ( $is_network_admin ) {
				?>
					<strong>These network settings can be overridden per-site.</strong>
				<?php } ?>
			</p>
			<p><em><?php esc_html_e( 'NOTE: Please do not leave debugging enabled on production sites as they can expose details about the server.', 'debug-wp-redirect' ); ?></em></p>

			<hr />

			<form method="post" action="<?php echo esc_attr( $action ); ?>">
				<?php
					if ( $is_network_admin ) {
						wp_nonce_field( 'debug-wp-redirect' );
					} else {
						settings_fields( 'debug-wp-redirect-settings-group' );
					}

					do_settings_sections( 'debug-wp-redirect' );
				?>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'debug-wp-redirect' ); ?>" />
				</p>
			</form>
		</div>
		<?php
	}
}
