<?php
/**
 * TLDRWP Social Sharing Functions
 *
 * @package TLDRWP
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * TLDRWP Social Sharing Class
 *
 * Handles social sharing functionality with proper metadata and API endpoints.
 */
class TLDRWP_Social_Sharing {

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
     * Initialize social sharing functionality.
     */
    private function init() {
        add_action( 'wp_ajax_tldrwp_get_share_data', array( $this, 'get_share_data' ) );
        add_action( 'wp_ajax_nopriv_tldrwp_get_share_data', array( $this, 'get_share_data' ) );
    }

    /**
     * Get share data for the current post.
     */
    public function get_share_data() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'tldrwp_ajax_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        $post_id = intval( $_POST['post_id'] );
        
        if ( ! $post_id || ! get_post( $post_id ) ) {
            wp_send_json_error( 'Invalid post ID' );
        }

        // Get post data
        $post = get_post( $post_id );
        
        // Get share data
        $share_data = $this->prepare_share_data( $post );
        
        wp_send_json_success( $share_data );
    }

    /**
     * Prepare share data for social platforms.
     *
     * @param WP_Post $post Post object.
     * @return array Share data.
     */
    public function prepare_share_data( $post ) {
        $url = get_permalink( $post->ID );
        $title = get_the_title( $post->ID );
        
        // Try to get meta description (e.g. Yoast); fall back to excerpt
        $description = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
        if ( ! $description ) {
            $description = get_the_excerpt( $post->ID );
        }
        
        // If still no description, create one from content
        if ( ! $description ) {
            $content = wp_strip_all_tags( $post->post_content );
            $description = wp_trim_words( $content, 25, '...' );
        }
        
        $site_name = get_bloginfo( 'name' );
        
        // Clean and encode data
        $share_data = array(
            'url' => urlencode( $url ),
            'title' => urlencode( $title ),
            'description' => urlencode( $description ),
            'site_name' => urlencode( $site_name ),
            'raw_url' => $url,
            'raw_title' => $title,
            'raw_description' => $description,
            'raw_site_name' => $site_name
        );
        
        return $share_data;
    }

    /**
     * Generate social sharing URLs.
     *
     * @param array $share_data Share data array.
     * @param string $tldr_text TL;DR text to include.
     * @return array Social sharing URLs.
     */
    public function generate_share_urls( $share_data, $tldr_text = '' ) {
        $urls = array();
        
        // Twitter/X
        $twitter_text = $tldr_text ? $tldr_text : $share_data['raw_title'];
        $twitter_text = $this->truncate_text( $twitter_text, 200 );
        $urls['twitter'] = sprintf(
            'https://twitter.com/intent/tweet?text=%s&url=%s',
            urlencode( $twitter_text ),
            $share_data['url']
        );
        
        // Facebook
        $urls['facebook'] = sprintf(
            'https://www.facebook.com/sharer/sharer.php?u=%s',
            $share_data['url']
        );
        
        // LinkedIn
        $linkedin_text = $tldr_text ? $tldr_text : $share_data['raw_description'];
        $linkedin_text = $this->truncate_text( $linkedin_text, 200 );
        $urls['linkedin'] = sprintf(
            'https://www.linkedin.com/shareArticle?mini=true&url=%s&title=%s&summary=%s&source=%s',
            $share_data['url'],
            $share_data['title'],
            urlencode( $linkedin_text ),
            $share_data['site_name']
        );
        
        return $urls;
    }

    /**
     * Truncate text at word boundaries.
     *
     * @param string $text Text to truncate.
     * @param int $max_length Maximum length.
     * @return string Truncated text.
     */
    private function truncate_text( $text, $max_length ) {
        if ( strlen( $text ) <= $max_length ) {
            return $text;
        }
        
        // Truncate at word boundary
        $truncated = substr( $text, 0, $max_length );
        $last_space = strrpos( $truncated, ' ' );
        
        if ( $last_space !== false ) {
            $truncated = substr( $truncated, 0, $last_space );
        }
        
        return $truncated . '...';
    }

    /**
     * Generate social sharing HTML.
     *
     * @param string $tldr_text TL;DR text.
     * @param int $post_id Post ID.
     * @return string HTML for social sharing buttons.
     */
    public function generate_social_sharing_html( $tldr_text, $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return '';
        }
        
        $share_data = $this->prepare_share_data( $post );
        $share_urls = $this->generate_share_urls( $share_data, $tldr_text );
        
        $html = '<div class="tldrwp-social-sharing">';
        $html .= '<div class="tldrwp-social-sharing-text">Share these insights:</div>';
        $html .= '<div class="tldrwp-social-buttons">';
        
        // Twitter/X
        $html .= sprintf(
            '<a href="%s" class="tldrwp-social-button" target="_blank" rel="noopener" title="Share on X (Twitter)">',
            esc_url( $share_urls['twitter'] )
        );
        $html .= '<svg viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>';
        $html .= '</a>';
        
        // Facebook
        $html .= sprintf(
            '<a href="%s" class="tldrwp-social-button" target="_blank" rel="noopener" title="Share on Facebook">',
            esc_url( $share_urls['facebook'] )
        );
        $html .= '<svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';
        $html .= '</a>';
        
        // LinkedIn
        $html .= sprintf(
            '<a href="%s" class="tldrwp-social-button" target="_blank" rel="noopener" title="Share on LinkedIn">',
            esc_url( $share_urls['linkedin'] )
        );
        $html .= '<svg viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>';
        $html .= '</a>';
        
        // Copy to clipboard
        $html .= sprintf(
            '<button class="tldrwp-social-button" onclick="tldrwp.copyToClipboard(\'%s\')" title="Copy to clipboard">',
            esc_js( $tldr_text )
        );
        $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                    <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                  </svg>';
        $html .= '</button>';
        
        $html .= '</div></div>';
        
        return $html;
    }
} 