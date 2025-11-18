# Priority 1: Security Enhancements

## Issues to Create

### Issue 1: Enhanced CSV Formula Injection Protection

**Title:** Enhance CSV Formula Injection Protection

**Labels:** `security`, `priority-1`, `enhancement`

**Description:**
```markdown
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
- [Security Audit Report](./claude.md)

## Priority
**Priority 1 - Security Enhancement**

## Assignee
@okapteinis

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
```

---

### Issue 2: Implement Additional Input Validation

**Title:** Add Comprehensive Input Validation for Contact and Message Data

**Labels:** `security`, `priority-1`, `enhancement`

**Description:**
```markdown
## Summary
Implement comprehensive input validation layer before data sanitization to ensure data type consistency and prevent edge cases.

## Current State
The plugin now sanitizes inputs but lacks comprehensive validation for:
- Email address format validation
- Field length limits
- Data type validation
- Required field checking

## Proposed Implementation

### 1. Contact Validation Function
```php
/**
 * Validates contact data before saving.
 *
 * @param array $data Contact data to validate.
 * @return WP_Error|true True if valid, WP_Error if invalid.
 */
function flamingo_validate_contact_data( $data ) {
    $errors = new WP_Error();

    // Email validation
    if ( empty( $data['email'] ) || ! is_email( $data['email'] ) ) {
        $errors->add( 'invalid_email', __( 'Invalid email address.', 'flamingo' ) );
    }

    // Email length check
    if ( strlen( $data['email'] ) > 254 ) {
        $errors->add( 'email_too_long', __( 'Email address too long.', 'flamingo' ) );
    }

    // Name length check
    if ( ! empty( $data['name'] ) && strlen( $data['name'] ) > 200 ) {
        $errors->add( 'name_too_long', __( 'Name too long (max 200 characters).', 'flamingo' ) );
    }

    // Validate props array
    if ( isset( $data['props'] ) && ! is_array( $data['props'] ) ) {
        $errors->add( 'invalid_props', __( 'Contact properties must be an array.', 'flamingo' ) );
    }

    return $errors->has_errors() ? $errors : true;
}
```

### 2. Message Validation Function
```php
/**
 * Validates inbound message data before saving.
 *
 * @param array $data Message data to validate.
 * @return WP_Error|true True if valid, WP_Error if invalid.
 */
function flamingo_validate_message_data( $data ) {
    $errors = new WP_Error();

    // Subject length check
    if ( ! empty( $data['subject'] ) && strlen( $data['subject'] ) > 255 ) {
        $errors->add( 'subject_too_long', __( 'Subject too long (max 255 characters).', 'flamingo' ) );
    }

    // From email validation
    if ( ! empty( $data['from_email'] ) && ! is_email( $data['from_email'] ) ) {
        $errors->add( 'invalid_from_email', __( 'Invalid sender email address.', 'flamingo' ) );
    }

    // Fields validation
    if ( isset( $data['fields'] ) && ! is_array( $data['fields'] ) ) {
        $errors->add( 'invalid_fields', __( 'Message fields must be an array.', 'flamingo' ) );
    }

    return $errors->has_errors() ? $errors : true;
}
```

### 3. Integration Points

**In `admin/admin.php` (contact save):**
```php
// After check_admin_referer, before sanitization
$validation = flamingo_validate_contact_data( $_POST['contact'] );
if ( is_wp_error( $validation ) ) {
    wp_die( $validation->get_error_message() );
}
```

**In `Flamingo_Contact::add()`:**
```php
$validation = flamingo_validate_contact_data( $args );
if ( is_wp_error( $validation ) ) {
    error_log( 'Flamingo: Invalid contact data - ' . $validation->get_error_message() );
    return null;
}
```

## Benefits
- Prevents invalid data from entering the system
- Improves data quality
- Provides clear error messages to users
- Prevents edge cases and unexpected behavior
- Complements existing sanitization

## Files to Modify
- `includes/functions.php` (add validation functions)
- `admin/admin.php` (integrate validation)
- `includes/class-contact.php` (integrate validation)
- `includes/class-inbound-message.php` (integrate validation)

## Testing Requirements
- Unit tests for validation functions
- Integration tests for admin save flow
- Edge case testing (empty strings, null values, extremely long strings)

## Priority
**Priority 1 - Security Enhancement**

## Related Issues
- Complements existing input sanitization from commit 36fe947

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
```
