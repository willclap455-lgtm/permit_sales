import type { Request, Response } from "express";
import jwt from "jsonwebtoken";
import { z } from "zod";
import { env } from "../config/env";
import { query } from "../db/pool";
import { comparePassword, hashPassword } from "../utils/password";

const authBody = z.object({
  email: z.string().email().transform((value) => value.toLowerCase()),
  password: z.string().min(8)
});

const registerBody = authBody.extend({
  fullName: z.string().min(2).max(120),
  phone: z.string().max(40).optional()
});

function signToken(user: { id: string; email: string; role: "admin" | "user" }) {
  return jwt.sign({ sub: user.id, email: user.email, role: user.role }, env.JWT_SECRET, { expiresIn: "8h" });
}

export async function register(req: Request, res: Response) {
  const body = registerBody.parse(req.body);
  const passwordHash = await hashPassword(body.password);

  const result = await query<{
    id: string;
    email: string;
    full_name: string;
    role: "user";
  }>(
    `INSERT INTO users (role_id, email, password_hash, full_name, phone)
     SELECT roles.id, $1, $2, $3, $4
     FROM roles
     WHERE roles.name = 'user'
     RETURNING id, email, full_name, 'user'::text AS role`,
    [body.email, passwordHash, body.fullName, body.phone ?? null]
  );

  const user = result.rows[0];
  const token = signToken({ id: user.id, email: user.email, role: user.role });

  res.status(201).json({
    token,
    user: { id: user.id, email: user.email, fullName: user.full_name, role: user.role }
  });
}

export async function login(req: Request, res: Response) {
  const body = authBody.parse(req.body);
  const result = await query<{
    id: string;
    email: string;
    full_name: string;
    password_hash: string;
    role: "admin" | "user";
  }>(
    `SELECT users.id, users.email, users.full_name, users.password_hash, roles.name AS role
     FROM users
     JOIN roles ON roles.id = users.role_id
     WHERE users.email = $1 AND users.deleted_at IS NULL AND users.is_active = TRUE`,
    [body.email]
  );

  const user = result.rows[0];
  if (!user || !(await comparePassword(body.password, user.password_hash))) {
    return res.status(401).json({ message: "Invalid email or password." });
  }

  await query("UPDATE users SET last_login_at = NOW() WHERE id = $1", [user.id]);

  const token = signToken({ id: user.id, email: user.email, role: user.role });
  res.json({
    token,
    user: { id: user.id, email: user.email, fullName: user.full_name, role: user.role }
  });
}
