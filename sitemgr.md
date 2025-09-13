# SiteMgr - Alpine Linux Site Management System

## Overview

SiteMgr is a comprehensive web hosting management tool for Alpine Linux that automates the creation, configuration, and management of PHP and Bun.js websites. It provides isolated, secure environments for each site using standard Linux users and permissions (without chroot jails) with automatic SSL certificate management, database provisioning, and email configuration.

## Architecture

### Core Components

1. **Main Script**: `/usr/local/bin/sitemgr` - Bash script that orchestrates all operations
2. **Configuration**: `/etc/sitemgr/` - Templates and default settings
3. **Sites Directory**: `/sites/` - Root directory for all hosted sites
4. **Port Allocations**: `/var/sitemgr/port_allocations` - Tracks ports for Bun applications

### Site Structure

Each site is created under `/sites/DOMAIN/` with the following structure:

```
/sites/domain.com/        # User's home directory
├── htdocs/              # Web root for PHP sites
├── app/                 # Application directory for Bun sites
├── config/              # Site configuration files
│   ├── nginx.conf       # Nginx configuration
│   ├── .env             # Environment variables (DB credentials)
│   ├── msmtprc          # Email configuration
│   ├── dkim.conf        # DKIM configuration
│   ├── dkim.key         # DKIM private key
│   ├── dkim.pub         # DKIM public key
│   └── readwritedirectories.txt  # Directories that remain writable
├── logs/                # Access and error logs
├── tmp/                 # Temporary files
├── .ssh/                # SSH keys
├── .npmrc               # NPM configuration
├── .config/             # User configurations (Fish shell, etc.)
├── .npm/                # NPM cache
├── .npm-global/         # Global NPM packages
├── site-readonly        # Script to make site read-only
└── site-writable        # Script to make site writable
```

## Key Features

### 1. Site Types

#### PHP Sites
- Supports PHP versions 81, 82, 83 (dynamically detected)
- PHP-FPM pool per site with isolated configuration
- WordPress optimization available
- Automatic PHP extension installation

#### Bun Sites
- Node.js/Bun.js application hosting
- Automatic port allocation (starting from 3001)
- OpenRC service management
- Proxy configuration through Nginx

### 2. Security Features

#### Isolation
- **Separate System User**: Each site gets a unique system user (domain without dots, max 32 chars)
- **Home Directory**: User home is set to `/sites/DOMAIN/` for isolation
- **Permission Separation**: Web files owned by user:nginx group
- **PHP open_basedir**: Restricts PHP access to site directories only

#### Access Control
- **SSH Access**: Key-based authentication only (no passwords)
- **Fish Shell**: Default shell for site users
- **SSH Restrictions**: No TCP/Agent forwarding, no X11, no tunnels
- **Read-only Mode**: Sites can be toggled between read-only and writable states
- **Directory Protection**: Sensitive directories blocked in Nginx

### 3. HTTPS Management

#### Modes
- **none**: HTTP-only configuration
- **self-signed**: Generates self-signed certificate
- **acme**: Production Let's Encrypt certificates
- **acme-test**: Staging Let's Encrypt certificates

#### Certificate Handling
- Automatic certificate generation and renewal
- Nginx configuration updates based on HTTPS mode
- ACME challenges via webroot method

### 4. Database Management

Each site automatically gets:
- Dedicated MariaDB database (named after username)
- Database user with full privileges on their database
- Credentials stored in `/sites/DOMAIN/config/.env`
- Password is base64 encoded in the .env file

### 5. Email Configuration

- MSMTP configuration for outbound email
- DKIM key generation for email authentication
- Demo page includes email testing functionality
- DNS record guidance for SPF, DKIM, and DMARC

## Template System

### Nginx Templates

Located in `/etc/sitemgr/templates/`:

#### PHP Templates
- `nginx-php.conf` - HTTPS-enabled PHP site
- `nginx-php-http.conf` - HTTP-only PHP site
- `nginx-wordpress.conf` - HTTPS WordPress optimized
- `nginx-wordpress-http.conf` - HTTP WordPress

#### Bun Templates
- `nginx-bun.conf` - HTTPS-enabled Bun proxy
- `nginx-bun-http.conf` - HTTP-only Bun proxy
- `nginx-bun-https.conf` - HTTPS Bun proxy with SSL

#### Template Variables
- `{{DOMAIN}}` - Site domain name
- `{{PHP_VERSION}}` - PHP version number (81, 82, 83)
- `{{PORT}}` - Bun application port
- `{{USERNAME}}` - System username

### PHP-FPM Template

`php-fpm-pool.conf` configures:
- Process manager settings
- Memory limits
- Execution timeouts
- Socket permissions
- Environment variables

## Configuration

### Default Settings (`/etc/sitemgr/defaults.conf`)

```bash
DEFAULT_PHP_VERSION=83
PHP_FPM_MAX_CHILDREN=5
PHP_FPM_START_SERVERS=2
PHP_FPM_MIN_SPARE=1
PHP_FPM_MAX_SPARE=3
PHP_FPM_MAX_REQUESTS=500
PHP_MEMORY_LIMIT=256M
PHP_MAX_EXECUTION_TIME=300
PHP_POST_MAX_SIZE=64M
PHP_UPLOAD_MAX_FILESIZE=64M
```

## Command Reference

### Site Creation

```bash
# PHP site with HTTPS
sitemgr --add --https=acme --php=82 example.com

# WordPress site
sitemgr --add --https=acme --php=82 --wordpress blog.com

# Bun.js site
sitemgr --add --https=self-signed --bun app.com

# HTTP-only site
sitemgr --add --https=none --php=83 test.local
```

### Site Management

```bash
# List all sites
sitemgr --list

# Remove a site (with confirmation)
sitemgr --remove example.com

# Change PHP version
sitemgr --change-php --php=83 example.com

# Change HTTPS mode
sitemgr --change-https --https=acme example.com

# Generate DKIM keys
sitemgr --dkim-generate example.com
```

## Template Selection Logic

### HTTPS Mode Template Selection

The script correctly selects Nginx templates based on the HTTPS mode:

```bash
if [ "$https_mode" = "none" ]; then
    # Use HTTP-only template for no HTTPS
    template="/etc/sitemgr/templates/nginx-php-http.conf"
elif [ "$https_mode" = "acme" ] || [ "$https_mode" = "acme-test" ]; then
    # Start with HTTP for ACME validation
    template="/etc/sitemgr/templates/nginx-php-http.conf"
else
    # Use HTTPS template for self-signed
    template="/etc/sitemgr/templates/nginx-php.conf"
fi
```

This ensures:
- `--https=none` uses HTTP-only templates
- `--https=acme` and `--https=acme-test` start with HTTP for certificate validation
- `--https=self-signed` uses HTTPS templates with the self-signed certificate

### Port Management

- Bun applications get sequential ports starting from 3001
- Port allocations stored in `/var/sitemgr/port_allocations`
- Format: `domain port`

### User Management

- Username derived from domain (dots removed, max 32 chars)
  - Example: `example.com` becomes username `examplecom`
  - Home directory: `/sites/example.com/` (note: directory uses full domain, not username)
- User added to nginx group for file permissions
- Password authentication disabled (key-only)
- Automatic SSH key deployment from root's key (~/.ssh/id_ed25519.pub)

### Site Environment Setup

The script creates an isolated environment by:
1. Creating a dedicated system user for each site
2. Setting user home directory to `/sites/DOMAIN/`
3. Configuring PHP-FPM pools with open_basedir restrictions
4. Setting proper file permissions and ownership
5. Configuring SSH access with security restrictions

### Service Management

#### PHP Sites
- PHP-FPM pool created per site
- Pool configuration based on defaults
- Automatic PHP-FPM reload on changes

#### Bun Sites
- Each user gets their own Bun installation at `/sites/DOMAIN/.bun/`
- PM2 process manager for user-controlled app management
- PM2 ecosystem config at `/sites/DOMAIN/ecosystem.config.js`
- Control script at `/sites/DOMAIN/control.sh` for easy management
- Users can manage their apps without root privileges:
  - `./control.sh start` - Start the application
  - `./control.sh stop` - Stop the application
  - `./control.sh restart` - Restart the application
  - `./control.sh status` - View application status
  - `./control.sh logs` - View application logs

## Permissions Model

### Directory Ownership
- `/sites/DOMAIN/` - username:nginx (user's home directory, mode 750)
- `/sites/DOMAIN/htdocs/` - username:nginx (mode 750)
- `/sites/DOMAIN/app/` - username:nginx (mode 750)
- `/sites/DOMAIN/config/` - username:nginx (mode 750)
- `/sites/DOMAIN/logs/` - username:nginx (mode 750)
- `/sites/DOMAIN/logs/nginx/` - nginx:username (mode 750) - tamper-proof nginx logs
- `/sites/DOMAIN/logs/php/` - username:username (mode 750)
- `/sites/DOMAIN/.ssh/` - username:username (mode 700)
- `/sites/DOMAIN/tmp/` - username:username (mode 700)

### File Permissions
- Configuration files: 600 (sensitive) or 640/644 (nginx needs access)
- Web files: 644 (or 444 in read-only mode)
- SSH keys: 600
- Scripts (site-readonly/writable): 755

## Helper Commands

Sites include helper scripts in the user's home directory:

### site-readonly
Located at `/sites/DOMAIN/site-readonly`
Makes web root read-only except directories listed in `readwritedirectories.txt`

The `readwritedirectories.txt` file contains full paths, for example:
```
/sites/domain.com/tmp
/sites/domain.com/logs
/sites/domain.com/htdocs/wp-content/uploads
```

### site-writable
Located at `/sites/DOMAIN/site-writable`
Restores write permissions to web root

## Database Password Handling

- Generated using `openssl rand -base64 16`
- Stored base64-encoded in `.env` file
- Decoded when used in demo page: `base64_decode($_ENV['DB_PASSWORD'])`

## WordPress Optimizations

When `--wordpress` flag is used:
- Specific Nginx rules for WordPress
- Additional writable directories configured
- Performance optimizations for WordPress assets

## Not Yet Implemented

The following commands are defined but not functional:
- `--info` - Show detailed site information
- `--backup` - Create site backup
- `--start` - Start site services
- `--stop` - Stop site services
- `--restart` - Restart site services
- `--status` - Show service status

## Security Considerations

1. **Root Password**: MariaDB root password must be in `/root/.mysql_root`
2. **User Isolation**: Users are isolated through Unix permissions and PHP restrictions
3. **SSL Certificates**: Stored in `/etc/nginx/ssl/` with appropriate permissions
4. **Database Passwords**: Base64 encoding is not encryption - use proper secrets management in production
5. **PHP Security**: open_basedir restricts PHP scripts to site directories only

## Troubleshooting

### Common Issues

1. **nginx: cannot load certificate**: Check if HTTPS mode matches certificate availability
2. **PHP-FPM socket not found**: Ensure PHP-FPM service is running
3. **Permission denied**: Check file ownership and group membership
4. **SSH access fails**: Verify SSH key is in `/sites/DOMAIN/.ssh/authorized_keys`

### Log Locations

- Nginx access/error: `/sites/DOMAIN/logs/`
- PHP-FPM: `/var/log/php*.log`
- MSMTP: `/sites/DOMAIN/logs/msmtp.log`
- System: `/var/log/messages`

## Extension Points

The script is designed to be extended with:
- Additional PHP versions (modify `get_available_php_versions()`)
- New site types (add to site_type logic)
- Custom templates (add to `/etc/sitemgr/templates/`)
- Additional HTTPS providers (extend HTTPS mode handling)