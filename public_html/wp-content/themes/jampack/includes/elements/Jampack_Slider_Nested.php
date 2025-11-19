<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Jampack_Slider_Nested extends \Bricks\Element_Slider_Nested {
	public function set_controls() {
		parent::set_controls();

		$keys = array_keys($this->controls);
		$index = array_search('height', $keys) + 1;

		$start_array = array_slice($this->controls, 0, $index, true);
		$end_array = array_slice($this->controls, $index, null, true);

		$extended_control = array(
			'padding' => [
				'group'       => 'track',
				'label'       => esc_html__( 'Padding', 'bricks' ),
				'type'        => 'spacing',
				'breakpoints' => true,
				'css'         => [
					[
						'property' => 'padding',
						'selector' => '.splide__track',
					],
				],
			],
			'border' => [
				'group'       => 'track',
				'label'       => esc_html__( 'Border', 'bricks' ),
				'type'        => 'border',
				'breakpoints' => true,
				'css'         => [
					[
						'property' => 'border',
						'selector' => '.splide__track',
					],
				],
			]
		);

		$this->controls = $start_array + $extended_control + $end_array;
	}

	public function set_control_groups() {
		parent::set_control_groups();
		$this->control_groups['track'] = [
			'title' => esc_html__( 'Track', 'jampack' ),
		];
	}
}