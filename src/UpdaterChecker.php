<?php

namespace VeronaLabs\Updater;

use Puc_v4_Factory;

class UpdaterChecker
{
    public $plugin_slug;
    public $website_url;
    public $license_key;
    public $plugin_path;
    public $setting_page;

    /**
     * Check plugin update checker
     *
     * @param array $args
     */
    public function __construct($args = [])
    {
        // List of reQuire Keys
        $require_keys = [
            'plugin_slug',
            'website_url',
            'license_key',
            'setting_page',
            'plugin_path',
        ];

        // Check Validation Data
        $wp_error = false;

        // Check Every items
        foreach ($require_keys as $key) {

            // Check Validation Error
            if (!array_key_exists($key, $args)) {
                $wp_error = true;
            } else {
                // Added to variable
                $this->{$key} = $args[$key];
            }
        }

        // Check Product license is validate
        if ($wp_error === false) {
            if (empty($this->license_key) or $this->license_key == '') {
                add_action("after_plugin_row", [$this, 'plugin_update_message'], 10, 3);
            } else {

                // Check Plugin request only in plugins.php Wordpress Admin area
                add_action('admin_init', [$this, 'plugin_update_checker']);
            }
        }
    }

    /**
     * Check is WordPress Admin Plugins Page
     */
    public function isPluginPage()
    {
        if (is_admin() and ((strpos($_SERVER['REQUEST_URI'], 'plugins.php') !== false) or (strpos($_SERVER['REQUEST_URI'], 'plugin-install.php') !== false) or (isset($_REQUEST['slug']) and $_REQUEST['slug'] == $this->plugin_slug))) {
            return true;
        }
    }

    /**
     * Plugin Update Checker
     */
    public function plugin_update_checker()
    {
        if (!$this->isPluginPage()) {
            return;
        }

        // Get This Product License
        $license_key = (isset($this->license_key) ? $this->license_key : '');

        // Prepare Request Link
        $request = add_query_arg([
            'plugin-name' => $this->plugin_slug,
            'license_key' => $license_key,
            'website'     => get_bloginfo('url'),
            'email'       => get_bloginfo('admin_email'),
        ], $this->website_url . '/wp-json/plugins/v1/download');

        // Request To WebSite
        $response = wp_remote_get($request);

        // Check Error Request
        if (is_wp_error($response)) {
            return;
        }

        // Get Data and Update
        if (wp_remote_retrieve_response_code($response) == '200') {

            // Get Body Response
            $body = wp_remote_retrieve_body($response);

            // Check Json Encoding
            $json_body = json_decode($body, true);

            if ($json_body != null) {

                // Check item require
                $require_item = array('name', 'version', 'download_url');

                // Create new validation
                $error = false;

                // Check every item
                foreach ($require_item as $key) {
                    if (array_key_exists($key, $json_body)) {
                        if (wp_strip_all_tags($json_body[$key]) == "") {
                            $error = true;
                        }
                    } else {
                        $error = true;
                    }
                }

                // Remote To server
                if (!$error) {
                    Puc_v4_Factory::buildUpdateChecker($request, $this->plugin_path, $this->plugin_slug);
                } else {
                    add_action("after_plugin_row", array($this, 'showPluginRowNotice'), 10, 3);
                }
            }
        }
    }

    /**
     * Plugin update message
     *
     * @param $plugin_file
     * @param $plugin_data
     * @param $status
     *
     * @see https://developer.wordpress.org/reference/hooks/after_plugin_row/
     */
    public function showPluginRowNotice($plugin_file, $plugin_data, $status)
    {
        if ($plugin_file == $this->plugin_slug . '/' . $this->plugin_slug . '.php') {
            echo '<tr style="background: #fcf9e8 !important;"><td>&nbsp;</td><td colspan="3">' . sprintf(__('<i>Automatic update is unavailable for the %s plugin.</i><br>To enable updates, please enter your license key on the <a href="%s">setting page</a> If you don\'t have a license key, please see <a href="%s">details & pricing</a>', $this->plugin_slug), $plugin_data['Name'], $this->setting_page, $this->website_url) . '</td></tr>';
        }
    }
}