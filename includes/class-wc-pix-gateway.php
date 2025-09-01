<?php
/**
 * Gateway de Pagamento PIX para WooCommerce
 * Integrado com Supabase Edge Functions e Multisite
 * Compatível com dispositivos mobile e desktop
 */

defined('ABSPATH') || exit;

if (!class_exists('WC_Pix_Gateway')) {

    class WC_Pix_Gateway extends WC_Payment_Gateway {

        // ================================
        // 1. CONFIGURAÇÕES DO SUPABASE
        // ================================
        private $supabase_url = 'https://czzidhzzpqegfvvmdgno.supabase.co';
        private $supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImN6emlkaHp6cHFlZ2Z2dm1kZ25vIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTI5NTIwMDMsImV4cCI6MjA2ODUyODAwM30.zK2iFp-b4e5vghpHgWGuOk0LooujlyU7kVm4sbM85m0';
        private $edge_function = '/functions/v1/generate-qrcode';

        /**
         * Construtor da classe
         * Configurações iniciais do gateway
         */
        public function __construct() {
            $this->id = 'pix_payment';
            $this->icon = plugins_url('assets/images/pix-icon.png', dirname(__FILE__));
            $this->method_title = __('PIX Payment', 'woo-pix-payment');
            $this->method_description = __('Aceite pagamentos via PIX com QR Code integrado ao Supabase.', 'woo-pix-payment');
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
         * Campos exibidos nas configurações do WooCommerce
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
                    'default' => __('Pague via PIX com QR Code.', 'woo-pix-payment'),
                    'desc_tip' => true
                ),
                'whatsapp_number' => array(
                    'title' => __('WhatsApp para Comprovante', 'woo-pix-payment'),
                    'type' => 'text',
                    'description' => __('Número com DDD para receber comprovantes (ex: 552132727548)', 'woo-pix-payment'),
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
            $order = wc_get_order($order_id);
            
            if ($order->get_payment_method() === $this->id) {
                echo '<div class="pix-payment-instructions">';
                echo '<h2>'.__('Pagamento via PIX', 'woo-pix-payment').'</h2>';
                
                // Só mostra instruções se ainda não foi pago
                if ($order->get_status() === 'pending') {
                    $this->display_pix_options($order);
                }
                // Se já foi pago, mostra apenas botão WhatsApp
                else {
                    $this->display_whatsapp_button($order);
                }
                
                echo '</div>';
            }
        }

        /**
         * Exibe opções PIX para mobile ou desktop
         * Adapta a exibição conforme o dispositivo
         */
        private function display_pix_options($order) {
            $pix_key = WC_PIX_Settings::get_setting('pix_key');
            $pix_key_type = WC_PIX_Settings::get_setting('pix_key_type');
            $merchant_name = WC_PIX_Settings::get_setting('merchant_name');
            $merchant_city = WC_PIX_Settings::get_setting('merchant_city');
            $amount = $order->get_total();
            $order_id = $order->get_id();
            
            // ================================
            // 1. DADOS PARA CONFIRMAÇÃO (SEMPRE MOSTRA)
            // ================================
            echo '<div class="pix-confirmation-data">';
            echo '<h3>'.__('Dados para Pagamento PIX', 'woo-pix-payment').'</h3>';
            echo '<table>';
            echo '<tr><td><strong>'.__('Valor:', 'woo-pix-payment').'</strong></td><td>R$ ' . number_format($amount, 2, ',', '.') . '</td></tr>';
            echo '<tr><td><strong>'.__('Chave PIX:', 'woo-pix-payment').'</strong></td><td>' . esc_html($pix_key) . '</td></tr>';
            echo '<tr><td><strong>'.__('Tipo:', 'woo-pix-payment').'</strong></td><td>' . esc_html(strtoupper($pix_key_type)) . '</td></tr>';
            echo '<tr><td><strong>'.__('Beneficiário:', 'woo-pix-payment').'</strong></td><td>' . esc_html($merchant_name) . '</td></tr>';
            echo '<tr><td><strong>'.__('Cidade:', 'woo-pix-payment').'</strong></td><td>' . esc_html($merchant_city) . '</td></tr>';
            echo '</table>';
            echo '</div>';
            
            // ================================
            // 2. DETECÇÃO MANUAL DE MOBILE (FALLBACK)
            // ================================
            $is_mobile = false;
            if (function_exists('wp_is_mobile')) {
                $is_mobile = wp_is_mobile();
            } else {
                // Fallback manual para detectar mobile
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $is_mobile = preg_match('/android|iphone|ipod|ipad|mobile/i', $user_agent);
            }
            
            // DEBUG: Mostra se está detectando como mobile
            // echo '<p style="color: red;">Debug: is_mobile = ' . ($is_mobile ? 'SIM' : 'NÃO') . '</p>';
            
            // ================================
            // 3. OPÇÕES ESPECÍFICAS POR DISPOSITIVO
            // ================================
            
            // SE FOR MOBILE - MOSTRA CÓDIGO PARA COPIAR
            if ($is_mobile) {
                echo '<div class="pix-mobile-options">';
                echo '<h3>'.__('📱 Pagamento pelo Celular', 'woo-pix-payment').'</h3>';
                
                // BOTÃO COPIAR CHAVE + VALOR
                $pix_code = $this->generate_pix_copiable_code($pix_key, $amount);
                echo '<div class="pix-copy-section">';
                echo '<input type="text" id="pix-copy-code" value="' . esc_attr($pix_code) . '" readonly>';
                echo '<button onclick="copyPixCode()" class="button" style="background: #0073aa; color: white; padding: 10px 15px; border: none; cursor: pointer; margin-top: 10px;">';
                echo __('📋 Copiar Código PIX', 'woo-pix-payment');
                echo '</button>';
                echo '</div>';
                
                echo '<div class="pix-instructions">';
                echo '<p>'.__('1. Clique em "Copiar Código PIX"', 'woo-pix-payment').'</p>';
                echo '<p>'.__('2. Abra seu app bancário', 'woo-pix-payment').'</p>';
                echo '<p>'.__('3. Cole no campo PIX', 'woo-pix-payment').'</p>';
                echo '<p>'.__('4. Confirme o pagamento', 'woo-pix-payment').'</p>';
                echo '</div>';
                
                echo '</div>';
                
                // SCRIPT PARA COPIAR
                echo '<script>
                function copyPixCode() {
                    var copyText = document.getElementById("pix-copy-code");
                    copyText.select();
                    copyText.setSelectionRange(0, 99999);
                    document.execCommand("copy");
                    alert("Código PIX copiado! Agora cole no seu app bancário.");
                }
                </script>';
                
            } 
            // SE FOR DESKTOP - MOSTRA QR CODE
            else {
                $this->display_qr_code($order);
            }
            
            // ================================
            // 4. BOTÃO WHATSAPP (PARA ENVIO DE COMPROVANTE)
            // ================================
            $this->display_whatsapp_button($order);
        }

        /**
         * Gera código copiável para mobile
         * Formato que apps bancários reconhecem
         */
        private function generate_pix_copiable_code($pix_key, $amount) {
            $amount_formatted = number_format($amount, 2, ',', '');
            
            // Formato que a maioria dos apps bancários reconhece
            return "PIX: " . $pix_key . " | Valor: R$ " . $amount_formatted;
        }

        /**
         * Gera e exibe QR Code PIX usando Supabase
         * Para dispositivos desktop
         */
        private function display_qr_code($order) {
            // Obtem configurações PIX do site atual
            $pix_key = WC_PIX_Settings::get_setting('pix_key');
            $merchant_name = WC_PIX_Settings::get_setting('merchant_name');
            $merchant_city = WC_PIX_Settings::get_setting('merchant_city');
            $amount = $order->get_total();
            
            // Gera payload PIX (formato BR Code)
            $payload = $this->generate_pix_payload($pix_key, $amount, $merchant_name, $merchant_city);
            
            // URL da Edge Function do Supabase
            $supabase_qr_url = $this->supabase_url . $this->edge_function . '?payload=' . urlencode($payload);
            
            echo '<div class="pix-qr-code">';
            echo '<h3>'.__('Escaneie o QR Code para pagar', 'woo-pix-payment').'</h3>';
            echo '<img src="' . esc_url($supabase_qr_url) . '" alt="QR Code PIX" style="max-width: 300px; height: auto;">';
            echo '<div class="pix-key">';
            echo '<strong>'.__('Chave PIX:', 'woo-pix-payment').'</strong> ' . esc_html($pix_key);
            echo '</div>';
            echo '</div>';
        }

        /**
         * Gera payload PIX no formato BR Code
         * Padrão oficial do Banco Central
         */
        private function generate_pix_payload($pix_key, $amount, $merchant_name, $merchant_city) {
            $amount_formatted = number_format($amount, 2, '', '');
            
            // Formato padrão BR Code PIX
            $payload = "000201"; // Início do payload
            $payload .= "26580014BR.GOV.BCB.PIX"; // GUI do PIX
            $payload .= "0136" . $pix_key; // Chave PIX
            $payload .= "52040000"; // Categoria comercial
            $payload .= "5303986"; // Moeda (986 = BRL)
            $payload .= "54" . sprintf('%02d', strlen($amount_formatted)) . $amount_formatted; // Valor
            $payload .= "5802BR"; // País
            $payload .= "59" . sprintf('%02d', strlen($merchant_name)) . $merchant_name; // Nome do beneficiário
            $payload .= "60" . sprintf('%02d', strlen($merchant_city)) . $merchant_city; // Cidade
            $payload .= "6304"; // CRC16
            
            return $payload;
        }

        /**
         * Exibe botão do WhatsApp para envio de comprovante
         */
        private function display_whatsapp_button($order) {
            // CORREÇÃO: Usa o número do site, não do gateway
            $whatsapp_number = WC_PIX_Settings::get_setting('whatsapp_number');
            $order_id = $order->get_id();
            $amount = $order->get_total();
            
            $message = rawurlencode(
                "📋 *Pedido Realizado.* \n" .
                "Pedido: #" . $order_id . "\n" .
                "Valor: R$ " . number_format($amount, 2, ',', '.') . "\n" .
                "Status: Aguardando comprovante PIX"
            );
            
            echo '<div class="pix-whatsapp">';
            echo '<a href="https://wa.me/' . esc_attr($whatsapp_number) . '?text=' . $message . '" 
                  target="_blank" class="button alt" style="background-color: #25D366; color: white; margin-top: 20px;">';
            echo __('📱 Enviar comprovante via WhatsApp', 'woo-pix-payment');
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