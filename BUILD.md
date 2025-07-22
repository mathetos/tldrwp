# TLDRWP Build Process

## Block Editor Integration

This plugin includes a Block Editor integration that adds a TL;DR toggle setting to the post sidebar.

### Building the JavaScript

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Build for production:**
   ```bash
   npm run build
   ```

3. **Build for development (with watch):**
   ```bash
   npm run dev
   ```

### Files

- `admin/js/tldrwp-editor.js` - Source file for Block Editor integration
- `webpack.config.js` - Webpack configuration
- `package.json` - Dependencies and build scripts

### What it does

The Block Editor integration adds a "TL;DR" toggle in the post sidebar settings that allows users to disable the TL;DR functionality for individual posts/pages. When disabled, the TL;DR button will not appear on the frontend for that specific post.

### Supported Post Types

Currently supports:
- Posts (`post`)
- Pages (`page`)

The setting appears in the Document Settings panel in the Block Editor sidebar. 