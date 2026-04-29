<?php
/**
 * Chat widget template.
 *
 * @package FAQ_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="faq-chatbot-widget" class="faq-chatbot-widget">
	<button id="faq-chatbot-button" class="faq-chatbot-button" aria-label="<?php esc_attr_e( 'Open chat', 'faq-chatbot' ); ?>">
		<svg class="faq-chatbot-button-icon" viewBox="1.75 0 13.75 19.25" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" preserveAspectRatio="xMidYMid meet">
			<!-- Help-bot: antenna, head, face, base -->
			<path d="M9 4.25V2.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
			<circle cx="9" cy="2" r="1" fill="currentColor"/>
			<rect x="3.25" y="5.25" width="11.5" height="12.5" rx="3" stroke="currentColor" stroke-width="1.7" fill="none"/>
			<circle cx="6.75" cy="10.5" r="1.15" fill="currentColor"/>
			<circle cx="11.25" cy="10.5" r="1.15" fill="currentColor"/>
			<path d="M6.75 14.25c0.85 0.65 1.65 0.95 2.25 0.95s1.4-0.3 2.25-0.95" stroke="currentColor" stroke-width="1.45" stroke-linecap="round" fill="none"/>
			<path d="M7.5 17.75h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
		</svg>
	</button>

	<div id="faq-chatbot-window" class="faq-chatbot-window" style="display: none;">
		<div class="faq-chatbot-header">
			<h3 class="faq-chatbot-title"><?php esc_html_e( 'FAQ Chat', 'faq-chatbot' ); ?></h3>
			<button id="faq-chatbot-close" class="faq-chatbot-close" aria-label="<?php esc_attr_e( 'Close chat', 'faq-chatbot' ); ?>">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</button>
		</div>

		<div id="faq-chatbot-messages" class="faq-chatbot-messages">
			<div class="faq-chatbot-message faq-chatbot-message-bot">
				<div class="faq-chatbot-message-content">
					<?php esc_html_e( 'Hello! How can I help you today?', 'faq-chatbot' ); ?>
				</div>
			</div>
		</div>

		<div class="faq-chatbot-input-container">
			<form id="faq-chatbot-form" class="faq-chatbot-form">
				<input
					type="text"
					id="faq-chatbot-input"
					class="faq-chatbot-input"
					placeholder="<?php esc_attr_e( 'Type your question...', 'faq-chatbot' ); ?>"
					autocomplete="off"
					maxlength="500"
				/>
				<button type="submit" id="faq-chatbot-send" class="faq-chatbot-send" aria-label="<?php esc_attr_e( 'Send message', 'faq-chatbot' ); ?>">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M18 2L9 11M18 2L12 18L9 11M18 2L2 8L9 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</form>
			<div id="faq-chatbot-loading" class="faq-chatbot-loading" style="display: none;">
				<div class="faq-chatbot-spinner"></div>
			</div>
		</div>
	</div>
</div>
