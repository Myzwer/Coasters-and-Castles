<?php
	/**
	 * Shared PHP design tokens for WordPress-driven UI contexts.
	 */

	defined( 'ABSPATH' ) || exit;

	/**
	 * Return brand color tokens used by PHP-driven theme contexts.
	 *
	 * @return array<string, string>
	 */
	function prelaunch_get_brand_colors(): array {
		return [
			// Neutrals.
			'black'                 => '#272D2D',
			'white'                 => '#F9F7F3',

			// Brand roles.
			'primary'               => '#F82F2F',
			'secondary'             => '#001890',
			'soft-1'                => '#D6EFFF',
			'soft-2'                => '#F4EAD7',

			// Gradient endpoints.
			'primary-gradient-to'   => '#F5E4C6',
			'secondary-gradient-to' => '#B4DFFA',
			'impact-gradient-to'    => '#001582',
		];
	}
