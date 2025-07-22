# TLDRWP v0.9 - "Block Editor Integration"

## 🎉 Major Release - Block Editor Toggle Feature

This release introduces a powerful new feature that allows users to conditionally disable TL;DR functionality on a per-post basis directly from the WordPress Block Editor.

## ✨ New Features

### 🔧 Block Editor Integration
- **New TL;DR Toggle**: Added a "TL;DR" setting in the Document Settings panel of the Block Editor
- **Per-Post Control**: Enable/disable TL;DR functionality for individual posts and pages
- **Native WordPress UI**: Seamlessly integrated into the existing Block Editor sidebar
- **Real-time Updates**: Changes take effect immediately on the frontend

### 🎨 Enhanced Copy Button
- **Ghost-style Design**: Beautiful, modern button design that matches contemporary UI patterns
- **Floated Layout**: Positioned to the right of social sharing buttons for better visual hierarchy
- **Icon States**: Dynamic icon switching (clipboard-add ↔ clipboard-check) for clear user feedback
- **Consistent Styling**: Matches the color scheme of other social sharing buttons

## 🔧 Technical Improvements

### 🏗️ Build System
- **Webpack Integration**: Modern JavaScript bundling for Block Editor components
- **Asset Management**: Proper dependency handling and versioning
- **Production Optimization**: Minified and optimized builds for performance

### 🔄 Social Sharing Refactor
- **JSON Data Injection**: Replaced AJAX calls with embedded JSON for better performance
- **Metadata Optimization**: Improved excerpt handling with Yoast SEO integration
- **Platform Compatibility**: Enhanced support for Twitter, Facebook, and LinkedIn sharing

### 🛡️ Enhanced Security
- **Nonce Verification**: Proper security checks for all AJAX operations
- **Input Sanitization**: Comprehensive data sanitization and validation
- **Permission Checks**: Role-based access control for admin functions

## 🐛 Bug Fixes

- **Toggle Persistence**: Fixed issue where Block Editor toggle would reset after saving
- **Copy Button Functionality**: Resolved JavaScript errors in copy-to-clipboard feature
- **Social Sharing**: Fixed empty content issues in social media shares
- **Meta Field Handling**: Improved reliability of post meta field saving

## 🚀 Performance Improvements

- **Reduced AJAX Calls**: Eliminated unnecessary server requests for share data
- **Optimized Asset Loading**: Streamlined JavaScript and CSS delivery
- **Better Caching**: Improved browser caching for static assets

## 📱 User Experience Enhancements

- **Visual Feedback**: Clear indication when TL;DR is copied to clipboard
- **Responsive Design**: Improved mobile experience for all UI elements
- **Accessibility**: Better ARIA labels and keyboard navigation support
- **Error Handling**: Graceful fallbacks when features are unavailable

## 🔧 Developer Experience

- **GitHub Actions**: Automated build and release process
- **Code Quality**: Improved code organization and documentation
- **Debugging Tools**: Enhanced error logging and debugging capabilities
- **Build Documentation**: Comprehensive setup and deployment guides

## 📋 System Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **JavaScript**: ES6+ support (modern browsers)
- **Block Editor**: Gutenberg/Block Editor required for toggle feature

## 🎯 Installation

1. Download the ZIP file from this release
2. Upload via WordPress Admin → Plugins → Add New → Upload Plugin
3. Activate the plugin
4. Configure AI settings in Reading Settings
5. Start using TL;DR on your posts!

## 🔄 Upgrade Notes

- **Automatic Upgrade**: Safe to upgrade from previous versions
- **Settings Migration**: All existing settings will be preserved
- **Database Changes**: New meta fields will be created automatically
- **No Breaking Changes**: Fully backward compatible

## 🎨 Customization

The new Block Editor toggle integrates seamlessly with existing themes and can be customized via CSS if needed. The copy button styling can be overridden using standard WordPress CSS customization methods.

## 🐛 Known Issues

- None reported in this release

## 🔮 What's Next

- Enhanced AI model selection
- Advanced customization options
- Performance analytics
- Multi-language support

---

**Thank you for using TLDRWP!** 🚀

This release represents a significant milestone in making TL;DR functionality more user-friendly and integrated with the modern WordPress experience. The Block Editor integration opens up new possibilities for content creators to have fine-grained control over their TL;DR features.

*For support, feature requests, or bug reports, please visit our GitHub repository.* 