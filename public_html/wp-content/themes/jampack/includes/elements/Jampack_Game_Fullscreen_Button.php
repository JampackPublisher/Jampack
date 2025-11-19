<?php

if ( ! defined( 'ABSPATH' ) ) exit; 

/**
 * Game Fullscreen Button element for Bricks Builder
 * 
 * Creates a button that launches fullscreen mode for game elements.
 * Leverages Bricks' built-in button styling and adds custom fullscreen functionality.
 * 
 * @extends \Bricks\Element
 */
class Jampack_Game_Fullscreen_Button extends \Bricks\Element {
	public $block    = 'core/button';
	public $category = 'jampack';
	public $name     = 'game-fullscreen-button';
	public $icon     = 'ti-fullscreen';
	public $tag      = 'span';

	/**
	 * Returns the display name for this element in Bricks Builder
	 * 
	 * @return string The localized element name
	 */
	public function get_label() {
		return esc_html__( 'Game Fullscreen Button', 'jampack' );
	}

	/**
	 * Defines the controls for The Fullscreen Button
	 
	 * @return void
	 */
	public function set_controls() {
		$this->controls['tag'] = [
			'label'          => esc_html__( 'HTML tag', 'bricks' ),
			'type'           => 'text',
			'hasDynamicData' => false,
			'inline'         => true,
			'placeholder'    => 'span',
			'required'       => [ 'link', '=', '' ],
		];

		$this->controls['size'] = [
			'label'       => esc_html__( 'Size', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['buttonSizes'],
			'inline'      => true,
			'reset'       => true,
			'placeholder' => esc_html__( 'Default', 'bricks' ),
		];

		$this->controls['style'] = [
			'label'       => esc_html__( 'Style', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['styles'],
			'inline'      => true,
			'reset'       => true,
			'default'     => 'primary',
			'placeholder' => esc_html__( 'None', 'bricks' ),
		];

		$this->controls['icon'] = [
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'icon',
		];

		// Custom control for fullscreen targeting
		$this->controls['targetSelector'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Target Selector', 'jampack' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'CSS selector (optional - auto-detects if empty)', 'jampack' ),
			'description' => esc_html__( 'CSS selector for the game element to make fullscreen. Leave empty for auto-detection.', 'jampack' ),
		];
	}

	/**
	 * Renders the HTML output for the frontend
	 * 
	 * Generates a button element with fullscreen click handler.
	 * Uses Bricks' built-in styling and adds custom onclick functionality.
	 * 
	 * @return void Echoes HTML directly
	 */
	public function render() {
		$settings = $this->settings;

		$this->set_attribute( '_root', 'class', 'bricks-button' );

		if ( ! empty( $settings['size'] ) ) {
			$this->set_attribute( '_root', 'class', $settings['size'] );
		}

		if ( ! empty( $settings['style'] ) ) {
            $this->set_attribute( '_root', 'class', "bricks-background-{$settings['style']}" );
		}

		if ( isset( $settings['block'] ) ) {
			$this->set_attribute( '_root', 'class', 'block' );
		}

		$text = 'Fullscreen';
		$target_selector = ! empty( $settings['targetSelector'] ) ? $settings['targetSelector'] : '';

		$on_click = "onClick='jampackFullscreen.launch(\"" . esc_attr( $target_selector ) . "\")'";

		$output = "<{$this->tag} {$this->render_attributes( '_root' )} ${on_click}>";

		// Set default fullscreen icon if none provided, always on the left
		$icon = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : self::render_icon(['library' => 'themify', 'icon' => 'ti-fullscreen']);
		if ( $icon ) {
			$output .= $icon;
		}

		// button text
        $output .= 'Fullscreen';

		$output .= "</{$this->tag}>";

		echo $output;
	}

	/**
	 * Provides template for Bricks Builder preview
	 * 
	 * This template shows how the element appears in the Bricks editor.
	 * Uses the same structure as the frontend render method.
	 * 
	 * @return void Outputs JavaScript template
	 */
	public static function render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-game-fullscreen-button">
			<component
				:is="settings.link ? 'a' : settings.tag ? settings.tag : 'span'"
				:class="[
					'bricks-button',
					settings.size ? settings.size : null,
					settings.style ? `bricks-background-${settings.style}` : null,
					settings.block ? 'block' : null,
					'icon-left'
				]">
				<icon-svg :iconSettings="settings?.icon || {library: 'themify', icon: 'ti-fullscreen'}"/>
				Fullscreen
			</component>
		</script>
		<?php
	}
}