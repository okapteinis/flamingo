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

Use WordPress's built-in `update_post_meta_cache()` function which properly primes the cache for subsequent `get_post_meta()` calls:

```php
/**
 * Prefetch metadata for multiple posts to prevent N+1 queries.
 *
 * @param array $post_ids Array of post IDs to prefetch meta for.
 */
function flamingo_prefetch_meta( $post_ids ) {
    if ( empty( $post_ids ) ) {
        return;
    }

    // Use WordPress built-in function to prime meta cache
    // This properly integrates with get_post_meta() calls
    update_post_meta_cache( $post_ids );
}
```

### Usage in List Views
```php
// In admin list table prepare_items()
$posts = $this->items;
$post_ids = wp_list_pluck( $posts, 'ID' );

// Prime the meta cache before iterating
flamingo_prefetch_meta( $post_ids );

// Now get_post_meta() calls will use cached data
foreach ( $posts as $post ) {
    $meta = get_post_meta( $post->ID, '_flamingo_email', true );
    // No additional query - uses primed cache
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
