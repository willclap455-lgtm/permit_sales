import dotenv from "dotenv";
import { z } from "zod";

dotenv.config();

const envSchema = z.object({
  NODE_ENV: z.enum(["development", "test", "production"]).default("development"),
  PORT: z.coerce.number().default(4000),
  DATABASE_URL: z.string().url().or(z.string().startsWith("postgres://")),
  JWT_SECRET: z.string().min(32, "JWT_SECRET must be at least 32 characters"),
  CARD_ENCRYPTION_KEY: z.string().min(44, "CARD_ENCRYPTION_KEY must be a base64 encoded 32-byte key"),
  LAST_FOUR_HASH_PEPPER: z.string().min(16, "LAST_FOUR_HASH_PEPPER must be at least 16 characters"),
  CORS_ORIGIN: z.string().default("http://localhost:3000")
});

export const env = envSchema.parse(process.env);
