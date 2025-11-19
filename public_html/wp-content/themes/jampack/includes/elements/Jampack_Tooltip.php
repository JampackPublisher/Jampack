<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Jampack_Tooltip extends \Bricks\Element {
	public $category = 'general';
	public $name     = 'tootlip';
	public $icon     = 'ti-info-alt';

	public function get_label() {
		return esc_html__( 'Tooltip', 'jampack' );
	}


	public function set_controls() {
		$this->controls['iconSeparator'] = [
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['icon'] = [
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'icon',
		];

		$this->controls['iconTypography'] = [
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.jampack-tooltip-icon i',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['contentSeparator'] = [
			'label' => esc_html__( 'Content', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['content'] = [
			'tab'     => 'content',
			'type'    => 'editor',
			'default' => '',
		];
	}


	public function render() {
		$settings = $this->settings;

		$this->set_attribute( '_root', 'class', 'jampack-tooltip-container' );

		$icon = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : false;
		$content = ! empty( $settings['content'] ) ? $settings['content'] : '';

		$output = "";

		if ($icon) {
			$output .= '<div ' . $this->render_attributes( '_root' ) . '>';
			$output .= '<div class="jampack-tooltip-icon">' . $icon . '</div>';
			$output .= '<div class="jampack-tooltip-text">' . $content . '</div>';
			$output .= '</div>';
		}

		echo $output;
	}

	public static function render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-tooltip">
			<component>
				<icon-svg v-if="(settings?.icon?.icon || settings?.icon?.svg)" :iconSettings="settings.icon"/>
			</component>
		</script>
		<?php
	}
}

add_action( 'wp_head', function() {
	echo '<style>
		.jampack-tooltip-container {
			position: relative;
			display: inline-block;
		}
		
		.jampack-tooltip-icon {
			cursor: pointer;
		}
		
		.jampack-tooltip-text {
			visibility: hidden;
			width: max-content;
			max-width: 280px;
			font-size: 12px;
			font-weight: 100;
			background-color: #f9f9f9;
			color: #000;
			text-align: left;
			border-radius: 6px;
			padding: 5px;
			position: absolute;
			z-index: 1;
			bottom: 125%;
			left: 50%;
			opacity: 0;
			transform: translateX(-50%);
			transition: opacity 0.3s;
		}
		
		.jampack-tooltip-text::after {
			content: "";
			position: absolute;
			top: 100%;
			left: 50%;
			margin-left: -5px;
			border-width: 5px;
			border-style: solid;
			border-color: #f9f9f9 transparent transparent transparent;
		}
		
		.jampack-tooltip-container:hover .jampack-tooltip-text {
			visibility: visible;
			opacity: 1;
		}
	</style>';
});
