<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * ```php
 *  remove_filter( 'some_filter', [ tribe( Tribe\Extensions\Per_Event_Checkin\Hooks::class ), 'some_filtering_method' ] );
 *  remove_filter( 'some_filter', [ tribe( 'extension.per_event_checkin.hooks' ), 'some_filtering_method' ] );
 * ```
 *
 * To remove an action:
 * ```php
 *  remove_action( 'some_action', [ tribe( Tribe\Extensions\Per_Event_Checkin\Hooks::class ), 'some_method' ] );
 *  remove_action( 'some_action', [ tribe( 'extension.per_event_checkin.hooks' ), 'some_method' ] );
 * ```
 *
 * @since   1.0.0
 *
 * @package Tribe\Extensions\Per_Event_Checkin;
 */

namespace Tribe\Extensions\Per_Event_Checkin;

use TEC\Common\Contracts\Service_Provider;
use Tribe__Main as Common;

/**
 * Class Hooks.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Per_Event_Checkin;
 */
class Hooks extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.per_event_checkin.hooks', $this );

		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function add_actions() {
		add_action( 'tribe_load_text_domains', [ $this, 'load_text_domains' ] );
		add_action( 'tribe_tickets_ticket_added', tribe_callback( 'extension.per_event_checkin.api_handler', 'generate_api_code_for_event' ) );
		add_action( 'add_meta_boxes', tribe_callback( 'extension.per_event_checkin.api_handler', 'add_event_api_meta_box' ) );

		// Add Community Ticket Integration.
		add_action( 'tribe_post_get_template_part_community-tickets/modules/tickets', tribe_callback( 'extension.per_event_checkin.api_handler', 'add_community_tickets_api_box' ) );
	}

	/**
	 * Adds the filters required by the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function add_filters() {
		add_filter( 'event_tickets_plus_requested_api_is_valid', tribe_callback( 'extension.per_event_checkin.api_handler', 'alter_default_api_with_event_api' ), 10, 2 );
	}

	/**
	 * Load text domain for localization of the plugin.
	 *
	 * @since 1.0.0
	 */
	public function load_text_domains() {
		$mopath = tribe( Plugin::class )->plugin_dir . 'lang/';
		$domain = 'et-per-event-checkin';

		// This will load `wp-content/languages/plugins` files first.
		Common::instance()->load_text_domain( $domain, $mopath );
	}
}
