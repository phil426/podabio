/**
 * Debug Logger for Theme Color Issues
 * Logs to both console and sends to server for debugging
 */

interface LogEntry {
  timestamp: string;
  level: 'debug' | 'info' | 'warn' | 'error';
  message: string;
  data?: any;
}

const logs: LogEntry[] = [];
const MAX_LOGS = 100;

export function debugLog(level: LogEntry['level'], message: string, data?: any): void {
  const entry: LogEntry = {
    timestamp: new Date().toISOString(),
    level,
    message,
    data
  };
  
  logs.push(entry);
  if (logs.length > MAX_LOGS) {
    logs.shift();
  }
  
  // Log to console
  const consoleMethod = level === 'error' ? 'error' : level === 'warn' ? 'warn' : 'log';
  console[consoleMethod](`[${level.toUpperCase()}] ${message}`, data || '');
}

export function getLogs(): LogEntry[] {
  return [...logs];
}

export function clearLogs(): void {
  logs.length = 0;
}

export function exportLogs(): string {
  return JSON.stringify(logs, null, 2);
}

// Make available globally for browser console access
if (typeof window !== 'undefined') {
  (window as any).themeDebugLogs = {
    get: getLogs,
    clear: clearLogs,
    export: exportLogs
  };
}

