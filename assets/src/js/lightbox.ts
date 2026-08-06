/**
 * Advisor gallery lightbox.
 *
 * Opens gallery images inside a shared native dialog and supports:
 * - mouse, touch, and keyboard activation
 * - Escape-key closing through native dialog behavior
 * - close-button interaction
 * - backdrop-click closing
 * - body scroll locking while open
 *
 * No previous/next navigation is intentionally included.
 */

const initializeAdvisorLightboxes = (): void => {
	const dialogs = document.querySelectorAll<HTMLDialogElement>(
		'.advisor-lightbox',
	);

	dialogs.forEach((dialog) => {
		const gallerySection = dialog.closest<HTMLElement>('section');
		const image =
			dialog.querySelector<HTMLImageElement>('.advisor-lightbox__image');
		const closeButton = dialog.querySelector<HTMLButtonElement>(
			'.advisor-lightbox__close',
		);

		if (!gallerySection || !image || !closeButton) {
			return;
		}

		const triggers = gallerySection.querySelectorAll<HTMLButtonElement>(
			'.advisor-lightbox-trigger',
		);

		const clearImage = (): void => {
			image.removeAttribute('src');
			image.alt = '';
		};

		const closeDialog = (): void => {
			if (!dialog.open) {
				return;
			}

			dialog.close();
		};

		triggers.forEach((trigger) => {
			trigger.addEventListener('click', () => {
				const imageUrl = trigger.dataset.lightboxImage;
				const imageAlt = trigger.dataset.lightboxAlt ?? '';

				if (!imageUrl) {
					return;
				}

				image.src = imageUrl;
				image.alt = imageAlt;

				document.body.classList.add('advisor-lightbox-open');
				dialog.showModal();
			});
		});

		closeButton.addEventListener('click', closeDialog);

		dialog.addEventListener('click', (event: MouseEvent) => {
			/*
			 * The dialog element itself represents the backdrop-click area.
			 * Clicks inside .advisor-lightbox__inner should not close it.
			 */
			if (event.target === dialog) {
				closeDialog();
			}
		});

		dialog.addEventListener('close', () => {
			document.body.classList.remove('advisor-lightbox-open');
			clearImage();
		});

		dialog.addEventListener('cancel', () => {
			document.body.classList.remove('advisor-lightbox-open');
		});
	});
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initializeAdvisorLightboxes);
} else {
	initializeAdvisorLightboxes();
}
