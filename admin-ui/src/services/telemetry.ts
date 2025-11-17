import { requestJson } from '../api/http';

interface TelemetryPayload {
  event: string;
  metadata?: Record<string, unknown>;
}

export async function trackTelemetry(payload: TelemetryPayload): Promise<void> {
  try {
    await requestJson('/api/telemetry.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': (window as Window & { __CSRF_TOKEN__?: string }).__CSRF_TOKEN__ ?? ''
      },
      body: JSON.stringify(payload)
    });
  } catch (error) {
    if (import.meta.env.DEV) {
      console.warn('[telemetry] Failed to record event', payload.event, error);
    }
  }
}

