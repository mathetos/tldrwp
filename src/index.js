import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { ToggleControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

registerPlugin( 'tldrwp-toggle', {
    render: () => {
        // Dynamically grab the current post type
        const postType = useSelect(
            ( select ) => select( 'core/editor' ).getCurrentPostType(),
            []
        );

        // Read + write the entire meta object for this post type
        const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

        // Our boolean flag is literally “disabled”
        const isDisabled = Boolean( meta._tldrwp_disabled );

        // When the toggle flips, write the new disabled-boolean back to meta
        const onToggle = ( newValue ) => {
            setMeta( {
                ...meta,
                _tldrwp_disabled: newValue,
            } );
        };

        return (
            <PluginDocumentSettingPanel
                name="tldrwp-panel"
                title={ __( 'TL;DR', 'tldrwp' ) }
                initialOpen={ false }
            >
                <ToggleControl
                    label={ __( 'Disable TL;DR', 'tldrwp' ) }
                    checked={ isDisabled }
                    onChange={ onToggle }
                    help={ __(
                        'Check to disable the TL;DR button and functionality on the frontend for this post.',
                        'tldrwp'
                    ) }
                />
            </PluginDocumentSettingPanel>
        );
    },
} );
