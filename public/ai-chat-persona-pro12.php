<?php
// Define plugin version for bridge compatibility checks
// MUST match the Version: header below for versace22 bridge harmony
if (!defined('AI_CHAT_PERSONA_PRO_VERSION')) {
    define('AI_CHAT_PERSONA_PRO_VERSION', '12.3');
}
/**
 * Plugin Name: AI Chat Persona Pro - Ultimate Character Engine
 * Description: AI Chat with Main Site Character, Public/Private Personas, Per-Client Assignment, Emotional Intelligence, Rewards System, Character Binding & 5-Slot Hidden Injection
 * Version: 12.3
 * Author: AI Pipeline Pro
 */
if (!defined('ABSPATH')) exit;
// MISS I: hard guard against duplicate plugin copies (e.g. /public/ and /wordpress-assets/
// both deployed) — without this, a second include yields a fatal "Cannot redeclare class".
if (class_exists('AI_Chat_Persona_Pro_Ultimate')) { return; }
// VERSACE22 INTEGRATION: Soft-load bridge to prevent fatal on missing dependency
$versace22_bridge_path = plugin_dir_path(__FILE__) . 'versace22-enqueue.php';
if (file_exists($versace22_bridge_path)) {
    require_once $versace22_bridge_path;
    // Register this plugin instance with the versace22 bridge for bidirectional communication
    if (function_exists('versace22_register_plugin')) {
        versace22_register_plugin('ai-chat-persona-pro', __FILE__, AI_CHAT_PERSONA_PRO_VERSION);
    }
} else {
    // Defer notice to admin_init so it renders properly
    add_action('admin_init', function () {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>AI Chat Persona Pro:</strong> versace22-enqueue.php is missing. The plugin will run in standalone mode with limited functionality. Place versace22-enqueue.php in the same folder for full integration.</p></div>';
        });
    });
    if (function_exists('error_log')) {
        error_log('[AI Chat Persona Pro] WARNING: versace22-enqueue.php not found. Running in standalone mode.');
    }
}

class AI_Chat_Persona_Pro_Ultimate {

    private static $instance = null;
    private $table_conversations;
    private $table_messages;
    private $table_personas;
    private $table_persona_assignments;
    private $table_analytics;
    private $table_injection_log;
    private $table_injection_state;
    private $table_referrals;
    private $table_memories;
    private $table_projects;
    private $table_project_files;
    private $table_artifacts;
    private $version = '12.3';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->check_versace22_bridge();
        $this->table_conversations      = $wpdb->prefix . 'aicpp_conversations';
        $this->table_messages            = $wpdb->prefix . 'aicpp_messages';
        $this->table_personas            = $wpdb->prefix . 'aicpp_personas';
        $this->table_persona_assignments = $wpdb->prefix . 'aicpp_persona_assignments';
        $this->table_analytics           = $wpdb->prefix . 'aicpp_analytics';
        $this->table_injection_log       = $wpdb->prefix . 'aicpp_injection_log';
        $this->table_injection_state     = $wpdb->prefix . 'aicpp_injection_state';
        $this->table_referrals          = $wpdb->prefix . 'aicpp_referrals';
        $this->table_memories            = $wpdb->prefix . 'aicpp_memories';
        $this->table_projects            = $wpdb->prefix . 'aicpp_projects';
        $this->table_project_files       = $wpdb->prefix . 'aicpp_project_files';
        $this->table_artifacts           = $wpdb->prefix . 'aicpp_artifacts';

        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('admin_menu', [$this, 'add_admin_menus']);
        add_action('admin_head', [$this, 'admin_styles']);
        add_action('admin_init', [$this, 'check_db_upgrade']);

        // Admin AJAX
        add_action('wp_ajax_aicpp_save_persona', [$this, 'ajax_save_persona']);
        add_action('wp_ajax_aicpp_delete_persona', [$this, 'ajax_delete_persona']);
        add_action('wp_ajax_aicpp_get_persona', [$this, 'ajax_get_persona']);
        add_action('wp_ajax_aicpp_search_users', [$this, 'ajax_search_users']);
        add_action('wp_ajax_aicpp_get_persona_users', [$this, 'ajax_get_persona_users']);
        add_action('wp_ajax_aicpp_assign_persona', [$this, 'ajax_assign_persona']);
        add_action('wp_ajax_aicpp_unassign_persona', [$this, 'ajax_unassign_persona']);
        add_action('wp_ajax_aicpp_get_user_personas', [$this, 'ajax_get_user_personas']);
        add_action('wp_ajax_aicpp_bulk_assign', [$this, 'ajax_bulk_assign']);

        // Public + logged-in AJAX
        add_action('wp_ajax_aicpp_chat', [$this, 'handle_chat']);
        add_action('wp_ajax_nopriv_aicpp_chat', [$this, 'handle_chat']);
        add_action('wp_ajax_aicpp_get_my_personas', [$this, 'ajax_get_my_personas']);
        add_action('wp_ajax_nopriv_aicpp_get_my_personas', [$this, 'ajax_get_my_personas']);
        add_action('wp_ajax_aicpp_get_conversations', [$this, 'ajax_get_conversations']);
        add_action('wp_ajax_nopriv_aicpp_get_conversations', [$this, 'ajax_get_conversations']);
        add_action('wp_ajax_aicpp_load_conversation', [$this, 'ajax_load_conversation']);
        add_action('wp_ajax_nopriv_aicpp_load_conversation', [$this, 'ajax_load_conversation']);
        add_action('wp_ajax_aicpp_delete_conversation', [$this, 'ajax_delete_conversation']);
        add_action('wp_ajax_nopriv_aicpp_delete_conversation', [$this, 'ajax_delete_conversation']);
        add_action('wp_ajax_aicpp_upload_file', [$this, 'ajax_upload_file']);
        add_action('wp_ajax_nopriv_aicpp_upload_file', [$this, 'ajax_upload_file']);
        add_action('wp_ajax_aicpp_transcribe_audio', [$this, 'ajax_transcribe_audio']);
        add_action('wp_ajax_nopriv_aicpp_transcribe_audio', [$this, 'ajax_transcribe_audio']);
        add_action('wp_ajax_nopriv_aicpp_register_user', [$this, 'ajax_register_user']);
        add_action('wp_ajax_nopriv_aicpp_login_user', [$this, 'ajax_login_user']);
        add_action('wp_ajax_aicpp_update_profile', [$this, 'ajax_update_profile']);
        add_action('wp_ajax_aicpp_get_leaderboard', [$this, 'ajax_get_leaderboard']);
        add_action('wp_ajax_aicpp_get_referral_data', [$this, 'ajax_get_referral_data']);
        add_action('wp_ajax_aicpp_search_messages', [$this, 'ajax_search_messages']);
        add_action('wp_ajax_aicpp_pin_conversation', [$this, 'ajax_pin_conversation']);
        add_action('wp_ajax_aicpp_speak', [$this, 'ajax_speak']);
        // Feature 2: Persistent Memory
        add_action('wp_ajax_aicpp_get_memories', [$this, 'ajax_get_memories']);
        add_action('wp_ajax_aicpp_add_memory', [$this, 'ajax_add_memory']);
        add_action('wp_ajax_aicpp_update_memory', [$this, 'ajax_update_memory']);
        add_action('wp_ajax_aicpp_delete_memory', [$this, 'ajax_delete_memory']);
        add_action('wp_ajax_aicpp_toggle_memory', [$this, 'ajax_toggle_memory']);
        // Feature 3: Projects
        add_action('wp_ajax_aicpp_get_projects', [$this, 'ajax_get_projects']);
        add_action('wp_ajax_aicpp_create_project', [$this, 'ajax_create_project']);
        add_action('wp_ajax_aicpp_update_project', [$this, 'ajax_update_project']);
        add_action('wp_ajax_aicpp_delete_project', [$this, 'ajax_delete_project']);
        add_action('wp_ajax_aicpp_attach_project_file', [$this, 'ajax_attach_project_file']);
        add_action('wp_ajax_aicpp_detach_project_file', [$this, 'ajax_detach_project_file']);
        add_action('wp_ajax_aicpp_assign_conversation_project', [$this, 'ajax_assign_conversation_project']);
        // Feature 4: Artifacts
        add_action('wp_ajax_aicpp_save_artifact', [$this, 'ajax_save_artifact']);
        add_action('wp_ajax_aicpp_get_artifact', [$this, 'ajax_get_artifact']);
        add_action('wp_ajax_aicpp_list_artifacts', [$this, 'ajax_list_artifacts']);
        add_action('wp_ajax_aicpp_delete_artifact', [$this, 'ajax_delete_artifact']);
        // Feature 6: OpenRouter free models preset
        add_action('wp_ajax_aicpp_or_free_models', [$this, 'ajax_or_free_models']);
        add_action('wp_ajax_aicpp_or_refresh_free', [$this, 'ajax_or_refresh_free']);

        // Main character chat (no persona_id needed)
        add_action('wp_ajax_aicpp_chat_main', [$this, 'handle_chat_main']);
        add_action('wp_ajax_nopriv_aicpp_chat_main', [$this, 'handle_chat_main']);

        add_shortcode('ai_chat_persona', [$this, 'chat_shortcode']);

        // VERSACE22 INTEGRATION: Register all endpoints with bridge
        if (function_exists('versace22_register_endpoints')) {
            versace22_register_endpoints($this->get_endpoint_manifest());
        }
    }

    /**
     * Check if versace22-enqueue is active and compatible
     * Reverse dependency check - ensures the bridge is present
     * Sets $this->bridge_ready so other methods can adapt behavior
     */
    private $bridge_ready = false;
    /**
     * Detect the bridge by its public API, not by manifest contents.
     * Manifest is keyed by 'chat'/'personas'/etc (never 'ai-chat-persona-pro'),
     * AND this runs before the plugin registers its endpoints, so any content
     * check here always fails. API presence is the only reliable signal.
     */
    public function check_versace22_bridge() {
        $has_render   = function_exists('versace22_render_app');
        $has_manifest = function_exists('versace22_get_endpoint_manifest');
        if (!$has_render || !$has_manifest) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p><strong>AI Chat Persona Pro:</strong> versace22-enqueue.php bridge API not detected. Running in standalone mode.</p></div>';
            });
            if (function_exists('error_log')) {
                error_log('[AI Chat Persona Pro] NOTICE: versace22 bridge API not found; standalone mode.');
            }
            $this->bridge_ready = false;
            return false;
        }
        $this->bridge_ready = true;
        return true;
    }

    /**
     * Return registered endpoints for external discovery (REST fallback)
     * NOTE: Primary transport is admin-ajax via versace22 bridge.
     * This method is reserved for future REST API v2 expansion.
     */
    public function get_registered_endpoints() {
        // Bridge-native endpoints are the canonical transport.
        // If you implement REST API handlers later, map them here.
        return apply_filters('ai_chat_persona_pro_endpoints', array());
    }



    // VERSACE22 INTEGRATION: Provide endpoint manifest to bridge
    // Manifest format version: v12 (matches bridge v12.x contract)
    // If you add/remove endpoints, bump the plugin version AND update bridge max version.
    public function get_endpoint_manifest() {
        return [
            'chat' => ['action' => 'aicpp_chat', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => true],
            'chat_main' => ['action' => 'aicpp_chat_main', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => true],
            'transcribe_audio' => ['action' => 'aicpp_transcribe_audio', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => true],
            'upload_file' => ['action' => 'aicpp_upload_file', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => true],
            'speak' => ['action' => 'aicpp_speak', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => false],
            'search_messages' => ['action' => 'aicpp_search_messages', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => false],
            'conversations' => [
                ['key' => 'list', 'action' => 'aicpp_get_conversations', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => true],
                ['key' => 'load', 'action' => 'aicpp_load_conversation', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => true],
                ['key' => 'delete', 'action' => 'aicpp_delete_conversation', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => true],
                ['key' => 'pin', 'action' => 'aicpp_pin_conversation', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => false],
                ['key' => 'assign_to_project', 'action' => 'aicpp_assign_conversation_project', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => false],
            ],
            'personas' => [
                ['key' => 'mine', 'action' => 'aicpp_get_my_personas', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => true],
                ['key' => 'get', 'action' => 'aicpp_get_persona', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'save', 'action' => 'aicpp_save_persona', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'delete', 'action' => 'aicpp_delete_persona', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'assign', 'action' => 'aicpp_assign_persona', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'unassign', 'action' => 'aicpp_unassign_persona', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'bulk_assign', 'action' => 'aicpp_bulk_assign', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'user_personas', 'action' => 'aicpp_get_user_personas', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'persona_users', 'action' => 'aicpp_get_persona_users', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'search_users', 'action' => 'aicpp_search_users', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
            ],
            'projects' => [
                ['key' => 'list', 'action' => 'aicpp_get_projects', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'create', 'action' => 'aicpp_create_project', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'update', 'action' => 'aicpp_update_project', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'delete', 'action' => 'aicpp_delete_project', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'attach_file', 'action' => 'aicpp_attach_project_file', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'detach_file', 'action' => 'aicpp_detach_project_file', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
            ],
            'memories' => [
                ['key' => 'list', 'action' => 'aicpp_get_memories', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'add', 'action' => 'aicpp_add_memory', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'update', 'action' => 'aicpp_update_memory', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'delete', 'action' => 'aicpp_delete_memory', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'toggle', 'action' => 'aicpp_toggle_memory', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
            ],
            'artifacts' => [
                ['key' => 'list', 'action' => 'aicpp_list_artifacts', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => false],
                ['key' => 'get', 'action' => 'aicpp_get_artifact', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => false],
                ['key' => 'save', 'action' => 'aicpp_save_artifact', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => false],
                ['key' => 'delete', 'action' => 'aicpp_delete_artifact', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => false],
            ],
            'rewards' => [
                ['key' => 'referrals', 'action' => 'aicpp_get_referral_data', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => false],
                ['key' => 'leaderboard', 'action' => 'aicpp_get_leaderboard', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => false],
            ],
            'account' => [
                ['key' => 'update_profile', 'action' => 'aicpp_update_profile', 'nonce' => 'aicpp_chat', 'cap' => 'read', 'nopriv' => false],
                ['key' => 'login', 'action' => 'aicpp_login_user', 'nonce' => 'aicpp_login', 'cap' => '', 'nopriv' => true],
                ['key' => 'register', 'action' => 'aicpp_register_user', 'nonce' => 'aicpp_register', 'cap' => '', 'nopriv' => true],
            ],
            'models' => [
                ['key' => 'free_models', 'action' => 'aicpp_or_free_models', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
                ['key' => 'refresh_free', 'action' => 'aicpp_or_refresh_free', 'nonce' => 'aicpp', 'cap' => 'manage_options', 'nopriv' => false],
            ],
        ];
    }

    // ===================== DB UPGRADE CHECK =====================
    public function check_db_upgrade() {
        $db_version = get_option('aicpp_db_version', '0');
        if (version_compare($db_version, $this->version, '<')) {
            $this->activate();
            update_option('aicpp_db_version', $this->version);
        }
    }

    // ===================== ACTIVATION =====================
    public function activate() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$this->table_conversations} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            persona_id bigint(20) DEFAULT NULL,
            session_id varchar(255) NOT NULL,
            title varchar(255) DEFAULT '',
            token_count int(11) DEFAULT 0,
            is_main_chat tinyint(1) DEFAULT 0,
            pinned tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_session_id (session_id),
            KEY idx_user_id (user_id)
        ) ENGINE=InnoDB $charset");

        dbDelta("CREATE TABLE {$this->table_messages} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) NOT NULL,
            role varchar(20) NOT NULL,
            content longtext NOT NULL,
            raw_content longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_conversation_id (conversation_id)
        ) ENGINE=InnoDB $charset");

        dbDelta("CREATE TABLE {$this->table_personas} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            avatar_initials varchar(10) DEFAULT '',
            avatar_color varchar(20) DEFAULT '#667eea',
            system_prompt longtext NOT NULL,
            emotional_intelligence_code longtext,
            rewards_code longtext,
            use_global_ei tinyint(1) DEFAULT 1,
            use_global_rewards tinyint(1) DEFAULT 1,
            model varchar(100) DEFAULT 'gpt-4',
            temperature decimal(3,2) DEFAULT 0.70,
            max_tokens int(11) DEFAULT 2000,
            visibility varchar(20) DEFAULT 'private',
            created_by bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) ENGINE=InnoDB $charset");

        dbDelta("CREATE TABLE {$this->table_persona_assignments} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            persona_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            assigned_by bigint(20) NOT NULL,
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_persona_user (persona_id,user_id),
            KEY idx_user_id (user_id),
            KEY idx_persona_id (persona_id)
        ) ENGINE=InnoDB $charset");

        dbDelta("CREATE TABLE {$this->table_analytics} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(100),
            tokens_used int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) ENGINE=InnoDB $charset");

        dbDelta("CREATE TABLE {$this->table_injection_log} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id varchar(100),
            slot_used int(11),
            message_preview text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) ENGINE=InnoDB $charset");

        dbDelta("CREATE TABLE {$this->table_injection_state} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_key varchar(255) NOT NULL,
            current_slot int(11) DEFAULT 1,
            question_count int(11) DEFAULT 1,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_user_key (user_key)
        ) ENGINE=InnoDB $charset");

        dbDelta("CREATE TABLE {$this->table_referrals} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            referrer_user_id bigint(20) NOT NULL,
            referred_user_id bigint(20) NOT NULL,
            referral_code varchar(64) DEFAULT '',
            points_awarded int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_referred_user (referred_user_id),
            KEY idx_referrer_user (referrer_user_id),
            KEY idx_referral_code (referral_code)
        ) ENGINE=InnoDB $charset");

        dbDelta("CREATE TABLE {$this->table_memories} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            persona_id bigint(20) DEFAULT 0,
            memory_text varchar(500) NOT NULL,
            enabled tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_user_id (user_id),
            KEY idx_persona_id (persona_id)
        ) ENGINE=InnoDB $charset");

        dbDelta("CREATE TABLE {$this->table_projects} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            custom_instructions longtext,
            color varchar(20) DEFAULT '#667eea',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_user_id (user_id)
        ) ENGINE=InnoDB $charset");

        dbDelta("CREATE TABLE {$this->table_project_files} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            project_id bigint(20) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_url varchar(1024) DEFAULT '',
            file_type varchar(100) DEFAULT '',
            content_excerpt longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_project_id (project_id)
        ) ENGINE=InnoDB $charset");

        dbDelta("CREATE TABLE {$this->table_artifacts} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            title varchar(255) DEFAULT '',
            artifact_type varchar(40) NOT NULL,
            content longtext NOT NULL,
            version int(11) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_conversation_id (conversation_id),
            KEY idx_user_id (user_id)
        ) ENGINE=InnoDB $charset");

        // Add project_id column to conversations if missing (idempotent)
        $col = $wpdb->get_var($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'project_id'",
            DB_NAME, $this->table_conversations
        ));
        if (!$col) {
            $wpdb->query("ALTER TABLE {$this->table_conversations} ADD COLUMN project_id bigint(20) DEFAULT 0, ADD KEY idx_project_id (project_id)");
        }

        $this->create_defaults();
    }

    private function create_defaults() {
        $defaults = [
            'aicpp_api_provider'            => 'openai',
            'aicpp_openai_api_key'          => '',
            'aicpp_anthropic_api_key'       => '',
            'aicpp_google_api_key'          => '',
            'aicpp_deepseek_api_key'        => '',
            'aicpp_openrouter_api_key'      => '',
            'aicpp_mistral_api_key'         => '',
            'aicpp_groq_api_key'            => '',
            'aicpp_character_binding_active' => '0',
            'aicpp_active_character_code'   => '',
            'aicpp_global_ei_enabled'       => '0',
            'aicpp_global_ei_code'          => '',
            'aicpp_global_rewards_enabled'  => '0',
            'aicpp_global_rewards_code'     => '',
            'aicpp_injection_enabled'       => '0',
            'aicpp_max_message_length'      => '10000',
            'aicpp_require_login'           => '1',
            'aicpp_login_message'           => 'Please sign in to access your AI assistant.',
            // Main Site Character defaults
            'aicpp_main_char_enabled'       => '0',
            'aicpp_main_char_name'          => 'AI Assistant',
            'aicpp_main_char_description'   => 'Your helpful AI assistant',
            'aicpp_main_char_avatar_initials' => 'AI',
            'aicpp_main_char_avatar_color'  => '#667eea',
            'aicpp_main_char_system_prompt' => 'You are a helpful, friendly AI assistant. Answer questions accurately and helpfully.',
            'aicpp_main_char_model'         => 'gpt-4',
            'aicpp_main_char_temperature'   => '0.7',
            'aicpp_main_char_max_tokens'    => '2000',
        ];
        foreach ($defaults as $k => $v) {
            add_option($k, $v);
        }
        for ($i = 1; $i <= 5; $i++) {
            add_option("aicpp_slot{$i}_enabled", '1');
            add_option("aicpp_hidden_message_{$i}", '');
        }
    }

    // ===================== ENCRYPTION HELPERS =====================
    private function encrypt_api_key($plain) {
        if (empty($plain)) return '';
        $key = wp_salt('auth');
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $strong = false;
        $iv = openssl_random_pseudo_bytes($iv_length, $strong);
        if (!$strong) {
            // Fall back to PHP's CSPRNG if available
            $iv = function_exists('random_bytes') ? random_bytes($iv_length) : $iv;
        }
        $encrypted = openssl_encrypt($plain, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    private function decrypt_api_key($stored) {
        if (empty($stored)) return '';
        $key = wp_salt('auth');
        $data = base64_decode($stored, true); // strict mode
        if ($data === false) return $stored; // not encrypted, return as-is
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        if (strlen($data) < $iv_length + 2) return '';
        $iv = substr($data, 0, $iv_length);
        // Confirm separator sits exactly after the fixed-length IV
        if (substr($data, $iv_length, 2) !== '::') return '';
        $encrypted = substr($data, $iv_length + 2);
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
        return $decrypted !== false ? $decrypted : '';
    }

    private function get_api_key($provider) {
        return $this->decrypt_api_key(get_option("aicpp_{$provider}_api_key", ''));
    }

    // ===================== RATE LIMITING =====================
    private function check_rate_limit($action, $limit = 5, $window = 300) {
        $ip = $this->get_client_ip();
        $transient_key = 'aicpp_rl_' . md5($action . $ip);
        $attempts = get_transient($transient_key);
        if ($attempts === false) {
            set_transient($transient_key, 1, $window);
            return true;
        }
        if ($attempts >= $limit) return false;
        set_transient($transient_key, $attempts + 1, $window);
        return true;
    }

    private function get_client_ip() {
        $candidates = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($candidates as $key) {
            if (empty($_SERVER[$key])) continue;
            $raw = wp_unslash($_SERVER[$key]);
            // X-Forwarded-For can be a comma-separated list; take the first.
            $ip = trim(explode(',', $raw)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
        return '0.0.0.0';
    }

    // ===================== CONVERSATION OWNERSHIP =====================
    private function verify_conversation_ownership($conv) {
        $user_id = get_current_user_id();
        $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';
        $row_user_id = (int) $conv->user_id;
        $row_session = (string) $conv->session_id;

        // Logged-in path: own row OR own a prior guest row by matching session id.
        if ($user_id) {
            if ($row_user_id === $user_id) return true;
            if ($row_user_id === 0 && !empty($session_id) && !empty($row_session) && hash_equals($row_session, $session_id)) {
                return true;
            }
            return false;
        }

        // Guest path: only guest-owned rows (user_id = 0) with a matching session id.
        if ($row_user_id !== 0) return false;
        if (empty($session_id) || empty($row_session)) return false;
        return hash_equals($row_session, $session_id);
    }

    // ===================== PERSONA ACCESS CHECK =====================
    private function user_can_access_persona($user_id, $persona_id) {
        $user_id = (int) $user_id;
        $persona_id = (int) $persona_id;
        if (!$user_id || !$persona_id) return false;
        if (user_can($user_id, 'manage_options')) return true;

        global $wpdb;

        // Check if persona is public — everyone can access
        $visibility = $wpdb->get_var($wpdb->prepare(
            "SELECT visibility FROM {$this->table_personas} WHERE id = %d",
            $persona_id
        ));
        if ($visibility === 'public') return true;

        // Private persona — check assignment
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_persona_assignments} WHERE persona_id = %d AND user_id = %d",
            $persona_id, $user_id
        ));
        return (int)$count > 0;
    }

    /**
     * Get all personas a user can access:
     * - All public personas
     * - All private personas assigned to them
     * - Admins see everything
     */
    private function get_user_personas($user_id) {
        global $wpdb;

        if (user_can($user_id, 'manage_options')) {
            return $wpdb->get_results("SELECT * FROM {$this->table_personas} ORDER BY name ASC");
        }

        // Public personas + privately assigned personas
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT p.* FROM {$this->table_personas} p
             LEFT JOIN {$this->table_persona_assignments} pa ON p.id = pa.persona_id AND pa.user_id = %d
             WHERE p.visibility = 'public' OR pa.user_id = %d
             ORDER BY p.name ASC",
            $user_id, $user_id
        ));
    }

    // ===================== ADMIN STYLES =====================
    public function admin_styles() {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (strpos($page, 'aicpp') === false && $page !== 'ai-chat-persona-pro') return;
        ?>
        <style>
        .aicpp-wrap{max-width:1400px;margin:20px 20px 20px 0}
        .aicpp-card{background:#fff;border:1px solid #ccd0d4;border-radius:8px;padding:25px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
        .aicpp-card h2{margin-top:0;padding-bottom:15px;border-bottom:2px solid #f0f0f1}
        .aicpp-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin:20px 0}
        .aicpp-stat{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:25px;border-radius:12px;text-align:center}
        .aicpp-stat h3{font-size:36px;margin:0 0 5px}
        .aicpp-stat p{margin:0;opacity:.9}
        .aicpp-code{background:#1e1e1e;border-radius:8px;padding:5px;margin:15px 0}
        .aicpp-code textarea{width:100%;min-height:200px;background:#23282d;color:#50fa7b;border:none;padding:15px;font-family:Consolas,monospace;font-size:13px;border-radius:6px;box-sizing:border-box}
        .aicpp-info{background:#e7f5ff;border-left:4px solid #228be6;padding:15px 20px;margin:20px 0;border-radius:0 8px 8px 0}
        .aicpp-warning{background:#fff3bf;border-left:4px solid #fab005;padding:15px 20px;margin:20px 0;border-radius:0 8px 8px 0}
        .aicpp-success{background:#d3f9d8;border-left:4px solid #40c057;padding:15px 20px;margin:20px 0;border-radius:0 8px 8px 0}
        .aicpp-grid-5{display:grid;grid-template-columns:repeat(5,1fr);gap:15px}
        .aicpp-slot{background:#fff;border:2px solid #dee2e6;border-radius:10px;padding:15px}
        .aicpp-slot.s1{border-color:#228be6;background:linear-gradient(to bottom,#e7f5ff,#fff)}
        .aicpp-slot.s2{border-color:#40c057;background:linear-gradient(to bottom,#d3f9d8,#fff)}
        .aicpp-slot.s3{border-color:#fab005;background:linear-gradient(to bottom,#fff3bf,#fff)}
        .aicpp-slot.s4{border-color:#fd7e14;background:linear-gradient(to bottom,#ffe8cc,#fff)}
        .aicpp-slot.s5{border-color:#e64980;background:linear-gradient(to bottom,#ffdeeb,#fff)}
        .aicpp-slot h3{font-size:14px;margin:0 0 10px;display:flex;align-items:center;gap:8px}
        .aicpp-slot textarea{width:100%;min-height:150px;font-family:monospace;font-size:12px;border:1px solid #ddd;border-radius:6px;padding:10px;box-sizing:border-box}
        .aicpp-personas{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;margin-top:25px}
        .aicpp-pcard{background:#fff;border:1px solid #dee2e6;border-radius:10px;padding:20px;transition:.2s}
        .aicpp-pcard:hover{box-shadow:0 5px 20px rgba(0,0,0,.1);transform:translateY(-3px)}
        .aicpp-badge{background:#667eea;color:#fff;padding:4px 12px;border-radius:20px;font-size:11px;display:inline-block;margin:2px}
        .aicpp-badge-green{background:#40c057;color:#fff;padding:4px 12px;border-radius:20px;font-size:11px;display:inline-block;margin:2px}
        .aicpp-badge-orange{background:#fd7e14;color:#fff;padding:4px 12px;border-radius:20px;font-size:11px;display:inline-block;margin:2px}
        .aicpp-badge-red{background:#e64980;color:#fff;padding:4px 12px;border-radius:20px;font-size:11px;display:inline-block;margin:2px}
        .aicpp-badge-blue{background:#228be6;color:#fff;padding:4px 12px;border-radius:20px;font-size:11px;display:inline-block;margin:2px}
        .aicpp-on{background:#d3f9d8;color:#2b8a3e;padding:8px 16px;border-radius:20px;font-weight:600;display:inline-block}
        .aicpp-off{background:#ffe3e3;color:#c92a2a;padding:8px 16px;border-radius:20px;font-weight:600;display:inline-block}
        .aicpp-modal{display:none;position:fixed;z-index:100000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.6);align-items:center;justify-content:center}
        .aicpp-mbox{background:#fff;padding:30px;border-radius:12px;max-width:900px;width:90%;max-height:90vh;overflow-y:auto;position:relative}
        .aicpp-close{position:absolute;right:15px;top:10px;font-size:28px;cursor:pointer;color:#868e96}
        .aicpp-flow{display:flex;align-items:center;justify-content:center;gap:10px;padding:20px;background:#f8f9fa;border-radius:10px;margin:20px 0;flex-wrap:wrap}
        .aicpp-flow-item{padding:10px 15px;border-radius:8px;font-weight:600;font-size:13px}
        .aicpp-flow-arrow{color:#adb5bd;font-size:20px}
        .aicpp-provider-card{border:2px solid #dee2e6;border-radius:10px;padding:20px;margin-bottom:15px;transition:.2s}
        .aicpp-provider-card.active{border-color:#667eea;background:#f8f9ff}
        .aicpp-provider-card h4{margin:0 0 10px;display:flex;align-items:center;gap:10px}
        .aicpp-provider-badge{font-size:11px;padding:3px 8px;border-radius:10px;background:#e9ecef}
        .aicpp-provider-badge.connected{background:#d3f9d8;color:#2b8a3e}
        .aicpp-providers-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:20px}
        .aicpp-assign-section{border:2px solid #e9ecef;border-radius:10px;padding:20px;margin-top:20px;background:#f8f9fa}
        .aicpp-assign-section h3{margin:0 0 15px;font-size:16px}
        .aicpp-user-search{display:flex;gap:10px;margin-bottom:15px}
        .aicpp-user-search input{flex:1;padding:10px 15px;border:2px solid #dee2e6;border-radius:8px;font-size:14px}
        .aicpp-user-search button{padding:10px 20px;background:#667eea;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;white-space:nowrap}
        .aicpp-user-search button:hover{background:#5a6fd6}
        .aicpp-user-results{max-height:200px;overflow-y:auto;border:1px solid #dee2e6;border-radius:8px;background:#fff}
        .aicpp-user-row{display:flex;justify-content:space-between;align-items:center;padding:10px 15px;border-bottom:1px solid #f0f0f1;transition:.2s}
        .aicpp-user-row:last-child{border-bottom:none}
        .aicpp-user-row:hover{background:#f8f9ff}
        .aicpp-user-row .user-info{display:flex;flex-direction:column}
        .aicpp-user-row .user-name{font-weight:600;font-size:14px}
        .aicpp-user-row .user-email{font-size:12px;color:#868e96}
        .aicpp-user-row .user-login{font-size:11px;color:#adb5bd}
        .aicpp-user-row button{padding:5px 12px;border-radius:6px;border:none;cursor:pointer;font-size:12px;font-weight:600}
        .aicpp-user-row .btn-assign{background:#667eea;color:#fff}
        .aicpp-user-row .btn-assign:hover{background:#5a6fd6}
        .aicpp-user-row .btn-remove{background:#ffe3e3;color:#c92a2a}
        .aicpp-user-row .btn-remove:hover{background:#ffc9c9}
        .aicpp-assigned-list{margin-top:15px}
        .aicpp-assigned-list h4{margin:0 0 10px;font-size:14px;color:#495057}
        .aicpp-empty{text-align:center;padding:20px;color:#adb5bd;font-size:14px}
        .aicpp-user-card{background:#fff;border:1px solid #dee2e6;border-radius:10px;padding:20px;margin-bottom:15px}
        .aicpp-user-card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px}
        .aicpp-user-card-header h3{margin:0}
        .aicpp-persona-tags{display:flex;flex-wrap:wrap;gap:6px}
        .aicpp-section-icon{font-size:20px;margin-right:8px;vertical-align:middle}
        @media(max-width:1200px){.aicpp-grid-5{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:782px){.aicpp-grid-5{grid-template-columns:1fr}.aicpp-providers-grid{grid-template-columns:1fr}}
        </style>
        <?php
    }

    // ===================== ADMIN MENUS (with emojis) =====================
    public function add_admin_menus() { add_menu_page('AI Chat Pro','🤖 AI Chat Pro','manage_options','ai-chat-persona-pro',[$this,'page_dashboard'],'dashicons-format-chat',30); add_submenu_page('ai-chat-persona-pro','Dashboard','📊 Dashboard','manage_options','ai-chat-persona-pro',[$this,'page_dashboard']); add_submenu_page('ai-chat-persona-pro','Main Character','⭐ Main Character','manage_options','aicpp-main-character',[$this,'page_main_character']); add_submenu_page('ai-chat-persona-pro','Personas','🎭 Personas','manage_options','aicpp-personas',[$this,'page_personas']); add_submenu_page('ai-chat-persona-pro','Character Binding','🔗 Character Binding','manage_options','aicpp-binding',[$this,'page_binding']); add_submenu_page('ai-chat-persona-pro','Emotional Intelligence','💛 Emotional Intelligence','manage_options','aicpp-ei',[$this,'page_ei']); add_submenu_page('ai-chat-persona-pro','Rewards System','🏆 Rewards System','manage_options','aicpp-rewards',[$this,'page_rewards']); add_submenu_page('ai-chat-persona-pro','Hidden Injection','💉 Hidden Injection','manage_options','aicpp-injection',[$this,'page_injection']); add_submenu_page('ai-chat-persona-pro','Memory','🧠 Memory','manage_options','aicpp-memory',[$this,'page_memory']); add_submenu_page('ai-chat-persona-pro','Projects','📁 Projects','manage_options','aicpp-projects',[$this,'page_projects']); add_submenu_page('ai-chat-persona-pro','Artifacts','🎨 Artifacts','manage_options','aicpp-artifacts',[$this,'page_artifacts']); add_submenu_page('ai-chat-persona-pro','Settings','⚙️ Settings','manage_options','aicpp-settings',[$this,'page_settings']); }

    // ===================== DASHBOARD =====================
    public function page_dashboard() {
        if (!current_user_can('manage_options')) wp_die('Access denied');
        global $wpdb;
        $convos       = (int)($wpdb->get_var("SELECT COUNT(*) FROM {$this->table_conversations}") ?: 0);
        $personas     = (int)($wpdb->get_var("SELECT COUNT(*) FROM {$this->table_personas}") ?: 0);
        $public_count = (int)($wpdb->get_var("SELECT COUNT(*) FROM {$this->table_personas} WHERE visibility = 'public'") ?: 0);
        $private_count = (int)($wpdb->get_var("SELECT COUNT(*) FROM {$this->table_personas} WHERE visibility = 'private'") ?: 0);
        $tokens       = (int)($wpdb->get_var("SELECT SUM(token_count) FROM {$this->table_conversations}") ?: 0);
        $assignments  = (int)($wpdb->get_var("SELECT COUNT(*) FROM {$this->table_persona_assignments}") ?: 0);
        $clients      = (int)($wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$this->table_persona_assignments}") ?: 0);
        $main_enabled = get_option('aicpp_main_char_enabled', '0') === '1';
        $main_name    = get_option('aicpp_main_char_name', 'AI Assistant');
        $provider     = get_option('aicpp_api_provider', 'openai');
        $provider_names = [
            'openai' => 'OpenAI', 'anthropic' => 'Anthropic (Claude)', 'google' => 'Google (Gemini)',
            'deepseek' => 'DeepSeek', 'openrouter' => 'OpenRouter (Free Models)', 'mistral' => 'Mistral AI', 'groq' => 'Groq',
        ];
        ?>
        <div class="wrap aicpp-wrap">
            <h1>💬 AI Chat Persona Pro — Dashboard</h1>

            <div class="aicpp-card">
                <h2><span class="aicpp-section-icon">🏠</span> Welcome to AI Chat Persona Pro v<?php echo esc_html($this->version); ?></h2>
                <p>Create AI personas, configure a <strong>Main Site Character</strong>, and assign <strong>public or private personas</strong> to specific clients.</p>
                <p><strong>🔌 Active Provider:</strong> <span class="aicpp-badge"><?php echo esc_html($provider_names[$provider] ?? $provider); ?></span></p>
                <p><strong>🌟 Main Character:</strong>
                    <?php if ($main_enabled): ?>
                        <span class="aicpp-on">✅ ACTIVE — <?php echo esc_html($main_name); ?></span>
                    <?php else: ?>
                        <span class="aicpp-off">❌ INACTIVE</span>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aicpp-main-character')); ?>" class="button button-small" style="margin-left:10px">Configure</a>
                    <?php endif; ?>
                </p>
            </div>

            <div class="aicpp-stats">
                <div class="aicpp-stat"><h3><?php echo number_format($personas); ?></h3><p>🎭 Total Personas</p></div>
                <div class="aicpp-stat" style="background:linear-gradient(135deg,#228be6,#667eea)"><h3><?php echo number_format($public_count); ?></h3><p>🌍 Public Personas</p></div>
                <div class="aicpp-stat" style="background:linear-gradient(135deg,#fd7e14,#e64980)"><h3><?php echo number_format($private_count); ?></h3><p>🔒 Private Personas</p></div>
                <div class="aicpp-stat" style="background:linear-gradient(135deg,#40c057,#228be6)"><h3><?php echo number_format($clients); ?></h3><p>👥 Active Clients</p></div>
                <div class="aicpp-stat" style="background:linear-gradient(135deg,#fab005,#fd7e14)"><h3><?php echo number_format($convos); ?></h3><p>💬 Conversations</p></div>
                <div class="aicpp-stat" style="background:linear-gradient(135deg,#e64980,#be4bdb)"><h3><?php echo number_format($tokens); ?></h3><p>🪙 Tokens Used</p></div>
            </div>

            <div class="aicpp-card">
                <h2><span class="aicpp-section-icon">🔄</span> How It Works</h2>
                <div class="aicpp-flow">
                    <div class="aicpp-flow-item" style="background:#228be6;color:#fff">🌟 Main Character<br><small>Everyone chats here</small></div>
                    <span class="aicpp-flow-arrow">+</span>
                    <div class="aicpp-flow-item" style="background:#40c057;color:#fff">🌍 Public Personas<br><small>All registered users</small></div>
                    <span class="aicpp-flow-arrow">+</span>
                    <div class="aicpp-flow-item" style="background:#fd7e14;color:#fff">🔒 Private Personas<br><small>Assigned clients only</small></div>
                </div>
                <div class="aicpp-info">
                    <strong>📋 Free User Flow:</strong> Registers → Chats with <strong>Main Character</strong> → Sees <strong>Public Personas</strong> in sidebar → Gets 1 gift persona to encourage subscription.
                </div>
                <div class="aicpp-success">
                    <strong>💎 Paid Subscriber Flow:</strong> Logs in → Chats with <strong>Main Character</strong> → Sees <strong>Public + Private Personas</strong> assigned to their account in sidebar.
                </div>
            </div>

            <div class="aicpp-card">
                <h2><span class="aicpp-section-icon">⚡</span> Quick Actions</h2>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aicpp-main-character')); ?>" class="button button-primary" style="background:#228be6;border-color:#228be6">🌟 Main Character</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aicpp-personas')); ?>" class="button button-primary">🎭 Create Persona</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aicpp-binding')); ?>" class="button">🔗 Character Binding</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aicpp-ei')); ?>" class="button">🧠 Emotional Intelligence</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aicpp-rewards')); ?>" class="button">🏆 Rewards</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aicpp-injection')); ?>" class="button">💉 Hidden Injection</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aicpp-settings')); ?>" class="button">⚙️ Settings</a>
                </p>
            </div>

            <div class="aicpp-card">
                <h2><span class="aicpp-section-icon">📌</span> Shortcode</h2>
                <code style="display:block;padding:15px;background:#f1f3f5;border-radius:6px">[ai_chat_persona height="700px" show_history="true" allow_upload="true" allow_voice="true"]</code>
                <p class="description" style="margin-top:10px">The <strong>Main Character</strong> appears as the default chat. Personas appear in the sidebar under "My Personas".</p>
            </div>
        </div>
        <?php
    }

    // ===================== MAIN CHARACTER PAGE =====================
    public function page_main_character() {
        if (!current_user_can('manage_options')) wp_die('Access denied');

        if (isset($_POST['save_main_char']) && check_admin_referer('aicpp_main_char')) {
            update_option('aicpp_main_char_enabled', isset($_POST['enabled']) ? '1' : '0');
            update_option('aicpp_main_char_name', sanitize_text_field(wp_unslash($_POST['name'] ?? 'AI Assistant')));
            update_option('aicpp_main_char_description', sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')));
            update_option('aicpp_main_char_avatar_initials', sanitize_text_field(mb_substr(wp_unslash($_POST['avatar_initials'] ?? 'AI'), 0, 4)));
            update_option('aicpp_main_char_avatar_color', sanitize_hex_color(wp_unslash($_POST['avatar_color'] ?? '#667eea')) ?: '#667eea');
            update_option('aicpp_main_char_system_prompt', mb_substr((string) wp_unslash($_POST['system_prompt'] ?? ''), 0, 200000));
            update_option('aicpp_main_char_model', sanitize_text_field(wp_unslash($_POST['model'] ?? 'gpt-4')));
            update_option('aicpp_main_char_temperature', max(0.0, min(2.0, floatval(wp_unslash($_POST['temperature'] ?? 0.7)))));
            update_option('aicpp_main_char_max_tokens', max(1, min(128000, intval(wp_unslash($_POST['max_tokens'] ?? 2000)))));
            echo '<div class="notice notice-success"><p>✅ Main Character saved successfully!</p></div>';
        }

        $enabled     = get_option('aicpp_main_char_enabled', '0') === '1';
        $name        = get_option('aicpp_main_char_name', 'AI Assistant');
        $description = get_option('aicpp_main_char_description', 'Your helpful AI assistant');
        $initials    = get_option('aicpp_main_char_avatar_initials', 'AI');
        $color       = get_option('aicpp_main_char_avatar_color', '#667eea');
        $prompt      = get_option('aicpp_main_char_system_prompt', 'You are a helpful, friendly AI assistant.');
        $model       = get_option('aicpp_main_char_model', 'gpt-4');
        $temperature = get_option('aicpp_main_char_temperature', '0.7');
        $max_tokens  = get_option('aicpp_main_char_max_tokens', '2000');
        ?>
        <div class="wrap aicpp-wrap">
            <h1>🌟 Main Site Character</h1>

            <div class="aicpp-info">
                <strong>💡 What is this?</strong> The Main Character is your site's default AI assistant — like ChatGPT's main chat. Every registered user can chat with this character directly. It is <strong>separate</strong> from the personas in the sidebar. Think of it as the "home page" assistant.
            </div>

            <form method="post"><?php wp_nonce_field('aicpp_main_char'); ?>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">⚡</span> Status</h2>
                    <p><label><input type="checkbox" name="enabled" <?php checked($enabled); ?>> <strong>Enable Main Site Character</strong></label></p>
                    <p><?php echo $enabled ? '<span class="aicpp-on">✅ ACTIVE</span>' : '<span class="aicpp-off">❌ INACTIVE</span>'; ?></p>
                    <p class="description">When enabled, all users see this character as the default chat experience. When disabled, users must select a persona from the sidebar to start chatting.</p>
                </div>

                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">🎨</span> Character Identity</h2>
                    <table class="form-table">
                        <tr><th>📝 Name</th><td><input type="text" name="name" value="<?php echo esc_attr($name); ?>" class="regular-text" required></td></tr>
                        <tr><th>📄 Description</th><td><textarea name="description" rows="2" class="large-text"><?php echo esc_textarea($description); ?></textarea></td></tr>
                        <tr><th>🖼️ Avatar</th><td>
                            <div style="display:flex;gap:15px;align-items:center">
                                <div>
                                    <label style="font-size:12px">Initials (2-4 chars)</label><br>
                                    <input type="text" name="avatar_initials" value="<?php echo esc_attr($initials); ?>" maxlength="4" style="width:80px" id="main-avatar-initials">
                                </div>
                                <div>
                                    <label style="font-size:12px">Color</label><br>
                                    <input type="color" name="avatar_color" value="<?php echo esc_attr($color); ?>" style="width:60px;height:36px;border:none;cursor:pointer" id="main-avatar-color">
                                </div>
                                <div id="main-avatar-preview" style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;color:#fff;background:<?php echo esc_attr($color); ?>"><?php echo esc_html(mb_strtoupper(mb_substr($initials, 0, 2))); ?></div>
                            </div>
                        </td></tr>
                    </table>
                </div>

                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">🤖</span> System Prompt</h2>
                    <p class="description">This is the personality and instructions for your main character. It defines how it behaves when chatting with any user.</p>
                    <div class="aicpp-code"><textarea name="system_prompt" rows="12"><?php echo esc_textarea($prompt); ?></textarea></div>
                </div>

                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">⚙️</span> Model Settings</h2>
                    <table class="form-table">
                        <tr><th>🧠 Model</th><td>
                            <select name="model">
                                <optgroup label="OpenAI">
                                    <option value="gpt-4o" <?php selected($model, 'gpt-4o'); ?>>GPT-4o</option>
                                    <option value="gpt-4o-mini" <?php selected($model, 'gpt-4o-mini'); ?>>GPT-4o Mini</option>
                                    <option value="gpt-4-turbo" <?php selected($model, 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                                    <option value="gpt-4" <?php selected($model, 'gpt-4'); ?>>GPT-4</option>
                                    <option value="gpt-3.5-turbo" <?php selected($model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                </optgroup>
                                <optgroup label="Anthropic (Claude)">
                                    <option value="claude-sonnet-4-20250514" <?php selected($model, 'claude-sonnet-4-20250514'); ?>>Claude Sonnet 4</option>
                                    <option value="claude-3-5-sonnet-20241022" <?php selected($model, 'claude-3-5-sonnet-20241022'); ?>>Claude 3.5 Sonnet</option>
                                    <option value="claude-3-opus-20240229" <?php selected($model, 'claude-3-opus-20240229'); ?>>Claude 3 Opus</option>
                                </optgroup>
                                <optgroup label="Google (Gemini)">
                                    <option value="gemini-1.5-pro-latest" <?php selected($model, 'gemini-1.5-pro-latest'); ?>>Gemini 1.5 Pro</option>
                                    <option value="gemini-1.5-flash-latest" <?php selected($model, 'gemini-1.5-flash-latest'); ?>>Gemini 1.5 Flash</option>
                                </optgroup>
                                <optgroup label="DeepSeek">
                                    <option value="deepseek-chat" <?php selected($model, 'deepseek-chat'); ?>>DeepSeek Chat</option>
                                    <option value="deepseek-reasoner" <?php selected($model, 'deepseek-reasoner'); ?>>DeepSeek Reasoner</option>
                                </optgroup>
                                <optgroup label="Mistral AI">
                                    <option value="mistral-large-latest" <?php selected($model, 'mistral-large-latest'); ?>>Mistral Large</option>
                                    <option value="mistral-small-latest" <?php selected($model, 'mistral-small-latest'); ?>>Mistral Small</option>
                                </optgroup>
                                <optgroup label="Groq">
                                    <option value="llama-3.1-405b-reasoning" <?php selected($model, 'llama-3.1-405b-reasoning'); ?>>Llama 3.1 405B</option>
                                    <option value="llama-3.1-70b-versatile" <?php selected($model, 'llama-3.1-70b-versatile'); ?>>Llama 3.1 70B</option>
                                </optgroup>
                                <optgroup label="OpenRouter (free)">
                                    <?php
                                    $cached2 = get_option('aicpp_or_free_models_cache', '');
                                    $free2 = $cached2 ? json_decode($cached2, true) : null;
                                    if (!is_array($free2) || empty($free2)) $free2 = $this->aicpp_or_default_free_models();
                                    foreach ($free2 as $val => $label) {
                                        echo '<option value="' . esc_attr($val) . '"' . selected($model, $val, false) . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </optgroup>
                            </select>
                        </td></tr>
                        <tr><th>🌡️ Temperature</th><td><input type="number" name="temperature" value="<?php echo esc_attr($temperature); ?>" min="0" max="2" step="0.1" style="width:100px"></td></tr>
                        <tr><th>📊 Max Tokens</th><td><input type="number" name="max_tokens" value="<?php echo esc_attr($max_tokens); ?>" min="1" max="128000" style="width:150px"></td></tr>
                    </table>
                </div>

                <p><input type="submit" name="save_main_char" class="button button-primary button-large" value="💾 Save Main Character"></p>
            </form>
        </div>
        <script>
        (function(){
            var initials = document.getElementById('main-avatar-initials');
            var color = document.getElementById('main-avatar-color');
            var preview = document.getElementById('main-avatar-preview');
            function update() {
                preview.textContent = (initials.value || 'AI').substring(0,2).toUpperCase();
                preview.style.background = color.value || '#667eea';
            }
            initials.addEventListener('input', update);
            color.addEventListener('input', update);
        })();
        </script>
        <?php
    }

    // ===================== PERSONAS PAGE =====================
    public function page_personas() {
        if (!current_user_can('manage_options')) wp_die('Access denied');
        global $wpdb;
        $list = $wpdb->get_results("SELECT p.*, (SELECT COUNT(*) FROM {$this->table_persona_assignments} pa WHERE pa.persona_id = p.id) as client_count FROM {$this->table_personas} p ORDER BY p.id DESC");
        ?>
        <div class="wrap aicpp-wrap">
            <h1>🎭 Character Personas</h1>

            <div class="aicpp-info">
                <strong>📋 Visibility System:</strong><br>
                🌍 <strong>Public</strong> = Visible to ALL registered users in the sidebar Persona section.<br>
                🔒 <strong>Private</strong> = Only visible to specifically assigned clients (linked to their username). Use the "Assign Clients" button for private personas.
            </div>

            <div class="aicpp-card">
                <button class="button button-primary button-large" onclick="aicppOpenModal()">➕ Create New Persona</button>
            </div>

            <div class="aicpp-personas">
                <?php foreach ($list as $p): ?>
                <div class="aicpp-pcard">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                        <h3 style="margin:0"><?php echo esc_html($p->name); ?></h3>
                        <div>
                            <?php if ($p->visibility === 'public'): ?>
                                <span class="aicpp-badge-blue">🌍 Public</span>
                            <?php else: ?>
                                <span class="aicpp-badge-orange">🔒 Private</span>
                                <span class="aicpp-badge-green"><?php echo (int)$p->client_count; ?> client<?php echo $p->client_count != 1 ? 's' : ''; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p style="color:#666;font-size:14px"><?php echo esc_html($p->description); ?></p>
                    <p style="font-size:13px;color:#888">
                        <strong>Model:</strong> <?php echo esc_html($p->model); ?> |
                        <strong>Temp:</strong> <?php echo esc_html($p->temperature); ?>
                    </p>
                    <?php if ($p->visibility === 'private'):
                        $assigned = $wpdb->get_results($wpdb->prepare(
                            "SELECT u.display_name, u.user_login FROM {$this->table_persona_assignments} pa
                             JOIN {$wpdb->users} u ON pa.user_id = u.ID
                             WHERE pa.persona_id = %d LIMIT 5", $p->id
                        ));
                        if ($assigned): ?>
                        <div style="margin-top:10px">
                            <strong style="font-size:12px;color:#495057">👤 Assigned to:</strong>
                            <div class="aicpp-persona-tags" style="margin-top:5px">
                                <?php foreach ($assigned as $a): ?>
                                    <span class="aicpp-badge"><?php echo esc_html($a->display_name ?: $a->user_login); ?></span>
                                <?php endforeach; ?>
                                <?php if ($p->client_count > 5): ?>
                                    <span class="aicpp-badge-orange">+<?php echo ($p->client_count - 5); ?> more</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif;
                    endif; ?>
                    <div style="margin-top:15px;display:flex;gap:8px;flex-wrap:wrap">
                        <button class="button" onclick="aicppEditPersona(<?php echo (int)$p->id; ?>)">✏️ Edit</button>
                        <?php if ($p->visibility === 'private'): ?>
                            <button class="button" onclick="aicppManageAssignments(<?php echo (int)$p->id; ?>, '<?php echo esc_js($p->name); ?>')" style="color:#228be6">👤 Assign Clients</button>
                        <?php endif; ?>
                        <button class="button" onclick="aicppDeletePersona(<?php echo (int)$p->id; ?>)" style="color:#c92a2a">🗑️ Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Create/Edit Persona Modal -->
            <div id="pmodal" class="aicpp-modal">
                <div class="aicpp-mbox">
                    <span class="aicpp-close" onclick="aicppCloseModal()">&times;</span>
                    <h2 id="mtitle">🎭 Create Persona</h2>
                    <form id="pform">
                        <input type="hidden" name="pid" id="pid">
                        <table class="form-table">
                            <tr><th>📝 Name *</th><td><input type="text" id="pname" class="regular-text" required></td></tr>
                            <tr><th>📄 Description</th><td><textarea id="pdesc" rows="2" class="large-text"></textarea></td></tr>
                            <tr><th>🖼️ Avatar</th><td>
                                <div style="display:flex;gap:15px;align-items:center">
                                    <div><label style="font-size:12px">Initials</label><br><input type="text" id="pavatar_initials" maxlength="4" style="width:80px" placeholder="DM"></div>
                                    <div><label style="font-size:12px">Color</label><br><input type="color" id="pavatar_color" value="#667eea" style="width:60px;height:36px;border:none;cursor:pointer"></div>
                                    <div id="pavatar_preview" style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;color:#fff;background:#667eea">DM</div>
                                </div>
                            </td></tr>
                            <tr><th>🤖 System Prompt *</th><td><textarea id="pprompt" rows="5" class="large-text code" required></textarea></td></tr>
                            <tr><th>🧠 EI Code</th><td><div class="aicpp-code"><textarea id="pei" rows="4"></textarea></div></td></tr>
                            <tr><th>🏆 Rewards Code</th><td><div class="aicpp-code"><textarea id="prewards" rows="4"></textarea></div></td></tr>
                            <tr><th>🔧 Global Overrides</th><td>
                                <label><input type="checkbox" id="puse_global_ei"> Use Global EI (when persona EI is empty)</label><br>
                                <label><input type="checkbox" id="puse_global_rewards"> Use Global Rewards (when persona rewards is empty)</label>
                            </td></tr>
                            <tr><th>👁️ Visibility *</th><td>
                                <select id="pvisibility" onchange="aicppToggleVisibility()">
                                    <option value="public">🌍 Public — All registered users can see this persona</option>
                                    <option value="private">🔒 Private — Only assigned clients can see this persona</option>
                                </select>
                                <p class="description" id="visibility-hint-public">This persona will appear for every registered user in their sidebar Persona section.</p>
                                <p class="description" id="visibility-hint-private" style="display:none">This persona will only appear for clients you specifically assign below. Link it to their username/account.</p>
                            </td></tr>
                            <tr><th>🧠 Model</th><td>
                                <select id="pmodel">
                                    <optgroup label="OpenAI">
                                        <option value="gpt-4o">GPT-4o</option>
                                        <option value="gpt-4o-mini">GPT-4o Mini</option>
                                        <option value="gpt-4-turbo">GPT-4 Turbo</option>
                                        <option value="gpt-4">GPT-4</option>
                                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                                        <option value="o1-preview">O1 Preview</option>
                                        <option value="o1-mini">O1 Mini</option>
                                    </optgroup>
                                    <optgroup label="Anthropic (Claude)">
                                        <option value="claude-sonnet-4-20250514">Claude Sonnet 4</option>
                                        <option value="claude-3-5-sonnet-20241022">Claude 3.5 Sonnet</option>
                                        <option value="claude-3-opus-20240229">Claude 3 Opus</option>
                                        <option value="claude-3-haiku-20240307">Claude 3 Haiku</option>
                                    </optgroup>
                                    <optgroup label="Google (Gemini)">
                                        <option value="gemini-1.5-pro-latest">Gemini 1.5 Pro</option>
                                        <option value="gemini-1.5-flash-latest">Gemini 1.5 Flash</option>
                                        <option value="gemini-pro">Gemini Pro</option>
                                    </optgroup>
                                    <optgroup label="DeepSeek">
                                        <option value="deepseek-chat">DeepSeek Chat</option>
                                        <option value="deepseek-coder">DeepSeek Coder</option>
                                        <option value="deepseek-reasoner">DeepSeek Reasoner</option>
                                    </optgroup>
                                    <optgroup label="Mistral AI">
                                        <option value="mistral-large-latest">Mistral Large</option>
                                        <option value="mistral-small-latest">Mistral Small</option>
                                        <option value="open-mixtral-8x22b">Mixtral 8x22B</option>
                                    </optgroup>
                                    <optgroup label="Groq">
                                        <option value="llama-3.1-405b-reasoning">Llama 3.1 405B</option>
                                        <option value="llama-3.1-70b-versatile">Llama 3.1 70B</option>
                                        <option value="llama-3.1-8b-instant">Llama 3.1 8B</option>
                                        <option value="gemma2-9b-it">Gemma 2 9B</option>
                                    </optgroup>
                                    <optgroup label="OpenRouter (paid)">
                                        <option value="openrouter/auto">Auto (Best)</option>
                                        <option value="openrouter/anthropic/claude-3.5-sonnet">Claude 3.5 Sonnet (OR)</option>
                                        <option value="openrouter/openai/gpt-4o">GPT-4o (OR)</option>
                                    </optgroup>
                                    <optgroup label="OpenRouter (free)" id="or-free-optgroup">
                                        <?php
                                        $cached = get_option('aicpp_or_free_models_cache', '');
                                        $free = $cached ? json_decode($cached, true) : null;
                                        if (!is_array($free) || empty($free)) $free = $this->aicpp_or_default_free_models();
                                        foreach ($free as $val => $label) {
                                            echo '<option value="' . esc_attr($val) . '">' . esc_html($label) . '</option>';
                                        }
                                        ?>
                                    </optgroup>
                                </select>
                            </td></tr>
                            <tr><th>🌡️ Temperature</th><td><input type="number" id="ptemp" value="0.7" min="0" max="2" step="0.1"></td></tr>
                            <tr><th>📊 Max Tokens</th><td><input type="number" id="ptokens" value="2000" min="1" max="128000"></td></tr>
                        </table>

                        <!-- Inline Client Assignment (only for private personas) -->
                        <div class="aicpp-assign-section" id="assign-section" style="display:none">
                            <h3>👤 Assign to Clients</h3>
                            <p class="description">Search for registered users by name, email, or username and link this persona to their account.</p>
                            <div class="aicpp-user-search">
                                <input type="text" id="user-search-input" placeholder="Search by username, email, or display name...">
                                <button type="button" onclick="aicppSearchUsers()">🔍 Search</button>
                            </div>
                            <div id="user-search-results" class="aicpp-user-results" style="display:none"></div>
                            <div class="aicpp-assigned-list">
                                <h4>✅ Currently Assigned Clients</h4>
                                <div id="assigned-users-list"><div class="aicpp-empty">No clients assigned yet</div></div>
                            </div>
                        </div>

                        <p style="margin-top:20px">
                            <button type="submit" class="button button-primary button-large">💾 Save Persona</button>
                            <button type="button" class="button" onclick="aicppCloseModal()">Cancel</button>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Quick Assignment Modal -->
            <div id="assign-modal" class="aicpp-modal">
                <div class="aicpp-mbox" style="max-width:600px">
                    <span class="aicpp-close" onclick="aicppCloseAssignModal()">&times;</span>
                    <h2 id="assign-modal-title">👤 Assign Clients</h2>
                    <div class="aicpp-user-search">
                        <input type="text" id="quick-search-input" placeholder="Search by username, email, or display name...">
                        <button type="button" onclick="aicppQuickSearch()">🔍 Search</button>
                    </div>
                    <div id="quick-search-results" class="aicpp-user-results" style="display:none"></div>
                    <div class="aicpp-assigned-list">
                        <h4>✅ Assigned Clients</h4>
                        <div id="quick-assigned-list"><div class="aicpp-empty">Loading...</div></div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function(){
            var ajaxurl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            var nonce = <?php echo wp_json_encode(wp_create_nonce('aicpp')); ?>;
            var currentPersonaId = null;
            var quickPersonaId = null;

            function updateAvatarPreview() {
                var initials = document.getElementById('pavatar_initials').value || 'AI';
                var color = document.getElementById('pavatar_color').value || '#667eea';
                var preview = document.getElementById('pavatar_preview');
                preview.textContent = initials.substring(0, 2).toUpperCase();
                preview.style.background = color;
            }
            document.getElementById('pavatar_initials').addEventListener('input', updateAvatarPreview);
            document.getElementById('pavatar_color').addEventListener('input', updateAvatarPreview);

            window.aicppToggleVisibility = function() {
                var vis = document.getElementById('pvisibility').value;
                var pid = document.getElementById('pid').value;
                document.getElementById('visibility-hint-public').style.display = vis === 'public' ? '' : 'none';
                document.getElementById('visibility-hint-private').style.display = vis === 'private' ? '' : 'none';
                if (vis === 'private' && pid) {
                    document.getElementById('assign-section').style.display = 'block';
                } else if (vis === 'private' && !pid) {
                    document.getElementById('assign-section').style.display = 'block';
                    document.getElementById('assigned-users-list').innerHTML = '<div class="aicpp-empty">Save the persona first, then assign clients</div>';
                } else {
                    document.getElementById('assign-section').style.display = 'none';
                }
            };

            window.aicppOpenModal = function(){
                document.getElementById('pid').value = '';
                document.getElementById('pform').reset();
                document.getElementById('mtitle').textContent = '🎭 Create Persona';
                document.getElementById('pvisibility').value = 'public';
                document.getElementById('assign-section').style.display = 'none';
                document.getElementById('user-search-results').style.display = 'none';
                document.getElementById('visibility-hint-public').style.display = '';
                document.getElementById('visibility-hint-private').style.display = 'none';
                updateAvatarPreview();
                document.getElementById('pmodal').style.display = 'flex';
            };

            window.aicppCloseModal = function(){ document.getElementById('pmodal').style.display = 'none'; };

            window.aicppEditPersona = function(id){
                document.getElementById('mtitle').textContent = '✏️ Edit Persona';
                currentPersonaId = id;
                var fd = new FormData();
                fd.append('action', 'aicpp_get_persona');
                fd.append('nonce', nonce);
                fd.append('persona_id', id);
                fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){return r.json()}).then(function(d){
                    if (d.success) {
                        var p = d.data;
                        document.getElementById('pid').value = p.id;
                        document.getElementById('pname').value = p.name;
                        document.getElementById('pdesc').value = p.description || '';
                        document.getElementById('pavatar_initials').value = p.avatar_initials || '';
                        document.getElementById('pavatar_color').value = p.avatar_color || '#667eea';
                        document.getElementById('pprompt').value = p.system_prompt;
                        document.getElementById('pei').value = p.emotional_intelligence_code || '';
                        document.getElementById('prewards').value = p.rewards_code || '';
                        document.getElementById('puse_global_ei').checked = (p.use_global_ei == 1);
                        document.getElementById('puse_global_rewards').checked = (p.use_global_rewards == 1);
                        document.getElementById('pvisibility').value = p.visibility || 'private';
                        document.getElementById('pmodel').value = p.model;
                        document.getElementById('ptemp').value = p.temperature;
                        document.getElementById('ptokens').value = p.max_tokens;
                        aicppToggleVisibility();
                        updateAvatarPreview();
                        if (p.visibility === 'private') loadAssignedUsers(id);
                        document.getElementById('pmodal').style.display = 'flex';
                    }
                });
            };

            window.aicppDeletePersona = function(id){
                if (!confirm('Delete this persona and all its client assignments?')) return;
                var fd = new FormData();
                fd.append('action', 'aicpp_delete_persona');
                fd.append('nonce', nonce);
                fd.append('persona_id', id);
                fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){return r.json()}).then(function(d){
                    if (d.success) location.reload();
                    else alert(d.data.message);
                });
            };

            window.aicppSearchUsers = function(){
                var query = document.getElementById('user-search-input').value.trim();
                if (query.length < 2) { alert('Enter at least 2 characters'); return; }
                var fd = new FormData();
                fd.append('action', 'aicpp_search_users');
                fd.append('nonce', nonce);
                fd.append('query', query);
                fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){return r.json()}).then(function(d){
                    var container = document.getElementById('user-search-results');
                    container.style.display = 'block';
                    if (d.success && d.data.users.length > 0) {
                        container.innerHTML = d.data.users.map(function(u){
                            return '<div class="aicpp-user-row"><div class="user-info"><span class="user-name">' + escapeHtml(u.display_name) + '</span><span class="user-email">' + escapeHtml(u.user_email) + '</span><span class="user-login">@' + escapeHtml(u.user_login) + '</span></div><button type="button" class="btn-assign" onclick="aicppAssignUser(' + u.ID + ')">➕ Assign</button></div>';
                        }).join('');
                    } else {
                        container.innerHTML = '<div class="aicpp-empty">No users found</div>';
                    }
                });
            };

            window.aicppAssignUser = function(userId){
                var personaId = document.getElementById('pid').value;
                if (!personaId) { alert('Please save the persona first.'); return; }
                var fd = new FormData();
                fd.append('action', 'aicpp_assign_persona');
                fd.append('nonce', nonce);
                fd.append('persona_id', personaId);
                fd.append('user_id', userId);
                fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){return r.json()}).then(function(d){
                    if (d.success) {
                        loadAssignedUsers(personaId);
                        document.getElementById('user-search-results').style.display = 'none';
                        document.getElementById('user-search-input').value = '';
                    } else {
                        alert(d.data.message || 'Error');
                    }
                });
            };

            function loadAssignedUsers(personaId) {
                var fd = new FormData();
                fd.append('action', 'aicpp_get_persona_users');
                fd.append('nonce', nonce);
                fd.append('persona_id', personaId);
                fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){return r.json()}).then(function(d){
                    var container = document.getElementById('assigned-users-list');
                    if (d.success && d.data.users.length > 0) {
                        container.innerHTML = '<div class="aicpp-user-results">' + d.data.users.map(function(u){
                            return '<div class="aicpp-user-row"><div class="user-info"><span class="user-name">' + escapeHtml(u.display_name) + '</span><span class="user-email">' + escapeHtml(u.user_email) + '</span><span class="user-login">@' + escapeHtml(u.user_login) + '</span></div><button type="button" class="btn-remove" onclick="aicppUnassignUser(' + personaId + ', ' + u.ID + ')">❌ Remove</button></div>';
                        }).join('') + '</div>';
                    } else {
                        container.innerHTML = '<div class="aicpp-empty">No clients assigned yet</div>';
                    }
                });
            }

            window.aicppUnassignUser = function(personaId, userId){
                if (!confirm('Remove this client from this persona?')) return;
                var fd = new FormData();
                fd.append('action', 'aicpp_unassign_persona');
                fd.append('nonce', nonce);
                fd.append('persona_id', personaId);
                fd.append('user_id', userId);
                fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){return r.json()}).then(function(d){
                    if (d.success) {
                        var pid = document.getElementById('pid').value;
                        if (pid) loadAssignedUsers(pid);
                        if (quickPersonaId) loadQuickAssigned(quickPersonaId);
                    }
                });
            };

            window.aicppManageAssignments = function(id, name){
                quickPersonaId = id;
                document.getElementById('assign-modal-title').textContent = '👤 Assign Clients to: ' + name;
                document.getElementById('quick-search-results').style.display = 'none';
                document.getElementById('quick-search-input').value = '';
                loadQuickAssigned(id);
                document.getElementById('assign-modal').style.display = 'flex';
            };
            window.aicppCloseAssignModal = function(){ document.getElementById('assign-modal').style.display = 'none'; quickPersonaId = null; };

            window.aicppQuickSearch = function(){
                var query = document.getElementById('quick-search-input').value.trim();
                if (query.length < 2) { alert('Enter at least 2 characters'); return; }
                var fd = new FormData();
                fd.append('action', 'aicpp_search_users');
                fd.append('nonce', nonce);
                fd.append('query', query);
                fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){return r.json()}).then(function(d){
                    var container = document.getElementById('quick-search-results');
                    container.style.display = 'block';
                    if (d.success && d.data.users.length > 0) {
                        container.innerHTML = d.data.users.map(function(u){
                            return '<div class="aicpp-user-row"><div class="user-info"><span class="user-name">' + escapeHtml(u.display_name) + '</span><span class="user-email">' + escapeHtml(u.user_email) + '</span><span class="user-login">@' + escapeHtml(u.user_login) + '</span></div><button type="button" class="btn-assign" onclick="aicppQuickAssign(' + u.ID + ')">➕ Assign</button></div>';
                        }).join('');
                    } else {
                        container.innerHTML = '<div class="aicpp-empty">No users found</div>';
                    }
                });
            };

            window.aicppQuickAssign = function(userId){
                if (!quickPersonaId) return;
                var fd = new FormData();
                fd.append('action', 'aicpp_assign_persona');
                fd.append('nonce', nonce);
                fd.append('persona_id', quickPersonaId);
                fd.append('user_id', userId);
                fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){return r.json()}).then(function(d){
                    if (d.success) {
                        loadQuickAssigned(quickPersonaId);
                        document.getElementById('quick-search-results').style.display = 'none';
                        document.getElementById('quick-search-input').value = '';
                    } else {
                        alert(d.data.message || 'Error');
                    }
                });
            };

            function loadQuickAssigned(personaId) {
                var fd = new FormData();
                fd.append('action', 'aicpp_get_persona_users');
                fd.append('nonce', nonce);
                fd.append('persona_id', personaId);
                fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){return r.json()}).then(function(d){
                    var container = document.getElementById('quick-assigned-list');
                    if (d.success && d.data.users.length > 0) {
                        container.innerHTML = '<div class="aicpp-user-results">' + d.data.users.map(function(u){
                            return '<div class="aicpp-user-row"><div class="user-info"><span class="user-name">' + escapeHtml(u.display_name) + '</span><span class="user-email">' + escapeHtml(u.user_email) + '</span><span class="user-login">@' + escapeHtml(u.user_login) + '</span></div><button type="button" class="btn-remove" onclick="aicppUnassignUser(' + personaId + ', ' + u.ID + ')">❌ Remove</button></div>';
                        }).join('') + '</div>';
                    } else {
                        container.innerHTML = '<div class="aicpp-empty">No clients assigned</div>';
                    }
                });
            }

            document.getElementById('pform').onsubmit = function(e){
                e.preventDefault();
                var fd = new FormData();
                fd.append('action', 'aicpp_save_persona');
                fd.append('nonce', nonce);
                fd.append('persona_id', document.getElementById('pid').value);
                fd.append('name', document.getElementById('pname').value);
                fd.append('description', document.getElementById('pdesc').value);
                fd.append('avatar_initials', document.getElementById('pavatar_initials').value);
                fd.append('avatar_color', document.getElementById('pavatar_color').value);
                fd.append('system_prompt', document.getElementById('pprompt').value);
                fd.append('emotional_intelligence_code', document.getElementById('pei').value);
                fd.append('rewards_code', document.getElementById('prewards').value);
                fd.append('use_global_ei', document.getElementById('puse_global_ei').checked ? '1' : '0');
                fd.append('use_global_rewards', document.getElementById('puse_global_rewards').checked ? '1' : '0');
                fd.append('visibility', document.getElementById('pvisibility').value);
                fd.append('model', document.getElementById('pmodel').value);
                fd.append('temperature', document.getElementById('ptemp').value);
                fd.append('max_tokens', document.getElementById('ptokens').value);
                fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){return r.json()}).then(function(d){
                    if (d.success) {
                        if (!document.getElementById('pid').value && d.data.persona_id) {
                            document.getElementById('pid').value = d.data.persona_id;
                            aicppToggleVisibility();
                            alert('Persona saved! You can now assign clients if this is a private persona.');
                        } else {
                            alert('Saved!');
                            location.reload();
                        }
                    } else {
                        alert(d.data.message || 'Error');
                    }
                });
            };

            document.getElementById('pmodal').onclick = function(e){ if (e.target === this) aicppCloseModal(); };
            document.getElementById('assign-modal').onclick = function(e){ if (e.target === this) aicppCloseAssignModal(); };
            document.getElementById('user-search-input').addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); aicppSearchUsers(); }});
            document.getElementById('quick-search-input').addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); aicppQuickSearch(); }});

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }
        })();
        </script>
        <?php
    }

    // ===================== CHARACTER BINDING =====================
    public function page_binding() {
        if (!current_user_can('manage_options')) wp_die('Access denied');
        if (isset($_POST['save_binding']) && check_admin_referer('aicpp_binding')) {
            update_option('aicpp_active_character_code', mb_substr((string) wp_unslash($_POST['code'] ?? ''), 0, 500000));
            update_option('aicpp_character_binding_active', isset($_POST['active']) ? '1' : '0');
            echo '<div class="notice notice-success"><p>✅ Saved!</p></div>';
        }
        $code   = get_option('aicpp_active_character_code', '');
        $active = get_option('aicpp_character_binding_active', '0') === '1';
        ?>
        <div class="wrap aicpp-wrap">
            <h1>🔗 Character Binding System</h1>
            <div class="aicpp-info"><strong>💡 How it works:</strong> Paste character code below to override ALL personas with a single character personality.</div>
            <form method="post"><?php wp_nonce_field('aicpp_binding'); ?>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">⚡</span> Status</h2>
                    <p><label><input type="checkbox" name="active" <?php checked($active); ?>> <strong>Enable Character Binding</strong></label></p>
                    <p><?php echo $active ? '<span class="aicpp-on">✅ ACTIVE</span>' : '<span class="aicpp-off">❌ INACTIVE</span>'; ?></p>
                </div>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">📝</span> Character Code</h2>
                    <div class="aicpp-code"><textarea name="code" rows="20" placeholder="Paste character code here..."><?php echo esc_textarea($code); ?></textarea></div>
                </div>
                <p><input type="submit" name="save_binding" class="button button-primary button-large" value="💾 Save"></p>
            </form>
        </div>
        <?php
    }

    // ===================== EMOTIONAL INTELLIGENCE =====================
    public function page_ei() {
        if (!current_user_can('manage_options')) wp_die('Access denied');
        if (isset($_POST['save_ei']) && check_admin_referer('aicpp_ei')) {
            update_option('aicpp_global_ei_code', mb_substr((string) wp_unslash($_POST['code'] ?? ''), 0, 500000));
            update_option('aicpp_global_ei_enabled', isset($_POST['enabled']) ? '1' : '0');
            echo '<div class="notice notice-success"><p>✅ Saved!</p></div>';
        }
        $code    = get_option('aicpp_global_ei_code', '');
        $enabled = get_option('aicpp_global_ei_enabled', '0') === '1';
        ?>
        <div class="wrap aicpp-wrap">
            <h1>🧠 Emotional Intelligence System</h1>
            <form method="post"><?php wp_nonce_field('aicpp_ei'); ?>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">⚡</span> Global EI Settings</h2>
                    <p><label><input type="checkbox" name="enabled" <?php checked($enabled); ?>> <strong>Enable Global Emotional Intelligence</strong></label></p>
                    <p><?php echo $enabled ? '<span class="aicpp-on">✅ ACTIVE</span>' : '<span class="aicpp-off">❌ INACTIVE</span>'; ?></p>
                </div>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">📝</span> EI Code</h2>
                    <div class="aicpp-code"><textarea name="code" rows="18"><?php echo esc_textarea($code); ?></textarea></div>
                </div>
                <p><input type="submit" name="save_ei" class="button button-primary button-large" value="💾 Save"></p>
            </form>
        </div>
        <?php
    }

    // ===================== REWARDS SYSTEM =====================
    public function page_rewards() {
        if (!current_user_can('manage_options')) wp_die('Access denied');
        if (isset($_POST['save_rewards']) && check_admin_referer('aicpp_rewards')) {
            update_option('aicpp_global_rewards_code', mb_substr((string) wp_unslash($_POST['code'] ?? ''), 0, 500000));
            update_option('aicpp_global_rewards_enabled', isset($_POST['enabled']) ? '1' : '0');
            echo '<div class="notice notice-success"><p>✅ Saved!</p></div>';
        }
        $code    = get_option('aicpp_global_rewards_code', '');
        $enabled = get_option('aicpp_global_rewards_enabled', '0') === '1';
        ?>
        <div class="wrap aicpp-wrap">
            <h1>🏆 Rewards System</h1>
            <form method="post"><?php wp_nonce_field('aicpp_rewards'); ?>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">⚡</span> Global Rewards Settings</h2>
                    <p><label><input type="checkbox" name="enabled" <?php checked($enabled); ?>> <strong>Enable Global Rewards System</strong></label></p>
                    <p><?php echo $enabled ? '<span class="aicpp-on">✅ ACTIVE</span>' : '<span class="aicpp-off">❌ INACTIVE</span>'; ?></p>
                </div>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">📝</span> Rewards Code</h2>
                    <div class="aicpp-code"><textarea name="code" rows="18"><?php echo esc_textarea($code); ?></textarea></div>
                </div>
                <p><input type="submit" name="save_rewards" class="button button-primary button-large" value="💾 Save"></p>
            </form>
        </div>
        <?php
    }

    // ===================== HIDDEN INJECTION (BUG #1 FIXED) =====================
    public function page_injection() {
        if (!current_user_can('manage_options')) wp_die('Access denied');
        if (isset($_POST['save_inj']) && check_admin_referer('aicpp_inj')) {
            update_option('aicpp_injection_enabled', isset($_POST['enabled']) ? '1' : '0');
            for ($i = 1; $i <= 5; $i++) {
                update_option("aicpp_slot{$i}_enabled", isset($_POST["s{$i}"]) ? '1' : '0');
                update_option("aicpp_hidden_message_{$i}", mb_substr((string) wp_unslash($_POST["msg{$i}"] ?? ''), 0, 200000));
            }
            echo '<div class="notice notice-success"><p>✅ All 5 slots saved!</p></div>';
        }
        $enabled = get_option('aicpp_injection_enabled', '0') === '1';
        $slots = []; $messages = [];
        for ($i = 1; $i <= 5; $i++) {
            $slots[$i]    = get_option("aicpp_slot{$i}_enabled", '1') === '1';
            $messages[$i] = get_option("aicpp_hidden_message_{$i}", '');
        }

        // FIX: Added missing $slot_config definition and proper PHP/HTML transition
        $slot_config = [
            1 => ['name' => 'Pattern Recognition', 'emoji' => '🔍', 'color' => '#228be6', 'desc' => 'Analyzes customer behavior patterns and adapts responses accordingly.'],
            2 => ['name' => 'Emotional Mapping',   'emoji' => '💚', 'color' => '#40c057', 'desc' => 'Maps emotional state and adjusts tone and empathy levels.'],
            3 => ['name' => 'Predictive Engine',    'emoji' => '⚡', 'color' => '#fab005', 'desc' => 'Anticipates customer needs before they express them.'],
            4 => ['name' => 'Conversion Catalyst',  'emoji' => '🔥', 'color' => '#fd7e14', 'desc' => 'Guides conversations toward desired outcomes naturally.'],
            5 => ['name' => 'Loyalty Architect',    'emoji' => '💎', 'color' => '#e64980', 'desc' => 'Builds long-term customer loyalty through personalized engagement.'],
        ];
        ?>
        <div class="wrap aicpp-wrap">
            <h1>💉 5-Slot Hidden Injection — Living Prediction Network</h1>

            <div class="aicpp-info">
                <strong>🧠 Living Prediction Network Architecture:</strong>
                Each slot is a specialized cognitive function. Together they form a predictive mind that anticipates customer questions.
            </div>

            <div class="aicpp-flow" style="background:#f8f9fa;border-radius:10px;padding:15px;margin:20px 0">
                <?php foreach ($slot_config as $num => $cfg): ?>
                    <div class="aicpp-flow-item" style="background:<?php echo esc_attr($cfg['color']); ?>;color:#fff"><?php echo $cfg['emoji']; ?> <?php echo esc_html($cfg['name']); ?></div>
                    <?php if ($num < 5): ?><span class="aicpp-flow-arrow">&rarr;</span><?php endif; ?>
                <?php endforeach; ?>
            </div>

            <form method="post"><?php wp_nonce_field('aicpp_inj'); ?>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">🎮</span> Master Control</h2>
                    <p><label><input type="checkbox" name="enabled" <?php checked($enabled); ?>> <strong>Enable 5-Slot Living Prediction Network</strong></label></p>
                    <p><?php echo $enabled ? '<span class="aicpp-on">✅ SYSTEM ACTIVE</span>' : '<span class="aicpp-off">❌ SYSTEM INACTIVE</span>'; ?></p>
                </div>
                <div class="aicpp-grid-5">
                    <?php foreach ($slot_config as $num => $cfg): ?>
                    <div class="aicpp-slot s<?php echo $num; ?>">
                        <h3 style="color:<?php echo esc_attr($cfg['color']); ?>"><?php echo $cfg['emoji']; ?> Slot <?php echo $num; ?></h3>
                        <p style="font-weight:600;font-size:13px;margin:0 0 5px"><?php echo esc_html($cfg['name']); ?></p>
                        <p style="font-size:11px;color:#888;margin:0 0 10px"><?php echo esc_html($cfg['desc']); ?></p>
                        <p><label style="font-size:12px"><input type="checkbox" name="s<?php echo $num; ?>" <?php checked($slots[$num]); ?>> ✅ Enable</label></p>
                        <textarea name="msg<?php echo $num; ?>" placeholder="Paste <?php echo esc_attr($cfg['name']); ?> protocol code here..."><?php echo esc_textarea($messages[$num]); ?></textarea>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="aicpp-card" style="margin-top:20px">
                    <h2><span class="aicpp-section-icon">🏷️</span> Available Tags</h2>
                    <p>
                        <code>{user_name}</code> — Customer's name or 'Friend'<br>
                        <code>{time_of_day}</code> — morning, afternoon, evening, night<br>
                        <code>{date}</code> — Today's date<br>
                        <code>{slot_number}</code> — Current slot number (1-5)<br>
                        <code>{question_number}</code> — Customer's question count
                    </p>
                    <p><input type="submit" name="save_inj" class="button button-primary button-large" value="💾 Save All Slots"></p>
                </div>
            </form>
        </div>
        <?php
    }

    // ===================== SETTINGS =====================
    public function page_settings() {
        if (!current_user_can('manage_options')) wp_die('Access denied');
        if (isset($_POST['save_set']) && check_admin_referer('aicpp_set')) {
            // FIX #2: provider whitelist + wp_unslash on all $_POST reads
            $allowed_providers = ['openai','anthropic','google','deepseek','openrouter','mistral','groq'];
            $incoming_provider = sanitize_text_field(wp_unslash($_POST['provider'] ?? 'openai'));
            if (!in_array($incoming_provider, $allowed_providers, true)) $incoming_provider = 'openai';
            update_option('aicpp_api_provider', $incoming_provider);
            update_option('aicpp_max_message_length', max(100, min(100000, intval(wp_unslash($_POST['max_message_length'] ?? 10000)))));
            update_option('aicpp_require_login', !empty($_POST['require_login']) ? '1' : '0');
            update_option('aicpp_login_message', sanitize_textarea_field(wp_unslash($_POST['login_message'] ?? '')));
            $provider_keys = ['openai', 'anthropic', 'google', 'deepseek', 'openrouter', 'mistral', 'groq'];
            foreach ($provider_keys as $pk) {
                $raw = sanitize_text_field(wp_unslash($_POST["{$pk}_key"] ?? ''));
                if (!empty($raw) && substr($raw, 0, 4) !== '****') {
                    update_option("aicpp_{$pk}_api_key", $this->encrypt_api_key($raw));
                } elseif (empty($raw)) {
                    update_option("aicpp_{$pk}_api_key", '');
                }
            }
            echo '<div class="notice notice-success"><p>✅ Settings saved!</p></div>';
        }
        $provider = get_option('aicpp_api_provider', 'openai');
        $max_len  = get_option('aicpp_max_message_length', '10000');
        $require_login = get_option('aicpp_require_login', '1') === '1';
        $login_message = get_option('aicpp_login_message', 'Please sign in to access your AI assistant.');

        $keys = [];
        $provider_list = ['openai', 'anthropic', 'google', 'deepseek', 'openrouter', 'mistral', 'groq'];
        foreach ($provider_list as $pk) {
            $decrypted = $this->get_api_key($pk);
            $keys[$pk] = !empty($decrypted) ? '****' . substr($decrypted, -4) : '';
        }

        $providers = [
            'openai'     => ['name'=>'OpenAI','icon'=>'🟢','url'=>'https://platform.openai.com/api-keys','placeholder'=>'sk-...'],
            'anthropic'  => ['name'=>'Anthropic (Claude)','icon'=>'🟠','url'=>'https://console.anthropic.com/settings/keys','placeholder'=>'sk-ant-...'],
            'google'     => ['name'=>'Google (Gemini)','icon'=>'🔵','url'=>'https://makersuite.google.com/app/apikey','placeholder'=>'AIza...'],
            'deepseek'   => ['name'=>'DeepSeek','icon'=>'💎','url'=>'https://platform.deepseek.com/api_keys','placeholder'=>'sk-...'],
            'openrouter' => ['name'=>'OpenRouter','icon'=>'🌐','url'=>'https://openrouter.ai/keys','placeholder'=>'sk-or-...'],
            'mistral'    => ['name'=>'Mistral AI','icon'=>'🟠','url'=>'https://console.mistral.ai/api-keys/','placeholder'=>'...'],
            'groq'       => ['name'=>'Groq','icon'=>'⚡','url'=>'https://console.groq.com/keys','placeholder'=>'gsk_...'],
        ];
        ?>
        <div class="wrap aicpp-wrap">
            <h1>⚙️ Settings — Multi-Provider API Configuration</h1>
            <form method="post"><?php wp_nonce_field('aicpp_set'); ?>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">🔐</span> Access Control</h2>
                    <table class="form-table">
                        <tr><th>🔒 Require Login</th><td>
                            <label><input type="checkbox" name="require_login" <?php checked($require_login); ?>> <strong>Require users to log in before chatting</strong></label>
                            <p class="description">Recommended: Since personas are assigned per-client, users must log in to see their assigned personas.</p>
                        </td></tr>
                        <tr><th>💬 Login Message</th><td>
                            <textarea name="login_message" rows="3" class="large-text"><?php echo esc_textarea($login_message); ?></textarea>
                            <p class="description">Message shown to logged-out users.</p>
                        </td></tr>
                    </table>
                </div>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">🏗️</span> Default AI Provider</h2>
                    <p class="description">Select the default provider. The system auto-detects based on model name when using personas.</p>
                    <select name="provider" id="provider_select" style="font-size:16px;padding:10px;min-width:300px">
                        <?php foreach ($providers as $key => $p): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($provider, $key); ?>><?php echo $p['icon']; ?> <?php echo esc_html($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">🔑</span> API Keys</h2>
                    <p class="description">Enter API keys for all providers you want to use. The plugin auto-detects which provider to use based on the model name.</p>
                    <div class="aicpp-providers-grid">
                        <?php foreach ($providers as $key => $p): ?>
                        <div class="aicpp-provider-card <?php echo ($provider===$key)?'active':''; ?>" data-provider="<?php echo esc_attr($key); ?>">
                            <h4><?php echo $p['icon']; ?> <?php echo esc_html($p['name']); ?>
                            <?php if (!empty($keys[$key])): ?><span class="aicpp-provider-badge connected">✅ Connected</span><?php endif; ?></h4>
                            <input type="text" name="<?php echo esc_attr($key); ?>_key" value="<?php echo esc_attr($keys[$key]); ?>" class="large-text" placeholder="<?php echo esc_attr($p['placeholder']); ?>" autocomplete="off">
                            <p class="description">Get from <a href="<?php echo esc_url($p['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html(str_replace('https://', '', $p['url'])); ?></a></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="aicpp-card">
                    <h2><span class="aicpp-section-icon">📏</span> Limits</h2>
                    <table class="form-table">
                        <tr><th>📝 Max Message Length</th><td><input type="number" name="max_message_length" value="<?php echo esc_attr($max_len); ?>" min="100" max="100000"></td></tr>
                    </table>
                </div>
                <p><input type="submit" name="save_set" class="button button-primary button-large" value="💾 Save Settings"></p>
            </form>
            <div class="aicpp-card">
                <h2><span class="aicpp-section-icon">🆓</span> OpenRouter Free Models Preset</h2>
                <p class="description">One-click load all working free OpenRouter models into your persona dropdown. Saves to <code>aicpp_or_free_models_cache</code> and overrides the OpenRouter optgroup in the persona editor.</p>
                <p>
                    <button type="button" class="button button-primary" onclick="aicppOrLoadFree()">Use free models (load cached)</button>
                    <button type="button" class="button" onclick="aicppOrRefresh()">Refresh from OpenRouter API</button>
                    <span id="aicpp-or-status" style="margin-left:10px"></span>
                </p>
                <div id="aicpp-or-list" style="margin-top:10px;max-height:240px;overflow:auto;background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:10px;font-size:12px;display:none"></div>
            </div>
            <script>
            (function(){
                var ajaxurl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
                var nonce = <?php echo wp_json_encode(wp_create_nonce('aicpp')); ?>;
                function render(list){
                    var box = document.getElementById('aicpp-or-list');
                    box.style.display='block';
                    var keys = Object.keys(list);
                    box.innerHTML = '<strong>'+keys.length+' free models available:</strong><br>' +
                        keys.map(function(k){ return '<code>'+k+'</code> &mdash; '+list[k]; }).join('<br>');
                }
                window.aicppOrLoadFree = function(){
                    var fd = new FormData();
                    fd.append('action','aicpp_or_free_models');
                    fd.append('nonce',nonce);
                    fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
                        if (d.success){ render(d.data.models); document.getElementById('aicpp-or-status').textContent='Loaded.'; }
                        else alert(d.data.message||'Error');
                    });
                };
                window.aicppOrRefresh = function(){
                    document.getElementById('aicpp-or-status').textContent='Refreshing…';
                    var fd = new FormData();
                    fd.append('action','aicpp_or_refresh_free');
                    fd.append('nonce',nonce);
                    fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
                        if (d.success){ render(d.data.models); document.getElementById('aicpp-or-status').textContent='Refreshed: '+d.data.count+' models.'; }
                        else { document.getElementById('aicpp-or-status').textContent='Failed.'; alert(d.data.message||'Error'); }
                    });
                };
            })();
            </script>
        </div>
        <script>
        document.getElementById('provider_select').addEventListener('change', function(){
            document.querySelectorAll('.aicpp-provider-card').forEach(function(c){c.classList.remove('active')});
            var s=document.querySelector('.aicpp-provider-card[data-provider="'+this.value+'"]');
            if(s)s.classList.add('active');
        });
        </script>
        <?php
    }

    // ===================== ADMIN AJAX: PERSONA CRUD =====================
    public function ajax_get_persona() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_personas} WHERE id = %d", intval($_POST['persona_id'])), ARRAY_A);
        $p ? wp_send_json_success($p) : wp_send_json_error(['message' => 'Not found']);
    }

    public function ajax_save_persona() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;

        $id   = intval($_POST['persona_id'] ?? 0);
        $visibility = sanitize_text_field(wp_unslash($_POST['visibility'] ?? 'private'));
        if (!in_array($visibility, ['public', 'private'], true)) $visibility = 'private';

        $data = [
            'name'                        => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'description'                 => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'avatar_initials'             => mb_substr(sanitize_text_field(wp_unslash($_POST['avatar_initials'] ?? '')), 0, 4),
            'avatar_color'                => sanitize_hex_color(wp_unslash($_POST['avatar_color'] ?? '#667eea')) ?: '#667eea',
            'system_prompt'               => mb_substr((string) wp_unslash($_POST['system_prompt'] ?? ''), 0, 200000),
            'emotional_intelligence_code' => mb_substr((string) wp_unslash($_POST['emotional_intelligence_code'] ?? ''), 0, 200000),
            'rewards_code'                => mb_substr((string) wp_unslash($_POST['rewards_code'] ?? ''), 0, 200000),
            'use_global_ei'               => ($_POST['use_global_ei'] ?? '0') === '1' ? 1 : 0,
            'use_global_rewards'          => ($_POST['use_global_rewards'] ?? '0') === '1' ? 1 : 0,
            'model'                       => sanitize_text_field(wp_unslash($_POST['model'] ?? 'gpt-4')),
            'temperature'                 => max(0.0, min(2.0, floatval($_POST['temperature'] ?? 0.7))),
            'max_tokens'                  => max(1, min(128000, intval($_POST['max_tokens'] ?? 2000))),
            'visibility'                  => $visibility,
            'created_by'                  => get_current_user_id(),
        ];

        if (empty($data['name']) || empty($data['system_prompt'])) {
            wp_send_json_error(['message' => 'Name and system prompt are required.']);
        }

        if (empty($data['avatar_initials'])) {
            $words = explode(' ', $data['name']);
            $data['avatar_initials'] = mb_strtoupper(mb_substr($words[0], 0, 1) . (isset($words[1]) ? mb_substr($words[1], 0, 1) : mb_substr($words[0], 1, 1)));
        }

        // Explicit, key-ordered format map — safe under any PHP key-ordering behavior.
        $format_map = [
            'name'                        => '%s',
            'description'                 => '%s',
            'avatar_initials'             => '%s',
            'avatar_color'                => '%s',
            'system_prompt'               => '%s',
            'emotional_intelligence_code' => '%s',
            'rewards_code'                => '%s',
            'use_global_ei'               => '%d',
            'use_global_rewards'          => '%d',
            'model'                       => '%s',
            'temperature'                 => '%f',
            'max_tokens'                  => '%d',
            'visibility'                  => '%s',
            'created_by'                  => '%d',
        ];

        if ($id > 0) {
            unset($data['created_by']);
            $format = [];
            foreach (array_keys($data) as $k) { $format[] = $format_map[$k]; }
            $result = $wpdb->update($this->table_personas, $data, ['id' => $id], $format, ['%d']);
            $persona_id = $id;
        } else {
            $format = [];
            foreach (array_keys($data) as $k) { $format[] = $format_map[$k]; }
            $result = $wpdb->insert($this->table_personas, $data, $format);
            $persona_id = $wpdb->insert_id;
        }

        if ($result === false) {
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
        }

        wp_send_json_success(['message' => 'Saved', 'persona_id' => $persona_id]);
    }

    public function ajax_delete_persona() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $id = intval($_POST['persona_id'] ?? 0);
        $wpdb->delete($this->table_persona_assignments, ['persona_id' => $id], ['%d']);
        $wpdb->delete($this->table_personas, ['id' => $id], ['%d']);
        wp_send_json_success(['message' => 'Deleted']);
    }

    // ===================== ADMIN AJAX: USER SEARCH & ASSIGNMENT =====================
    public function ajax_search_users() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);

        $query = sanitize_text_field(wp_unslash($_POST['query'] ?? ''));
        if (strlen($query) < 2) wp_send_json_error(['message' => 'Query too short']);

        $users = get_users([
            'search'         => '*' . $query . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number'         => 20,
            'fields'         => ['ID', 'user_login', 'user_email', 'display_name'],
        ]);

        wp_send_json_success(['users' => $users]);
    }

    public function ajax_get_persona_users() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;

        $persona_id = intval($_POST['persona_id'] ?? 0);
        $users = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.user_login, u.user_email, u.display_name
             FROM {$this->table_persona_assignments} pa
             JOIN {$wpdb->users} u ON pa.user_id = u.ID
             WHERE pa.persona_id = %d
             ORDER BY u.display_name ASC",
            $persona_id
        ));

        wp_send_json_success(['users' => $users]);
    }

    public function ajax_assign_persona() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;

        $persona_id = intval($_POST['persona_id'] ?? 0);
        $user_id    = intval($_POST['user_id'] ?? 0);

        if (!$persona_id || !$user_id) wp_send_json_error(['message' => 'Invalid data']);

        $persona = $wpdb->get_row($wpdb->prepare("SELECT id, visibility FROM {$this->table_personas} WHERE id = %d", $persona_id));
        if (!$persona) wp_send_json_error(['message' => 'Persona not found']);

        $user = get_user_by('id', $user_id);
        if (!$user) wp_send_json_error(['message' => 'User not found']);

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_persona_assignments} WHERE persona_id = %d AND user_id = %d",
            $persona_id, $user_id
        ));

        if ($exists > 0) wp_send_json_error(['message' => 'This persona is already assigned to this user.']);

        $result = $wpdb->insert($this->table_persona_assignments, [
            'persona_id'  => $persona_id,
            'user_id'     => $user_id,
            'assigned_by' => get_current_user_id(),
        ], ['%d', '%d', '%d']);

        if ($result === false) {
            wp_send_json_error(['message' => 'Database error']);
        }

        wp_send_json_success(['message' => 'Assigned successfully']);
    }

    public function ajax_unassign_persona() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;

        $persona_id = intval($_POST['persona_id'] ?? 0);
        $user_id    = intval($_POST['user_id'] ?? 0);

        $wpdb->delete($this->table_persona_assignments, [
            'persona_id' => $persona_id,
            'user_id'    => $user_id,
        ], ['%d', '%d']);

        wp_send_json_success(['message' => 'Removed']);
    }

    public function ajax_get_user_personas() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;

        $user_id = intval($_POST['user_id'] ?? 0);
        $personas = $wpdb->get_results($wpdb->prepare(
            "SELECT p.id, p.name, p.model FROM {$this->table_personas} p
             INNER JOIN {$this->table_persona_assignments} pa ON p.id = pa.persona_id
             WHERE pa.user_id = %d ORDER BY p.name ASC",
            $user_id
        ));

        wp_send_json_success(['personas' => $personas]);
    }

    public function ajax_bulk_assign() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;

        $persona_id = intval($_POST['persona_id'] ?? 0);
        if ($persona_id <= 0) wp_send_json_error(['message' => 'Invalid persona id']);

        $persona_exists = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_personas} WHERE id = %d", $persona_id
        ));
        if (!$persona_exists) wp_send_json_error(['message' => 'Persona not found']);

        $raw_ids = isset($_POST['user_ids']) ? wp_unslash($_POST['user_ids']) : '[]';
        $decoded = json_decode($raw_ids, true);
        $user_ids = is_array($decoded) ? array_map('intval', $decoded) : [];
        $admin_id = get_current_user_id();

        $count = 0;
        foreach ($user_ids as $uid) {
            if ($uid <= 0) continue;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_persona_assignments} WHERE persona_id = %d AND user_id = %d",
                $persona_id, $uid
            ));
            if (!$exists) {
                $wpdb->insert($this->table_persona_assignments, [
                    'persona_id' => $persona_id, 'user_id' => $uid, 'assigned_by' => $admin_id
                ], ['%d', '%d', '%d']);
                $count++;
            }
        }

        wp_send_json_success(['message' => "Assigned to {$count} user(s)"]);
    }

    // ===================== PUBLIC AJAX: GET MY PERSONAS =====================
    public function ajax_get_my_personas() {
        check_ajax_referer('aicpp_chat', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'Please log in to access your AI assistants.']);
        }

        $personas = $this->get_user_personas($user_id);
        $result = [];
        foreach ($personas as $p) {
            $result[] = [
                'id'               => $p->id,
                'name'             => $p->name,
                'description'      => $p->description,
                'avatar_initials'  => $p->avatar_initials ?: mb_strtoupper(mb_substr($p->name, 0, 2)),
                'avatar_color'     => $p->avatar_color ?: '#667eea',
                'model'            => $p->model,
                'visibility'       => $p->visibility,
            ];
        }

        // Also include main character info
        $main_enabled = get_option('aicpp_main_char_enabled', '0') === '1';
        $main_char = null;
        if ($main_enabled) {
            $main_char = [
                'name'            => get_option('aicpp_main_char_name', 'AI Assistant'),
                'description'     => get_option('aicpp_main_char_description', 'Your helpful AI assistant'),
                'avatar_initials' => get_option('aicpp_main_char_avatar_initials', 'AI'),
                'avatar_color'    => get_option('aicpp_main_char_avatar_color', '#667eea'),
                'model'           => get_option('aicpp_main_char_model', 'gpt-4'),
            ];
        }

        wp_send_json_success(['personas' => $result, 'main_character' => $main_char]);
    }

    // ===================== CONVERSATION AJAX =====================
    public function ajax_get_conversations() {
        check_ajax_referer('aicpp_chat', 'nonce');
        global $wpdb;

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_success(['conversations' => []]);
            return;
        }

        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.title, c.token_count, c.persona_id, c.is_main_chat, c.pinned, c.created_at, c.updated_at,
                    p.name as persona_name, p.avatar_initials, p.avatar_color
             FROM {$this->table_conversations} c
             LEFT JOIN {$this->table_personas} p ON c.persona_id = p.id
             WHERE c.user_id = %d
             ORDER BY c.pinned DESC, c.updated_at DESC LIMIT 50",
            $user_id
        ));

        // Collect all conversations missing a title in one pass to avoid N+1 queries.
        $needs_title_ids = [];
        foreach ($conversations as $c) {
            if (empty($c->title)) $needs_title_ids[] = (int) $c->id;
        }

        if (!empty($needs_title_ids)) {
            $placeholders = implode(',', array_fill(0, count($needs_title_ids), '%d'));
            $first_msgs = $wpdb->get_results($wpdb->prepare(
                "SELECT m.conversation_id, m.content
                 FROM {$this->table_messages} m
                 INNER JOIN (
                     SELECT conversation_id, MIN(id) AS min_id
                     FROM {$this->table_messages}
                     WHERE role = 'user' AND conversation_id IN ($placeholders)
                     GROUP BY conversation_id
                 ) f ON f.conversation_id = m.conversation_id AND f.min_id = m.id",
                ...$needs_title_ids
            ));

            $title_map = [];
            foreach ($first_msgs as $row) {
                $title_map[(int)$row->conversation_id] = wp_trim_words($row->content, 8, '...');
            }

            foreach ($conversations as &$c) {
                if (empty($c->title)) {
                    $c->title = $title_map[(int)$c->id] ?? ('Conversation #' . $c->id);
                    $wpdb->update($this->table_conversations, ['title' => $c->title], ['id' => $c->id], ['%s'], ['%d']);
                }
            }
            unset($c);
        }

        wp_send_json_success(['conversations' => $conversations]);
    }

    public function ajax_load_conversation() {
        check_ajax_referer('aicpp_chat', 'nonce');
        global $wpdb;

        $conv_id = intval($_POST['conversation_id'] ?? 0);
        $conv    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_conversations} WHERE id = %d", $conv_id));
        if (!$conv) wp_send_json_error(['message' => 'Not found']);

        if (!$this->verify_conversation_ownership($conv)) {
            wp_send_json_error(['message' => 'Access denied']);
        }

        $user_id = get_current_user_id();
        if ($user_id && $conv->persona_id && !$conv->is_main_chat && !$this->user_can_access_persona($user_id, $conv->persona_id)) {
            wp_send_json_error(['message' => 'You no longer have access to this persona.']);
        }

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT role, content FROM {$this->table_messages} WHERE conversation_id = %d ORDER BY id ASC",
            $conv_id
        ), ARRAY_A);

        wp_send_json_success([
            'messages'    => $messages ?: [],
            'session_id'  => $conv->session_id,
            'persona_id'  => $conv->persona_id,
            'pinned'      => (int)$conv->pinned,
            'is_main_chat' => (int)$conv->is_main_chat,
        ]);
    }

    public function ajax_delete_conversation() {
        check_ajax_referer('aicpp_chat', 'nonce');
        global $wpdb;

        $conv_id = intval($_POST['conversation_id'] ?? 0);
        $conv    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_conversations} WHERE id = %d", $conv_id));
        if (!$conv) wp_send_json_error(['message' => 'Not found']);
        if (!$this->verify_conversation_ownership($conv)) wp_send_json_error(['message' => 'Access denied']);

        $wpdb->delete($this->table_messages, ['conversation_id' => $conv_id], ['%d']);
        $wpdb->delete($this->table_conversations, ['id' => $conv_id], ['%d']);
        wp_send_json_success(['message' => 'Deleted']);
    }

    // ===================== FILE UPLOAD =====================
    public function ajax_upload_file() {
        check_ajax_referer('aicpp_chat', 'nonce');

        // Honor the require_login setting for uploads
        if (get_option('aicpp_require_login', '1') === '1' && !is_user_logged_in()) {
            wp_send_json_error(['message' => 'Please sign in to upload files.']);
        }

        if (!$this->check_rate_limit('upload', 20, 300)) wp_send_json_error(['message' => 'Rate limit exceeded.']);
        if (empty($_FILES['file']) || !is_array($_FILES['file']) || !empty($_FILES['file']['error'])) {
            wp_send_json_error(['message' => 'No file uploaded']);
        }

        $file = $_FILES['file'];
        if ($file['size'] > 10 * 1024 * 1024) wp_send_json_error(['message' => 'File too large. Max 10MB.']);

        $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        $allowed = ['jpg','jpeg','png','gif','webp','pdf','txt'];
        if (empty($file_info['ext']) || !in_array(strtolower($file_info['ext']), $allowed, true)) {
            wp_send_json_error(['message' => 'File type not allowed.']);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $upload = wp_handle_upload($file, ['test_form' => false]);
        if (isset($upload['error'])) wp_send_json_error(['message' => $upload['error']]);

        $actual_type = $file_info['type'] ?: $upload['type'];
        $response = ['file_url' => $upload['url'], 'file_name' => basename($upload['file']), 'file_type' => $actual_type];

        if (strpos($actual_type, 'image/') === 0 && filesize($upload['file']) <= 2 * 1024 * 1024) {
            $img_data = file_get_contents($upload['file']);
            if ($img_data !== false) $response['file_data'] = 'data:' . $actual_type . ';base64,' . base64_encode($img_data);
        }
        if ($actual_type === 'text/plain') {
            $txt = file_get_contents($upload['file'], false, null, 0, 5000);
            if ($txt !== false) $response['file_data'] = $txt;
        }

        wp_send_json_success($response);
    }

    // ===================== AUDIO TRANSCRIPTION =====================
    public function ajax_transcribe_audio() {
        check_ajax_referer('aicpp_chat', 'nonce');

        if (get_option('aicpp_require_login', '1') === '1' && !is_user_logged_in()) {
            wp_send_json_error(['message' => 'Please sign in to use voice transcription.']);
        }

        if (!$this->check_rate_limit('transcribe', 10, 300)) wp_send_json_error(['message' => 'Rate limit exceeded.']);
        if (empty($_FILES['audio']) || !empty($_FILES['audio']['error'])) wp_send_json_error(['message' => 'No audio received']);

        // FIX #4: Cap reduced to 10MB to avoid OOM on shared hosting; Whisper handles 10MB fine.
        if (!empty($_FILES['audio']['size']) && $_FILES['audio']['size'] > 10 * 1024 * 1024) {
            wp_send_json_error(['message' => 'Audio too large. Max 10MB.']);
        }

        // FIX #4: Validate audio MIME/extension before forwarding to OpenAI.
        $check = wp_check_filetype_and_ext($_FILES['audio']['tmp_name'], $_FILES['audio']['name']);
        $ok_audio = ['webm','mp3','m4a','wav','ogg','mp4','mpga','mpeg'];
        if (empty($check['ext']) || !in_array(strtolower($check['ext']), $ok_audio, true)) {
            wp_send_json_error(['message' => 'Audio format not allowed.']);
        }

        $key = $this->get_api_key('openai');
        if (!$key) wp_send_json_error(['message' => 'OpenAI API key required for transcription.']);

        $boundary = wp_generate_password(24, false);
        // Force a safe, fixed filename — never trust client-supplied filename inside multipart headers.
        $audio_name = 'recording.webm';
        // FIX #4: Guard against unreadable temp file before composing multipart body.
        $audio_bytes = @file_get_contents($_FILES['audio']['tmp_name']);
        if ($audio_bytes === false) wp_send_json_error(['message' => 'Could not read audio file.']);
        $body  = "--{$boundary}\r\nContent-Disposition: form-data; name=\"model\"\r\n\r\nwhisper-1\r\n";
        $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"file\"; filename=\"{$audio_name}\"\r\nContent-Type: audio/webm\r\n\r\n";
        $body .= $audio_bytes . "\r\n--{$boundary}--\r\n";

        $r = wp_remote_post('https://api.openai.com/v1/audio/transcriptions', [
            'headers' => ['Authorization' => 'Bearer ' . $key, 'Content-Type' => 'multipart/form-data; boundary=' . $boundary],
            'body' => $body, 'timeout' => 60,
        ]);

        if (is_wp_error($r)) wp_send_json_error(['message' => $r->get_error_message()]);
        $result = json_decode(wp_remote_retrieve_body($r), true);
        isset($result['text']) ? wp_send_json_success(['text' => $result['text']]) : wp_send_json_error(['message' => $result['error']['message'] ?? 'Failed']);
    }

    // ===================== REGISTRATION =====================
    public function ajax_register_user() {
        check_ajax_referer('aicpp_register', 'nonce');
        global $wpdb;
        if (is_user_logged_in()) wp_send_json_error(['message' => 'Already logged in.']);
        if (!get_option('users_can_register')) wp_send_json_error(['message' => 'Registration disabled.']);
        if (!$this->check_rate_limit('register', 3, 900)) wp_send_json_error(['message' => 'Too many attempts. Try again later.']);

        $username     = sanitize_user(wp_unslash($_POST['username'] ?? ''));
        $email        = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $password     = wp_unslash($_POST['password'] ?? '');
        $display_name = sanitize_text_field(wp_unslash($_POST['display_name'] ?? ''));
        $referral_code = sanitize_text_field(wp_unslash($_POST['referral_code'] ?? ''));
        // FIX #7: Validate referral code format (VRS-{ID}-{6 alphanum}); drop if malformed.
        if ($referral_code && !preg_match('/^VRS-\d+-[A-Z0-9]{6}$/i', $referral_code)) {
            $referral_code = '';
        }

        if (empty($username) || empty($email) || empty($password)) wp_send_json_error(['message' => 'All fields required.']);
        if (strlen($password) < 8) wp_send_json_error(['message' => 'Password must be at least 8 characters.']);
        if (!is_email($email)) wp_send_json_error(['message' => 'Invalid email.']);
        if (username_exists($username)) wp_send_json_error(['message' => 'Username taken.']);
        if (email_exists($email)) wp_send_json_error(['message' => 'Email already registered.']);

        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) wp_send_json_error(['message' => $user_id->get_error_message()]);

        if ($display_name) {
            wp_update_user(['ID' => $user_id, 'display_name' => wp_strip_all_tags($display_name)]);
        }

        $my_ref_code = strtoupper('VRS-' . $user_id . '-' . wp_generate_password(6, false, false));
        update_user_meta($user_id, 'aicpp_referral_code', $my_ref_code);
        update_user_meta($user_id, 'aicpp_reward_points', 0);

        if (!empty($referral_code)) {
            $referrers = get_users([
                'meta_key'   => 'aicpp_referral_code',
                'meta_value' => $referral_code,
                'fields'     => 'ids',
                'number'     => 1,
            ]);

            if (!empty($referrers)) {
                $referrer_id = (int) $referrers[0];

                // Make sure the referrer still exists as a real user.
                $referrer_user = get_userdata($referrer_id);

                // Guard against: self-referral, deleted referrer, and duplicate award (UNIQUE idx_referred_user already enforces this at DB level).
                if ($referrer_user && $referrer_id !== (int) $user_id) {
                    $inserted = $wpdb->insert($this->table_referrals, [
                        'referrer_user_id' => $referrer_id,
                        'referred_user_id' => $user_id,
                        'referral_code'    => $referral_code,
                        'points_awarded'   => 100,
                    ], ['%d', '%d', '%s', '%d']);

                    // Only credit points if the row was actually inserted (UNIQUE key prevents double credit on race).
                    if ($inserted) {
                        $current_points = (int) get_user_meta($referrer_id, 'aicpp_reward_points', true);
                        update_user_meta($referrer_id, 'aicpp_reward_points', $current_points + 100);
                    }
                }
            }
        }

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        wp_send_json_success([
            'message'       => 'Registration successful!',
            'user_id'       => $user_id,
            'display_name'  => $display_name ?: $username,
            'email'         => $email,
            'bio'           => '',
            'avatar'        => '',
            'referral_code' => $my_ref_code,
            'points'        => 0,
        ]);
    }

    // ===================== LOGIN =====================
    public function ajax_login_user() {
        check_ajax_referer('aicpp_login', 'nonce');
        if (is_user_logged_in()) wp_send_json_error(['message' => 'Already logged in.']);
        if (!$this->check_rate_limit('login', 5, 300)) wp_send_json_error(['message' => 'Too many attempts. Try again later.']);

        // FIX #3: wp_unslash on login + password so quotes/backslashes don't silently mismatch the stored hash.
        $login    = sanitize_text_field(wp_unslash($_POST['login'] ?? ''));
        $password = wp_unslash($_POST['password'] ?? '');
        if (empty($login) || empty($password)) wp_send_json_error(['message' => 'Username/email and password required.']);

        $creds = ['user_login' => $login, 'user_password' => $password, 'remember' => true];
        if (is_email($login)) {
            $user = get_user_by('email', $login);
            if ($user) $creds['user_login'] = $user->user_login;
        }

        $user = wp_signon($creds, is_ssl());

        if (is_wp_error($user)) {
            wp_send_json_error(['message' => 'Invalid username/email or password.']);
        }

        wp_set_current_user($user->ID);

        wp_send_json_success([
            'message'       => 'Welcome back, ' . ($user->display_name ?: $user->user_login) . '!',
            'user_id'       => $user->ID,
            'display_name'  => $user->display_name ?: $user->user_login,
            'email'         => $user->user_email,
            'bio'           => $user->description ?: '',
            'avatar'        => get_user_meta($user->ID, 'aicpp_avatar', true) ?: '',
            'referral_code' => get_user_meta($user->ID, 'aicpp_referral_code', true) ?: '',
            'points'        => (int) get_user_meta($user->ID, 'aicpp_reward_points', true),
        ]);
    }

    // ===================== MAIN CHARACTER CHAT HANDLER =====================
    public function handle_chat_main() {
        check_ajax_referer('aicpp_chat', 'nonce');
        global $wpdb;

        if (!$this->check_rate_limit('chat', 30, 300)) wp_send_json_error(['message' => 'Rate limit exceeded.']);

        if (get_option('aicpp_require_login', '1') === '1' && !is_user_logged_in()) {
            wp_send_json_error(['message' => get_option('aicpp_login_message', 'Please sign in.')]);
        }

        $main_enabled = get_option('aicpp_main_char_enabled', '0') === '1';
        if (!$main_enabled) wp_send_json_error(['message' => 'Main character is not enabled.']);

        $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
        $session = sanitize_text_field(wp_unslash($_POST['session_id'] ?? ''));
        $user_id = get_current_user_id();

        $max_len = intval(get_option('aicpp_max_message_length', 10000));
        if (mb_strlen($message) > $max_len) wp_send_json_error(['message' => "Message too long. Max {$max_len} characters."]);
        if (empty($message)) wp_send_json_error(['message' => 'Message cannot be empty.']);
        if (empty($session)) wp_send_json_error(['message' => 'Invalid session.']);

        // Build a pseudo-persona object from main character settings
        $main_persona = (object)[
            'id'                          => 0,
            'name'                        => get_option('aicpp_main_char_name', 'AI Assistant'),
            'system_prompt'               => get_option('aicpp_main_char_system_prompt', 'You are a helpful AI assistant.'),
            'emotional_intelligence_code' => '',
            'rewards_code'                => '',
            'use_global_ei'               => 1,
            'use_global_rewards'          => 1,
            'model'                       => get_option('aicpp_main_char_model', 'gpt-4'),
            'temperature'                 => floatval(get_option('aicpp_main_char_temperature', '0.7')),
            'max_tokens'                  => intval(get_option('aicpp_main_char_max_tokens', '2000')),
        ];

        $conv = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_conversations} WHERE session_id = %s AND is_main_chat = 1 AND (persona_id IS NULL OR persona_id = 0) ORDER BY id DESC LIMIT 1", $session
        ));

        if (!$conv) {
            $wpdb->insert($this->table_conversations, [
                'user_id' => $user_id, 'session_id' => $session,
                'title' => wp_trim_words($message, 8, '...'), 'token_count' => 0, 'is_main_chat' => 1, 'updated_at' => current_time('mysql'),
            ], ['%d', '%s', '%s', '%d', '%d', '%s']);
            $conv_id = $wpdb->insert_id;
            $token_count = 0;
        } else {
            // FIX #1: Verify ownership when resuming an existing conversation.
            if ($user_id) {
                if ((int) $conv->user_id !== $user_id) {
                    wp_send_json_error(['message' => 'Conversation ownership mismatch.']);
                }
            } else {
                if ((int) $conv->user_id !== 0 || !hash_equals((string) $conv->session_id, $session)) {
                    wp_send_json_error(['message' => 'Conversation ownership mismatch.']);
                }
            }
            $conv_id = $conv->id;
            $token_count = (int)$conv->token_count;
        }

        // Handle attachments
        $attachment_context = '';
        if (!empty($_POST['has_attachment']) && $_POST['has_attachment'] === '1') {
            $att_type = sanitize_text_field(wp_unslash($_POST['attachment_type'] ?? ''));
            $att_url  = esc_url_raw(wp_unslash($_POST['attachment_url'] ?? ''));
            $att_data = wp_unslash($_POST['attachment_data'] ?? '');
            if (strpos($att_type, 'image/') === 0 && $att_url) $attachment_context = "\n\n[User shared an image: {$att_url}]";
            elseif ($att_type === 'text/plain' && $att_data) $attachment_context = "\n\n[User shared text file:\n" . sanitize_textarea_field(mb_substr($att_data, 0, 5000)) . "\n]";
            elseif ($att_type === 'application/pdf' && $att_url) $attachment_context = "\n\n[User shared PDF: {$att_url}]";
        }

        $user_content = $message . $attachment_context;

        $wpdb->insert($this->table_messages, [
            'conversation_id' => $conv_id, 'role' => 'user', 'content' => $message, 'raw_content' => $user_content,
        ], ['%d', '%s', '%s', '%s']);

        $recent = $wpdb->get_results($wpdb->prepare(
            "SELECT role, raw_content, content FROM {$this->table_messages} WHERE conversation_id = %d ORDER BY id DESC LIMIT 20", $conv_id
        ), ARRAY_A);
        $recent = array_reverse($recent);

        $api_history = [];
        foreach ($recent as $rm) {
            $api_history[] = ['role' => $rm['role'], 'content' => $rm['raw_content'] ?: $rm['content']];
        }

        $api_msgs = $this->build_messages($main_persona, $api_history, $session, $conv_id);
        $response = $this->call_api($api_msgs, $main_persona);

        if (isset($response['error'])) wp_send_json_error(['message' => $response['error']]);

        $reply  = $response['choices'][0]['message']['content'] ?? '';
        $tokens = $response['usage']['total_tokens'] ?? 0;

        // Upgrade F: extract & strip <remember> tags before storing/displaying.
        $reply = $this->extract_and_save_memories($reply, get_current_user_id() ?: 0, (int) ($main_persona->id ?? 0));

        $wpdb->insert($this->table_messages, [
            'conversation_id' => $conv_id, 'role' => 'assistant', 'content' => $reply, 'raw_content' => $reply,
        ], ['%d', '%s', '%s', '%s']);

        // FEATURE 4: extract any <artifact>...</artifact> blocks and persist them
        $this->extract_and_save_artifacts($reply, $conv_id, get_current_user_id() ?: 0);

        $wpdb->update($this->table_conversations,
            ['token_count' => $token_count + $tokens, 'updated_at' => current_time('mysql')],
            ['id' => $conv_id], ['%d', '%s'], ['%d']
        );

        wp_send_json_success(['message' => $reply, 'tokens' => $tokens, 'conversation_id' => $conv_id]);
    }

    // ===================== PERSONA CHAT HANDLER (BUG #2 FIXED) =====================
    public function handle_chat() {
        check_ajax_referer('aicpp_chat', 'nonce');
        global $wpdb;

        if (!$this->check_rate_limit('chat', 30, 300)) wp_send_json_error(['message' => 'Rate limit exceeded.']);

        if (get_option('aicpp_require_login', '1') === '1' && !is_user_logged_in()) {
            wp_send_json_error(['message' => get_option('aicpp_login_message', 'Please sign in.')]);
        }

        $persona_id = intval($_POST['persona_id'] ?? 0);
        $message    = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
        $session    = sanitize_text_field(wp_unslash($_POST['session_id'] ?? ''));
        $user_id    = get_current_user_id();

        $max_len = intval(get_option('aicpp_max_message_length', 10000));
        if (mb_strlen($message) > $max_len) wp_send_json_error(['message' => "Message too long. Max {$max_len} characters."]);
        if (empty($message)) wp_send_json_error(['message' => 'Message cannot be empty.']);
        if (empty($session)) wp_send_json_error(['message' => 'Invalid session.']);

        // Personas are always tied to user accounts (public personas still require a logged-in user).
        if (!$user_id) {
            wp_send_json_error(['message' => get_option('aicpp_login_message', 'Please sign in to chat with a persona.')]);
        }
        if (!$this->user_can_access_persona($user_id, $persona_id)) {
            wp_send_json_error(['message' => 'You do not have access to this persona.']);
        }

        $persona = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_personas} WHERE id = %d", $persona_id));
        if (!$persona) wp_send_json_error(['message' => 'Persona not found']);

        // FIX: Added persona_id filter to prevent cross-persona session collision
        $conv = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_conversations} WHERE session_id = %s AND persona_id = %d AND is_main_chat = 0 ORDER BY id DESC LIMIT 1",
            $session, $persona_id
        ));

        if (!$conv) {
            $wpdb->insert($this->table_conversations, [
                'user_id' => $user_id, 'persona_id' => $persona_id, 'session_id' => $session,
                'title' => wp_trim_words($message, 8, '...'), 'token_count' => 0, 'is_main_chat' => 0, 'updated_at' => current_time('mysql'),
            ], ['%d', '%d', '%s', '%s', '%d', '%d', '%s']);
            $conv_id = $wpdb->insert_id;
            $token_count = 0;
        } else {
            // Persona chat always requires login, so the conversation must belong to this user.
            if ((int) $conv->user_id !== $user_id) {
                wp_send_json_error(['message' => 'Conversation ownership mismatch.']);
            }
            $conv_id = $conv->id;
            $token_count = (int)$conv->token_count;
        }

        $attachment_context = '';
        if (!empty($_POST['has_attachment']) && $_POST['has_attachment'] === '1') {
            $att_type = sanitize_text_field(wp_unslash($_POST['attachment_type'] ?? ''));
            $att_url  = esc_url_raw(wp_unslash($_POST['attachment_url'] ?? ''));
            $att_data = wp_unslash($_POST['attachment_data'] ?? '');
            if (strpos($att_type, 'image/') === 0 && $att_url) $attachment_context = "\n\n[User shared an image: {$att_url}]";
            elseif ($att_type === 'text/plain' && $att_data) $attachment_context = "\n\n[User shared text file:\n" . sanitize_textarea_field(mb_substr($att_data, 0, 5000)) . "\n]";
            elseif ($att_type === 'application/pdf' && $att_url) $attachment_context = "\n\n[User shared PDF: {$att_url}]";
        }

        $user_content = $message . $attachment_context;

        $wpdb->insert($this->table_messages, [
            'conversation_id' => $conv_id, 'role' => 'user', 'content' => $message, 'raw_content' => $user_content,
        ], ['%d', '%s', '%s', '%s']);

        $recent = $wpdb->get_results($wpdb->prepare(
            "SELECT role, raw_content, content FROM {$this->table_messages} WHERE conversation_id = %d ORDER BY id DESC LIMIT 20", $conv_id
        ), ARRAY_A);
        $recent = array_reverse($recent);

        $api_history = [];
        foreach ($recent as $rm) {
            $api_history[] = ['role' => $rm['role'], 'content' => $rm['raw_content'] ?: $rm['content']];
        }

        $api_msgs = $this->build_messages($persona, $api_history, $session, $conv_id);
        $response = $this->call_api($api_msgs, $persona);

        if (isset($response['error'])) wp_send_json_error(['message' => $response['error']]);

        $reply  = $response['choices'][0]['message']['content'] ?? '';
        $tokens = $response['usage']['total_tokens'] ?? 0;

        // Upgrade F: extract & strip <remember> tags before storing/displaying.
        $reply = $this->extract_and_save_memories($reply, get_current_user_id() ?: 0, (int) ($persona->id ?? 0));

        $wpdb->insert($this->table_messages, [
            'conversation_id' => $conv_id, 'role' => 'assistant', 'content' => $reply, 'raw_content' => $reply,
        ], ['%d', '%s', '%s', '%s']);

        // FEATURE 4: extract any <artifact>...</artifact> blocks and persist them
        $this->extract_and_save_artifacts($reply, $conv_id, get_current_user_id() ?: 0);

        $wpdb->update($this->table_conversations,
            ['token_count' => $token_count + $tokens, 'updated_at' => current_time('mysql')],
            ['id' => $conv_id], ['%d', '%s'], ['%d']
        );

        wp_send_json_success(['message' => $reply, 'tokens' => $tokens, 'conversation_id' => $conv_id]);
    }

    // ===================== BUILD MESSAGES =====================
    private function build_messages($persona, $msgs, $session = '', $conv_id = 0) {
        $binding = get_option('aicpp_character_binding_active', '0') === '1';
        $bcode   = get_option('aicpp_active_character_code', '');

        $sys = ($binding && !empty(trim($bcode))) ? $bcode . "\n\nStay in character always." : $persona->system_prompt;

        $ei = '';
        if (!empty(trim($persona->emotional_intelligence_code ?? ''))) $ei = $persona->emotional_intelligence_code;
        elseif ($persona->use_global_ei && get_option('aicpp_global_ei_enabled', '0') === '1') $ei = get_option('aicpp_global_ei_code', '');
        if (!empty(trim($ei))) $sys .= "\n\n## EMOTIONAL INTELLIGENCE\n" . $ei;

        $rew = '';
        if (!empty(trim($persona->rewards_code ?? ''))) $rew = $persona->rewards_code;
        elseif ($persona->use_global_rewards && get_option('aicpp_global_rewards_enabled', '0') === '1') $rew = get_option('aicpp_global_rewards_code', '');
        if (!empty(trim($rew))) $sys .= "\n\n## REWARDS\n" . $rew;

        // FEATURE 2: Persistent Memory
        $memory_block = $this->get_user_memory_block(get_current_user_id(), (int) ($persona->id ?? 0));
        if (!empty($memory_block)) $sys .= "\n\n## WHAT YOU REMEMBER ABOUT THE USER\n" . $memory_block;

        // FEATURE 3: Project context (custom instructions + knowledge files)
        $project_block = $this->get_project_context_block($conv_id);
        if (!empty($project_block)) $sys .= "\n\n## PROJECT CONTEXT\n" . $project_block;

        // FEATURE 4: Tell the model to wrap code/SVG/markdown in artifact tags
        $sys .= "\n\n## OUTPUT FORMATTING\nWhen returning a substantial code block, full HTML page, SVG, or long-form Markdown document, wrap it in an artifact block:\n<artifact type=\"html|css|js|svg|markdown|code\" title=\"Short title\">\n...content...\n</artifact>\nUse one artifact block per artifact. Outside the block, give a brief explanation only.";

        // Upgrade F: let the model save durable facts about the user.
        $sys .= "\n\n## MEMORY\nIf the user shares a durable, useful fact about themselves (a stable preference, their name, role, language, recurring goal), record it ONCE using a tag on its own line:\n<remember>short fact in third person</remember>\nDo not use it for trivia, one-off requests, or anything sensitive. The tag is stripped before the user sees your reply.";

        $injection = $this->get_injection_content($session);
        if (!empty($injection)) $sys .= "\n\n## ADDITIONAL INSTRUCTIONS\n" . $injection;

        $result = [['role' => 'system', 'content' => $sys]];
        foreach (array_slice($msgs, -20) as $m) {
            $result[] = ['role' => $m['role'], 'content' => $m['content']];
        }
        // Upgrade B: trim history to an estimated token budget so we never blow the context window.
        return $this->trim_to_token_budget($result, max(2000, (int) $persona->max_tokens * 3));
    }

    /**
     * Upgrade B: crude ~4-chars-per-token estimate. Keeps the system prompt
     * plus as many recent turns as fit within $budget tokens.
     */
    private function trim_to_token_budget(array $msgs, $budget = 6000) {
        if (empty($msgs)) return $msgs;
        $system = array_shift($msgs); // always keep system prompt
        $used = (int) ceil(mb_strlen($system['content']) / 4);
        $kept = [];
        foreach (array_reverse($msgs) as $m) {
            $cost = (int) ceil(mb_strlen($m['content']) / 4);
            if ($used + $cost > $budget && !empty($kept)) break;
            $used += $cost;
            $kept[] = $m;
        }
        return array_merge([$system], array_reverse($kept));
    }

    // ===================== 5-SLOT INJECTION =====================
    private function get_injection_content($session) {
        if (get_option('aicpp_injection_enabled', '0') !== '1') return '';
        global $wpdb;

        $user_id  = get_current_user_id();
        $user_key = $user_id ? 'u_' . $user_id : 'g_' . md5($session);

        $state = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_injection_state} WHERE user_key = %s", $user_key));

        if (!$state) {
            $slot = 1;
            $question_count = 1;
        } else {
            $slot = max(1, min(5, (int)$state->current_slot));
            $question_count = max(1, (int)$state->question_count);
        }

        $hidden = '';
        if (get_option("aicpp_slot{$slot}_enabled", '1') === '1') $hidden = get_option("aicpp_hidden_message_{$slot}", '');

        if (!empty($hidden)) {
            $safe_name = mb_substr(wp_strip_all_tags(wp_get_current_user()->display_name ?: 'Friend'), 0, 50);
            $hidden = str_replace(
                ['{user_name}', '{time_of_day}', '{date}', '{slot_number}', '{question_number}'],
                [$safe_name, $this->time_of_day(), current_time('F j, Y'), (string)$slot, (string)$question_count],
                $hidden
            );
        }

        $next = ($slot % 5) + 1;
        $now  = current_time('mysql');

        // Single atomic write — no double-increment on first message.
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$this->table_injection_state} (user_key, current_slot, question_count, updated_at)
             VALUES (%s, %d, %d, %s)
             ON DUPLICATE KEY UPDATE current_slot = %d, question_count = %d, updated_at = %s",
            $user_key, $next, $question_count + 1, $now,
            $next, $question_count + 1, $now
        ));

        return $hidden;
    }

    private function time_of_day() {
        $h = (int)current_time('G');
        if ($h < 12) return 'morning';
        if ($h < 17) return 'afternoon';
        if ($h < 21) return 'evening';
        return 'night';
    }

    // ===================== API PROVIDERS =====================
    /**
     * Upgrade E (foundation): build an OpenAI-style multimodal user message.
     * Pass an image data-URI or URL to actually let vision models SEE the image
     * instead of just reading a text placeholder. Wire this into your front-end
     * by sending attachment_data (data URI) on image uploads, then call this
     * from the chat handlers for vision-capable models.
     */
    private function build_vision_user_content($text, $image_src) {
        if (empty($image_src)) return $text;
        return [
            ['type' => 'text', 'text' => (string) $text],
            ['type' => 'image_url', 'image_url' => ['url' => (string) $image_src]],
        ];
    }

    private function model_supports_vision($model) {
        $m = strtolower((string) $model);
        return (strpos($m, 'gpt-4o') !== false
            || strpos($m, 'gpt-4-turbo') !== false
            || strpos($m, 'claude') !== false
            || strpos($m, 'gemini') !== false
            || strpos($m, '-vl') !== false);
    }

    private function detect_provider($model) {
        $m = strtolower($model);
        if (strpos($m, 'openrouter/') === 0) return 'openrouter';
        if (strpos($m, 'gpt') !== false || strpos($m, 'o1') !== false) return 'openai';
        if (strpos($m, 'claude') !== false) return 'anthropic';
        if (strpos($m, 'gemini') !== false) return 'google';
        if (strpos($m, 'deepseek') !== false) return 'deepseek';
        if (strpos($m, 'mistral') !== false || strpos($m, 'mixtral') !== false || strpos($m, 'open-') !== false) return 'mistral';
        if (strpos($m, 'llama') !== false || strpos($m, 'gemma') !== false) return 'groq';
        return get_option('aicpp_api_provider', 'openai');
    }

    private function call_api($msgs, $persona) {
        $p = $this->detect_provider($persona->model);
        switch ($p) {
            case 'anthropic':  return $this->call_anthropic($msgs, $persona);
            case 'google':     return $this->call_google($msgs, $persona);
            case 'deepseek':   return $this->call_openai_compatible('https://api.deepseek.com/chat/completions', 'deepseek', $msgs, $persona, 'DeepSeek');
            case 'mistral':    return $this->call_openai_compatible('https://api.mistral.ai/v1/chat/completions', 'mistral', $msgs, $persona, 'Mistral');
            case 'groq':       return $this->call_openai_compatible('https://api.groq.com/openai/v1/chat/completions', 'groq', $msgs, $persona, 'Groq');
            case 'openrouter': return $this->call_openrouter($msgs, $persona);
            default:           return $this->call_openai('https://api.openai.com/v1/chat/completions', 'openai', $msgs, $persona, 'OpenAI');
        }
    }

    /**
     * Generic caller for any OpenAI-Chat-Completions-compatible endpoint.
     * Includes Upgrade D: automatic retry with backoff on 429/5xx.
     */
    private function call_openai_compatible($url, $key_name, $msgs, $persona, $label, $extra_headers = []) {
        $key = $this->get_api_key($key_name);
        if (!$key) return ['error' => $label . ' API key not set.'];
        $headers = array_merge(
            ['Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json'],
            $extra_headers
        );
        $payload = wp_json_encode([
            'model'       => $persona->model,
            'messages'    => $msgs,
            'temperature' => (float) $persona->temperature,
            'max_tokens'  => (int) $persona->max_tokens,
        ]);
        return $this->http_with_retry($url, $headers, $payload, $label);
    }

    /**
     * Upgrade D: shared HTTP-POST with up to 2 retries on transient failures.
     */
    private function http_with_retry($url, $headers, $payload, $label, $attempts = 3) {
        $delay = 1;
        for ($i = 1; $i <= $attempts; $i++) {
            $r = wp_remote_post($url, ['headers' => $headers, 'body' => $payload, 'timeout' => 120]);
            if (is_wp_error($r)) {
                if ($i === $attempts) return ['error' => $r->get_error_message()];
                sleep($delay); $delay *= 2; continue;
            }
            $code = (int) wp_remote_retrieve_response_code($r);
            $b = json_decode(wp_remote_retrieve_body($r), true);
            // Retry only on rate-limit / server errors.
            if (($code === 429 || $code >= 500) && $i < $attempts) {
                sleep($delay); $delay *= 2; continue;
            }
            if (isset($b['error'])) {
                return ['error' => is_array($b['error']) ? ($b['error']['message'] ?? ($label . ' error')) : (string) $b['error']];
            }
            return is_array($b) ? $b : ['error' => $label . ' returned an invalid response.'];
        }
        return ['error' => $label . ' failed after retries.'];
    }

    private function call_openai($url, $key_name, $msgs, $persona, $label) {
        return $this->call_openai_compatible($url, $key_name, $msgs, $persona, $label);
    }

    private function call_anthropic($msgs, $persona) {
        $key = $this->get_api_key('anthropic');
        if (!$key) return ['error' => 'Anthropic API key not set.'];
        $sys = ''; $api = [];
        foreach ($msgs as $m) {
            if ($m['role'] === 'system') $sys .= $m['content'] . "\n";
            else $api[] = ['role' => $m['role'], 'content' => $m['content']];
        }
        $bd = ['model' => $persona->model, 'max_tokens' => (int)$persona->max_tokens, 'messages' => $api];
        if (!empty(trim($sys))) $bd['system'] = trim($sys);
        $r = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'headers' => ['x-api-key' => $key, 'anthropic-version' => '2023-06-01', 'Content-Type' => 'application/json'],
            'body' => wp_json_encode($bd), 'timeout' => 120,
        ]);
        if (is_wp_error($r)) return ['error' => $r->get_error_message()];
        $b = json_decode(wp_remote_retrieve_body($r), true);
        if (isset($b['error'])) return ['error' => $b['error']['message'] ?? 'Anthropic error'];
        if (isset($b['content'][0]['text'])) return ['choices' => [['message' => ['content' => $b['content'][0]['text']]]], 'usage' => ['total_tokens' => ($b['usage']['input_tokens'] ?? 0) + ($b['usage']['output_tokens'] ?? 0)]];
        return ['error' => 'Unexpected Anthropic response'];
    }

    private function call_google($msgs, $persona) {
        $key = $this->get_api_key('google');
        if (!$key) return ['error' => 'Google API key not set.'];
        $c = []; $si = '';
        foreach ($msgs as $m) {
            if ($m['role'] === 'system') $si .= $m['content'] . "\n";
            else { $r = $m['role'] === 'assistant' ? 'model' : 'user'; $c[] = ['role' => $r, 'parts' => [['text' => $m['content']]]]; }
        }
        $bd = ['contents' => $c, 'generationConfig' => ['temperature' => (float)$persona->temperature, 'maxOutputTokens' => (int)$persona->max_tokens]];
        if (!empty(trim($si))) $bd['systemInstruction'] = ['parts' => [['text' => trim($si)]]];
        $model = sanitize_text_field($persona->model);
        // FIX #5: Pass Google API key via x-goog-api-key header so it never lands in access/error logs.
        $r = wp_remote_post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
            'headers' => ['Content-Type' => 'application/json', 'x-goog-api-key' => $key], 'body' => wp_json_encode($bd), 'timeout' => 120,
        ]);
        if (is_wp_error($r)) return ['error' => $r->get_error_message()];
        $b = json_decode(wp_remote_retrieve_body($r), true);
        if (isset($b['error'])) return ['error' => $b['error']['message'] ?? 'Google error'];
        if (isset($b['candidates'][0]['content']['parts'][0]['text'])) return ['choices' => [['message' => ['content' => $b['candidates'][0]['content']['parts'][0]['text']]]], 'usage' => ['total_tokens' => ($b['usageMetadata']['promptTokenCount'] ?? 0) + ($b['usageMetadata']['candidatesTokenCount'] ?? 0)]];
        return ['error' => 'Unexpected Google response'];
    }

    private function call_openrouter($msgs, $persona) {
        $key = $this->get_api_key('openrouter');
        if (!$key) return ['error' => 'OpenRouter key not set.'];
        // Strip ONLY the leading "openrouter/" wrapper that we added in our model preset list.
        // Do not touch any other slashes or "openrouter/" segments inside the upstream id.
        $model = $persona->model;
        if (strpos($model, 'openrouter/') === 0) {
            $model = substr($model, strlen('openrouter/'));
        }
        $r = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', [
            'headers' => ['Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json', 'HTTP-Referer' => get_site_url(), 'X-Title' => get_bloginfo('name')],
            'body' => wp_json_encode(['model' => $model, 'messages' => $msgs, 'temperature' => (float)$persona->temperature, 'max_tokens' => (int)$persona->max_tokens]),
            'timeout' => 120,
        ]);
        if (is_wp_error($r)) return ['error' => $r->get_error_message()];
        $b = json_decode(wp_remote_retrieve_body($r), true);
        return isset($b['error']) ? ['error' => $b['error']['message'] ?? 'OpenRouter error'] : $b;
    }

    // ===================== SHORTCODE =====================
    // VERSACE22 INTEGRATION: 100% frontend rendering delegation with standalone fallback
        public function chat_shortcode($atts = []) {
        $atts = shortcode_atts(['height' => '700px'], $atts, 'ai_chat_persona');
        // If bridge is present and ready, delegate fully
        if ($this->bridge_ready && function_exists('versace22_render_app')) {
            return versace22_render_app(['height' => $atts['height'], 'plugin' => 'ai-chat-persona-pro', 'version' => AI_CHAT_PERSONA_PRO_VERSION, 'instance' => $this]);
        }
        // STANDALONE FALLBACK: Minimal React root + inline loader
        $root_id = 'aicpp-standalone-root-' . uniqid();
        $height = esc_attr($atts['height']);
        $ajax_url = esc_url(admin_url('admin-ajax.php'));
        $nonce = wp_create_nonce('aicpp_chat');
        ob_start();
        ?>
        <div id="<?php echo esc_attr($root_id); ?>" style="width:100%;height:<?php echo $height; ?>;border:1px solid #ddd;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#fafafa;">
            <div style="text-align:center;color:#666;font-family:sans-serif;">
                <p><strong>AI Chat Persona Pro</strong></p>
                <p>Bridge not detected. Running in standalone mode.</p>
                <p style="font-size:12px;color:#999;">Install versace22-enqueue.php for full UI.</p>
            </div>
        </div>
        <script>
        (function(){
            var root = document.getElementById('<?php echo esc_js($root_id); ?>');
            window.aicppStandalone = window.aicppStandalone || {};
            window.aicppStandalone.ajaxUrl = '<?php echo $ajax_url; ?>';
            window.aicppStandalone.nonce = '<?php echo esc_js($nonce); ?>';
        })();
        </script>
        <?php
        return ob_get_clean();
    }


    public function ajax_update_profile() {
        check_ajax_referer('aicpp_chat', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please sign in.']);

        $user_id = get_current_user_id();
        $display_name = sanitize_text_field(wp_unslash($_POST['display_name'] ?? ''));
        $bio = sanitize_textarea_field(wp_unslash($_POST['bio'] ?? ''));
        $avatar = esc_url_raw(wp_unslash($_POST['avatar'] ?? '')); // avatar is a URL/data URI, not free text

        if ($display_name === '') wp_send_json_error(['message' => 'Display name is required.']);

        $updated = wp_update_user([
            'ID' => $user_id,
            'display_name' => wp_strip_all_tags($display_name),
            'description' => $bio,
        ]);

        if (is_wp_error($updated)) {
            wp_send_json_error(['message' => $updated->get_error_message()]);
        }

        update_user_meta($user_id, 'aicpp_avatar', $avatar);

        $user = get_userdata($user_id);

        wp_send_json_success([
            'message' => 'Profile updated.',
            'user' => [
                'user_id' => $user_id,
                'display_name' => $user->display_name ?: $user->user_login,
                'email' => $user->user_email,
                'bio' => $user->description ?: '',
                'avatar' => get_user_meta($user_id, 'aicpp_avatar', true) ?: '',
                'referral_code' => get_user_meta($user_id, 'aicpp_referral_code', true) ?: '',
                'points' => (int) get_user_meta($user_id, 'aicpp_reward_points', true),
            ]
        ]);
    }

    public function ajax_get_leaderboard() {
        check_ajax_referer('aicpp_chat', 'nonce');
        // FIX #6: Gate the leaderboard behind require_login so guests can't enumerate display names.
        if (get_option('aicpp_require_login', '1') === '1' && !is_user_logged_in()) {
            wp_send_json_error(['message' => 'Please sign in to view the leaderboard.']);
        }
        global $wpdb;

        $rows = $wpdb->get_results("
            SELECT u.ID, u.user_login, u.display_name,
                   COALESCE(CAST(um.meta_value AS UNSIGNED), 0) AS points
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um
              ON um.user_id = u.ID AND um.meta_key = 'aicpp_reward_points'
            ORDER BY points DESC, u.ID ASC
            LIMIT 20
        ");

        $leaderboard = [];
        foreach ($rows as $index => $row) {
            $rank = $index + 1;
            $badge = $rank === 1 ? 'Diamond' : ($rank <= 3 ? 'Platinum' : ($rank <= 5 ? 'Gold' : ($rank <= 10 ? 'Silver' : 'Bronze')));
            $leaderboard[] = [
                'rank' => $rank,
                'user_id' => (int) $row->ID,
                'username' => $row->display_name ?: $row->user_login,
                'points' => (int) $row->points,
                'badge' => $badge,
                'avatar' => get_user_meta($row->ID, 'aicpp_avatar', true) ?: '',
            ];
        }

        wp_send_json_success(['leaderboard' => $leaderboard]);
    }

    public function ajax_get_referral_data() {
        check_ajax_referer('aicpp_chat', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please sign in.']);
        global $wpdb;

        $user_id = get_current_user_id();
        $code = get_user_meta($user_id, 'aicpp_referral_code', true);

        if (!$code) {
            $code = strtoupper('VRS-' . $user_id . '-' . wp_generate_password(6, false, false));
            update_user_meta($user_id, 'aicpp_referral_code', $code);
        }

        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_referrals} WHERE referrer_user_id = %d",
            $user_id
        ));

        $points = (int) get_user_meta($user_id, 'aicpp_reward_points', true);

        wp_send_json_success([
            'referral_code' => $code,
            'referral_link' => add_query_arg('ref', rawurlencode($code), home_url('/')),
            'referred_count' => $count,
            'points' => $points,
        ]);
    }

    public function ajax_search_messages() {
        check_ajax_referer('aicpp_chat', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please sign in.']);
        global $wpdb;

        $user_id = get_current_user_id();
        $query = sanitize_text_field(wp_unslash($_POST['query'] ?? ''));

        if (mb_strlen($query) < 2) {
            wp_send_json_error(['message' => 'Search must be at least 2 characters.']);
        }

        $like = '%' . $wpdb->esc_like($query) . '%';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT m.id, m.conversation_id, m.role, m.content, m.created_at, c.title
             FROM {$this->table_messages} m
             INNER JOIN {$this->table_conversations} c ON c.id = m.conversation_id
             WHERE c.user_id = %d
               AND (m.content LIKE %s OR m.raw_content LIKE %s)
             ORDER BY m.id DESC
             LIMIT 50",
            $user_id, $like, $like
        ), ARRAY_A);

        wp_send_json_success(['results' => $results ?: []]);
    }

    public function ajax_pin_conversation() {
        check_ajax_referer('aicpp_chat', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please sign in.']);
        global $wpdb;

        $user_id = get_current_user_id();
        $conv_id = intval($_POST['conversation_id'] ?? 0);

        $conv = $wpdb->get_row($wpdb->prepare(
            "SELECT id, user_id, pinned FROM {$this->table_conversations} WHERE id = %d",
            $conv_id
        ));

        if (!$conv) wp_send_json_error(['message' => 'Conversation not found.']);
        if ((int) $conv->user_id !== $user_id) wp_send_json_error(['message' => 'Access denied.']);

        $next = isset($_POST['pinned']) ? intval($_POST['pinned']) : ((int) $conv->pinned ? 0 : 1);

        $ok = $wpdb->update(
            $this->table_conversations,
            ['pinned' => $next],
            ['id' => $conv_id, 'user_id' => $user_id],
            ['%d'],
            ['%d', '%d']
        );

        if ($ok === false) wp_send_json_error(['message' => 'Could not update pin state.']);

        wp_send_json_success([
            'conversation_id' => $conv_id,
            'pinned' => (int) $next,
        ]);
    }

    public function ajax_speak() {
        check_ajax_referer('aicpp_chat', 'nonce');

        // FIX #6: Require login so unauthenticated bots can't burn the OpenAI TTS budget.
        if (get_option('aicpp_require_login', '1') === '1' && !is_user_logged_in()) {
            wp_send_json_error(['message' => 'Please sign in to use voice synthesis.']);
        }

        if (!$this->check_rate_limit('speak', 10, 300)) wp_send_json_error(['message' => 'Rate limit exceeded.']);

        // Cap input length up front; 2500 chars maps to roughly <1MB of MP3 at OpenAI's bitrate.
        $text = mb_substr(sanitize_textarea_field(wp_unslash($_POST['text'] ?? '')), 0, 2500);
        $voice = sanitize_text_field(wp_unslash($_POST['voice'] ?? 'alloy'));

        // FIX #6: Constrain voice to OpenAI's allowlist so we don't waste an API call on bad input.
        $allowed_voices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
        if (!in_array($voice, $allowed_voices, true)) $voice = 'alloy';

        if ($text === '') wp_send_json_error(['message' => 'Text is required.']);

        $key = $this->get_api_key('openai');
        if (!$key) wp_send_json_error(['message' => 'OpenAI API key required for speech.']);

        $response = wp_remote_post('https://api.openai.com/v1/audio/speech', [
            'headers' => [
                'Authorization' => 'Bearer ' . $key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'model' => 'gpt-4o-mini-tts',
                'voice' => $voice,
                'input' => $text,
                'response_format' => 'mp3',
            ]),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) wp_send_json_error(['message' => $response->get_error_message()]);

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code >= 300 || empty($body)) {
            wp_send_json_error(['message' => 'TTS failed.']);
        }

        // Hard cap audio response at 5MB to avoid memory blow-ups on huge synthesis.
        if (strlen($body) > 5 * 1024 * 1024) {
            wp_send_json_error(['message' => 'Generated audio is too large.']);
        }

        wp_send_json_success([
            'audio' => 'data:audio/mpeg;base64,' . base64_encode($body),
        ]);
    }


    // =========================================================
    // FEATURE 2: PERSISTENT MEMORY
    // =========================================================
    private function get_user_memory_block($user_id, $persona_id = 0) {
        $user_id = (int) $user_id;
        if (!$user_id) return '';
        global $wpdb;
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT memory_text FROM {$this->table_memories} WHERE user_id = %d AND enabled = 1 AND (persona_id = 0 OR persona_id = %d) ORDER BY id ASC LIMIT 50",
            $user_id, (int) $persona_id
        ));
        if (empty($rows)) return '';
        $lines = [];
        foreach ($rows as $r) {
            $r = trim(wp_strip_all_tags((string) $r));
            if ($r !== '') $lines[] = '- ' . $r;
        }
        return implode("\n", $lines);
    }

    public function page_memory() {
        if (!current_user_can('manage_options')) wp_die('Access denied');
        global $wpdb;
        $personas = $wpdb->get_results("SELECT id, name FROM {$this->table_personas} ORDER BY name ASC");
        ?>
        <div class="wrap aicpp-wrap">
            <h1>🧩 Persistent Memory</h1>
            <div class="aicpp-info">
                <strong>What this does:</strong> Each user has a small list of facts the AI remembers across all their conversations (e.g. "prefers Python", "lives in Lisbon"). Lines are injected into the system prompt under <code>## WHAT YOU REMEMBER ABOUT THE USER</code>. Memories can be global (apply to every persona) or scoped to a single persona.
            </div>
            <div class="aicpp-card">
                <h2>Find user</h2>
                <div class="aicpp-user-search">
                    <input type="text" id="mem-user-search" placeholder="Search by username, email, or display name...">
                    <button type="button" onclick="aicppMemSearchUsers()">🔍 Search</button>
                </div>
                <div id="mem-user-results" class="aicpp-user-results" style="display:none"></div>
            </div>
            <div class="aicpp-card" id="mem-editor" style="display:none">
                <h2>Memories for <span id="mem-target-name"></span></h2>
                <input type="hidden" id="mem-target-uid" value="">
                <p>
                    <label>Scope:
                        <select id="mem-scope">
                            <option value="0">Global (all personas)</option>
                            <?php foreach ($personas as $p): ?>
                                <option value="<?php echo (int) $p->id; ?>"><?php echo esc_html($p->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </p>
                <p>
                    <input type="text" id="mem-text" class="regular-text" maxlength="500" placeholder="e.g. Prefers concise answers in Portuguese" style="width:60%">
                    <button type="button" class="button button-primary" onclick="aicppMemAdd()">➕ Add memory</button>
                </p>
                <div id="mem-list"><div class="aicpp-empty">Loading…</div></div>
            </div>
        </div>
        <script>
        (function(){
            var ajaxurl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            var nonce   = <?php echo wp_json_encode(wp_create_nonce('aicpp')); ?>;
            function esc(t){ var d=document.createElement('div'); d.textContent=t==null?'':t; return d.innerHTML; }
            window.aicppMemSearchUsers = function(){
                var q = document.getElementById('mem-user-search').value.trim();
                if (q.length < 2) { alert('Enter at least 2 characters'); return; }
                var fd = new FormData(); fd.append('action','aicpp_search_users'); fd.append('nonce',nonce); fd.append('query',q);
                fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
                    var c = document.getElementById('mem-user-results'); c.style.display='block';
                    if (d.success && d.data.users.length){
                        c.innerHTML = d.data.users.map(u =>
                            '<div class="aicpp-user-row"><div class="user-info"><span class="user-name">'+esc(u.display_name)+'</span><span class="user-email">'+esc(u.user_email)+'</span></div>'+
                            '<button type="button" class="btn-assign" onclick="aicppMemPick('+u.ID+', \''+esc(u.display_name||u.user_login).replace(/\'/g,"\\\\'")+'\')">Edit memories</button></div>'
                        ).join('');
                    } else c.innerHTML = '<div class="aicpp-empty">No users found</div>';
                });
            };
            window.aicppMemPick = function(uid, name){
                document.getElementById('mem-editor').style.display='block';
                document.getElementById('mem-target-uid').value = uid;
                document.getElementById('mem-target-name').textContent = name;
                aicppMemReload();
            };
            function aicppMemReload(){
                var uid = document.getElementById('mem-target-uid').value;
                var fd = new FormData(); fd.append('action','aicpp_get_memories'); fd.append('nonce',nonce); fd.append('user_id',uid);
                fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
                    var box = document.getElementById('mem-list');
                    if (!d.success || !d.data.memories.length){ box.innerHTML='<div class="aicpp-empty">No memories yet</div>'; return; }
                    box.innerHTML = '<div class="aicpp-user-results">' + d.data.memories.map(m =>
                        '<div class="aicpp-user-row"><div class="user-info">'+
                        '<span class="user-name">'+(m.enabled==1?'':'(disabled) ')+esc(m.memory_text)+'</span>'+
                        '<span class="user-email">Scope: '+(parseInt(m.persona_id)===0?'Global':'Persona #'+m.persona_id)+'</span></div>'+
                        '<div><button class="aicpp-convo-btn" onclick="aicppMemToggle('+m.id+')">'+(m.enabled==1?'Disable':'Enable')+'</button> '+
                        '<button class="btn-remove" onclick="aicppMemDel('+m.id+')">Delete</button></div></div>'
                    ).join('') + '</div>';
                });
            }
            window.aicppMemAdd = function(){
                var uid = document.getElementById('mem-target-uid').value;
                var scope = document.getElementById('mem-scope').value;
                var text = document.getElementById('mem-text').value.trim();
                if (!uid || !text) return;
                var fd = new FormData(); fd.append('action','aicpp_add_memory'); fd.append('nonce',nonce);
                fd.append('user_id',uid); fd.append('persona_id',scope); fd.append('memory_text',text);
                fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
                    if (d.success){ document.getElementById('mem-text').value=''; aicppMemReload(); } else alert(d.data && d.data.message || 'Error');
                });
            };
            window.aicppMemDel = function(id){
                if (!confirm('Delete this memory?')) return;
                var fd = new FormData(); fd.append('action','aicpp_delete_memory'); fd.append('nonce',nonce); fd.append('memory_id',id);
                fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{ if(d.success) aicppMemReload(); });
            };
            window.aicppMemToggle = function(id){
                var fd = new FormData(); fd.append('action','aicpp_toggle_memory'); fd.append('nonce',nonce); fd.append('memory_id',id);
                fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{ if(d.success) aicppMemReload(); });
            };
        })();
        </script>
        <?php
    }

    public function ajax_get_memories() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $uid = intval($_POST['user_id'] ?? 0);
        if ($uid <= 0) wp_send_json_error(['message' => 'Invalid user']);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, persona_id, memory_text, enabled FROM {$this->table_memories} WHERE user_id = %d ORDER BY id ASC",
            $uid
        ));
        wp_send_json_success(['memories' => $rows ?: []]);
    }
    public function ajax_add_memory() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $uid = intval($_POST['user_id'] ?? 0);
        $pid = intval($_POST['persona_id'] ?? 0);
        $text = mb_substr(sanitize_text_field(wp_unslash($_POST['memory_text'] ?? '')), 0, 500);
        if (!$uid || $text === '') wp_send_json_error(['message' => 'Missing data']);
        $ok = $wpdb->insert($this->table_memories, [
            'user_id' => $uid, 'persona_id' => $pid, 'memory_text' => $text, 'enabled' => 1,
        ], ['%d','%d','%s','%d']);
        if ($ok === false) wp_send_json_error(['message' => 'DB error']);
        wp_send_json_success(['id' => $wpdb->insert_id]);
    }
    public function ajax_update_memory() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $id = intval($_POST['memory_id'] ?? 0);
        $text = mb_substr(sanitize_text_field(wp_unslash($_POST['memory_text'] ?? '')), 0, 500);
        if (!$id || $text === '') wp_send_json_error(['message' => 'Missing data']);
        $wpdb->update($this->table_memories, ['memory_text' => $text], ['id' => $id], ['%s'], ['%d']);
        wp_send_json_success();
    }
    public function ajax_delete_memory() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $id = intval($_POST['memory_id'] ?? 0);
        $wpdb->delete($this->table_memories, ['id' => $id], ['%d']);
        wp_send_json_success();
    }
    public function ajax_toggle_memory() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $id = intval($_POST['memory_id'] ?? 0);
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_memories} SET enabled = 1 - enabled WHERE id = %d", $id
        ));
        wp_send_json_success();
    }

    // =========================================================
    // FEATURE 3: PROJECTS
    // =========================================================
    private function get_project_context_block($conv_id) {
        $conv_id = (int) $conv_id;
        if (!$conv_id) return '';
        global $wpdb;
        $project_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT project_id FROM {$this->table_conversations} WHERE id = %d", $conv_id
        ));
        if ($project_id <= 0) return '';
        $proj = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_projects} WHERE id = %d", $project_id
        ));
        if (!$proj) return '';
        $out = "Project: {$proj->name}";
        if (!empty(trim((string) $proj->description))) $out .= "\nDescription: " . trim($proj->description);
        if (!empty(trim((string) $proj->custom_instructions))) {
            $out .= "\n\nCustom instructions for this project:\n" . trim($proj->custom_instructions);
        }
        $files = $wpdb->get_results($wpdb->prepare(
            "SELECT file_name, content_excerpt FROM {$this->table_project_files} WHERE project_id = %d ORDER BY id ASC LIMIT 20",
            $project_id
        ));
        if ($files) {
            $out .= "\n\nKnowledge base files (excerpts):";
            foreach ($files as $f) {
                $excerpt = trim((string) $f->content_excerpt);
                if ($excerpt === '') continue;
                $out .= "\n\n--- " . $f->file_name . " ---\n" . mb_substr($excerpt, 0, 4000);
            }
        }
        return mb_substr($out, 0, 50000);
    }

    public function page_projects() {
        if (!current_user_can('manage_options')) wp_die('Access denied');
        global $wpdb;
        $user_id = get_current_user_id();
        $projects = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_projects} WHERE user_id = %d ORDER BY id DESC", $user_id
        ));
        ?>
        <div class="wrap aicpp-wrap">
            <h1>📁 Projects</h1>
            <div class="aicpp-info">
                <strong>What this does:</strong> Group conversations into a project that shares a knowledge base (uploaded text/markdown excerpts) and custom instructions. Every chat assigned to the project sees them automatically under <code>## PROJECT CONTEXT</code>.
            </div>
            <div class="aicpp-card">
                <h2>Create a project</h2>
                <p><input type="text" id="prj-name" class="regular-text" placeholder="Project name" style="width:40%"></p>
                <p><textarea id="prj-desc" rows="2" class="large-text" placeholder="Short description"></textarea></p>
                <p><textarea id="prj-instr" rows="4" class="large-text" placeholder="Custom instructions for this project (e.g. 'Always answer in TypeScript', 'Use the brand voice from the file')"></textarea></p>
                <p><button class="button button-primary" onclick="aicppPrjCreate()">➕ Create project</button></p>
            </div>
            <div class="aicpp-personas">
                <?php foreach ($projects as $p): ?>
                    <div class="aicpp-pcard" data-pid="<?php echo (int)$p->id; ?>">
                        <h3 style="margin:0"><?php echo esc_html($p->name); ?></h3>
                        <p style="color:#666;font-size:13px"><?php echo esc_html($p->description); ?></p>
                        <p style="font-size:12px;color:#888">Custom instructions: <?php echo esc_html(mb_substr($p->custom_instructions, 0, 120)); ?>…</p>
                        <p>
                            <button class="button" onclick="aicppPrjFiles(<?php echo (int)$p->id; ?>, '<?php echo esc_js($p->name); ?>')">📎 Knowledge files</button>
                            <button class="button" onclick="aicppPrjDelete(<?php echo (int)$p->id; ?>)" style="color:#c92a2a">🗑 Delete</button>
                        </p>
                        <div class="aicpp-prj-files" id="prj-files-<?php echo (int)$p->id; ?>" style="display:none">
                            <h4>Knowledge files</h4>
                            <p>
                                <input type="text" placeholder="File name (e.g. brand-guide.md)" id="prj-fname-<?php echo (int)$p->id; ?>" style="width:40%">
                                <textarea rows="4" class="large-text" placeholder="Paste excerpt content (max 60000 chars)" id="prj-fcontent-<?php echo (int)$p->id; ?>"></textarea>
                                <button class="button button-primary" onclick="aicppPrjAttach(<?php echo (int)$p->id; ?>)">Attach</button>
                            </p>
                            <div id="prj-flist-<?php echo (int)$p->id; ?>"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
        (function(){
            var ajaxurl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            var nonce   = <?php echo wp_json_encode(wp_create_nonce('aicpp')); ?>;
            function esc(t){ var d=document.createElement('div'); d.textContent=t==null?'':t; return d.innerHTML; }
            window.aicppPrjCreate = function(){
                var fd = new FormData(); fd.append('action','aicpp_create_project'); fd.append('nonce',nonce);
                fd.append('name', document.getElementById('prj-name').value);
                fd.append('description', document.getElementById('prj-desc').value);
                fd.append('custom_instructions', document.getElementById('prj-instr').value);
                fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
                    if (d.success) location.reload(); else alert(d.data.message||'Error');
                });
            };
            window.aicppPrjDelete = function(id){
                if (!confirm('Delete project? Conversations stay but lose project context.')) return;
                var fd = new FormData(); fd.append('action','aicpp_delete_project'); fd.append('nonce',nonce); fd.append('project_id',id);
                fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); });
            };
            window.aicppPrjFiles = function(id, name){
                var box = document.getElementById('prj-files-'+id);
                box.style.display = (box.style.display==='none')?'block':'none';
                if (box.style.display==='block') aicppPrjReloadFiles(id);
            };
            function aicppPrjReloadFiles(id){
                var fd = new FormData(); fd.append('action','aicpp_get_projects'); fd.append('nonce',nonce); fd.append('project_id',id);
                fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
                    if (!d.success) return;
                    var list = (d.data.files || []);
                    var box = document.getElementById('prj-flist-'+id);
                    box.innerHTML = list.length ? list.map(f =>
                        '<div class="aicpp-user-row"><span class="user-name">'+esc(f.file_name)+'</span>'+
                        '<button class="btn-remove" onclick="aicppPrjDetach('+f.id+','+id+')">Remove</button></div>'
                    ).join('') : '<div class="aicpp-empty">No files</div>';
                });
            }
            window.aicppPrjAttach = function(id){
                var fd = new FormData(); fd.append('action','aicpp_attach_project_file'); fd.append('nonce',nonce);
                fd.append('project_id', id);
                fd.append('file_name', document.getElementById('prj-fname-'+id).value);
                fd.append('content_excerpt', document.getElementById('prj-fcontent-'+id).value);
                fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
                    if (d.success){
                        document.getElementById('prj-fname-'+id).value='';
                        document.getElementById('prj-fcontent-'+id).value='';
                        aicppPrjReloadFiles(id);
                    } else alert(d.data.message||'Error');
                });
            };
            window.aicppPrjDetach = function(fileId, projectId){
                if (!confirm('Detach this file?')) return;
                var fd = new FormData(); fd.append('action','aicpp_detach_project_file'); fd.append('nonce',nonce); fd.append('file_id',fileId);
                fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{ if(d.success) aicppPrjReloadFiles(projectId); });
            };
        })();
        </script>
        <?php
    }

    public function ajax_get_projects() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $project_id = intval($_POST['project_id'] ?? 0);
        if ($project_id > 0) {
            $files = $wpdb->get_results($wpdb->prepare(
                "SELECT id, file_name, file_type FROM {$this->table_project_files} WHERE project_id = %d ORDER BY id ASC",
                $project_id
            ));
            wp_send_json_success(['files' => $files ?: []]);
        }
        $user_id = get_current_user_id();
        $projects = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, description FROM {$this->table_projects} WHERE user_id = %d ORDER BY id DESC", $user_id
        ));
        wp_send_json_success(['projects' => $projects ?: []]);
    }
    public function ajax_create_project() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        if ($name === '') wp_send_json_error(['message' => 'Name required']);
        $wpdb->insert($this->table_projects, [
            'user_id' => get_current_user_id(),
            'name' => $name,
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'custom_instructions' => mb_substr((string) wp_unslash($_POST['custom_instructions'] ?? ''), 0, 50000),
            'color' => '#667eea',
        ], ['%d','%s','%s','%s','%s']);
        wp_send_json_success(['id' => $wpdb->insert_id]);
    }
    public function ajax_update_project() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $id = intval($_POST['project_id'] ?? 0);
        if (!$id) wp_send_json_error(['message' => 'Invalid id']);
        $wpdb->update($this->table_projects, [
            'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'custom_instructions' => mb_substr((string) wp_unslash($_POST['custom_instructions'] ?? ''), 0, 50000),
        ], ['id' => $id], ['%s','%s','%s'], ['%d']);
        wp_send_json_success();
    }
    public function ajax_delete_project() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $id = intval($_POST['project_id'] ?? 0);
        $wpdb->delete($this->table_project_files, ['project_id' => $id], ['%d']);
        $wpdb->delete($this->table_projects, ['id' => $id], ['%d']);
        $wpdb->update($this->table_conversations, ['project_id' => 0], ['project_id' => $id], ['%d'], ['%d']);
        wp_send_json_success();
    }
    public function ajax_attach_project_file() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $pid = intval($_POST['project_id'] ?? 0);
        $fname = sanitize_text_field(wp_unslash($_POST['file_name'] ?? ''));
        $content = mb_substr((string) wp_unslash($_POST['content_excerpt'] ?? ''), 0, 60000);
        if (!$pid || $fname === '') wp_send_json_error(['message' => 'Missing data']);
        $wpdb->insert($this->table_project_files, [
            'project_id' => $pid,
            'file_name' => $fname,
            'file_url' => esc_url_raw(wp_unslash($_POST['file_url'] ?? '')),
            'file_type' => sanitize_text_field(wp_unslash($_POST['file_type'] ?? 'text/plain')),
            'content_excerpt' => $content,
        ], ['%d','%s','%s','%s','%s']);
        wp_send_json_success(['id' => $wpdb->insert_id]);
    }
    public function ajax_detach_project_file() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        global $wpdb;
        $id = intval($_POST['file_id'] ?? 0);
        $wpdb->delete($this->table_project_files, ['id' => $id], ['%d']);
        wp_send_json_success();
    }
    public function ajax_assign_conversation_project() {
        check_ajax_referer('aicpp_chat', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please sign in.']);
        global $wpdb;
        $user_id = get_current_user_id();
        $conv_id = intval($_POST['conversation_id'] ?? 0);
        $project_id = intval($_POST['project_id'] ?? 0);
        $conv = $wpdb->get_row($wpdb->prepare("SELECT id, user_id FROM {$this->table_conversations} WHERE id = %d", $conv_id));
        if (!$conv || (int)$conv->user_id !== $user_id) wp_send_json_error(['message' => 'Access denied']);
        if ($project_id > 0) {
            $owner = (int) $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$this->table_projects} WHERE id = %d", $project_id));
            if ($owner !== $user_id && !current_user_can('manage_options')) wp_send_json_error(['message' => 'Project not yours']);
        }
        $wpdb->update($this->table_conversations, ['project_id' => $project_id], ['id' => $conv_id], ['%d'], ['%d']);
        wp_send_json_success();
    }

    // =========================================================
    // FEATURE 4: ARTIFACTS / CANVAS
    // =========================================================
    /**
     * Upgrade F: pull <remember>...</remember> tags out of a reply, save them,
     * and return the reply with those tags removed so the user never sees them.
     */
    private function extract_and_save_memories($reply, $user_id, $persona_id = 0) {
        $user_id = (int) $user_id;
        if (!$user_id || empty($reply)) return (string) $reply;
        $reply = (string) $reply;
        if (preg_match_all('#<remember>(.+?)</remember>#is', $reply, $m)) {
            global $wpdb;
            foreach ($m[1] as $fact) {
                $fact = mb_substr(trim(wp_strip_all_tags($fact)), 0, 500);
                if ($fact === '') continue;
                // Avoid duplicates for the same user.
                $exists = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_memories} WHERE user_id = %d AND memory_text = %s",
                    $user_id, $fact
                ));
                if ($exists) continue;
                $wpdb->insert($this->table_memories, [
                    'user_id'     => $user_id,
                    'persona_id'  => (int) $persona_id,
                    'memory_text' => $fact,
                    'enabled'     => 1,
                ], ['%d', '%d', '%s', '%d']);
            }
            // Strip the tags from the visible reply.
            $reply = preg_replace('#\s*<remember>.+?</remember>\s*#is', '', $reply);
        }
        return $reply;
    }

    private function extract_and_save_artifacts($reply, $conv_id, $user_id) {
        if (empty($reply) || !$conv_id) return;
        $reply = (string) $reply;
        if (preg_match_all('#<artifact\s+type="([a-zA-Z0-9_-]+)"(?:\s+title="([^"]*)")?>(.+?)</artifact>#is', $reply, $m, PREG_SET_ORDER)) {
            global $wpdb;
            foreach ($m as $hit) {
                $type = strtolower(sanitize_key($hit[1]));
                $title = isset($hit[2]) ? sanitize_text_field($hit[2]) : 'Artifact';
                $content = trim($hit[3]);
                if ($content === '') continue;
                $allowed_types = ['html','css','js','svg','markdown','code','react'];
                if (!in_array($type, $allowed_types, true)) $type = 'code';
                $wpdb->insert($this->table_artifacts, [
                    'conversation_id' => (int) $conv_id,
                    'user_id'         => (int) $user_id,
                    'title'           => mb_substr($title, 0, 255),
                    'artifact_type'   => $type,
                    'content'         => mb_substr($content, 0, 500000),
                    'version'         => 1,
                ], ['%d','%d','%s','%s','%s','%d']);
            }
        }
    }

    public function page_artifacts() {
        if (!current_user_can('manage_options')) wp_die('Access denied');
        global $wpdb;
        $rows = $wpdb->get_results("SELECT a.*, c.title AS conv_title FROM {$this->table_artifacts} a LEFT JOIN {$this->table_conversations} c ON a.conversation_id = c.id ORDER BY a.id DESC LIMIT 200");
        ?>
        <div class="wrap aicpp-wrap">
            <h1>🧱 Artifacts</h1>
            <div class="aicpp-info">
                Artifacts are auto-extracted from any AI reply that contains <code>&lt;artifact type="..."&gt;...&lt;/artifact&gt;</code>. The frontend renders each artifact in a side panel with Preview / Edit tabs.
            </div>
            <div class="aicpp-personas">
                <?php foreach ($rows as $a): ?>
                    <div class="aicpp-pcard">
                        <h3 style="margin:0"><?php echo esc_html($a->title ?: 'Untitled'); ?> <span class="aicpp-badge"><?php echo esc_html($a->artifact_type); ?></span></h3>
                        <p style="color:#888;font-size:12px">From: <?php echo esc_html($a->conv_title ?: '#' . $a->conversation_id); ?> · v<?php echo (int)$a->version; ?> · <?php echo esc_html($a->updated_at); ?></p>
                        <pre style="max-height:180px;overflow:auto;background:#1e1e1e;color:#50fa7b;padding:10px;border-radius:6px;font-size:11px"><?php echo esc_html(mb_substr($a->content, 0, 1500)); ?></pre>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function ajax_save_artifact() {
        check_ajax_referer('aicpp_chat', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please sign in']);
        global $wpdb;
        $user_id = get_current_user_id();
        $id      = intval($_POST['artifact_id'] ?? 0);
        $type    = sanitize_key($_POST['artifact_type'] ?? 'code');
        $title   = sanitize_text_field(wp_unslash($_POST['title'] ?? 'Artifact'));
        $content = mb_substr((string) wp_unslash($_POST['content'] ?? ''), 0, 500000);
        $conv_id = intval($_POST['conversation_id'] ?? 0);
        $allowed = ['html','css','js','svg','markdown','code','react'];
        if (!in_array($type, $allowed, true)) $type = 'code';
        if ($id > 0) {
            $owner = (int) $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$this->table_artifacts} WHERE id = %d", $id));
            if ($owner !== $user_id && !current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied']);
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->table_artifacts} SET title=%s, artifact_type=%s, content=%s, version=version+1, updated_at=%s WHERE id=%d",
                $title, $type, $content, current_time('mysql'), $id
            ));
            wp_send_json_success(['id' => $id]);
        }
        $wpdb->insert($this->table_artifacts, [
            'conversation_id' => $conv_id,
            'user_id'         => $user_id,
            'title'           => $title,
            'artifact_type'   => $type,
            'content'         => $content,
            'version'         => 1,
        ], ['%d','%d','%s','%s','%s','%d']);
        wp_send_json_success(['id' => $wpdb->insert_id]);
    }
    public function ajax_get_artifact() {
        check_ajax_referer('aicpp_chat', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please sign in']);
        global $wpdb;
        $id = intval($_POST['artifact_id'] ?? 0);
        $a = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_artifacts} WHERE id = %d", $id), ARRAY_A);
        if (!$a) wp_send_json_error(['message' => 'Not found']);
        if ((int)$a['user_id'] !== get_current_user_id() && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied']);
        }
        wp_send_json_success($a);
    }
    public function ajax_list_artifacts() {
        check_ajax_referer('aicpp_chat', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please sign in']);
        global $wpdb;
        $conv_id = intval($_POST['conversation_id'] ?? 0);
        $user_id = get_current_user_id();
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, artifact_type, version, updated_at FROM {$this->table_artifacts} WHERE conversation_id = %d AND user_id = %d ORDER BY id DESC",
            $conv_id, $user_id
        ));
        wp_send_json_success(['artifacts' => $rows ?: []]);
    }
    public function ajax_delete_artifact() {
        check_ajax_referer('aicpp_chat', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Please sign in']);
        global $wpdb;
        $id = intval($_POST['artifact_id'] ?? 0);
        $owner = (int) $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$this->table_artifacts} WHERE id = %d", $id));
        if ($owner !== get_current_user_id() && !current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied']);
        $wpdb->delete($this->table_artifacts, ['id' => $id], ['%d']);
        wp_send_json_success();
    }

    // =========================================================
    // FEATURE 6: OPENROUTER FREE MODEL PRESETS
    // =========================================================
    private function aicpp_or_default_free_models() {
        return [
            'openrouter/tencent/hy3-preview:free' => 'Tencent: Hy3 Preview (free)',
            'openrouter/nvidia/nemotron-3-super-120b-a12b:free' => 'NVIDIA: Nemotron 3 Super 120B (free)',
            'openrouter/nvidia/nemotron-3-nano-30b-a3b:free' => 'NVIDIA: Nemotron 3 Nano 30B (free)',
            'openrouter/nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free' => 'NVIDIA: Nemotron 3 Nano Omni 30B (free)',
            'openrouter/nvidia/nemotron-nano-12b-v2-vl:free' => 'NVIDIA: Nemotron Nano 12B VL (free)',
            'openrouter/nvidia/nemotron-nano-9b-v2:free' => 'NVIDIA: Nemotron Nano 9B V2 (free)',
            'openrouter/inclusionai/ling-2.6-1t:free' => 'inclusionAI: Ling-2.6-1T (free)',
            'openrouter/openai/gpt-oss-120b:free' => 'OpenAI: gpt-oss-120b (free)',
            'openrouter/openai/gpt-oss-20b:free' => 'OpenAI: gpt-oss-20b (free)',
            'openrouter/minimax/minimax-m2.5:free' => 'MiniMax: M2.5 (free)',
            'openrouter/z-ai/glm-4.5-air:free' => 'Z.ai: GLM 4.5 Air (free)',
            'openrouter/qwen/qwen3-next-80b-a3b-instruct:free' => 'Qwen3 Next 80B A3B Instruct (free)',
            'openrouter/qwen/qwen3-coder:free' => 'Qwen3 Coder 480B (free)',
            'openrouter/google/gemma-4-26b-a4b-it:free' => 'Google: Gemma 4 26B A4B (free)',
            'openrouter/google/gemma-4-31b-it:free' => 'Google: Gemma 4 31B (free)',
            'openrouter/google/gemma-3-27b-it:free' => 'Google: Gemma 3 27B (free)',
            'openrouter/google/gemma-3-12b-it:free' => 'Google: Gemma 3 12B (free)',
            'openrouter/google/gemma-3-4b-it:free' => 'Google: Gemma 3 4B (free)',
            'openrouter/google/gemma-3n-e4b-it:free' => 'Google: Gemma 3n E4B (free)',
            'openrouter/google/gemma-3n-e2b-it:free' => 'Google: Gemma 3n E2B (free)',
            'openrouter/poolside/laguna-xs.2:free' => 'Poolside: Laguna XS.2 (free)',
            'openrouter/poolside/laguna-m.1:free' => 'Poolside: Laguna M.1 (free)',
            'openrouter/baidu/qianfan-ocr-fast:free' => 'Baidu: Qianfan OCR Fast (free)',
            'openrouter/meta-llama/llama-3.3-70b-instruct:free' => 'Meta: Llama 3.3 70B (free)',
            'openrouter/meta-llama/llama-3.2-3b-instruct:free' => 'Meta: Llama 3.2 3B (free)',
            'openrouter/nousresearch/hermes-3-llama-3.1-405b:free' => 'Nous: Hermes 3 Llama 3.1 405B (free)',
            'openrouter/liquid/lfm-2.5-1.2b-thinking:free' => 'LiquidAI: LFM 2.5 1.2B Thinking (free)',
            'openrouter/liquid/lfm-2.5-1.2b-instruct:free' => 'LiquidAI: LFM 2.5 1.2B Instruct (free)',
            'openrouter/cognitivecomputations/dolphin-mistral-24b-venice-edition:free' => 'Venice: Uncensored (free)',
            'openrouter/openrouter/free' => 'OpenRouter: Free Auto Router',
        ];
    }

    public function ajax_or_free_models() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Forbidden'], 403);
        $cached = get_transient('aicpp_or_free_models_cache');
        if (empty($cached)) $cached = get_option('aicpp_or_free_models_cache', '');
        if (!empty($cached)) {
            $list = is_array($cached) ? $cached : json_decode($cached, true);
            if (is_array($list) && !empty($list)) wp_send_json_success(['models' => $list]);
        }
        wp_send_json_success(['models' => $this->aicpp_or_default_free_models()]);
    }
    public function ajax_or_refresh_free() {
        check_ajax_referer('aicpp', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Forbidden'], 403);
        $r = wp_remote_get('https://openrouter.ai/api/v1/models', ['timeout' => 30]);
        if (is_wp_error($r)) wp_send_json_error(['message' => $r->get_error_message()]);
        $b = json_decode(wp_remote_retrieve_body($r), true);
        if (empty($b['data']) || !is_array($b['data'])) wp_send_json_error(['message' => 'Unexpected response']);
        $list = [];
        foreach ($b['data'] as $m) {
            $id = $m['id'] ?? '';
            if (!$id) continue;
            $is_free = false;
            if (substr($id, -5) === ':free') $is_free = true;
            if (!$is_free && isset($m['pricing']['prompt']) && (float)$m['pricing']['prompt'] === 0.0
                && isset($m['pricing']['completion']) && (float)$m['pricing']['completion'] === 0.0) $is_free = true;
            if (!$is_free) continue;
            $key = 'openrouter/' . $id;
            $list[$key] = $m['name'] ?? $id;
        }
        if (empty($list)) wp_send_json_error(['message' => 'No free models returned']);
        update_option('aicpp_or_free_models_cache', wp_json_encode($list), false);
        set_transient('aicpp_or_free_models_cache', $list, 12 * HOUR_IN_SECONDS);
        wp_send_json_success(['models' => $list, 'count' => count($list)]);
    }

}

AI_Chat_Persona_Pro_Ultimate::get_instance();
