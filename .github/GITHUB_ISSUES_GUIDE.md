# GitHub Issues Creation Guide

This guide helps you create GitHub issues from the templates provided for the Flamingo plugin improvements.

## Quick Start

The issue templates are organized by priority in the `.github/ISSUE_TEMPLATES/` directory:

1. **priority-1-security.md** - Critical security enhancements (2 issues)
2. **priority-2-code-quality.md** - Performance and code quality (3 issues)
3. **priority-3-features.md** - Feature enhancements (3 issues)

## Creating Issues

### Method 1: Manual Creation from Templates

1. Go to your GitHub repository
2. Click "Issues" → "New Issue"
3. Open the template file for the priority level
4. Copy the content from each issue section
5. Paste into GitHub issue form
6. Add the appropriate labels
7. Assign to team members if desired

### Method 2: GitHub CLI (Recommended)

If you have [GitHub CLI](https://cli.github.com/) installed:

```bash
# Navigate to repository
cd /path/to/flamingo

# Create Priority 1 issues
gh issue create --title "Enhance CSV Formula Injection Protection" \
  --label "security,priority-1,enhancement" \
  --body-file .github/ISSUE_TEMPLATES/priority-1-security.md

# Repeat for each issue...
```

### Method 3: Bulk Creation Script

Create a script to automate issue creation:

```bash
#!/bin/bash

# Priority 1: Security Enhancements
gh issue create --title "Enhance CSV Formula Injection Protection" \
  --label "security,priority-1,enhancement" \
  --body "See .github/ISSUE_TEMPLATES/priority-1-security.md - Issue 1"

gh issue create --title "Add Comprehensive Input Validation for Contact and Message Data" \
  --label "security,priority-1,enhancement" \
  --body "See .github/ISSUE_TEMPLATES/priority-1-security.md - Issue 2"

# Priority 2: Code Quality
gh issue create --title "Optimize CSV Export for Large Datasets with Batched Processing" \
  --label "performance,priority-2,enhancement" \
  --body "See .github/ISSUE_TEMPLATES/priority-2-code-quality.md - Issue 1"

gh issue create --title "Fix N+1 Query Problem in Contact List Table History Column" \
  --label "performance,priority-2,bug" \
  --body "See .github/ISSUE_TEMPLATES/priority-2-code-quality.md - Issue 2"

gh issue create --title "Add Transient Caching for Frequently Accessed Taxonomy Terms" \
  --label "performance,priority-2,enhancement" \
  --body "See .github/ISSUE_TEMPLATES/priority-2-code-quality.md - Issue 3"

# Priority 3: Features
gh issue create --title "Implement Configurable Debug Logging for Troubleshooting" \
  --label "enhancement,priority-3,developer-experience" \
  --body "See .github/ISSUE_TEMPLATES/priority-3-features.md - Issue 1"

gh issue create --title "Add Database Cleanup and Data Integrity Verification Tools" \
  --label "enhancement,priority-3,maintenance" \
  --body "See .github/ISSUE_TEMPLATES/priority-3-features.md - Issue 2"

gh issue create --title "Add Settings Export/Import for Easy Site Migration" \
  --label "enhancement,priority-3,usability" \
  --body "See .github/ISSUE_TEMPLATES/priority-3-features.md - Issue 3"
```

Save as `.github/create-issues.sh`, make executable, and run:

```bash
chmod +x .github/create-issues.sh
./.github/create-issues.sh
```

## Issue Summary

### Priority 1: Security (2 issues)

**CRITICAL - Address First**

1. **Enhance CSV Formula Injection Protection**
   - Improve CSV export security
   - Use industry-standard mitigation
   - Files: `includes/csv.php`

2. **Add Comprehensive Input Validation**
   - Validation layer before sanitization
   - Email format validation
   - Field length limits
   - Files: Multiple

### Priority 2: Code Quality (3 issues)

**HIGH - Improves Performance and Maintainability**

1. **Optimize CSV Export for Large Datasets**
   - Batch processing for exports
   - Prevents memory exhaustion
   - Files: `includes/csv.php`

2. **Fix N+1 Query Problem in Contact History**
   - Optimize database queries
   - Single query instead of loop
   - Files: `admin/includes/class-contacts-list-table.php`

3. **Add Transient Caching for Taxonomy Terms**
   - Cache frequently accessed terms
   - Reduce database load
   - Files: Multiple

### Priority 3: Features (3 issues)

**MEDIUM - Enhances User Experience**

1. **Implement Configurable Debug Logging**
   - Admin logging interface
   - Troubleshooting tools
   - Files: New + multiple

2. **Add Database Cleanup Tools**
   - Orphaned data cleanup
   - Data integrity checks
   - Files: New admin tools page

3. **Add Settings Export/Import**
   - Easy site migration
   - Configuration backup
   - Files: New import/export functions

## Labels to Use

Create these labels in your repository:

- `security` - Security-related issues
- `performance` - Performance improvements
- `enhancement` - New features or improvements
- `bug` - Something isn't working
- `priority-1` - Critical priority
- `priority-2` - High priority
- `priority-3` - Medium priority
- `developer-experience` - Improves developer workflow
- `maintenance` - Maintenance and cleanup
- `usability` - User experience improvements

## Issue Organization

### Milestones

Consider creating milestones:

- **Milestone 1: Security Hardening** - Priority 1 issues
- **Milestone 2: Performance Optimization** - Priority 2 issues
- **Milestone 3: Feature Enhancements** - Priority 3 issues

### Project Board

Create a project board with columns:

- **Backlog** - All issues start here
- **To Do** - Ready to be worked on
- **In Progress** - Currently being developed
- **In Review** - Awaiting code review
- **Done** - Completed and merged

## Implementation Order

Recommended implementation sequence:

1. **Week 1-2:** Priority 1 Security Enhancements
   - Issue #1: CSV Formula Injection Protection (2-3 hours)
   - Issue #2: Input Validation Layer (4-6 hours)
   - Testing and security review (2-3 hours)

2. **Week 3-4:** Priority 2 Code Quality
   - Issue #1: CSV Export Optimization (4-6 hours)
   - Issue #2: N+1 Query Fix (3-4 hours)
   - Issue #3: Transient Caching (3-4 hours)
   - Performance testing (2-3 hours)

3. **Week 5-6:** Priority 3 Features
   - Issue #1: Debug Logging (6-8 hours)
   - Issue #2: Cleanup Tools (5-7 hours)
   - Issue #3: Export/Import (4-6 hours)
   - Documentation and testing (3-4 hours)

**Total Estimated Time:** 38-55 hours (5-7 weeks part-time)

## Additional Resources

- [Security Audit Report](../claude.md) - Full audit findings
- [Test Suite](../tests/README.md) - Testing documentation
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [Plugin Security Best Practices](https://developer.wordpress.org/plugins/security/)

## Notes

- All issues include code examples and implementation guidance
- Each issue specifies files to modify
- Testing requirements are included
- Co-authorship is acknowledged in each template

## Co-Authors

These issue templates were created by:
- Claude (code@claude.ai)
- Ojārs Kapteinis (ojars@kapteinis.lv)

---

**Questions or Issues?**

If you need clarification on any issue template or implementation guidance, please refer to the full audit report in `claude.md` or create a discussion in the repository.
