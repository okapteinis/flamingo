# Priority 3: Feature Enhancements

## Issues to Create

### Issue 1: Enhanced Debug Logging System

**Title:** Implement Configurable Debug Logging for Troubleshooting

**Labels:** `enhancement`, `priority-3`, `developer-experience`

**Description:**
```markdown
## Summary
Add a comprehensive, configurable debug logging system to help users and developers troubleshoot issues with the Flamingo plugin.

## Rationale
Currently, errors are logged using `error_log()`, but there's no way to:
- Enable/disable debug logging from admin
- View logs within WordPress admin
- Log different severity levels
- Track plugin operations for debugging

## Proposed Implementation

### 1. Settings Page with Debug Option

Add a settings page under Flamingo menu:

```php
// In admin/admin.php
function flamingo_settings_page() {
    add_submenu_page(
        'flamingo',
        __( 'Settings', 'flamingo' ),
        __( 'Settings', 'flamingo' ),
        'manage_options',
        'flamingo-settings',
        'flamingo_settings_page_content'
    );
}
add_action( 'admin_menu', 'flamingo_settings_page', 9 );

function flamingo_settings_page_content() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( __( 'Flamingo Settings', 'flamingo' ) ); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'flamingo_settings' );
            do_settings_sections( 'flamingo_settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
```

### 2. Debug Logging Functions

```php
/**
 * Logs a debug message if debug mode is enabled.
 *
 * @param string $message The message to log.
 * @param string $level   Log level: 'error', 'warning', 'info', 'debug'.
 * @param array  $context Optional context data.
 */
function flamingo_log( $message, $level = 'info', $context = array() ) {
    if ( ! get_option( 'flamingo_debug_enabled', false ) ) {
        return;
    }

    $log_entry = array(
        'timestamp' => current_time( 'mysql' ),
        'level' => $level,
        'message' => $message,
        'context' => $context,
    );

    // Log to WordPress debug.log if WP_DEBUG is enabled
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( sprintf(
            '[Flamingo %s] %s',
            strtoupper( $level ),
            $message
        ) );

        if ( ! empty( $context ) ) {
            error_log( '[Flamingo Context] ' . wp_json_encode( $context ) );
        }
    }

    // Also store in database for viewing in admin
    $max_logs = 100; // Keep only last 100 log entries
    $logs = get_option( 'flamingo_debug_logs', array() );

    array_unshift( $logs, $log_entry );

    if ( count( $logs ) > $max_logs ) {
        $logs = array_slice( $logs, 0, $max_logs );
    }

    update_option( 'flamingo_debug_logs', $logs, false );
}

/**
 * Convenience functions for different log levels.
 */
function flamingo_log_error( $message, $context = array() ) {
    flamingo_log( $message, 'error', $context );
}

function flamingo_log_warning( $message, $context = array() ) {
    flamingo_log( $message, 'warning', $context );
}

function flamingo_log_info( $message, $context = array() ) {
    flamingo_log( $message, 'info', $context );
}

function flamingo_log_debug( $message, $context = array() ) {
    flamingo_log( $message, 'debug', $context );
}
```

### 3. Log Viewer Page

Add a log viewer in the admin:

```php
function flamingo_logs_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have permission to view logs.', 'flamingo' ) );
    }

    // Handle clear logs action
    if ( isset( $_POST['clear_logs'] ) ) {
        check_admin_referer( 'flamingo_clear_logs' );
        delete_option( 'flamingo_debug_logs' );
        wp_admin_notice(
            __( 'Logs cleared successfully.', 'flamingo' ),
            array( 'type' => 'success' )
        );
    }

    $logs = get_option( 'flamingo_debug_logs', array() );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html( __( 'Flamingo Debug Logs', 'flamingo' ) ); ?></h1>

        <?php if ( ! get_option( 'flamingo_debug_enabled', false ) ) : ?>
            <div class="notice notice-info">
                <p><?php
                    printf(
                        __( 'Debug logging is currently disabled. <a href="%s">Enable it in settings</a>.', 'flamingo' ),
                        admin_url( 'admin.php?page=flamingo-settings' )
                    );
                ?></p>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field( 'flamingo_clear_logs' ); ?>
            <?php submit_button( __( 'Clear Logs', 'flamingo' ), 'secondary', 'clear_logs', false ); ?>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 150px;"><?php echo esc_html( __( 'Timestamp', 'flamingo' ) ); ?></th>
                    <th style="width: 80px;"><?php echo esc_html( __( 'Level', 'flamingo' ) ); ?></th>
                    <th><?php echo esc_html( __( 'Message', 'flamingo' ) ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $logs ) ) : ?>
                    <tr>
                        <td colspan="3"><?php echo esc_html( __( 'No log entries found.', 'flamingo' ) ); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $logs as $log ) : ?>
                        <tr class="log-level-<?php echo esc_attr( $log['level'] ); ?>">
                            <td><?php echo esc_html( $log['timestamp'] ); ?></td>
                            <td>
                                <span class="log-badge log-badge-<?php echo esc_attr( $log['level'] ); ?>">
                                    <?php echo esc_html( strtoupper( $log['level'] ) ); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html( $log['message'] ); ?>
                                <?php if ( ! empty( $log['context'] ) ) : ?>
                                    <details>
                                        <summary><?php echo esc_html( __( 'Show context', 'flamingo' ) ); ?></summary>
                                        <pre><?php echo esc_html( wp_json_encode( $log['context'], JSON_PRETTY_PRINT ) ); ?></pre>
                                    </details>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <style>
        .log-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .log-badge-error { background: #dc3232; color: #fff; }
        .log-badge-warning { background: #ffb900; color: #000; }
        .log-badge-info { background: #00a0d2; color: #fff; }
        .log-badge-debug { background: #888; color: #fff; }
    </style>
    <?php
}
```

### 4. Update Existing Error Logging

Replace existing `error_log()` calls with the new system:

```php
// In includes/class-contact.php
// Before
error_log( sprintf(
    'Flamingo: Failed to save contact "%s" - %s',
    $this->email,
    $post_id->get_error_message()
) );

// After
flamingo_log_error(
    'Failed to save contact',
    array(
        'email' => $this->email,
        'error' => $post_id->get_error_message(),
    )
);
```

### 5. Add Logging Throughout Plugin

Add strategic logging points:

```php
// When contacts are imported
flamingo_log_info( 'Contacts imported from users', array(
    'count' => count( $users ),
) );

// When cron jobs run
flamingo_log_debug( 'Cron job executed', array(
    'job' => 'move_trash',
    'moved' => count( $posts_to_move ),
) );

// When spam is detected
flamingo_log_warning( 'Message marked as spam', array(
    'message_id' => $this->id,
    'reason' => $reason,
) );
```

## Benefits
- Easier troubleshooting for users and support
- Track plugin operations without accessing server logs
- Different log levels for different severity
- In-admin log viewer (no FTP access needed)
- Helps identify issues in production environments
- Optional (can be disabled for performance)

## Files to Modify
- `includes/functions.php` (logging functions)
- `admin/admin.php` (settings and logs pages)
- `includes/class-contact.php` (add logging)
- `includes/class-inbound-message.php` (add logging)
- `includes/user.php` (add logging)
- `includes/comment.php` (add logging)
- `includes/cron.php` (add logging)

## Configuration Options
- Enable/disable debug logging
- Log retention (number of entries to keep)
- Minimum log level to record

## Privacy Considerations
- Don't log sensitive user data (passwords, tokens)
- Obfuscate email addresses in logs if needed
- Clear logs option for GDPR compliance

## Priority
**Priority 3 - Feature Enhancement**

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
```

---

### Issue 2: Data Integrity and Cleanup Tools

**Title:** Add Database Cleanup and Data Integrity Verification Tools

**Labels:** `enhancement`, `priority-3`, `maintenance`

**Description:**
```markdown
## Summary
Implement administrative tools for database cleanup and data integrity verification to maintain plugin health over time.

## Rationale
Over time, plugins can accumulate:
- Orphaned post meta (metadata for deleted posts)
- Unused taxonomy terms
- Duplicate entries
- Invalid data

A cleanup tool helps maintain database health and plugin performance.

## Proposed Implementation

### 1. Data Integrity Check Functions

```php
/**
 * Checks for orphaned contact metadata.
 *
 * @return array Array of orphaned meta IDs.
 */
function flamingo_find_orphaned_contact_meta() {
    global $wpdb;

    $orphaned = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT pm.meta_id
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
            AND pm.meta_key LIKE %s",
            $wpdb->esc_like( '_' ) . '%'
        )
    );

    return array_map( 'intval', $orphaned );
}

/**
 * Checks for duplicate contacts (same email).
 *
 * @return array Array of duplicate contact groups.
 */
function flamingo_find_duplicate_contacts() {
    global $wpdb;

    $duplicates = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT pm.meta_value as email, GROUP_CONCAT(pm.post_id) as post_ids, COUNT(*) as count
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_email'
            AND p.post_type = %s
            AND p.post_status != 'trash'
            GROUP BY pm.meta_value
            HAVING COUNT(*) > 1",
            Flamingo_Contact::post_type
        )
    );

    return $duplicates;
}

/**
 * Checks for empty contacts (no email).
 *
 * @return array Array of empty contact post IDs.
 */
function flamingo_find_empty_contacts() {
    global $wpdb;

    $empty = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_email'
            WHERE p.post_type = %s
            AND p.post_status != 'trash'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')",
            Flamingo_Contact::post_type
        )
    );

    return array_map( 'intval', $empty );
}

/**
 * Checks for unused taxonomy terms.
 *
 * @return array Array of unused term IDs.
 */
function flamingo_find_unused_terms() {
    global $wpdb;

    $unused = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT t.term_id, t.name, tt.taxonomy, tt.count
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy IN (%s, %s)
            AND tt.count = 0",
            Flamingo_Contact::contact_tag_taxonomy,
            Flamingo_Inbound_Message::channel_taxonomy
        )
    );

    return $unused;
}
```

### 2. Cleanup Admin Page

```php
function flamingo_tools_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have permission to use these tools.', 'flamingo' ) );
    }

    // Handle cleanup actions
    if ( isset( $_POST['action'] ) ) {
        check_admin_referer( 'flamingo_tools' );

        $action = $_POST['action'];
        $result = array();

        switch ( $action ) {
            case 'clean_orphaned_meta':
                $orphaned = flamingo_find_orphaned_contact_meta();
                foreach ( $orphaned as $meta_id ) {
                    delete_metadata_by_mid( 'post', $meta_id );
                }
                $result['message'] = sprintf(
                    __( 'Cleaned %d orphaned metadata entries.', 'flamingo' ),
                    count( $orphaned )
                );
                break;

            case 'delete_empty_contacts':
                $empty = flamingo_find_empty_contacts();
                foreach ( $empty as $post_id ) {
                    wp_delete_post( $post_id, true );
                }
                $result['message'] = sprintf(
                    __( 'Deleted %d empty contacts.', 'flamingo' ),
                    count( $empty )
                );
                break;

            case 'clean_unused_terms':
                $unused = flamingo_find_unused_terms();
                foreach ( $unused as $term ) {
                    wp_delete_term( $term->term_id, $term->taxonomy );
                }
                $result['message'] = sprintf(
                    __( 'Deleted %d unused terms.', 'flamingo' ),
                    count( $unused )
                );
                break;
        }

        if ( ! empty( $result['message'] ) ) {
            wp_admin_notice( $result['message'], array( 'type' => 'success' ) );
        }
    }

    // Display current status
    $orphaned_count = count( flamingo_find_orphaned_contact_meta() );
    $duplicate_count = count( flamingo_find_duplicate_contacts() );
    $empty_count = count( flamingo_find_empty_contacts() );
    $unused_terms_count = count( flamingo_find_unused_terms() );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html( __( 'Flamingo Tools', 'flamingo' ) ); ?></h1>

        <div class="card">
            <h2><?php echo esc_html( __( 'Database Status', 'flamingo' ) ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html( __( 'Orphaned Metadata', 'flamingo' ) ); ?></th>
                    <td>
                        <?php echo esc_html( number_format_i18n( $orphaned_count ) ); ?>
                        <?php if ( $orphaned_count > 0 ) : ?>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field( 'flamingo_tools' ); ?>
                                <input type="hidden" name="action" value="clean_orphaned_meta" />
                                <?php submit_button( __( 'Clean Up', 'flamingo' ), 'secondary small', 'submit', false ); ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html( __( 'Duplicate Contacts', 'flamingo' ) ); ?></th>
                    <td>
                        <?php echo esc_html( number_format_i18n( $duplicate_count ) ); ?>
                        <?php if ( $duplicate_count > 0 ) : ?>
                            <a href="#duplicates"><?php echo esc_html( __( 'Review', 'flamingo' ) ); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html( __( 'Empty Contacts', 'flamingo' ) ); ?></th>
                    <td>
                        <?php echo esc_html( number_format_i18n( $empty_count ) ); ?>
                        <?php if ( $empty_count > 0 ) : ?>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field( 'flamingo_tools' ); ?>
                                <input type="hidden" name="action" value="delete_empty_contacts" />
                                <?php submit_button( __( 'Delete', 'flamingo' ), 'delete small', 'submit', false ); ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html( __( 'Unused Terms', 'flamingo' ) ); ?></th>
                    <td>
                        <?php echo esc_html( number_format_i18n( $unused_terms_count ) ); ?>
                        <?php if ( $unused_terms_count > 0 ) : ?>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field( 'flamingo_tools' ); ?>
                                <input type="hidden" name="action" value="clean_unused_terms" />
                                <?php submit_button( __( 'Delete', 'flamingo' ), 'secondary small', 'submit', false ); ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <?php if ( $duplicate_count > 0 ) : ?>
            <div class="card" id="duplicates">
                <h2><?php echo esc_html( __( 'Duplicate Contacts', 'flamingo' ) ); ?></h2>
                <p><?php echo esc_html( __( 'These contacts have the same email address. You may want to merge them manually.', 'flamingo' ) ); ?></p>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html( __( 'Email', 'flamingo' ) ); ?></th>
                            <th><?php echo esc_html( __( 'Count', 'flamingo' ) ); ?></th>
                            <th><?php echo esc_html( __( 'Actions', 'flamingo' ) ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $duplicates = flamingo_find_duplicate_contacts();
                        foreach ( $duplicates as $dup ) :
                        ?>
                            <tr>
                                <td><?php echo esc_html( $dup->email ); ?></td>
                                <td><?php echo esc_html( $dup->count ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( add_query_arg( 's', $dup->email, menu_page_url( 'flamingo', false ) ) ); ?>">
                                        <?php echo esc_html( __( 'View', 'flamingo' ) ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
```

### 3. Add to Admin Menu

```php
function flamingo_add_tools_page() {
    add_submenu_page(
        'flamingo',
        __( 'Tools', 'flamingo' ),
        __( 'Tools', 'flamingo' ),
        'manage_options',
        'flamingo-tools',
        'flamingo_tools_page'
    );
}
add_action( 'admin_menu', 'flamingo_add_tools_page', 9 );
```

## Benefits
- Maintains database health
- Identifies data integrity issues
- Easy cleanup of orphaned data
- Identifies duplicate entries
- Improves plugin performance
- Reduces database size

## Safety Features
- Requires admin capabilities
- Shows preview before cleanup
- Uses nonces for security
- Logs all cleanup actions (if debug logging enabled)

## Files to Add/Modify
- Create: `admin/includes/tools.php`
- Modify: `admin/admin.php` (add submenu)

## Priority
**Priority 3 - Maintenance Feature**

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
```

---

### Issue 3: Export/Import Configuration

**Title:** Add Settings Export/Import for Easy Site Migration

**Labels:** `enhancement`, `priority-3`, `usability`

**Description:**
```markdown
## Summary
Add the ability to export and import plugin settings for easier site migration and configuration backup.

## Rationale
Users who maintain multiple WordPress sites or migrate sites would benefit from:
- Backing up plugin configuration
- Copying settings between sites
- Restoring settings after reinstallation

## Proposed Implementation

### 1. Export Function

```php
/**
 * Exports plugin settings as JSON.
 *
 * @return string JSON string of settings.
 */
function flamingo_export_settings() {
    $settings = array(
        'version' => FLAMINGO_VERSION,
        'exported_at' => current_time( 'mysql' ),
        'site_url' => get_site_url(),
        'settings' => array(
            'debug_enabled' => get_option( 'flamingo_debug_enabled', false ),
            'move_trash_days' => FLAMINGO_MOVE_TRASH_DAYS,
        ),
        // Don't export actual data, just configuration
    );

    return wp_json_encode( $settings, JSON_PRETTY_PRINT );
}

/**
 * Handles settings export download.
 */
function flamingo_handle_export() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have permission to export settings.', 'flamingo' ) );
    }

    check_admin_referer( 'flamingo_export_settings' );

    $json = flamingo_export_settings();
    $filename = 'flamingo-settings-' . date( 'Y-m-d' ) . '.json';

    header( 'Content-Type: application/json' );
    header( 'Content-Disposition: attachment; filename=' . $filename );
    header( 'Cache-Control: no-cache, no-store, must-revalidate' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );

    echo $json;
    exit;
}
```

### 2. Import Function

```php
/**
 * Imports settings from JSON.
 *
 * @param string $json JSON settings string.
 * @return WP_Error|true True on success, WP_Error on failure.
 */
function flamingo_import_settings( $json ) {
    $settings = json_decode( $json, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error(
            'invalid_json',
            __( 'Invalid JSON format.', 'flamingo' )
        );
    }

    if ( ! isset( $settings['version'] ) ) {
        return new WP_Error(
            'invalid_format',
            __( 'Invalid settings format.', 'flamingo' )
        );
    }

    // Version check
    if ( version_compare( $settings['version'], FLAMINGO_VERSION, '>' ) ) {
        return new WP_Error(
            'version_mismatch',
            sprintf(
                __( 'Settings are from a newer version (%s). Current version is %s.', 'flamingo' ),
                $settings['version'],
                FLAMINGO_VERSION
            )
        );
    }

    // Import settings
    if ( isset( $settings['settings'] ) ) {
        foreach ( $settings['settings'] as $key => $value ) {
            update_option( 'flamingo_' . $key, $value );
        }
    }

    return true;
}

/**
 * Handles settings import upload.
 */
function flamingo_handle_import() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have permission to import settings.', 'flamingo' ) );
    }

    check_admin_referer( 'flamingo_import_settings' );

    if ( empty( $_FILES['import_file'] ) ) {
        wp_die( __( 'No file uploaded.', 'flamingo' ) );
    }

    $file = $_FILES['import_file'];

    if ( $file['error'] !== UPLOAD_ERR_OK ) {
        wp_die( __( 'File upload error.', 'flamingo' ) );
    }

    $json = file_get_contents( $file['tmp_name'] );
    $result = flamingo_import_settings( $json );

    if ( is_wp_error( $result ) ) {
        wp_die( $result->get_error_message() );
    }

    wp_safe_redirect( add_query_arg(
        array(
            'page' => 'flamingo-settings',
            'imported' => '1',
        ),
        admin_url( 'admin.php' )
    ) );
    exit;
}
```

### 3. Admin UI

Add to settings page:

```php
<div class="card">
    <h2><?php echo esc_html( __( 'Export Settings', 'flamingo' ) ); ?></h2>
    <p><?php echo esc_html( __( 'Export your plugin settings as a JSON file. This includes configuration but not your actual contact or message data.', 'flamingo' ) ); ?></p>

    <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
        <?php wp_nonce_field( 'flamingo_export_settings' ); ?>
        <input type="hidden" name="action" value="flamingo_export_settings" />
        <?php submit_button( __( 'Export Settings', 'flamingo' ), 'secondary', 'submit', false ); ?>
    </form>
</div>

<div class="card">
    <h2><?php echo esc_html( __( 'Import Settings', 'flamingo' ) ); ?></h2>
    <p><?php echo esc_html( __( 'Import settings from a previously exported JSON file.', 'flamingo' ) ); ?></p>
    <p class="description"><?php echo esc_html( __( 'Warning: This will overwrite your current settings.', 'flamingo' ) ); ?></p>

    <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field( 'flamingo_import_settings' ); ?>
        <input type="hidden" name="action" value="flamingo_import_settings" />
        <input type="file" name="import_file" accept=".json" required />
        <?php submit_button( __( 'Import Settings', 'flamingo' ), 'secondary', 'submit', false ); ?>
    </form>
</div>
```

### 4. Register Admin Actions

```php
add_action( 'admin_post_flamingo_export_settings', 'flamingo_handle_export' );
add_action( 'admin_post_flamingo_import_settings', 'flamingo_handle_import' );
```

## Benefits
- Easy site migration
- Configuration backup
- Multi-site management
- Disaster recovery
- Testing/staging environments

## Security Considerations
- Requires `manage_options` capability
- Validates JSON format
- Version compatibility checks
- Nonce verification
- Does not export sensitive data (API keys would need special handling)

## Files to Modify
- Create: `admin/includes/import-export.php`
- Modify: `admin/admin.php` (add settings page sections)

## Future Enhancements
- Export/import contact tags
- Export/import channel configurations
- Selective import (choose what to import)

## Priority
**Priority 3 - Usability Enhancement**

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
```
