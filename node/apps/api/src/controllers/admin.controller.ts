import type { Request, Response } from "express";
import { pool } from "../db/pool";

export async function getMetrics(_request: Request, response: Response) {
  const { rows } = await pool.query<{
    users: string;
    vehicles: string;
    cards: string;
  }>(`
    SELECT
      (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) AS users,
      (SELECT COUNT(*) FROM vehicles WHERE deleted_at IS NULL) AS vehicles,
      (SELECT COUNT(*) FROM credit_cards WHERE deleted_at IS NULL) AS cards
  `);

  response.json({
    users: Number(rows[0].users),
    vehicles: Number(rows[0].vehicles),
    cards: Number(rows[0].cards)
  });
}

export const getAdminMetrics = getMetrics;

export async function listUsers(_request: Request, response: Response) {
  const { rows } = await pool.query(`
    SELECT users.id, users.email, users.full_name, roles.name AS role, users.is_active, users.created_at
    FROM users
    JOIN roles ON roles.id = users.role_id
    WHERE users.deleted_at IS NULL
    ORDER BY users.created_at DESC
  `);

  response.json({ users: rows });
}

export const listAllUsers = listUsers;

export async function listAllVehicles(_request: Request, response: Response) {
  const { rows } = await pool.query(`
    SELECT
      vehicles.id,
      vehicles.make,
      vehicles.model,
      vehicles.color,
      vehicles.license_plate,
      vehicles.license_plate_region,
      vehicles.created_at,
      users.full_name AS owner_name,
      users.email AS owner_email
    FROM vehicles
    JOIN users ON users.id = vehicles.user_id
    WHERE vehicles.deleted_at IS NULL
    ORDER BY vehicles.created_at DESC
  `);

  response.json({ vehicles: rows });
}

export const listRegisteredVehicles = listAllVehicles;
