<?php

	/**
	 * Travel Team blog-author integration.
	 *
	 * Provides a consistent author-panel data structure for blog posts written by:
	 *
	 * - An advisor linked to a published Advisor profile.
	 * - The agency-wide Coasters & Castles Travel Team account.
	 * - Any unlinked WordPress user, which falls back to the Travel Team panel.
	 *
	 * The dedicated Travel Team user is identified by email so its numeric user ID
	 * may safely differ between local, staging, and production environments.
	 *
	 * Unlinked authors also trigger an administrative warning so editors can correct
	 * the post attribution without allowing the frontend author panel to disappear.
	 */

	declare( strict_types=1 );

	/**
	 * Email address assigned to the agency-wide blog author account.
	 */
	const PRELAUNCH_TRAVEL_TEAM_EMAIL = 'info@coastersandcastlestravel.com';

	/**
	 * Retrieve the agency-wide Travel Team WordPress user.
	 */
	function prelaunch_get_travel_team_user(): ?WP_User {
		$user = get_user_by( 'email', PRELAUNCH_TRAVEL_TEAM_EMAIL );

		return $user instanceof WP_User ? $user : null;
	}

	/**
	 * Retrieve the public name of the agency-wide Travel Team author.
	 *
	 * The WordPress display name is used when the dedicated account exists. This
	 * allows the public name to change without requiring a theme-code update.
	 */
	function prelaunch_get_travel_team_name(): string {
		$user = prelaunch_get_travel_team_user();

		if ( $user ) {
			return $user->display_name;
		}

		return __( 'Coasters & Castles Travel Team', 'prelaunch-wp' );
	}

	/**
	 * Find the published Advisor profile connected to a WordPress user.
	 */
	function prelaunch_get_advisor_id_for_user( int $user_id ): int {
		if ( $user_id <= 0 ) {
			return 0;
		}

		$advisor_ids = get_posts( [
			'post_type'      => 'advisor',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [
				[
					'key'     => 'advisor_linked_user',
					'value'   => $user_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				],
			],
		] );

		return ! empty( $advisor_ids ) ? (int) $advisor_ids[0] : 0;
	}

	/**
	 * Normalize an ACF link field into a predictable array.
	 *
	 * @param mixed $link Raw ACF link-field value.
	 *
	 * @return array{
	 *     url: string,
	 *     title: string,
	 *     target: string
	 * }
	 */
	function prelaunch_normalize_author_link( $link ): array {
		if ( ! is_array( $link ) ) {
			return [
				'url'    => '',
				'title'  => '',
				'target' => '',
			];
		}

		return [
			'url'    => isset( $link['url'] ) ? (string) $link['url'] : '',
			'title'  => isset( $link['title'] ) ? (string) $link['title'] : '',
			'target' => isset( $link['target'] ) ? (string) $link['target'] : '',
		];
	}

	/**
	 * Get normalized author-panel content for a blog post.
	 *
	 * Advisor data is used whenever a linked Advisor profile exists. All unlinked
	 * authors fall back to the global Travel Team fields so the frontend remains
	 * complete and internally consistent.
	 *
	 * @param int $post_id Blog post ID.
	 * @param int $advisor_id Optional pre-resolved Advisor profile ID.
	 *
	 * @return array{
	 *     type: string,
	 *     name: string,
	 *     image_id: int,
	 *     bio: string,
	 *     primary_link: array{url: string, title: string, target: string},
	 *     secondary_link: array{url: string, title: string, target: string}
	 * }
	 */
	function prelaunch_get_post_author_panel_data(
		int $post_id,
		int $advisor_id = 0
	): array {
		$author_id = (int) get_post_field( 'post_author', $post_id );

		if ( $advisor_id <= 0 ) {
			$advisor_id = prelaunch_get_advisor_id_for_user( $author_id );
		}

		if ( $advisor_id > 0 ) {
			$advisor_bio = (string) get_field( 'advisor_bio', $advisor_id );
			$is_duo      = function_exists( 'prelaunch_advisor_is_duo' )
				&& prelaunch_advisor_is_duo( $advisor_id );

			return [
				'type'           => 'advisor',
				'name'           => get_the_title( $advisor_id ),
				'image_id'       => (int) get_field(
					'advisor_professional_headshot',
					$advisor_id
				),
				'bio'            => $advisor_bio
					? wp_trim_words(
						wp_strip_all_tags( $advisor_bio ),
						55,
						'&hellip;'
					)
					: '',
				'primary_link'   => [
					'url'    => function_exists( 'prelaunch_get_advisor_booking_url' )
						? prelaunch_get_advisor_booking_url( $advisor_id )
						: '',
					'title'  => $is_duo
						? __( 'Book With Us', 'prelaunch-wp' )
						: __( 'Book With Me', 'prelaunch-wp' ),
					'target' => '',
				],
				'secondary_link' => [
					'url'    => get_permalink( $advisor_id ),
					'title'  => $is_duo
						? __( 'View Our Profile', 'prelaunch-wp' )
						: __( 'View My Profile', 'prelaunch-wp' ),
					'target' => '',
				],
			];
		}

		$team_image = get_field( 'team_image', 'option' );
		$image_id   = 0;

		if ( is_array( $team_image ) && isset( $team_image['ID'] ) ) {
			$image_id = (int) $team_image['ID'];
		} elseif ( is_numeric( $team_image ) ) {
			$image_id = (int) $team_image;
		}

		return [
			'type'           => 'travel-team',
			'name'           => prelaunch_get_travel_team_name(),
			'image_id'       => $image_id,
			'bio'            => (string) get_field(
				'travel_team_bio',
				'option'
			),
			'primary_link'   => prelaunch_normalize_author_link(
				get_field( 'primary_link', 'option' )
			),
			'secondary_link' => prelaunch_normalize_author_link(
				get_field( 'secondary_link', 'option' )
			),
		];
	}

	/**
	 * Display an admin warning when a post uses an unlinked personal account.
	 *
	 * The frontend safely falls back to the Travel Team panel, but the actual
	 * WordPress author should still be corrected for author archives, metadata,
	 * structured data, and administrative clarity.
	 */
	function prelaunch_warn_about_unlinked_post_author(): void {
		$screen = get_current_screen();

		if (
			! $screen
			|| 'post' !== $screen->base
			|| 'post' !== $screen->post_type
		) {
			return;
		}

		$post_id = isset( $_GET['post'] )
			? absint( $_GET['post'] )
			: 0;

		if ( $post_id <= 0 ) {
			return;
		}

		$author_id  = (int) get_post_field( 'post_author', $post_id );
		$advisor_id = prelaunch_get_advisor_id_for_user( $author_id );
		$team_user  = prelaunch_get_travel_team_user();

		$is_team_author = (
			$team_user
			&& $author_id === (int) $team_user->ID
		);

		if ( $advisor_id > 0 || $is_team_author ) {
			return;
		}

		?>
		<div class="notice notice-warning">
			<p>
				<strong>
					<?php esc_html_e( 'Blog author needs attention:', 'prelaunch-wp' ); ?>
				</strong>

				<?php
					printf(
					/* translators: %s: Travel Team account name. */
						esc_html__(
							'This author is not linked to an Advisor profile. Agency-authored posts should be assigned to %s.',
							'prelaunch-wp'
						),
						esc_html( prelaunch_get_travel_team_name() )
					);
				?>
			</p>
		</div>
		<?php
	}

	add_action(
		'admin_notices',
		'prelaunch_warn_about_unlinked_post_author'
	);
