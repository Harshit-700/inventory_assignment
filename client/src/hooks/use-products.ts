import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  api,
  buildUrl,
  API_BASE,
  type ProductInput,
  type ProductUpdateInput,
} from "@/shared/routes";
import { z } from "zod";

// ============================================
// Products Hooks
// ============================================

// Helper to handle API responses safely
async function handleResponse<T>(
  res: Response,
  schema: z.ZodSchema<T>,
): Promise<T> {
  if (!res.ok) {
    if (res.status === 404) throw new Error("Resource not found");
    // Try to parse error message if available
    try {
      const errorData = await res.json();
      throw new Error(errorData.message || "An unexpected error occurred");
    } catch {
      throw new Error(`API Error: ${res.status}`);
    }
  }
  const data = await res.json();
  return schema.parse(data);
}

// GET /api/products
export function useProducts(filters?: {
  search?: string;
  category?: string;
  status?: string;
}) {
  return useQuery({
    queryKey: [api.products.list.path, filters],
    queryFn: async () => {
      // Build query string manually since filters are optional
      const params = new URLSearchParams();
      if (filters?.search) params.append("search", filters.search);
      if (filters?.category && filters.category !== "all")
        params.append("category", filters.category);
      if (filters?.status && filters.status !== "all")
        params.append("status", filters.status);

      const path = `${api.products.list.path}`;
      const url =
        API_BASE + path + (params.toString() ? `?${params.toString()}` : "");
      const token =
        typeof window !== "undefined" ? localStorage.getItem("token") : null;
      const res = await fetch(url, {
        headers: token ? { Authorization: `Bearer ${token}` } : undefined,
      });
      return handleResponse(res, api.products.list.responses[200]);
    },
  });
}

// GET /api/products/:id
export function useProduct(id: number | null) {
  return useQuery({
    queryKey: [api.products.get.path, id],
    enabled: !!id,
    queryFn: async () => {
      if (!id) throw new Error("ID required");
      const path = buildUrl(api.products.get.path, { id });
      const url = API_BASE + path;
      const token =
        typeof window !== "undefined" ? localStorage.getItem("token") : null;
      const res = await fetch(url, {
        headers: token ? { Authorization: `Bearer ${token}` } : undefined,
      });
      return handleResponse(res, api.products.get.responses[200]);
    },
  });
}

// GET /api/stats
export function useProductStats() {
  return useQuery({
    queryKey: [api.products.getStats.path],
    queryFn: async () => {
      const token =
        typeof window !== "undefined" ? localStorage.getItem("token") : null;
      const res = await fetch(API_BASE + api.products.getStats.path, {
        headers: token ? { Authorization: `Bearer ${token}` } : undefined,
      });
      return handleResponse(res, api.products.getStats.responses[200]);
    },
  });
}

// POST /api/products
export function useCreateProduct() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: ProductInput) => {
      // Ensure numeric fields are numbers (Zod coercion handles this, but good to be safe)
      const payload = {
        ...data,
        price: Number(data.price),
        quantity: Number(data.quantity),
      };

      const validated = api.products.create.input.parse(payload);

      const token =
        typeof window !== "undefined" ? localStorage.getItem("token") : null;
      const headers: Record<string, string> = {
        "Content-Type": "application/json",
        Accept: "application/json",
      };
      if (token) headers["Authorization"] = `Bearer ${token}`;
      const res = await fetch(API_BASE + api.products.create.path, {
        method: api.products.create.method,
        headers,
        body: JSON.stringify(validated),
      });

      return handleResponse(res, api.products.create.responses[201]);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [api.products.list.path] });
      queryClient.invalidateQueries({ queryKey: [api.products.getStats.path] });
    },
  });
}

// PUT /api/products/:id
export function useUpdateProduct() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({
      id,
      ...updates
    }: { id: number } & ProductUpdateInput) => {
      const payload = {
        ...updates,
        price: updates.price ? Number(updates.price) : undefined,
        quantity: updates.quantity ? Number(updates.quantity) : undefined,
      };

      const validated = api.products.update.input.parse(payload);
      const url = buildUrl(api.products.update.path, { id });

      const token =
        typeof window !== "undefined" ? localStorage.getItem("token") : null;
      const headers: Record<string, string> = {
        "Content-Type": "application/json",
        Accept: "application/json",
      };
      if (token) headers["Authorization"] = `Bearer ${token}`;
      const res = await fetch(API_BASE + url, {
        method: api.products.update.method,
        headers,
        body: JSON.stringify(validated),
      });

      return handleResponse(res, api.products.update.responses[200]);
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: [api.products.list.path] });
      queryClient.invalidateQueries({
        queryKey: [api.products.get.path, variables.id],
      });
      queryClient.invalidateQueries({ queryKey: [api.products.getStats.path] });
    },
  });
}

// DELETE /api/products/:id
export function useDeleteProduct() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      const url = buildUrl(api.products.delete.path, { id });
      const token =
        typeof window !== "undefined" ? localStorage.getItem("token") : null;
      const headers: Record<string, string> = { Accept: "application/json" };
      if (token) headers["Authorization"] = `Bearer ${token}`;
      const res = await fetch(API_BASE + url, {
        method: api.products.delete.method,
        headers,
      });

      if (!res.ok && res.status !== 404) {
        throw new Error("Failed to delete product");
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [api.products.list.path] });
      queryClient.invalidateQueries({ queryKey: [api.products.getStats.path] });
    },
  });
}
