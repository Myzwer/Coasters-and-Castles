<?php

	/**
	 * Gravity Forms Dynamic Options
	 *
	 * Dynamically populates Gravity Forms choice fields using WordPress content:
	 *
	 * - Published Advisor posts.
	 * - Vacation Type taxonomy terms.
	 * - Group Type taxonomy terms.
	 *
	 * To populate a Gravity Forms field, add one of these classes under:
	 *
	 * Appearance > Custom CSS Class
	 *
	 * - populate-advisors
	 * - populate-vacation-types
	 * - populate-group-types
	 *
	 * Supported Gravity Forms field types include Drop Down, Radio Buttons,
	 * Checkboxes, and other choice-based fields exposing a `choices` property.
	 *
	 * Advisor field values use the Advisor post ID. This keeps WordPress as the
	 * source of truth and allows the VacationCRM agent code to be resolved and
	 * validated server-side when constructing the API payload.
	 *
	 * Published advisors without a VacationCRM identifier are excluded because
	 * they cannot currently be routed safely through the integration.
	 *
	 * @package PrelaunchWP
	 */

	declare( strict_types=1 );

	/**
	 * Populate dynamic Gravity Forms choices before the form is rendered.
	 */
	add_filter(
		'gform_pre_render',
		'prelaunch_populate_gravity_forms_choices'
	);

	/**
	 * Repopulate choices before validation.
	 *
	 * Gravity Forms must know the dynamically generated choices when validating
	 * a submitted value.
	 */
	add_filter(
		'gform_pre_validation',
		'prelaunch_populate_gravity_forms_choices'
	);

	/**
	 * Repopulate choices before submission processing.
	 *
	 * This preserves choice labels and values for entries, notifications,
	 * merge tags, and integrations.
	 */
	add_filter(
		'gform_pre_submission_filter',
		'prelaunch_populate_gravity_forms_choices'
	);

	/**
	 * Repopulate choices when an entry is viewed in wp-admin.
	 */
	add_filter(
		'gform_admin_pre_render',
		'prelaunch_populate_gravity_forms_choices'
	);

	/**
	 * Populate supported Gravity Forms fields with WordPress content.
	 *
	 * @param array<string, mixed> $form Gravity Forms form object.
	 *
	 * @return array<string, mixed>
	 */
	function prelaunch_populate_gravity_forms_choices( array $form ): array {
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return $form;
		}

		foreach ( $form['fields'] as $field ) {
			if ( ! is_object( $field ) ) {
				continue;
			}

			$css_classes = prelaunch_get_gravity_forms_field_classes( $field );

			if ( in_array( 'populate-advisors', $css_classes, true ) ) {
				$field->choices = prelaunch_get_advisor_form_choices();

				prelaunch_set_gravity_forms_placeholder(
					$field,
					__( 'Select an advisor', 'prelaunch-wp' )
				);

				continue;
			}

			if ( in_array( 'populate-vacation-types', $css_classes, true ) ) {
				$field->choices = prelaunch_get_taxonomy_form_choices(
					'vacation_type'
				);

				prelaunch_set_gravity_forms_placeholder(
					$field,
					__( 'Select a vacation type', 'prelaunch-wp' )
				);

				continue;
			}

			if ( in_array( 'populate-group-types', $css_classes, true ) ) {
				$field->choices = prelaunch_get_taxonomy_form_choices(
					'group_type'
				);

				prelaunch_set_gravity_forms_placeholder(
					$field,
					__( 'Select who is traveling', 'prelaunch-wp' )
				);
			}
		}

		return $form;
	}

	/**
	 * Get the CSS classes assigned to a Gravity Forms field.
	 *
	 * @param object $field Gravity Forms field object.
	 *
	 * @return array<int, string>
	 */
	function prelaunch_get_gravity_forms_field_classes( object $field ): array {
		if (
			! isset( $field->cssClass ) ||
			! is_string( $field->cssClass )
		) {
			return [];
		}

		$classes = preg_split(
			'/\s+/',
			trim( $field->cssClass )
		);

		if ( ! is_array( $classes ) ) {
			return [];
		}

		return array_values(
			array_filter(
				$classes,
				static fn( string $class ): bool => '' !== $class
			)
		);
	}

	/**
	 * Get published Advisors as Gravity Forms choices.
	 *
	 * The Advisor name is displayed to the visitor. The submitted value is the
	 * corresponding Advisor post ID.
	 *
	 * The VacationCRM advisor identifier is intentionally not submitted by the
	 * browser. It will be retrieved and validated server-side when constructing
	 * the VacationCRM API payload.
	 *
	 * Published advisors without a CRM identifier are excluded because they
	 * cannot currently be routed safely through the VacationCRM API.
	 *
	 * @return array<int, array{text: string, value: string}>
	 */
	function prelaunch_get_advisor_form_choices(): array {
		$advisor_posts = get_posts(
			[
				'post_type'              => 'advisor',
				'post_status'            => 'publish',
				'posts_per_page'         => - 1,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			]
		);

		$choices = [];

		foreach ( $advisor_posts as $advisor_post ) {
			$advisor_name = trim(
				wp_strip_all_tags( get_the_title( $advisor_post ) )
			);

			$advisor_crm_id = trim(
				(string) get_post_meta(
					$advisor_post->ID,
					'advisor_crm_id',
					true
				)
			);

			if (
				'' === $advisor_name ||
				'' === $advisor_crm_id
			) {
				continue;
			}

			$choices[] = [
				'text'  => $advisor_name,
				'value' => (string) $advisor_post->ID,
			];
		}

		return $choices;
	}

	/**
	 * Get taxonomy terms as Gravity Forms choices.
	 *
	 * Term IDs are used as submitted values so visible labels may be renamed
	 * without changing the underlying identifier stored with new entries.
	 *
	 * Escape-hatch choices such as "Other" and "I'm not sure" are moved to the
	 * bottom of their corresponding lists.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array<int, array{text: string, value: string}>
	 */
	function prelaunch_get_taxonomy_form_choices(
		string $taxonomy
	): array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		$terms = prelaunch_sort_gravity_forms_taxonomy_terms(
			$terms,
			$taxonomy
		);

		$choices = [];

		foreach ( $terms as $term ) {
			$choices[] = [
				'text'  => $term->name,
				'value' => (string) $term->term_id,
			];
		}

		return $choices;
	}

	/**
	 * Move designated fallback taxonomy terms to the bottom of their lists.
	 *
	 * All ordinary terms retain their alphabetical order from get_terms().
	 * Apostrophe variations are normalized so both "I'm not sure" and
	 * "I’m not sure" are recognized.
	 *
	 * @param array<int, WP_Term> $terms Taxonomy terms.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array<int, WP_Term>
	 */
	function prelaunch_sort_gravity_forms_taxonomy_terms(
		array $terms,
		string $taxonomy
	): array {
		$bottom_terms = [
			'vacation_type' => [
				'other',
			],
			'group_type'    => [
				"i'm not sure",
			],
		];

		if ( empty( $bottom_terms[ $taxonomy ] ) ) {
			return $terms;
		}

		$standard_terms = [];
		$fallback_terms = [];

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$normalized_name = prelaunch_normalize_gravity_forms_choice_label(
				$term->name
			);

			if (
				in_array(
					$normalized_name,
					$bottom_terms[ $taxonomy ],
					true
				)
			) {
				$fallback_terms[] = $term;
				continue;
			}

			$standard_terms[] = $term;
		}

		return array_merge(
			$standard_terms,
			$fallback_terms
		);
	}

	/**
	 * Normalize a choice label for reliable comparisons.
	 *
	 * @param string $label Choice label.
	 *
	 * @return string
	 */
	function prelaunch_normalize_gravity_forms_choice_label(
		string $label
	): string {
		$label = wp_strip_all_tags( $label );

		$label = str_replace(
			[
				'’',
				'‘',
				'`',
			],
			"'",
			$label
		);

		return strtolower(
			trim( $label )
		);
	}

	/**
	 * Set a placeholder on supported Gravity Forms fields.
	 *
	 * The placeholder remains separate from the choices and therefore cannot be
	 * accidentally submitted as a valid choice.
	 *
	 * @param object $field Gravity Forms field object.
	 * @param string $placeholder Placeholder text.
	 */
	function prelaunch_set_gravity_forms_placeholder(
		object $field,
		string $placeholder
	): void {
		if ( property_exists( $field, 'placeholder' ) ) {
			$field->placeholder = $placeholder;
		}
	}


	/**
	 * Replace stored dynamic choice values with human-readable labels when
	 * Gravity Forms displays entry values in wp-admin and notifications.
	 */
	add_filter(
		'gform_entry_field_value',
		'prelaunch_format_gravity_forms_dynamic_entry_value',
		10,
		4
	);

	/**
	 * Format dynamic Gravity Forms entry values for humans.
	 *
	 * The stored entry value remains the stable WordPress object ID. This filter
	 * changes only the displayed value when Gravity Forms renders an entry.
	 *
	 * @param string $value The value Gravity Forms is about to display.
	 * @param GF_Field $field Gravity Forms field object.
	 * @param array $entry Gravity Forms entry.
	 * @param array $form Gravity Forms form object.
	 *
	 * @return string
	 */
	function prelaunch_format_gravity_forms_dynamic_entry_value(
		string $value,
		$field,
		array $entry,
		array $form
	): string {
		if ( ! is_object( $field ) ) {
			return $value;
		}

		$css_classes = prelaunch_get_gravity_forms_field_classes( $field );

		if ( in_array( 'populate-advisors', $css_classes, true ) ) {
			$advisor_post_id = absint( $value );

			if ( 0 === $advisor_post_id ) {
				return $value;
			}

			$advisor_post = get_post( $advisor_post_id );

			if (
				! $advisor_post instanceof WP_Post ||
				'advisor' !== $advisor_post->post_type
			) {
				return $value;
			}

			$advisor_name = trim(
				wp_strip_all_tags( get_the_title( $advisor_post ) )
			);

			return '' !== $advisor_name
				? $advisor_name
				: $value;
		}

		if ( in_array( 'populate-vacation-types', $css_classes, true ) ) {
			return prelaunch_get_gravity_forms_term_display_name(
				$value,
				'vacation_type'
			);
		}

		if ( in_array( 'populate-group-types', $css_classes, true ) ) {
			return prelaunch_get_gravity_forms_term_display_name(
				$value,
				'group_type'
			);
		}

		return $value;
	}

	/**
	 * Get a taxonomy term's human-readable name for Gravity Forms display.
	 *
	 * @param string $value Stored Gravity Forms entry value.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return string
	 */
	function prelaunch_get_gravity_forms_term_display_name(
		string $value,
		string $taxonomy
	): string {
		$term_id = absint( $value );

		if ( 0 === $term_id ) {
			return $value;
		}

		$term = get_term(
			$term_id,
			$taxonomy
		);

		if (
			is_wp_error( $term ) ||
			! $term instanceof WP_Term
		) {
			return $value;
		}

		$term_name = trim(
			wp_strip_all_tags( $term->name )
		);

		return '' !== $term_name
			? $term_name
			: $value;
	}
