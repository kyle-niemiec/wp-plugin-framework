# Tutorial: A plugin building adventure

This tutorial walks through building a plugin the “WPPF way.” It is opinionated and assumes you are comfortable with Composer and WordPress development basics.

## What you’ll build

- A plugin entry file that extends `WPPF\v1_2_2\WordPress\Plugin`.
- A custom post type with supporting meta and admin screens.
- An admin module, meta box, and a few framework utilities.

## How to follow along

Run all CLI commands from your plugin root directory (the folder whose name matches your plugin slug). Most commands use the current working directory to decide file paths and class names.

## Sections

- **Build a plugin**: step-by-step scaffolding with the CLI.
- **Framework Tools**: guides for common functionality.

## Build a plugin map

| Step | Page |
| --- | --- |
| Getting started | [Getting started (installation)](build/getting-started.md) |
| Main plugin file | [Create a plugin file](build/create-plugin.md) |
| Post type | [Create a post type](build/create-post-type.md) |
| Post type meta | [Create a post type meta](build/create-post-type-meta.md) |
| Admin module | [Create a plugin admin module](build/create-plugin-admin.md) |
| Post screens | [Create a post screen](build/create-post-screen.md) |
| Meta boxes | [Create a meta box](build/create-meta-box.md) |

## Framework tools map

| Tool | Page |
| --- | --- |
| Action Scheduler | [Action Scheduler](tools/action-scheduler.md) |
| Admin notices | [Admin Notice](tools/admin-notice.md) |
| Templates | [Calling a template](tools/calling-templates.md) |
| Assets | [Enqueueing JS/CSS](tools/enqueueing-assets.md) |
| Staging | [Staging Detection](tools/staging-detection.md) |
| Upgrades | [Upgrade Schema](tools/upgrade-schema.md) |
| Utilities | [Utility class and debugging](tools/utility-debugging.md) |
