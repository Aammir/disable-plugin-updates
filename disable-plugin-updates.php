<?php
/*
Plugin Name: Disable Plugin Updates
Description: Adds a page to the WP Admin menu to disable auto-updates for specific plugins.
Version: 1.0
Author: Aamir Hussain
Author URI: https://aammir.github.io
*/

// Hook to add submenu page under Plugins menu
add_action('admin_menu', 'dpu_add_submenu_page');

function dpu_add_submenu_page() {
    add_submenu_page(
        'plugins.php',
        'Disable Plugin Updates',
        'Disable Plugin Updates',
        'manage_options',
        'disable-plugin-updates',
        'dpu_admin_page'
    );
}

// Display admin page content
function dpu_admin_page() {
    // Save settings if form is submitted
    if (isset($_POST['dpu_save_settings'])) {
        check_admin_referer('dpu_save_settings');
        $disabled_plugins = isset($_POST['disabled_plugins']) ? array_map('sanitize_text_field', $_POST['disabled_plugins']) : [];
        update_option('dpu_disabled_plugins', $disabled_plugins);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    // Get all plugins
    $all_plugins = get_plugins();
    $disabled_plugins = get_option('dpu_disabled_plugins', []);

    ?>
    <div class="wrap">
        <h1>Disable Plugin Updates</h1>
        <form method="post" action="">
            <?php wp_nonce_field('dpu_save_settings'); ?>
            <table class="form-table">
                <tbody>
                    <?php foreach ($all_plugins as $plugin_file => $plugin_data): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($plugin_data['Name']); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="disabled_plugins[]" value="<?php echo esc_attr($plugin_file); ?>" <?php checked(in_array($plugin_file, $disabled_plugins)); ?>>
                                    Disable updates
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php submit_button('Save Settings', 'primary', 'dpu_save_settings'); ?>
        </form>
    </div>
    <?php
}

// Hook to disable plugin updates
add_filter('site_transient_update_plugins', 'dpu_disable_plugin_updates');

function dpu_disable_plugin_updates($value) {
    $disabled_plugins = get_option('dpu_disabled_plugins', []);
    if (!empty($disabled_plugins)) {
        foreach ($disabled_plugins as $plugin) {
            if (isset($value->response[$plugin])) {
                unset($value->response[$plugin]);
            }
        }
    }
    return $value;
}

// Hook to disable plugin update notifications
add_filter('transient_update_plugins', 'dpu_disable_plugin_updates');

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'dpu_add_action_links');

function dpu_add_action_links($links) {
    $settings_link = '<a href="admin.php?page=disable-plugin-updates">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
