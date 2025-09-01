<?php
/**
 * Plugin Name: WooCommerce PIX Payment
 * Description: Um plugin profissional para aceitar pagamentos via PIX com QR Code.
 * Version: 1.0
 * Author: Seu Nome
 */

// Protege contra acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Impede que o arquivo seja acessado diretamente via URL.
}

// ================================
// 1. INCLUSÃO DE ARQUIVOS EXTERNOS
// ================================

// Inclui a classe responsável pelo gateway de pagamento PIX
require_once plugin_dir_path(__FILE__) . 'includes/class-wc-pix-gateway.php';

// Inclui a função para gerar o QR Code usando Supabase
require_once plugin_dir_path(__FILE__) . 'includes/qr-code-generator.php';

// ================================
// 2. CARREGAMENTO DE CSS E JS
// ================================

// Função para carregar os estilos CSS (opcional)
function enqueue_pix_styles() {
    wp_enqueue_style(
        'pix-styles', // Identificador único para o estilo
        plugin_dir_url(__FILE__) . 'assets/css/style.css' // Caminho para o arquivo CSS
    );
}
add_action('wp_enqueue_scripts', 'enqueue_pix_styles'); // Adiciona a função ao hook de scripts

// Função para carregar os scripts JavaScript (opcional)
function enqueue_pix_scripts() {
    wp_enqueue_script(
        'pix-scripts', // Identificador único para o script
        plugin_dir_url(__FILE__) . 'assets/js/script.js', // Caminho para o arquivo JS
        array(),       // Dependências (nenhuma neste caso)
        null,          // Versão (null significa sem controle de cache)
        true           // Carregar no rodapé (true = footer, false = header)
    );
}
add_action('wp_enqueue_scripts', 'enqueue_pix_scripts'); // Adiciona a função ao hook de scripts

// ================================
// 3. SUPORTE AO MULTISITE
// ================================

// Verifica se o WordPress está configurado como Multisite
if (is_multisite()) {
    add_action('network_admin_notices', function () {
        echo '<div class="notice notice-info"><p>O plugin PIX Payment foi ativado em todos os sites do Multisite.</p></div>';
    });
}

// ================================
// 4. CONFIGURAÇÃO DO PLUGIN
// ================================

// Função para adicionar um campo personalizado para a chave PIX no WooCommerce
add_filter('woocommerce_get_settings_pages', 'add_pix_gateway_settings');
function add_pix_gateway_settings($settings) {
    $settings[] = include 'includes/class-wc-pix-settings.php';
    return $settings;
}