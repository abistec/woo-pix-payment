<?php
class WC_PIX_Multisite_Settings {
    
    public static function init() {
        add_action('network_admin_menu', [__CLASS__, 'add_network_menu']);
        add_action('network_admin_edit_woo_pix_network_settings', [__CLASS__, 'save_network_settings']);
    }
    
    public static function add_network_menu() {
        add_submenu_page(
            'settings.php',
            'PIX Payment Settings',
            'PIX Payment',
            'manage_network_options',
            'woo-pix-settings',
            [__CLASS__, 'render_network_settings']
        );
    }
    
    public static function render_network_settings() {
        $allowed_sites = get_site_option('woo_pix_allowed_sites', []);
        $sites = get_sites();
        ?>
        <div class="wrap">
            <h1>Configurações do PIX Payment - Multisite</h1>
            <form method="post" action="edit.php?action=woo_pix_network_settings">
                <?php wp_nonce_field('woo_pix_network_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Sites permitidos</th>
                        <td>
                            <?php foreach ($sites as $site): ?>
                                <?php $site_details = get_blog_details($site->blog_id); ?>
                                <label>
                                    <input type="checkbox" name="allowed_sites[]" value="<?php echo $site->blog_id; ?>"
                                        <?php checked(in_array($site->blog_id, $allowed_sites)); ?>>
                                    <?php echo $site_details->blogname . ' (ID: ' . $site->blog_id . ')'; ?>
                                </label><br>
                            <?php endforeach; ?>
                            <p class="description">Selecione os sites onde o PIX Payment estará ativo</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Salvar Configurações'); ?>
            </form>
        </div>
        <?php
    }
    
    public static function save_network_settings() {
        check_admin_referer('woo_pix_network_settings');
        
        $allowed_sites = isset($_POST['allowed_sites']) ? array_map('intval', $_POST['allowed_sites']) : [];
        update_site_option('woo_pix_allowed_sites', $allowed_sites);
        
        wp_redirect(add_query_arg(['page' => 'woo-pix-settings', 'updated' => 'true'], network_admin_url('settings.php')));
        exit;
    }
}

WC_PIX_Multisite_Settings::init();