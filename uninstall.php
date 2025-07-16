<?php

/**
 * Example Module Uninstallation Script
 * 
 * This script demonstrates how to properly uninstall a PhPstrap module.
 * It handles:
 * - Safe data removal with backup options
 * - Module deregistration
 * - File system cleanup
 * - Permission cleanup
 * - Cache clearing
 * - Hook/filter removal
 * - Rollback capabilities
 */

/**
 * Uninstall the Example Module
 * 
 * @param PDO $pdo Database connection
 * @param array $options Uninstall options
 * @return bool Success status
 */
function uninstall_example_module($pdo, $options = [])
{
    try {
        // Set default options
        $options = array_merge([
            'remove_data' => false,        // Keep user data by default
            'remove_settings' => true,     // Remove module settings
            'remove_files' => false,       // Keep files by default
            'create_backup' => true,       // Create backup before removal
            'force_removal' => false       // Force removal even if errors occur
        ], $options);
        
        // Start database transaction for atomic uninstallation
        $pdo->beginTransaction();
        
        // Trigger pre-uninstall hooks
        if (function_exists('do_action')) {
            do_action('example_module_before_uninstall', $options);
        }
        
        // Create backup if requested
        if ($options['create_backup']) {
            $backup_result = create_uninstall_backup($pdo);
            if (!$backup_result && !$options['force_removal']) {
                throw new Exception("Failed to create backup - uninstall aborted");
            }
        }
        
        // Deactivate module first
        if (!deactivate_example_module($pdo)) {
            if (!$options['force_removal']) {
                throw new Exception("Failed to deactivate module");
            }
        }
        
        // Remove module data if requested
        if ($options['remove_data']) {
            if (!remove_module_data($pdo, $options['force_removal'])) {
                throw new Exception("Failed to remove module data");
            }
        }
        
        // Remove module settings and configuration
        if ($options['remove_settings']) {
            if (!remove_module_settings($pdo)) {
                throw new Exception("Failed to remove module settings");
            }
        }
        
        // Remove module permissions
        if (!remove_module_permissions($pdo)) {
            if (!$options['force_removal']) {
                throw new Exception("Failed to remove module permissions");
            }
        }
        
        // Deregister the module
        if (!deregister_example_module($pdo)) {
            throw new Exception("Failed to deregister module");
        }
        
        // Clear module cache
        clear_module_cache($pdo);
        
        // Remove files if requested
        if ($options['remove_files']) {
            remove_module_files();
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Log successful uninstallation
        error_log("Example Module: Uninstallation completed successfully");
        
        // Trigger post-uninstall hooks
        if (function_exists('do_action')) {
            do_action('example_module_after_uninstall', $options);
        }
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        // Log error
        error_log("Example Module Uninstall Error: " . $e->getMessage());
        
        // Attempt to restore from backup if available
        if ($options['create_backup']) {
            restore_from_backup($pdo);
        }
        
        return false;
    }
}

/**
 * Deactivate the module before uninstallation
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function deactivate_example_module($pdo)
{
    try {
        // Check if module exists and is active
        $stmt = $pdo->prepare("SELECT id, settings FROM modules WHERE name = 'example_module'");
        $stmt->execute();
        $module = $stmt->fetch();
        
        if (!$module) {
            return true; // Module doesn't exist, consider it deactivated
        }
        
        // Load module if it exists to call deactivation method
        $module_path = dirname(__FILE__) . '/ExampleModule.php';
        if (file_exists($module_path)) {
            require_once $module_path;
            
            if (class_exists('PhPstrap\\Modules\\Example\\ExampleModule')) {
                $moduleInstance = new \PhPstrap\Modules\Example\ExampleModule();
                if (method_exists($moduleInstance, 'deactivate')) {
                    $moduleInstance->deactivate();
                }
            }
        }
        
        // Mark module as inactive
        $stmt = $pdo->prepare("
            UPDATE modules 
            SET enabled = 0, status = 'inactive', updated_at = NOW() 
            WHERE name = 'example_module'
        ");
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Example Module: Deactivation error - " . $e->getMessage());
        return false;
    }
}

/**
 * Create backup before uninstallation
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function create_uninstall_backup($pdo)
{
    try {
        $backup_timestamp = date('Y-m-d_H-i-s');
        $backup_prefix = "example_module_backup_{$backup_timestamp}";
        
        // Backup main data table
        $pdo->exec("
            CREATE TABLE {$backup_prefix}_data AS 
            SELECT * FROM example_module_data
        ");
        
        // Backup cache table
        $pdo->exec("
            CREATE TABLE {$backup_prefix}_cache AS 
            SELECT * FROM example_module_cache
        ");
        
        // Backup audit table
        $pdo->exec("
            CREATE TABLE {$backup_prefix}_audit AS 
            SELECT * FROM example_module_audit
        ");
        
        // Backup module settings
        $pdo->exec("
            CREATE TABLE {$backup_prefix}_settings AS 
            SELECT * FROM modules WHERE name = 'example_module'
        ");
        
        // Log backup creation
        error_log("Example Module: Backup created with prefix {$backup_prefix}");
        
        // Store backup info for potential restoration
        $stmt = $pdo->prepare("
            INSERT INTO example_module_audit (action, data, created_at)
            VALUES ('backup_created', ?, NOW())
        ");
        $stmt->execute([json_encode(['backup_prefix' => $backup_prefix])]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Example Module: Backup creation error - " . $e->getMessage());
        return false;
    }
}

/**
 * Remove module data tables
 * 
 * @param PDO $pdo Database connection
 * @param bool $force_removal Force removal even on errors
 * @return bool Success status
 */
function remove_module_data($pdo, $force_removal = false)
{
    try {
        $tables_to_remove = [
            'example_module_data',
            'example_module_cache',
            'example_module_audit'
        ];
        
        foreach ($tables_to_remove as $table) {
            try {
                // Check if table exists
                $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($stmt->rowCount() > 0) {
                    $pdo->exec("DROP TABLE {$table}");
                    error_log("Example Module: Removed table {$table}");
                }
            } catch (Exception $e) {
                error_log("Example Module: Error removing table {$table} - " . $e->getMessage());
                if (!$force_removal) {
                    throw $e;
                }
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Example Module: Data removal error - " . $e->getMessage());
        return false;
    }
}

/**
 * Remove module settings and configuration
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function remove_module_settings($pdo)
{
    try {
        // Remove from modules table will be done in deregister_example_module
        
        // Remove any cached settings
        if (function_exists('delete_option')) {
            delete_option('example_module_settings');
            delete_option('example_module_cache');
        }
        
        // Remove settings from cache table if it still exists
        try {
            $stmt = $pdo->prepare("DELETE FROM example_module_cache WHERE cache_group = 'settings'");
            $stmt->execute();
        } catch (Exception $e) {
            // Table might not exist anymore, ignore
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Example Module: Settings removal error - " . $e->getMessage());
        return false;
    }
}

/**
 * Remove module permissions from the system
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function remove_module_permissions($pdo)
{
    try {
        // Check if permissions table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'permissions'");
        if ($stmt->rowCount() > 0) {
            // Remove module permissions
            $stmt = $pdo->prepare("DELETE FROM permissions WHERE module = 'example_module'");
            $stmt->execute();
            
            // Remove role-permission associations if they exist
            $stmt = $pdo->query("SHOW TABLES LIKE 'role_permissions'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("
                    DELETE FROM role_permissions 
                    WHERE permission_id IN (
                        SELECT id FROM permissions WHERE module = 'example_module'
                    )
                ");
                $stmt->execute();
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Example Module: Permission removal error - " . $e->getMessage());
        return false;
    }
}

/**
 * Deregister the module from PhPstrap
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function deregister_example_module($pdo)
{
    try {
        $stmt = $pdo->prepare("DELETE FROM modules WHERE name = 'example_module'");
        $result = $stmt->execute();
        
        if ($result) {
            error_log("Example Module: Module deregistered successfully");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Example Module: Deregistration error - " . $e->getMessage());
        return false;
    }
}

/**
 * Clear all module cache entries
 * 
 * @param PDO $pdo Database connection
 */
function clear_module_cache($pdo)
{
    try {
        // Clear from module cache table if it exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'example_module_cache'");
        if ($stmt->rowCount() > 0) {
            $pdo->exec("DELETE FROM example_module_cache");
        }
        
        // Clear from system cache if functions exist
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete('example_module_settings');
            wp_cache_delete('example_module_data');
        }
        
        // Clear file-based cache if it exists
        $cache_dir = dirname(__FILE__) . '/cache';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Example Module: Cache clearing error - " . $e->getMessage());
    }
}

/**
 * Remove module files from the filesystem
 */
function remove_module_files()
{
    try {
        $module_path = dirname(__FILE__);
        
        // Files to remove
        $files_to_remove = [
            '/assets/example-module.css',
            '/assets/example-module.js',
            '/views/widget.php',
            '/views/admin.php'
        ];
        
        foreach ($files_to_remove as $file) {
            $full_path = $module_path . $file;
            if (file_exists($full_path)) {
                unlink($full_path);
                error_log("Example Module: Removed file {$file}");
            }
        }
        
        // Remove empty directories
        $dirs_to_remove = [
            $module_path . '/assets',
            $module_path . '/views',
            $module_path . '/cache'
        ];
        
        foreach ($dirs_to_remove as $dir) {
            if (is_dir($dir) && count(scandir($dir)) == 2) { // Only . and ..
                rmdir($dir);
                error_log("Example Module: Removed directory " . basename($dir));
            }
        }
        
    } catch (Exception $e) {
        error_log("Example Module: File removal error - " . $e->getMessage());
    }
}

/**
 * Restore module from backup
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function restore_from_backup($pdo)
{
    try {
        // Find the most recent backup
        $stmt = $pdo->query("
            SELECT data FROM example_module_audit 
            WHERE action = 'backup_created' 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        
        $backup_info = $stmt->fetch();
        if (!$backup_info) {
            return false;
        }
        
        $backup_data = json_decode($backup_info['data'], true);
        $backup_prefix = $backup_data['backup_prefix'];
        
        // Restore data table
        $pdo->exec("
            CREATE TABLE example_module_data AS 
            SELECT * FROM {$backup_prefix}_data
        ");
        
        // Restore cache table
        $pdo->exec("
            CREATE TABLE example_module_cache AS 
            SELECT * FROM {$backup_prefix}_cache
        ");
        
        // Restore audit table
        $pdo->exec("
            CREATE TABLE example_module_audit AS 
            SELECT * FROM {$backup_prefix}_audit
        ");
        
        // Restore module registration
        $pdo->exec("
            INSERT INTO modules 
            SELECT * FROM {$backup_prefix}_settings
        ");
        
        error_log("Example Module: Restored from backup {$backup_prefix}");
        return true;
        
    } catch (Exception $e) {
        error_log("Example Module: Backup restoration error - " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up backup tables after successful uninstall
 * 
 * @param PDO $pdo Database connection
 * @param int $keep_days Number of days to keep backups
 */
function cleanup_old_backups($pdo, $keep_days = 30)
{
    try {
        // Find old backup tables
        $stmt = $pdo->query("SHOW TABLES LIKE 'example_module_backup_%'");
        $backup_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $cutoff_date = date('Y-m-d', strtotime("-{$keep_days} days"));
        
        foreach ($backup_tables as $table) {
            // Extract date from table name
            if (preg_match('/example_module_backup_(\d{4}-\d{2}-\d{2})_/', $table, $matches)) {
                $backup_date = $matches[1];
                
                if ($backup_date < $cutoff_date) {
                    $pdo->exec("DROP TABLE {$table}");
                    error_log("Example Module: Cleaned up old backup table {$table}");
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Example Module: Backup cleanup error - " . $e->getMessage());
    }
}

/**
 * Verify uninstallation success
 * 
 * @param PDO $pdo Database connection
 * @return array Verification results
 */
function verify_example_module_uninstall($pdo)
{
    $results = [
        'module_removed' => false,
        'tables_removed' => false,
        'permissions_removed' => false,
        'files_removed' => false,
        'success' => false
    ];
    
    try {
        // Check if module is removed from modules table
        $stmt = $pdo->prepare("SELECT id FROM modules WHERE name = 'example_module'");
        $stmt->execute();
        $results['module_removed'] = ($stmt->rowCount() == 0);
        
        // Check if tables are removed
        $tables = ['example_module_data', 'example_module_cache', 'example_module_audit'];
        $tables_exist = 0;
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                $tables_exist++;
            }
        }
        $results['tables_removed'] = ($tables_exist == 0);
        
        // Check if permissions are removed
        $stmt = $pdo->query("SHOW TABLES LIKE 'permissions'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT id FROM permissions WHERE module = 'example_module'");
            $stmt->execute();
            $results['permissions_removed'] = ($stmt->rowCount() == 0);
        } else {
            $results['permissions_removed'] = true;
        }
        
        // Check if files are removed
        $module_path = dirname(__FILE__);
        $key_files = [
            $module_path . '/assets/example-module.css',
            $module_path . '/assets/example-module.js'
        ];
        $files_exist = 0;
        foreach ($key_files as $file) {
            if (file_exists($file)) {
                $files_exist++;
            }
        }
        $results['files_removed'] = ($files_exist == 0);
        
        // Overall success
        $results['success'] = $results['module_removed'] && 
                             $results['tables_removed'] && 
                             $results['permissions_removed'];
        
    } catch (Exception $e) {
        error_log("Example Module: Verification error - " . $e->getMessage());
    }
    
    return $results;
}

/**
 * Get uninstall options from user input or config
 * 
 * @return array Uninstall options
 */
function get_uninstall_options()
{
    $options = [
        'remove_data' => false,
        'remove_settings' => true,
        'remove_files' => false,
        'create_backup' => true,
        'force_removal' => false
    ];
    
    // Check for command line arguments
    if (isset($_SERVER['argv'])) {
        foreach ($_SERVER['argv'] as $arg) {
            switch ($arg) {
                case '--remove-data':
                    $options['remove_data'] = true;
                    break;
                case '--remove-files':
                    $options['remove_files'] = true;
                    break;
                case '--no-backup':
                    $options['create_backup'] = false;
                    break;
                case '--force':
                    $options['force_removal'] = true;
                    break;
                case '--keep-settings':
                    $options['remove_settings'] = false;
                    break;
            }
        }
    }
    
    return $options;
}

// If called directly, run uninstallation
if (basename($_SERVER['PHP_SELF']) == 'uninstall.php') {
    try {
        if (function_exists('getDbConnection')) {
            $pdo = getDbConnection();
            $options = get_uninstall_options();
            
            echo "Example Module Uninstaller\n";
            echo "==========================\n";
            echo "Remove data: " . ($options['remove_data'] ? 'Yes' : 'No') . "\n";
            echo "Remove files: " . ($options['remove_files'] ? 'Yes' : 'No') . "\n";
            echo "Create backup: " . ($options['create_backup'] ? 'Yes' : 'No') . "\n";
            echo "Force removal: " . ($options['force_removal'] ? 'Yes' : 'No') . "\n";
            echo "\n";
            
            if (uninstall_example_module($pdo, $options)) {
                echo "Example Module uninstalled successfully!\n";
                
                $verification = verify_example_module_uninstall($pdo);
                echo "\nVerification Results:\n";
                echo "Module removed: " . ($verification['module_removed'] ? 'Yes' : 'No') . "\n";
                echo "Tables removed: " . ($verification['tables_removed'] ? 'Yes' : 'No') . "\n";
                echo "Permissions removed: " . ($verification['permissions_removed'] ? 'Yes' : 'No') . "\n";
                echo "Files removed: " . ($verification['files_removed'] ? 'Yes' : 'No') . "\n";
                echo "Overall success: " . ($verification['success'] ? 'Yes' : 'No') . "\n";
                
                // Cleanup old backups
                cleanup_old_backups($pdo);
                
            } else {
                echo "Example Module uninstallation failed!\n";
                echo "Check error logs for details.\n";
            }
        } else {
            echo "Database connection not available.\n";
        }
    } catch (Exception $e) {
        echo "Uninstallation error: " . $e->getMessage() . "\n";
    }
}