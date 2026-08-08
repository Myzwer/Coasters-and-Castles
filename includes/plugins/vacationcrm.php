<?php

	/**
	 * VacationCRM Gravity Forms Integration
	 *
	 * Builds a VacationCRM PostLead payload from the main Gravity Forms lead
	 * form. The initial implementation operates in log-only mode:
	 *
	 * - Gravity Forms saves the complete entry normally.
	 * - The VacationCRM payload is constructed.
	 * - Advisor routing and fallbacks are resolved.
	 * - A private note containing the payload is added to the Gravity Forms entry.
	 * - No request is sent to VacationCRM.
	 *
	 * Form ID: 4
	 *
	 * @package PrelaunchWP
	 */

	declare( strict_types=1 );

	/**
	 * Main VacationCRM lead-form configuration.
	 *
	 * These values are not secrets and may remain in the theme.
	 */
	const PRELAUNCH_VCRM_FORM_ID       = 4;
	const PRELAUNCH_VCRM_DEFAULT_AGENT = 'Lynne';
	const PRELAUNCH_VCRM_MODE          = 'log_only';

	/**
	 * Build the VacationCRM payload after Gravity Forms saves the entry.
	 *
	 * This action is limited to Gravity Form 4.
	 */
	add_action(
		'gform_after_submission_' . PRELAUNCH_VCRM_FORM_ID,
		'prelaunch_vcrm_process_lead_entry',
		10,
		2
	);

	/**
	 * Process a submitted Gravity Forms lead entry.
	 *
	 * @param array<string, mixed> $entry Gravity Forms entry.
	 * @param array<string, mixed> $form Gravity Forms form object.
	 */
	function prelaunch_vcrm_process_lead_entry(
		array $entry,
		array $form
	): void {
		if ( 'disabled' === PRELAUNCH_VCRM_MODE ) {
			return;
		}

		$routing = prelaunch_vcrm_resolve_agent_routing( $entry );
		$payload = prelaunch_vcrm_build_lead_payload(
			$entry,
			$routing['agent_code'],
			$routing['warning']
		);

		if ( 'log_only' === PRELAUNCH_VCRM_MODE ) {
			prelaunch_vcrm_add_log_only_entry_note(
				$entry,
				$payload,
				$routing
			);

			return;
		}

		/*
		 * Live API delivery will be added after the generated payload has been
		 * reviewed and tested through Gravity Forms.
		 */
	}

	/**
	 * Resolve the VacationCRM agent for a submitted entry.
	 *
	 * Field 8:
	 * - No  = route to the default owner account.
	 * - Yes = resolve the Advisor post selected in field 9.
	 *
	 * @param array<string, mixed> $entry Gravity Forms entry.
	 *
	 * @return array{
	 *     agent_code: string,
	 *     advisor_name: string,
	 *     fallback_used: bool,
	 *     warning: string
	 * }
	 */
	function prelaunch_vcrm_resolve_agent_routing(
		array $entry
	): array {
		$has_preferred_advisor = trim(
			(string) rgar( $entry, '8' )
		);

		if ( 'Yes' !== $has_preferred_advisor ) {
			return [
				'agent_code'    => PRELAUNCH_VCRM_DEFAULT_AGENT,
				'advisor_name'  => 'No preferred advisor',
				'fallback_used' => false,
				'warning'       => '',
			];
		}

		$advisor_post_id = absint(
			rgar( $entry, '9' )
		);

		if ( 0 === $advisor_post_id ) {
			return prelaunch_vcrm_get_agent_fallback(
				'The submitted advisor selection was empty or invalid.',
				'Unknown advisor'
			);
		}

		$advisor_post = get_post( $advisor_post_id );

		if (
			! $advisor_post instanceof WP_Post ||
			'advisor' !== $advisor_post->post_type ||
			'publish' !== $advisor_post->post_status
		) {
			return prelaunch_vcrm_get_agent_fallback(
				'The selected Advisor post does not exist or is not published.',
				'Unknown advisor'
			);
		}

		$advisor_name = trim(
			wp_strip_all_tags( get_the_title( $advisor_post ) )
		);

		$advisor_code = trim(
			(string) get_post_meta(
				$advisor_post_id,
				'advisor_crm_id',
				true
			)
		);

		if ( '' === $advisor_code ) {
			return prelaunch_vcrm_get_agent_fallback(
				'The selected Advisor does not have a VacationCRM identifier.',
				$advisor_name
			);
		}

		return [
			'agent_code'    => $advisor_code,
			'advisor_name'  => $advisor_name,
			'fallback_used' => false,
			'warning'       => '',
		];
	}

	/**
	 * Get the default-agent routing result after an advisor-routing problem.
	 *
	 * @param string $warning Internal routing warning.
	 * @param string $advisor_name Requested advisor name.
	 *
	 * @return array{
	 *     agent_code: string,
	 *     advisor_name: string,
	 *     fallback_used: bool,
	 *     warning: string
	 * }
	 */
	function prelaunch_vcrm_get_agent_fallback(
		string $warning,
		string $advisor_name
	): array {
		return [
			'agent_code'    => PRELAUNCH_VCRM_DEFAULT_AGENT,
			'advisor_name'  => $advisor_name,
			'fallback_used' => true,
			'warning'       => $warning,
		];
	}

	/**
	 * Build the VacationCRM PostLead payload.
	 *
	 * VacationType is intentionally omitted until the agency confirms how its
	 * VacationCRM trip types should map to the website Vacation Type taxonomy.
	 *
	 * @param array<string, mixed> $entry Gravity Forms entry.
	 * @param string $agent_code VacationCRM agent code.
	 * @param string $route_warning Optional routing warning.
	 *
	 * @return array<string, mixed>
	 */
	function prelaunch_vcrm_build_lead_payload(
		array $entry,
		string $agent_code,
		string $route_warning = ''
	): array {
		$first_name = trim(
			(string) rgar( $entry, '2.3' )
		);

		$last_name = trim(
			(string) rgar( $entry, '2.6' )
		);

		$email = sanitize_email(
			(string) rgar( $entry, '3' )
		);

		$phone = trim(
			(string) rgar( $entry, '4' )
		);

		$state_name = trim(
			(string) rgar( $entry, '6' )
		);

		$passenger = [
			'FirstName'   => $first_name,
			'LastName'    => $last_name,
			'Email'       => $email,
			'Phone1'      => $phone,
			'ReferredBy'  => 'IN',
			'PrimaryPass' => 'Y',
		];

		$state_code = prelaunch_vcrm_get_state_code(
			$state_name
		);

		if ( '' !== $state_code ) {
			$passenger['State'] = $state_code;
		}

		$payload = [
			'PrimaryAgent'  => $agent_code,
			'OtherQuestion' => prelaunch_vcrm_build_other_information(
				$entry,
				$route_warning
			),
			'Passengers'    => [
				$passenger,
			],
		];

		$special_request = prelaunch_vcrm_build_special_request(
			$entry
		);

		if ( '' !== $special_request ) {
			$payload['SpecialRequest'] = $special_request;
		}

		return $payload;
	}

	/**
	 * Build the structured VacationCRM OtherQuestion notes.
	 *
	 * @param array<string, mixed> $entry Gravity Forms entry.
	 * @param string $route_warning Optional routing warning.
	 *
	 * @return string
	 */
	function prelaunch_vcrm_build_other_information(
		array $entry,
		string $route_warning = ''
	): string {
		$contact_method = trim(
			(string) rgar( $entry, '5' )
		);

		$state_name = trim(
			(string) rgar( $entry, '6' )
		);

		$vacation_type = prelaunch_vcrm_get_term_name_from_entry(
			$entry,
			'11',
			'vacation_type'
		);

		$group_type = prelaunch_vcrm_get_term_name_from_entry(
			$entry,
			'12',
			'group_type'
		);

		$planning_stage = trim(
			(string) rgar( $entry, '15' )
		);

		$trip_details = trim(
			(string) rgar( $entry, '16' )
		);

		$lines = [
			'Website Lead Details',
			'',
			'Preferred Contact Method: ' . $contact_method,
			'State of Residence: ' . $state_name,
			'Website Vacation Type: ' . $vacation_type,
			'Travel Group: ' . $group_type,
			'Planning Stage: ' . $planning_stage,
		];

		if ( '' !== $trip_details ) {
			$lines[] = '';
			$lines[] = 'Trip Details:';
			$lines[] = $trip_details;
		}

		if ( '' !== $route_warning ) {
			$lines[] = '';
			$lines[] = 'ROUTING FALLBACK:';
			$lines[] = 'The requested advisor could not be routed automatically.';
			$lines[] = 'This inquiry was assigned to the agency owner for review.';
		}

		return implode(
			"\n",
			$lines
		);
	}

	/**
	 * Build the VacationCRM SpecialRequest value.
	 *
	 * @param array<string, mixed> $entry Gravity Forms entry.
	 *
	 * @return string
	 */
	function prelaunch_vcrm_build_special_request(
		array $entry
	): string {
		$support_answer = trim(
			(string) rgar( $entry, '13' )
		);

		$support_details = trim(
			(string) rgar( $entry, '14' )
		);

		if (
			'' === $support_answer ||
			'No' === $support_answer
		) {
			return '';
		}

		$lines = [
			'Specialized Planning Support: ' . $support_answer,
		];

		if ( '' !== $support_details ) {
			$lines[] = '';
			$lines[] = 'Guest-provided details:';
			$lines[] = $support_details;
		}

		return implode(
			"\n",
			$lines
		);
	}

	/**
	 * Get a taxonomy term name from a Gravity Forms entry value.
	 *
	 * Dynamic taxonomy fields submit the WordPress term ID.
	 *
	 * @param array<string, mixed> $entry Gravity Forms entry.
	 * @param string $field_id Gravity Forms field ID.
	 * @param string $taxonomy Expected taxonomy slug.
	 *
	 * @return string
	 */
	function prelaunch_vcrm_get_term_name_from_entry(
		array $entry,
		string $field_id,
		string $taxonomy
	): string {
		$term_id = absint(
			rgar( $entry, $field_id )
		);

		if ( 0 === $term_id ) {
			return 'Not provided';
		}

		$term = get_term(
			$term_id,
			$taxonomy
		);

		if (
			is_wp_error( $term ) ||
			! $term instanceof WP_Term
		) {
			return 'Not provided';
		}

		return trim(
			wp_strip_all_tags( $term->name )
		);
	}

	/**
	 * Convert a full US state name to its two-letter code.
	 *
	 * @param string $state_name Full state name.
	 *
	 * @return string
	 */
	function prelaunch_vcrm_get_state_code(
		string $state_name
	): string {
		$states = [
			'Alabama'              => 'AL',
			'Alaska'               => 'AK',
			'Arizona'              => 'AZ',
			'Arkansas'             => 'AR',
			'California'           => 'CA',
			'Colorado'             => 'CO',
			'Connecticut'          => 'CT',
			'Delaware'             => 'DE',
			'District of Columbia' => 'DC',
			'Florida'              => 'FL',
			'Georgia'              => 'GA',
			'Hawaii'               => 'HI',
			'Idaho'                => 'ID',
			'Illinois'             => 'IL',
			'Indiana'              => 'IN',
			'Iowa'                 => 'IA',
			'Kansas'               => 'KS',
			'Kentucky'             => 'KY',
			'Louisiana'            => 'LA',
			'Maine'                => 'ME',
			'Maryland'             => 'MD',
			'Massachusetts'        => 'MA',
			'Michigan'             => 'MI',
			'Minnesota'            => 'MN',
			'Mississippi'          => 'MS',
			'Missouri'             => 'MO',
			'Montana'              => 'MT',
			'Nebraska'             => 'NE',
			'Nevada'               => 'NV',
			'New Hampshire'        => 'NH',
			'New Jersey'           => 'NJ',
			'New Mexico'           => 'NM',
			'New York'             => 'NY',
			'North Carolina'       => 'NC',
			'North Dakota'         => 'ND',
			'Ohio'                 => 'OH',
			'Oklahoma'             => 'OK',
			'Oregon'               => 'OR',
			'Pennsylvania'         => 'PA',
			'Rhode Island'         => 'RI',
			'South Carolina'       => 'SC',
			'South Dakota'         => 'SD',
			'Tennessee'            => 'TN',
			'Texas'                => 'TX',
			'Utah'                 => 'UT',
			'Vermont'              => 'VT',
			'Virginia'             => 'VA',
			'Washington'           => 'WA',
			'West Virginia'        => 'WV',
			'Wisconsin'            => 'WI',
			'Wyoming'              => 'WY',
		];

		return $states[ $state_name ] ?? '';
	}

	/**
	 * Add the generated payload as a private Gravity Forms entry note.
	 *
	 * The payload intentionally excludes the VacationCRM API key.
	 *
	 * @param array<string, mixed> $entry Gravity Forms entry.
	 * @param array<string, mixed> $payload VacationCRM payload.
	 * @param array{
	 *     agent_code: string,
	 *     advisor_name: string,
	 *     fallback_used: bool,
	 *     warning: string
	 * } $routing Advisor-routing result.
	 */
	function prelaunch_vcrm_add_log_only_entry_note(
		array $entry,
		array $payload,
		array $routing
	): void {
		if ( ! class_exists( 'GFAPI' ) ) {
			return;
		}

		$entry_id = absint(
			rgar( $entry, 'id' )
		);

		if ( 0 === $entry_id ) {
			return;
		}

		$payload_json = wp_json_encode(
			$payload,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);

		if ( false === $payload_json ) {
			$payload_json = 'Unable to encode payload.';
		}

		$note_lines = [
			'VacationCRM Log-Only Test',
			'',
			'No request was sent to VacationCRM.',
			'',
			'Requested Advisor: ' . $routing['advisor_name'],
			'Resolved Agent Code: ' . $routing['agent_code'],
			'Routing Fallback Used: ' . (
			$routing['fallback_used'] ? 'Yes' : 'No'
			),
		];

		if ( '' !== $routing['warning'] ) {
			$note_lines[] = 'Routing Warning: ' . $routing['warning'];
		}

		$note_lines[] = '';
		$note_lines[] = 'Generated Payload:';
		$note_lines[] = $payload_json;

		GFAPI::add_note(
			$entry_id,
			0,
			'VacationCRM',
			implode( "\n", $note_lines )
		);
	}
