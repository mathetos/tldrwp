const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
    ...defaultConfig,
    entry: {
        'tldrwp-editor': './admin/js/tldrwp-editor.js'
    },
    output: {
        path: __dirname + '/admin/js',
        filename: '[name].js'
    }
}; 