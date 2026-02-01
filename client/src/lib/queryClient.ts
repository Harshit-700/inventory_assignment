import { QueryClient, QueryFunction } from "@tanstack/react-query";
import { API_BASE } from "@/shared/routes";

async function throwIfResNotOk(res: Response) {
  if (!res.ok) {
    const text = (await res.text()) || res.statusText;
    throw new Error(`${res.status}: ${text}`);
  }
}

export async function apiRequest(
  method: string,
  url: string,
  data?: unknown | undefined,
): Promise<Response> {
  const token =
    typeof window !== "undefined" ? localStorage.getItem("token") : null;
  const headers: Record<string, string> = data
    ? { "Content-Type": "application/json" }
    : {};
  if (token) headers["Authorization"] = `Bearer ${token}`;

  // If a relative API path is provided, prefix with API_BASE from env
  const fullUrl = url.startsWith("/")
    ? API_BASE.replace(/\/api$/, "") + url
    : url;

  const res = await fetch(fullUrl, {
    method,
    headers,
    body: data ? JSON.stringify(data) : undefined,
    credentials: "include",
  });

  await throwIfResNotOk(res);
  return res;
}

type UnauthorizedBehavior = "returnNull" | "throw";
export const getQueryFn: <T>(options: {
  on401: UnauthorizedBehavior;
}) => QueryFunction<T> =
  ({ on401: unauthorizedBehavior }) =>
  async ({ queryKey }) => {
    const token =
      typeof window !== "undefined" ? localStorage.getItem("token") : null;
    const headers: Record<string, string> = {};
    if (token) headers["Authorization"] = `Bearer ${token}`;

    // Build a full URL from the queryKey
    let url = queryKey[0] as string;
    // If the key is a relative API path, prefix with API_BASE
    if (typeof url === "string" && url.startsWith("/")) {
      url = API_BASE + url;
    }

    // If the second segment is an object, treat it as query params
    if (
      queryKey.length > 1 &&
      typeof queryKey[1] === "object" &&
      queryKey[1] !== null
    ) {
      const params = new URLSearchParams();
      Object.entries(
        queryKey[1] as Record<string, string | number | undefined>,
      ).forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== "")
          params.append(k, String(v));
      });
      const qs = params.toString();
      if (qs) url = url + (url.includes("?") ? "&" : "?") + qs;
    }

    const res = await fetch(url, {
      headers,
      credentials: "include",
    });

    if (unauthorizedBehavior === "returnNull" && res.status === 401) {
      return null;
    }

    await throwIfResNotOk(res);
    return await res.json();
  };

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      queryFn: getQueryFn({ on401: "throw" }),
      refetchInterval: false,
      refetchOnWindowFocus: false,
      staleTime: Infinity,
      retry: false,
    },
    mutations: {
      retry: false,
    },
  },
});
