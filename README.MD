# WooCommerce PIX Payment

## Descrição
Este plugin adiciona um método de pagamento PIX ao WooCommerce. Ele gera um QR Code dinâmico com base no valor total do pedido e exibe-o na página de agradecimento após o checkout.

## Como Usar
1. Ative o plugin em **Plugins > Plugins Instalados**.
2. Vá para **WooCommerce > Configurações > Pagamentos**.
3. Habilite o método "PIX Payment".
4. Configure o título e descrição.
5. Use o método no checkout.

## Requisitos
- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.2+

## Desinstalação
1. Desative o plugin.
2. Exclua a pasta `woo-pix-payment` do diretório `/wp-content/plugins/`.

### ARQUIVO PRINCIPAL (woo-pix-pagament.php)

✅ Explicação Passo a Passo
🔹 1. Proteção Contra Acesso Direto
A linha if (!defined('ABSPATH')) { exit; } garante que o arquivo só seja executado dentro do WordPress. Isso evita que alguém acesse o arquivo diretamente via URL, o que poderia causar erros ou expor informações sensíveis.
🔹 2. Inclusão de Arquivos Externos
O plugin é modular, então dividimos as funcionalidades em arquivos separados:
class-wc-pix-gateway.php: Contém a classe que implementa o método de pagamento PIX no WooCommerce.
qr-code-generator.php: Contém a função para gerar o QR Code usando a API do Supabase.
Usamos plugin_dir_path(__FILE__) para garantir que os caminhos dos arquivos sejam absolutos e funcionem corretamente.
🔹 3. Carregamento de CSS e JS
Os arquivos CSS e JS são opcionais, mas úteis para estilizar o QR Code e adicionar interatividade (como um botão para WhatsApp).
wp_enqueue_style e wp_enqueue_script são funções nativas do WordPress para carregar recursos externos.
🔹 4. Suporte ao Multisite
O WordPress Multisite permite criar várias lojas em um único site. Cada loja pode ter sua própria configuração de chave PIX.
A função is_multisite() verifica se o WordPress está configurado como Multisite.
Um aviso é exibido no painel do Multisite para informar que o plugin foi ativado em todos os sites.
🔹 5. Configuração da Chave PIX
Para permitir que cada site do Multisite tenha sua própria chave PIX, adicionamos um campo personalizado no WooCommerce.
A função add_pix_gateway_settings inclui um arquivo adicional (class-wc-pix-settings.php) que define as configurações específicas do plugin.

### ARQUIVO (class-wc-pix-gateway.php)

✅ Explicação Passo a Passo
🔹 1. Proteção Contra Acesso Direto
A linha if (!defined('ABSPATH')) { exit; } garante que o arquivo só seja executado dentro do WordPress. Isso evita que alguém acesse o arquivo diretamente via URL.
🔹 2. Classe WC_Pix_Gateway
Esta classe implementa o método de pagamento PIX no WooCommerce.
Ela estende a classe WC_Payment_Gateway, que é nativa do WooCommerce e fornece métodos para criar gateways de pagamento personalizados.
🔹 3. Construtor (__construct)
O construtor define as propriedades básicas do gateway:
$this->id: ID único para o gateway (pix_payment).
$this->icon: Ícone opcional (pode ser uma imagem ou SVG).
$this->method_title: Título exibido no painel do WooCommerce.
$this->method_description: Descrição exibida no painel do WooCommerce.
🔹 4. Método init_form_fields
Este método define os campos que aparecem na página de configurações do WooCommerce:
Habilitar/Desabilitar: Um checkbox para ativar ou desativar o gateway.
Título: O nome do método de pagamento exibido no checkout.
Descrição: Uma descrição exibida no checkout.
🔹 5. Método process_payment
Este método define o comportamento do gateway ao processar um pedido:
Atualiza o status do pedido para "Em espera" (on-hold).
Limpa o carrinho do cliente.
Redireciona o cliente para a página de agradecimento.
🔹 6. Compatibilidade com Multisite
No WordPress Multisite, cada site tem suas próprias configurações.
Quando você acessa o painel de administração de um site específico, as configurações definidas aqui serão salvas apenas para aquele site.
Isso permite que cada site do Multisite tenha seu próprio título e descrição para o método de pagamento PIX.
✅ Dados Preenchidos para o Primeiro Site
Para o site https://pedidossimples.com.br/mercearia-luanda/, você pode configurar o seguinte:

Chave PIX: 01083172778
Nome da Loja: Mercearia Luanda
Cidade: Rio de Janeiro, RJ


### CÓDIGO (generate-qr-code.php)

✅ Explicação Passo a Passo
🔹 1. Proteção Contra Acesso Direto
A linha if (!defined('ABSPATH')) { exit; } garante que o arquivo só seja executado dentro do WordPress. Isso evita que alguém acesse o arquivo diretamente via URL.
🔹 2. Configurações do Site Atual
O código identifica o site atual usando get_bloginfo('name'), que retorna o nome do site (ex: "Mercearia Luanda").
Convertendo o nome do site para um formato de slug (ex: "mercearia-luanda"), podemos associá-lo às configurações PIX corretas.
As configurações PIX (chave, nome da loja e cidade) são armazenadas em um array $pix_settings. Você pode adicionar novas lojas facilmente ao array.
🔹 3. Gerar o Payload PIX
O payload PIX segue o formato fixo do Banco Central do Brasil.
Ele inclui:
A chave PIX ($pix_key),
O valor total do pedido ($amount),
O nome da loja ($merchant_name),
A cidade da loja ($merchant_city).
🔹 4. Chamar a API do Supabase
A URL da API Supabase é construída com o payload PIX codificado.
O token público (anon) é usado para autenticar a requisição.
A função wp_remote_get faz a chamada à API e retorna o QR Code gerado.
✅ Como Adicionar Novas Lojas?

Para adicionar uma nova loja, basta incluir uma nova entrada no array $pix_settings:
'nova-loja' => array(
    'key'   => 'nova_chave_pix',
    'name'  => 'Nova Loja',
    'city'  => 'Cidade da Nova Loja',
),

✅ Dados Reais Inseridos
URL do Projeto Supabase: https://czzidhzzpqegfvvmdgno.supabase.co
Token Anon: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImN6emlkaHp6cHFlZ2Z2dm1kZ25vIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTI5NTIwMDMsImV4cCI6MjA2ODUyODAwM30.zK2iFp-b4e5vghpHgWGuOk0LooujlyU7kVm4sbM85m0

##  ARQUIVO (class-wc-pix-setting.php)

✅ Explicação Passo a Passo
🔹 1. Proteção Contra Acesso Direto
A linha if (!defined('ABSPATH')) { exit; } garante que o arquivo só seja executado dentro do WordPress.
🔹 2. Identificação do Site Atual
O código usa get_bloginfo('name') para obter o nome do site atual (ex: "Mercearia Luanda").
Ele converte o nome para um formato de slug (ex: "mercearia-luanda") usando strtolower e str_replace.
🔹 3. Configurações Padrão por Site
Um array $default_pix_settings armazena as configurações PIX para cada site:
Chave PIX: Exemplo: 01083172778 para "Mercearia Luanda".
Nome da Loja: Exemplo: "Mercearia Luanda".
Cidade: Exemplo: "Rio de Janeiro, RJ".
🔹 4. Recuperação das Configurações do Site Atual
O código verifica se o slug do site atual existe no array $default_pix_settings.
Se existir, ele carrega as configurações correspondentes. Caso contrário, define valores padrão vazios.
🔹 5. Campos de Configuração
O método get_settings define três campos personalizados:
Chave PIX: Um campo de texto para inserir a chave PIX.
Nome da Loja: Um campo de texto para inserir o nome da loja.
Cidade: Um campo de texto para inserir a cidade da loja.
✅ Como Funciona na Prática?
Acesse o Painel do WooCommerce:
Em qualquer site do Multisite, vá para WooCommerce > Configurações > Pagamentos.
Habilite o método "PIX Payment".
Configure os Dados PIX:
Na aba Configurações do PIX, você verá os campos preenchidos automaticamente com os dados do site atual:
Para https://pedidossimples.com.br/mercearia-luanda/:
Chave PIX: 01083172778
Nome da Loja: Mercearia Luanda
Cidade: Rio de Janeiro, RJ
Para https://pedidossimples.com.br/3g-luanda/:
Chave PIX: celulardabelinalva@gmail.com
Nome da Loja: 3G Luanda
Cidade: Rio de Janeiro, RJ
Salve as Configurações:
As configurações serão salvas apenas para aquele site."# woo-pix-payment" 
