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

	$facebook  = get_field( 'facebook', $advisor_id );
	$instagram = get_field( 'instagram', $advisor_id );
	$tiktok    = get_field( 'tiktok', $advisor_id );
	$youtube   = get_field( 'youtube', $advisor_id );

	$social_links = array_filter(
		[
			[
				'url'   => $facebook,
				'label' => __( 'Facebook', 'prelaunch-wp' ),
				'icon'  => 'fa-brands fa-facebook-f',
			],
			[
				'url'   => $instagram,
				'label' => __( 'Instagram', 'prelaunch-wp' ),
				'icon'  => 'fa-brands fa-instagram',
			],
			[
				'url'   => $tiktok,
				'label' => __( 'TikTok', 'prelaunch-wp' ),
				'icon'  => 'fa-brands fa-tiktok',
			],
			[
				'url'   => $youtube,
				'label' => __( 'YouTube', 'prelaunch-wp' ),
				'icon'  => 'fa-brands fa-youtube',
			],
		],
		static fn( array $social ): bool => ! empty( $social['url'] )
	);
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

			<?php if ( $social_links ) : ?>
				<div class="mt-8">
					<h3 class="text-lg font-semibold">
						<?php esc_html_e( 'Connect with me', 'prelaunch-wp' ); ?>
					</h3>

					<div class="mt-3 grid w-fit grid-flow-col gap-3">
						<?php foreach ( $social_links as $social ) : ?>
							<a
								class="grid size-11 place-items-center rounded-full bg-white text-xl text-secondary shadow-md transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-secondary focus-visible:ring-offset-4"
								href="<?php echo esc_url( $social['url'] ); ?>"
								target="_blank"
								rel="noopener noreferrer"
								aria-label="<?php
									echo esc_attr(
										sprintf(
										/* translators: %s: Social network name. */
											__( 'Follow this advisor on %s', 'prelaunch-wp' ),
											$social['label']
										)
									);
								?>"
							>
								<i
									class="<?php echo esc_attr( $social['icon'] ); ?>"
									aria-hidden="true"
								></i>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
