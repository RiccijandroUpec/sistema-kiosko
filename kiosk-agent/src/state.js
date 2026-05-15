export const state = {
  kioskName: '',
  centralUrl: '',
  kioskId: null,
  status: 'starting',
  lastHeartbeatAt: null,
  lastSyncAt: null,
  lastError: null,
  currentJob: null,
  recentLogs: [],
};

export function log(message, level = 'info') {
  const entry = {
    timestamp: new Date().toISOString(),
    level,
    message,
  };

  state.recentLogs.unshift(entry);
  state.recentLogs = state.recentLogs.slice(0, 20);

  const prefix = level === 'error' ? '[ERROR]' : level === 'warn' ? '[WARN]' : '[INFO]';
  console.log(`${prefix} ${message}`);
}

export function setStatus(status, extra = {}) {
  state.status = status;
  Object.assign(state, extra);
}