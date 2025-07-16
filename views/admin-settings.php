<?php
/**
 * Example Module Admin Settings View
 * 
 * This view creates a proper admin interface for module settings
 * with organized sections, proper form controls, and validation.
 */

// Ensure this file is being included properly
if (!defined('ABSPATH') && !isset($this)) {
    exit('Direct access not allowed');
}

// Get current settings
$settings = $this->getSettings();
$sections = $this->getSettingsSections();

// Handle form submission
if (isset($_POST['save_settings'])) {
    $new_settings = $this->sanitizeSettings($_POST);
    if ($this->updateSettings($new_settings)) {
        $success_message = 'Settings saved successfully!';
        $settings = array_merge($settings, $new_settings);
    } else {
        $error_message = 'Failed to save settings. Please try again.';
    }
}

// Generate CSRF token
$csrf_token = function_exists('wp_create_nonce') ? wp_create_nonce('example_module_settings') : 
    (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : bin2hex(random_bytes(16)));
?>

<div class="example-module-admin-settings">
    
    <!-- Header -->
    <div class="settings-header">
        <h1>Example Module Settings</h1>
        <p class="description">Configure your Example Module settings below. Changes will take effect immediately.</p>
    </div>

    <!-- Messages -->
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success">
            <p><strong><?php echo esc_html($success_message); ?></strong></p>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="notice notice-error">
            <p><strong><?php echo esc_html($error_message); ?></strong></p>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="settings-form">
        <!-- CSRF Protection -->
        <input type="hidden" name="csrf_token" value="<?php echo esc_attr($csrf_token); ?>">
        <input type="hidden" name="save_settings" value="1">

        <!-- Settings Navigation -->
        <div class="settings-navigation">
            <ul class="nav-tabs">
                <li><a href="#general" class="nav-tab nav-tab-active">General</a></li>
                <li><a href="#display" class="nav-tab">Display</a></li>
                <li><a href="#notifications" class="nav-tab">Notifications</a></li>
                <li><a href="#uploads" class="nav-tab">File Uploads</a></li>
                <li><a href="#security" class="nav-tab">Security</a></li>
                <li><a href="#limits" class="nav-tab">Limits</a></li>
                <li><a href="#performance" class="nav-tab">Performance</a></li>
                <li><a href="#development" class="nav-tab">Development</a></li>
            </ul>
        </div>

        <!-- Settings Sections -->
        <div class="settings-content">

            <!-- General Settings -->
            <div id="general" class="settings-section active">
                <h2><i class="fas fa-cog"></i> General Settings</h2>
                <p class="section-description">Basic module configuration options</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enabled">Enable Module</label>
                        </th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" id="enabled" name="enabled" value="1" 
                                       <?php checked($settings['enabled'] ?? true, true); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">Enable or disable the example module functionality</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Display Options -->
            <div id="display" class="settings-section">
                <h2><i class="fas fa-eye"></i> Display Options</h2>
                <p class="section-description">Customize how content is displayed</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="welcome_title">Welcome Title</label>
                        </th>
                        <td>
                            <input type="text" id="welcome_title" name="welcome_title" 
                                   value="<?php echo esc_attr($settings['welcome_title'] ?? 'Welcome to Example Module!'); ?>"
                                   class="regular-text" maxlength="255" required>
                            <p class="description">Title displayed in widgets and forms</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="welcome_message">Welcome Message</label>
                        </th>
                        <td>
                            <textarea id="welcome_message" name="welcome_message" rows="4" cols="50"
                                      class="large-text" maxlength="1000"><?php echo esc_textarea($settings['welcome_message'] ?? 'This is a demonstration of PhPstrap module capabilities.'); ?></textarea>
                            <p class="description">Message displayed in widgets and forms</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="show_date">Show Date</label>
                        </th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" id="show_date" name="show_date" value="1"
                                       <?php checked($settings['show_date'] ?? true, true); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">Display current date in widgets</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="widget_style">Widget Style</label>
                        </th>
                        <td>
                            <select id="widget_style" name="widget_style">
                                <option value="default" <?php selected($settings['widget_style'] ?? 'default', 'default'); ?>>Default</option>
                                <option value="minimal" <?php selected($settings['widget_style'] ?? 'default', 'minimal'); ?>>Minimal</option>
                                <option value="fancy" <?php selected($settings['widget_style'] ?? 'default', 'fancy'); ?>>Fancy</option>
                            </select>
                            <p class="description">Default style for module widgets</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Notifications -->
            <div id="notifications" class="settings-section">
                <h2><i class="fas fa-bell"></i> Notifications</h2>
                <p class="section-description">Email and alert configuration</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="admin_notifications">Admin Notifications</label>
                        </th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" id="admin_notifications" name="admin_notifications" value="1"
                                       <?php checked($settings['admin_notifications'] ?? true, true); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">Send email notifications to administrators</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="notification_email">Notification Email</label>
                        </th>
                        <td>
                            <input type="email" id="notification_email" name="notification_email" 
                                   value="<?php echo esc_attr($settings['notification_email'] ?? ''); ?>"
                                   class="regular-text">
                            <p class="description">Email address for admin notifications</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- File Uploads -->
            <div id="uploads" class="settings-section">
                <h2><i class="fas fa-upload"></i> File Uploads</h2>
                <p class="section-description">File upload settings and restrictions</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="allow_uploads">Allow File Uploads</label>
                        </th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" id="allow_uploads" name="allow_uploads" value="1"
                                       <?php checked($settings['allow_uploads'] ?? false, true); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">Enable file upload functionality</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_upload_size">Maximum Upload Size</label>
                        </th>
                        <td>
                            <input type="number" id="max_upload_size" name="max_upload_size" 
                                   value="<?php echo esc_attr($settings['max_upload_size'] ?? 5242880); ?>"
                                   min="1024" max="52428800" class="small-text">
                            <span class="description">bytes (<?php echo $this->formatFileSize($settings['max_upload_size'] ?? 5242880); ?>)</span>
                            <p class="description">Maximum file size for uploads</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="allowed_file_types">Allowed File Types</label>
                        </th>
                        <td>
                            <?php
                            $allowed_types = $settings['allowed_file_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                            $file_types = [
                                'jpg' => 'JPEG Images',
                                'jpeg' => 'JPEG Images', 
                                'png' => 'PNG Images',
                                'gif' => 'GIF Images',
                                'pdf' => 'PDF Documents',
                                'doc' => 'Word Documents',
                                'docx' => 'Word Documents',
                                'txt' => 'Text Files'
                            ];
                            ?>
                            <fieldset>
                                <?php foreach ($file_types as $ext => $label): ?>
                                    <label>
                                        <input type="checkbox" name="allowed_file_types[]" value="<?php echo esc_attr($ext); ?>"
                                               <?php checked(in_array($ext, $allowed_types), true); ?>>
                                        <?php echo esc_html($label); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description">File extensions allowed for upload</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Security -->
            <div id="security" class="settings-section">
                <h2><i class="fas fa-shield-alt"></i> Security</h2>
                <p class="section-description">Security and spam protection settings</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_captcha">Enable CAPTCHA</label>
                        </th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" id="enable_captcha" name="enable_captcha" value="1"
                                       <?php checked($settings['enable_captcha'] ?? true, true); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">Enable CAPTCHA protection for forms</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="rate_limit">Rate Limit</label>
                        </th>
                        <td>
                            <input type="number" id="rate_limit" name="rate_limit" 
                                   value="<?php echo esc_attr($settings['rate_limit'] ?? 5); ?>"
                                   min="1" max="100" class="small-text">
                            <p class="description">Maximum submissions per time window</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="rate_limit_window">Rate Limit Window</label>
                        </th>
                        <td>
                            <input type="number" id="rate_limit_window" name="rate_limit_window" 
                                   value="<?php echo esc_attr($settings['rate_limit_window'] ?? 3600); ?>"
                                   min="60" max="86400" class="small-text">
                            <span class="description">seconds (<?php echo $this->formatDuration($settings['rate_limit_window'] ?? 3600); ?>)</span>
                            <p class="description">Time window for rate limiting</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Limits -->
            <div id="limits" class="settings-section">
                <h2><i class="fas fa-chart-bar"></i> Limits</h2>
                <p class="section-description">Data and content limitations</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="max_items">Maximum Items</label>
                        </th>
                        <td>
                            <input type="number" id="max_items" name="max_items" 
                                   value="<?php echo esc_attr($settings['max_items'] ?? 10); ?>"
                                   min="1" max="100" class="small-text">
                            <p class="description">Maximum number of items to display</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_content_length">Maximum Content Length</label>
                        </th>
                        <td>
                            <input type="number" id="max_content_length" name="max_content_length" 
                                   value="<?php echo esc_attr($settings['max_content_length'] ?? 2000); ?>"
                                   min="100" max="10000" class="small-text">
                            <p class="description">Maximum characters allowed in content fields</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Performance -->
            <div id="performance" class="settings-section">
                <h2><i class="fas fa-tachometer-alt"></i> Performance</h2>
                <p class="section-description">Caching and optimization settings</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cache_duration">Cache Duration</label>
                        </th>
                        <td>
                            <input type="number" id="cache_duration" name="cache_duration" 
                                   value="<?php echo esc_attr($settings['cache_duration'] ?? 3600); ?>"
                                   min="0" max="86400" class="small-text">
                            <span class="description">seconds (<?php echo $this->formatDuration($settings['cache_duration'] ?? 3600); ?>)</span>
                            <p class="description">How long to cache module data</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Development -->
            <div id="development" class="settings-section">
                <h2><i class="fas fa-code"></i> Development</h2>
                <p class="section-description">Development and debugging options</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="debug_mode">Debug Mode</label>
                        </th>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" id="debug_mode" name="debug_mode" value="1"
                                       <?php checked($settings['debug_mode'] ?? false, true); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="description">Enable debug logging and verbose error messages</p>
                            <?php if ($settings['debug_mode'] ?? false): ?>
                                <p class="warning"><strong>Warning:</strong> Debug mode is enabled. Disable this in production.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        <!-- Submit Button -->
        <div class="settings-footer">
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings">
                <button type="button" class="button button-secondary" onclick="resetToDefaults()">Reset to Defaults</button>
            </p>
        </div>

    </form>

</div>

<!-- Styles for Admin Settings -->
<style>
.example-module-admin-settings {
    max-width: 1200px;
    margin: 20px 0;
}

.settings-header {
    margin-bottom: 30px;
}

.settings-header h1 {
    font-size: 24px;
    margin: 0 0 10px 0;
}

.settings-header .description {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.notice {
    background: #fff;
    border-left: 4px solid;
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    margin: 5px 15px 2px;
    padding: 1px 12px;
}

.notice-success {
    border-left-color: #46b450;
}

.notice-error {
    border-left-color: #dc3232;
}

.settings-navigation {
    margin-bottom: 20px;
    border-bottom: 1px solid #ccc;
}

.nav-tabs {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
}

.nav-tabs li {
    margin: 0;
}

.nav-tab {
    display: inline-block;
    padding: 10px 15px;
    text-decoration: none;
    color: #0073aa;
    border: 1px solid transparent;
    border-bottom: none;
    background: #f1f1f1;
    margin-right: 5px;
    transition: all 0.3s ease;
}

.nav-tab:hover {
    background: #e1e1e1;
    color: #005177;
}

.nav-tab-active {
    background: #fff;
    border-color: #ccc;
    color: #000;
    position: relative;
    top: 1px;
}

.settings-content {
    background: #fff;
    border: 1px solid #ccc;
    padding: 20px;
}

.settings-section {
    display: none;
}

.settings-section.active {
    display: block;
}

.settings-section h2 {
    font-size: 20px;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-description {
    color: #666;
    margin-bottom: 20px;
    font-style: italic;
}

.form-table {
    width: 100%;
    border-collapse: collapse;
}

.form-table th {
    width: 200px;
    padding: 20px 10px 20px 0;
    vertical-align: top;
    text-align: left;
    font-weight: 600;
}

.form-table td {
    padding: 20px 0;
    border-bottom: 1px solid #f1f1f1;
}

.form-table .description {
    font-size: 13px;
    color: #666;
    margin: 5px 0 0 0;
}

.form-table .warning {
    color: #d63638;
    font-weight: 500;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #0073aa;
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

.regular-text {
    width: 25em;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.large-text {
    width: 99%;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-family: inherit;
    resize: vertical;
}

.small-text {
    width: 80px;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

select {
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
    min-width: 150px;
}

.settings-footer {
    background: #f1f1f1;
    padding: 15px 20px;
    margin: 20px -20px -20px -20px;
    border-top: 1px solid #ddd;
}

.button {
    display: inline-block;
    text-decoration: none;
    font-size: 13px;
    line-height: 2.15384615;
    min-height: 30px;
    margin: 0;
    padding: 0 10px;
    cursor: pointer;
    border-width: 1px;
    border-style: solid;
    border-radius: 3px;
    white-space: nowrap;
    box-sizing: border-box;
}

.button-primary {
    background: #0073aa;
    border-color: #0073aa;
    color: #fff;
}

.button-secondary {
    background: #f7f7f7;
    border-color: #ccc;
    color: #555;
}

.button:hover {
    background: #005177;
    border-color: #005177;
}

.button-secondary:hover {
    background: #fafafa;
    border-color: #999;
}

@media (max-width: 768px) {
    .nav-tabs {
        flex-direction: column;
    }
    
    .nav-tab {
        margin-bottom: 5px;
        margin-right: 0;
    }
    
    .form-table th {
        width: auto;
        display: block;
        padding-bottom: 5px;
    }
    
    .form-table td {
        display: block;
        padding-top: 5px;
    }
    
    .regular-text,
    .large-text {
        width: 100%;
        box-sizing: border-box;
    }
}
</style>

<!-- JavaScript for Tab Navigation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab navigation
    const tabs = document.querySelectorAll('.nav-tab');
    const sections = document.querySelectorAll('.settings-section');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and sections
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            sections.forEach(s => s.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('nav-tab-active');
            
            // Show corresponding section
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });
    
    // Reset to defaults function
    window.resetToDefaults = function() {
        if (confirm('Are you sure you want to reset all settings to their default values? This action cannot be undone.')) {
            // Reset form to default values
            document.getElementById('enabled').checked = true;
            document.getElementById('welcome_title').value = 'Welcome to Example Module!';
            document.getElementById('welcome_message').value = 'This is a demonstration of PhPstrap module capabilities.';
            document.getElementById('show_date').checked = true;
            document.getElementById('widget_style').value = 'default';
            document.getElementById('admin_notifications').checked = true;
            document.getElementById('notification_email').value = '';
            document.getElementById('allow_uploads').checked = false;
            document.getElementById('max_upload_size').value = '5242880';
            document.getElementById('enable_captcha').checked = true;
            document.getElementById('rate_limit').value = '5';
            document.getElementById('rate_limit_window').value = '3600';
            document.getElementById('max_items').value = '10';
            document.getElementById('max_content_length').value = '2000';
            document.getElementById('cache_duration').value = '3600';
            document.getElementById('debug_mode').checked = false;
            
            // Reset file type checkboxes
            const fileTypeCheckboxes = document.querySelectorAll('input[name="allowed_file_types[]"]');
            fileTypeCheckboxes.forEach(checkbox => {
                checkbox.checked = ['jpg', 'jpeg', 'png', 'gif', 'pdf'].includes(checkbox.value);
            });
        }
    };
});
</script>

<?php
// Helper functions for this view
function selected($current, $value) {
    return $current === $value ? 'selected="selected"' : '';
}

function checked($current, $value) {
    return $current == $value ? 'checked="checked"' : '';
}

function esc_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_html($text) {
    return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
}

function esc_textarea($text) {
    return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
}
?>