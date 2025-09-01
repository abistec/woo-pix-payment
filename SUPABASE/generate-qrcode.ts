// Importa as dependências necessárias
import { serve } from "https://deno.land/std@0.192.0/http/server.ts";
import { toSvg } from "https://deno.land/x/qrcode@v2.0.0/mod.ts";
/**
 * Função principal da API Edge do Supabase
 * 
 * Esta função recebe um payload PIX via GET e retorna um QR Code no formato SVG.
 * Ela é projetada para ser usada pelo plugin WooCommerce em um ambiente Multisite.
 */ serve(async (req)=>{
  try {
    // ================================
    // 1. RECUPERAR O PAYLOAD DA REQUISIÇÃO
    // ================================
    // Extrai o parâmetro 'payload' da URL
    const url = new URL(req.url);
    const payload = url.searchParams.get("payload");
    // Verifica se o payload foi fornecido
    if (!payload) {
      return new Response(JSON.stringify({
        error: "Payload is required"
      }), {
        status: 400,
        headers: {
          "Content-Type": "application/json"
        }
      });
    }
    // ================================
    // 2. GERAR O QR CODE
    // ================================
    // Gera o QR Code em formato SVG usando a biblioteca qrcode
    const qrCodeSvg = await toSvg(payload, {
      margin: 2
    });
    // Retorna o QR Code como uma resposta HTTP
    return new Response(qrCodeSvg, {
      headers: {
        "Content-Type": "image/svg+xml"
      },
      status: 200
    });
  // ================================
  // 3. TRATAMENTO DE ERROS
  // ================================
  } catch (error) {
    console.error("Erro ao gerar QR Code:", error.message);
    // Retorna uma resposta de erro em caso de falha
    return new Response(JSON.stringify({
      error: "Failed to generate QR Code"
    }), {
      status: 500,
      headers: {
        "Content-Type": "application/json"
      }
    });
  }
});
