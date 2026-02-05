/**
 * EMS Sponsor Logo Carousel
 *
 * Pure JavaScript carousel with jQuery dependency for WP compatibility.
 * Features: auto-scroll, pause on hover, keyboard navigation,
 * responsive (6 desktop / 4 tablet / 2 mobile), dot indicators, prev/next arrows.
 *
 * @package Event_Management_System
 * @since   1.5.0
 */

(function( $ ) {
	'use strict';

	/**
	 * Carousel constructor.
	 *
	 * @param {HTMLElement} el   The .ems-sponsor-carousel container.
	 */
	function EMSCarousel( el ) {
		this.el        = el;
		this.$el       = $( el );
		this.track     = el.querySelector( '.ems-sponsor-carousel__track' );
		this.slides    = Array.from( el.querySelectorAll( '.ems-sponsor-carousel__slide' ) );
		this.prevBtn   = el.querySelector( '.ems-sponsor-carousel__prev' );
		this.nextBtn   = el.querySelector( '.ems-sponsor-carousel__next' );
		this.dotsWrap  = el.querySelector( '.ems-sponsor-carousel__dots' );
		this.speed     = parseInt( el.getAttribute( 'data-speed' ), 10 ) || 3000;
		this.current   = 0;
		this.timer     = null;
		this.isHovered = false;
		this.totalSlides = this.slides.length;

		if ( this.totalSlides === 0 ) {
			return;
		}

		this.init();
	}

	EMSCarousel.prototype = {

		/**
		 * Initialize the carousel.
		 */
		init: function() {
			this.calculateVisible();
			this.buildDots();
			this.bindEvents();
			this.goTo( 0 );
			this.startAutoPlay();
		},

		/**
		 * Calculate number of visible slides based on viewport width.
		 *
		 * @return {number} Number of visible slides.
		 */
		calculateVisible: function() {
			var width = this.el.offsetWidth;
			if ( width >= 1024 ) {
				this.visible = Math.min( 6, this.totalSlides );
			} else if ( width >= 768 ) {
				this.visible = Math.min( 4, this.totalSlides );
			} else {
				this.visible = Math.min( 2, this.totalSlides );
			}
			this.maxIndex = Math.max( 0, this.totalSlides - this.visible );

			// Set slide widths.
			var slideWidth = 100 / this.visible;
			for ( var i = 0; i < this.totalSlides; i++ ) {
				this.slides[ i ].style.flex    = '0 0 ' + slideWidth + '%';
				this.slides[ i ].style.maxWidth = slideWidth + '%';
			}

			return this.visible;
		},

		/**
		 * Build dot indicators.
		 */
		buildDots: function() {
			if ( ! this.dotsWrap ) {
				return;
			}

			this.dotsWrap.innerHTML = '';
			var dotCount = this.maxIndex + 1;

			if ( dotCount <= 1 ) {
				this.dotsWrap.style.display = 'none';
				return;
			}

			this.dotsWrap.style.display = '';

			for ( var i = 0; i < dotCount; i++ ) {
				var dot = document.createElement( 'button' );
				dot.className = 'ems-sponsor-carousel__dot';
				dot.setAttribute( 'type', 'button' );
				dot.setAttribute( 'role', 'tab' );
				dot.setAttribute( 'aria-selected', i === 0 ? 'true' : 'false' );
				dot.setAttribute( 'aria-label',
					/* translators-like: Go to slide group N */
					'Go to slide group ' + ( i + 1 )
				);
				dot.setAttribute( 'data-index', i );
				this.dotsWrap.appendChild( dot );
			}
		},

		/**
		 * Bind all event listeners.
		 */
		bindEvents: function() {
			var self = this;

			// Prev / Next buttons.
			if ( this.prevBtn ) {
				this.prevBtn.addEventListener( 'click', function() {
					self.prev();
				} );
			}
			if ( this.nextBtn ) {
				this.nextBtn.addEventListener( 'click', function() {
					self.next();
				} );
			}

			// Dots.
			if ( this.dotsWrap ) {
				this.dotsWrap.addEventListener( 'click', function( e ) {
					var dot = e.target.closest( '.ems-sponsor-carousel__dot' );
					if ( dot ) {
						var index = parseInt( dot.getAttribute( 'data-index' ), 10 );
						self.goTo( index );
					}
				} );
			}

			// Pause on hover.
			this.el.addEventListener( 'mouseenter', function() {
				self.isHovered = true;
				self.stopAutoPlay();
			} );
			this.el.addEventListener( 'mouseleave', function() {
				self.isHovered = false;
				self.startAutoPlay();
			} );

			// Keyboard navigation.
			this.el.addEventListener( 'keydown', function( e ) {
				if ( e.key === 'ArrowLeft' || e.key === 'Left' ) {
					e.preventDefault();
					self.prev();
				} else if ( e.key === 'ArrowRight' || e.key === 'Right' ) {
					e.preventDefault();
					self.next();
				}
			} );

			// Make carousel focusable.
			if ( ! this.el.getAttribute( 'tabindex' ) ) {
				this.el.setAttribute( 'tabindex', '0' );
			}

			// Responsive: recalculate on resize.
			var resizeTimer;
			window.addEventListener( 'resize', function() {
				clearTimeout( resizeTimer );
				resizeTimer = setTimeout( function() {
					var prevVisible = self.visible;
					self.calculateVisible();
					if ( prevVisible !== self.visible ) {
						self.buildDots();
						if ( self.current > self.maxIndex ) {
							self.current = self.maxIndex;
						}
						self.goTo( self.current );
					}
				}, 200 );
			} );
		},

		/**
		 * Navigate to a specific slide index.
		 *
		 * @param {number} index Slide index (0-based).
		 */
		goTo: function( index ) {
			if ( index < 0 ) {
				index = this.maxIndex;
			}
			if ( index > this.maxIndex ) {
				index = 0;
			}

			this.current = index;

			// Transform the track.
			var offset = -( index * ( 100 / this.visible ) );
			this.track.style.transform = 'translateX(' + offset + '%)';
			this.track.style.transition = 'transform 0.4s ease';

			// Update tabindex on slide links.
			for ( var i = 0; i < this.totalSlides; i++ ) {
				var link = this.slides[ i ].querySelector( 'a' );
				if ( link ) {
					if ( i >= index && i < index + this.visible ) {
						link.setAttribute( 'tabindex', '0' );
					} else {
						link.setAttribute( 'tabindex', '-1' );
					}
				}
			}

			// Update dots.
			this.updateDots();

			// Update arrow states.
			this.updateArrows();
		},

		/**
		 * Go to the previous slide group.
		 */
		prev: function() {
			this.goTo( this.current - 1 );
			this.restartAutoPlay();
		},

		/**
		 * Go to the next slide group.
		 */
		next: function() {
			this.goTo( this.current + 1 );
			this.restartAutoPlay();
		},

		/**
		 * Update dot active states.
		 */
		updateDots: function() {
			if ( ! this.dotsWrap ) {
				return;
			}
			var dots = this.dotsWrap.querySelectorAll( '.ems-sponsor-carousel__dot' );
			for ( var i = 0; i < dots.length; i++ ) {
				if ( i === this.current ) {
					dots[ i ].classList.add( 'ems-sponsor-carousel__dot--active' );
					dots[ i ].setAttribute( 'aria-selected', 'true' );
				} else {
					dots[ i ].classList.remove( 'ems-sponsor-carousel__dot--active' );
					dots[ i ].setAttribute( 'aria-selected', 'false' );
				}
			}
		},

		/**
		 * Update arrow disabled states.
		 */
		updateArrows: function() {
			// Arrows always work (wrapping), but dim at bounds for visual cue.
			if ( this.prevBtn ) {
				this.prevBtn.classList.toggle( 'ems-sponsor-carousel__arrow--dimmed', this.current === 0 );
			}
			if ( this.nextBtn ) {
				this.nextBtn.classList.toggle( 'ems-sponsor-carousel__arrow--dimmed', this.current === this.maxIndex );
			}
		},

		/**
		 * Start auto-play timer.
		 */
		startAutoPlay: function() {
			if ( this.isHovered || this.maxIndex === 0 ) {
				return;
			}
			var self = this;
			this.timer = setInterval( function() {
				self.next();
			}, self.speed );
		},

		/**
		 * Stop auto-play timer.
		 */
		stopAutoPlay: function() {
			if ( this.timer ) {
				clearInterval( this.timer );
				this.timer = null;
			}
		},

		/**
		 * Restart auto-play (used after manual interaction).
		 */
		restartAutoPlay: function() {
			this.stopAutoPlay();
			if ( ! this.isHovered ) {
				this.startAutoPlay();
			}
		}
	};

	/**
	 * Initialize all carousels on the page.
	 */
	$( document ).ready( function() {
		var carousels = document.querySelectorAll( '.ems-sponsor-carousel' );
		for ( var i = 0; i < carousels.length; i++ ) {
			new EMSCarousel( carousels[ i ] );
		}
	} );

})( jQuery );
