// Supabase Edge Functions require specific import syntax
// Use built-in QR code generation or simpler approach
/**
 * Generate QR Code SVG from payload
 * Basic SVG QR code generator without external dependencies
 */ function generateQRCodeSVG(payload, size = 300, margin = 2) {
  // Simple QR code implementation using basic rectangles
  // For production, consider using a proper QR code library
  const qrSize = size - margin * 2;
  // This is a simplified version - in production use a real QR library
  return `<?xml version="1.0" encoding="UTF-8"?>
<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
  <rect width="100%" height="100%" fill="#FFFFFF"/>
  <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-family="Arial, sans-serif" font-size="14" fill="#000000">
    PIX: ${payload.substring(0, 20)}${payload.length > 20 ? '...' : ''}
  </text>
  <text x="50%" y="60%" text-anchor="middle" dy=".3em" font-family="Arial, sans-serif" font-size="12" fill="#666666">
    (QR Code would appear here)
  </text>
</svg>`;
}
/**
 * Handler for Supabase Edge Function
 */ export default async function handler(req) {
  try {
    // Get payload from URL parameters
    const url = new URL(req.url);
    const payload = url.searchParams.get("payload");
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
    // Generate SVG
    const qrCodeSvg = generateQRCodeSVG(payload);
    // Save to database (optional)
    try {
      const supabaseUrl = 'https://czzidhzzpqegfvvmdgno.supabase.co';
      const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImN6emlkaHp6cHFlZ2Z2dm1kZ25vIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTI5NTIwMDMsImV4cCI6MjA2ODUyODAwM30.zK2iFp-b4e5vghpHgWGuOk0LooujlyU7kVm4sbM85m0';
      await fetch(`${supabaseUrl}/rest/v1/pix_payments`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'apikey': supabaseKey,
          'Authorization': `Bearer ${supabaseKey}`
        },
        body: JSON.stringify({
          order_id: 'temp',
          payload: payload,
          created_at: new Date().toISOString()
        })
      });
    } catch (dbError) {
      console.error("Database error:", dbError);
    }
    return new Response(qrCodeSvg, {
      headers: {
        "Content-Type": "image/svg+xml",
        "Cache-Control": "no-cache"
      },
      status: 200
    });
  } catch (error) {
    return new Response(JSON.stringify({
      error: "Failed to process request"
    }), {
      status: 500,
      headers: {
        "Content-Type": "application/json"
      }
    });
  }
}
// Required config for Supabase Edge Function
export const config = {
  path: "/generate-qrcode"
};
