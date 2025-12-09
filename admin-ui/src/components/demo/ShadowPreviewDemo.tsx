import { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { usePageSnapshot } from '../../api/page';

interface PreviewData {
  title: string;
  subtitle: string;
  avatarUrl: string;
  accent: string;
  background: string;
}

export function ShadowPreviewDemo(): JSX.Element {
  const hostRef = useRef<HTMLDivElement | null>(null);
  const [shadowRoot, setShadowRoot] = useState<ShadowRoot | null>(null);
  const { data: snapshot } = usePageSnapshot();

  const [data, setData] = useState<PreviewData>(() => ({
    title: 'Shadow DOM Preview',
    subtitle: 'This preview is rendered inside a ShadowRoot.',
    avatarUrl: 'https://placekitten.com/200/200',
    accent: '#00FF7F',
    background: '#0f172a'
  }));

  // Sync with global theme when loaded
  useEffect(() => {
    if (snapshot?.page) {
      setData(prev => ({
        ...prev,
        title: snapshot.page.podcast_name || snapshot.page.username || prev.title,
        subtitle: snapshot.page.podcast_description || prev.subtitle,
        avatarUrl: snapshot.page.profile_image || snapshot.page.cover_image || prev.avatarUrl,
        // Use page_background from theme, fallback to current or default
        background: snapshot.page.page_background || prev.background,
        // We could also try to extract an accent from snapshot.page.colors if structured
      }));
    }
  }, [snapshot]);

  useEffect(() => {
    if (!hostRef.current) return;
    const root = hostRef.current.shadowRoot ?? hostRef.current.attachShadow({ mode: 'open' });
    setShadowRoot(root);
  }, []);

  const shadowStyles = useMemo(() => {
    return `
      :host {
        all: initial;
        color: #e2e8f0;
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
      }
      .card {
        background: ${data.background};
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 20px;
        display: grid;
        grid-template-columns: 96px 1fr;
        gap: 16px;
        align-items: center;
        box-shadow: 0 10px 40px rgba(0,0,0,0.35);
      }
      .avatar {
        width: 96px;
        height: 96px;
        border-radius: 14px;
        overflow: hidden;
        border: 2px solid rgba(255, 255, 255, 0.08);
        background: rgba(255,255,255,0.04);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #cbd5e1;
        font-weight: 700;
        font-size: 24px;
      }
      .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
      }
      .content h2 {
        margin: 0 0 6px 0;
        font-size: 20px;
        font-weight: 700;
        color: #f8fafc;
      }
      .content p {
        margin: 0;
        color: #cbd5e1;
        font-size: 14px;
      }
      .pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        margin-top: 10px;
        border-radius: 999px;
        background: ${data.accent};
        color: #0b1221;
        font-weight: 600;
        font-size: 12px;
      }
      .layout {
        display: grid;
        gap: 16px;
      }
    `;
  }, [data.accent, data.background]);

  const initials = useMemo(() => {
    const text = data.title || 'P';
    const parts = text.trim().split(' ');
    const letters = parts.slice(0, 2).map((chunk) => chunk[0]).join('');
    return letters.toUpperCase();
  }, [data.title]);

  return (
    <div style={{ padding: '24px', maxWidth: 800, margin: '0 auto' }}>
      <h1 style={{ marginBottom: 12 }}>Shadow DOM Preview Demo</h1>
      <p style={{ marginBottom: 20, color: '#475569' }}>
        This renders the preview as first-party React inside a ShadowRoot (no iframe). Update the fields below to see it live.
      </p>

      <div
        style={{
          display: 'grid',
          gap: 12,
          marginBottom: 24,
          gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))'
        }}
      >
        <label style={{ display: 'grid', gap: 6, fontSize: 13, color: '#475569' }}>
          Title
          <input
            type="text"
            value={data.title}
            onChange={(e) => setData((prev) => ({ ...prev, title: e.target.value }))}
            style={{
              padding: '10px 12px',
              borderRadius: 8,
              border: '1px solid #cbd5e1',
              fontSize: 14
            }}
          />
        </label>

        <label style={{ display: 'grid', gap: 6, fontSize: 13, color: '#475569' }}>
          Subtitle
          <input
            type="text"
            value={data.subtitle}
            onChange={(e) => setData((prev) => ({ ...prev, subtitle: e.target.value }))}
            style={{
              padding: '10px 12px',
              borderRadius: 8,
              border: '1px solid #cbd5e1',
              fontSize: 14
            }}
          />
        </label>

        <label style={{ display: 'grid', gap: 6, fontSize: 13, color: '#475569' }}>
          Avatar URL
          <input
            type="url"
            value={data.avatarUrl}
            onChange={(e) => setData((prev) => ({ ...prev, avatarUrl: e.target.value }))}
            style={{
              padding: '10px 12px',
              borderRadius: 8,
              border: '1px solid #cbd5e1',
              fontSize: 14
            }}
            placeholder="https://..."
          />
        </label>

        <label style={{ display: 'grid', gap: 6, fontSize: 13, color: '#475569' }}>
          Accent color
          <input
            type="color"
            value={data.accent}
            onChange={(e) => setData((prev) => ({ ...prev, accent: e.target.value }))}
            style={{ width: '100%', height: 42, padding: 0, borderRadius: 8, border: '1px solid #cbd5e1' }}
          />
        </label>

        <label style={{ display: 'grid', gap: 6, fontSize: 13, color: '#475569' }}>
          Background
          <input
            type="color"
            value={data.background}
            onChange={(e) => setData((prev) => ({ ...prev, background: e.target.value }))}
            style={{ width: '100%', height: 42, padding: 0, borderRadius: 8, border: '1px solid #cbd5e1' }}
          />
        </label>
      </div>

      <div
        ref={hostRef}
        style={{
          width: '100%',
          border: '1px dashed #cbd5e1',
          borderRadius: 12,
          padding: 12,
          background: '#f8fafc'
        }}
      />

      {shadowRoot &&
        createPortal(
          <div className="layout">
            <style>{shadowStyles}</style>
            <div className="card">
              <div className="avatar" aria-hidden="true">
                {data.avatarUrl ? <img src={data.avatarUrl} alt="Avatar" /> : initials}
              </div>
              <div className="content">
                <h2>{data.title || 'Shadow DOM Preview'}</h2>
                <p>{data.subtitle || 'Live preview rendered without an iframe.'}</p>
                <span className="pill">Shadow DOM</span>
              </div>
            </div>
          </div>,
          shadowRoot as unknown as Element
        )}
    </div>
  );
}



