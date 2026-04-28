import { Pool, type QueryResultRow } from "pg";
import { env } from "../config/env";

export const pool = new Pool({
  connectionString: env.DATABASE_URL,
  ssl: env.NODE_ENV === "production" ? { rejectUnauthorized: false } : undefined,
});

export const query = <T extends QueryResultRow = QueryResultRow>(text: string, params?: unknown[]) => pool.query<T>(text, params);
