<?php

	/**
	 * Advisor biography section.
	 *
	 * Displays the advisor's biography beneath a conversational heading.
	 * Background alternation and texture are handled by single-advisor.php.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$advisor_id = get_the_ID();
	$bio        = get_field( 'advisor_bio', $advisor_id );

	if ( ! $bio ) {
		return;
	}

	$advisor_name       = get_the_title();
	$advisor_first_name = strtok( $advisor_name, ' ' );

	if ( false === $advisor_first_name ) {
		$advisor_first_name = $advisor_name;
	}
?>

<section class="py-10 md:py-16 wrap">
	<div class="grid-12">
		<div class="col-span-12">

			<h2 class="heading-2 normal-case">
				<?php
					printf(
					/* translators: %s: Advisor first name. */
						esc_html__( 'Hey, I’m %s', 'prelaunch-wp' ),
						esc_html( $advisor_first_name )
					);
				?>
				<span aria-hidden="true">👋</span>
			</h2>

			<div class="prose-theme mt-6">
				<?php echo wp_kses_post( $bio ); ?>
			</div>

		</div>
	</div>
</section>
