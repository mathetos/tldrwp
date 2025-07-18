<?php
/**
 * TLDRWP Settings Management Class
 *
 * @package TLDRWP
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * TLDRWP Settings Management Class
 *
 * Handles all settings registration, management, and sanitization.
 */
class TLDRWP_Settings {

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
        $this->init();
    }

    /**
     * Initialize settings.
     */
    private function init() {
        add_action( 'admin_init', array( $this, 'init_settings' ) );
    }

    /**
     * Get default settings for TLDRWP.
     *
     * @return array
     */
    public function get_default_settings() {
        return array(
            'enabled_post_types' => array( 'post' ),
            'default_prompt'     => 'Please provide a concise TL;DR summary of this article with a call-to-action at the end.',
            'button_title'       => 'Short on time?',
            'button_description' => 'Click here to generate a TL;DR of this article',
            'success_message'    => 'Enjoy reading!',
            'enable_social_sharing' => true,
            'selected_ai_platform' => '',
            'selected_ai_model' => '',
            'rate_limit_requests' => 10,
            'rate_limit_window' => 3600 // 1 hour in seconds
        );
    }

    /**
     * Get current TLDRWP settings.
     *
     * @return array
     */
    public function get_settings() {
        $defaults = $this->get_default_settings();
        $settings = get_option( 'tldrwp_settings', array() );
        
        return wp_parse_args( $settings, $defaults );
    }

    /**
     * Refresh settings from database.
     */
    public function refresh_settings() {
        $this->plugin->settings = $this->get_settings();
    }

    /**
     * Initialize settings page.
     */
    public function init_settings() {
        // Add settings section to Reading page
        add_settings_section(
            'tldrwp_settings_section',
            __( 'TL;DR Settings', 'tldrwp' ),
            array( $this, 'settings_section_callback' ),
            'reading'
        );

        // Register settings
        register_setting( 'reading', 'tldrwp_settings', array(
            'sanitize_callback' => array( $this, 'sanitize_settings' ),
            'default' => $this->get_default_settings()
        ) );

        // Add settings fields
        add_settings_field(
            'tldrwp_enabled_post_types',
            __( 'Enable on Post Types', 'tldrwp' ),
            array( $this, 'enabled_post_types_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_default_prompt',
            __( 'Default Prompt', 'tldrwp' ),
            array( $this, 'default_prompt_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_button_text',
            __( 'Button Text', 'tldrwp' ),
            array( $this, 'button_text_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_success_message',
            __( 'Success Message', 'tldrwp' ),
            array( $this, 'success_message_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_enable_social_sharing',
            __( 'Social Sharing', 'tldrwp' ),
            array( $this, 'enable_social_sharing_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_ai_platform',
            __( 'AI Platform', 'tldrwp' ),
            array( $this, 'ai_platform_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_ai_model',
            __( 'AI Model', 'tldrwp' ),
            array( $this, 'ai_model_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_test_connection',
            __( 'Test Connection', 'tldrwp' ),
            array( $this, 'test_connection_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_rate_limiting',
            __( 'Rate Limiting', 'tldrwp' ),
            array( $this, 'rate_limiting_callback' ),
            'reading',
            'tldrwp_settings_section'
        );
    }

    /**
     * Settings section callback.
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure TL;DR settings for your content. The TL;DR button will appear on enabled post types.', 'tldrwp' ) . '</p>';
    }

    /**
     * Enabled post types callback.
     */
    public function enabled_post_types_callback() {
        $this->refresh_settings();
        $enabled_post_types = $this->plugin->settings['enabled_post_types'];
        
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        
        foreach ( $post_types as $post_type ) {
            $checked = in_array( $post_type->name, $enabled_post_types ) ? 'checked' : '';
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="tldrwp_settings[enabled_post_types][]" value="' . esc_attr( $post_type->name ) . '" ' . $checked . '> ';
            echo esc_html( $post_type->labels->name );
            echo '</label>';
        }
    }

    /**
     * Default prompt callback.
     */
    public function default_prompt_callback() {
        $this->refresh_settings();
        echo '<textarea name="tldrwp_settings[default_prompt]" rows="3" cols="50" style="width: 100%;">' . esc_textarea( $this->plugin->settings['default_prompt'] ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'Default prompt used for TL;DR generation. You can customize this per post using the post meta field.', 'tldrwp' ) . '</p>';
    }

    /**
     * Button text callback.
     */
    public function button_text_callback() {
        $this->refresh_settings();
        echo '<p><label>' . esc_html__( 'Button Title:', 'tldrwp' ) . '</label><br>';
        echo '<input type="text" name="tldrwp_settings[button_title]" value="' . esc_attr( $this->plugin->settings['button_title'] ) . '" style="width: 100%;">';
        echo '</p>';
        echo '<p><label>' . esc_html__( 'Button Description:', 'tldrwp' ) . '</label><br>';
        echo '<input type="text" name="tldrwp_settings[button_description]" value="' . esc_attr( $this->plugin->settings['button_description'] ) . '" style="width: 100%;">';
        echo '</p>';
    }

    /**
     * Success message callback.
     */
    public function success_message_callback() {
        $this->refresh_settings();
        echo '<input type="text" name="tldrwp_settings[success_message]" value="' . esc_attr( $this->plugin->settings['success_message'] ) . '" style="width: 100%;">';
        echo '<p class="description">' . esc_html__( 'Message displayed on the button after TL;DR generation is complete.', 'tldrwp' ) . '</p>';
    }

    /**
     * Enable social sharing callback.
     */
    public function enable_social_sharing_callback() {
        $this->refresh_settings();
        $checked = $this->plugin->settings['enable_social_sharing'] ? 'checked' : '';
        echo '<label>';
        echo '<input type="checkbox" name="tldrwp_settings[enable_social_sharing]" value="1" ' . $checked . '> ';
        echo esc_html__( 'Enable social sharing buttons on TL;DR summaries', 'tldrwp' );
        echo '</label>';
    }

    /**
     * AI platform callback.
     */
    public function ai_platform_callback() {
        $this->refresh_settings();
        $available_platforms = $this->plugin->get_available_ai_platforms();
        
        if ( empty( $available_platforms ) ) {
            echo '<p class="description">' . esc_html__( 'No AI platforms available. Please configure API keys in the AI Services plugin settings.', 'tldrwp' ) . '</p>';
            return;
        }
        
        echo '<select name="tldrwp_settings[selected_ai_platform]" id="tldrwp_ai_platform">';
        echo '<option value="">' . esc_html__( 'Select AI Platform', 'tldrwp' ) . '</option>';
        
        foreach ( $available_platforms as $slug => $name ) {
            $selected = ( $this->plugin->settings['selected_ai_platform'] === $slug ) ? 'selected' : '';
            echo '<option value="' . esc_attr( $slug ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Select which AI platform to use for TL;DR generation.', 'tldrwp' ) . '</p>';
    }

    /**
     * AI model callback.
     */
    public function ai_model_callback() {
        $this->refresh_settings();
        $selected_platform = $this->plugin->get_selected_ai_platform();
        
        if ( empty( $selected_platform ) ) {
            echo '<p class="description">' . esc_html__( 'Please select an AI platform first.', 'tldrwp' ) . '</p>';
            return;
        }
        
        $available_models = $this->plugin->get_available_ai_models( $selected_platform );
        
        if ( empty( $available_models ) ) {
            echo '<p class="description">' . esc_html__( 'No models available for the selected platform.', 'tldrwp' ) . '</p>';
            return;
        }
        
        echo '<select name="tldrwp_settings[selected_ai_model]" id="tldrwp_ai_model">';
        echo '<option value="">' . esc_html__( 'Select AI Model', 'tldrwp' ) . '</option>';
        
        foreach ( $available_models as $model_slug => $model_data ) {
            $selected = ( $this->plugin->settings['selected_ai_model'] === $model_slug ) ? 'selected' : '';
            $model_name = isset( $model_data['name'] ) ? $model_data['name'] : $model_slug;
            echo '<option value="' . esc_attr( $model_slug ) . '" ' . $selected . '>' . esc_html( $model_name ) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Select which AI model to use for TL;DR generation.', 'tldrwp' ) . '</p>';
    }

    /**
     * Test connection callback.
     */
    public function test_connection_callback() {
        $selected_platform = $this->plugin->get_selected_ai_platform();
        
        if ( empty( $selected_platform ) ) {
            echo '<p class="description">' . esc_html__( 'Please select an AI platform first.', 'tldrwp' ) . '</p>';
            return;
        }
        
        echo '<button type="button" id="tldrwp_test_connection" class="button button-secondary">' . esc_html__( 'Test Connection', 'tldrwp' ) . '</button>';
        echo '<span id="tldrwp_test_result" style="margin-left: 10px;"></span>';
        echo '<p class="description">' . esc_html__( 'Test the connection to your selected AI platform.', 'tldrwp' ) . '</p>';
        
        // Add JavaScript for AJAX test
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#tldrwp_test_connection').on('click', function() {
                var button = $(this);
                var resultSpan = $('#tldrwp_test_result');
                
                button.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'tldrwp' ) ); ?>');
                resultSpan.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tldrwp_test_ai',
                        nonce: '<?php echo wp_create_nonce( 'tldrwp_ajax_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultSpan.html('<span style="color: green;">✓ ' + response.data + '</span>');
                        } else {
                            resultSpan.html('<span style="color: red;">✗ ' + response.data + '</span>');
                        }
                    },
                    error: function() {
                        resultSpan.html('<span style="color: red;">✗ <?php echo esc_js( __( 'Connection failed', 'tldrwp' ) ); ?></span>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php echo esc_js( __( 'Test Connection', 'tldrwp' ) ); ?>');
                    }
                });
            });
            
            // Update models when platform changes
            $('#tldrwp_ai_platform').on('change', function() {
                var platform = $(this).val();
                var modelSelect = $('#tldrwp_ai_model');
                
                if (platform) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'tldrwp_get_models',
                            platform: platform,
                            nonce: '<?php echo wp_create_nonce( 'tldrwp_ajax_nonce' ); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                modelSelect.html('<option value=""><?php echo esc_js( __( 'Select AI Model', 'tldrwp' ) ); ?></option>');
                                $.each(response.data, function(slug, name) {
                                    modelSelect.append('<option value="' + slug + '">' + name + '</option>');
                                });
                            }
                        }
                    });
                } else {
                    modelSelect.html('<option value=""><?php echo esc_js( __( 'Please select an AI platform first.', 'tldrwp' ) ); ?></option>');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Rate limiting callback.
     */
    public function rate_limiting_callback() {
        $this->refresh_settings();
        $rate_limit_requests = $this->plugin->settings['rate_limit_requests'];
        $rate_limit_window = $this->plugin->settings['rate_limit_window'];
        
        echo '<p><label>' . esc_html__( 'Maximum requests per hour:', 'tldrwp' ) . '</label><br>';
        echo '<input type="number" name="tldrwp_settings[rate_limit_requests]" value="' . esc_attr( $rate_limit_requests ) . '" min="1" max="100" style="width: 100px;">';
        echo '</p>';
        echo '<p><label>' . esc_html__( 'Time window (seconds):', 'tldrwp' ) . '</label><br>';
        echo '<input type="number" name="tldrwp_settings[rate_limit_window]" value="' . esc_attr( $rate_limit_window ) . '" min="60" max="86400" style="width: 100px;">';
        echo ' <span class="description">(' . esc_html( gmdate( 'H:i:s', $rate_limit_window ) ) . ')</span>';
        echo '</p>';
        echo '<p class="description">' . esc_html__( 'Rate limiting helps prevent abuse and control API costs. Set to 0 to disable rate limiting.', 'tldrwp' ) . '</p>';
    }

    /**
     * Sanitize settings.
     *
     * @param array $input Input settings.
     * @return array Sanitized settings.
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();
        
        // Enabled post types
        if ( isset( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
            $sanitized['enabled_post_types'] = array_map( 'sanitize_text_field', $input['enabled_post_types'] );
        } else {
            $sanitized['enabled_post_types'] = array( 'post' );
        }
        
        // Default prompt
        if ( isset( $input['default_prompt'] ) ) {
            $sanitized['default_prompt'] = sanitize_textarea_field( $input['default_prompt'] );
        }
        
        // Button title
        if ( isset( $input['button_title'] ) ) {
            $sanitized['button_title'] = sanitize_text_field( $input['button_title'] );
        }
        
        // Button description
        if ( isset( $input['button_description'] ) ) {
            $sanitized['button_description'] = sanitize_text_field( $input['button_description'] );
        }
        
        // Success message
        if ( isset( $input['success_message'] ) ) {
            $sanitized['success_message'] = sanitize_text_field( $input['success_message'] );
        }
        
        // Enable social sharing
        $sanitized['enable_social_sharing'] = isset( $input['enable_social_sharing'] ) ? true : false;
        
        // Selected AI platform
        if ( isset( $input['selected_ai_platform'] ) ) {
            $sanitized['selected_ai_platform'] = sanitize_text_field( $input['selected_ai_platform'] );
        }
        
        // Selected AI model
        if ( isset( $input['selected_ai_model'] ) ) {
            $sanitized['selected_ai_model'] = sanitize_text_field( $input['selected_ai_model'] );
        }
        
        // Rate limiting settings
        if ( isset( $input['rate_limit_requests'] ) ) {
            $sanitized['rate_limit_requests'] = absint( $input['rate_limit_requests'] );
            if ( $sanitized['rate_limit_requests'] > 100 ) {
                $sanitized['rate_limit_requests'] = 100;
            }
        }
        
        if ( isset( $input['rate_limit_window'] ) ) {
            $sanitized['rate_limit_window'] = absint( $input['rate_limit_window'] );
            if ( $sanitized['rate_limit_window'] < 60 ) {
                $sanitized['rate_limit_window'] = 60;
            } elseif ( $sanitized['rate_limit_window'] > 86400 ) {
                $sanitized['rate_limit_window'] = 86400;
            }
        }
        
        return $sanitized;
    }
} 