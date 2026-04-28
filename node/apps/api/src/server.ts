import cors from "cors";
import express from "express";
import helmet from "helmet";
import { env } from "./config/env";
import { adminRouter } from "./routes/admin.routes";
import { authRouter } from "./routes/auth.routes";
import { cardsRouter } from "./routes/cards.routes";
import { vehiclesRouter } from "./routes/vehicles.routes";

const app = express();

app.use(helmet());
app.use(cors({ origin: env.CORS_ORIGIN, credentials: true }));
app.use(express.json({ limit: "32kb" }));

app.get("/health", (_req, res) => {
  res.json({ ok: true, service: "permitsales-api" });
});

app.use("/api/auth", authRouter);
app.use("/api/vehicles", vehiclesRouter);
app.use("/api/cards", cardsRouter);
app.use("/api/admin", adminRouter);

app.use((_req, res) => {
  res.status(404).json({ message: "Route not found" });
});

app.listen(env.PORT, () => {
  console.log(`PermitSales API listening on :${env.PORT}`);
});
