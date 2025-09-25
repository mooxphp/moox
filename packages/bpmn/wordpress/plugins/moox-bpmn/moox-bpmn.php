<?php
/**
 * Plugin Name: Moox BPMN
 * Plugin URI: https://moox.org
 * Description: Upload, view and edit BPMN 2.0 models made with BMPN.io or Camunda.
 * Version: 1.0.0
 * Author: Moox Developer
 * Author URI: https://moox.org
 * License: MIT
 * Text Domain: moox-bpmn
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MOOX_BPMN_VERSION', '1.0.0');
define('MOOX_BPMN_PLUGIN_FILE', __FILE__);
define('MOOX_BPMN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MOOX_BPMN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MOOX_BPMN_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class MooxBpmn {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Get plugin instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendScripts']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets']);
        add_action('wp_ajax_moox_bpmn_save', [$this, 'handleBpmnSave']);
        add_action('wp_ajax_nopriv_moox_bpmn_save', [$this, 'handleBpmnSave']);

        // Add BPMN support to media library
        add_filter('upload_mimes', [$this, 'addBpmnMimeType']);
        add_filter('wp_check_filetype_and_ext', [$this, 'addBpmnFileType'], 10, 5);
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('moox-bpmn', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Register Gutenberg block
        register_block_type('moox/bpmn-viewer', [
            'render_callback' => [$this, 'renderBpmnBlock'],
            'attributes' => [
                'mediaId' => [
                    'type' => 'number',
                    'default' => 0,
                ],
                'mode' => [
                    'type' => 'string',
                    'default' => 'view',
                ],
                'height' => [
                    'type' => 'string',
                    'default' => '500px',
                ],
            ],
        ]);
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueueFrontendScripts() {
        if (has_block('moox/bpmn-viewer')) {
            wp_enqueue_script(
                'moox-bpmn-viewer',
                MOOX_BPMN_PLUGIN_URL . 'js/bpmn-viewer.js',
                ['jquery'],
                MOOX_BPMN_VERSION,
                true
            );

            wp_enqueue_style(
                'moox-bpmn-viewer',
                MOOX_BPMN_PLUGIN_URL . 'css/bpmn-viewer.css',
                [],
                MOOX_BPMN_VERSION
            );

            wp_localize_script('moox-bpmn-viewer', 'mooxBpmn', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('moox_bpmn_nonce'),
            ]);
        }
    }

    /**
     * Enqueue block editor assets
     */
    public function enqueueBlockEditorAssets() {
        wp_enqueue_script(
            'moox-bpmn-block',
            MOOX_BPMN_PLUGIN_URL . 'js/bpmn-block.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'],
            MOOX_BPMN_VERSION,
            true
        );

        wp_enqueue_style(
            'moox-bpmn-block',
            MOOX_BPMN_PLUGIN_URL . 'css/bpmn-block.css',
            ['wp-edit-blocks'],
            MOOX_BPMN_VERSION
        );

        wp_localize_script('moox-bpmn-block', 'mooxBpmnBlock', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('moox_bpmn_nonce'),
            'mediaLibrary' => [
                'title' => __('Select BPMN File', 'moox-bpmn'),
                'button' => __('Use BPMN File', 'moox-bpmn'),
                'mimeTypes' => ['application/xml'],
            ],
        ]);
    }

    /**
     * Render BPMN block
     */
    public function renderBpmnBlock($attributes) {
        $mediaId = $attributes['mediaId'] ?? 0;
        $mode = $attributes['mode'] ?? 'view';
        $height = $attributes['height'] ?? '500px';

        if (!$mediaId) {
            return '<div class="moox-bpmn-error">' . __('No BPMN file selected', 'moox-bpmn') . '</div>';
        }

        $attachment = get_post($mediaId);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return '<div class="moox-bpmn-error">' . __('Invalid BPMN file', 'moox-bpmn') . '</div>';
        }

        $fileUrl = wp_get_attachment_url($mediaId);
        if (!$fileUrl) {
            return '<div class="moox-bpmn-error">' . __('BPMN file not found', 'moox-bpmn') . '</div>';
        }

        ob_start();
        ?>
        <div class="moox-bpmn-viewer"
             data-media-id="<?php echo esc_attr($mediaId); ?>"
             data-mode="<?php echo esc_attr($mode); ?>"
             data-file-url="<?php echo esc_url($fileUrl); ?>"
             style="height: <?php echo esc_attr($height); ?>;">
            <div class="moox-bpmn-container">
                <div class="moox-bpmn-loading">
                    <?php _e('Loading BPMN diagram...', 'moox-bpmn'); ?>
                </div>
            </div>
            <?php if ($mode === 'edit'): ?>
                <div class="moox-bpmn-toolbar">
                    <button type="button" class="moox-bpmn-save">
                        <?php _e('Save Changes', 'moox-bpmn'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle BPMN save
     */
    public function handleBpmnSave() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'moox_bpmn_nonce')) {
            wp_die(__('Security check failed', 'moox-bpmn'));
        }

        $mediaId = intval($_POST['mediaId'] ?? 0);
        $bpmnContent = $_POST['bpmnContent'] ?? '';

        if (!$mediaId || !$bpmnContent) {
            wp_send_json_error(__('Invalid data', 'moox-bpmn'));
        }

        // Check if user can edit the attachment
        if (!current_user_can('edit_post', $mediaId)) {
            wp_send_json_error(__('Permission denied', 'moox-bpmn'));
        }

        $filePath = get_attached_file($mediaId);
        if (!$filePath || !file_exists($filePath)) {
            wp_send_json_error(__('File not found', 'moox-bpmn'));
        }

        // Save BPMN content
        if (file_put_contents($filePath, $bpmnContent) === false) {
            wp_send_json_error(__('Failed to save file', 'moox-bpmn'));
        }

        wp_send_json_success(__('BPMN file saved successfully', 'moox-bpmn'));
    }

    /**
     * Add BPMN MIME type
     */
    public function addBpmnMimeType($mimes) {
        $mimes['bpmn'] = 'application/xml';
        return $mimes;
    }

    /**
     * Add BPMN file type
     */
    public function addBpmnFileType($data, $file, $filename, $mimes) {
        $filetype = wp_check_filetype($filename, $mimes);

        if ($filetype['type'] === false && preg_match('/\.bpmn$/i', $filename)) {
            $data['ext'] = 'bpmn';
            $data['type'] = 'application/xml';
        }

        return $data;
    }
}

// Initialize plugin
MooxBpmn::getInstance();
