<?php
/**
 * Plugin Name: WooCommerce PIX Payment
 * Plugin URI: https://github.com/abistec/woo-pix-payment
 * Description: Gateway de pagamento PIX para WooCommerce com integração Supabase
 * Version: 1.0.0
 * Author: Abistec
 * Author URI: https://abistec.com.br
 * Text Domain: woo-pix-payment
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * 
 * @package WooCommerce_PIX_Payment
 */

// ================================
// 1. SEGURANÇA - PROTEÇÃO CONTRA ACESSO DIRETO
// ================================
defined('ABSPATH') || exit;

// ================================
// 2. DEFINIÇÃO DE CONSTANTES DO PLUGIN
// ================================
define('WOO_PIX_PAYMENT_VERSION', '1.0.0');
define('WOO_PIX_PAYMENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOO_PIX_PAYMENT_PLUGIN_PATH', plugin_dir_path(__FILE__));

// ================================
// 3. CONTROLE DE SITES PERMITIDOS (MULTISITE)
// ================================
// Array com IDs dos sites onde o plugin pode funcionar
// 
// SITE 2: pedidossimples.com.br/mercearia-luanda (ATIVO)
// SITE 3: pedidossimples.com.br/mercado1 (INATIVO - exemplo)
// SITE 4: pedidossimples.com.br/3g-luanda (INATIVO - exemplo)
// 
// PARA ADICIONAR NOVOS SITES:
// 1. Descubra o ID do site em Rede WordPress → Sites
// 2. Adicione o ID no array abaixo
// 3. Exemplo: [2, 3, 4] para ativar em 3 sites

define('WOO_PIX_ALLOWED_SITES', [2]); // ← APENAS SITE 2 ATIVO

// ================================
// 4. INICIALIZAÇÃO PRINCIPAL DO PLUGIN
// ================================
function woo_pix_payment_init() {
    // 4.1 VERIFICAÇÃO DE MULTISITE - Só carrega em sites permitidos
    $current_site_id = get_current_blog_id();
    $allowed_sites = defined('WOO_PIX_ALLOWED_SITES') ? WOO_PIX_ALLOWED_SITES : [];
    
    if (!in_array($current_site_id, $allowed_sites)) {
        return; // Não carrega o plugin em sites não permitidos
    }
    
    // 4.2 VERIFICAÇÃO DO WOOCOMMERCE - Só carrega se WooCommerce existir
    if (!class_exists('WooCommerce') || !class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'woo_pix_payment_woocommerce_missing_notice');
        return;
    }
    
// 4.3 INCLUSÃO DAS CLASSES PRINCIPAIS
require_once WOO_PIX_PAYMENT_PLUGIN_PATH . 'includes/class-wc-pix-gateway.php';
require_once WOO_PIX_PAYMENT_PLUGIN_PATH . 'includes/class-wc-pix-settings.php';
require_once WOO_PIX_PAYMENT_PLUGIN_PATH . 'includes/generate-qr-code.php';
require_once WOO_PIX_PAYMENT_PLUGIN_PATH . 'includes/class-wc-pix-multisite-settings.php';
    
    // 4.4 REGISTRO DO GATEWAY DE PAGAMENTO
    add_filter('woocommerce_payment_gateways', 'woo_pix_payment_add_gateway');
}

// ================================
// 5. REGISTRO DO HOOK DE INICIALIZAÇÃO
// ================================
// Usa woocommerce_loaded para garantir que WooCommerce está carregado primeiro
add_action('woocommerce_loaded', 'woo_pix_payment_init');

// ================================
// 6. FUNÇÃO PARA ADICIONAR O GATEWAY
// ================================
function woo_pix_payment_add_gateway($gateways) {
    $gateways[] = 'WC_PIX_Gateway';
    return $gateways;
}

// ================================
// 7. NOTIFICAÇÃO DE ERRO SE WOOCOMMERCE AUSENTE
// ================================
function woo_pix_payment_woocommerce_missing_notice() {
    echo '<div class="error"><p>';
    echo __('WooCommerce PIX Payment requer que o WooCommerce esteja instalado e ativo.', 'woo-pix-payment');
    echo '</p></div>';
}

// ================================
// 8. CARREGAMENTO DE CSS E JS (OPCIONAL)
// ================================
function enqueue_pix_styles() {
    wp_enqueue_style(
        'pix-styles',
        WOO_PIX_PAYMENT_PLUGIN_URL . 'assets/css/style.css',
        [],
        WOO_PIX_PAYMENT_VERSION
    );
}
add_action('wp_enqueue_scripts', 'enqueue_pix_styles');

function enqueue_pix_scripts() {
    wp_enqueue_script(
        'pix-scripts',
        WOO_PIX_PAYMENT_PLUGIN_URL . 'assets/js/script.js',
        [],
        WOO_PIX_PAYMENT_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_pix_scripts');

// ================================
// 9. ATIVAÇÃO E DESATIVAÇÃO DO PLUGIN
// ================================
function woo_pix_payment_activate() {
    // Configurações padrão apenas se não existirem
    if (false === get_option('woo_pix_payment_settings')) {
        $default_settings = array(
            'enabled' => 'yes',
            'title' => 'PIX',
            'description' => 'Pague via PIX',
            'pix_key' => '',
            'pix_key_type' => 'cpf',
            'discount' => 0,
            'instruction' => 'Escaneie o QR Code ou use a chave PIX para pagamento.'
        );
        add_option('woo_pix_payment_settings', $default_settings);
    }
}
register_activation_hook(__FILE__, 'woo_pix_payment_activate');

function woo_pix_payment_deactivate() {
    // Limpeza de agendamentos se necessário
    wp_clear_scheduled_hook('woo_pix_payment_daily_check');
}
register_deactivation_hook(__FILE__, 'woo_pix_payment_deactivate');

// ================================
// 10. SUPORTE A MULTISITE (APENAS NOTIFICAÇÃO)
// ================================
if (is_multisite()) {
    add_action('network_admin_notices', function () {
        echo '<div class="notice notice-info"><p>O plugin PIX Payment está configurado para funcionar apenas em sites específicos do Multisite.</p></div>';
    });
}