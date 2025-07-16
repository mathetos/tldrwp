(function() {
    'use strict';

    // TL;DR Frontend JavaScript
    class TLDRWP {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('tldrwp-button')) {
                    e.preventDefault();
                    this.handleTLDRRequest(e.target);
                }
            });
        }

        async handleTLDRRequest(button) {
            const container = button.closest('.tldrwp-container');
            const content = container.querySelector('.tldrwp-content');
            
            // Get the prompt from button data or use default
            const prompt = button.dataset.prompt || this.getDefaultPrompt();
            
            // Show loading state
            this.showLoading(button, content);
            
            try {
                // Get post content for AI processing
                const postContent = this.getPostContent();
                
                // Make API request
                const response = await this.makeAPIRequest(prompt, postContent);
                
                // Show success state with TL;DR content
                this.showTLDRContent(button, content, response);
                
            } catch (error) {
                console.error('TL;DR Error:', error);
                this.showError(button, content, error.message);
            }
        }

        getDefaultPrompt() {
            // This will be populated from PHP settings
            return document.querySelector('.tldrwp-container').dataset.defaultPrompt || 
                   'Please provide a concise TL;DR summary of this article with a call-to-action at the end.';
        }

        getPostContent() {
            // Get the main post content
            const contentSelectors = [
                '.entry-content',
                '.post-content', 
                '.content',
                'article .content',
                '.main-content'
            ];
            
            for (const selector of contentSelectors) {
                const element = document.querySelector(selector);
                if (element) {
                    return element.textContent.trim();
                }
            }
            
            // Fallback to body content
            return document.body.textContent.trim();
        }

        async makeAPIRequest(prompt, content) {
            const formData = new FormData();
            formData.append('action', 'tldrwp_generate_summary');
            formData.append('prompt', prompt);
            formData.append('content', content);
            formData.append('nonce', tldrwp_ajax.nonce);

            // Debug logging
            if (window.console && console.log) {
                console.log('TLDRWP: Making API request with prompt length:', prompt.length);
                console.log('TLDRWP: Content length:', content.length);
            }

            const response = await fetch(tldrwp_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Server error (${response.status}): ${response.statusText}`);
            }

            const data = await response.json();
            
            if (!data.success) {
                // Provide more specific error messages
                let errorMessage = data.data || 'Unknown error occurred';
                
                // Map common error messages to user-friendly versions
                if (errorMessage.includes('AI Services plugin is not active')) {
                    errorMessage = 'AI Services plugin is not active. Please contact your site administrator.';
                } else if (errorMessage.includes('No AI service is configured')) {
                    errorMessage = 'No AI service is configured. Please contact your site administrator to set up an AI provider.';
                } else if (errorMessage.includes('API configuration')) {
                    errorMessage = 'AI service configuration issue. Please contact your site administrator.';
                } else if (errorMessage.includes('Security check failed')) {
                    errorMessage = 'Security verification failed. Please refresh the page and try again.';
                }
                
                throw new Error(errorMessage);
            }

            return data.data;
        }

        showLoading(button, content) {
            // Disable button and show loading state
            button.disabled = true;
            button.classList.add('loading');
            
            // Update button text and icon
            const titleElement = button.querySelector('.tldrwp-button-title');
            const descElement = button.querySelector('.tldrwp-button-desc');
            
            if (titleElement) titleElement.textContent = 'Generating...';
            if (descElement) descElement.textContent = 'Creating your TL;DR';
            
            // Clear any existing content
            if (content) {
                content.innerHTML = '';
                content.style.display = 'none';
            }
        }

        showTLDRContent(button, content, response) {
            // Remove loading state
            button.classList.remove('loading');
            button.disabled = false;
            
            // Restore button to original state
            this.restoreButton(button);
            
            // Show TL;DR content with animation
            if (content) {
                // Check if social sharing is enabled
                const socialSharingEnabled = tldrwp_ajax.enable_social_sharing;
                
                // Build social sharing HTML conditionally
                const socialSharingHTML = socialSharingEnabled ? `
                    <div class="tldrwp-social-sharing">
                        <div class="tldrwp-social-sharing-text">Share these insights here:</div>
                        <div class="tldrwp-social-buttons">
                            <a href="#" class="tldrwp-social-button" onclick="tldrwp.shareToTwitter('${this.escapeHtml(response)}')" title="Share on Twitter">
                                <svg viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                            <a href="#" class="tldrwp-social-button" onclick="tldrwp.shareToFacebook('${this.escapeHtml(response)}')" title="Share on Facebook">
                                <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="#" class="tldrwp-social-button" onclick="tldrwp.shareToLinkedIn('${this.escapeHtml(response)}')" title="Share on LinkedIn">
                                <svg viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                            <button class="tldrwp-social-button" onclick="tldrwp.copyToClipboard('${this.escapeHtml(response)}')" title="Copy to clipboard">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                                    <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                ` : '';
                
                content.innerHTML = `
                    <div class="tldrwp-summary">
                        <h4 class="tldrwp-summary-title">
                            <svg class="tldrwp-summary-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path>
                                <path d="M20 3v4"></path>
                                <path d="M22 5h-4"></path>
                                <path d="M4 17v2"></path>
                                <path d="M5 18H3"></path>
                            </svg>
                            Key Insights
                        </h4>
                        <div class="tldrwp-summary-content">${response}</div>
                        ${socialSharingHTML}
                    </div>
                `;
                
                content.style.display = 'block';
                content.classList.add('tldrwp-fade-in');
                
                // Scroll to content if it's not visible
                this.scrollToElement(content);
            }
        }

        showError(button, content, errorMessage) {
            // Remove loading state
            button.classList.remove('loading');
            button.disabled = false;
            
            // Restore button to original state
            this.restoreButton(button);
            
            // Show error message
            if (content) {
                content.innerHTML = `
                    <div class="tldrwp-error">
                        <p>❌ Sorry, we couldn't generate a TL;DR summary at this time.</p>
                        <p class="tldrwp-error-details">${errorMessage}</p>
                    </div>
                `;
                
                content.style.display = 'block';
                content.classList.add('tldrwp-fade-in');
            }
        }

        restoreButton(button) {
            // Restore original button text
            const titleElement = button.querySelector('.tldrwp-button-title');
            const descElement = button.querySelector('.tldrwp-button-desc');
            
            if (titleElement) titleElement.textContent = 'Short on time?';
            if (descElement) descElement.textContent = 'Click here to generate a TL;DR of this article';
        }

        // Helper function to escape HTML for sharing
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Social sharing methods
        shareToTwitter(text) {
            const url = encodeURIComponent(window.location.href);
            const text_encoded = encodeURIComponent(text.substring(0, 200) + '...');
            window.open(`https://twitter.com/intent/tweet?text=${text_encoded}&url=${url}`, '_blank');
        }

        shareToFacebook(text) {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        }

        shareToLinkedIn(text) {
            const url = encodeURIComponent(window.location.href);
            const text_encoded = encodeURIComponent(text.substring(0, 200) + '...');
            window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank');
        }

        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show a brief success message
                const button = event.target.closest('.tldrwp-social-button');
                const originalHTML = button.innerHTML;
                button.innerHTML = '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
                button.style.color = '#10b981';
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.style.color = '';
                }, 2000);
            });
        }

        scrollToElement(element) {
            const rect = element.getBoundingClientRect();
            const isVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;
            
            if (!isVisible) {
                element.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'nearest' 
                });
            }
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.tldrwp = new TLDRWP();
        });
    } else {
        window.tldrwp = new TLDRWP();
    }

})(); 