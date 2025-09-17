# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

SiteMgr is a comprehensive web hosting management tool for Alpine Linux that automates the creation, configuration, and management of PHP and Bun.js websites. The main script is `/usr/local/bin/sitemgr` (a Bash script) that orchestrates all site operations.

## Key Commands

### Service Management
```bash
# Reload/restart PHP-FPM for a specific version
rc-service php-fpm83 reload
rc-service php-fpm83 restart

# Reload SSH service after updating authorized_keys
rc-service sshd reload
```

### Site Management Commands
```bash
# Create a new site (various examples)
sitemgr --create example.com --https acme --php 83
sitemgr --create example.com --https none --php 84 --wordpress
sitemgr --create app.com --https self-signed --type bun

# Remove a site
sitemgr --remove example.com

# Change PHP version for a site
sitemgr --change-php example.com 84

# Change HTTPS mode
sitemgr --change-https example.com acme

# List all sites
sitemgr --list
```

### Site Permissions Management
```bash
# The site-permissions script (/usr/local/bin/site-permissions) handles permission changes
# Users run the 'site' command (alias to site-permissions) from within their site directory
site --readonly   # Make site read-only (secure)
site --writable  # Make site writable (for updates)
site --status    # Check current permission status
```

## Architecture & File Structure

### Core Components
- **Main Script**: `/usr/local/bin/sitemgr` - Main management script
- **Permissions Script**: `/usr/local/bin/site-permissions` - Handles site permission changes
- **Configuration Directory**: `/etc/sitemgr/` - Contains templates and defaults
- **Sites Root**: `/sites/` - All sites are created here
- **Port Allocations**: `/var/sitemgr/port_allocations` - Tracks Bun app ports

### Site Directory Structure
Each site at `/sites/DOMAIN/` contains:
- `htdocs/` - Web root for PHP sites
- `app/` - Application directory for Bun sites
- `config/` - Site configuration files (nginx.conf, .env, DKIM keys, readwritedirectories.txt)
- `logs/` - Site logs (nginx/, php/)
- `tmp/` - Temporary files
- `.ssh/` - SSH keys

### Template System
Templates are located in `/etc/sitemgr/templates/`:
- `nginx-php.conf` - HTTPS PHP site
- `nginx-php-http.conf` - HTTP-only PHP site
- `nginx-wordpress.conf` - HTTPS WordPress site
- `nginx-wordpress-http.conf` - HTTP WordPress site
- `nginx-bun.conf` / `nginx-bun-http.conf` / `nginx-bun-https.conf` - Bun proxy templates
- `php-fpm-pool.conf` - PHP-FPM pool configuration
- `bun-service` - OpenRC service template for Bun apps
- `pm2-ecosystem.config.js` - PM2 configuration for Bun apps

Templates use placeholders: `{{DOMAIN}}`, `{{USERNAME}}`, `{{PHP_VERSION}}`, `{{PORT}}`

## Key Implementation Details

### User Management
- Username is derived from domain (dots removed, max 32 chars)
- User home directory is `/sites/DOMAIN/`
- Users are added to nginx group for file permissions
- SSH access is key-based only (no passwords)

### PHP Configuration
- Supports PHP 82, 83, 84 (automatically detected)
- Each site gets its own PHP-FPM pool
- Configuration uses open_basedir for security

### HTTPS Modes
- `none` - HTTP-only configuration
- `self-signed` - Self-signed SSL certificate
- `acme` - Production Let's Encrypt certificate
- `acme-test` - Staging Let's Encrypt certificate

### Database Management
- Each site gets a MariaDB database named after the username
- Credentials stored in `/sites/DOMAIN/config/.env`
- Root password required in `/root/.mysql_root`

### Permission System
- Read-only mode: dirs 2550, files 440
- Writable mode: dirs 2750, files 640
- Exceptions listed in `config/readwritedirectories.txt`
- Integrated with doas for privilege escalation

## Important Notes

- Always use absolute paths in the scripts
- PHP version changes require PHP-FPM service reload
- ACME certificates start with HTTP template for validation
- Bun sites get sequential ports starting from 3001
- WordPress sites use specialized nginx templates with optimizations
- The `site` command (site-permissions) is available to all site users for permission management

## Working with This Repository

When starting work on this project, check for discrepancies between the repository files in `/root/sitemgr` and their production locations. The production files are the authoritative source of truth:

- `/usr/local/bin/sitemgr` - Main script (production)
- `/usr/local/bin/site-permissions` - Permissions management script (production)
- `/etc/sitemgr/templates/` - Template files (production)
- `/etc/sitemgr/defaults.conf` - Default configuration (production)

Any changes found in production locations should be synced back to the `/root/sitemgr` repository directory to ensure you're working with the latest versions. This server's production files are authoritative.
- When starting work with this project, ask user if they want to have /usr/local/bin/sitemgr and all other components to be compared to the files in this directory (md5) to detect any unsynced changes.
- Every time some change is done to /usr/local/bin/sitemgr or any of its related files and the changes are final, copy the changes to this repo and commit the changes and push.