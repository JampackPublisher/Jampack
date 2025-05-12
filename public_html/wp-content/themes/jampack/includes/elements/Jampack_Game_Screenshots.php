<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Jampack_Game_Screenshots extends \Bricks\Element {
	public $block    = 'core/gallery';
	public $category = 'media';
	public $name     = 'game-screenshots';
	public $icon     = 'ti-gallery';
	public $scripts  = [ 'bricksIsotope' ];

	public function get_label() {
		return esc_html__( 'Game Screenshots', 'jampack' );
	}

	public function enqueue_scripts() {
		$layout = $this->settings['layout'] ?? 'grid';

		if ( $layout === 'masonry' ) {
			wp_enqueue_script( 'bricks-isotope' );
			wp_enqueue_style( 'bricks-isotope' );
		}

		$link_to = $this->settings['link'] ?? false;

		if ( $link_to === 'lightbox' ) {
			wp_enqueue_script( 'bricks-photoswipe' );
			wp_enqueue_script( 'bricks-photoswipe-lightbox' );
			wp_enqueue_style( 'bricks-photoswipe' );
		}
	}

	public function set_controls() {
		$this->controls['_border']['css'][0]['selector']    = '.image';
		$this->controls['_boxShadow']['css'][0]['selector'] = '.image';

		// Settings

		$this->controls['settingsSeparator'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Settings', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['layout'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Layout', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'grid'    => esc_html__( 'Grid', 'bricks' ),
				'masonry' => esc_html__( 'Masonry', 'bricks' ),
				'metro'   => esc_html__( 'Metro', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Grid', 'bricks' ),
			'inline'      => true,
			'rerender'    => true,
		];

		$this->control_options['imageRatio']['custom'] = esc_html__( 'Custom', 'bricks' );

		$this->controls['imageRatio'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Image ratio', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['imageRatio'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Square', 'bricks' ),
			'required'    => [ 'layout', '!=', [ 'masonry', 'metro' ] ],
		];

		/**
		 * Custom aspect ratio (remove control from style tab)
		 *
		 * @since 1.9.7
		 */
		unset( $this->controls['_aspectRatio'] );
		$this->controls['_aspectRatio'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Aspect ratio', 'bricks' ),
			'type'     => 'text',
			'inline'   => true,
			'dd'       => false,
			'css'      => [
				[
					'selector' => '.image',
					'property' => 'aspect-ratio',
				],
			],
			'required' => [ 'imageRatio', '=', 'custom' ],
		];

		$this->controls['imageHeight'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Image height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property'  => 'height',
					'selector'  => '.image',
					'important' => true,
				],
			],
			'placeholder' => '',
			'required'    => [ 'layout', '!=', [ 'masonry', 'metro' ] ],
		];

		$this->controls['columns'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Columns', 'bricks' ),
			'type'        => 'number',
			'min'         => 2,
			'css'         => [
				[
					'property' => '--columns',
					'selector' => '',
				],
			],
			'rerender'    => true,
			'placeholder' => 3,
			'required'    => [ 'layout', '!=', [ 'metro' ] ],
		];

		$this->controls['gutter'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => '--gutter',
					'selector' => '',
				],
			],
			'placeholder' => 0,
		];

		$this->controls['link'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Link to', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'lightbox'   => esc_html__( 'Lightbox', 'bricks' ),
				'attachment' => esc_html__( 'Attachment Page', 'bricks' ),
				'media'      => esc_html__( 'Media File', 'bricks' ),
				'custom'     => esc_html__( 'Custom URL', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
		];

		$this->controls['lightboxImageSize'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Lightbox image size', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['imageSizes'],
			'placeholder' => esc_html__( 'Full', 'bricks' ),
			'required'    => [ 'link', '=', 'lightbox' ],
		];

		// @since 1.8.4
		$this->controls['lightboxAnimationType'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Lightbox animation type', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['lightboxAnimationTypes'],
			'placeholder' => esc_html__( 'Zoom', 'bricks' ),
			'required'    => [ 'link', '=', 'lightbox' ],
		];

		$this->controls['linkCustom'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Custom links', 'bricks' ),
			'type'        => 'repeater',
			'fields'      => [
				'link' => [
					'label'   => esc_html__( 'Link', 'bricks' ),
					'type'    => 'link',
					'exclude' => [
						'lightboxImage',
						'lightboxVideo',
					],
				],
			],
			'placeholder' => esc_html__( 'Custom link', 'bricks' ),
			'required'    => [ 'link', '=', 'custom' ],
		];

		$this->controls['caption'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Caption', 'bricks' ),
			'type'  => 'checkbox',
		];
	}

	public function get_normalized_image_settings( $settings ) {
		$items = $settings['items'] ?? [];
		$size  = $items['size'] ?? BRICKS_DEFAULT_IMAGE_SIZE;

		$items['images'] = [];

		$images = wp_list_pluck(get_field('game_screenshots', $this->post_id ), 'id');

		if ( is_array( $images ) ) {
			foreach ( $images as $image_id ) {
				$items['images'][] = [
					'id'   => $image_id,
					'full' => wp_get_attachment_image_url( $image_id, 'full' ),
					'url'  => wp_get_attachment_image_url( $image_id, $size )
				];
			}
		}


		// Old data structure (images were saved as one array directly on $items)
		if ( ! isset( $items['images'] ) ) {
			$images = ! empty( $items ) ? $items : [];

			unset( $items );

			$items['images'] = $images;
		}

		// Get 'size' from first image if not set
		$first_image_size = ! empty( $items['images'][0]['size'] ) ? $items['images'][0]['size'] : false;
		$size             = empty( $items['size'] ) && $first_image_size ? $first_image_size : $size;

		// Get image 'url' for requested $size
		foreach ( $items['images'] as $key => $image ) {
			if ( ! empty( $image['id'] ) ) {
				$items['images'][ $key ]['url'] = wp_get_attachment_image_url( $image['id'], $size );
			}
		}

		$settings['items']         = $items;
		$settings['items']['size'] = $size;

		return $settings;
	}

	public function render() {
		$settings = $this->get_normalized_image_settings( $this->settings );
		$images   = $settings['items']['images'] ?? false;
		$size     = $settings['items']['size'] ?? BRICKS_DEFAULT_IMAGE_SIZE;
		$layout   = $settings['layout'] ?? 'grid';
		$link_to  = $settings['link'] ?? false;
		$columns  = $settings['columns'] ?? 3;

		// STEP: Return placeholder
		if ( ! $images ) {
			if ( ! empty( $settings['items']['useDynamicData'] ) ) {
				if ( ! \Bricks\Helpers::is_bricks_template( $this->post_id ) ) {
					return $this->render_element_placeholder(
						[
							'title' => esc_html__( 'Dynamic data is empty.', 'bricks' )
						]
					);
				}
			} else {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'No image selected.', 'bricks' ),
					]
				);
			}
		}

		$root_classes = [ 'bricks-layout-wrapper' ];

		// Set isotopeJS CSS class
		if ( $layout === 'masonry' ) {
			$root_classes[] = 'isotope';
		}

		$this->set_attribute( '_root', 'class', $root_classes );
		$this->set_attribute( '_root', 'data-layout', $layout );

		// STEP: Render
		if ( $link_to === 'lightbox' ) {
			$this->set_attribute( '_root', 'class', 'bricks-lightbox' );

			if ( ! empty( $settings['lightboxAnimationType'] ) ) {
				$this->set_attribute( '_root', 'data-animation-type', esc_attr( $settings['lightboxAnimationType'] ) );
			}
		}

		echo "<ul {$this->render_attributes( '_root' )}>";

		foreach ( $images as $index => $item ) {
			$close_a_tag = false;
			$image_id    = $item['id'] ?? false;

			$image_classes = [ 'image' ];

			// Image lazy load
			if ( $this->lazy_load() ) {
				$image_classes[] = 'bricks-lazy-hidden';
				$image_classes[] = 'bricks-lazy-load-isotope';
			}

			if ( $layout !== 'masonry' ) {
				$image_classes[] = 'bricks-layout-inner';
			}

			// Grid: Image ratio
			if ( $layout === 'grid' && ! empty( $settings['imageRatio'] ) ) {
				$image_classes[] = "bricks-aspect-{$settings['imageRatio']}";
			}

			// CSS filters
			$image_classes[] = 'css-filter';

			// List item
			$this->set_attribute( "li-{$index}", 'class', 'bricks-layout-item' );

			// Add 'data-id' attribute to image <li> (helps to perform custom JS logic based on attachment ID)
			if ( $image_id ) {
				$this->set_attribute( "li-{$index}", 'data-id', $image_id );
			}

			echo "<li {$this->render_attributes( "li-{$index}" )}>";

			// Open 'figure' tag (@since 1.9.7)
			echo '<figure class="game-screenshot">';

			if ( $link_to === 'attachment' && $image_id ) {
				$close_a_tag = true;

				echo '<a href="' . get_permalink( $image_id ) . '" target="_blank">';
			}

			// Media file
			elseif ( $link_to === 'media' ) {
				$close_a_tag = true;

				echo '<a href="' . esc_url( $item['url'] ) . '" target="_blank">';
			}

			// Custom link
			elseif ( $link_to === 'custom' && isset( $settings['linkCustom'][ $index ]['link'] ) ) {
				$close_a_tag = true;

				$this->set_link_attributes( "a-$index", $settings['linkCustom'][ $index ]['link'] );

				echo "<a {$this->render_attributes( "a-$index" )}>";
			}

			// Lightbox attributes
			elseif ( $link_to === 'lightbox' ) {
				$close_a_tag         = true;
				$lightbox_image_size = $settings['lightboxImageSize'] ?? 'full';
				$lightbox_image      = $image_id ? wp_get_attachment_image_src( $image_id, $lightbox_image_size ) : false;
				$lightbox_image      = is_array( $lightbox_image ) && ! empty( $lightbox_image ) ? $lightbox_image : [ ! empty( $item['url'] ) ? $item['url'] : '', 800, 600 ];

				$this->set_attribute( "a-$index", 'href', $lightbox_image[0] );
				$this->set_attribute( "a-$index", 'data-pswp-src', $lightbox_image[0] );
				$this->set_attribute( "a-$index", 'data-pswp-width', $lightbox_image[1] );
				$this->set_attribute( "a-$index", 'data-pswp-height', $lightbox_image[2] );

				echo "<a {$this->render_attributes( "a-$index" )}>";
			}

			// STEP: Render image
			$image_atts = [ 'class' => implode( ' ', $image_classes ) ];

			echo wp_get_attachment_image( $image_id, $size, false, $image_atts );

			if ( $close_a_tag ) {
				echo '</a>';
			}

			// Image caption
			$caption = isset( $settings['caption'] ) && $image_id ? wp_get_attachment_caption( $image_id ) : false;

			if ( $caption ) {
				echo '<figcaption class="bricks-image-caption">' . $caption . '</figcaption>';
			}

			// Close 'figure' tag (@since 1.9.7)
			echo '</figure>';

			echo '</li>';
		}

		if ( $layout === 'masonry' ) {
			// Item sizer (Isotope requirement)
			echo '<li class="bricks-isotope-sizer"></li>';
			echo '<li class="bricks-gutter-sizer"></li>';
		}

		echo '</ul>';
	}

	public function convert_element_settings_to_block( $settings ) {
		$settings = $this->get_normalized_image_settings( $settings );

		$images     = ! empty( $settings['items']['images'] ) ? $settings['items']['images'] : false;
		$image_size = $settings['items']['size'];

		if ( ! $images ) {
			return;
		}

		$columns = isset( $settings['columns'] ) ? intval( $settings['columns'] ) : 3;

		if ( count( $images ) < $columns ) {
			$columns = count( $images );
		}

		$block = [
			'blockName'    => $this->block,
			'attrs'        => [
				'ids'      => [],
				'columns'  => $columns,
				'sizeSlug' => $image_size,
			],
			'innerContent' => [],
		];

		$image_gallery_html  = '<figure class="wp-block-gallery columns-' . esc_attr( $columns ) . ' is-cropped">';
		$image_gallery_html .= '<ul class="blocks-gallery-grid">';

		foreach ( $images as $image ) {
			$image_id = isset( $image['id'] ) && ! empty( $image['id'] ) ? intval( $image['id'] ) : false;

			if ( $image_id ) {
				$block['attrs']['ids'][] = $image_id;

				$image_url           = wp_get_attachment_image_url( $image_id, $image_size );
				$image_url_full_size = wp_get_attachment_image_url( $image_id, 'full' );

				$image_gallery_html .= '<li class="blocks-gallery-item">';
				$image_gallery_html .= '<figure>';

				// Retrieve the alt text for the image
				$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

				$image_gallery_html .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $alt_text ) . '" data-id="' . esc_attr( $image_id ) . '" data-full-url="' . esc_url( $image_url_full_size ) . '" data-link="' . esc_url( get_permalink( $image_id ) ) . '" class="wp-image-' . esc_attr( $image_id ) . '"/>';

				$image_gallery_html .= '</figure>';
				$image_gallery_html .= '</li>';
			}
		}

		$image_gallery_html .= '</ul></figure>';

		$block['innerContent'] = [ $image_gallery_html ];

		return $block;
	}

	public function convert_block_to_element_settings( $block, $attributes ) {
		$image_ids  = isset( $attributes['ids'] ) ? $attributes['ids'] : [];
		$image_size = isset( $attributes['sizeSlug'] ) ? $attributes['sizeSlug'] : 'large';
		$columns    = isset( $attributes['columns'] ) ? intval( $attributes['columns'] ) : 3;

		if ( ! count( $image_ids ) ) {
			return;
		}

		$element_settings = [
			'gutter'  => 15,
			'columns' => $columns,
		];

		if ( isset( $attributes['linkTo'] ) && in_array( $attributes['linkTo'], [ 'attachment', 'media' ] ) ) {
			$element_settings['link'] = $attributes['linkTo'];
		}

		$items = [];

		foreach ( $image_ids as $image_id ) {
			$image_url = wp_get_attachment_image_url( $image_id, $image_size );

			if ( $image_id && $image_url ) {
				$items['images'][] = [
					'full' => wp_get_attachment_image_url( $image_id, 'full' ),
					'id'   => $image_id,
					'size' => $image_size,
					'url'  => $image_url,
				];
			}
		}

		if ( ! empty( $items ) ) {
			$element_settings['items'] = $items;
		}

		return $element_settings;
	}
}
