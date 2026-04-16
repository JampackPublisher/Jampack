<?php
/**
 * Approved FAQ context for Claude fallback.
 *
 * This file is separate from scratch/faq.txt. It contains curated, high-confidence
 * context that Claude may use when deterministic matching misses.
 *
 * @package FAQ_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	array(
		'question' => 'What is Jampack?',
		'answer'   => 'Jampack is an online gaming platform built by Games for Love. It offers browser-based games across common devices.',
	),
	array(
		'question' => 'How does my subscription help?',
		'answer'   => 'Subscriptions support Games for Love programs for children in hospitals and underserved communities.',
	),
	array(
		'question' => 'Can I cancel anytime?',
		'answer'   => 'Users can generally cancel from account or billing settings and keep access through the paid billing period.',
	),
	array(
		'question' => 'Do you offer free trials?',
		'answer'   => 'For free trial details, direct users to info@gamesforlove.org for official support and the most current information.',
	),
	array(
		'question' => 'Can I request a refund?',
		'answer'   => 'For refund requests and eligibility, direct users to info@gamesforlove.org for official assistance.',
	),
	array(
		'question' => 'Can I gift a membership or subscription?',
		'answer'   => 'For gifting questions and options, direct users to info@gamesforlove.org for official assistance.',
	),
	array(
		'question' => 'Do you have multiplayer or social games?',
		'answer'   => 'Multiplayer and social games are not currently part of the catalog, but these features are planned for the future.',
	),
	array(
		'question' => 'Are there regional restrictions?',
		'answer'   => 'There are currently no regional restrictions described in approved support guidance.',
	),
	array(
		'question' => 'Is Jampack suitable for kids?',
		'answer'   => 'Jampack curates age-appropriate and family-friendly experiences. Users should still check each game rating to confirm suitability for their child.',
	),
	array(
		'question' => 'What membership perks are available?',
		'answer'   => 'Membership perks vary by tier and can include Priority Support, Exclusive Community Badge and Chatroom access, Player+ Spotlight, access to source and soundtracks/assets, behind-the-scenes content, interactive developer sessions, and one game design submission per month with potential designer credit.',
	),
	array(
		'question' => 'How can I compare tier benefits?',
		'answer'   => 'Users should visit the Jampack homepage and review the tier sections to compare the latest benefits and choose the best option.',
	),
	array(
		'question' => 'I am having billing issues. Who can help?',
		'answer'   => 'For billing help, direct users to info@gamesforlove.org or the Games for Love website contact form.',
	),
	array(
		'question' => 'Who do I contact for support?',
		'answer'   => 'Support is available at Info@gamesforlove.org and via the Games for Love website contact form.',
	),
	array(
		'question' => 'What is Games for Love?',
		'answer'   => 'Games for Love is the nonprofit organization behind Jampack.',
	),
	array(
		'question' => 'Who founded Games for Love?',
		'answer'   => 'Nathan "Jetti" Blair is the founder, CEO, and Chairman of the Board of Games For Love',
	),
);
