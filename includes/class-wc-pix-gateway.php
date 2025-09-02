<?php
/**
 * Gateway de Pagamento PIX para WooCommerce
 * Fluxo manual via WhatsApp com chave PIX concatenada
 * Compatível com Multisite
 */

defined('ABSPATH') || exit;

if (!class_exists('WC_Pix_Gateway')) {

    class WC_Pix_Gateway extends WC_Payment_Gateway {

        /**
         * Construtor da classe
         * Configurações iniciais do gateway
         */
        public function __construct() {
            $this->id = 'pix_payment';
            $this->method_title = __('PIX Payment', 'woo-pix-payment');
            $this->method_description = __('Pagamento manual via PIX com chave enviada por WhatsApp.', 'woo-pix-payment');
            $this->has_fields = false;

            // Carrega configurações
            $this->init_form_fields();
            $this->init_settings();

            // Define valores das configurações
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');

            // Ação para salvar configurações
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            
            // Ação para adicionar conteúdo na thank you page
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        }

        /**
         * Define os campos de configuração do gateway
         * Campos exibidos nas configurações do WooCommerce (básicos, detalhes PIX na aba separada)
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Habilitar/Desabilitar', 'woo-pix-payment'),
                    'type' => 'checkbox',
                    'label' => __('Habilitar pagamento via PIX', 'woo-pix-payment'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Título', 'woo-pix-payment'),
                    'type' => 'text',
                    'description' => __('Título exibido no checkout.', 'woo-pix-payment'),
                    'default' => __('PIX', 'woo-pix-payment'),
                    'desc_tip' => true
                ),
                'description' => array(
                    'title' => __('Descrição', 'woo-pix-payment'),
                    'type' => 'textarea',
                    'description' => __('Descrição exibida no checkout.', 'woo-pix-payment'),
                    'default' => __('Pague via PIX manualmente com chave enviada por WhatsApp.', 'woo-pix-payment'),
                    'desc_tip' => true
                ),
                'whatsapp_number' => array(
                    'title' => __('WhatsApp para Comprovante', 'woo-pix-payment'),
                    'type' => 'text',
                    'description' => __('Número com DDD para receber comprovantes (ex: 552132727548). Sobrescrito pela aba PIX.', 'woo-pix-payment'),
                    'default' => '552132727548',
                    'placeholder' => '552132727548',
                    'desc_tip' => true
                )
            );
        }

        /**
         * Processa o pagamento - MÉTODO PRINCIPAL
         * Chamado quando o cliente finaliza o pedido
         */
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            
            // Marca como pendente (aguardando PIX)
            $order->update_status('pending', __('Aguardando pagamento PIX.', 'woo-pix-payment'));
            
            // Redireciona para thank you page
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }

        /**
         * Conteúdo da thank you page - MOSTRA OPÇÕES DE PAGAMENTO
         * Exibido após o cliente finalizar o pedido
         */
        public function thankyou_page($order_id) {
            static $rendered = false; // Flag para evitar duplicação no Fluid Checkout
            if ($rendered) {
                return;
            }
            $rendered = true;

            $order = wc_get_order($order_id);
            
            if ($order->get_payment_method() === $this->id) {
                echo '<div class="pix-payment-instructions">';
                echo '<h2>' . __('Pagamento via PIX', 'woo-pix-payment') . '</h2>';
                
                if ($order->get_status() === 'pending') {
                    $this->display_pix_options($order);
                }
                echo '</div>';
            }
        }

        /**
         * Exibe opções PIX para envio via WhatsApp
         * Usa dados da aba PIX e valor total ajustado por desconto
         */
private function display_pix_options($order) {
    // Puxa configs da aba PIX via WC_PIX_Settings
    $pix_key = WC_PIX_Settings::get_setting('pix_key');
    $pix_key_type = WC_PIX_Settings::get_setting('pix_key_type', 'CPF');
    $discount = floatval(WC_PIX_Settings::get_setting('discount', 0)) / 100; // Percentual de desconto
    $merchant_name = WC_PIX_Settings::get_setting('merchant_name');
    $merchant_city = WC_PIX_Settings::get_setting('merchant_city');
    $whatsapp_number = WC_PIX_Settings::get_setting('whatsapp_number');
    $amount = $order->get_total();
    $amount_with_discount = $amount * (1 - $discount); // Aplica desconto
    $order_number = $order->get_order_number();
    
    // Formata valor sem HTML
    $formatted_amount = 'R$ ' . number_format($amount_with_discount, 2, ',', '.');

    // Dados para exibição
    echo '<div class="pix-confirmation-data">';
    echo '<h3>' . __('Dados para Pagamento PIX', 'woo-pix-payment') . '</h3>';
    echo '<table>';
    echo '<tr><td><strong>' . __('Valor:', 'woo-pix-payment') . '</strong></td><td>' . $formatted_amount . '</td></tr>';
    echo '<tr><td><strong>' . __('Chave PIX:', 'woo-pix-payment') . '</strong></td><td>' . esc_html($pix_key) . '</td></tr>';
    echo '<tr><td><strong>' . __('Tipo:', 'woo-pix-payment') . '</strong></td><td>' . esc_html(strtoupper($pix_key_type)) . '</td></tr>';
    echo '<tr><td><strong>' . __('Beneficiário:', 'woo-pix-payment') . '</strong></td><td>' . esc_html($merchant_name) . '</td></tr>';
    echo '<tr><td><strong>' . __('Cidade:', 'woo-pix-payment') . '</strong></td><td>' . esc_html($merchant_city) . '</td></tr>';
    echo '</table>';
    echo '</div>';

    // Botão para copiar código PIX com valor
    $pix_code = $pix_key . " | Valor: " . str_replace('R$ ', '', $formatted_amount); // Formato sugerido
    echo '<div class="pix-copy-section">';
    echo '<input type="text" id="pix-copy-code" value="' . esc_attr($pix_code) . '" readonly>';
    echo '<button onclick="copyPixCode()" class="button" style="background: #0073aa; color: white; padding: 10px 15px; border: none; cursor: pointer; margin-top: 10px;">';
    echo __('📋 Copiar Código PIX', 'woo-pix-payment');
    echo '</button>';
    echo '</div>';
    echo '<script>
        function copyPixCode() {
            var copyText = document.getElementById("pix-copy-code");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("Código PIX copiado! Cole no seu app bancário.");
        }
    </script>';

    // Botão WhatsApp com mensagem pré-preenchida
    $message = rawurlencode(
        "Pedido Realizado.\n" .
        "Pedido: #{$order_number}\n" .
        "Valor: {$formatted_amount}\n" .
        "Status: Aguardando comprovante do PIX\n" .
        "Chave PIX: {$pix_key}\n" .
        "Tipo: " . strtoupper($pix_key_type) . "\n" .
        "Beneficiário: {$merchant_name}\n" .
        "Cidade: {$merchant_city}\n\n" .
        "Copie a chave acima e pague no seu banco. Envie o comprovante aqui!"
    );
    $whatsapp_link = "https://wa.me/{$whatsapp_number}?text=" . $message;

    echo '<div class="pix-whatsapp">';
    echo '<a href="' . esc_url($whatsapp_link) . '" target="_blank" class="button alt" style="background-color: #25D366; color: white; margin-top: 20px;">';
    echo __('Copie a chave PIX aqui', 'woo-pix-payment');
    echo '</a>';
    echo '</div>';
}

        /**
         * Salva configurações do WhatsApp
         * Inclui validação do número
         */
        public function process_admin_options() {
            parent::process_admin_options();
            
            // Valida número do WhatsApp
            $whatsapp_number = $this->get_option('whatsapp_number');
            if (!empty($whatsapp_number) && !preg_match('/^[0-9]+$/', $whatsapp_number)) {
                WC_Admin_Settings::add_error(__('Número do WhatsApp deve conter apenas números.', 'woo-pix-payment'));
            }
        }
    }

    /**
     * Registra o gateway no WooCommerce
     * Adiciona PIX como método de pagamento disponível
     */
    function add_pix_gateway($methods) {
        $methods[] = 'WC_Pix_Gateway';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'add_pix_gateway');
}