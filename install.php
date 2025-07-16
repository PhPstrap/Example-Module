<?php

/**
 * Example Module Installation Script
 * 
 * This script demonstrates how to properly install a PhPstrap module.
 * It handles:
 * - Database table creation
 * - Module registration with simple settings
 * - Default settings setup
 * - Permission and hook registration
 * - Error handling and rollback
 */

/**
 * Install the Example Module
 * 
 * @param PDO $pdo Database connection
 * @param array $options Installation options
 * @return bool Success status
 */
function install_example_module($pdo, $options = [])
{
    try {
        // Start database transaction for atomic installation
        $pdo->beginTransaction();
        
        // Create module data table
        if (!create_example_module_tables($pdo)) {
            throw new Exception("Failed to create module tables");
        }
        
        // Register the module in the modules table
        if (!register_example_module($pdo, $options)) {
            throw new Exception("Failed to register module");
        }
        
        // Create default module data (optional)
        if (isset($options['create_sample_data']) && $options['create_sample_data']) {
            create_sample_data($pdo);
        }
        
        // Set up module permissions
        setup_module_permissions($pdo);
        
        // Create module configuration files
        create_module_config_files();
        
        // Commit transaction
        $pdo->commit();
        
        // Log successful installation
        error_log("Example Module: Installation completed successfully");
        
        // Trigger post-installation hooks
        if (function_exists('do_action')) {
            do_action('example_module_installed');
        }
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        // Log error
        error_log("Example Module Installation Error: " . $e->getMessage());
        
        // Clean up any created files
        cleanup_installation_files();
        
        return false;
    }
}

/**
 * Create database tables for the module
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function create_example_module_tables($pdo)
{
    try {
        // Main data table for the module
        $sql_data_table = "
            CREATE TABLE IF NOT EXISTS example_module_data (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                data_type VARCHAR(50) DEFAULT 'general',
                status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
                meta_data JSON,
                user_id INT DEFAULT NULL,
                views INT DEFAULT 0,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Indexes for performance
                INDEX idx_data_type (data_type),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at),
                INDEX idx_user_id (user_id),
                INDEX idx_sort_order (sort_order),
                
                -- Full text search index
                FULLTEXT idx_search (title, content)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sql_data_table);
        
        // Settings cache table (optional - for modules with complex settings)
        $sql_cache_table = "
            CREATE TABLE IF NOT EXISTS example_module_cache (
                cache_key VARCHAR(255) PRIMARY KEY,
                cache_value LONGTEXT,
                cache_group VARCHAR(100) DEFAULT 'default',
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                INDEX idx_group (cache_group),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sql_cache_table);
        
        // Audit log table (for tracking module usage)
        $sql_audit_table = "
            CREATE TABLE IF NOT EXISTS example_module_audit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50),
                entity_id INT,
                user_id INT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                INDEX idx_action (action),
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_user (user_id),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sql_audit_table);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Example Module: Table creation error - " . $e->getMessage());
        return false;
    }
}

/**
 * Register the module in the PhPstrap modules table
 * 
 * @param PDO $pdo Database connection
 * @param array $options Installation options
 * @return bool Success status
 */
function register_example_module($pdo, $options = [])
{
    try {
        // Check if module already exists
        $stmt = $pdo->prepare("SELECT id FROM modules WHERE name = 'example_module'");
        $stmt->execute();
        if ($stmt->fetch()) {
            // Module already exists, update it instead
            return update_existing_module($pdo, $options);
        }
        
        // Prepare module settings (simple key-value pairs)
        $settings = get_default_module_settings($options);
        
        // Prepare module hooks
        $hooks = json_encode([
            'example_module_display',
            'example_module_save_data',
            'example_module_init',
            'example_module_loaded',
            'example_module_settings_updated',
            'example_module_data_saved',
            'example_module_deactivating'
        ]);
        
        // Prepare module permissions
        $permissions = json_encode([
            'example_module_view',
            'example_module_admin',
            'example_module_settings',
            'example_module_manage_data',
            'example_module_delete_data'
        ]);
        
        // Get installation SQL for reference
        $install_sql = get_installation_sql();
        
        // Insert module record
        $stmt = $pdo->prepare("
            INSERT INTO modules (
                name, title, description, version, author, author_url,
                license, enabled, settings, hooks, permissions, 
                install_path, namespace, install_sql, status,
                priority, is_core, is_commercial, price,
                required_version, dependencies, tags
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'example_module',                                    // name
            'Example Module',                                    // title
            'A comprehensive example module showing PhPstrap module development best practices', // description
            '1.0.0',                                            // version
            'PhPstrap Team',                                     // author
            'https://PhPstrap.com',                              // author_url
            'MIT',                                              // license
            1,                                                  // enabled
            json_encode($settings),                             // settings (simple key-value pairs)
            $hooks,                                             // hooks
            $permissions,                                       // permissions
            'modules/example_module',                           // install_path
            'PhPstrap\\Modules\\Example',                        // namespace
            $install_sql,                                       // install_sql
            'active',                                           // status
            10,                                                 // priority
            0,                                                  // is_core
            0,                                                  // is_commercial
            0.00,                                              // price
            '1.0.0',                                           // required_version
            json_encode([]),                                    // dependencies
            json_encode(['example', 'demo', 'tutorial', 'development']) // tags
        ]);
        
        if (!$result) {
            throw new Exception("Failed to insert module record");
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Example Module: Registration error - " . $e->getMessage());
        return false;
    }
}

/**
 * Update existing module record
 * 
 * @param PDO $pdo Database connection
 * @param array $options Installation options
 * @return bool Success status
 */
function update_existing_module($pdo, $options = [])
{
    try {
        $settings = get_default_module_settings($options);
        
        $stmt = $pdo->prepare("
            UPDATE modules SET 
                version = '1.0.0',
                settings = ?,
                status = 'active',
                updated_at = NOW()
            WHERE name = 'example_module'
        ");
        
        return $stmt->execute([json_encode($settings)]);
        
    } catch (Exception $e) {
        error_log("Example Module: Update error - " . $e->getMessage());
        return false;
    }
}

/**
 * Get default module settings (simple key-value pairs)
 * 
 * @param array $options Installation options
 * @return array Default settings
 */
function get_default_module_settings($options = [])
{
    $defaults = [
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
        'debug_mode' => false,
        'installation_date' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ];
    
    // Merge with any custom options
    return array_merge($defaults, $options['settings'] ?? []);
}

/**
 * Create sample data for demonstration
 * 
 * @param PDO $pdo Database connection
 */
function create_sample_data($pdo)
{
    try {
        $sample_data = [
            [
                'title' => 'Welcome Example',
                'content' => 'This is sample data created during module installation.',
                'data_type' => 'welcome',
                'status' => 'active'
            ],
            [
                'title' => 'Getting Started',
                'content' => 'Learn how to use this module by exploring its features.',
                'data_type' => 'tutorial',
                'status' => 'active'
            ],
            [
                'title' => 'Admin Guide',
                'content' => 'Administrative features are available in the admin panel.',
                'data_type' => 'admin',
                'status' => 'active'
            ],
            [
                'title' => 'Widget Example',
                'content' => 'This shows how widgets work in the Example Module.',
                'data_type' => 'demo',
                'status' => 'active'
            ],
            [
                'title' => 'Form Submission Test',
                'content' => 'Test the form submission functionality with this sample.',
                'data_type' => 'test',
                'status' => 'active'
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO example_module_data (title, content, data_type, status, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        foreach ($sample_data as $item) {
            $stmt->execute([
                $item['title'],
                $item['content'],
                $item['data_type'],
                $item['status']
            ]);
        }
        
        error_log("Example Module: Sample data created successfully");
        
    } catch (Exception $e) {
        error_log("Example Module: Sample data creation error - " . $e->getMessage());
        // Don't fail installation if sample data fails
    }
}

/**
 * Set up module permissions in the system
 * 
 * @param PDO $pdo Database connection
 */
function setup_module_permissions($pdo)
{
    try {
        // If PhPstrap has a permissions system, register permissions here
        $permissions = [
            'example_module_view' => 'View Example Module content',
            'example_module_admin' => 'Access Example Module admin panel',
            'example_module_settings' => 'Modify Example Module settings',
            'example_module_manage_data' => 'Create and edit module data',
            'example_module_delete_data' => 'Delete module data'
        ];
        
        // Check if permissions table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'permissions'");
        if ($stmt->rowCount() > 0) {
            $perm_stmt = $pdo->prepare("
                INSERT IGNORE INTO permissions (name, description, module)
                VALUES (?, ?, 'example_module')
            ");
            
            foreach ($permissions as $perm_name => $description) {
                $perm_stmt->execute([$perm_name, $description]);
            }
            
            error_log("Example Module: Permissions set up successfully");
        }
        
    } catch (Exception $e) {
        error_log("Example Module: Permission setup error - " . $e->getMessage());
        // Don't fail installation if permissions fail
    }
}

/**
 * Create module configuration files
 */
function create_module_config_files()
{
    try {
        $module_path = dirname(__FILE__);
        
        // Create assets directory if it doesn't exist
        $assets_dir = $module_path . '/assets';
        if (!is_dir($assets_dir)) {
            mkdir($assets_dir, 0755, true);
        }
        
        // Create views directory if it doesn't exist
        $views_dir = $module_path . '/views';
        if (!is_dir($views_dir)) {
            mkdir($views_dir, 0755, true);
        }
        
        // Create basic CSS file if it doesn't exist
        $css_file = $assets_dir . '/example-module.css';
        if (!file_exists($css_file)) {
            $css_content = "/* Example Module Styles */\n";
            $css_content .= ".example-module-widget { padding: 15px; border: 1px solid #ddd; border-radius: 5px; }\n";
            $css_content .= ".example-module-data .data-item { margin-bottom: 10px; padding: 10px; background: #f9f9f9; }\n";
            $css_content .= ".example-module-form { max-width: 800px; margin: 0 auto; }\n";
            $css_content .= ".form-control { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ccc; border-radius: 3px; }\n";
            file_put_contents($css_file, $css_content);
        }
        
        // Create basic JS file if it doesn't exist
        $js_file = $assets_dir . '/example-module.js';
        if (!file_exists($js_file)) {
            $js_content = "// Example Module JavaScript\n";
            $js_content .= "document.addEventListener('DOMContentLoaded', function() {\n";
            $js_content .= "    console.log('Example Module loaded');\n";
            $js_content .= "    \n";
            $js_content .= "    // Initialize form enhancements\n";
            $js_content .= "    const forms = document.querySelectorAll('.example-module-form');\n";
            $js_content .= "    forms.forEach(form => {\n";
            $js_content .= "        // Add form validation and enhancements here\n";
            $js_content .= "        console.log('Form initialized:', form.id);\n";
            $js_content .= "    });\n";
            $js_content .= "});\n";
            file_put_contents($js_file, $js_content);
        }
        
        // Create basic widget view if it doesn't exist
        $widget_file = $views_dir . '/widget.php';
        if (!file_exists($widget_file)) {
            $widget_content = "<?php\n";
            $widget_content .= "// Example Module Widget View\n";
            $widget_content .= "?>\n";
            $widget_content .= "<div class=\"example-module-widget <?php echo esc_attr(\$attributes['style']); ?>\">\n";
            $widget_content .= "    <h3><?php echo esc_html(\$attributes['title']); ?></h3>\n";
            $widget_content .= "    <p><?php echo esc_html(\$this->settings['welcome_message']); ?></p>\n";
            $widget_content .= "    <?php if (\$attributes['show_date']): ?>\n";
            $widget_content .= "        <p><small>Today: <?php echo date('Y-m-d H:i:s'); ?></small></p>\n";
            $widget_content .= "    <?php endif; ?>\n";
            $widget_content .= "</div>\n";
            file_put_contents($widget_file, $widget_content);
        }
        
        error_log("Example Module: Configuration files created successfully");
        
    } catch (Exception $e) {
        error_log("Example Module: Config file creation error - " . $e->getMessage());
        // Don't fail installation if file creation fails
    }
}

/**
 * Get installation SQL for reference
 * 
 * @return string SQL statements used during installation
 */
function get_installation_sql()
{
    return "-- Example Module Installation SQL
CREATE TABLE IF NOT EXISTS example_module_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    data_type VARCHAR(50) DEFAULT 'general',
    status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
    meta_data JSON,
    user_id INT DEFAULT NULL,
    views INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_data_type (data_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FULLTEXT idx_search (title, content)
);

CREATE TABLE IF NOT EXISTS example_module_cache (
    cache_key VARCHAR(255) PRIMARY KEY,
    cache_value LONGTEXT,
    cache_group VARCHAR(100) DEFAULT 'default',
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS example_module_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
}

/**
 * Clean up installation files on error
 */
function cleanup_installation_files()
{
    try {
        $module_path = dirname(__FILE__);
        
        // Remove created directories if they're empty
        $dirs_to_check = [
            $module_path . '/assets',
            $module_path . '/views'
        ];
        
        foreach ($dirs_to_check as $dir) {
            if (is_dir($dir) && count(scandir($dir)) == 2) { // Only . and ..
                rmdir($dir);
            }
        }
        
    } catch (Exception $e) {
        error_log("Example Module: Cleanup error - " . $e->getMessage());
    }
}

/**
 * Verify installation success
 * 
 * @param PDO $pdo Database connection
 * @return array Verification results
 */
function verify_example_module_installation($pdo)
{
    $results = [
        'module_registered' => false,
        'tables_created' => false,
        'settings_valid' => false,
        'files_created' => false,
        'success' => false
    ];
    
    try {
        // Check if module is registered
        $stmt = $pdo->prepare("SELECT id, settings FROM modules WHERE name = 'example_module' AND status = 'active'");
        $stmt->execute();
        $module = $stmt->fetch();
        $results['module_registered'] = !empty($module);
        
        // Check if settings are simple values (not complex objects)
        if ($module && !empty($module['settings'])) {
            $settings = json_decode($module['settings'], true);
            $results['settings_valid'] = is_array($settings) && 
                                       isset($settings['enabled']) && 
                                       !is_array($settings['enabled']); // Should be simple boolean, not object
        }
        
        // Check if main table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'example_module_data'");
        $results['tables_created'] = ($stmt->rowCount() > 0);
        
        // Check if module files exist
        $module_path = dirname(__FILE__);
        $required_files = [
            $module_path . '/ExampleModule.php',
            $module_path . '/module.json',
            $module_path . '/assets/example-module.css',
            $module_path . '/assets/example-module.js'
        ];
        
        $files_exist = 0;
        foreach ($required_files as $file) {
            if (file_exists($file)) {
                $files_exist++;
            }
        }
        $results['files_created'] = ($files_exist == count($required_files));
        
        // Overall success
        $results['success'] = $results['module_registered'] && 
                             $results['tables_created'] && 
                             $results['settings_valid'] &&
                             $results['files_created'];
        
    } catch (Exception $e) {
        error_log("Example Module: Verification error - " . $e->getMessage());
    }
    
    return $results;
}

/**
 * Fix existing installations with complex settings
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function fix_existing_installation($pdo)
{
    try {
        // Get current settings
        $stmt = $pdo->prepare("SELECT settings FROM modules WHERE name = 'example_module'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if (!$result || empty($result['settings'])) {
            return false;
        }
        
        $current_settings = json_decode($result['settings'], true);
        
        // Check if settings are already simple
        if (isset($current_settings['enabled']) && !is_array($current_settings['enabled'])) {
            return true; // Already fixed
        }
        
        // Convert complex objects to simple values
        $fixed_settings = get_default_module_settings();
        
        // Try to preserve existing values
        foreach ($fixed_settings as $key => $default_value) {
            if (isset($current_settings[$key])) {
                $value = $current_settings[$key];
                
                // Extract value from complex object
                if (is_array($value)) {
                    if (isset($value['default'])) {
                        $fixed_settings[$key] = $value['default'];
                    } elseif (isset($value['value'])) {
                        $fixed_settings[$key] = $value['value'];
                    }
                } else {
                    $fixed_settings[$key] = $value;
                }
            }
        }
        
        // Update settings in database
        $stmt = $pdo->prepare("UPDATE modules SET settings = ? WHERE name = 'example_module'");
        $result = $stmt->execute([json_encode($fixed_settings)]);
        
        if ($result) {
            error_log("Example Module: Existing installation fixed successfully");
            return true;
        }
        
    } catch (Exception $e) {
        error_log("Example Module: Error fixing existing installation - " . $e->getMessage());
    }
    
    return false;
}

// If called directly, run installation
if (basename($_SERVER['PHP_SELF']) == 'install.php') {
    try {
        if (function_exists('getDbConnection')) {
            $pdo = getDbConnection();
            $options = [
                'create_sample_data' => true,
                'settings' => [
                    'welcome_title' => 'Example Module Installed!',
                    'welcome_message' => 'Installation completed successfully.'
                ]
            ];
            
            echo "Example Module Installer\n";
            echo "========================\n";
            
            // Check if module already exists and try to fix it
            $stmt = $pdo->prepare("SELECT id FROM modules WHERE name = 'example_module'");
            $stmt->execute();
            if ($stmt->fetch()) {
                echo "Existing installation detected. Attempting to fix...\n";
                if (fix_existing_installation($pdo)) {
                    echo "Existing installation fixed successfully!\n";
                } else {
                    echo "Could not fix existing installation. Proceeding with fresh install...\n";
                }
            }
            
            if (install_example_module($pdo, $options)) {
                echo "Example Module installed successfully!\n";
                
                $verification = verify_example_module_installation($pdo);
                echo "\nVerification Results:\n";
                echo "Module registered: " . ($verification['module_registered'] ? 'Yes' : 'No') . "\n";
                echo "Tables created: " . ($verification['tables_created'] ? 'Yes' : 'No') . "\n";
                echo "Settings valid: " . ($verification['settings_valid'] ? 'Yes' : 'No') . "\n";
                echo "Files created: " . ($verification['files_created'] ? 'Yes' : 'No') . "\n";
                echo "Overall success: " . ($verification['success'] ? 'Yes' : 'No') . "\n";
                
                if ($verification['success']) {
                    echo "\n✅ Installation completed successfully!\n";
                    echo "The module settings should now display properly without '[object Object]' issues.\n";
                }
                
            } else {
                echo "Example Module installation failed!\n";
                echo "Check error logs for details.\n";
            }
        } else {
            echo "Database connection not available.\n";
        }
    } catch (Exception $e) {
        echo "Installation error: " . $e->getMessage() . "\n";
    }
}
?>