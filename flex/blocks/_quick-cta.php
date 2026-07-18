<?php
	/**
	 * Call to Action block.
	 *
	 * Renders a short call-to-action message with a single button.
	 *
	 * Used in:
	 * - conversion prompts
	 * - signup invitations
	 * - quick engagement sections
	 *
	 * Content is sourced from ACF Flexible Content fields.
	 *
	 * Notes:
	 * - Message field is typically a short paragraph.
	 * - Button links to a primary action such as contact or signup.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}


	$title   = get_sub_field( 'huge_title' );
	$message = get_sub_field( 'message' );
	$link    = get_sub_field( 'link' );
?>

<section class="bg-impact-gradient text-white">
	<div class="py-20 wrap">
		<div class="grid-12">
			<div class="col-span-12 md:col-span-5">
				<?php if ( $title ) : ?>
					<h2 class="text-6xl font-bold uppercase"><?php echo nl2br( esc_html( $title ) ); ?></h2>
				<?php endif; ?>
			</div>

			<div class="col-span-12 grid md:col-span-6 md:col-start-7 md:items-center">
				<div class="grid justify-items-start gap-6">
					<?php if ( $message ) : ?>
						<p><?php echo nl2br( esc_html( $message ) ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $link['url'] ) ) : ?>
						<a
							class="btn_light"
							href="<?php echo esc_url( $link['url'] ); ?>"
							<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener noreferrer"' : ''; ?>
						>
							<span><?php echo esc_html( $link['title'] ?: 'Learn More' ); ?></span>
							<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
						</a>
					<?php endif; ?>
				</div>
			</div>

		</div>
	</div>
</section>
