<?php

if (!defined('ABSPATH')) {
    exit; // Protege contra acesso direto ao arquivo.
}

/**
 * Função para gerar o QR Code do PIX usando a API do Supabase.
 * 
 * Esta função é modular e permite que cada site do Multisite tenha suas próprias configurações PIX.
 * Os dados são recuperados dinamicamente com base no site atual.
 */
function generate_pix_qr_code($order_id) {
    // Recupera o objeto do pedido
    $order = wc_get_order($order_id);
    $amount = $order->get_total();

    // ================================
    // 1. CONFIGURAÇÕES DO SITE ATUAL
    // ================================

    // Define as configurações específicas para cada site do Multisite
    $current_site_slug = get_bloginfo('name'); // Nome do site atual (ex: Mercearia Luanda)

    // Configurações PIX por site
    $pix_settings = array(
        'mercearia-luanda' => array(
            'key'   => '01083172778', // Chave PIX da Mercearia Luanda
            'name'  => 'Mercearia Luanda', // Nome da loja
            'city'  => 'Rio de Janeiro, RJ', // Cidade
        ),
        '3g-luanda' => array(
            'key'   => 'celulardabelinalva@gmail.com', // Chave PIX da 3G Luanda
            'name'  => '3G Luanda', // Nome da loja
            'city'  => 'Rio de Janeiro, RJ', // Cidade
        ),
        'outra-loja' => array(
            'key'   => '00000000', // Chave PIX da Outra Loja
            'name'  => 'Outra Loja', // Nome da loja
            'city'  => 'Rio de Janeiro, RJ', // Cidade
        ),
    );

    // Identifica o site atual e recupera as configurações correspondentes
    $site_slug = strtolower(str_replace(' ', '-', $current_site_slug)); // Converte o nome do site para slug
    if (isset($pix_settings[$site_slug])) {
        $pix_key = $pix_settings[$site_slug]['key'];
        $merchant_name = $pix_settings[$site_slug]['name'];
        $merchant_city = $pix_settings[$site_slug]['city'];
    } else {
        return 'Erro: Configurações PIX não encontradas para este site.';
    }

    // ================================
    // 2. GERAR O PAYLOAD PIX
    // ================================

    // Formato fixo do payload PIX conforme padrão do Banco Central
    $payload = "00020126580014br.gov.bcb.pix0136{$pix_key}5204000053039865405{$amount}5802BR59" . strlen($merchant_name) . "{$merchant_name}60" . strlen($merchant_city) . "{$merchant_city}62070503***6304";

    // ================================
    // 3. CHAMAR A API DO SUPABASE
    // ================================

    // URL da API do Supabase
    $api_url = "https://czzidhzzpqegfvvmdgno.supabase.co/functions/v1/generate-qrcode?payload=" . urlencode($payload);

    // Token público (anon) do Supabase
    $supabase_token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImN6emlkaHp6cHFlZ2Z2dm1kZ25vIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTI5NTIwMDMsImV4cCI6MjA2ODUyODAwM30.zK2iFp-b4e5vghpHgWGuOk0LooujlyU7kVm4sbM85m0';

    // Faz a requisição GET para a API do Supabase
    $response = wp_remote_get($api_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $supabase_token, // Autenticação com token anon
        ],
    ]);

    // Verifica se houve erro na requisição
    if (is_wp_error($response)) {
        return 'Erro ao gerar QR Code: ' . $response->get_error_message();
    }

    // Recupera o corpo da resposta
    $body = wp_remote_retrieve_body($response);

    // Retorna o QR Code gerado
    return $body;
}