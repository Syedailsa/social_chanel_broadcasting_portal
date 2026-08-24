# Technology News & Startup Media Portal
## Implementation Plan - MVP (Basic Package) — REVISED v2.0

**Target Domain:** `portal.27.jugaar.ai` (custom VPS)
**Staging:** `staging.portal.27.jugaar.ai`
**Timeline:** 4-6 weeks (aggressive)
**Stack:** WordPress + MySQL + Nginx on custom VPS
**Package:** Basic (MVP)
**YouTube:** Existing channel with content ready
**VPS:** 5.8GB RAM, 2+ vCPU, NVMe (confirmed sufficient)

---

## Executive Summary

This document provides a detailed, week-by-week implementation plan for launching a Pakistan-focused technology news and startup media portal. The Basic package focuses on core publishing, YouTube integration (with API for live detection), SEO foundation, Nginx-level caching, security hardening, and PECA 2025 compliance.

**Key design decisions (validated):**
- **Caching:** Nginx FastCGI Cache only (server-level). No WP Super Cache — they conflict.
- **Theme:** ColorMag (40K+ active installs, most battle-tested free magazine theme).
- **YouTube API:** Core scope, not optional. liveBroadcasts.list for live detection.
- **Comments:** Enabled on interviews/live shows only. Akismet for spam.
- **Git:** Scoped to `wp-content/themes/[your-theme]/` only.
- **Staging:** Required before every risky change (SSL, hardening, plugin updates).

---

## Phase 1: Server & Infrastructure Setup (Week 1)

### 1.1 VPS Configuration
- [ ] Access VPS at `27.jugaar.ai`
- [ ] Verify Ubuntu 22.04 LTS, confirm 5.8GB RAM available
- [ ] Configure UFW firewall (open ports 80, 443, 22)
- [ ] Set up SSH key authentication
- [ ] Disable root login, create admin user
- [ ] Configure timezone (Asia/Karachi)
- [ ] Install fail2ban for brute-force protection

### 1.2 Web Server Stack (LEMP)
- [ ] Install Nginx
- [ ] Install PHP 8.2+ with required extensions:
  - php-fpm, php-mysql, php-curl, php-gd, php-mbstring
  - php-xml, php-zip, php-intl, php-soap, php-bcmath
- [ ] Install MariaDB/MySQL 10.x
- [ ] Install Certbot for Let's Encrypt SSL
- [ ] Install Redis (for object caching, optional later)

### 1.3 Domain & DNS
- [ ] Configure DNS A record: `portal.27.jugaar.ai` -> VPS IP
- [ ] Configure DNS A record: `staging.portal.27.jugaar.ai` -> VPS IP
- [ ] Verify DNS propagation

### 1.4 SSL Certificate
- [ ] Run Certbot to obtain Let's Encrypt SSL for both domains
- [ ] Configure auto-renewal cron job
- [ ] Force HTTPS redirect

### 1.5 Database Setup
- [ ] Create MySQL database: `techportal_db`
- [ ] Create MySQL database: `techportal_staging` (for staging)
- [ ] Create database users with strong passwords
- [ ] Grant privileges and secure access

### 1.6 Staging Environment
- [ ] Create `/var/www/techportal-staging/` webroot
- [ ] Configure Nginx virtual host for `staging.portal.27.jugaar.ai`
- [ ] Install separate WordPress instance on staging
- [ ] Test staging site loads correctly

**Deliverables:** VPS with LEMP stack, SSL on both domains, staging environment ready, secured databases

---

## Phase 2: WordPress Installation & Configuration (Week 1-2)

### 2.1 WordPress Installation (Production)
- [ ] Download latest WordPress to `/var/www/techportal/`
- [ ] Configure `wp-config.php`:
  - Database credentials (production)
  - Authentication keys/salts (from api.wordpress.org)
  - Debug mode: false in production
  - Memory limit: 256M
  - Upload limit: 64M
  - Nginx Helper cache path: `define('RT_WP_NGINX_HELPER_CACHE_PATH', '/var/cache/nginx/fastcgi');`
- [ ] Run WordPress installer via browser
- [ ] Set strong admin credentials

### 2.2 WordPress Settings
- [ ] Settings > General: Site title, timezone (Asia/Karachi), language
- [ ] Settings > Reading: Front page displays latest posts, 12 per page
- [ ] Settings > Permalinks: Custom structure `/%category%/%postname%/`
- [ ] Settings > Discussion: Disable comments by default
- [ ] Settings > Media: Large 1024x1024, Medium 590x590

### 2.3 Essential Plugins
| Plugin | Purpose | Priority |
|--------|---------|----------|
| Yoast SEO | SEO optimization | HIGH |
| Nginx Helper (rtCamp) | Auto-purge FastCGI cache on publish | HIGH |
| Wordfence Security | WAF & security | HIGH |
| UpdraftPlus | Automated backups | HIGH |
| Akismet | Spam protection (for enabled comments) | HIGH |
| Smush | Image compression | MEDIUM |
| MonsterInsights | Google Analytics | MEDIUM |
| Redirection | URL redirects | MEDIUM |

**Note:** NO WP Super Cache or W3 Total Cache — Nginx FastCGI Cache handles page caching at server level.

### 2.4 User Roles
- [ ] Admin (full access)
- [ ] Editor (publish, edit all content)
- [ ] Author (publish own content)
- [ ] Contributor (write, submit for review)
- [ ] Subscriber (read only)

---

## Phase 3: Theme & Design (Week 2-3)

### 3.1 Theme Selection

**Primary choice: ColorMag (Free)**
- 40,000+ active installs, 4.7/5 rating
- Most battle-tested free magazine theme on WordPress.org
- 30+ one-click starter demos (news, health, sports, entertainment)
- Magazine Blocks plugin (21+ custom Gutenberg blocks)
- Built-in ad widget areas for monetization
- Core Web Vitals optimized, fast load times
- WooCommerce compatible, RTL ready, 35+ languages
- Pro upgrade: $55/yr (not needed for MVP)

**Trade-off vs MagazineNP:**
- ColorMag: 40K+ installs, larger community, more battle-tested, $55/yr Pro
- MagazineNP: ~50 installs, newer, $49/yr or $99 lifetime Pro
- **Decision: ColorMag** — stability and community support outweigh faster setup for a 6-week build

### 3.2 Homepage Layout
```
+----------------------------------------------------------+
|  HEADER: Logo | Nav (IT News|Startups|Cyber|AI|Reviews|  |
|            Live Shows) | Search | Sign In                 |
+----------------------------------------------------------+
|  BREAKING NEWS TICKER (scrolling headlines)               |
+----------------------------------------------------------+
|  HERO SECTION: Featured Story / Live Show                 |
|  [Featured Image] | Headline + Excerpt + Category + Date  |
+----------------------------------------------------------+
|  LIVE STREAM: YouTube Embed | Upcoming Shows List         |
+----------------------------------------------------------+
|  LATEST NEWS GRID (3-4 columns, 8 cards)                  |
|  [Card] [Card] [Card] [Card]                              |
|  [Card] [Card] [Card] [Card]                              |
+----------------------------------------------------------+
|  SIDEBAR: Trending | Newsletter Signup | Social | Ads     |
+----------------------------------------------------------+
|  FOOTER: Nav | Social | Policies | Contact | Copyright    |
+----------------------------------------------------------+
```

### 3.3 Brand Implementation
- Primary color: Purple `#37215F`
- Secondary color: Blue `#0881BE`
- Typography: Modern readable fonts (Inter, Roboto)
- Mobile-first responsive design

### 3.4 Template Files
- [ ] `front-page.php` - Custom homepage
- [ ] `header.php` - Navigation & ticker
- [ ] `footer.php` - Footer layout
- [ ] `single.php` - Article page
- [ ] `archive.php` - Category/tag archives
- [ ] `page-templates/template-liveshows.php`

---

## Phase 4: Content Architecture & Version Control (Week 2-3)

### 4.1 Categories
```
IT News (Latest News, Industry, Enterprise, Pakistan Tech)
Startups & Entrepreneurs (Stories, Interviews, Profiles)
Cybersecurity & Tech (Cybersecurity, Privacy, Threats)
AI & Cloud (AI, Cloud Computing, ML)
Reviews (Products, Software, Apps)
Web Channel / Live Shows (Live Now, Upcoming, Episodes, Archive)
```

### 4.2 Custom Post Types
- [ ] Videos - YouTube video embeds
- [ ] Shows - Live show episodes
- [ ] Startups - Company profiles

### 4.3 Custom Taxonomies
- [ ] Show Category (Tech Talk, Startup Interview, Product Launch)
- [ ] Guest Type (Founder, CEO, CTO, Investor)

### 4.4 Version Control (Git)
- [ ] Initialize git repo in `wp-content/themes/[your-theme]/` (NOT full webroot)
- [ ] Create `.gitignore`:
  ```
  wp-content/uploads/
  wp-content/cache/
  wp-config.php
  *.log
  .env
  ```
- [ ] Create remote repo (GitHub private)
- [ ] Commit custom theme files after each working session
- [ ] Test on staging before pushing to production

**Git scope:** Only `wp-content/themes/[your-theme]/` — WordPress core stays out of the repo.

---

## Phase 5: Core Features (Week 3-4)

### 5.1 Breaking News Ticker
- [ ] Create/customize ticker widget (ColorMag supports custom widgets)
- [ ] Configure "Breaking News" category
- [ ] Test scrolling on homepage

### 5.2 Article Templates
- [ ] Single post: Featured image, title, category badge, author, date
- [ ] Social sharing buttons
- [ ] Related articles section
- [ ] Author bio box
- [ ] Comment section (ONLY on "Startups & Entrepreneurs" and "Live Shows" categories)
  - All other categories: comments disabled
  - Akismet enabled for spam filtering
  - Moderation queue for new commenters

### 5.3 Search
- [ ] Improved WordPress search
- [ ] Search results page template

### 5.4 Newsletter
- [ ] Mailchimp/ConvertKit signup form
- [ ] Sidebar widget + footer form
- [ ] Welcome email automation

### 5.5 Social Sharing
- [ ] Share buttons on articles
- [ ] Open Graph meta tags (Yoast)
- [ ] Twitter Card support

---

## Phase 6: YouTube Integration (Week 3-4) — CORE SCOPE

### 6.1 Channel Integration
- [ ] YouTube channel link in header
- [ ] Subscriber count display (optional)

### 6.2 YouTube Data API v3 Setup (REQUIRED)
- [ ] Create Google Cloud project
- [ ] Enable YouTube Data API v3
- [ ] Create OAuth 2.0 credentials (for liveBroadcasts.list)
- [ ] Configure WordPress to store OAuth tokens
- [ ] Set up API key for non-authenticated requests (search, videos)

**Live detection strategy:**
| Endpoint | Purpose | Quota Cost | Auth Required |
|----------|---------|------------|---------------|
| `liveBroadcasts.list(broadcastStatus=active, mine=true)` | Primary live check | 1 unit | OAuth (channel owner) |
| `videos.list(part=snippet, id=VIDEO_ID)` | Video metadata | 1 unit | API key |
| `search.list(channelId, eventType=live)` | Fallback (expensive) | 100 units | API key |

**Quota management:**
- Default: 10,000 units/day
- liveBroadcasts.list at 1 unit = can poll every 60 seconds safely (1,440 units/day)
- Cache API results in WordPress transients for 5 minutes (reduces calls)
- Set up quota alerts in Google Cloud Console

### 6.3 Video Embed System
- [ ] Homepage: Live stream section with YouTube embed
- [ ] Fallback to latest video when offline (using liveBroadcasts.list)
- [ ] Individual video page template

### 6.4 Video Archive
- [ ] Page template: `/live-shows/`
- [ ] Grid layout of video thumbnails
- [ ] Filter by category/guest/date
- [ ] Pagination

### 6.5 Episode Pages
- [ ] Template: Video embed, show notes, guest bio
- [ ] Related episodes section
- [ ] SEO metadata

---

## Phase 7: SEO & Performance (Week 4-5)

### 7.1 Yoast SEO
- [ ] Site title, tagline, social profiles
- [ ] XML sitemap enabled
- [ ] Schema markup (NewsArticle, Organization)
- [ ] Breadcrumb navigation

### 7.2 Technical SEO
- [ ] Clean URLs (permalinks set)
- [ ] XML sitemap to Google Search Console
- [ ] robots.txt configuration
- [ ] Canonical URLs
- [ ] 301 redirects

### 7.3 Nginx FastCGI Cache Configuration

**Cache setup (server-level, no plugin):**

```nginx
# In http block - /etc/nginx/conf.d/fastcgi-cache.conf
fastcgi_cache_path /var/cache/nginx/fastcgi levels=1:2 keys_zone=WORDPRESS:100m max_size=512m inactive=60m use_temp_path=off;
fastcgi_cache_key "$scheme$request_method$host$request_uri";
```

```nginx
# In server block - map block for cache bypass
map $http_cookie $skip_cache {
    default 0;
    ~*wordpress_logged_in 1;
    ~*comment_author 1;
    ~*wp-postpass 1;
}

map $request_method $skip_cache_method {
    default 0;
    POST 1;
}

map $query_string $skip_cache_query {
    default 0;
    ~.+ 1;
}
```

```nginx
# In server block - PHP location with cache
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;

    fastcgi_cache WORDPRESS;
    fastcgi_cache_valid 200 60m;
    fastcgi_cache_bypass $skip_cache $skip_cache_method $skip_cache_query;
    fastcgi_no_cache $skip_cache $skip_cache_method $skip_cache_query;
    fastcgi_cache_use_stale error timeout invalid_header updating http_500;
    fastcgi_cache_lock on;

    add_header X-FastCGI-Cache $upstream_cache_status;
}
```

**Nginx Helper plugin (rtCamp):**
- [ ] Install Nginx Helper plugin
- [ ] Configure cache path: `/var/cache/nginx/fastcgi`
- [ ] Enable auto-purge on post publish/update
- [ ] Enable auto-purge on any WordPress update (add filter):
  ```php
  add_filter('rt_wp_nginx_helper_enable_auto_purge_on_any_update', '__return_true');
  ```

### 7.4 Performance
- [ ] GZIP compression enabled
- [ ] Image optimization + lazy loading
- [ ] CSS/JS minification
- [ ] CDN setup (Cloudflare free tier)

### 7.5 Analytics
- [ ] Google Analytics 4 property
- [ ] MonsterInsights installed
- [ ] Goals: newsletter signup, video views
- [ ] Google Search Console verified

---

## Phase 8: Security & PECA Compliance (Week 5)

### 8.1 Wordfence
- [ ] Install and activate
- [ ] Security scan
- [ ] Firewall rules configured
- [ ] Brute force protection enabled
- [ ] Schedule scans during off-peak hours (reduces resource impact)

### 8.2 WordPress Hardening
- [ ] Change database prefix
- [ ] Disable file editing
- [ ] Protect wp-config.php
- [ ] Disable XML-RPC
- [ ] Limit login attempts

### 8.3 Server Security
- [ ] UFW firewall rules
- [ ] fail2ban installed
- [ ] SSH hardened (key-only)
- [ ] File permissions: 644 files, 755 dirs

### 8.4 Backups
- [ ] UpdraftPlus: Weekly full, daily database
- [ ] Store to Google Drive/Dropbox
- [ ] Test restoration procedure

### 8.5 SSL
- [ ] Force HTTPS
- [ ] Fix mixed content
- [ ] Test all pages

### 8.6 Pakistan PECA 2025 Compliance

**Key requirements under Prevention of Electronic Crimes (Amendment) Act, 2025:**

| Requirement | Action |
|-------------|--------|
| **Section 26A — False information** | Criminal offense to intentionally disseminate false/fake information likely to cause fear, panic, or unrest. Penalty: up to 3 years imprisonment + Rs 2M fine. |
| **SMPRA registration** | Social Media Protection and Regulatory Authority (SMPRA) may require platform enlistment. Monitor SMPRA regulations for portal registration requirements. |
| **Content takedown** | Respond to PTA/SMPRA takedown orders within 24 hours (6 hours for emergencies). |
| **Content moderation** | Deploy mechanisms to prevent live streaming of terrorism, hate speech, extremism, incitement to violence. |
| **Complaint mechanism** | Maintain effective procedure for handling complaints about unlawful content. |
| **Data retention** | Retain traffic data as specified by PTA/SMPRA. |
| **Intermediary protection** | Section 38 PECA: No liability unless "specific actual knowledge and willful intent" proven. |

**MVP compliance actions:**
- [ ] Draft and publish editorial policy / content moderation guidelines
- [ ] Create community guidelines page
- [ ] Establish PTA takedown request handling procedure (24h response window)
- [ ] Add legal pages: Privacy Policy, Terms of Service, Content Policy
- [ ] Configure content review workflow for user submissions
- [ ] Monitor SMPRA enlistment requirements as regulations are issued

---

## Phase 9: Content & Launch Prep (Week 5-6)

### 9.1 Seed Content
- [ ] 10-15 articles (mix of categories)
- [ ] About Us, Contact Us, Privacy Policy, ToS, Content Policy pages
- [ ] 5-10 YouTube videos integrated with episode pages

### 9.2 Visual Assets
- [ ] Logo (purple #37215F + blue #0881BE)
- [ ] Homepage hero banner
- [ ] Social media profile images
- [ ] Default featured image template

### 9.3 Pre-Launch
- [ ] Clone production to staging for final testing
- [ ] Remove "Coming Soon" mode
- [ ] Enable search engine visibility
- [ ] Test all forms
- [ ] Verify all links
- [ ] Mobile responsiveness check
- [ ] Page speed test
- [ ] Verify Nginx cache hit rates (check X-FastCGI-Cache header)

---

## Phase 10: QA & Go-Live (Week 6)

### 10.1 Functional Testing
- [ ] Navigation, search, categories work
- [ ] Article pages display correctly
- [ ] Video embeds play (test YouTube API live detection)
- [ ] Newsletter signup works
- [ ] Contact form submits
- [ ] Comments work on interviews/live shows, disabled elsewhere
- [ ] Cache purges correctly on post publish (check Nginx Helper)

### 10.2 Cross-Browser
- [ ] Chrome, Firefox, Safari, Edge
- [ ] Mobile Chrome, Mobile Safari

### 10.3 Responsive
- [ ] Desktop: 1920px, 1440px, 1280px
- [ ] Tablet: 768px, 1024px
- [ ] Mobile: 375px, 414px

### 10.4 Performance
- [ ] GTmetrix: A or B
- [ ] PageSpeed: 80+ mobile, 90+ desktop
- [ ] Load time under 3 seconds
- [ ] Verify X-FastCGI-Cache: HIT on cached pages

### 10.5 Launch Day
- [ ] Final content review
- [ ] Enable search engine visibility
- [ ] Submit sitemap to Google
- [ ] Announce on social media + newsletter
- [ ] Monitor for errors, cache performance, API quota usage

---

## Post-Launch Roadmap

### Week 7-8: Monitor & Optimize
- Daily analytics review
- Bug fixes
- Regular content publishing
- Social media promotion
- Monitor YouTube API quota usage

### Phase 2 (Month 2-3): Enhanced Features
- User registration & membership system
- Press release submission workflow
- Sponsor/advertising modules
- Advanced video archive with filters

### Phase 3 (Month 4+): Scaling
- Mobile app (React Native/Flutter)
- Podcast distribution
- AI content tagging
- Advanced analytics dashboard
- Startup directory
- Job board

---

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| VPS downtime | Reliable provider, uptime monitoring |
| WordPress vulnerability | Keep updated, WAF, backups |
| Slow performance | Nginx FastCGI cache, CDN, image optimization |
| Security breach | Hardening, backups, monitoring |
| Launch delays | Phased MVP approach, staging environment |
| YouTube API quota exceeded | Cache results 5 min, use liveBroadcasts.list (1 unit), monitor quota |
| PECA takedown | 24h response procedure, editorial policy, content moderation |
| Wordfence resource usage | Schedule scans off-peak, monitor CPU/RAM |
| Cache serving stale content | Nginx Helper auto-purge on publish, 60m cache expiry |

---

## Server Configuration Reference

### Nginx Virtual Host (Complete)
```nginx
# /etc/nginx/conf.d/fastcgi-cache.conf (http block)
fastcgi_cache_path /var/cache/nginx/fastcgi levels=1:2 keys_zone=WORDPRESS:100m max_size=512m inactive=60m use_temp_path=off;
fastcgi_cache_key "$scheme$request_method$host$request_uri";

# /etc/nginx/sites-available/portal.27.jugaar.ai
server {
    listen 443 ssl http2;
    server_name portal.27.jugaar.ai;

    root /var/www/techportal;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/portal.27.jugaar.ai/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/portal.27.jugaar.ai/privkey.pem;

    # Cache bypass map blocks
    map $http_cookie $skip_cache {
        default 0;
        ~*wordpress_logged_in 1;
        ~*comment_author 1;
        ~*wp-postpass 1;
    }
    map $request_method $skip_cache_method {
        default 0;
        POST 1;
    }
    map $query_string $skip_cache_query {
        default 0;
        ~.+ 1;
    }

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        fastcgi_cache WORDPRESS;
        fastcgi_cache_valid 200 60m;
        fastcgi_cache_bypass $skip_cache $skip_cache_method $skip_cache_query;
        fastcgi_no_cache $skip_cache $skip_cache_method $skip_cache_query;
        fastcgi_cache_use_stale error timeout invalid_header updating http_500;
        fastcgi_cache_lock on;

        add_header X-FastCGI-Cache $upstream_cache_status;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```

### WP-Config Security
```php
define('DISALLOW_FILE_EDIT', true);
define('FORCE_SSL_ADMIN', true);
define('WP_AUTO_UPDATE_CORE', 'minor');
define('RT_WP_NGINX_HELPER_CACHE_PATH', '/var/cache/nginx/fastcgi');
```

---

**Document Version:** 2.0 (Revised)
**Created:** August 2026
**Last Revised:** August 2026
**Status:** Ready for Implementation
