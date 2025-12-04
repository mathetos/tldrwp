<?php
/**
 * TLDRWP AI Service Integration Class
 *
 * @package TLDRWP
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * TLDRWP AI Service Integration Class
 *
 * Handles AI API calls using WordPress AI Client and response formatting.
 */
class TLDRWP_AI_Service {

    /**
     * Plugin instance.
     *
     * @var TLDRWP
     */
    private $plugin;

    /**
     * Constructor.
     *
     * @param TLDRWP $plugin Plugin instance.
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;
    }

    /**
     * Check if WordPress AI plugin is active and has credentials configured.
     *
     * @return bool
     */
    public function check_ai_plugin() {
        // Check if WordPress AI plugin helper function exists
        if ( ! function_exists( 'WordPress\AI\has_ai_credentials' ) ) {
            return false;
        }
        
        // Use the WordPress AI plugin helper function
        return \WordPress\AI\has_ai_credentials();
    }

    /**
     * Test AI connection via AJAX.
     */
    public function test_ai_connection() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tldrwp_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed', 'tldrwp' ) );
        }

        if ( ! $this->check_ai_plugin() ) {
            wp_send_json_error( __( 'WordPress AI plugin is not active or AI credentials are not configured.', 'tldrwp' ) );
        }

        // Test with a simple prompt
        $test_prompt = 'Please respond with "Connection successful" if you can read this message.';
        $response = $this->call_ai_service( $test_prompt );

        if ( ! empty( $response ) && ! is_wp_error( $response ) ) {
            wp_send_json_success( __( 'Connection successful!', 'tldrwp' ) );
        } else {
            $error_message = is_wp_error( $response ) ? $response->get_error_message() : __( 'Connection failed. Please check your AI credentials configuration.', 'tldrwp' );
            wp_send_json_error( $error_message );
        }
    }

    /**
     * Call AI service with the given prompt.
     *
     * @param string $prompt The prompt to send to the AI service.
     * @return string|\WP_Error The AI response or a WP_Error on failure.
     */
    public function call_ai_service( $prompt ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( 'TLDRWP: Starting AI service call for user: ' . ( is_user_logged_in() ? 'logged-in' : 'non-logged-in' ) );
        }
        
        // Check if WordPress AI Client is available
        if ( ! class_exists( 'WordPress\AI_Client\AI_Client' ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( 'TLDRWP: WordPress AI Client class not available' );
            }
            return new \WP_Error( 'ai_client_unavailable', __( 'WordPress AI Client is not available.', 'tldrwp' ) );
        }

        // Check if credentials are configured
        if ( ! $this->check_ai_plugin() ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( 'TLDRWP: AI credentials not configured' );
            }
            return new \WP_Error( 'no_credentials', __( 'AI credentials are not configured. Please configure them in the WordPress AI plugin settings.', 'tldrwp' ) );
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( 'TLDRWP: Calling AI Client with prompt length: ' . strlen( $prompt ) );
        }

        try {
            // Use WordPress AI Client to generate text
            // AI Client automatically handles provider/model selection
            $text = \WordPress\AI_Client\AI_Client::prompt_with_wp_error( $prompt )
                ->using_temperature( 0.7 )
                ->generate_text();

            // Check if we got a WP_Error
            if ( is_wp_error( $text ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log( 'TLDRWP: AI Client returned WP_Error: ' . $text->get_error_message() );
                }
                return $text;
            }

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( 'TLDRWP: AI Client response received, length: ' . strlen( $text ) );
            }

            return $text;
            
        } catch ( \Throwable $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( 'TLDRWP: Exception in AI service call: ' . $e->getMessage() );
            }
            return new \WP_Error( 'ai_generation_failed', __( 'AI generation failed: ', 'tldrwp' ) . $e->getMessage() );
        }
    }

    /**
     * Format AI response for display.
     *
     * @param string|\WP_Error $raw_response Raw AI response or WP_Error.
     * @return string Formatted response.
     */
    public function format_ai_response( $raw_response ) {
        // Handle WP_Error
        if ( is_wp_error( $raw_response ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( 'TLDRWP: format_ai_response received WP_Error: ' . $raw_response->get_error_message() );
            }
            return '';
        }

        if ( empty( $raw_response ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( 'TLDRWP: Raw response is empty' );
            }
            return '';
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( 'TLDRWP: Formatting response: ' . substr( $raw_response, 0, 200 ) . '...' );
        }

        // Remove markdown code blocks
        $response = $this->remove_markdown_code_blocks( $raw_response );

        // Convert plain text to HTML
        $response = $this->convert_plain_text_to_html( $response );

        // Sanitize and format using WordPress functions
        $response = wp_kses_post( $response );
        $response = wpautop( $response );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( 'TLDRWP: Final formatted response: ' . substr( $response, 0, 200 ) . '...' );
        }

        return $response;
    }

    /**
     * Convert plain text to HTML or handle existing HTML.
     *
     * @param string $text Text that may be plain text or HTML.
     * @return string HTML formatted text.
     */
    private function convert_plain_text_to_html( $text ) {
        // Check if the text already contains HTML tags
        if ( preg_match( '/<[^>]+>/', $text ) ) {
            // Text already contains HTML, just clean it up
            return $text;
        }

        // Convert plain text bullet points to HTML lists
        $lines = explode( "\n", $text );
        $html_lines = array();
        $in_list = false;

        foreach ( $lines as $line ) {
            $line = trim( $line );
            
            if ( empty( $line ) ) {
                if ( $in_list ) {
                    $html_lines[] = '</ul>';
                    $in_list = false;
                }
                continue;
            }

            // Check for bullet points (various formats)
            if ( preg_match( '/^[\-\*•]\s+(.+)$/', $line, $matches ) ) {
                if ( ! $in_list ) {
                    $html_lines[] = '<ul>';
                    $in_list = true;
                }
                $html_lines[] = '<li>' . esc_html( $matches[1] ) . '</li>';
            } else {
                if ( $in_list ) {
                    $html_lines[] = '</ul>';
                    $in_list = false;
                }
                $html_lines[] = '<p>' . esc_html( $line ) . '</p>';
            }
        }

        if ( $in_list ) {
            $html_lines[] = '</ul>';
        }

        return implode( "\n", $html_lines );
    }

    /**
     * Remove markdown code blocks from text.
     *
     * @param string $text Text containing markdown code blocks.
     * @return string Text with code blocks removed.
     */
    private function remove_markdown_code_blocks( $text ) {
        // Extract content from ```code blocks``` (keep the content, remove the markers)
        $text = preg_replace( '/```(?:html)?\s*\n?(.*?)\n?```/s', '$1', $text );
        
        // Remove `inline code` markers but keep the content
        $text = preg_replace( '/`([^`]+)`/', '$1', $text );
        
        return $text;
    }
}
