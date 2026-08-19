<?php

	/**
	 * Booking Page / Advisor Booking Context
	 *
	 * Provides the dedicated booking-page context and keeps advisor-specific
	 * Gravity Forms behavior isolated from the generic dynamic-options system.
	 *
	 * Advisor booking URLs use the Advisor CPT slug as the public identifier:
	 *
	 * /plan-your-vacation/?advisor=jane-smith
	 *
	 * The browser never needs to know the VacationCRM agent code. Form 4 continues
	 * to submit the Advisor post ID, and vacationcrm.php resolves the CRM identifier
	 * server-side exactly as it does for a manually selected advisor.
	 *
	 * @package PrelaunchWP
	 */

	declare( strict_types=1 );

	const PRELAUNCH_BOOKING_TEMPLATE       = 'template-booking.php';
	const PRELAUNCH_BOOKING_ADVISOR_PARAM  = 'advisor';
	const PRELAUNCH_BOOKING_ADVISOR_POSTED = 'prelaunch_booking_advisor';

	/**
	 * Get the advisor requested for the current booking request.
	 *
	 * Initial page loads read ?advisor=advisor-post-slug. Gravity Forms submissions
	 * carry the same slug in a hidden request value injected into Form 4 so the
	 * advisor context survives validation errors without relying on the query string.
	 */
	function prelaunch_get_booking_advisor(): ?WP_Post {
		$raw_slug = '';

		if ( isset( $_POST[ PRELAUNCH_BOOKING_ADVISOR_POSTED ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$raw_slug = (string) wp_unslash( $_POST[ PRELAUNCH_BOOKING_ADVISOR_POSTED ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} elseif ( isset( $_GET[ PRELAUNCH_BOOKING_ADVISOR_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$raw_slug = (string) wp_unslash( $_GET[ PRELAUNCH_BOOKING_ADVISOR_PARAM ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$advisor_slug = sanitize_title( $raw_slug );

		if ( '' === $advisor_slug ) {
			return null;
		}

		$advisor = get_page_by_path(
			$advisor_slug,
			OBJECT,
			'advisor'
		);

		if (
			! $advisor instanceof WP_Post ||
			'publish' !== $advisor->post_status
		) {
			return null;
		}

		/*
		 * Do not enter advisor mode for an advisor who cannot be routed. This mirrors
		 * the exclusion already used by the normal dynamically populated advisor list.
		 */
		$advisor_crm_id = trim(
			(string) get_post_meta(
				$advisor->ID,
				'advisor_crm_id',
				true
			)
		);

		if ( '' === $advisor_crm_id ) {
			return null;
		}

		return $advisor;
	}

	/**
	 * Determine whether this request is using a valid advisor-specific booking mode.
	 */
	function prelaunch_is_advisor_booking(): bool {
		return prelaunch_get_booking_advisor() instanceof WP_Post;
	}

	/**
	 * Find the published page assigned to the dedicated Booking template.
	 */
	function prelaunch_get_booking_page(): ?WP_Post {
		$pages = get_posts(
			[
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'orderby'                => 'menu_order title',
				'order'                  => 'ASC',
				'meta_key'               => '_wp_page_template',
				'meta_value'             => PRELAUNCH_BOOKING_TEMPLATE,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		return ! empty( $pages ) && $pages[0] instanceof WP_Post
			? $pages[0]
			: null;
	}

	/**
	 * Get an advisor-specific URL for the page assigned to the Booking template.
	 *
	 * Returns an empty string until a published page is actually assigned to the
	 * template. This avoids silently generating a link to the wrong page.
	 */
	function prelaunch_get_advisor_booking_url( int $advisor_id ): string {
		$advisor = get_post( $advisor_id );

		if (
			! $advisor instanceof WP_Post ||
			'advisor' !== $advisor->post_type ||
			'publish' !== $advisor->post_status ||
			'' === trim( $advisor->post_name )
		) {
			return '';
		}

		$booking_page = prelaunch_get_booking_page();

		if ( ! $booking_page instanceof WP_Post ) {
			return '';
		}

		return add_query_arg(
			PRELAUNCH_BOOKING_ADVISOR_PARAM,
			$advisor->post_name,
			get_permalink( $booking_page )
		);
	}

	/**
	 * Apply advisor mode to Gravity Form 4 after normal dynamic choices are built.
	 */
	add_filter(
		'gform_pre_render_4',
		'prelaunch_prepare_advisor_booking_form',
		20
	);
	add_filter(
		'gform_pre_validation_4',
		'prelaunch_prepare_advisor_booking_form',
		20
	);
	add_filter(
		'gform_pre_submission_filter_4',
		'prelaunch_prepare_advisor_booking_form',
		20
	);

	/**
	 * Lock Form 4 to the advisor supplied by the booking URL.
	 *
	 * Field 7 = Advisor Preference section heading.
	 * Field 8 = Do you already have an advisor? (forced to Yes).
	 * Field 9 = Advisor selector (forced to the current Advisor post ID).
	 *
	 * The fields remain part of the submitted Gravity Forms entry so the existing
	 * VacationCRM integration does not need a second routing path.
	 *
	 * @param array<string, mixed> $form Gravity Forms form object.
	 *
	 * @return array<string, mixed>
	 */
	function prelaunch_prepare_advisor_booking_form( array $form ): array {
		$advisor = prelaunch_get_booking_advisor();

		if ( ! $advisor instanceof WP_Post ) {
			return $form;
		}

		/*
		 * Enforce the routing values server-side as well as setting their display
		 * defaults. This prevents a modified browser request from changing the locked
		 * advisor while a valid advisor booking context is present.
		 */
		if ( 'POST' === strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
			$_POST['input_8'] = 'Yes'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$_POST['input_9'] = (string) $advisor->ID; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return $form;
		}

		foreach ( $form['fields'] as $field ) {
			if ( ! is_object( $field ) || ! isset( $field->id ) ) {
				continue;
			}

			$field_id = absint( $field->id );

			if ( 7 === $field_id ) {
				$field->cssClass = prelaunch_booking_append_field_class(
					(string) $field->cssClass,
					'booking-advisor-field-hidden'
				);
				continue;
			}

			if ( 8 === $field_id ) {
				$field->defaultValue = 'Yes';

				if ( isset( $field->choices ) && is_array( $field->choices ) ) {
					foreach ( $field->choices as &$choice ) {
						if ( is_array( $choice ) ) {
							$choice['isSelected'] = isset( $choice['value'] ) && 'Yes' === (string) $choice['value'];
						}
					}
					unset( $choice );
				}
				$field->cssClass = prelaunch_booking_append_field_class(
					(string) $field->cssClass,
					'booking-advisor-field-hidden'
				);
				continue;
			}

			if ( 9 === $field_id ) {
				$field->defaultValue = (string) $advisor->ID;

				if ( isset( $field->choices ) && is_array( $field->choices ) ) {
					foreach ( $field->choices as &$choice ) {
						if ( is_array( $choice ) ) {
							$choice['isSelected'] = isset( $choice['value'] ) && (string) $advisor->ID === (string) $choice['value'];
						}
					}
					unset( $choice );
				}
				$field->cssClass = prelaunch_booking_append_field_class(
					(string) $field->cssClass,
					'booking-advisor-field-hidden'
				);
			}
		}

		return $form;
	}

	/**
	 * Inject the advisor slug into Form 4 so advisor mode survives validation posts.
	 *
	 * @param string $form_tag Opening Gravity Forms <form> tag.
	 * @param array<string, mixed> $form Gravity Forms form object.
	 */
	add_filter(
		'gform_form_tag_4',
		'prelaunch_add_booking_advisor_context_to_form',
		10,
		2
	);

	function prelaunch_add_booking_advisor_context_to_form(
		string $form_tag,
		array $form
	): string {
		$advisor = prelaunch_get_booking_advisor();

		if ( ! $advisor instanceof WP_Post ) {
			return $form_tag;
		}

		$hidden_input = sprintf(
			'<input type="hidden" name="%1$s" value="%2$s">',
			esc_attr( PRELAUNCH_BOOKING_ADVISOR_POSTED ),
			esc_attr( $advisor->post_name )
		);

		return $form_tag . $hidden_input;
	}

	/**
	 * Append a CSS class without duplicating it.
	 */
	function prelaunch_booking_append_field_class(
		string $classes,
		string $new_class
	): string {
		$class_list = preg_split(
			'/\s+/',
			trim( $classes )
		);

		if ( ! is_array( $class_list ) ) {
			$class_list = [];
		}

		$class_list[] = $new_class;

		return implode(
			' ',
			array_values(
				array_unique(
					array_filter( $class_list )
				)
			)
		);
	}
