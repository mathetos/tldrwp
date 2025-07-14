# TLDRWP AI Block

This plugin provides a basic boilerplate for creating dynamic Gutenberg blocks that interact with the [AI Services](https://wordpress.org/plugins/ai-services/) plugin. A simple "AI Chat" block is included which displays an input field and sends the prompt to the server via a REST request. The response is generated through a filter so you can connect any AI provider supported by the AI Services plugin.

## Features

- Dynamic Gutenberg block registered via `register_block_type`.
- Front‑end interaction with a REST API endpoint for AI requests.
- Dependency on the **AI Services** plugin so site owners can choose their preferred AI backend.

## Usage

1. Install and activate the **AI Services** plugin.
2. Upload or clone this plugin into your WordPress `wp-content/plugins` directory and activate it.
3. Insert the **AI Chat** block into a post or page.
4. On the front end, enter a prompt and click **Ask AI** to see the response.

Developers can hook into the `tldrwp_generate_ai_response` filter to send the prompt to any supported AI service.
