import type { Request, Response } from "express";
import { z } from "zod";
import { pool } from "../db/pool";

const vehicleSchema = z.object({
  make: z.string().trim().min(1),
  model: z.string().trim().min(1),
  color: z.string().trim().min(1),
  licensePlate: z.string().trim().min(1),
  licensePlateRegion: z.string().trim().optional()
});

export async function listVehicles(req: Request, res: Response) {
  const { rows } = await pool.query(
    `SELECT id, make, model, color, license_plate AS "licensePlate",
      license_plate_region AS "licensePlateRegion", is_active AS "isActive", created_at AS "createdAt"
     FROM vehicles
     WHERE user_id = $1 AND deleted_at IS NULL
     ORDER BY created_at DESC`,
    [req.user!.id]
  );

  res.json({ vehicles: rows });
}

export const getVehicles = listVehicles;

export async function createVehicle(req: Request, res: Response) {
  const vehicle = vehicleSchema.parse(req.body);
  const { rows } = await pool.query(
    `INSERT INTO vehicles (user_id, make, model, color, license_plate, license_plate_region)
     VALUES ($1, $2, $3, $4, $5, $6)
     RETURNING id, make, model, color, license_plate AS "licensePlate",
      license_plate_region AS "licensePlateRegion", created_at AS "createdAt"`,
    [
      req.user!.id,
      vehicle.make,
      vehicle.model,
      vehicle.color,
      vehicle.licensePlate.toUpperCase(),
      vehicle.licensePlateRegion
    ]
  );

  res.status(201).json({ vehicle: rows[0] });
}

export async function updateVehicle(req: Request, res: Response) {
  const vehicle = vehicleSchema.parse(req.body);
  const { rows } = await pool.query(
    `UPDATE vehicles
     SET make = $1, model = $2, color = $3, license_plate = $4, license_plate_region = $5
     WHERE id = $6 AND user_id = $7 AND deleted_at IS NULL
     RETURNING id, make, model, color, license_plate AS "licensePlate",
      license_plate_region AS "licensePlateRegion", updated_at AS "updatedAt"`,
    [
      vehicle.make,
      vehicle.model,
      vehicle.color,
      vehicle.licensePlate.toUpperCase(),
      vehicle.licensePlateRegion,
      req.params.id,
      req.user!.id
    ]
  );

  if (!rows[0]) {
    return res.status(404).json({ message: "Vehicle not found" });
  }

  res.json({ vehicle: rows[0] });
}

export async function deleteVehicle(req: Request, res: Response) {
  const { rowCount } = await pool.query(
    "UPDATE vehicles SET deleted_at = NOW(), is_active = FALSE WHERE id = $1 AND user_id = $2 AND deleted_at IS NULL",
    [req.params.id, req.user!.id]
  );

  if (!rowCount) {
    return res.status(404).json({ message: "Vehicle not found" });
  }

  res.status(204).send();
}
