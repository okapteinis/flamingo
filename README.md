# Flamingo

Message storage plugin for Contact Form 7 - independent development repository.

## About This Repository

This is an independent development repository for the Flamingo plugin (version 2.6), originally created for Contact Form 7 message storage and contact management.

**Original Plugin:** [Flamingo](https://wordpress.org/plugins/flamingo/)
**Original Author:** Takayuki Miyoshi
**Contributors:** takayukister, megumithemes, itpixelz
**Original License:** GPLv2 or later

## Description

Flamingo is a trustworthy message storage plugin originally created for [Contact Form 7](https://wordpress.org/plugins/contact-form-7/), which doesn't store submitted messages by default.

After activation of the plugin, you'll find **Flamingo** in the WordPress admin menu. All messages submitted through contact forms are listed there and are searchable. With Flamingo, you no longer need to worry about losing important messages due to mail server issues or misconfiguration in mail setup.

## Key Features

- **Message Storage** - Stores all Contact Form 7 submissions in the database
- **Searchable Archive** - Search through all stored messages
- **Contact Management** - Manage contact information from form submissions
- **Reliable Backup** - Never lose important messages due to email issues
- **Privacy-Focused** - Stores data securely in your WordPress database
- **Easy Access** - All messages accessible from WordPress admin panel

## Requirements

- **WordPress:** 6.7 or higher
- **PHP:** 7.4 or higher
- **Tested up to:** WordPress 6.8
- **Contact Form 7:** Recommended (but not required)

## Installation

1. Upload the `flamingo` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access **Flamingo** from the WordPress admin menu

## How It Works

Once activated, Flamingo automatically:
1. Captures all form submissions sent through Contact Form 7
2. Stores messages in your WordPress database
3. Creates an organized, searchable archive
4. Manages contact information from submissions

## Privacy Notice

**Important:** This plugin stores submission data collected through contact forms, which may include the submitters' personal information, in the database on the server that hosts the website.

Make sure you:
- Comply with data protection regulations (GDPR, etc.)
- Inform users about data storage in your privacy policy
- Have proper data handling procedures in place

## Version Information

- **Current Version:** 2.6
- **Stable Tag:** 2.6
- **Minimum WordPress:** 6.7
- **Minimum PHP:** 7.4

## Changelog

### Version 2.6
- Bumps up the minimum required WordPress version to 6.7
- Fixes errors reported by PCP
- Performs a tune-up for the cron job scheduling

### Version 2.5
- Bumps up the minimum required WordPress version to 6.4
- Uses `wp_json_encode()` instead of `json_encode()`
- Uses `get_views_links()`
- Uses null coalescing operators

## Development

This repository is maintained as an independent development fork.

- **Main branch:** Stable releases only
- **Nightly branch:** Active development (default)

All development work happens on the `nightly` branch. Only tested, stable changes are merged to `main`.

## Usage

After installation:

1. **View Messages:** Go to **Flamingo → Inbound Messages** in your WordPress admin
2. **Search Messages:** Use the search function to find specific messages
3. **View Contacts:** Go to **Flamingo → Address Book** to see contact information
4. **Export Data:** Export messages and contacts as needed

## Integration with Contact Form 7

Flamingo works seamlessly with Contact Form 7:
- Automatically captures all CF7 form submissions
- No additional configuration needed
- Works with all CF7 form types
- Preserves all form field data

## Documentation

For detailed documentation, visit:
- [Contact Form 7 Documentation](https://contactform7.com/save-submitted-messages-with-flamingo/)
- [WordPress.org Plugin Page](https://wordpress.org/plugins/flamingo/)

## Support

For official plugin support, visit the [WordPress.org support forum](https://wordpress.org/support/plugin/flamingo/).

For issues specific to this fork, use the GitHub issue tracker.

## License

GPLv2 or later - Same as the original plugin

See license.txt file for the complete GNU General Public License Version 2.

## Credits

- Original plugin by Takayuki Miyoshi
- Contributors: takayukister, megumithemes, itpixelz
- Independent development by Ojārs Kapteinis
- Co-developed with Claude AI assistance

## Donate

Support the original plugin development: [Donate to Contact Form 7](https://contactform7.com/donate/)

## Disclaimer

This is an independent development repository. For the official version and support, please visit the [original plugin page](https://wordpress.org/plugins/flamingo/).

## Tags

bird, contact, mail, crm, contact-form-7, message-storage, form-submission
