<?php

	/**
	 * Advisor short-name helpers.
	 *
	 * Greetings and CTAs use a short form of the profile title:
	 *
	 * - Default: everything except the last word ("Mary Jane Foster" → "Mary Jane").
	 * - Optional `advisor_short_name` override for edge cases.
	 * - Optional `advisor_is_duo` toggle switches pronouns to we / us / our.
	 */

	declare( strict_types=1 );

	/**
	 * Normalize an advisor title for name parsing.
	 *
	 * WordPress stores ampersands in post titles as HTML entities
	 * (e.g. "Leanna &#038; Dusty Schnipke"). Decode those so short-name
	 * output matches what editors see in admin.
	 */
	function prelaunch_normalize_advisor_name( string $name ): string {
		$name = wp_strip_all_tags( $name );
		$name = wp_specialchars_decode( $name, ENT_QUOTES );
		$name = html_entity_decode( $name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return trim( $name );
	}

	/**
	 * Derive a short greeting / CTA name from a full advisor title.
	 *
	 * Drops the final word (treated as the shared / family surname):
	 *
	 * - "Jane Foster" → "Jane"
	 * - "Mary Jane Foster" → "Mary Jane"
	 * - "Josh & Mary Jane Foster" → "Josh & Mary Jane"
	 *
	 * Single-word titles are returned unchanged.
	 */
	function prelaunch_parse_advisor_short_name( string $full_name ): string {
		$full_name = prelaunch_normalize_advisor_name( $full_name );

		if ( '' === $full_name ) {
			return '';
		}

		$parts = preg_split( '/\s+/', $full_name );

		if ( ! is_array( $parts ) || [] === $parts ) {
			return $full_name;
		}

		if ( count( $parts ) < 2 ) {
			return $parts[0];
		}

		array_pop( $parts );

		return implode( ' ', $parts );
	}

	/**
	 * Resolve the advisor ID for helpers that default to the current post.
	 */
	function prelaunch_resolve_advisor_id( int $advisor_id = 0 ): int {
		if ( $advisor_id > 0 ) {
			return $advisor_id;
		}

		$current_id = (int) get_the_ID();

		return $current_id > 0 ? $current_id : 0;
	}

	/**
	 * Get the short name used in greetings and booking CTAs.
	 *
	 * Prefers the optional `advisor_short_name` ACF override, then drops the
	 * last word from the profile title.
	 */
	function prelaunch_get_advisor_short_name( int $advisor_id = 0 ): string {
		$advisor_id = prelaunch_resolve_advisor_id( $advisor_id );

		if ( $advisor_id <= 0 ) {
			return '';
		}

		$override = '';

		if ( function_exists( 'get_field' ) ) {
			$override = trim(
				(string) get_field( 'advisor_short_name', $advisor_id )
			);
		}

		if ( '' !== $override ) {
			return $override;
		}

		return prelaunch_parse_advisor_short_name(
			(string) get_the_title( $advisor_id )
		);
	}

	/**
	 * Whether this advisor profile should use duo / team voice copy.
	 *
	 * Controlled only by the agency `advisor_is_duo` checkbox.
	 */
	function prelaunch_advisor_is_duo( int $advisor_id = 0 ): bool {
		$advisor_id = prelaunch_resolve_advisor_id( $advisor_id );

		if ( $advisor_id <= 0 || ! function_exists( 'get_field' ) ) {
			return false;
		}

		return (bool) get_field( 'advisor_is_duo', $advisor_id );
	}
