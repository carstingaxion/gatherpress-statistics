/**
 * Frontend JavaScript for GatherPress Statistics block.
 *
 * This file adds confetti animation to the confetti style variation
 * and other interactive enhancements.
 */

document.addEventListener( 'DOMContentLoaded', function () {
	const statBlocks = document.querySelectorAll(
		'.wp-block-gatherpress-statistics'
	);

	statBlocks.forEach( function ( block ) {
		const numberElement = block.querySelector(
			'.gatherpress-stats-number'
		);

		if ( numberElement ) {
			// Fade in animation for all blocks
			numberElement.style.opacity = '0';
			setTimeout( function () {
				numberElement.style.transition = 'opacity 0.5s ease-in-out';
				numberElement.style.opacity = '1';
			}, 100 );
		}

		// Add confetti functionality for confetti style
		if ( block.classList.contains( 'is-style-confetti' ) ) {
			addConfettiAnimation( block );
		}
	} );

	/**
	 * Add confetti animation to a block
	 * @param {HTMLElement} block - The block element to add confetti to
	 */
	function addConfettiAnimation( block ) {
		// Create confetti container
		const confettiContainer = document.createElement( 'div' );
		confettiContainer.className = 'gatherpress-confetti';
		block.appendChild( confettiContainer );

		// Confetti colors
		const colors = [
			'#ff6b6b',
			'#4ecdc4',
			'#45b7d1',
			'#f9ca24',
			'#f0932b',
			'#eb4d4b',
			'#6c5ce7',
			'#a29bfe',
		];

		/**
		 * Create a single confetti piece
		 * @param {number} delay - Animation delay in milliseconds
		 */
		function createConfetti( delay ) {
			const confetti = document.createElement( 'div' );
			confetti.className = 'gatherpress-confetti-piece';

			// Random properties
			const left = Math.random() * 100;
			const size = Math.random() * 6 + 4;
			const color = colors[ Math.floor( Math.random() * colors.length ) ];
			const animationDuration = Math.random() * 2 + 2;
			const animationDelay = delay || Math.random() * 0.5;

			confetti.style.left = left + '%';
			confetti.style.width = size + 'px';
			confetti.style.height = size + 'px';
			confetti.style.backgroundColor = color;
			confetti.style.animationDuration = animationDuration + 's';
			confetti.style.animationDelay = animationDelay + 's';

			confettiContainer.appendChild( confetti );

			// Remove confetti after animation completes
			setTimeout(
				function () {
					if ( confetti.parentNode ) {
						confetti.parentNode.removeChild( confetti );
					}
				},
				( animationDuration + animationDelay ) * 1000
			);
		}

		/**
		 * Trigger confetti burst
		 */
		function triggerConfetti() {
			// Create 30 confetti pieces with staggered delays
			for ( let i = 0; i < 30; i++ ) {
				setTimeout( function () {
					createConfetti( i * 0.05 );
				}, i * 10 );
			}
		}

		// Trigger confetti on hover
		let isHovering = false;
		let hoverTimeout;

		block.addEventListener( 'mouseenter', function () {
			isHovering = true;
			// Delay slightly to avoid triggering on quick mouse movements
			hoverTimeout = setTimeout( function () {
				if ( isHovering ) {
					triggerConfetti();
				}
			}, 100 );
		} );

		block.addEventListener( 'mouseleave', function () {
			isHovering = false;
			clearTimeout( hoverTimeout );
		} );
	}
} );
