# TLDRWP Release Process

## Automated Release Workflow

This plugin uses GitHub Actions to automatically build and package releases when you create a new GitHub release.

### How It Works

1. **Create a GitHub Release**: When you create a new release on GitHub, the workflow automatically triggers
2. **Build Process**: The workflow installs dependencies, runs webpack build, and creates the production assets
3. **Cleanup**: Development files are removed to create a clean distribution package
4. **Package**: The plugin is zipped with the release tag name
5. **Attach**: The ZIP file is automatically attached to the GitHub release

### Creating a Release

1. **Prepare your changes**:
   ```bash
   git add .
   git commit -m "Your commit message"
   git push origin main
   ```

2. **Create a GitHub Release**:
   - Go to your GitHub repository
   - Click "Releases" → "Draft a new release"
   - Choose a tag (e.g., `v1.0.0`)
   - Add a title and description
   - Click "Publish release"

3. **Wait for the workflow**:
   - The GitHub Action will automatically run
   - Check the "Actions" tab to monitor progress
   - The ZIP file will be attached to your release when complete

### What Gets Included in the Release

✅ **Included**:
- All plugin PHP files
- Built JavaScript assets (`admin/js/tldrwp-editor.js`)
- Asset manifest (`admin/js/tldrwp-editor.asset.php`)
- CSS files
- Images and other assets
- README and documentation

❌ **Excluded**:
- `node_modules/` (development dependencies)
- `.github/` (workflow files)
- `package.json` and `package-lock.json`
- `webpack.config.js`
- Source maps (`.map` files)
- Development documentation

### Manual Build (if needed)

If you need to build manually:

```bash
# Install dependencies
npm install

# Build for production
npm run build

# The built files will be in admin/js/
```

### Version Management

- Use semantic versioning (e.g., `v1.0.0`, `v1.1.0`, `v2.0.0`)
- Update the version in `tldrwp.php` to match your release tag
- The ZIP filename will match your release tag

### Troubleshooting

If the workflow fails:
1. Check the "Actions" tab for error details
2. Ensure all dependencies are properly listed in `package.json`
3. Verify the build works locally with `npm run build`
4. Check that the plugin slug in the workflow matches your directory name 