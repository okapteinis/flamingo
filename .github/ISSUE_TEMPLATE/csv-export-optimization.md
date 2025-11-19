---
name: CSV Export Optimization
about: Optimize CSV export for large datasets with batched processing
title: 'Optimize CSV Export with Batched Processing and Limits'
labels: performance, priority-2, enhancement
assignees: okapteinis
---

## Summary
Implement batched processing for CSV exports to handle large datasets efficiently and prevent memory exhaustion.

## Current Issue
Large exports (10,000+ records) can exhaust PHP memory limits and cause timeouts.

## Proposed Solution

### 1. Define Export Limits
```php
/**
 * Maximum number of records for CSV export.
 * @var int
 */
const FLAMINGO_CSV_EXPORT_LIMIT = 100000;
```

### 2. Implement Batched Processing
```php
function flamingo_export_csv_batched( $args, $batch_size = 500 ) {
    $total = flamingo_get_total_count( $args );

    if ( $total > FLAMINGO_CSV_EXPORT_LIMIT ) {
        error_log( sprintf(
            'Flamingo: CSV export truncated. Requested %d records, limit is %d.',
            $total,
            FLAMINGO_CSV_EXPORT_LIMIT
        ) );
        $total = FLAMINGO_CSV_EXPORT_LIMIT;
    }

    $offset = 0;
    while ( $offset < $total ) {
        $batch = flamingo_get_records( array_merge( $args, array(
            'offset' => $offset,
            'limit' => $batch_size,
        ) ) );

        foreach ( $batch as $record ) {
            flamingo_output_csv_row( $record );
        }

        $offset += $batch_size;

        // Clear memory between batches
        wp_cache_flush();
    }
}
```

## Benefits
- Handles large datasets without memory issues
- User notification when export is truncated
- Proper logging for monitoring

## Files to Modify
- `includes/csv.php`

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
