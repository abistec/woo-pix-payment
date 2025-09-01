<?php
/**
 * Configurações do PIX Payment para Multisite
 * Sistema modular baseado no slug do diretório de cada site
 */

defined('ABSPATH') || exit;

class WC_PIX_Settings {
    
    /**
     * Configurações padrão para cada site (baseado no slug)
     */
    private static $default_settings = [
        'mercearia-luanda' => [
            'pix_key' => '01083172778',
            'pix_key_type' => 'cpf',
            'whatsapp_number' => '552132727548', // ← AQUI
            'title' => 'PIX - Mercearia Luanda',
            'description' => 'Pague via PIX com 5% de desconto',
            'discount' => 5,
            'instruction' => 'Escaneie o QR Code ou use a chave PIX: 01083172778',
            'merchant_name' => 'Mercearia Luanda',
            'merchant_city' => 'Rio de Janeiro, RJ'
        ],
        '3g-luanda' => [
            'pix_key' => 'celulardabelinalva@gmail.com',
            'whatsapp_number' => '5521983496342', // ← AQUI
            'pix_key_type' => 'email', 
            'title' => 'PIX - 3G Luanda',
            'description' => 'Pagamento via PIX',
            'discount' => 0,
            'instruction' => 'Use a chave PIX: celulardabelinalva@gmail.com',
            'merchant_name' => '3G Luanda',
            'merchant_city' => 'Rio de Janeiro, RJ'
        ],
        // Template para novas lojas (descomente e adapte)
        /*
        'nova-loja' => [
            'pix_key' => '',
            'whatsapp_number' => '5521983496342', // ← AQUI
            'pix_key_type' => 'cpf',
            'title' => 'PIX',
            'description' => 'Pagamento via PIX',
            'discount' => 0,
            'instruction' => 'Escaneie o QR Code para pagamento',
            'merchant_name' => 'Nova Loja',
            'merchant_city' => 'Cidade, Estado'
        ]
        */
    ];
    
    /**
     * Inicializa o sistema de configurações
     */
    public static function init() {
        add_filter('woocommerce_get_settings_pages', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }
    
    /**
     * Adiciona página de configurações apenas se WooCommerce estiver carregado
     */
    public static function add_settings_page($settings) {
        if (class_exists('WC_Settings_Page')) {
            require_once dirname(__FILE__) . '/class-wc-pix-settings-page.php';
            $settings[] = new WC_PIX_Settings_Page();
        }
        return $settings;
    }
    
    /**
     * Registra as configurações
     */
    public static function register_settings() {
        register_setting('woocommerce_pix_settings', 'woo_pix_payment_settings');
    }
    
    /**
     * Obtém as configurações do site atual baseado no slug
     */
    public static function get_settings() {
        $current_site_slug = self::get_current_site_slug();
        $saved_settings = get_option('woo_pix_payment_settings', []);
        
        // Combina configurações padrão com as salvas (se existirem)
        $default_settings = isset(self::$default_settings[$current_site_slug]) 
            ? self::$default_settings[$current_site_slug] 
            : self::get_default_fallback();
        
        return wp_parse_args($saved_settings, $default_settings);
    }
    
    /**
     * Obtém uma configuração específica
     */
    public static function get_setting($key, $default = '') {
        $settings = self::get_settings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Obtém o slug do site atual
     */
    public static function get_current_site_slug() {
        $site_url = get_site_url();
        $path = parse_url($site_url, PHP_URL_PATH);
        return trim($path, '/');
    }
    
    /**
     * Configurações padrão de fallback
     */
    private static function get_default_fallback() {
        return [
            'pix_key' => '',
            'pix_key_type' => 'cpf',
            'whatsapp_number' => '', // ← AQUI
            'title' => 'PIX',
            'description' => 'Pagamento via PIX',
            'discount' => 0,
            'instruction' => 'Escaneie o QR Code para pagamento',
            'merchant_name' => get_bloginfo('name'),
            'merchant_city' => ''
        ];
    }
}

// Inicializa apenas se WooCommerce estiver ativo
if (class_exists('WooCommerce')) {
    WC_PIX_Settings::init();
}