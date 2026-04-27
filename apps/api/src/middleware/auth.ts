import type { NextFunction, Request, Response } from "express";
import jwt from "jsonwebtoken";
import { env } from "../config/env";
import { pool } from "../db/pool";
import type { AuthenticatedUser } from "../types/auth";

declare global {
  namespace Express {
    interface Request {
      user?: AuthenticatedUser;
    }
  }
}

type JwtPayload = {
  sub: string;
  role: "admin" | "user";
};

export async function requireAuth(req: Request, res: Response, next: NextFunction) {
  const header = req.headers.authorization;
  const token = header?.startsWith("Bearer ") ? header.slice(7) : undefined;

  if (!token) {
    return res.status(401).json({ error: "Missing bearer token." });
  }

  try {
    const payload = jwt.verify(token, env.JWT_SECRET) as JwtPayload;
    const { rows } = await pool.query(
      `SELECT users.id, users.email, users.full_name, roles.name AS role
       FROM users
       JOIN roles ON roles.id = users.role_id
       WHERE users.id = $1 AND users.is_active = TRUE AND users.deleted_at IS NULL`,
      [payload.sub],
    );

    if (!rows[0]) {
      return res.status(401).json({ error: "Account is unavailable." });
    }

    req.user = {
      id: rows[0].id,
      email: rows[0].email,
      fullName: rows[0].full_name,
      role: rows[0].role,
    };
    return next();
  } catch {
    return res.status(401).json({ error: "Invalid or expired token." });
  }
}

export const authenticate = requireAuth;
