<?php
/**
 * Plugin Name: Tech Portal News Aggregator
 * Description: Fetches real-time tech news from multiple REST APIs
 * Version: 2.0.0
 * Author: Tech Portal Team
 */

if (!defined('ABSPATH')) exit;

class TechPortal_News_Aggregator {
    
    private $cache_duration = 1800;
    private $exclude_regex;
    private $tech_regex;
    
    public function __construct() {
        $this->build_filter_regex();
        
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('techportal test-fetch', array($this, 'cli_test_fetch'));
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Shortcodes
        add_shortcode('tech_news', array($this, 'news_shortcode'));
        add_shortcode('tech_news_grid', array($this, 'news_grid_shortcode'));
        add_shortcode('startup_news', array($this, 'startup_news_shortcode'));
        add_shortcode('cyber_news', array($this, 'cyber_news_shortcode'));
        add_shortcode('ai_news', array($this, 'ai_news_shortcode'));
        add_shortcode('it_news', array($this, 'it_news_shortcode'));
        
        add_action('widgets_init', function() {
            register_widget('TechPortal_News_Widget');
        });
        
        add_action('wp_ajax_fetch_news', array($this, 'ajax_fetch_news'));
        add_action('wp_ajax_nopriv_fetch_news', array($this, 'ajax_fetch_news'));
        
        add_filter('cron_schedules', array($this, 'add_cron_interval'));
        add_action('techportal_fetch_news_cron', array($this, 'auto_fetch_news'));
        
        if (!wp_next_scheduled('techportal_fetch_news_cron')) {
            wp_schedule_event(time(), 'twice_daily', 'techportal_fetch_news_cron');
        }
    }
    
    public function add_cron_interval($schedules) {
        $schedules['twice_daily'] = array('interval' => 43200, 'display' => 'Twice Daily');
        return $schedules;
    }
    
    public function add_admin_menu() {
        add_menu_page('News Aggregator', 'News Aggregator', 'manage_options', 'techportal-news', array($this, 'admin_page'), 'dashicons-schedule', 30);
        add_submenu_page('techportal-news', 'API Settings', 'API Settings', 'manage_options', 'techportal-news-settings', array($this, 'settings_page'));
    }
    
    public function register_settings() {
        register_setting('techportal_news_settings', 'techportal_gnews_api_key');
        register_setting('techportal_news_settings', 'techportal_newsdata_api_key');
        register_setting('techportal_news_settings', 'techportal_currents_api_key');
        register_setting('techportal_news_settings', 'techportal_thenewsapi_key');
        register_setting('techportal_news_settings', 'techportal_mediastack_key');
        register_setting('techportal_news_settings', 'techportal_news_auto_fetch');
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>News Aggregator</h1>
            <div class="card" style="max-width:600px;padding:20px;">
                <h2>Status</h2>
                <p><strong>Last Fetch:</strong> <?php echo get_option('techportal_last_fetch', 'Never'); ?></p>
                <p><strong>Active APIs:</strong> <?php echo count($this->get_active_apis()); ?></p>
                <a href="<?php echo admin_url('admin.php?page=techportal-news-settings'); ?>" class="button">API Settings</a>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>API Settings</h1>
            <form method="post" action="options.php" style="max-width:700px;">
                <?php settings_fields('techportal_news_settings'); ?>
                <table class="form-table">
                    <tr><th>GNews API Key</th><td><input type="text" name="techportal_gnews_api_key" value="<?php echo esc_attr(get_option('techportal_gnews_api_key')); ?>" class="regular-text" /></td></tr>
                    <tr><th>NewsData.io Key</th><td><input type="text" name="techportal_newsdata_api_key" value="<?php echo esc_attr(get_option('techportal_newsdata_api_key')); ?>" class="regular-text" /></td></tr>
                    <tr><th>Currents API Key</th><td><input type="text" name="techportal_currents_api_key" value="<?php echo esc_attr(get_option('techportal_currents_api_key')); ?>" class="regular-text" /></td></tr>
                    <tr><th>TheNewsAPI Key</th><td><input type="text" name="techportal_thenewsapi_key" value="<?php echo esc_attr(get_option('techportal_thenewsapi_key')); ?>" class="regular-text" /></td></tr>
                    <tr><th>Mediastack Key</th><td><input type="text" name="techportal_mediastack_key" value="<?php echo esc_attr(get_option('techportal_mediastack_key')); ?>" class="regular-text" /></td></tr>
                </table>
                <?php submit_button('Save'); ?>
            </form>
        </div>
        <?php
    }
    
    public function get_active_apis() {
        $apis = array(
            'gnews' => array('key' => 'techportal_gnews_api_key', 'name' => 'GNews'),
            'newsdata' => array('key' => 'techportal_newsdata_api_key', 'name' => 'NewsData'),
            'currents' => array('key' => 'techportal_currents_api_key', 'name' => 'Currents'),
            'thenewsapi' => array('key' => 'techportal_thenewsapi_key', 'name' => 'TheNewsAPI'),
            'mediastack' => array('key' => 'techportal_mediastack_key', 'name' => 'Mediastack'),
        );
        $active = array();
        foreach ($apis as $k => $v) {
            if (!empty(get_option($v['key']))) $active[$k] = $v;
        }
        return $active;
    }
    
    public function auto_fetch_news() {
        $this->fetch_all_news('technology', 'publishedAt', 50);
    }
    
    public function fetch_all_news($category = 'technology', $sort = 'publishedAt', $count = 20) {
        $active = $this->get_active_apis();
        if (empty($active)) return array();
        
        $articles = array();
        foreach ($active as $key => $api) {
            $fetched = $this->fetch_from_provider($key, $api['key'], $category, $count);
            $articles = array_merge($articles, $fetched);
        }
        
        $articles = $this->deduplicate($articles);
        
        $stats = array('fetched' => count($articles), 'accepted' => 0, 'rejected' => 0, 'reasons' => array());
        $filtered = array();
        foreach ($articles as $a) {
            if (!$this->validate_article($a)) {
                $stats['rejected']++;
                $stats['reasons']['invalid_article'] = ($stats['reasons']['invalid_article'] ?? 0) + 1;
                continue;
            }
            if (!$this->is_tech_article($a)) {
                $stats['rejected']++;
                $stats['reasons']['non_tech'] = ($stats['reasons']['non_tech'] ?? 0) + 1;
                continue;
            }
            $stats['accepted']++;
            $filtered[] = $a;
        }
        $articles = $filtered;
        
        usort($articles, function($a, $b) {
            return strtotime($b['published'] ?? '') - strtotime($a['published'] ?? '');
        });
        
        set_transient('techportal_news_cache_' . $category, array_slice($articles, 0, $count), $this->cache_duration);
        update_option('techportal_last_fetch', current_time('mysql'));
        update_option('techportal_fetch_log', array(
            'last_run' => current_time('mysql'),
            'category' => $category,
            'stats' => $stats,
        ));
        
        return array_slice($articles, 0, $count);
    }
    
    private function fetch_from_provider($provider, $key_option, $category, $count) {
        $key = get_option($key_option, '');
        if (empty($key)) return array();
        
        $query = $this->get_category_query($category);
        
        switch ($provider) {
            case 'gnews':
                $url = "https://gnews.io/api/v4/search?q={$query}&topic=technology&lang=en&max={$count}&apikey={$key}";
                $response = wp_remote_get($url, array('timeout' => 15));
                if (is_wp_error($response)) return array();
                $body = json_decode(wp_remote_retrieve_body($response), true);
                return $this->normalize_gnews($body['articles'] ?? []);
                
            case 'newsdata':
                $url = "https://newsdata.io/api/1/latest?apikey={$key}&q={$query}&category=technology&language=en&size={$count}";
                $response = wp_remote_get($url, array('timeout' => 15));
                if (is_wp_error($response)) return array();
                $body = json_decode(wp_remote_retrieve_body($response), true);
                return $this->normalize_newsdata($body['results'] ?? []);
                
            case 'currents':
                $url = "https://api.currentsapi.services/v1/search?keywords={$query}&category=Technology&apiKey={$key}&pageSize={$count}";
                $response = wp_remote_get($url, array('timeout' => 15));
                if (is_wp_error($response)) return array();
                $body = json_decode(wp_remote_retrieve_body($response), true);
                return $this->normalize_currents($body['news'] ?? []);
                
            case 'thenewsapi':
                $url = "https://api.thenewsapi.com/v1/news/top?api_token={$key}&search={$query}&categories=tech&locale=us&limit={$count}";
                $response = wp_remote_get($url, array('timeout' => 15));
                if (is_wp_error($response)) return array();
                $body = json_decode(wp_remote_retrieve_body($response), true);
                return $this->normalize_thenewsapi($body['data'] ?? []);
                
            case 'mediastack':
                $url = "http://api.mediastack.com/v1/news?access_key={$key}&keywords={$query}&categories=technology&languages=en&limit={$count}";
                $response = wp_remote_get($url, array('timeout' => 15));
                if (is_wp_error($response)) return array();
                $body = json_decode(wp_remote_retrieve_body($response), true);
                return $this->normalize_mediastack($body['data'] ?? []);
        }
        
        return array();
    }
    
    private function normalize_gnews($articles) {
        $result = array();
        foreach ($articles as $a) {
            $result[] = array(
                'title' => $a['title'] ?? '',
                'description' => $a['description'] ?? '',
                'url' => $a['url'] ?? '',
                'image' => $this->validate_image_url($a['image'] ?? ''),
                'source' => $a['source']['name'] ?? 'GNews',
                'published' => $a['publishedAt'] ?? '',
                'provider' => 'GNews',
            );
        }
        return $result;
    }
    
    private function normalize_newsdata($articles) {
        $result = array();
        foreach ($articles as $a) {
            $result[] = array(
                'title' => $a['title'] ?? '',
                'description' => $a['description'] ?? '',
                'url' => $a['link'] ?? '',
                'image' => $this->validate_image_url($a['image_url'] ?? ''),
                'source' => $a['source_name'] ?? 'NewsData',
                'published' => $a['pubDate'] ?? '',
                'provider' => 'NewsData.io',
            );
        }
        return $result;
    }
    
    private function normalize_currents($articles) {
        $result = array();
        foreach ($articles as $a) {
            $result[] = array(
                'title' => $a['title'] ?? '',
                'description' => $a['description'] ?? '',
                'url' => $a['url'] ?? '',
                'image' => $this->validate_image_url($a['image'] ?? ''),
                'source' => $a['author'] ?? 'Currents',
                'published' => $a['published'] ?? '',
                'provider' => 'Currents API',
            );
        }
        return $result;
    }
    
    private function normalize_thenewsapi($articles) {
        $result = array();
        foreach ($articles as $a) {
            $result[] = array(
                'title' => $a['title'] ?? '',
                'description' => $a['description'] ?? '',
                'url' => $a['url'] ?? '',
                'image' => $this->validate_image_url($a['image'] ?? ''),
                'source' => $a['source'] ?? 'TheNewsAPI',
                'published' => $a['published_at'] ?? '',
                'provider' => 'TheNewsAPI',
            );
        }
        return $result;
    }
    
    private function normalize_mediastack($articles) {
        $result = array();
        foreach ($articles as $a) {
            $result[] = array(
                'title' => $a['title'] ?? '',
                'description' => $a['description'] ?? '',
                'url' => $a['url'] ?? '',
                'image' => $this->validate_image_url($a['image'] ?? ''),
                'source' => $a['source'] ?? 'Mediastack',
                'published' => $a['published_at'] ?? '',
                'provider' => 'Mediastack',
            );
        }
        return $result;
    }
    
    private function get_category_query($category) {
        $queries = array(
            'technology' => 'technology OR software OR programming OR cybersecurity OR AI OR startup OR cloud OR DevOps OR blockchain',
            'it' => 'information technology OR enterprise software OR DevOps OR cloud computing OR SaaS OR database OR API OR programming language',
            'startups' => 'tech startup OR venture capital OR seed funding OR series A OR startup funding OR tech entrepreneurship',
            'startup' => 'tech startup OR venture capital OR seed funding OR series A OR startup funding OR tech entrepreneurship',
            'cybersecurity' => 'cybersecurity OR data breach OR malware OR ransomware OR vulnerability OR hack OR phishing OR zero-day OR encryption',
            'ai' => 'artificial intelligence OR machine learning OR deep learning OR neural network OR LLM OR GPT OR ChatGPT OR Claude OR Gemini OR AI model',
        );
        return urlencode($queries[$category] ?? $queries['technology']);
    }
    
    private function is_tech_article($article) {
        $title = strtolower($article['title'] ?? '');
        $desc = strtolower($article['description'] ?? '');
        $text = $title . ' ' . $desc;
        
        if (preg_match($this->exclude_regex, $text)) {
            return false;
        }
        
        if (!preg_match($this->tech_regex, $text)) {
            return false;
        }
        
        return true;
    }
    
    private function validate_article($article) {
        if (empty(trim($article['title'] ?? ''))) return false;
        if (empty($article['url']) || !filter_var($article['url'], FILTER_VALIDATE_URL)) return false;
        $published = strtotime($article['published'] ?? '');
        if ($published && (time() - $published) > 14 * DAY_IN_SECONDS) return false;
        return true;
    }
    
    private function deduplicate($articles) {
        $unique = array();
        $seen = array();
        foreach ($articles as $a) {
            $key = strtolower(preg_replace('/[^a-z0-9]/', '', $a['title'] ?? ''));
            if (!in_array($key, $seen) && !empty($a['title'])) {
                $seen[] = $key;
                $unique[] = $a;
            }
        }
        return $unique;
    }
    
    private function validate_image_url($url) {
        if (empty($url) || !is_string($url)) return '';
        $url = trim($url);
        if (in_array(strtolower($url), array('none', 'null', '', 'false'))) return '';
        if (!filter_var($url, FILTER_VALIDATE_URL)) return '';
        if (!preg_match('/^https?:\/\//i', $url)) return '';
        return $url;
    }
    
    private function build_filter_regex() {
        $exclude = array(
            '\bcricket\b', '\bfootball\b', '\bsoccer\b', '\bnfl\b', '\bnba\b', '\bmlb\b',
            '\bwicket\b', '\btest\s+match\b', '\bpremier\s+league\b', '\bchampions\s+league\b',
            '\bwrestling\b', '\bmma\b', '\bboxing\b', '\brugby\b',
            '\bhollywood\b', '\bbollywood\b', '\bcelebrity\b', '\bgossip\b',
            '\bmovie\b', '\bfilm\b', '\bnetflix\b', '\bdisney\b', '\bstreaming\b',
            '\bstar\s+wars\b', '\bhorror\s+series\b', '\breality\s+tv\b',
            '\brecipe\b', '\bcooking\b', '\brestaurant\b', '\bfashion\b', '\bbeauty\b',
            '\bhoroscope\b', '\bastrology\b', '\bzodiac\b',
            '\bweather\b', '\bearthquake\b', '\bflood\b', '\bhurricane\b',
            '\boil\s+price\b', '\boil\s+venture\b', '\bopec\b',
            '\belection\b', '\bpolitical\s+party\b', '\bparliament\b',
            '\bwar\b', '\bmilitary\b', '\bmissile\b', '\bbomb\b',
            '\bapple\s+pie\b', '\bapple\s+orchard\b', '\bapple\s+harvest\b',
            '\bnikola\s+tesla\b', '\btesla\s+coil\b',
        );
        $this->exclude_regex = '/(' . implode('|', $exclude) . ')/i';
        
        $tech = array(
            '\btech\b', '\btechnology\b', '\bsoftware\b', '\bhardware\b', '\bdeveloper\b',
            '\bprogramming\b', '\bcoding\b', '\bopen\s+source\b',
            '\bartificial\s+intelligence\b', '\bmachine\s+learning\b', '\bdeep\s+learning\b',
            '\bllm\b', '\bgpt\b', '\bai\b', '\bai\s+model\b', '\bneural\s+network\b',
            '\bcybersecurity\b', '\bdata\s+breach\b', '\bmalware\b', '\bransomware\b',
            '\bvulnerability\b', '\bphishing\b', '\bzero-day\b', '\bencryption\b',
            '\bcloud\s+computing\b', '\bsaas\b', '\bpaas\b', '\biaas\b',
            '\bdevops\b', '\bkubernetes\b', '\bdocker\b',
            '\bstartup\b', '\bventure\s+capital\b', '\bseed\s+funding\b',
            '\bseries\s+[a-z]\b', '\bunicorn\b', '\bfintech\b', '\bedtech\b',
            '\bcrypto\b', '\bbitcoin\b', '\bblockchain\b',
            '\bchip\b', '\bsemiconductor\b', '\bquantum\s+computing\b',
            '\bgoogle\b', '\bmicrosoft\b', '\bapple\b', '\bmeta\b', '\bnvidia\b',
            '\bamazon\s+web\b', '\baws\b', '\bopenai\b', '\bspacex\b', '\bstarlink\b',
            '\bclaude\b', '\banthropic\b', '\bgemini\b', '\bchatgpt\b', '\bcopilot\b',
            '\bllama\b', '\bmistral\b', '\bperplexity\b', '\bcursor\b',
            '\bautonomous\b', '\brobot\b', '\brobotics\b', '\biot\b', '\bedge\s+computing\b',
            '\bapi\b', '\bopenshift\b', '\bred\s+hat\b', '\bcve\b', '\bsecurity\s+advisory\b',
            '\bcontainer\b', '\bkubernetes\b', '\bci\/cd\b', '\bgit\b', '\bgithub\b',
            '\bapp\b', '\bgaming\b', '\blaptop\b', '\bpc\b', '\bphone\b',
            '\bsmartphone\b', '\btablet\b', '\bconsole\b', '\bxbox\b', '\bplaystation\b',
            '\bgeforce\b', '\bradeon\b', '\bdlss\b', '\brtx\b',
            '\biphone\b', '\bipad\b', '\bmacbook\b', '\bchromebook\b',
            '\bsmartwatch\b', '\bwearable\b', '\bpower\s+supply\b',
            '\bsamsung\b', '\bxperia\b', '\bgalaxy\b', '\barcade\b',
            '\brog\b', '\balienware\b', '\bthinkpad\b', '\bMacBook\b',
        );
        $this->tech_regex = '/(' . implode('|', $tech) . ')/i';
    }
    
    private function time_ago($datetime) {
        if (empty($datetime)) return '';
        try {
            $now = new DateTime();
            $past = new DateTime($datetime);
            $diff = $now->diff($past);
            if ($diff->y > 0) return $diff->y . 'y ago';
            if ($diff->m > 0) return $diff->m . 'mo ago';
            if ($diff->d > 0) return $diff->d . 'd ago';
            if ($diff->h > 0) return $diff->h . 'h ago';
            if ($diff->i > 0) return $diff->i . 'm ago';
            return 'Just now';
        } catch (Exception $e) {
            return '';
        }
    }
    
    public function ajax_fetch_news() {
        check_ajax_referer('fetch_news_nonce', 'nonce');
        $category = sanitize_text_field($_POST['category'] ?? 'technology');
        $count = intval($_POST['count'] ?? 10);
        $articles = $this->fetch_all_news($category, 'publishedAt', $count);
        wp_send_json_success(array('articles' => $articles, 'count' => count($articles)));
    }
    
    public function cli_test_fetch($args, $assoc_args) {
        $category = $args[0] ?? 'startups';
        $count = intval($assoc_args['count'] ?? 10);
        
        \WP_CLI::log("=== {$category} Pipeline Test ===");
        
        $active = $this->get_active_apis();
        \WP_CLI::log("Active APIs: " . implode(', ', array_keys($active)) . "\n");
        
        $all_articles = array();
        foreach ($active as $key => $api) {
            $fetched = $this->fetch_from_provider($key, $api['key'], $category, $count);
            $all_articles = array_merge($all_articles, $fetched);
            \WP_CLI::log("[" . strtoupper($key) . "] Raw fetched: " . count($fetched));
        }
        
        $all_articles = $this->deduplicate($all_articles);
        \WP_CLI::log("\nAfter dedup: " . count($all_articles) . " unique articles\n");
        
        $accepted = array();
        $rejected = array();
        foreach ($all_articles as $a) {
            if (!$this->validate_article($a)) {
                $rejected[] = array('title' => $a['title'], 'reason' => 'invalid_article');
                continue;
            }
            if (!$this->is_tech_article($a)) {
                $rejected[] = array('title' => $a['title'], 'reason' => 'non_tech');
                continue;
            }
            $accepted[] = $a;
        }
        
        \WP_CLI::log("=== Results: " . count($accepted) . " accepted, " . count($rejected) . " rejected ===\n");
        
        \WP_CLI::log("--- Accepted Samples ---");
        foreach (array_slice($accepted, 0, 10) as $i => $a) {
            $has_image = !empty($a['image']) ? '✓ image' : '✗ no image';
            \WP_CLI::log(($i + 1) . ". " . mb_substr($a['title'], 0, 80) . " [{$a['source']}] {$has_image}");
        }
        
        if (!empty($rejected)) {
            \WP_CLI::log("\n--- Rejected Samples ---");
            foreach (array_slice($rejected, 0, 10) as $i => $r) {
                \WP_CLI::log(($i + 1) . ". " . mb_substr($r['title'], 0, 80) . " → {$r['reason']}");
            }
        }
        
        \WP_CLI::success("Test complete.");
    }
    
    // === SHORTCODES ===
    
    public function news_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 10, 'category' => 'technology'), $atts);
        $articles = $this->get_cached_or_fetch($atts['category'], $atts['count']);
        return $this->render_news_list($articles, $atts['count'], 'it');
    }
    
    public function news_grid_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 6, 'category' => 'technology'), $atts);
        $articles = $this->get_cached_or_fetch($atts['category'], $atts['count']);
        return $this->render_news_grid($articles, $atts['count'], 'it');
    }
    
    public function startup_news_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 8), $atts);
        $articles = $this->get_cached_or_fetch('startups', $atts['count']);
        return $this->render_news_list($articles, $atts['count'], 'startup');
    }
    
    public function cyber_news_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 8), $atts);
        $articles = $this->get_cached_or_fetch('cybersecurity', $atts['count']);
        return $this->render_news_list($articles, $atts['count'], 'cyber');
    }
    
    public function ai_news_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 8), $atts);
        $articles = $this->get_cached_or_fetch('ai', $atts['count']);
        return $this->render_news_list($articles, $atts['count'], 'ai');
    }
    
    public function it_news_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 8), $atts);
        $articles = $this->get_cached_or_fetch('it', $atts['count']);
        return $this->render_news_list($articles, $atts['count'], 'it');
    }
    
    public function get_cached_or_fetch($category, $count) {
        $cached = get_transient('techportal_news_cache_' . $category);
        if ($cached !== false && count($cached) >= $count) {
            return array_slice($cached, 0, $count);
        }
        return $this->fetch_all_news($category, 'publishedAt', $count);
    }
    
    private function render_news_list($articles, $count, $tag) {
        if (empty($articles)) return '<p>No news available.</p>';
        
        $tag_class = 'tag-' . $tag;
        $tag_labels = array(
            'it' => 'IT News',
            'startup' => 'Startups',
            'cyber' => 'Cybersecurity',
            'ai' => 'AI & LLMs',
            'live' => 'Live Shows',
        );
        $tag_label = $tag_labels[$tag] ?? ucfirst($tag);
        
        $output = '<div class="tech-news-feed">';
        foreach (array_slice($articles, 0, $count) as $article) {
            $source = esc_html($article['source'] ?? '');
            $time = $this->time_ago($article['published'] ?? '');
            
            $output .= '<div class="news-card">';
            $output .= '<div class="news-card-content">';
            $output .= '<h3><a href="' . esc_url($article['url']) . '" target="_blank" rel="noopener">' . esc_html($article['title'] ?? '') . '</a></h3>';
            $output .= '<p>' . esc_html(wp_trim_words($article['description'] ?? '', 25)) . '</p>';
            $output .= '<div class="news-card-meta">';
            $output .= '<span class="news-source">' . $source . '</span>';
            $output .= '<span class="news-time">' . $time . '</span>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
        }
        $output .= '</div>';
        
        return $output;
    }
    
    private function render_news_grid($articles, $count, $tag) {
        if (empty($articles)) return '<p>No news available.</p>';
        
        $output = '<div class="tech-news-grid">';
        foreach (array_slice($articles, 0, $count) as $article) {
            $output .= '<div class="news-card-grid">';
            $output .= '<h4><a href="' . esc_url($article['url']) . '" target="_blank" rel="noopener">' . esc_html($article['title'] ?? '') . '</a></h4>';
            $output .= '<p>' . esc_html(wp_trim_words($article['description'] ?? '', 20)) . '</p>';
            $output .= '<span class="news-source-small">' . esc_html($article['source'] ?? '') . '</span>';
            $output .= '</div>';
        }
        $output .= '</div>';
        
        return $output;
    }
}

class TechPortal_News_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('techportal_news_widget', 'Tech News Widget');
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'] . ($instance['title'] ?? 'Latest Tech News') . $args['after_title'];
        
        $aggregator = new TechPortal_News_Aggregator();
        $articles = $aggregator->fetch_all_news($instance['category'] ?? 'technology', 'publishedAt', $instance['count'] ?? 5);
        
        echo '<ul class="tech-news-widget">';
        foreach (array_slice($articles, 0, $instance['count'] ?? 5) as $article) {
            echo '<li><a href="' . esc_url($article['url']) . '" target="_blank" rel="noopener">' . esc_html($article['title'] ?? '') . '</a></li>';
        }
        echo '</ul>';
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        ?>
        <p><label>Title:</label> <input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title'] ?? 'Latest Tech News'); ?>" /></p>
        <p><label>Category:</label> <select class="widefat" name="<?php echo $this->get_field_name('category'); ?>">
            <option value="technology" <?php selected($instance['category'] ?? '', 'technology'); ?>>Technology</option>
            <option value="startups" <?php selected($instance['category'] ?? '', 'startups'); ?>>Startups</option>
            <option value="cybersecurity" <?php selected($instance['category'] ?? '', 'cybersecurity'); ?>>Cybersecurity</option>
            <option value="ai" <?php selected($instance['category'] ?? '', 'ai'); ?>>AI</option>
        </select></p>
        <p><label>Count:</label> <input type="number" class="small-text" name="<?php echo $this->get_field_name('count'); ?>" value="<?php echo esc_attr($instance['count'] ?? 5); ?>" min="1" max="20" /></p>
        <?php
    }
}

new TechPortal_News_Aggregator();
