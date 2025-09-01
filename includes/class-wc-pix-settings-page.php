<?php
/**
 * Página de configurações do PIX para WooCommerce
 */

defined('ABSPATH') || exit;

class WC_PIX_Settings_Page extends WC_Settings_Page {
    
    public function __construct() {
        $this->id = 'pix_payment';
        $this->label = __('PIX', 'woo-pix-payment');
        
        parent::__construct();
    }
    
    /**
     * Configurações da página
     */
    public function get_settings() {
        $current_site_slug = WC_PIX_Settings::get_current_site_slug();
        
        return [
            [
                'title' => __('Configurações PIX - Site: ', 'woo-pix-payment') . $current_site_slug,
                'type' => 'title',
                'desc' => __('Configure os dados PIX específicos para este site.', 'woo-pix-payment'),
                'id' => 'pix_payment_options'
            ],
            [
                'title' => __('Chave PIX', 'woo-pix-payment'),
                'desc' => __('Chave PIX deste site (CPF, CNPJ, Email, etc.)', 'woo-pix-payment'),
                'id' => 'woo_pix_payment_settings[pix_key]',
                'default' => WC_PIX_Settings::get_setting('pix_key'),
                'type' => 'text',
                'desc_tip' => true
            ],
            [
                'title' => __('Tipo de Chave', 'woo-pix-payment'),
                'desc' => __('Tipo da chave PIX', 'woo-pix-payment'),
                'id' => 'woo_pix_payment_settings[pix_key_type]',
                'default' => WC_PIX_Settings::get_setting('pix_key_type'),
                'type' => 'select',
                'options' => [
                    'cpf' => __('CPF', 'woo-pix-payment'),
                    'cnpj' => __('CNPJ', 'woo-pix-payment'),
                    'email' => __('Email', 'woo-pix-payment'),
                    'phone' => __('Telefone', 'woo-pix-payment'),
                    'random' => __('Chave Aleatória', 'woo-pix-payment')
                ],
                'desc_tip' => true
            ],
            [
                'title' => __('Desconto PIX (%)', 'woo-pix-payment'),
                'desc' => __('Desconto para pagamentos via PIX', 'woo-pix-payment'),
                'id' => 'woo_pix_payment_settings[discount]',
                'default' => WC_PIX_Settings::get_setting('discount'),
                'type' => 'number',
                'custom_attributes' => [
                    'min' => '0',
                    'max' => '100',
                    'step' => '0.5'
                ],
                'desc_tip' => true
            ],
            [
                'title' => __('Nome do Mercador', 'woo-pix-payment'),
                'desc' => __('Nome exibido no QR Code PIX', 'woo-pix-payment'),
                'id' => 'woo_pix_payment_settings[merchant_name]',
                'default' => WC_PIX_Settings::get_setting('merchant_name'),
                'type' => 'text',
                'desc_tip' => true
            ],
            [
                'title' => __('Cidade do Mercador', 'woo-pix-payment'),
                'desc' => __('Cidade exibida no QR Code PIX', 'woo-pix-payment'),
                'id' => 'woo_pix_payment_settings[merchant_city]',
                'default' => WC_PIX_Settings::get_setting('merchant_city'),
                'type' => 'text',
                'desc_tip' => true
            ],
            [
                'title' => __('WhatsApp para Comprovante', 'woo-pix-payment'),
                'desc' => __('Número com DDD para receber comprovantes (ex: 552132727548)', 'woo-pix-payment'),
                'id' => 'woo_pix_payment_settings[whatsapp_number]',
                'default' => WC_PIX_Settings::get_setting('whatsapp_number'),
                'type' => 'text',
                'placeholder' => '552132727548',
                'desc_tip' => true
            ],
            [
                'type' => 'sectionend',
                'id' => 'pix_payment_options'
            ]
        ];
    }
}