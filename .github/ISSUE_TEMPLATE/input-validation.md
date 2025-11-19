---
name: Input Validation
about: Add comprehensive input validation for contact and message data
title: 'Add Comprehensive Input Validation for Contact and Message Data'
labels: security, priority-1, enhancement
assignees: okapteinis
---

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
function flamingo_validate_contact_data( $data ) {
    $errors = new WP_Error();

    // Email validation with proper elseif chain
    if ( empty( $data['email'] ) ) {
        $errors->add( 'empty_email', __( 'Email address is required.', 'flamingo' ) );
    } elseif ( strlen( $data['email'] ) > 254 ) {
        $errors->add( 'email_too_long', __( 'Email address too long.', 'flamingo' ) );
    } elseif ( ! is_email( $data['email'] ) ) {
        $errors->add( 'invalid_email', __( 'Invalid email address format.', 'flamingo' ) );
    }

    // Name length check
    if ( ! empty( $data['name'] ) && strlen( $data['name'] ) > 200 ) {
        $errors->add( 'name_too_long', __( 'Name too long (max 200 characters).', 'flamingo' ) );
    }

    return $errors->has_errors() ? $errors : true;
}
```

### 2. Message Validation Function
```php
function flamingo_validate_message_data( $data ) {
    $errors = new WP_Error();

    // Subject length check
    if ( ! empty( $data['subject'] ) && strlen( $data['subject'] ) > 255 ) {
        $errors->add( 'subject_too_long', __( 'Subject too long.', 'flamingo' ) );
    }

    // From email validation
    if ( ! empty( $data['from_email'] ) && ! is_email( $data['from_email'] ) ) {
        $errors->add( 'invalid_from_email', __( 'Invalid sender email.', 'flamingo' ) );
    }

    return $errors->has_errors() ? $errors : true;
}
```

## Files to Modify
- `admin/admin.php`
- `includes/class-contact.php`
- `includes/class-inbound-message.php`

## Co-Authors
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)
