# Comparison of Main vs Develop Branches for AIText Question Type Plugin

## Overview
This document provides a summary of the differences between the main and develop branches of the AIText question type plugin for Moodle. The AIText question type allows teachers to create questions that are automatically graded using AI (Artificial Intelligence) technology.

## Key Differences

### Main Branch Features
The main branch contains the stable, released version of the plugin (version 1.00) with the following key features:

- **Stable release**: Version 1.00 released in October 2025
- **Moodle 5.1 compatibility**: Confirmed compatibility with Moodle 5.1 through automated tests
- **HTML rendering fix**: Switched sample response evaluation to render HTML properly instead of showing HTML tags
- **Bug fixes**: Includes fixes for reported issues related to HTML rendering and other functionality

### Develop Branch Features
The develop branch contains work-in-progress changes focused on:

- **Backup and Restore Functionality**: Enhanced backup and restore capabilities for AIText questions
  - Fixes for backup restoration issues with hashing and default values
  - Removal of problematic ID processing during backup restoration
  - New test files for backup/restore functionality
  - Improved test coverage for backup/restore scenarios

## Technical Changes

### Files Modified in Develop Branch
- **Backup/Restore Handler**: Improvements to `restore_qtype_aitext_plugin.class.php` to fix backup restoration issues
- **Question Logic**: Minor changes to `question.php` and `renderer.php` for better functionality
- **Testing**: Added new test files (`aitext_repeated_restore_test.php`) and test fixtures to ensure proper functionality
- **Form Handling**: Minor updates to `edit_aitext_form.php` for question editing

### Testing Improvements
The develop branch includes additional automated tests to ensure backup and restore functionality works correctly, which is important for Moodle sites that regularly backup and restore course content.

## Impact for Users

### For Site Administrators
- The develop branch addresses potential issues with backup and restore functionality
- If you frequently backup and restore courses containing AIText questions, the changes in the develop branch may be beneficial
- The main branch represents the current stable release with known compatibility

### For Teachers and Students
- No visible changes to the question interface or functionality for end users
- Both branches provide the same core AI-powered question grading experience
- The changes primarily affect the technical infrastructure rather than user experience

## Recommendation

- **Main branch**: Recommended for production environments where stability is the priority
- **Develop branch**: Suitable for testing environments where backup/restore functionality is critical and you want to try the latest fixes

The develop branch appears to be preparing for a future release that improves the reliability of backup and restore operations, which is a critical function in Moodle environments.