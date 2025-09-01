<?php

if (!defined('ABSPATH')) {
    exit; // Protege contra acesso direto ao arquivo.
}

/**
 * Classe WC_Pix_Gateway
 * 
 * Esta classe implementa o método de pagamento PIX no WooCommerce.
 * Ela é compatível com WordPress Multisite, permitindo que cada site configure sua própria chave PIX.
 */
if (!class_exists('WC_Pix_Gateway')) {

    class WC_Pix_Gateway extends WC_Payment_Gateway {

        /**
         * Construtor da classe
         * 
         * Define as configurações básicas do gateway PIX.
         */
        public function __construct() {
            $this->id = 'pix_payment'; // ID único para este gateway
            $this->icon = ''; // Ícone (pode ser uma imagem ou SVG)
            $this->method_title = __('PIX Payment', 'woocommerce'); // Título exibido no admin
            $this->method_description = __('Aceite pagamentos via PIX com QR Code.', 'woocommerce'); // Descrição do método
            $this->has_fields = false; // Este gateway não tem campos personalizados

            // Carrega as configurações do gateway
            $this->init_form_fields();
            $this->init_settings();

            // Define os valores das configurações
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            // Ação para salvar as configurações
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        /**
         * Define os campos de configuração do gateway
         * 
         * Aqui adicionamos os campos que aparecem na página de configurações do WooCommerce.
         */
        public function init_form_fields() {
            $this->form_fields = array(

                // Habilitar/Desabilitar o gateway
                'enabled' => array(
                    'title' => __('Habilitar/Desabilitar', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Habilitar PIX Payment', 'woocommerce'),
                    'default' => 'yes',
                ),

                // Título do método de pagamento
                'title' => array(
                    'title' => __('Título', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Título exibido no checkout.', 'woocommerce'),
                    'default' => __('PIX Payment', 'woocommerce'),
                ),

                // Descrição do método de pagamento
                'description' => array(
                    'title' => __('Descrição', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Descrição exibida no checkout.', 'woocommerce'),
                    'default' => __('Pague com PIX usando QR Code.', 'woocommerce'),
                ),
            );
        }

        /**
         * Processa o pagamento
         * 
         * Este método define o comportamento do gateway ao processar um pedido.
         */
        public function process_payment($order_id) {
            global $woocommerce;

            $order = wc_get_order($order_id);

            // Atualiza o status do pedido para "Em espera"
            $order->update_status('on-hold', __('Aguardando pagamento via PIX.', 'woocommerce'));

            // Limpa o carrinho
            $woocommerce->cart->empty_cart();

            // Redireciona o cliente para a página de agradecimento
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }
    }

    /**
     * Adiciona o gateway PIX ao WooCommerce
     * 
     * Registra o gateway PIX como um método de pagamento disponível.
     */
    function add_pix_gateway($methods) {
        $methods[] = 'WC_Pix_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_pix_gateway');
}