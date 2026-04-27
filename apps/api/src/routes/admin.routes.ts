import { Router } from "express";
import { getAdminMetrics, listAllUsers, listRegisteredVehicles } from "../controllers/admin.controller";
import { authenticate } from "../middleware/auth";
import { requireRole } from "../middleware/requireRole";

export const adminRouter = Router();

adminRouter.use(authenticate, requireRole("admin"));
adminRouter.get("/metrics", getAdminMetrics);
adminRouter.get("/users", listAllUsers);
adminRouter.get("/vehicles", listRegisteredVehicles);

export default adminRouter;
