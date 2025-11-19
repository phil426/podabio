export interface JsonRequestInit extends RequestInit {
  /**
   * When true (default) the helper attempts to parse the response as JSON.
   * Set to false when you need the raw Response object.
   */
  parseJson?: boolean;
}

export class ApiError extends Error {
  public readonly status: number;
  public readonly payload: unknown;

  constructor(message: string, status: number, payload: unknown) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.payload = payload;
  }
}

const defaultHeaders = {
  'X-Requested-With': 'XMLHttpRequest'
};

async function refreshCsrfToken(): Promise<string> {
  const response = await fetch('/api/csrf.php', {
    credentials: 'include',
    headers: defaultHeaders
  });

  if (!response.ok) {
    throw new ApiError('Failed to refresh CSRF token', response.status, null);
  }

  const data = (await response.json()) as { success: boolean; csrf_token?: string };
  
  if (!data.success || !data.csrf_token) {
    throw new ApiError('Invalid CSRF token response', 500, data);
  }

  // Update the global token
  if (typeof window !== 'undefined') {
    (window as Window & { __CSRF_TOKEN__?: string }).__CSRF_TOKEN__ = data.csrf_token;
  }

  return data.csrf_token;
}

export async function requestJson<TResponse>(
  input: RequestInfo | URL,
  init: JsonRequestInit = {}
): Promise<TResponse> {
  const { parseJson = true, headers, ...rest } = init;
  
  // Store original body for potential retry
  const originalBody = rest.body;
  const isFormData = originalBody instanceof FormData;

  const response = await fetch(input, {
    credentials: 'include',
    headers: {
      ...defaultHeaders,
      ...headers
    },
    ...rest
  });

  if (!parseJson) {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    return response as any;
  }

  const text = await response.text();
  const payload = text ? safeJsonParse(text) : null;

  if (!response.ok) {
    const message =
      typeof payload === 'object' && payload && 'error' in payload
        ? String((payload as { error?: string }).error)
        : `Request failed with status ${response.status}`;
    
    // Handle 401 Unauthorized - redirect to login
    if (response.status === 401) {
      // Only redirect if we're not already on the login page
      if (typeof window !== 'undefined' && !window.location.pathname.includes('/login')) {
        const currentUrl = window.location.pathname + window.location.search;
        window.location.href = `/login.php?redirect=${encodeURIComponent(currentUrl)}`;
        // Return a rejected promise to stop further execution
        return Promise.reject(new ApiError('Unauthorized - redirecting to login', 401, payload));
      }
    }
    
    // Check if this is a CSRF token error
    // Treat any 403 error as a potential CSRF error for POST requests
    const isCsrfError = 
      response.status === 403 && 
      (typeof payload === 'object' && 
       payload && 
       'error' in payload &&
       String((payload as { error?: string }).error).toLowerCase().includes('csrf')) ||
      (response.status === 403 && rest.method === 'POST');

    // If it's a CSRF error and the request was a POST with form data, try refreshing the token and retrying
    if (isCsrfError && rest.method === 'POST' && isFormData && originalBody instanceof FormData) {
      try {
        await refreshCsrfToken();
        
        // Update the CSRF token in the form data
        const updatedFormData = new FormData();
        const csrfToken = (window as Window & { __CSRF_TOKEN__?: string }).__CSRF_TOKEN__ ?? '';
        
        // Copy all entries from the original form data, updating csrf_token
        for (const [key, value] of originalBody.entries()) {
          if (key === 'csrf_token') {
            updatedFormData.append(key, csrfToken);
          } else {
            updatedFormData.append(key, value);
          }
        }
        
        // Retry the request with the new token
        const retryResponse = await fetch(input, {
          credentials: 'include',
          headers: {
            ...defaultHeaders,
            ...headers
          },
          ...rest,
          body: updatedFormData
        });

        if (!retryResponse.ok) {
          const retryText = await retryResponse.text();
          const retryPayload = retryText ? safeJsonParse(retryText) : null;
          const retryMessage =
            typeof retryPayload === 'object' && retryPayload && 'error' in retryPayload
              ? String((retryPayload as { error?: string }).error)
              : `Request failed with status ${retryResponse.status}`;
          throw new ApiError(retryMessage, retryResponse.status, retryPayload);
        }

        const retryText = await retryResponse.text();
        return (retryText ? safeJsonParse(retryText) : null) as TResponse;
      } catch {
        // If token refresh fails, throw the original error
        throw new ApiError(message, response.status, payload);
      }
    }

    throw new ApiError(message, response.status, payload);
  }

  return payload as TResponse;
}

export function buildFormData(payload: Record<string, FormDataEntryValue | undefined>): FormData {
  const formData = new FormData();

  Object.entries(payload).forEach(([key, value]) => {
    if (value === undefined || value === null) {
      return;
    }
    formData.append(key, value);
  });

  return formData;
}

function safeJsonParse(text: string): unknown {
  try {
    return JSON.parse(text);
  } catch (parseError) {
    // Check if the response looks like HTML (common when PHP errors occur)
    const isHtml = text.trim().startsWith('<!') || text.includes('<html') || text.includes('<body');
    const errorMessage = isHtml 
      ? 'Server returned HTML instead of JSON. Check for PHP errors or feature flag status.'
      : 'Failed to parse JSON response';
    
    throw new ApiError(errorMessage, 500, text.substring(0, 500)); // Limit payload size
  }
}

