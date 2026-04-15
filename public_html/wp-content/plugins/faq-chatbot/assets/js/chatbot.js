/**
 * FAQ Chatbot Frontend JavaScript
 */
(function() {
	'use strict';
	
	// Wait for DOM to be ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
	
	function init() {
		// Check if required elements exist
		if (typeof faqChatbot === 'undefined') {
			console.error('FAQ Chatbot: Configuration not found');
			return;
		}
		
		const button = document.getElementById('faq-chatbot-button');
		const window = document.getElementById('faq-chatbot-window');
		const closeButton = document.getElementById('faq-chatbot-close');
		const form = document.getElementById('faq-chatbot-form');
		const input = document.getElementById('faq-chatbot-input');
		const messagesContainer = document.getElementById('faq-chatbot-messages');
		const loadingIndicator = document.getElementById('faq-chatbot-loading');
		
		if (!button || !window || !closeButton || !form || !input || !messagesContainer) {
			console.error('FAQ Chatbot: Required elements not found');
			return;
		}
		
		// Toggle chat window
		button.addEventListener('click', function() {
			toggleChatWindow(true);
		});
		
		closeButton.addEventListener('click', function() {
			toggleChatWindow(false);
		});
		
		// Handle form submission
		form.addEventListener('submit', function(e) {
			e.preventDefault();
			handleSubmit();
		});
		
		// Handle Enter key in input
		input.addEventListener('keydown', function(e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				handleSubmit();
			}
		});
		
		/**
		 * Toggle chat window visibility
		 */
		function toggleChatWindow(show) {
			if (show) {
				window.style.display = 'flex';
				button.style.display = 'none';
				input.focus();
			} else {
				window.style.display = 'none';
				button.style.display = 'block';
			}
		}
		
		/**
		 * Handle form submission
		 */
		function handleSubmit() {
			const query = input.value.trim();
			
			if (!query) {
				return;
			}
			
			// Add user message
			addMessage(query, 'user');
			
			// Clear input
			input.value = '';
			
			// Show loading indicator
			showLoading(true);
			
			// Disable form
			input.disabled = true;
			form.querySelector('button[type="submit"]').disabled = true;
			
			// Send AJAX request
			const formData = new FormData();
			formData.append('action', 'faq_chatbot_query');
			formData.append('nonce', faqChatbot.nonce);
			formData.append('query', query);
			
			fetch(faqChatbot.ajaxUrl, {
				method: 'POST',
				body: formData
			})
			.then(function(response) {
				return response.json();
			})
			.then(function(data) {
				showLoading(false);
				
				// Re-enable form
				input.disabled = false;
				form.querySelector('button[type="submit"]').disabled = false;
				
				if (data.success && data.data) {
					// Add bot response
					addMessage(data.data.answer, 'bot', data.data.fallback);
				} else {
					// Show error message
					const errorMsg = data.data && data.data.message
						? data.data.message 
						: (faqChatbot.errorMessage || 'An error occurred. Please try again.');
					addMessage(errorMsg, 'bot', true);
				}
				
				// Focus input
				input.focus();
			})
			.catch(function(error) {
				console.error('FAQ Chatbot Error:', error);
				showLoading(false);
				
				// Re-enable form
				input.disabled = false;
				form.querySelector('button[type="submit"]').disabled = false;
				
				// Show error message
				addMessage(faqChatbot.errorMessage || 'An error occurred. Please try again.', 'bot', true);
				
				// Focus input
				input.focus();
			});
		}
		
		/**
		 * Add message to chat
		 */
		function addMessage(text, type, isFallback) {
			const messageDiv = document.createElement('div');
			messageDiv.className = 'faq-chatbot-message faq-chatbot-message-' + type;
			
			const contentDiv = document.createElement('div');
			contentDiv.className = 'faq-chatbot-message-content';
			
			if (type === 'bot' && isFallback) {
				contentDiv.classList.add('faq-chatbot-message-fallback');
			}
			
			// Render as plain text for safe output.
			contentDiv.textContent = text;
			
			messageDiv.appendChild(contentDiv);
			messagesContainer.appendChild(messageDiv);
			
			// Scroll to bottom
			scrollToBottom();
		}
		
		/**
		 * Show/hide loading indicator
		 */
		function showLoading(show) {
			if (loadingIndicator) {
				loadingIndicator.style.display = show ? 'block' : 'none';
			}
		}
		
		/**
		 * Scroll messages container to bottom
		 */
		function scrollToBottom() {
			messagesContainer.scrollTop = messagesContainer.scrollHeight;
		}
	}
})();
