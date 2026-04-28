import type { NextFunction, Request, Response } from "express";

export function requireRole(...roles: Array<"admin" | "user">) {
  return (req: Request, res: Response, next: NextFunction) => {
    if (!req.user || !roles.includes(req.user.role)) {
      return res.status(403).json({ error: "Forbidden" });
    }

    return next();
  };
}
