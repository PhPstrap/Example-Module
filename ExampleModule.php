<?php

namespace PhPstrap\Modules\Example;

use Exception;
use PDO;

/**
 * Example Module for PhPstrap
 * 
 * This is a complete example showing how to create a PhPstrap module.
 * It demonstrates all the core concepts including:
 * - Module initialization and setup
 * - Settings management
 * - Database integration
 * - Hook system usage
 * - Admin interface integration
 * - Asset loading
 * - Shortcode/widget functionality
 */
class ExampleModule
{
    private $pdo;
    private $settings;
    private $module_path;
    private $version = '1.0.0';
    
    /**
     * Constructor - Initialize the module
     */
    public function __construct()
    {
        // Set the module path
        $this->module_path = dirname(__FILE__);
        
        // Load module settings from database
        $this->loadSettings();
        
        // Initialize database connection
        $this->initDatabase();
    }
    
    /**
     * Initialize the module
     * This is called by PhPstrap after the module is loaded
     */
    public function init()
    {
        // Only proceed if module is enabled
        if (!$this->settings['enabled']) {
            return;
        }
        
        // Register all hooks
        $this->registerHooks();
        
        // Load frontend assets if needed
        if ($this->shouldLoadAssets()) {
            $this->loadAssets();
        }
        
        // Register admin functionality if user is admin
        if ($this->isAdmin()) {
            $this->registerAdminMenu();
        }
        
        // Register shortcodes
        $this->registerShortcodes();
    }
    
    /**
     * Load module settings from database with fallback to defaults
     */
    private function loadSettings()
    {
        try {
            // Check if PhPstrap database function exists
            if (function_exists('getDbConnection')) {
                $this->pdo = getDbConnection();
                
                // Try to load settings from modules table
                $stmt = $this->pdo->prepare("
                    SELECT settings FROM modules 
                    WHERE name = 'example_module' AND enabled = 1
                ");
                $stmt->execute();
                $result = $stmt->fetch();
                
                if ($result && !empty($result['settings'])) {
                    $loadedSettings = json_decode($result['settings'], true);
                    
                    // Ensure we have simple values, not complex objects
                    $this->settings = $this->simplifySettings($loadedSettings);
                } else {
                    $this->settings = $this->getDefaultSettings();
                }
            } else {
                // Fallback to default settings if no database
                $this->settings = $this->getDefaultSettings();
            }
        } catch (Exception $e) {
            // Log error and use defaults
            error_log("Example Module: Error loading settings - " . $e->getMessage());
            $this->settings = $this->getDefaultSettings();
        }
    }
    
    /**
     * Simplify complex settings objects to simple values
     */
    private function simplifySettings($settings)
    {
        $simplified = [];
        
        foreach ($settings as $key => $value) {
            // If it's an object with a 'default' or 'value' property, extract that
            if (is_array($value)) {
                if (isset($value['default'])) {
                    $simplified[$key] = $value['default'];
                } elseif (isset($value['value'])) {
                    $simplified[$key] = $value['value'];
                } else {
                    // If it's just a simple array (like file types), keep it
                    $simplified[$key] = $value;
                }
            } else {
                // Simple value, keep as-is
                $simplified[$key] = $value;
            }
        }
        
        return $simplified;
    }
    
    /**
     * Get default module settings
     * These are used when the module is first installed or if database settings fail
     */
    private function getDefaultSettings()
    {
        return [
            'enabled' => true,
            'welcome_title' => 'Welcome to Example Module!',
            'welcome_message' => 'This is a demonstration of PhPstrap module capabilities.',
            'show_date' => true,
            'widget_style' => 'default',
            'cache_duration' => 3600,
            'admin_notifications' => true,
            'notification_email' => '',
            'max_items' => 10,
            'max_content_length' => 2000,
            'allow_uploads' => false,
            'max_upload_size' => 5242880,
            'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
            'enable_captcha' => true,
            'rate_limit' => 5,
            'rate_limit_window' => 3600,
            'debug_mode' => false
        ];
    }
    
    /**
     * Initialize database connection if available
     */
    private function initDatabase()
    {
        if (!$this->pdo && function_exists('getDbConnection')) {
            try {
                $this->pdo = getDbConnection();
            } catch (Exception $e) {
                error_log("Example Module: Database connection failed - " . $e->getMessage());
            }
        }
    }
    
    /**
     * Register module hooks
     * Hooks allow your module to interact with PhPstrap core and other modules
     */
    private function registerHooks()
    {
        // Action hooks - execute code at specific points
        add_action('example_module_display', [$this, 'displayWidget']);
        add_action('example_module_save_data', [$this, 'saveData']);
        add_action('wp_footer', [$this, 'addFooterContent']); // If WordPress compatibility
        
        // Filter hooks - modify data
        add_filter('example_module_content', [$this, 'filterContent']);
        add_filter('example_module_settings', [$this, 'filterSettings']);
        
        // Custom module hooks for other modules to use
        add_action('init', [$this, 'triggerModuleHooks']);
    }
    
    /**
     * Load module assets (CSS/JS) when needed
     */
    private function loadAssets()
    {
        $module_url = '/modules/example_module/assets/';
        
        // Add CSS
        if (file_exists($this->module_path . '/assets/example-module.css')) {
            echo '<link rel="stylesheet" href="' . $module_url . 'example-module.css?v=' . $this->version . '">';
        }
        
        // Add JavaScript
        if (file_exists($this->module_path . '/assets/example-module.js')) {
            echo '<script src="' . $module_url . 'example-module.js?v=' . $this->version . '"></script>';
        }
    }
    
    /**
     * Register admin menu items
     */
    private function registerAdminMenu()
    {
        // Register admin menu items
        $this->registerAdminMenuItems();
    }
    
    /**
     * Register shortcodes for use in content
     */
    private function registerShortcodes()
    {
        // Register shortcode if PhPstrap supports them
        if (function_exists('add_shortcode')) {
            add_shortcode('example_widget', [$this, 'shortcodeHandler']);
            add_shortcode('example_data', [$this, 'dataShortcode']);
        }
    }
    
    /**
     * Main widget/content display function
     */
    public function displayWidget($attributes = [])
    {
        if (!$this->settings['enabled']) {
            return '';
        }
        
        // Merge attributes with defaults
        $attributes = array_merge([
            'style' => $this->settings['widget_style'],
            'show_date' => $this->settings['show_date'],
            'title' => $this->settings['welcome_title']
        ], $attributes);
        
        // Start output buffering
        ob_start();
        
        // Include view template
        if (file_exists($this->module_path . '/views/widget.php')) {
            include $this->module_path . '/views/widget.php';
        } else {
            // Fallback content
            echo $this->getFallbackContent($attributes);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Handle shortcode [example_widget]
     */
    public function shortcodeHandler($attributes)
    {
        // Parse shortcode attributes
        $attributes = shortcode_atts([
            'title' => $this->settings['welcome_title'],
            'style' => 'default',
            'show_date' => 'true'
        ], $attributes);
        
        // Convert string boolean to actual boolean
        $attributes['show_date'] = ($attributes['show_date'] === 'true');
        
        return $this->displayWidget($attributes);
    }
    
    /**
     * Handle data shortcode [example_data]
     */
    public function dataShortcode($attributes)
    {
        $attributes = shortcode_atts([
            'type' => 'latest',
            'limit' => $this->settings['max_items']
        ], $attributes);
        
        return $this->getDataDisplay($attributes['type'], $attributes['limit']);
    }
    
    /**
     * Save data to database
     */
    public function saveData($data)
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO example_module_data 
                (title, content, data_type, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $data['title'] ?? 'Untitled',
                $data['content'] ?? '',
                $data['type'] ?? 'general'
            ]);
            
            if ($result) {
                $id = $this->pdo->lastInsertId();
                
                // Trigger hook for other modules
                do_action('example_module_data_saved', $id, $data);
                
                return $id;
            }
            
        } catch (Exception $e) {
            error_log("Example Module: Error saving data - " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Get data for display
     */
    private function getDataDisplay($type = 'latest', $limit = 10)
    {
        if (!$this->pdo) {
            return '<p>No data available.</p>';
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM example_module_data 
                WHERE data_type = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$type, (int)$limit]);
            $results = $stmt->fetchAll();
            
            if (empty($results)) {
                return '<p>No ' . htmlspecialchars($type) . ' data found.</p>';
            }
            
            $output = '<div class="example-module-data">';
            foreach ($results as $row) {
                $output .= '<div class="data-item">';
                $output .= '<h4>' . htmlspecialchars($row['title']) . '</h4>';
                $output .= '<p>' . htmlspecialchars($row['content']) . '</p>';
                $output .= '<small>Posted: ' . $row['created_at'] . '</small>';
                $output .= '</div>';
            }
            $output .= '</div>';
            
            return $output;
            
        } catch (Exception $e) {
            error_log("Example Module: Error getting data - " . $e->getMessage());
            return '<p>Error loading data.</p>';
        }
    }
    
    /**
     * Filter content hook example
     */
    public function filterContent($content)
    {
        // Example: Add a signature to content
        if ($this->settings['enabled'] && !empty($content)) {
            $content .= "\n\n<!-- Generated by Example Module -->";
        }
        
        return $content;
    }
    
    /**
     * Filter settings hook example
     */
    public function filterSettings($settings)
    {
        // Allow other modules to modify settings
        return apply_filters('example_module_modify_settings', $settings);
    }
    
    /**
     * Add content to footer
     */
    public function addFooterContent()
    {
        if ($this->settings['enabled']) {
            echo '<!-- Example Module ' . $this->version . ' -->';
        }
    }
    
    /**
     * Trigger custom hooks for other modules
     */
    public function triggerModuleHooks()
    {
        // Allow other modules to hook into this module
        do_action('example_module_init', $this);
        do_action('example_module_loaded', $this->settings);
    }
    
    /**
     * Get fallback content when view file is missing
     */
    private function getFallbackContent($attributes)
    {
        $output = '<div class="example-module-widget ' . $attributes['style'] . '">';
        $output .= '<h3>' . htmlspecialchars($attributes['title']) . '</h3>';
        $output .= '<p>' . htmlspecialchars($this->settings['welcome_message']) . '</p>';
        
        if ($attributes['show_date']) {
            $output .= '<p><small>Today: ' . date('Y-m-d H:i:s') . '</small></p>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Check if assets should be loaded on current page
     */
    private function shouldLoadAssets()
    {
        // Logic to determine if current page needs module assets
        // Could check for shortcodes, page templates, etc.
        
        // Simple example: load on pages containing our shortcode
        global $post;
        if (isset($post->post_content)) {
            return (strpos($post->post_content, '[example_widget]') !== false ||
                    strpos($post->post_content, '[example_data]') !== false);
        }
        
        return true; // Default: always load (you may want to be more selective)
    }
    
    /**
     * Check if current user is admin
     */
    private function isAdmin()
    {
        // This depends on PhPstrap's user system
        if (function_exists('current_user_can')) {
            return current_user_can('manage_options');
        }
        
        // Fallback check
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }
    
    /**
     * Admin interface methods
     */
    
    /**
     * Get module settings for admin display
     */
    public function getSettings()
    {
        return $this->settings;
    }
    
    /**
     * Get raw setting values for PhPstrap's settings UI
     * This method ensures PhPstrap gets simple values, not complex objects
     */
    public function getSettingValues()
    {
        return [
            'enabled' => $this->settings['enabled'] ?? true,
            'welcome_title' => $this->settings['welcome_title'] ?? 'Welcome to Example Module!',
            'welcome_message' => $this->settings['welcome_message'] ?? 'This is a demonstration of PhPstrap module capabilities.',
            'show_date' => $this->settings['show_date'] ?? true,
            'widget_style' => $this->settings['widget_style'] ?? 'default',
            'cache_duration' => $this->settings['cache_duration'] ?? 3600,
            'admin_notifications' => $this->settings['admin_notifications'] ?? true,
            'notification_email' => $this->settings['notification_email'] ?? '',
            'max_items' => $this->settings['max_items'] ?? 10,
            'max_content_length' => $this->settings['max_content_length'] ?? 2000,
            'allow_uploads' => $this->settings['allow_uploads'] ?? false,
            'max_upload_size' => $this->settings['max_upload_size'] ?? 5242880,
            'allowed_file_types' => $this->settings['allowed_file_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
            'enable_captcha' => $this->settings['enable_captcha'] ?? true,
            'rate_limit' => $this->settings['rate_limit'] ?? 5,
            'rate_limit_window' => $this->settings['rate_limit_window'] ?? 3600,
            'debug_mode' => $this->settings['debug_mode'] ?? false
        ];
    }
    
    /**
     * Magic method to handle property access
     * This ensures that when PhPstrap accesses $module->settings, it gets simple values
     */
    public function __get($property)
    {
        if ($property === 'settings') {
            return $this->getSettingValues();
        }
        
        return null;
    }
    
    /**
     * Update module settings from admin
     */
    public function updateSettings($new_settings)
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            // Validate settings before saving
            $validated_settings = $this->validateSettings($new_settings);
            
            // Merge with existing settings
            $this->settings = array_merge($this->settings, $validated_settings);
            
            // Ensure we're storing simple values, not complex objects
            $simple_settings = $this->getSettingValues();
            
            // Save to database
            $stmt = $this->pdo->prepare("
                UPDATE modules SET settings = ? WHERE name = 'example_module'
            ");
            $result = $stmt->execute([json_encode($simple_settings)]);
            
            if ($result) {
                // Trigger hook for settings update
                do_action('example_module_settings_updated', $this->settings);
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Example Module: Error updating settings - " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Validate settings input
     */
    private function validateSettings($settings)
    {
        $validated = [];
        
        // Validate each setting
        if (isset($settings['enabled'])) {
            $validated['enabled'] = (bool)$settings['enabled'];
        }
        
        if (isset($settings['welcome_title'])) {
            $validated['welcome_title'] = sanitize_text_field($settings['welcome_title']);
        }
        
        if (isset($settings['welcome_message'])) {
            $validated['welcome_message'] = sanitize_textarea_field($settings['welcome_message']);
        }
        
        if (isset($settings['show_date'])) {
            $validated['show_date'] = (bool)$settings['show_date'];
        }
        
        if (isset($settings['widget_style'])) {
            $allowed_styles = ['default', 'minimal', 'fancy'];
            $validated['widget_style'] = in_array($settings['widget_style'], $allowed_styles) 
                ? $settings['widget_style'] 
                : 'default';
        }
        
        if (isset($settings['cache_duration'])) {
            $validated['cache_duration'] = max(0, (int)$settings['cache_duration']);
        }
        
        if (isset($settings['max_items'])) {
            $validated['max_items'] = max(1, min(100, (int)$settings['max_items']));
        }
        
        return $validated;
    }
    
    /**
     * Get module statistics for admin dashboard
     */
    public function getStats()
    {
        if (!$this->pdo) {
            return null;
        }
        
        try {
            $stats = [];
            
            // Count total data items
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM example_module_data");
            $stats['total_items'] = $stmt->fetchColumn();
            
            // Count by type
            $stmt = $this->pdo->query("
                SELECT data_type, COUNT(*) as count 
                FROM example_module_data 
                GROUP BY data_type
            ");
            $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Recent activity
            $stmt = $this->pdo->query("
                SELECT COUNT(*) FROM example_module_data 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stats['recent_items'] = $stmt->fetchColumn();
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Example Module: Error getting stats - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Module cleanup/maintenance
     */
    public function cleanup()
    {
        if (!$this->pdo) {
            return;
        }
        
        try {
            // Clean up old data if needed
            $cleanup_days = 365; // Keep data for 1 year
            
            $stmt = $this->pdo->prepare("
                DELETE FROM example_module_data 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$cleanup_days]);
            
            // Log cleanup
            error_log("Example Module: Cleanup completed");
            
        } catch (Exception $e) {
            error_log("Example Module: Cleanup error - " . $e->getMessage());
        }
    }
    
    /**
     * Render admin settings page
     */
    public function renderAdminSettings()
    {
        // Include the admin settings view
        if (file_exists($this->module_path . '/views/admin-settings.php')) {
            include $this->module_path . '/views/admin-settings.php';
        } else {
            echo '<div class="notice notice-error"><p>Admin settings view not found.</p></div>';
        }
    }
    
    /**
     * Get settings sections configuration
     */
    public function getSettingsSections()
    {
        return [
            'general' => [
                'title' => 'General Settings',
                'description' => 'Basic module configuration options',
                'icon' => 'fas fa-cog'
            ],
            'display' => [
                'title' => 'Display Options', 
                'description' => 'Customize how content is displayed',
                'icon' => 'fas fa-eye'
            ],
            'notifications' => [
                'title' => 'Notifications',
                'description' => 'Email and alert configuration',
                'icon' => 'fas fa-bell'
            ],
            'uploads' => [
                'title' => 'File Uploads',
                'description' => 'File upload settings and restrictions',
                'icon' => 'fas fa-upload'
            ],
            'security' => [
                'title' => 'Security',
                'description' => 'Security and spam protection settings',
                'icon' => 'fas fa-shield-alt'
            ],
            'limits' => [
                'title' => 'Limits',
                'description' => 'Data and content limitations',
                'icon' => 'fas fa-chart-bar'
            ],
            'performance' => [
                'title' => 'Performance',
                'description' => 'Caching and optimization settings',
                'icon' => 'fas fa-tachometer-alt'
            ],
            'development' => [
                'title' => 'Development',
                'description' => 'Development and debugging options',
                'icon' => 'fas fa-code'
            ]
        ];
    }
    
    /**
     * Sanitize settings input from admin form
     */
    public function sanitizeSettings($input)
    {
        $sanitized = [];
        
        // Boolean settings
        $boolean_fields = ['enabled', 'show_date', 'admin_notifications', 'allow_uploads', 'enable_captcha', 'debug_mode'];
        foreach ($boolean_fields as $field) {
            $sanitized[$field] = isset($input[$field]) && $input[$field] == '1';
        }
        
        // String settings
        if (isset($input['welcome_title'])) {
            $sanitized['welcome_title'] = sanitize_text_field($input['welcome_title']);
        }
        
        if (isset($input['welcome_message'])) {
            $sanitized['welcome_message'] = sanitize_textarea_field($input['welcome_message']);
        }
        
        if (isset($input['notification_email'])) {
            $email = sanitize_email($input['notification_email']);
            if (empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $sanitized['notification_email'] = $email;
            }
        }
        
        // Select settings with validation
        if (isset($input['widget_style'])) {
            $allowed_styles = ['default', 'minimal', 'fancy'];
            if (in_array($input['widget_style'], $allowed_styles)) {
                $sanitized['widget_style'] = $input['widget_style'];
            }
        }
        
        // Integer settings with validation
        $integer_fields = [
            'max_items' => ['min' => 1, 'max' => 100],
            'max_content_length' => ['min' => 100, 'max' => 10000],
            'max_upload_size' => ['min' => 1024, 'max' => 52428800],
            'rate_limit' => ['min' => 1, 'max' => 100],
            'rate_limit_window' => ['min' => 60, 'max' => 86400],
            'cache_duration' => ['min' => 0, 'max' => 86400]
        ];
        
        foreach ($integer_fields as $field => $limits) {
            if (isset($input[$field])) {
                $value = intval($input[$field]);
                if ($value >= $limits['min'] && $value <= $limits['max']) {
                    $sanitized[$field] = $value;
                }
            }
        }
        
        // Array settings
        if (isset($input['allowed_file_types']) && is_array($input['allowed_file_types'])) {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
            $sanitized['allowed_file_types'] = array_intersect($input['allowed_file_types'], $allowed_extensions);
        }
        
        return $sanitized;
    }
    
    /**
     * Format file size for display
     */
    public function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    /**
     * Format duration for display
     */
    public function formatDuration($seconds)
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return $minutes . ' minute' . ($minutes != 1 ? 's' : '');
        } else {
            $hours = floor($seconds / 3600);
            $remaining_minutes = floor(($seconds % 3600) / 60);
            $result = $hours . ' hour' . ($hours != 1 ? 's' : '');
            if ($remaining_minutes > 0) {
                $result .= ' ' . $remaining_minutes . ' minute' . ($remaining_minutes != 1 ? 's' : '');
            }
            return $result;
        }
    }
    
    /**
     * Handle admin menu registration
     */
    public function registerAdminMenuItems()
    {
        // Main menu item
        if (function_exists('add_admin_menu')) {
            add_admin_menu([
                'title' => 'Example Module',
                'capability' => 'example_module_admin',
                'slug' => 'example_module',
                'callback' => [$this, 'renderAdminDashboard'],
                'icon' => 'fas fa-star',
                'position' => 25
            ]);
            
            // Submenu items
            add_admin_submenu('example_module', [
                'title' => 'Dashboard',
                'capability' => 'example_module_view',
                'slug' => 'example_module_dashboard',
                'callback' => [$this, 'renderAdminDashboard']
            ]);
            
            add_admin_submenu('example_module', [
                'title' => 'Manage Content',
                'capability' => 'example_module_manage_data',
                'slug' => 'example_module_content',
                'callback' => [$this, 'renderAdminContent']
            ]);
            
            add_admin_submenu('example_module', [
                'title' => 'Settings',
                'capability' => 'example_module_settings',
                'slug' => 'example_module_settings',
                'callback' => [$this, 'renderAdminSettings']
            ]);
        }
    }
    
    /**
     * Render admin dashboard
     */
    public function renderAdminDashboard()
    {
        echo '<div class="wrap">';
        echo '<h1>Example Module Dashboard</h1>';
        echo '<p>Welcome to the Example Module administration area.</p>';
        
        // Display module statistics
        $stats = $this->getStats();
        if ($stats) {
            echo '<div class="dashboard-stats">';
            echo '<h2>Statistics</h2>';
            echo '<div class="stats-grid">';
            echo '<div class="stat-item">';
            echo '<h3>' . $stats['total_items'] . '</h3>';
            echo '<p>Total Items</p>';
            echo '</div>';
            echo '<div class="stat-item">';
            echo '<h3>' . $stats['recent_items'] . '</h3>';
            echo '<p>Recent Items (7 days)</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render admin content management
     */
    public function renderAdminContent()
    {
        echo '<div class="wrap">';
        echo '<h1>Manage Content</h1>';
        echo '<p>Content management interface will be implemented here.</p>';
        echo '</div>';
    }
    
    /**
     * Handle module deactivation
     */
    public function deactivate()
    {
        // Cleanup tasks when module is deactivated
        // Remove hooks, cleanup temporary data, etc.
        do_action('example_module_deactivating');
    }
    
    /**
     * Utility function for sanitizing text fields
     */
    private function sanitize_text_field($text)
    {
        return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Utility function for sanitizing textarea fields
     */
    private function sanitize_textarea_field($text)
    {
        return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Utility function for sanitizing email fields
     */
    private function sanitize_email($email)
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
}