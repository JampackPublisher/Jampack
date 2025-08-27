<?php

require_once get_stylesheet_directory() . '/memberpress/init.php';

function register_jampack_bricks_elements() {
	$element_files = [
		__DIR__ . '/includes/elements/Jampack_Game_Screenshots.php',
		__DIR__ . '/includes/elements/Jampack_Game_Favorite_Button.php',
		__DIR__ . '/includes/elements/Jampack_Game_Fullscreen_Button.php',
		__DIR__ . '/includes/elements/Jampack_Tooltip.php',
		__DIR__ . '/includes/elements/Jampack_Slider_Nested.php',
	];

	foreach ( $element_files as $file ) {
		\Bricks\Elements::register_element( $file );
	}
}

add_action( 'init', 'register_jampack_bricks_elements', 11 );

function prefix_disable_gutenberg( $current_status, $post_type ) {
	// Use your post type key instead of 'product'
	if ( $post_type === 'games' ) {
		return false;
	}

	return $current_status;
}

add_filter( 'use_block_editor_for_post_type', 'prefix_disable_gutenberg', 10, 2 );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! bricks_is_builder_main() ) {
		wp_enqueue_style( 'jampack-style', get_stylesheet_directory_uri() . '/assets/css/style.css', [ 'bricks-frontend' ], filemtime( get_stylesheet_directory() . '/assets/css/style.css' ) );
		wp_enqueue_script('bricks-splide-autoscroll', 'https://cdn.jsdelivr.net/npm/@splidejs/splide-extension-auto-scroll@0.5.3/dist/js/splide-extension-auto-scroll.min.js', ['bricks-splide'], '0.5.3');
		wp_enqueue_script( 'jampack-script', get_stylesheet_directory_uri() . '/assets/js/main.js', [ 'bricks-scripts', 'bricks-splide-autoscroll' ], filemtime( get_stylesheet_directory() . '/assets/js/main.js' ) );
		wp_localize_script( 'jampack-script', 'ajax_object', array(
			'ajaxurl'               => admin_url( 'admin-ajax.php' ),
			'game_fav_button_nonce' => wp_create_nonce( 'game_fav_button_nonce' )
		) );
	}
} );

function game_fav_button_handler() {
	check_ajax_referer( 'game_fav_button_nonce', 'nonce' );
	$action     = $_POST['button_action'];
	$user_id    = get_current_user_id();
	$game_id    = $_POST['game_id'];
	$meta_key   = 'game_favs';
	$meta_value = get_user_meta( $user_id, $meta_key, true );
	if ( empty( $meta_value ) ) {
		$meta_value = [];
	}

	if ( ! in_array( $action, [ 'add', 'remove' ] ) ) {
		wp_send_json_error( 'Undefined action', 400 );
	}

	if ( ! get_post( $game_id ) ) {
		wp_send_json_error( 'Game cannot be found', 400 );
	}

	if ( $action == 'add' ) {
		$meta_value[] = $game_id;
		update_user_meta( $user_id, $meta_key, $meta_value );
		wp_send_json_success( array( 'text' => 'Remove from favs', 'action' => 'remove' ), 200 );
	} else {
		update_user_meta( $user_id, $meta_key, array_diff( $meta_value, [ $game_id ] ) );
		wp_send_json_success( array( 'text' => 'Add to favs', 'action' => 'add' ), 200 );
	}
}

add_action( 'wp_ajax_game_fav_button', 'game_fav_button_handler' );

function user_has_favorites() {
	if ( ! $user = get_current_user_id() ) {
		return 'false';
	}

	if ( count( get_user_meta( get_current_user_id(), 'game_favs', true ) ) > 0 ) {
		return 'true';
	}

	return 'false';
}

function is_game_favorited( $game_id ) {
	if ( ! $game_id ) {
		return 'false';
	}

	if ( in_array( $game_id, get_user_meta( get_current_user_id(), 'game_favs', true ) ) ) {
		return 'true';
	}

	return 'false';
}

function current_query_index() {
	$index = \Bricks\Query::get_loop_index();
	return $index + 1;
}

function get_current_user_subscription_ids() {
	$subscription_ids = false;
	if ($user = wp_get_current_user()) {
		$member  = new MeprUser( $user->ID );
		$subscription_ids = $member->active_product_subscriptions() ?: false;
	}
	return $subscription_ids;
}

add_filter( 'bricks/code/echo_function_names', function () {
	return [
		'is_game_favorited',
		'user_has_favorites',
		'current_query_index',
		'get_current_user_subscription_ids'
	];
} );

function change_default_query_order( WP_Query $query ) {
	if ( ! is_admin() ) {
		if ( is_front_page() ) {
			$meta_key = 'rmp_avg_rating';
			$query->set( 'meta_query', array(
				'relation' => 'OR',
				array(
					'key'     => $meta_key,
					'compare' => 'EXISTS'
				),
				array(
					'key'     => $meta_key,
					'compare' => 'NOT EXISTS'
				)
			) );
			$query->set( 'orderby', array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC'
			) );
		}
	}
}

add_action( 'pre_get_posts', 'change_default_query_order', 10 );


function early_access_query_filter( $query_vars ) {
	if ( ! is_admin() ) {
		if ( ! is_user_logged_in() || ( is_user_logged_in() && in_array( 'subscriber', wp_get_current_user()->roles ) ) ) {
			$post_type = $query_vars['post_type'];
			if ( $post_type && ! is_array( $post_type ) ) {
				$post_type = [ $post_type ];
			}
			if ( in_array( "games", $post_type ) ) {
				$user_id = get_current_user_id();
				$member  = new MeprUser( $user_id );
				if ( empty( array_intersect( [ 1270, 1271 ], $member->active_product_subscriptions() ) ) ) {
					$query_vars['date_query'] = array(
						array(
							'column' => 'post_date',
							'before' => date( 'Y-m-d', strtotime( '-1 month' ) ),
						),
					);
				}
			}
		}
	}
	return $query_vars;
}
add_filter( 'bricks/posts/query_vars', 'early_access_query_filter', 10, 1 );


function add_games_manifest() {
	$post_id = get_the_ID();
	if($post_id && get_post_type($post_id) == 'games') {
		$manifest_obj = [
			"name"  => get_the_title(),
			"short_name" => get_the_title(),
			"start_url" => get_the_permalink(),
		];
		if ($thumb_id = get_field('game_logo', $post_id)) {
			$mime_type = get_post_mime_type($thumb_id);
			$icons = [];
			if (strpos($mime_type, 'svg') !== false) {
				$icon = [
					"src"   => wp_get_attachment_image_src( $thumb_id)[0],
					"sizes" => "any",
					"type"  => "image/svg+xml"
				];
				$icons[] = $icon;

			} else {
				foreach ([64, 192, 512] as $size) {
					$icon = [
						"src"   =>  bis_get_attachment_image_src( $thumb_id, array($size, $size), true)["src"],
						"sizes" => $size . "x" . $size,
						"type"  => $mime_type
					];
					$icons[] = $icon;
				}
			}
			$manifest_obj["icons"] = $icons;
		}
		$manifest_url = home_url() . '/manifest.php?data=' . json_encode($manifest_obj);
		echo "<link rel='manifest' href='$manifest_url'>";
	};
}
add_action('wp_head', 'add_games_manifest');

function filter_menu_by_membership( $items ) {
	if (!is_admin() && !current_user_can('administrator')) {
		$page_to_remove = [1285, 1248];
		$menu_to_remove = [];
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$member  = new MeprUser( $user_id );
			if ( in_array( 1271, $member->active_product_subscriptions() ) ) {
				$page_to_remove = [ 1248 ];
			} else if ( in_array( 1270, $member->active_product_subscriptions() ) ) {
				$page_to_remove = [ 1285 ];
			}
		} else {
			$page_to_remove[] = 1672;
			$menu_to_remove = [66];
		}

		foreach ( $items as $key => $item ) {
			if ( $item->object == 'page' && in_array( $item->object_id, $page_to_remove ) ) {
				unset( $items[ $key ] );
			}
			if ( in_array( $item->object_id, $menu_to_remove ) ) {
				unset ( $items[ $key ] );
			}
		}
	}

	return $items;
}

add_filter( 'wp_get_nav_menu_items', 'filter_menu_by_membership', 10, 1 );

