<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Jampack_Game_Favorite_Button extends \Bricks\Element {
	public $block    = 'core/button';
	public $category = 'basic';
	public $name     = 'game-fav-button';
	public $icon     = 'ti-control-stop';
	public $tag      = 'span';

	public function get_label() {
		return esc_html__( 'Game Favorite Button', 'jampack' );
	}

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

		$this->controls['iconTypography'] = [
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'i',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconPosition'] = [
			'label'       => esc_html__( 'Position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'bricks' ),
			'required'    => [ 'icon', '!=', '' ],
		];

		$this->controls['iconGap'] = [
			'label'    => esc_html__( 'Gap', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'gap',
				],
			],
			'required' => [ 'icon', '!=', '' ],
		];

		$this->controls['iconSpace'] = [
			'label'    => esc_html__( 'Space between', 'bricks' ),
			'type'     => 'checkbox',
			'css'      => [
				[
					'property' => 'justify-content',
					'value'    => 'space-between',
				],
			],
			'required' => [ 'icon', '!=', '' ],
		];
	}

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

        if ($user = get_current_user_id()) {
            $favs = get_user_meta( $user, 'game_favs', true ) ?: [];
	        if ( in_array( $this->post_id, $favs ) ) {
		        $text   = 'Remove from favs';
		        $action = 'remove';
	        } else {
		        $text   = 'Add to favs';
		        $action = 'add';
	        }
        } else {
	        $text   = 'Add to favs';
	        $action = 'none';
        }

        if ($action != 'none') {
	        $on_click = "onClick='game_fav_button_handler(this, {$this->post_id})' data-action='{$action}'";
        } else {
            $on_click = "";
            $settings['link']['type'] = 'external';
            $settings['link']['url'] = wp_login_url(get_permalink($this->post_id));
	        $this->set_link_attributes( '_root', $settings['link'] );
        }
		$output = "<{$this->tag} {$this->render_attributes( '_root' )} ${on_click}>";

		$icon          = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : false;
		$icon_position = ! empty( $settings['iconPosition'] ) ? $settings['iconPosition'] : 'right';

		if ( $icon && $icon_position === 'left' ) {
			$output .= $icon;
		}

        $output .= $text;

		if ( $icon && $icon_position === 'right' ) {
			$output .= $icon;
		}

		$output .= "</{$this->tag}>";

		echo $output;
	}

	public static function render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-button">
			<component
				:is="settings.link ? 'a' : settings.tag ? settings.tag : 'span'"
				:class="[
					'bricks-button',
					settings.size ? settings.size : null,
					settings.style ? `bricks-background-${settings.style}` : null,
					settings.block ? 'block' : null,
					settings.icon && settings.iconPosition ? `icon-${settings.iconPosition}` : null,
					settings.icon && !settings.iconPosition ? 'icon-right' : null
				]">
				<icon-svg v-if="settings.iconPosition === 'left' && (settings?.icon?.icon || settings?.icon?.svg)" :iconSettings="settings.icon"/>
				Add to fav
				<icon-svg v-if="settings.iconPosition !== 'left' && (settings?.icon?.icon || settings?.icon?.svg)" :iconSettings="settings.icon"/>
			</component>
		</script>
		<?php
	}
}
