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

  if (isLoading) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <p>Preparing design tokens…</p>
      </div>
    );
  }

  if (isError) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center', color: '#b91c1c' }}>
        <p>We couldn’t load your design tokens.</p>
        <p style={{ fontSize: '0.85rem' }}>{error instanceof Error ? error.message : String(error)}</p>
      </div>
    );
  }

  return <>{children}</>;
}

