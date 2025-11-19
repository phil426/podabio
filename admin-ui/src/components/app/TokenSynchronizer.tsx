import { PropsWithChildren, useEffect } from 'react';

import { useTokensQuery } from '../../api/tokens';
import { useTokens } from '../../design-system/theme/TokenProvider';
import { sanitizeTokenBundle } from '../../design-system/tokens/sanitize';

export function TokenSynchronizer({ children }: PropsWithChildren): JSX.Element {
  const { setTokens } = useTokens();
  const { data, isLoading, isSuccess, isError, error } = useTokensQuery();

  useEffect(() => {
    if (isSuccess && data) {
      setTokens(sanitizeTokenBundle(data));
    }
  }, [data, isSuccess, setTokens]);

  // Check if current page doesn't need tokens (demo/docs pages)
  const isSpecialPage = typeof window !== 'undefined' && (
    window.location.pathname.includes('/demo/') ||
    window.location.pathname.includes('/studio-docs')
  );

  if (isLoading && !isSpecialPage) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <p>Preparing design tokens…</p>
      </div>
    );
  }

  if (isError || (isLoading && isSpecialPage)) {
    // For demo/docs pages or when tokens aren't critical, allow the app to continue
    // The TokenProvider already has defaultTokenPreset as fallback
    if (isSpecialPage || isError) {
      // Demo/docs pages don't need tokens - just continue with defaults
      if (isError) {
        console.warn('Failed to load design tokens, using defaults:', error);
      }
      return <>{children}</>;
    }
  }

  return <>{children}</>;
}

