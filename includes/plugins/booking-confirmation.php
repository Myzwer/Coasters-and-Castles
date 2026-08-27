<?php

	/**
	 * Booking Confirmation
	 *
	 * Redirects Gravity Form 4 submissions to a dedicated booking confirmation
	 * page and provides the display context used by that page.
	 *
	 * The redirect carries only the context needed to personalize the confirmation:
	 * - Advisor post ID, when a preferred advisor was selected.
	 * - Vacation Type term ID from the booking form.
	 * - Traveler first name for the optional {fname} success-headline token.
	 *
	 * No contact information or trip-detail fields are passed in the URL.
	 *
	 * @package PrelaunchWP
	 */

	declare( strict_types=1 );

	const PRELAUNCH_BOOKING_CONFIRMATION_TEMPLATE   = 'template-booking-confirmation.php';
	const PRELAUNCH_BOOKING_CONFIRMATION_MARKER     = 'booking_complete';
	const PRELAUNCH_BOOKING_CONFIRMATION_ADVISOR    = 'booking_advisor_id';
	const PRELAUNCH_BOOKING_CONFIRMATION_TRIP       = 'booking_trip_id';
	const PRELAUNCH_BOOKING_CONFIRMATION_FIRST_NAME = 'booking_first_name';

	/**
	 * Find the published page assigned to the Booking Confirmation template.
	 */
	function prelaunch_get_booking_confirmation_page(): ?WP_Post {
		$pages = get_posts(
			[
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'orderby'                => 'menu_order title',
				'order'                  => 'ASC',
				'meta_key'               => '_wp_page_template',
				'meta_value'             => PRELAUNCH_BOOKING_CONFIRMATION_TEMPLATE,
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
	 * Redirect successful Form 4 submissions to the dedicated confirmation page.
	 *
	 * Field 2.3 = First name.
	 * Field 8   = Preferred advisor? Yes / No.
	 * Field 9   = Advisor post ID.
	 * Field 11  = Vacation Type term ID.
	 *
	 * @param string|array $confirmation Existing Gravity Forms confirmation.
	 * @param array $form Gravity Forms form object.
	 * @param array $entry Gravity Forms entry.
	 * @param bool $ajax Whether the form was submitted using AJAX.
	 *
	 * @return string|array
	 */
	add_filter(
		'gform_confirmation_4',
		'prelaunch_booking_confirmation_redirect',
		20,
		4
	);

	function prelaunch_booking_confirmation_redirect(
		$confirmation,
		array $form,
		array $entry,
		bool $ajax
	) {
		$confirmation_page = prelaunch_get_booking_confirmation_page();

		if ( ! $confirmation_page instanceof WP_Post ) {
			return $confirmation;
		}

		$query_args = [
			PRELAUNCH_BOOKING_CONFIRMATION_MARKER => '1',
		];

		$first_name = trim(
			(string) rgar( $entry, '2.3' )
		);

		if ( '' !== $first_name ) {
			$query_args[ PRELAUNCH_BOOKING_CONFIRMATION_FIRST_NAME ] = $first_name;
		}

		$has_preferred_advisor = trim(
			(string) rgar( $entry, '8' )
		);

		if ( 'Yes' === $has_preferred_advisor ) {
			$advisor_id = absint(
				rgar( $entry, '9' )
			);

			if ( $advisor_id > 0 ) {
				$query_args[ PRELAUNCH_BOOKING_CONFIRMATION_ADVISOR ] = (string) $advisor_id;
			}
		}

		$vacation_type_id = absint(
			rgar( $entry, '11' )
		);

		if ( $vacation_type_id > 0 ) {
			$query_args[ PRELAUNCH_BOOKING_CONFIRMATION_TRIP ] = (string) $vacation_type_id;
		}

		$redirect_url = add_query_arg(
			$query_args,
			get_permalink( $confirmation_page )
		);

		return [
			'redirect' => $redirect_url,
		];
	}

	/**
	 * Get the submitted traveler's first name from the confirmation URL.
	 */
	function prelaunch_get_booking_confirmation_first_name(): string {
		if ( ! isset( $_GET[ PRELAUNCH_BOOKING_CONFIRMATION_FIRST_NAME ] ) ) {
			return '';
		}

		return sanitize_text_field(
			wp_unslash(
				$_GET[ PRELAUNCH_BOOKING_CONFIRMATION_FIRST_NAME ]
			)
		);
	}

	/**
	 * Get the Advisor represented by the confirmation URL.
	 */
	function prelaunch_get_booking_confirmation_advisor(): ?WP_Post {
		$advisor_id = isset( $_GET[ PRELAUNCH_BOOKING_CONFIRMATION_ADVISOR ] )
			? absint( wp_unslash( $_GET[ PRELAUNCH_BOOKING_CONFIRMATION_ADVISOR ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: 0;

		if ( 0 === $advisor_id ) {
			return null;
		}

		$advisor = get_post( $advisor_id );

		if (
			! $advisor instanceof WP_Post ||
			'advisor' !== $advisor->post_type ||
			'publish' !== $advisor->post_status
		) {
			return null;
		}

		return $advisor;
	}

	/**
	 * Get an advisor’s personal scheduling URL from their profile.
	 *
	 * This is distinct from the site booking-page URL. An empty string means
	 * the confirmation page should not render the {booking-link} section.
	 */
	function prelaunch_get_advisor_scheduling_url( ?WP_Post $advisor ): string {
		if (
			! $advisor instanceof WP_Post ||
			'advisor' !== $advisor->post_type
		) {
			return '';
		}

		$url = trim(
			(string) get_field(
				'advisor_booking_link',
				$advisor->ID
			)
		);

		if ( '' === $url ) {
			return '';
		}

		$safe_url = esc_url_raw( $url );

		if ( '' === $safe_url ) {
			return '';
		}

		return $safe_url;
	}

	/**
	 * Get the submitted Vacation Type term represented by the confirmation URL.
	 */
	function prelaunch_get_booking_confirmation_vacation_type(): ?WP_Term {
		$term_id = isset( $_GET[ PRELAUNCH_BOOKING_CONFIRMATION_TRIP ] )
			? absint( wp_unslash( $_GET[ PRELAUNCH_BOOKING_CONFIRMATION_TRIP ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: 0;

		if ( 0 === $term_id ) {
			return null;
		}

		$term = get_term(
			$term_id,
			'vacation_type'
		);

		if (
			is_wp_error( $term ) ||
			! $term instanceof WP_Term
		) {
			return null;
		}

		return $term;
	}

	/**
	 * Match an Advisor Vacation Type term to the blog's separate Trip Type taxonomy.
	 *
	 * The two taxonomies intentionally use different internal keys. Slug matching is
	 * attempted first, followed by a normalized display-name comparison so editorial
	 * differences in term IDs do not matter.
	 */
	function prelaunch_get_blog_trip_type_for_vacation_type(
		?WP_Term $vacation_type
	): ?WP_Term {
		if (
			! $vacation_type instanceof WP_Term ||
			! taxonomy_exists( 'trip_type' )
		) {
			return null;
		}

		$by_slug = get_term_by(
			'slug',
			$vacation_type->slug,
			'trip_type'
		);

		if ( $by_slug instanceof WP_Term ) {
			return $by_slug;
		}

		$blog_terms = get_terms(
			[
				'taxonomy'   => 'trip_type',
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $blog_terms ) ) {
			return null;
		}

		$normalize = static function ( string $value ): string {
			$value = wp_strip_all_tags( $value );
			$value = str_replace(
				[
					'’',
					'‘',
					'`',
				],
				"'",
				$value
			);

			return strtolower(
				trim( $value )
			);
		};

		$target_name = $normalize(
			$vacation_type->name
		);

		foreach ( $blog_terms as $blog_term ) {
			if (
				$blog_term instanceof WP_Term &&
				$target_name === $normalize( $blog_term->name )
			) {
				return $blog_term;
			}
		}

		return null;
	}

	/**
	 * Get blog recommendations for the submitted Vacation Type.
	 *
	 * Matching Trip Type posts are preferred. If no matching articles exist, the
	 * latest published posts are returned as a general inspiration fallback.
	 *
	 * @return array{query: WP_Query, matched_trip: bool, trip_type: ?WP_Term}
	 */
	function prelaunch_get_booking_confirmation_posts(
		?WP_Term $vacation_type,
		int $posts_per_page = 3
	): array {
		$posts_per_page = max(
			1,
			$posts_per_page
		);

		$blog_trip_type = prelaunch_get_blog_trip_type_for_vacation_type(
			$vacation_type
		);

		if ( $blog_trip_type instanceof WP_Term ) {
			$matching_query = new WP_Query(
				[
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => $posts_per_page,
					'orderby'             => 'date',
					'order'               => 'DESC',
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'tax_query'           => [
						[
							'taxonomy'         => 'trip_type',
							'field'            => 'term_id',
							'terms'            => [
								$blog_trip_type->term_id,
							],
							'include_children' => true,
						],
					],
				]
			);

			if ( $matching_query->have_posts() ) {
				return [
					'query'        => $matching_query,
					'matched_trip' => true,
					'trip_type'    => $blog_trip_type,
				];
			}
		}

		return [
			'query'        => new WP_Query(
				[
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => $posts_per_page,
					'orderby'             => 'date',
					'order'               => 'DESC',
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				]
			),
			'matched_trip' => false,
			'trip_type'    => $blog_trip_type,
		];
	}

	/**
	 * Keep successful booking confirmation pages out of search indexes.
	 */
	add_filter(
		'wp_robots',
		'prelaunch_noindex_booking_confirmation'
	);

	function prelaunch_noindex_booking_confirmation(
		array $robots
	): array {
		if (
			is_page_template(
				PRELAUNCH_BOOKING_CONFIRMATION_TEMPLATE
			)
		) {
			$robots['noindex']  = true;
			$robots['nofollow'] = false;
		}

		return $robots;
	}
