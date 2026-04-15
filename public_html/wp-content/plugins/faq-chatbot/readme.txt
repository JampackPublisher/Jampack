=== FAQ Chatbot ===
Contributors: jampack
Tags: chatbot, faq, chat widget
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A floating chat widget that uses FAQ content with deterministic keyword-based matching.

== Description ==

FAQ Chatbot displays a floating chat widget on selected pages. The chatbot uses a JSON FAQ file as its knowledge source and performs deterministic matching first, with optional Claude fallback.

== Features ==

* JSON FAQ source (`data/faqs.json`)
* Deterministic matching pipeline (normalize, tokenize, exact match, weighted keyword score)
* Configurable page targeting
* Floating chat widget UI
* AJAX-powered responses
* Optional Claude server-side fallback (disabled by default)
* Nonce validation, sanitization, and scoped asset loading

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/faq-chatbot` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > FAQ Chatbot to configure allowed pages and threshold
4. Edit `wp-content/plugins/faq-chatbot/data/faqs.json` with entries in this format:

```
[
  {
    "id": "faq_unique_id",
    "question": "Question text",
    "answer": "Answer text",
    "keywords": ["keyword one", "keyword two"]
  }
]
```

5. (Optional) Enable Claude fallback in settings and provide an API key

== Security ==

* AJAX requests are protected with nonces.
* User input is sanitized server-side.
* Responses are escaped before output.
* Scripts/styles are only enqueued on allowed pages.

== Changelog ==

= 1.0.0 =
* JSON-first deterministic FAQ chatbot implementation
* Optional Claude fallback adapter (off by default)
* Admin settings for allowed pages, threshold, fallback toggle, and API key
