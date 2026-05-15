import { config } from './config.js';

async function requestJson(url, options = {}) {
  const response = await fetch(url, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Kiosk-Token': config.kioskApiToken,
      ...(options.headers || {}),
    },
  });

  const text = await response.text();
  let data = null;

  try {
    data = text ? JSON.parse(text) : null;
  } catch {
    data = { raw: text };
  }

  if (!response.ok) {
    const message = data?.message || `HTTP ${response.status}`;
    throw new Error(message);
  }

  return data;
}

export async function authenticateKiosk() {
  return requestJson(`${config.centralUrl}/api/kiosk/authenticate`, {
    method: 'POST',
  });
}

export async function sendHeartbeat() {
  return requestJson(`${config.centralUrl}/api/kiosk/heartbeat`, {
    method: 'POST',
  });
}

export async function fetchPendingJobs() {
  return requestJson(`${config.centralUrl}/api/kiosk/jobs/pending`, {
    method: 'GET',
  });
}

export async function markPrinting(jobId) {
  return requestJson(`${config.centralUrl}/api/kiosk/jobs/${jobId}/printing`, {
    method: 'POST',
  });
}

export async function completeJob(jobId, notes = '') {
  return requestJson(`${config.centralUrl}/api/kiosk/jobs/${jobId}/complete`, {
    method: 'POST',
    body: JSON.stringify({ notes }),
  });
}

export async function downloadPdf(jobId) {
  const response = await fetch(`${config.centralUrl}/api/kiosk/jobs/${jobId}/pdf`, {
    method: 'GET',
    headers: {
      'X-Kiosk-Token': config.kioskApiToken,
    },
  });

  if (!response.ok) {
    const text = await response.text();
    throw new Error(text || `HTTP ${response.status}`);
  }

  return Buffer.from(await response.arrayBuffer());
}