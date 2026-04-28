import { Router } from "express";
import {
  createVehicle,
  deleteVehicle,
  getVehicles,
  updateVehicle,
} from "../controllers/vehicles.controller";
import { authenticate } from "../middleware/auth";

export const vehiclesRouter = Router();

vehiclesRouter.use(authenticate);
vehiclesRouter.get("/", getVehicles);
vehiclesRouter.post("/", createVehicle);
vehiclesRouter.patch("/:id", updateVehicle);
vehiclesRouter.delete("/:id", deleteVehicle);

export default vehiclesRouter;
