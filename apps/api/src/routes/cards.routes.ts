import { Router } from "express";
import {
  createCard,
  deleteCard,
  listCards,
  makeDefaultCard
} from "../controllers/cards.controller";
import { authenticate } from "../middleware/auth";

export const cardsRouter = Router();

cardsRouter.use(authenticate);
cardsRouter.get("/", listCards);
cardsRouter.post("/", createCard);
cardsRouter.patch("/:id/default", makeDefaultCard);
cardsRouter.delete("/:id", deleteCard);

export default cardsRouter;
