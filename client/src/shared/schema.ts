import { z } from "zod";

// ============================================
// Category Schema
// ============================================

export const categorySchema = z.object({
  id: z.number(),
  name: z.string(),
  description: z.string().nullable(),
  status: z.enum(["active", "inactive"]),
  created_at: z.string().optional(),
  updated_at: z.string().optional(),
  products_count: z.number().optional(),
});

export const insertCategorySchema = z.object({
  name: z.string().min(1, "Category name is required"),
  description: z.string().nullable().optional(),
  status: z.enum(["active", "inactive"]).optional().default("active"),
});

export type Category = z.infer<typeof categorySchema>;
export type InsertCategory = z.infer<typeof insertCategorySchema>;

// ============================================
// Product Schema
// ============================================

export const productSchema = z.object({
  id: z.number(),
  name: z.string(),
  sku: z.string(),
  category: z.string().nullable(),
  category_id: z.number().optional(),
  category_relation: z
    .object({
      id: z.number(),
      name: z.string(),
    })
    .optional()
    .nullable(),
  description: z.string().nullable(),
  image_url: z.string().nullable(),
  price: z.number(),
  quantity: z.number(),
  status: z.enum(["in_stock", "low_stock", "out_of_stock"]),
  created_at: z.string().optional(),
  updated_at: z.string().optional(),
});

export const insertProductSchema = z.object({
  name: z.string().min(1, "Product name is required"),
  sku: z.string().min(1, "SKU is required"),
  category_id: z.coerce.number().min(1, "Category is required"),
  description: z.string().nullable().optional(),
  image_url: z.string().url().nullish().or(z.literal("")),
  price: z.coerce.number().min(0, "Price must be positive"),
  quantity: z.coerce.number().int().min(0, "Quantity must be non-negative"),
  status: z.enum(["in_stock", "low_stock", "out_of_stock"]).optional(),
});

export const updateProductSchema = insertProductSchema.partial();

export type Product = z.infer<typeof productSchema>;
export type InsertProduct = z.infer<typeof insertProductSchema>;
export type UpdateProduct = z.infer<typeof updateProductSchema>;

// ============================================
// Stats Schema
// ============================================

export const statsSchema = z.object({
  totalProducts: z.number(),
  totalValue: z.number(),
  lowStockCount: z.number(),
  outOfStockCount: z.number(),
  categoriesCount: z.number(),
  lowStockProducts: z
    .array(
      z.object({
        id: z.number(),
        name: z.string(),
        sku: z.string(),
        category: z.string().nullable(),
        quantity: z.number(),
      }),
    )
    .optional(),
  categoryBreakdown: z
    .array(
      z.object({
        name: z.string(),
        value: z.number(),
      }),
    )
    .optional(),
});

export type Stats = z.infer<typeof statsSchema>;

// ============================================
// Stock Movement Schema
// ============================================

export const stockMovementSchema = z.object({
  id: z.number(),
  product_id: z.number(),
  user_id: z.number().nullable(),
  type: z.enum(["in", "out"]),
  quantity: z.number(),
  previous_quantity: z.number(),
  new_quantity: z.number(),
  notes: z.string().nullable(),
  created_at: z.string(),
  product: z
    .object({
      id: z.number(),
      name: z.string(),
      sku: z.string(),
    })
    .optional(),
  user: z
    .object({
      id: z.number(),
      name: z.string(),
    })
    .nullable()
    .optional(),
});

export type StockMovement = z.infer<typeof stockMovementSchema>;

// ============================================
// Auth Schema
// ============================================

export const userSchema = z.object({
  id: z.number(),
  name: z.string(),
  email: z.string().email(),
});

export const loginSchema = z.object({
  email: z.string().email("Invalid email address"),
  password: z.string().min(1, "Password is required"),
});

export const registerSchema = z
  .object({
    name: z.string().min(1, "Name is required"),
    email: z.string().email("Invalid email address"),
    password: z.string().min(8, "Password must be at least 8 characters"),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Passwords don't match",
    path: ["password_confirmation"],
  });

export type User = z.infer<typeof userSchema>;
export type LoginInput = z.infer<typeof loginSchema>;
export type RegisterInput = z.infer<typeof registerSchema>;
