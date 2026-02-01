import { z } from "zod";
import {
  productSchema,
  insertProductSchema,
  updateProductSchema,
  statsSchema,
  categorySchema,
  insertCategorySchema,
} from "./schema";

// API Base URL - will be replaced in production
export const API_BASE =
  import.meta.env.VITE_API_URL || "http://localhost:8000/api";

// Helper to build URL with path parameters
export function buildUrl(
  path: string,
  params: Record<string, string | number>,
): string {
  let url = path;
  for (const [key, value] of Object.entries(params)) {
    url = url.replace(`:${key}`, String(value));
  }
  return url;
}

// API Routes Definition
export const api = {
  // Authentication
  auth: {
    login: {
      path: "/auth/login",
      method: "POST" as const,
      input: z.object({
        email: z.string().email(),
        password: z.string(),
      }),
      responses: {
        200: z.object({
          status: z.string(),
          message: z.string(),
          data: z.object({
            user: z.object({
              id: z.number(),
              name: z.string(),
              email: z.string(),
            }),
            token: z.string(),
          }),
        }),
      },
    },
    register: {
      path: "/auth/register",
      method: "POST" as const,
      input: z.object({
        name: z.string(),
        email: z.string().email(),
        password: z.string().min(8),
        password_confirmation: z.string(),
      }),
      responses: {
        201: z.object({
          status: z.string(),
          message: z.string(),
          data: z.object({
            user: z.object({
              id: z.number(),
              name: z.string(),
              email: z.string(),
            }),
            token: z.string(),
          }),
        }),
      },
    },
    logout: {
      path: "/auth/logout",
      method: "POST" as const,
      responses: {
        200: z.object({
          status: z.string(),
          message: z.string(),
        }),
      },
    },
    me: {
      path: "/auth/me",
      method: "GET" as const,
      responses: {
        200: z.object({
          status: z.string(),
          message: z.string(),
          data: z.object({
            id: z.number(),
            name: z.string(),
            email: z.string(),
          }),
        }),
      },
    },
  },

  // Products
  products: {
    list: {
      path: "/products",
      method: "GET" as const,
      responses: {
        200: z.array(productSchema),
      },
    },
    get: {
      path: "/products/:id",
      method: "GET" as const,
      responses: {
        200: productSchema,
      },
    },
    create: {
      path: "/products",
      method: "POST" as const,
      input: insertProductSchema,
      responses: {
        201: productSchema,
      },
    },
    update: {
      path: "/products/:id",
      method: "PUT" as const,
      input: updateProductSchema,
      responses: {
        200: productSchema,
      },
    },
    delete: {
      path: "/products/:id",
      method: "DELETE" as const,
      responses: {
        200: z.object({
          status: z.string(),
          message: z.string(),
        }),
      },
    },
    getStats: {
      path: "/stats",
      method: "GET" as const,
      responses: {
        200: statsSchema,
      },
    },
  },

  // Categories
  categories: {
    list: {
      path: "/categories",
      method: "GET" as const,
      responses: {
        200: z.object({
          status: z.string(),
          message: z.string(),
          data: z.array(categorySchema),
          meta: z.object({
            current_page: z.number(),
            last_page: z.number(),
            per_page: z.number(),
            total: z.number(),
          }),
        }),
      },
    },
    listActive: {
      path: "/categories/active",
      method: "GET" as const,
      responses: {
        200: z.object({
          status: z.string(),
          message: z.string(),
          data: z.array(
            z.object({
              id: z.number(),
              name: z.string(),
            }),
          ),
        }),
      },
    },
    get: {
      path: "/categories/:id",
      method: "GET" as const,
      responses: {
        200: z.object({
          status: z.string(),
          message: z.string(),
          data: categorySchema,
        }),
      },
    },
    create: {
      path: "/categories",
      method: "POST" as const,
      input: insertCategorySchema,
      responses: {
        201: z.object({
          status: z.string(),
          message: z.string(),
          data: categorySchema,
        }),
      },
    },
    update: {
      path: "/categories/:id",
      method: "PUT" as const,
      input: insertCategorySchema.partial(),
      responses: {
        200: z.object({
          status: z.string(),
          message: z.string(),
          data: categorySchema,
        }),
      },
    },
    delete: {
      path: "/categories/:id",
      method: "DELETE" as const,
      responses: {
        200: z.object({
          status: z.string(),
          message: z.string(),
        }),
      },
    },
  },

  // Stock
  stock: {
    movements: {
      path: "/products/:id/stock-movements",
      method: "GET" as const,
    },
    stockIn: {
      path: "/products/:id/stock-in",
      method: "POST" as const,
      input: z.object({
        quantity: z.number().min(1),
        notes: z.string().optional(),
      }),
    },
    stockOut: {
      path: "/products/:id/stock-out",
      method: "POST" as const,
      input: z.object({
        quantity: z.number().min(1),
        notes: z.string().optional(),
      }),
    },
    allMovements: {
      path: "/stock/movements",
      method: "GET" as const,
    },
    statistics: {
      path: "/stock/statistics",
      method: "GET" as const,
    },
  },
};

// Export types for use in hooks
export type ProductInput = z.infer<typeof insertProductSchema>;
export type ProductUpdateInput = z.infer<typeof updateProductSchema>;
export type CategoryInput = z.infer<typeof insertCategorySchema>;
