=== FAQ Chatbot ===
Contributors: jampack
Tags: chatbot, faq, chat widget
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A floating chat widget that uses FAQ content with deterministic key-phrase matching.

== Description ==

FAQ Chatbot displays a floating chat widget on selected pages. The chatbot uses a JSON FAQ file as its knowledge source and performs deterministic matching first, with optional Claude fallback.

== Features ==

* JSON FAQ source (`data/faqs.json`)
* Deterministic matching pipeline (normalize, exact question match, multi-word phrase substring score)
* Configurable page targeting
* Floating chat widget UI
* AJAX-powered responses
* Optional Claude server-side fallback (disabled by default), gated by on-topic / question-shape checks and per-IP rate limits before any API call
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
    "phrases": ["multi word phrase one", "another user phrase"]
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
* Pre-Claude query guard (Jampack topic allowlist, question shape, cheap abuse heuristics) and per-IP transient rate limits with short backoff
* Stricter Claude system prompt, non-2xx handling, lower max tokens / temperature 0
* JSON-first deterministic FAQ chatbot implementation
* Multi-word `phrases` matching in `faqs.json` (legacy `keywords` still read as phrases)
* Default FAQ content for Jampack (overview, subscription, Play Pass, cancel, password, device, kids, support, Games for Love)
* Optional Claude fallback adapter (off by default)
* Admin settings for allowed pages, threshold, fallback toggle, API key, Claude per-IP limits, and optional extra topic phrases
