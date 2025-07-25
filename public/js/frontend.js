(function() {
    'use strict';

    // TL;DR Frontend JavaScript
    class TLDRWP {
        constructor() {
            this.init();
        }

        async init() {
            await this.checkAlreadyClicked();
            this.bindEvents();
        }

        /**
         * Check if TL;DR has already been generated for this article
         */
        async checkAlreadyClicked() {
            const articleId = tldrwp_ajax.article_id;
            if (!articleId) return;

            const tldrKey = `tldr_clicked_${articleId}`;
            const tldrContentKey = `tldr_content_${articleId}`;
            const alreadyClicked = localStorage.getItem(tldrKey);
            const savedContent = localStorage.getItem(tldrContentKey);
            
            if (alreadyClicked && savedContent) {
                // Find the button and convert it to success state
                const button = document.querySelector('.tldrwp-button');
                if (button) {
                    this.convertButtonToSuccessDiv(button);
                }
                
                // Restore the TL;DR content
                await this.restoreTLDRContent(savedContent);
            }
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
            
            // Set localStorage to mark this article as clicked
            this.markArticleAsClicked();
            
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

        showTLDRContent(button, content, responseData) {
            // Remove loading state
            button.classList.remove('loading');
            
            // Convert button to success div - completely remove clickability
            this.convertButtonToSuccessDiv(button);
            
            // Extract response and action hooks
            const response = responseData.response || responseData; // Handle both new and old format
            const actionHooks = responseData.action_hooks || {};
            
            // Store share data for social sharing functions (keeping for potential future use)
            this.shareData = responseData.social_sharing_data;
            
            // Save the TL;DR content to localStorage
            this.saveTLDRContent(response);
            
            // Dispatch custom event for analytics tracking
            this.dispatchTLDREvent();
            
            // Show TL;DR content with animation
            if (content) {
                // Check if social sharing is enabled
                const socialSharingEnabled = tldrwp_ajax.enable_social_sharing;
                
                // Build social sharing HTML conditionally
                const socialSharingHTML = socialSharingEnabled ? `
                    <div class="tldrwp-social-sharing">
                        <div class="tldrwp-social-sharing-text">Share this article:</div>
                        <div class="tldrwp-social-buttons">
                            <a href="#" class="tldrwp-social-button" onclick="tldrwp.shareToTwitter()" title="Share on X (Twitter)">
                                <svg viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                            <a href="#" class="tldrwp-social-button" onclick="tldrwp.shareToFacebook()" title="Share on Facebook">
                                <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="#" class="tldrwp-social-button" onclick="tldrwp.shareToLinkedIn()" title="Share on LinkedIn">
                                <svg viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        </div>
                    </div>
                ` : '';
                
                // Add the Copy TL;DR button HTML, floated right, with SVG icon
                const copyButtonHTML = `
                    <button class="tldrwp-copy-tldr-btn" id="tldrwp-copy-tldr-btn" aria-label="Copy TL;DR">
                        <img src="/wp-content/plugins/tldrwp/public/img/clipboard-add-svgrepo-com.svg" alt="Copy" class="tldrwp-copy-tldr-icon"/>
                        <span class="tldrwp-copy-tldr-text">Copy TL;DR</span>
                    </button>
                `;
                // Insert copyButtonHTML inside the social sharing footer, floated right
                const socialSharingFooter = document.createElement('div');
                socialSharingFooter.className = 'tldrwp-social-footer';
                socialSharingFooter.innerHTML = socialSharingHTML + copyButtonHTML;
                
                // Build the TL;DR content with action hooks
                const summaryHTML = this.buildSummaryWithHooks(response, socialSharingFooter.outerHTML, actionHooks);
                content.innerHTML = summaryHTML;
                
                content.style.display = 'block';
                content.classList.add('tldrwp-fade-in');
                
                // Scroll to content if it's not visible
                this.scrollToElement(content);

                // Add event listener for the copy button after rendering
                setTimeout(() => {
                    const copyBtn = document.getElementById('tldrwp-copy-tldr-btn');
                    if (copyBtn) {
                        copyBtn.addEventListener('click', () => {
                            // Get plain text TL;DR (strip HTML tags)
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = response;
                            const plainText = tempDiv.innerText;
                            navigator.clipboard.writeText(plainText).then(() => {
                                // Swap icon to check
                                const icon = copyBtn.querySelector('.tldrwp-copy-tldr-icon');
                                if (icon) {
                                    icon.src = '/wp-content/plugins/tldrwp/public/img/clipboard-check-svgrepo-com.svg';
                                    icon.alt = 'Copied';
                                }
                                // Optionally, visually indicate success (e.g., add a class)
                                copyBtn.classList.add('tldrwp-copied');
                            });
                        });
                    }
                }, 0);
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

        // Helper function to escape JavaScript strings for onclick attributes
        escapeJsString(text) {
            return text
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'")
                .replace(/"/g, '\\"')
                .replace(/\n/g, '\\n')
                .replace(/\r/g, '\\r')
                .replace(/\t/g, '\\t');
        }

        // Get share data from hidden JSON
        getShareData() {
            const shareDataElement = document.getElementById('tldrwp-share-data');
            if (!shareDataElement) {
                console.warn('Share data element not found');
                return null;
            }
            
            try {
                const shareData = JSON.parse(shareDataElement.textContent);
                console.log('Share data loaded:', shareData);
                return shareData;
            } catch (error) {
                console.error('Failed to parse share data:', error);
                return null;
            }
        }

        // Social sharing methods
        shareToTwitter() {
            console.log('Twitter share clicked');
            
            const shareData = this.getShareData();
            if (!shareData) {
                console.error('No share data available');
                return;
            }
            
            // Format: "Title" Excerpt URL
            const shareText = shareData.excerpt ? 
                `"${shareData.title}" ${shareData.excerpt}` : 
                `"${shareData.title}"`;
            
            const truncatedText = this.truncateText(shareText, 200);
            const shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(truncatedText)}&url=${encodeURIComponent(shareData.url)}`;
            
            console.log('Share URL:', shareUrl);
            window.open(shareUrl, '_blank', 'noopener,noreferrer');
        }

        shareToFacebook() {
            console.log('Facebook share clicked');
            
            const shareData = this.getShareData();
            if (!shareData) {
                console.error('No share data available');
                return;
            }
            
            // Facebook doesn't support pre-populated text, only URL
            const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareData.url)}`;
            
            console.log('Share URL:', shareUrl);
            window.open(shareUrl, '_blank', 'noopener,noreferrer');
        }

        shareToLinkedIn() {
            console.log('LinkedIn share clicked');
            
            const shareData = this.getShareData();
            if (!shareData) {
                console.error('No share data available');
                return;
            }
            
            // LinkedIn doesn't support pre-populated text, but we can set title and summary
            const shareUrl = `https://www.linkedin.com/shareArticle?mini=true&url=${encodeURIComponent(shareData.url)}&title=${encodeURIComponent(shareData.title)}&summary=${encodeURIComponent(shareData.excerpt || '')}`;
            
            console.log('Share URL:', shareUrl);
            window.open(shareUrl, '_blank', 'noopener,noreferrer');
        }

        // Helper method to truncate text at word boundaries
        truncateText(text, maxLength) {
            if (text.length <= maxLength) {
                return text;
            }
            
            const truncated = text.substring(0, maxLength);
            const lastSpace = truncated.lastIndexOf(' ');
            
            if (lastSpace !== -1) {
                return truncated.substring(0, lastSpace) + '...';
            }
            
            return truncated + '...';
        }

        copyToClipboard() {
            console.log('Copy to clipboard clicked');
            
            const shareData = this.getShareData();
            if (!shareData) {
                console.error('No share data available');
                return;
            }
            
            // Format: "Title" Excerpt URL
            const clipboardText = shareData.excerpt ? 
                `"${shareData.title}" ${shareData.excerpt}\n\n${shareData.url}` : 
                `"${shareData.title}"\n\n${shareData.url}`;
            
            console.log('Clipboard text:', clipboardText);
            
            navigator.clipboard.writeText(clipboardText).then(() => {
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

        /**
         * Mark the current article as clicked in localStorage
         */
        markArticleAsClicked() {
            const articleId = tldrwp_ajax.article_id;
            if (!articleId) return;

            const tldrKey = `tldr_clicked_${articleId}`;
            localStorage.setItem(tldrKey, 'true');
        }

        /**
         * Build TL;DR summary HTML with action hooks
         */
        buildSummaryWithHooks(response, socialSharingHTML, actionHooks = {}) {
            // Extract action hook outputs
            const beforeSummaryHeading = actionHooks.tldr_before_summary_heading || '';
            const afterSummaryHeading = actionHooks.tldr_after_summary_heading || '';
            const beforeSummaryCopy = actionHooks.tldr_before_summary_copy || '';
            const afterSummaryCopy = actionHooks.tldr_after_summary_copy || '';
            const summaryFooter = actionHooks.tldr_summary_footer || '';

            return `
                <div class="tldrwp-summary">
                    ${beforeSummaryHeading}
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
                    ${afterSummaryHeading}
                    ${beforeSummaryCopy}
                    <div class="tldrwp-summary-content">${response}</div>
                    ${afterSummaryCopy}
                    ${socialSharingHTML}
                    ${summaryFooter}
                </div>
            `;
        }

        /**
         * Dispatch custom event for analytics tracking
         */
        dispatchTLDREvent() {
            const articleId = tldrwp_ajax.article_id;
            const tldrEvent = new CustomEvent('tldrwp_generated', {
                detail: {
                    articleId: articleId,
                    articleTitle: document.title,
                    timestamp: new Date().toISOString(),
                    platform: 'tldrwp'
                }
            });
            document.dispatchEvent(tldrEvent);
        }

        /**
         * Save TL;DR content to localStorage
         */
        saveTLDRContent(content) {
            const articleId = tldrwp_ajax.article_id;
            if (!articleId) return;

            const tldrContentKey = `tldr_content_${articleId}`;
            localStorage.setItem(tldrContentKey, content);
        }

        /**
         * Restore TL;DR content from localStorage
         */
        async restoreTLDRContent(content) {
            const container = document.querySelector('.tldrwp-container');
            if (!container) return;

            const contentElement = container.querySelector('.tldrwp-content');
            if (!contentElement) return;

            // Parse the saved content (it's stored as JSON)
            let parsedContent;
            try {
                parsedContent = JSON.parse(content);
            } catch (e) {
                // Fallback to plain text if JSON parsing fails
                parsedContent = content;
            }

            // Check if social sharing is enabled
            const socialSharingEnabled = tldrwp_ajax.enable_social_sharing;
            
                        // Build social sharing HTML conditionally
            const socialSharingHTML = socialSharingEnabled ? `
                <div class="tldrwp-social-sharing">
                    <div class="tldrwp-social-sharing-text">Share this article:</div>
                    <div class="tldrwp-social-buttons">
                        <a href="#" class="tldrwp-social-button" onclick="tldrwp.shareToTwitter()" title="Share on X (Twitter)">
                            <svg viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </a>
                        <a href="#" class="tldrwp-social-button" onclick="tldrwp.shareToFacebook()" title="Share on Facebook">
                            <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="tldrwp-social-button" onclick="tldrwp.shareToLinkedIn()" title="Share on LinkedIn">
                            <svg viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    </div>
                </div>
            ` : '';
            
            // Add the Copy TL;DR button HTML, floated right, with SVG icon
            const copyButtonHTML = `
                <button class="tldrwp-copy-tldr-btn" id="tldrwp-copy-tldr-btn" aria-label="Copy TL;DR">
                    <img src="/wp-content/plugins/tldrwp/public/img/clipboard-add-svgrepo-com.svg" alt="Copy" class="tldrwp-copy-tldr-icon"/>
                    <span class="tldrwp-copy-tldr-text">Copy TL;DR</span>
                </button>
            `;
            // Insert copyButtonHTML inside the social sharing footer, floated right
            const socialSharingFooter = document.createElement('div');
            socialSharingFooter.className = 'tldrwp-social-footer';
            socialSharingFooter.innerHTML = socialSharingHTML + copyButtonHTML;
            
            contentElement.innerHTML = `
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
                    <div class="tldrwp-summary-content">${parsedContent}</div>
                    ${socialSharingFooter.outerHTML}
                </div>
            `;
            
            contentElement.style.display = 'block';
            contentElement.classList.add('tldrwp-fade-in');

            // Add event listener for the copy button after rendering
            setTimeout(() => {
                const copyBtn = document.getElementById('tldrwp-copy-tldr-btn');
                if (copyBtn) {
                    copyBtn.addEventListener('click', () => {
                        // Get plain text TL;DR (strip HTML tags)
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = parsedContent;
                        const plainText = tempDiv.innerText;
                        navigator.clipboard.writeText(plainText).then(() => {
                            // Swap icon to check
                            const icon = copyBtn.querySelector('.tldrwp-copy-tldr-icon');
                            if (icon) {
                                icon.src = '/wp-content/plugins/tldrwp/public/img/clipboard-check-svgrepo-com.svg';
                                icon.alt = 'Copied';
                            }
                            // Optionally, visually indicate success (e.g., add a class)
                            copyBtn.classList.add('tldrwp-copied');
                        });
                    });
                }
            }, 0);
        }

        /**
         * Convert the button to a success div - completely removes clickability
         * @param {HTMLElement} button - The original button element
         */
        convertButtonToSuccessDiv(button) {
            // Create new success div
            const successDiv = document.createElement('div');
            successDiv.className = 'tldrwp-success-div';
            
            // Get the success message
            const successMessage = tldrwp_ajax.success_message || 'Enjoy reading!';
            
            // Create success content with icon and message
            successDiv.innerHTML = `
                <div class="tldrwp-success-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                </div>
                <div class="tldrwp-success-text">
                    <span class="tldrwp-success-title">${successMessage}</span>
                </div>
            `;
            
            // Replace the button with the success div
            button.parentNode.replaceChild(successDiv, button);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', async () => {
            window.tldrwp = new TLDRWP();
        });
    } else {
        window.tldrwp = new TLDRWP();
    }

})(); 