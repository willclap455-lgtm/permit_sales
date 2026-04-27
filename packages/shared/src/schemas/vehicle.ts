import { z } from "zod";

export const vehicleSchema = z.object({
  make: z.string().trim().min(1, "Make is required"),
  model: z.string().trim().min(1, "Model is required"),
  color: z.string().trim().min(1, "Color is required"),
  licensePlate: z.string().trim().min(1, "License plate is required"),
  licensePlateRegion: z.string().trim().optional()
});

export type VehicleInput = z.infer<typeof vehicleSchema>;
