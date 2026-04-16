<?php
/**
 * Substrings that trigger the abuse / injection gate (case-insensitive, against compact lower text).
 *
 * @package FAQ_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'ignore previous',
	'ignore all previous',
	'disregard',
	'system prompt',
	'developer message',
	'you are now',
	'you\'re now',
	'new instructions',
	'override',
	'jailbreak',
	'dan mode',
	'```',
	'<|',
	'|>',
	'</script>',
	'<script',
	'base64,',
);
