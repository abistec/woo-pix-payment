<?php

if (!defined('ABSPATH')) {
    exit; // Protege contra acesso direto ao arquivo.
}

/**
 * Classe WC_Settings_Pix
 * 
 * Esta classe adiciona uma nova seção de configurações no WooCommerce para permitir
 * que cada site do Multisite configure sua própria chave PIX.
 */
if (!class_exists('WC_Settings_Pix')) {

    class WC_Settings_Pix extends WC_Settings_Page {

        /**
         * Construtor da classe
         * 
         * Define o ID e o rótulo da página de configurações.
         */
        public function __construct() {
            $this->id    = 'pix_payment'; // ID único para esta página de configurações
            $this->label = __('PIX Payment', 'woocommerce'); // Nome exibido no menu
            parent::__construct();
        }

        /**
         * Retorna as configurações desta página
         * 
         * Aqui definimos os campos que aparecerão na página de configurações.
         * Cada site do Multisite terá sua própria chave PIX configurada aqui.
         */
        public function get_settings() {
            // Identifica o site atual pelo slug do diretório
            $current_site_slug = strtolower(str_replace(' ', '-', get_bloginfo('name')));

            // Configurações padrão para cada site
            $default_pix_settings = array(
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

            // Recupera as configurações do site atual
            $current_site_settings = isset($default_pix_settings[$current_site_slug])
                ? $default_pix_settings[$current_site_slug]
                : array(
                    'key'   => '', // Chave PIX padrão (vazia)
                    'name'  => '', // Nome da loja padrão (vazio)
                    'city'  => '', // Cidade padrão (vazia)
                );

            // Define os campos de configuração
            $settings = array(

                // Título da seção
                'section_title' => array(
                    'name' => __('Configurações do PIX', 'woocommerce'), // Título da seção
                    'type' => 'title', // Tipo: título de seção
                    'desc' => '', // Descrição (opcional)
                    'id'   => 'wc_pix_settings_section', // ID único para esta seção
                ),

                // Campo para a Chave PIX
                'pix_key' => array(
                    'name'     => __('Chave PIX', 'woocommerce'), // Nome do campo
                    'type'     => 'text', // Tipo de campo: texto
                    'desc'     => __('Insira a chave PIX para este site.', 'woocommerce'), // Descrição do campo
                    'id'       => 'wc_pix_key', // ID único para este campo
                    'default'  => $current_site_settings['key'], // Valor padrão (chave PIX do site atual)
                ),

                // Campo para o Nome da Loja
                'merchant_name' => array(
                    'name'     => __('Nome da Loja', 'woocommerce'), // Nome do campo
                    'type'     => 'text', // Tipo de campo: texto
                    'desc'     => __('Insira o nome da loja para este site.', 'woocommerce'), // Descrição do campo
                    'id'       => 'wc_pix_merchant_name', // ID único para este campo
                    'default'  => $current_site_settings['name'], // Valor padrão (nome da loja do site atual)
                ),

                // Campo para a Cidade
                'merchant_city' => array(
                    'name'     => __('Cidade', 'woocommerce'), // Nome do campo
                    'type'     => 'text', // Tipo de campo: texto
                    'desc'     => __('Insira a cidade da loja para este site.', 'woocommerce'), // Descrição do campo
                    'id'       => 'wc_pix_merchant_city', // ID único para este campo
                    'default'  => $current_site_settings['city'], // Valor padrão (cidade do site atual)
                ),

                // Fim da seção
                'section_end' => array(
                    'type' => 'sectionend', // Fecha a seção
                    'id'   => 'wc_pix_settings_section', // ID correspondente ao início da seção
                ),
            );

            return apply_filters('woocommerce_get_settings_' . $this->id, $settings);
        }
    }
}