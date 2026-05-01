const apiWhatsApp = {
  baseUrl: 'https://api.example.com',
  token: 'ACCESS_TOKEN',
  tenantId: 'TENANT_PUBLIC_ID',
};

async function sendWhatsAppMessage() {
  const response = await fetch(`${apiWhatsApp.baseUrl}/api/v1/messages/send`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${apiWhatsApp.token}`,
      'X-Tenant-ID': apiWhatsApp.tenantId,
    },
    body: JSON.stringify({
      session_id: '1',
      to: '5511999999999',
      type: 'text',
      content: 'Mensagem enviada via JavaScript no navegador.',
    }),
  });

  if (!response.ok) {
    throw new Error(`Falha HTTP ${response.status}`);
  }

  return response.json();
}
