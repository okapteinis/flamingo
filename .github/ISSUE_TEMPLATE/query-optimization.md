---
name: Query Optimization
about: Fix N+1 query issues in list views
title: 'Optimize Database Queries to Fix N+1 Issues'
labels: performance, priority-2, enhancement
assignees: okapteinis
---

## Summary
Implement eager loading pattern to fix N+1 query issues in contact and message list views.

## Current Issue
Each row in list views triggers individual queries for metadata, causing performance issues with large datasets.

## Proposed Solution

```php
function flamingo_prefetch_meta( $post_ids, $meta_keys ) {
    global $wpdb;

    if ( empty( $post_ids ) || empty( $meta_keys ) ) {
        return;
    }

    $post_ids_placeholder = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
    $meta_keys_placeholder = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

    $query = $wpdb->prepare(
        "SELECT post_id, meta_key, meta_value
         FROM {$wpdb->postmeta}
         WHERE post_id IN ($post_ids_placeholder)
         AND meta_key IN ($meta_keys_placeholder)",
        array_merge( $post_ids, $meta_keys )
    );

    $results = $wpdb->get_results( $query );

    // Prime the WordPress object cache
    foreach ( $results as $row ) {
        wp_cache_set(
            $row->post_id . '_' . $row->meta_key,
            $row->meta_value,
            'flamingo_meta'
        );
    }
}
```

## Benefits
- Single query instead of N+1 queries
- Faster list view loading
- Reduced database load

## Files to Modify
- `includes/class-contact.php`
- `includes/class-inbound-message.php`
- `admin/admin.php`

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
