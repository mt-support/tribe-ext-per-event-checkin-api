<?php
/**
 * Generates Per Event API and handles API calls from the QR Code app.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Per_Event_Checkin;
 */

namespace Tribe\Extensions\Per_Event_Checkin;

/**
 * Class API_Handler.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Per_Event_Checkin;
 */
class Api_Handler {

	/**
	 * Meta key name for storing Event API key.
	 */
	public $api_meta_key = '_et_event_api_key';

	/**
	 * Generate API code for Event with Tickets/RSVP.
	 *
	 * @param int $post_id Event or Post ID.
	 */
	public function generate_api_code_for_event( $post_id ) {

		$api_key = get_post_meta( $post_id, $this->api_meta_key, true );

		// If API key is already generated, return.
		if ( ! empty( $api_key ) ) {
			return;
		}
		/** @var \Tribe__Tickets_Plus__QR__Settings $qr_settings */
		$qr_settings = tribe( 'tickets-plus.qr.site-settings' );

		$api_key = $qr_settings->generate_new_api_key();

		update_post_meta( $post_id, $this->api_meta_key, $api_key );
	}

	/**
	 * Filter the API key validation for Check in Request.
	 *
	 * @param bool  $is_valid Whether the requested API Key is valid or not.
	 * @param array $qr_data Request data array.
	 *
	 * @return bool
	 */
	function alter_default_api_with_event_api( $is_valid, $qr_data ) {

		// If already valid, bail out.
		if ( $is_valid ) {
			return $is_valid;
		}

		if ( ! isset( $qr_data['api_key'] ) ) {
			return $is_valid;
		}

		$requested_api = $qr_data['api_key'];

		$args = [
			'post_type'  => 'any',
			'meta_key'   => $this->api_meta_key,
			'meta_query' => [
				'key'   => $this->api_meta_key,
				'value' => $requested_api,
			]
		];

		$posts = get_posts( $args );

		// If no events are found for given API key, return original status.
		if ( empty( $posts ) || count( $posts ) > 1 ) {
			return $is_valid;
		}
		// If found event is not same as requested event, bail out.
		if ( $posts[0]->ID != $qr_data['event_id'] ) {
			return $is_valid;
		}

		return true;
	}

	/**
	 * Add the Event API meta box to Edit screen.
	 */
	public function add_event_api_meta_box() {
		$post = get_post();

		// Get list of supported post types for Tickets.
		$supported_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		if ( ! in_array( $post->post_type, $supported_post_types ) ) {
			return;
		}

		$api_key = get_post_meta( $post->ID, $this->api_meta_key, true );

		if ( empty( $api_key ) ) {
			$this->generate_api_code_for_event( $post->ID );
		}

		add_meta_box(
			'event_tickets_event_api',
			__( 'Event Check-in API', 'et-per-event-checkin' ),
			[ $this, 'render_event_tickets_api_meta_box' ],
			null,
			'side'
		);
	}

	/**
	 * Show a meta box with the Event API key.
	 *
	 * @param \WP_Post $post Current post Object.
	 */
	function render_event_tickets_api_meta_box( $post ) {

		$api_key = get_post_meta( $post->ID, $this->api_meta_key, true );

		$kb_url  = 'https://theeventscalendar.com/knowledgebase/k/using-qr-codes-with-event-tickets-plus/';
		$kb_link = '<a href="' . esc_url( $kb_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'QR Code App', 'et-per-event-checkin' ) . '</a>';
		?>
		<input
			type="text"
			disabled
			value="<?php esc_attr_e( $api_key ); ?>"
		>
		<p>
			<?php printf( esc_html__( 'Copy this API into the %s to allow checkin for this Event Only.', 'et-per-event-checkin' ), $kb_link ); ?>
		</p>
		<?php
	}

	/**
	 * Show API key box for Event on Community Event edit page.
	 *
	 * @since 1.1.0
	 */
	function add_community_tickets_api_box() {

		$post = get_post();

		// Only display the API box if we are editing a valid post.
		if ( ! $post ) {
			return;
		}

		// Don't display if not on community edit page.
		if ( ! tribe_is_community_edit_event_page() ) {
			return;
		}

		// Maybe generate API key if needed.
		$this->generate_api_code_for_event( $post->ID );
		?>
		<div id="tickets-api" class="tribe-section tribe-section-api-key">
			<div class="tribe-section-header">
				<h3>
					<?php esc_html_e( 'Event API Key', 'et-per-event-checkin' ); ?>
				</h3>
			</div>
			<div class="tribe-section-content">
				<?php $this->render_event_tickets_api_meta_box( $post ); ?>
			</div>
		</div>
		<?php
	}
}
