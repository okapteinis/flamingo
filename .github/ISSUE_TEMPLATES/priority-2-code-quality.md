# Priority 2: Code Quality Improvements

## Issues to Create

### Issue 1: Implement Batched CSV Export Processing

**Title:** Optimize CSV Export for Large Datasets with Batched Processing

**Labels:** `performance`, `priority-2`, `enhancement`

**Description:**
```markdown
## Summary
Implement batched processing and streaming output for CSV exports to prevent memory exhaustion and timeouts on sites with large contact/message databases.

## Current Issue
CSV export currently retrieves all records at once using `'posts_per_page' => -1`, which can cause:
- PHP memory exhaustion on large datasets (>10,000 records)
- Script timeouts
- Poor user experience

**Location:** `includes/csv.php` lines 46-90 (Contact CSV) and 106-180 (Inbound CSV)

## Proposed Solution

### 1. Batched Contact CSV Export
```php
public function print_data() {
    $labels = array(
        __( 'Email', 'flamingo' ),
        __( 'Full name', 'flamingo' ),
        __( 'First name', 'flamingo' ),
        __( 'Last name', 'flamingo' ),
    );

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo flamingo_csv_row( $labels );

    $batch_size = 100;
    $offset = 0;
    $has_more = true;

    while ( $has_more ) {
        $args = array(
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_key' => '_email',
        );

        // Apply filters from GET parameters
        if ( ! empty( $_GET['s'] ) ) {
            $args['s'] = $_GET['s'];
        }

        if ( ! empty( $_GET['orderby'] ) ) {
            if ( 'email' === $_GET['orderby'] ) {
                $args['meta_key'] = '_email';
            } elseif ( 'name' === $_GET['orderby'] ) {
                $args['meta_key'] = '_name';
            }
        }

        if (
            ! empty( $_GET['order'] ) and
            'asc' === strtolower( $_GET['order'] )
        ) {
            $args['order'] = 'ASC';
        }

        if ( ! empty( $_GET['contact_tag_id'] ) ) {
            $args['contact_tag_id'] = explode( ',', $_GET['contact_tag_id'] );
        }

        $items = Flamingo_Contact::find( $args );

        if ( empty( $items ) || count( $items ) < $batch_size ) {
            $has_more = false;
        }

        foreach ( $items as $item ) {
            echo "\r\n";

            $row = array(
                $item->email,
                $item->get_prop( 'name' ),
                $item->get_prop( 'first_name' ),
                $item->get_prop( 'last_name' ),
            );

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo flamingo_csv_row( $row );

            // Flush output buffer to send data immediately
            if ( ob_get_level() > 0 ) {
                ob_flush();
            }
            flush();
        }

        $offset += $batch_size;

        // Prevent infinite loops
        if ( $offset > 100000 ) {
            break;
        }
    }
}
```

### 2. Similar Implementation for Inbound CSV Export

Apply the same batching logic to `Flamingo_Inbound_CSV::print_data()`.

### 3. Add Progress Feedback

For very large exports, consider adding a progress indicator or download link:

```php
// In admin/admin.php, before starting export
if ( Flamingo_Contact::count() > 1000 ) {
    wp_admin_notice(
        __( 'Large export detected. This may take a few moments...', 'flamingo' ),
        array( 'type' => 'info' )
    );
}
```

## Benefits
- Handles datasets of 100,000+ records
- Prevents PHP memory exhaustion
- Prevents script timeouts
- Streams data to browser (user sees progress)
- Better server resource utilization

## Testing Requirements
- Test with 100 contacts/messages
- Test with 1,000 contacts/messages
- Test with 10,000+ contacts/messages
- Monitor memory usage during export
- Test with various filter combinations

## Files to Modify
- `includes/csv.php` (Flamingo_Contact_CSV and Flamingo_Inbound_CSV classes)
- Consider adding progress UI in admin pages

## Priority
**Priority 2 - Performance Enhancement**

## Related Issues
- Performance optimization identified in security audit

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
```

---

### Issue 2: Optimize Database Queries in Contact History

**Title:** Fix N+1 Query Problem in Contact List Table History Column

**Labels:** `performance`, `priority-2`, `bug`

**Description:**
```markdown
## Summary
Fix N+1 query problem in contact list table that causes performance issues when displaying contact history.

## Current Issue
In `admin/includes/class-contacts-list-table.php`, the `column_history()` method executes a separate query for each contact channel to count messages:

**Location:** Lines 288-293
```php
foreach ( (array) $terms as $term ) {
    Flamingo_Inbound_Message::find( array(
        'channel' => $term->slug,
        's' => $item->email,
    ) );

    $count = (int) Flamingo_Inbound_Message::count();
    // ...
}
```

If there are 5 channels and 20 contacts displayed, this executes 100 additional queries!

## Proposed Solution

### 1. Cache Channel Counts
```php
protected function column_history( $item ) {
    static $channel_counts_cache = array();
    $cache_key = 'contact_' . $item->id();

    if ( ! isset( $channel_counts_cache[$cache_key] ) ) {
        $history = array();

        // User
        if ( $user = get_user_by( 'email', $item->email ) ) {
            $link = sprintf( 'user-edit.php?user_id=%d', $user->ID );

            $history[] = sprintf(
                '<a href="%2$s">%1$s</a>',
                esc_html( __( 'User', 'flamingo' ) ),
                admin_url( $link )
            );
        }

        // Comment (already optimized)
        $comment_count = (int) get_comments( array(
            'count' => true,
            'author_email' => $item->email,
            'status' => 'approve',
            'type' => 'comment',
        ) );

        if ( 0 < $comment_count ) {
            $link = sprintf( 'edit-comments.php?s=%s', urlencode( $item->email ) );

            $history[] = sprintf(
                '<a href="%2$s">%1$s</a>',
                esc_html( sprintf(
                    __( 'Comment (%d)', 'flamingo' ),
                    $comment_count
                ) ),
                admin_url( $link )
            );
        }

        // Contact channels - use a single query
        global $wpdb;

        $channel_counts = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.slug, t.name, COUNT(p.ID) as count
            FROM {$wpdb->term_taxonomy} tt
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE tt.taxonomy = %s
            AND pm.meta_key = '_from_email'
            AND pm.meta_value = %s
            AND p.post_status != 'trash'
            GROUP BY t.slug, t.name",
            Flamingo_Inbound_Message::channel_taxonomy,
            $item->email
        ) );

        foreach ( $channel_counts as $channel ) {
            if ( $channel->count > 0 ) {
                $link = add_query_arg( array(
                    'channel' => $channel->slug,
                    's' => $item->email,
                ), menu_page_url( 'flamingo_inbound', false ) );

                $history[] = sprintf(
                    '<a href="%2$s">%1$s</a>',
                    esc_html( sprintf(
                        _x( '%1$s (%2$d)', 'contact history', 'flamingo' ),
                        $channel->name,
                        $channel->count
                    ) ),
                    esc_url( $link )
                );
            }
        }

        $channel_counts_cache[$cache_key] = $history;
    }

    $history = $channel_counts_cache[$cache_key];

    $output = '';

    foreach ( $history as $item ) {
        $output .= sprintf( '<li>%s</li>', $item );
    }

    return sprintf( '<ul class="contact-history">%s</ul>', $output );
}
```

### 2. Add Object Caching

For even better performance with persistent object cache:

```php
$cache_key = 'flamingo_contact_history_' . $item->id();
$history = wp_cache_get( $cache_key, 'flamingo' );

if ( false === $history ) {
    // Build history array
    // ...

    wp_cache_set( $cache_key, $history, 'flamingo', HOUR_IN_SECONDS );
}
```

## Benefits
- Reduces queries from O(n*m) to O(n) where n=contacts, m=channels
- Dramatically improves page load time with many contacts
- Reduces database load
- Improves user experience

## Performance Impact
- **Before:** 20 contacts × 5 channels = 100 extra queries
- **After:** 1 query per page load (or cached)

## Files to Modify
- `admin/includes/class-contacts-list-table.php` (lines 246-325)

## Testing Requirements
- Test with 0 messages per contact
- Test with 100+ messages per contact
- Test with multiple channels
- Monitor query count before/after (use Query Monitor plugin)
- Test with WordPress object cache enabled

## Priority
**Priority 2 - Performance Bug Fix**

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
```

---

### Issue 3: Implement Transient Caching for Taxonomy Terms

**Title:** Add Transient Caching for Frequently Accessed Taxonomy Terms

**Labels:** `performance`, `priority-2`, `enhancement`

**Description:**
```markdown
## Summary
Implement transient caching for taxonomy terms that don't change frequently to reduce database queries.

## Rationale
Taxonomy terms (contact tags, message channels) are queried frequently but rarely change. Caching them reduces unnecessary database load.

## Implementation

### 1. Contact Tags Caching
```php
/**
 * Gets contact tags with caching.
 *
 * @param array $args Optional. Arguments to pass to get_terms().
 * @return array|WP_Error Array of term objects or WP_Error on failure.
 */
function flamingo_get_contact_tags( $args = array() ) {
    $cache_key = 'flamingo_contact_tags_' . md5( serialize( $args ) );
    $tags = get_transient( $cache_key );

    if ( false === $tags ) {
        $tags = get_terms( array_merge(
            array( 'taxonomy' => Flamingo_Contact::contact_tag_taxonomy ),
            $args
        ) );

        if ( ! is_wp_error( $tags ) ) {
            set_transient( $cache_key, $tags, HOUR_IN_SECONDS );
        }
    }

    return $tags;
}
```

### 2. Message Channels Caching
```php
/**
 * Gets message channels with caching.
 *
 * @param array $args Optional. Arguments to pass to get_terms().
 * @return array|WP_Error Array of term objects or WP_Error on failure.
 */
function flamingo_get_channels( $args = array() ) {
    $cache_key = 'flamingo_channels_' . md5( serialize( $args ) );
    $channels = get_transient( $cache_key );

    if ( false === $channels ) {
        $channels = get_terms( array_merge(
            array( 'taxonomy' => Flamingo_Inbound_Message::channel_taxonomy ),
            $args
        ) );

        if ( ! is_wp_error( $channels ) ) {
            set_transient( $cache_key, $channels, HOUR_IN_SECONDS );
        }
    }

    return $channels;
}
```

### 3. Cache Invalidation
```php
/**
 * Clears taxonomy term caches.
 *
 * @param int $term_id Term ID.
 * @param int $tt_id Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 */
function flamingo_clear_taxonomy_cache( $term_id, $tt_id, $taxonomy ) {
    if ( in_array( $taxonomy, array(
        Flamingo_Contact::contact_tag_taxonomy,
        Flamingo_Inbound_Message::channel_taxonomy,
    ), true ) ) {
        // Clear all related transients
        global $wpdb;

        $pattern = $wpdb->esc_like( '_transient_flamingo_' ) . '%';

        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s",
            $pattern
        ) );
    }
}

add_action( 'created_term', 'flamingo_clear_taxonomy_cache', 10, 3 );
add_action( 'edited_term', 'flamingo_clear_taxonomy_cache', 10, 3 );
add_action( 'delete_term', 'flamingo_clear_taxonomy_cache', 10, 3 );
```

### 4. Update Existing Code

Replace direct `get_terms()` calls with cached functions:

**In `admin/includes/meta-boxes.php`:**
```php
// Before
$most_used_tags = get_terms( array(
    'taxonomy' => Flamingo_Contact::contact_tag_taxonomy,
    // ...
) );

// After
$most_used_tags = flamingo_get_contact_tags( array(
    'orderby' => 'count',
    'order' => 'DESC',
    'number' => 10,
    'exclude' => $tag_ids,
    'fields' => 'names',
) );
```

**In list tables:**
```php
// Before
$terms = get_terms( array(
    'taxonomy' => Flamingo_Inbound_Message::channel_taxonomy,
) );

// After
$terms = flamingo_get_channels();
```

## Benefits
- Reduces database queries on every page load
- Improves admin page response time
- Works with WordPress object cache if available
- Automatic cache invalidation when terms change

## Files to Modify
- `includes/functions.php` (add cache functions)
- `admin/includes/meta-boxes.php` (use cached functions)
- `admin/includes/class-contacts-list-table.php` (use cached functions)
- `admin/includes/class-inbound-messages-list-table.php` (use cached functions)

## Testing Requirements
- Test that cached terms are used
- Test cache invalidation on term create/update/delete
- Monitor query count reduction
- Test with WordPress object cache enabled
- Test transient expiration (wait 1 hour)

## Priority
**Priority 2 - Performance Enhancement**

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
```
