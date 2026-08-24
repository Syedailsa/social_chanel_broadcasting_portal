# Technology News & Startup Media Portal

WordPress-based technology news and startup media portal for Pakistan.

## Tech Stack
- **CMS:** WordPress 6.x
- **Database:** MariaDB 10.11
- **Web Server:** Caddy (auto-SSL)
- **PHP:** 8.2 FPM
- **Theme:** ColorMag (free)
- **VPS:** Ubuntu 22.04, 5.8GB RAM

## Domains
- Production: `https://techportal.27.jugaar.ai`
- Staging: `https://staging.27.jugaar.ai`

## Development

### Phase 1: Server & Infrastructure ✅
- MariaDB installed and configured
- PHP 8.2 FPM with all extensions
- WordPress downloaded to `/var/www/techportal/` and `/var/www/techportal-staging/`
- Caddy configured with auto-SSL
- MySQL databases: `techportal_db`, `techportal_staging`

### Phase 2: WordPress Configuration
- Run WordPress installer via browser
- Install essential plugins
- Configure settings

### Phase 3-10: See implementation plan

## Repository Structure
```
/
├── docs/
│   └── implementation-plan.md    # Full 10-phase plan
├── config/
│   └── Caddyfile                 # Production Caddy config
├── .gitignore
└── README.md
```
