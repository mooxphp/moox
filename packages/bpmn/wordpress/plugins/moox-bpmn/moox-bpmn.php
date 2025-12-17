<?php
/**
 * Plugin Name: Moox BPMN
 * Description: Upload, view, and edit BPMN 2.0 models made with BPMN.io or Camunda.
 * Version: 1.0.1
 * Author: Moox Developer
 * License: MIT
 * Text Domain: moox-bpmn
 * Domain Path: /languages
 */
if (! defined('ABSPATH')) {
    exit;
}

define('MOOX_BPMN_VERSION', '1.0.1');
define('MOOX_BPMN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MOOX_BPMN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MOOX_BPMN_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MOOX_BPMN_PLUGIN_FILE', __FILE__);

class Moox_BPMN_Plugin
{
    public function __construct()
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'register_block']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_ajax_moox_bpmn_save', [$this, 'save_bpmn']);
        add_action('wp_ajax_nopriv_moox_bpmn_save', [$this, 'save_bpmn']);
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('moox-bpmn', false, dirname(plugin_basename(__FILE__)).'/languages');
    }

    public function register_block()
    {
        // register from build/block.json if present
        if (function_exists('register_block_type_from_metadata')) {
            register_block_type_from_metadata(__DIR__.'/build', [
                'render_callback' => [$this, 'render_bpmn_block'],
            ]);
        } else {
            // fallback: register minimal dynamic block
            register_block_type('moox-bpmn/bpmn-viewer', [
                'editor_script' => 'moox-bpmn-block',
                'render_callback' => [$this, 'render_bpmn_block'],
            ]);
        }
    }

    public function enqueue_block_editor_assets()
    {
        // --- Load BPMN Viewer (must load first) ---
        wp_enqueue_script(
            'bpmn-js-viewer',

            true
        );

        // --- Load BPMN Modeler (depends on Viewer) ---
        wp_enqueue_script(
            'bpmn-js-modeler',
            MOOX_BPMN_PLUGIN_URL.'node_modules/bpmn-js/dist/bpmn-modeler.development.js',
            ['bpmn-js-viewer'],
            '18.8.0',
            true
        );

        // --- Expose global constructors for Gutenberg ---
        wp_add_inline_script(
            'bpmn-js-modeler',
            'window.BpmnViewer = window.BpmnViewer || BpmnJS;
                 window.BpmnModeler = window.BpmnModeler || BpmnJS;
                 window.BpmnJS = window.BpmnJS || BpmnJS;',
            'after'
        );

        // --- Load Gutenberg BPMN renderer (provides renderGutenbergBpmn) ---
        wp_enqueue_script(
            'moox-bpmn-gutenberg-renderer',
            MOOX_BPMN_PLUGIN_URL.'src/bpmn-gutenberg-renderer.js',
            ['bpmn-js-viewer', 'bpmn-js-modeler'],
            MOOX_BPMN_VERSION,
            true
        );

        // --- Load Gutenberg block JS (no imports!) ---
        wp_enqueue_script(
            'moox-bpmn-block',
            MOOX_BPMN_PLUGIN_URL.'src/bpmn-block.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wp-block-editor', 'moox-bpmn-gutenberg-renderer'],
            MOOX_BPMN_VERSION,
            true
        );

        // --- Localize AJAX for saving ---
        wp_localize_script('moox-bpmn-block', 'mooxBpmnBlock', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('moox_bpmn_nonce'),
        ]);

        // --- Load BPMN CSS ---
        wp_enqueue_style(
            'bpmn-js-diagram',
            MOOX_BPMN_PLUGIN_URL.'node_modules/bpmn-js/dist/assets/diagram-js.css',
            [],
            MOOX_BPMN_VERSION
        );
        wp_enqueue_style(
            'bpmn-js',
            MOOX_BPMN_PLUGIN_URL.'node_modules/bpmn-js/dist/assets/bpmn-js.css',
            [],
            '18.8.0'
        );

        wp_enqueue_style(
            'bpmn-js-font',
            MOOX_BPMN_PLUGIN_URL.'node_modules/bpmn-js/dist/assets/bpmn-font/css/bpmn-embedded.css',
            [],
            MOOX_BPMN_VERSION
        );

        wp_enqueue_style(
            'moox-bpmn-block',
            MOOX_BPMN_PLUGIN_URL.'css/bpmn-block.css',
            ['wp-edit-blocks'],
            MOOX_BPMN_VERSION
        );
    }

    public function enqueue_frontend_assets()
    {
        $mode = isset($_GET['moox_bpmn_mode']) ? sanitize_text_field($_GET['moox_bpmn_mode']) : 'edit';

        // Base scripts & styles
        wp_enqueue_style(
            'bpmn-js-diagram',
            MOOX_BPMN_PLUGIN_URL.'node_modules/bpmn-js/dist/assets/diagram-js.css',
            [],
            '18.8.0'
        );
        wp_enqueue_style(
            'bpmn-js',
            MOOX_BPMN_PLUGIN_URL.'node_modules/bpmn-js/dist/assets/bpmn-js.css',
            [],
            '18.8.0'
        );

        wp_enqueue_style(
            'bpmn-js-bpmn-font',
            MOOX_BPMN_PLUGIN_URL.'node_modules/bpmn-js/dist/assets/bpmn-font/css/bpmn-embedded.css',
            [],
            '18.8.0'
        );

        wp_enqueue_style(
            'moox-bpmn-viewer',
            MOOX_BPMN_PLUGIN_URL.'css/bpmn-viewer.css',
            [],
            MOOX_BPMN_VERSION
        );

        // Enqueue the correct BPMN JS based on mode
        if ($mode === 'edit') {
            wp_enqueue_script(
                'bpmn-js-modeler',
                MOOX_BPMN_PLUGIN_URL.'node_modules/bpmn-js/dist/bpmn-modeler.development.js',
                [],
                '18.8.0',
                true
            );
        } else {
            wp_enqueue_script(
                'bpmn-js-navigated-viewer',
                MOOX_BPMN_PLUGIN_URL.'node_modules/bpmn-js/dist/bpmn-navigated-viewer.development.js',
                [],
                '18.8.0',
                true
            );

            wp_add_inline_script(
                'bpmn-js',
                '
                        window.BpmnJS = window.BpmnJS || BpmnJS;
                        window.BpmnModeler = window.BpmnModeler || (typeof BpmnJS !== "undefined" ? BpmnJS.Modeler : null);
                        window.BpmnViewer = window.BpmnViewer || (typeof BpmnJS !== "undefined" ? BpmnJS.Viewer : null);
                        window.BpmnNavigatedViewer = window.BpmnNavigatedViewer || (typeof BpmnJS !== "undefined" ? BpmnJS.NavigatedViewer : null);
                    ',
                'after'
            );
        }

        // Always enqueue our frontend viewer script
        wp_enqueue_script(
            'moox-bpmn-viewer',
            MOOX_BPMN_PLUGIN_URL.'src/bpmn-viewer.js',
            ['jquery'],
            MOOX_BPMN_VERSION,
            true
        );

        wp_localize_script('moox-bpmn-viewer', 'mooxBpmn', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('moox_bpmn_nonce'),
            'mode' => $mode,
        ]);
    }

    public function render_bpmn_block($attributes)
    {
        $media_id = intval($attributes['mediaId'] ?? 0);
        $mode = sanitize_text_field($attributes['mode'] ?? 'view');
        $height = sanitize_text_field($attributes['height'] ?? '500px');
        $data_mode = esc_attr($mode);
        $block_url = add_query_arg('moox_bpmn_mode', $data_mode, home_url($_SERVER['REQUEST_URI']));

        if (! $media_id) {
            return '<p>'.esc_html__('No BPMN file selected.', 'moox-bpmn').'</p>';
        }

        $file_url = wp_get_attachment_url($media_id);
        if (! $file_url) {
            return '<p>'.esc_html__('BPMN file not found.', 'moox-bpmn').'</p>';
        }

        ob_start();
        ?>
    <div class="moox-bpmn-viewer"
        data-media-id="<?php echo esc_attr($media_id); ?>"
        data-mode="<?php echo $data_mode; ?>"
        data-file-url="<?php echo esc_url($file_url); ?>"
        data-view-url="<?php echo esc_url($block_url); ?>"
        style="height: <?php echo esc_attr($height); ?>;">

        <?php if ($mode === 'edit') { ?>
            <div class="moox-bpmn-toolbar">
                <span class="moox-bpmn-title">
                    <?php esc_html_e('BPMN Editor', 'moox-bpmn'); ?>
                </span>

                <button class="moox-bpmn-save button button-primary">
                    <?php esc_html_e('Save BPMN', 'moox-bpmn'); ?>
                </button>
            </div>
        <?php } ?>

        <div class="moox-bpmn-container">
            <div class="moox-bpmn-loading">
                <?php esc_html_e('Loading BPMN diagram...', 'moox-bpmn'); ?>
            </div>
        </div>

    </div>
    <?php
        return ob_get_clean();
    }

    public function save_bpmn()
    {
        check_ajax_referer('moox_bpmn_nonce', 'nonce');

        $media_id = intval($_POST['mediaId'] ?? 0);
        $content = stripslashes($_POST['bpmnContent'] ?? '');

        if (! $media_id || $content === '') {
            wp_send_json_error(['message' => 'Invalid data received.']);
        }

        $file = get_attached_file($media_id);

        if (! $file) {
            wp_send_json_error([
                'message' => 'Attachment file path not found.',
                'media_id' => $media_id,
            ]);
        }

        // Attempt to fix permissions if file isn't writable
        if (! is_writable($file)) {
            @chmod($file, 0664);
        }

        if (! is_writable($file)) {
            wp_send_json_error([
                'message' => 'File is not writable.',
                'path' => $file,
            ]);
        }

        // Write new BPMN XML
        $saved = @file_put_contents($file, $content);

        if ($saved === false) {
            wp_send_json_error([
                'message' => 'Failed to write BPMN file.',
                'path' => $file,
            ]);
        }

        // Force WP to update attachment metadata (so block reloads correct content)
        $meta = wp_generate_attachment_metadata($media_id, $file);
        wp_update_attachment_metadata($media_id, $meta);

        wp_send_json_success([
            'message' => 'BPMN file saved successfully.',
            'bytes' => $saved,
            'path' => $file,
        ]);
    }
}

new Moox_BPMN_Plugin;
