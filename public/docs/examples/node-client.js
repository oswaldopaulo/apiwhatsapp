const baseUrl = process.env.APIWHATSAPP_URL || 'https://api.example.com';
const token = process.env.APIWHATSAPP_TOKEN;
const tenantId = process.env.APIWHATSAPP_TENANT_ID;

async function sendMessage() {
  const response = await fetch(`${baseUrl}/api/v1/messages/send`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
      'X-Tenant-ID': tenantId,
    },
    body: JSON.stringify({
      session_id: '1',
      to: '5511999999999',
      type: 'text',
      content: 'Mensagem enviada via Node.js.',
    }),
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(`API error ${response.status}: ${JSON.stringify(data)}`);
  }

  return data;
}

sendMessage()
  .then((data) => console.log(data))
  .catch((error) => {
    console.error(error.message);
    process.exitCode = 1;
  });
