<?php

namespace Action_Scheduler\Custom_Tables;

/**
 * Class Dependencies
 *
 * APIs for working with Action Scheduler dependencies, including public methods to check
 * dependecies are met, and notify admins if they are not met.
 */
class Dependencies {

	/**
	 * @var string
	 */
	private $min_version = '2.1.0';

	/**
	 * @var string
	 */
	private $max_version = '3.0.0';

	/**
	 * Check Action Scheduler class is available and at required versions.
	 *
	 * @return bool
	 */
	public function dependencies_met() {
		return $this->is_action_scheduler_available() && $this->is_action_scheduler_new_enough() && $this->is_action_scheduler_old_enough();
	}

	/**
	 * Attach an admin notice in the WordPress admin about the missing dependeices (if any)
	 */
	public function add_notice() {
		add_filter( 'admin_notices', [ $this, 'admin_notices' ], 10, 1 );
	}

	/**
	 * Display an inactive notice when Action Scheduler is inactive or an invalid version is running.
	 */
	public function admin_notices() {
		if ( ! $this->dependencies_met() && current_user_can( 'activate_plugins' ) ) : ?>
<div id="message" class="error">
	<p>
		<?php
		// translators: 1$-2$: opening and closing <strong> tags
		printf( esc_html__( '%1$sThe Action Scheduler Custom Tables plugin is not running.%2$s', 'action-scheduler' ), '<strong>', '</strong>' );

		$plugins_url = admin_url( 'plugins.php' );

		if ( ! $this->is_action_scheduler_available() ) {
			// translators: 1$-2$: link tags to the Action Scheduler GitHub page, 3$-4$: link tags for Plugins administration screen
			printf( esc_html__( 'The %1$sAction Scheduler library%2$s must be active for this plugin to work. No instance of Action Scheduler was detected. Please %3$sinstall & activate Action Scheduler as a plugin &raquo;%4$s', 'action-scheduler' ), '<a href="https://github.com/prospress/action-scheduler">', '</a>', '<a href="' .  esc_url( $plugins_url ) . '">', '</a>' );
		} elseif ( ! $this->is_action_scheduler_new_enough() ) {
			// translators: 1$-2$: link tags to the Action Scheduler GitHub page, 3$-4$: version number, e.g. 2.1.0 5$-6$: link tags for Plugins administration screen
			printf( esc_html__( 'The %1$sAction Scheduler library%2$s version %3$s or newer must be active for this plugin to work. Action Scheduler version %4$s was detected. Please %5$supdate Action Scheduler as a plugin &raquo;%6$s', 'action-scheduler' ), '<a href="https://github.com/prospress/action-scheduler">', '</a>', $this->min_version, $this->get_action_scheduler_version(), '<a href="' .  esc_url( $plugins_url ) . '">', '</a>' );
		} elseif ( ! $this->is_action_scheduler_old_enough() ) {
			// translators: 1$-2$: link tags to the Action Scheduler GitHub page, 3$-4$: version number, e.g. 2.1.0 5$-6$: link tags for Plugins administration screen
			printf( esc_html__( 'This plugin is only required with %1$sAction Scheduler%2$s versions prior to %3$s. Action Scheduler version %4$s was detected. Please disable and delete the Action Scheduler Custom Tables plugin on the %5$sPlugins Administration screen &raquo;%6$s', 'action-scheduler' ), '<a href="https://github.com/prospress/action-scheduler">', '</a>', $this->max_version, $this->get_action_scheduler_version(), '<a href="' .  esc_url( $plugins_url ) . '">', '</a>' );
		}
		?>
	</p>
</div>
	<?php endif;
	}

	/**
	 * Check Action Scheduler class is available and at required versions.
	 *
	 * @return bool
	 */
	private function is_action_scheduler_available() {
		return class_exists( 'ActionScheduler_Versions' );
	}

	/**
	 * Action Scheduler v2.1.0 is the first version to provide all required APIs.
	 *
	 * @return bool
	 */
	private function is_action_scheduler_new_enough() {
		return version_compare( $this->get_action_scheduler_version(), $this->min_version, '>=' );
	}

	/**
	 * Action Scheduler v3.0.0 will include custom tables in core making this plugin unnecessary.
	 *
	 * @return bool
	 */
	private function is_action_scheduler_old_enough() {
		return version_compare( $this->get_action_scheduler_version(), $this->max_version, '<' );
	}

	/**
	 * Check Action Scheduler class is available and at required versions.
	 *
	 * @return string
	 */
	private function get_action_scheduler_version() {
		return $this->is_action_scheduler_available() ? \ActionScheduler_Versions::instance()->latest_version() : '0.0.0';
	}
}