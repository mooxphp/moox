# Moox BPMN WordPress Plugin

A WordPress plugin for uploading, viewing and editing BPMN 2.0 models made with [BMPN.io](https://bpmn.io/) or [Camunda](https://camunda.com).

## Features

-   **Gutenberg Block**: `moox/bpmn-viewer` block for easy BPMN integration
-   **Media Library Integration**: Upload and manage .bpmn files in WordPress Media Library
-   **Inline Editing**: Edit BPMN diagrams directly in the block editor
-   **Frontend Rendering**: Display BPMN diagrams on the frontend
-   **Self-contained**: No external dependencies, includes bpmn-js library

## Installation

1. Upload the plugin files to `/wp-content/plugins/moox-bpmn/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Start using the BPMN Viewer block in your posts and pages

## Usage

### Gutenberg Block

1. Add the "BPMN Viewer" block to your post or page
2. Click "Select BPMN File" to choose a .bpmn file from your Media Library
3. Configure the block settings:
    - **Mode**: View Only or Edit
    - **Height**: Custom height for the BPMN viewer
4. Save your post or page

### Block Settings

-   **Mode**:
    -   `View Only`: Display the BPMN diagram (read-only)
    -   `Edit`: Allow editing of the BPMN diagram
-   **Height**: CSS height value (e.g., 500px, 50vh)

### Frontend Display

The BPMN diagrams are automatically rendered on the frontend using bpmn-js Viewer. Users can interact with the diagrams but cannot edit them unless in edit mode.

## File Support

The plugin supports BPMN 2.0 files with the following characteristics:

-   File extension: `.bpmn`
-   MIME type: `application/xml`
-   Compatible with BPMN.io and Camunda Modeler

## Technical Details

### Dependencies

-   **bpmn-js**: BPMN 2.0 rendering library
-   **WordPress 5.0+**: Required for Gutenberg blocks
-   **Modern browsers**: ES6+ support required

### File Structure

```
moox-bpmn/
├── moox-bpmn.php          # Main plugin file
├── js/
│   ├── bpmn-block.js      # Gutenberg block JavaScript
│   └── bpmn-viewer.js     # Frontend BPMN viewer
├── css/
│   ├── bpmn-block.css     # Block editor styles
│   └── bpmn-viewer.css    # Frontend styles
└── README.md              # This file
```

### AJAX Endpoints

-   `wp_ajax_moox_bpmn_save`: Save BPMN content to media file

## Security

-   All AJAX requests are protected with WordPress nonces
-   File uploads are restricted to .bpmn files
-   User permissions are checked before allowing edits

## Browser Support

-   Chrome 60+
-   Firefox 55+
-   Safari 12+
-   Edge 79+

## License

MIT License - see the main Moox project for details.

## Support

For support and questions, please visit [Moox.org](https://moox.org).
