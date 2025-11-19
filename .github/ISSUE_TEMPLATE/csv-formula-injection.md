---
name: CSV Formula Injection Protection
about: Enhance CSV export formula injection protection
title: 'Enhance CSV Formula Injection Protection'
labels: security, priority-1, enhancement
assignees: okapteinis
---

## Summary
Improve the CSV export formula injection protection to use standard mitigation techniques instead of warning messages.

## Current Implementation
The plugin currently detects formula triggers (`=`, `+`, `-`, `@`) and adds a warning message prefix. While this alerts users, it may not prevent exploitation in all spreadsheet applications.

## Proposed Solution
Prepend a single quote (`'`) to cells starting with formula triggers, which is the industry-standard mitigation:

```php
function flamingo_csv_field_prefix_text( $prefix, $input ) {
    $formula_triggers = array( '=', '+', '-', '@' );

    if ( in_array( substr( $input, 0, 1 ), $formula_triggers, true ) ) {
        $prefix = "'";  // Prevents formula execution in spreadsheet apps
    }

    return $prefix;
}
```

## Benefits
- Industry-standard protection
- Prevents formula execution in Excel, LibreOffice, Google Sheets
- Simpler implementation
- Better user experience (no warning text clutter)

## Files to Modify
- `includes/csv.php` (lines 225-243)

## References
- [OWASP CSV Injection](https://owasp.org/www-community/attacks/CSV_Injection)

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
