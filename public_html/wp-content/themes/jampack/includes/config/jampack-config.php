<?php

/** The name of the database for Jampack */
define('JPCK_DB_NAME', 'jampack_db');
/** MySQL database username */
define('JPCK_DB_USER', 'jampack_user');
/** MySQL database password */
define('JPCK_DB_PASSWORD', 'jampack_pass');
/** MySQL hostname */
define('JPCK_DB_HOST', 'db:3306');

/** Games */
define('JPCK_CHILDREN_RESTRICTED_GENRES', ['fighting', 'horror', 'first-person-shooter-fps', 'survival', 'shooter', 'bullet-hell']);

/** Menus */
define('JPCK_USER_MENU_NAME', 'User Menu');
define('JPCK_USER_MENU_ID', 41);
define('JPCK_GAMESUBMISSION_LINK', '<li class="menu-item menu-item-type-custom menu-item-object-custom bricks-menu-item"><a href="/game-submission">Game Submission</a></li>');

/**
 * WordPress page ID for the Play Pass hub (canonical: https://jampack.org/play-pass/).
 * Same idea as tier rows in jampack_subscription_tier_landing_map() (page IDs, not paths).
 * Set to 0 in repo; override in wp-config.php on each environment with the real page ID, or leave 0 to resolve by slug {@see jampack_playpass_default_landing_url()}.
 */
if ( ! defined( 'JPCK_PLAYPASS_HUB_PAGE_ID' ) ) {
	define( 'JPCK_PLAYPASS_HUB_PAGE_ID', 0 );
}