<?php
	/**
	 * Advisor archive card.
	 *
	 * Displays:
	 * - Professional headshot
	 * - Advisor name
	 * - Vacation Type terms
	 * - Group Type terms
	 * - Profile CTA
	 *
	 * Relies on the global post context supplied by the advisor archive loop.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$advisor_id   = get_the_ID();
	$advisor_name = get_the_title();

	$external_url = get_field(
		'advisor_external_url',
		$advisor_id
	);

	$profile_url = $external_url
		? $external_url
		: get_permalink( $advisor_id );

	$link_target = $external_url
		? '_blank'
		: '_self';

	$link_rel = $external_url
		? 'noopener noreferrer'
		: '';

	$headshot_id = get_field(
		'advisor_professional_headshot',
		$advisor_id
	);

	/*
	 * Fall back to the general advisor headshot when the archive-specific
	 * professional image has not yet been assigned.
	 */
	if ( ! $headshot_id ) {
		$headshot_id = get_field(
			'advisor_headshot',
			$advisor_id
		);
	}

	$vacation_types = get_the_terms(
		$advisor_id,
		'vacation_type'
	);

	$group_types = get_the_terms(
		$advisor_id,
		'group_type'
	);

	$has_vacation_types = (
		! is_wp_error( $vacation_types )
		&& ! empty( $vacation_types )
	);

	$has_group_types = (
		! is_wp_error( $group_types )
		&& ! empty( $group_types )
	);
?>

<article
	class="grid h-full grid-rows-[auto_1fr] overflow-hidden rounded-xl bg-white shadow-lg border-3 border-secondary"
>

	<a
		class="block overflow-hidden"
		href="<?php echo esc_url( $profile_url ); ?>"
		target="<?php echo esc_attr( $link_target ); ?>"
		<?php echo $link_rel ? 'rel="' . esc_attr( $link_rel ) . '"' : ''; ?>
		aria-label="<?php
			echo esc_attr(
				sprintf(
				/* translators: %s: advisor name. */
					__( 'View the profile for %s', 'prelaunch-wp' ),
					$advisor_name
				)
			);
		?>"
	>
		<?php if ( $headshot_id ) : ?>

			<?php
			echo wp_get_attachment_image(
				$headshot_id,
				'large',
				false,
				[
					'class'    => 'block aspect-[4/5] w-full object-cover object-[center_15%]',
					'loading'  => 'lazy',
					'decoding' => 'async',
				]
			);
			?>

		<?php else : ?>

			<div class="grid aspect-square w-full place-items-center bg-white">
				<i
					class="fa-regular fa-user text-6xl opacity-30"
					aria-hidden="true"
				></i>

				<span class="sr-only">
					<?php esc_html_e( 'No advisor headshot available', 'prelaunch-wp' ); ?>
				</span>
			</div>

		<?php endif; ?>
	</a>

	<div class="grid grid-rows-[1fr_auto]">

		<div class="p-5 md:p-6">

			<h2 class="heading-4">
				<a
					class="hover:underline focus-visible:underline underline-offset-4"
					href="<?php echo esc_url( $profile_url ); ?>"
					target="<?php echo esc_attr( $link_target ); ?>"
					<?php echo $link_rel ? 'rel="' . esc_attr( $link_rel ) . '"' : ''; ?>
				>
					<?php echo esc_html( $advisor_name ); ?>
				</a>
			</h2>

			<?php if ( $has_vacation_types ) : ?>
				<div class="mt-5">
					<h3 class="text-sm font-bold uppercase tracking-wide">
						<?php esc_html_e( 'Vacation Types', 'prelaunch-wp' ); ?>
					</h3>

					<ul class=" flex flex-wrap gap-x-2 gap-y-1">
						<ul class="mt-1 grid gap-1">
							<?php foreach ( $vacation_types as $vacation_type ) : ?>
								<li class="text-sm leading-snug">
									<?php echo esc_html( $vacation_type->name ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $has_group_types ) : ?>
				<div class="mt-5">
					<h3 class="text-sm font-bold uppercase tracking-wide">
						<?php esc_html_e( 'Group Types', 'prelaunch-wp' ); ?>
					</h3>

					<ul class="flex flex-wrap gap-x-2 gap-y-1">
						<ul class="mt-1 grid gap-1">
							<?php foreach ( $group_types as $group_type ) : ?>
								<li class="text-sm leading-snug">
									<?php echo esc_html( $group_type->name ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</ul>
				</div>
			<?php endif; ?>

		</div>

		<a
			class="grid place-items-center bg-secondary px-5 py-3 text-center font-bold text-white transition hover:brightness-90 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-inset focus-visible:ring-white focus-visible:brightness-90"
			href="<?php echo esc_url( $profile_url ); ?>"
			target="<?php echo esc_attr( $link_target ); ?>"
			<?php echo $link_rel ? 'rel="' . esc_attr( $link_rel ) . '"' : ''; ?>
		>
	<span class="inline-grid grid-flow-col items-center gap-2">
		<span>
			<?php
				echo esc_html(
					$external_url
						? __( 'Visit Website', 'prelaunch-wp' )
						: __( 'View Profile', 'prelaunch-wp' )
				);
			?>
		</span>

		<i
			class="fa-solid fa-arrow-right"
			aria-hidden="true"
		></i>
	</span>
		</a>

	</div>

</article>
