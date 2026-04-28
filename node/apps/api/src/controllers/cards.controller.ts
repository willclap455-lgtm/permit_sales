import { Request, Response } from "express";
import { z } from "zod";
import { pool } from "../db/pool";
import { encryptValue, hashCardLastFour } from "../utils/encryption";

const createCardSchema = z.object({
  cardholderName: z.string().min(2),
  cardNumber: z.string().regex(/^[0-9]{13,19}$/),
  expMonth: z.string().regex(/^(0?[1-9]|1[0-2])$/),
  expYear: z.string().regex(/^[0-9]{4}$/),
  cvc: z.string().regex(/^[0-9]{3,4}$/).optional(),
  brand: z.string().max(32).optional(),
  billingZip: z.string().max(20).optional(),
  isDefault: z.boolean().default(false)
});

export async function listCards(req: Request, res: Response) {
  const cards = await pool.query(
    `SELECT id, cardholder_name, brand, display_last_four, billing_zip, is_default, created_at
     FROM credit_cards
     WHERE user_id = $1 AND deleted_at IS NULL
     ORDER BY is_default DESC, created_at DESC`,
    [req.user!.id]
  );
  res.json({ cards: cards.rows });
}

export async function createCard(req: Request, res: Response) {
  const parsed = createCardSchema.safeParse(req.body);
  if (!parsed.success) {
    return res.status(400).json({ message: "Invalid card payload", issues: parsed.error.flatten() });
  }

  const { cardholderName, cardNumber, expMonth, expYear, cvc, brand, billingZip, isDefault } = parsed.data;
  const encryptedNumber = encryptValue(cardNumber);
  const encryptedExpMonth = encryptValue(expMonth.padStart(2, "0"));
  const encryptedExpYear = encryptValue(expYear);
  const encryptedCvc = cvc ? encryptValue(cvc) : null;
  const lastFour = cardNumber.slice(-4);

  const client = await pool.connect();
  try {
    await client.query("BEGIN");
    if (isDefault) {
      await client.query("UPDATE credit_cards SET is_default = FALSE WHERE user_id = $1", [req.user!.id]);
    }

    const result = await client.query(
      `INSERT INTO credit_cards (
        user_id, cardholder_name, brand,
        encrypted_card_number, card_number_iv, card_number_auth_tag,
        encrypted_exp_month, exp_month_iv, exp_month_auth_tag,
        encrypted_exp_year, exp_year_iv, exp_year_auth_tag,
        encrypted_cvc, cvc_iv, cvc_auth_tag,
        last_four_hash, display_last_four, billing_zip, is_default
      )
      VALUES ($1, $2, $3, decode($4, 'base64'), decode($5, 'base64'), decode($6, 'base64'),
              decode($7, 'base64'), decode($8, 'base64'), decode($9, 'base64'),
              decode($10, 'base64'), decode($11, 'base64'), decode($12, 'base64'),
              CASE WHEN $13::text IS NULL THEN NULL ELSE decode($13, 'base64') END,
              CASE WHEN $14::text IS NULL THEN NULL ELSE decode($14, 'base64') END,
              CASE WHEN $15::text IS NULL THEN NULL ELSE decode($15, 'base64') END,
              $16, $17, $18, $19)
      RETURNING id, cardholder_name, brand, display_last_four, billing_zip, is_default, created_at`,
      [
        req.user!.id,
        cardholderName,
        brand ?? null,
        encryptedNumber.ciphertext,
        encryptedNumber.iv,
        encryptedNumber.authTag,
        encryptedExpMonth.ciphertext,
        encryptedExpMonth.iv,
        encryptedExpMonth.authTag,
        encryptedExpYear.ciphertext,
        encryptedExpYear.iv,
        encryptedExpYear.authTag,
        encryptedCvc?.ciphertext ?? null,
        encryptedCvc?.iv ?? null,
        encryptedCvc?.authTag ?? null,
        hashCardLastFour(lastFour),
        lastFour,
        billingZip ?? null,
        isDefault
      ]
    );
    await client.query("COMMIT");
    res.status(201).json({ card: result.rows[0] });
  } catch (error) {
    await client.query("ROLLBACK");
    throw error;
  } finally {
    client.release();
  }
}

export async function deleteCard(req: Request, res: Response) {
  const result = await pool.query(
    "UPDATE credit_cards SET deleted_at = NOW(), is_default = FALSE WHERE id = $1 AND user_id = $2 AND deleted_at IS NULL RETURNING id",
    [req.params.id, req.user!.id]
  );
  if (!result.rowCount) {
    return res.status(404).json({ message: "Card not found" });
  }
  res.status(204).send();
}

export async function makeDefaultCard(req: Request, res: Response) {
  const client = await pool.connect();
  try {
    await client.query("BEGIN");
    const { rowCount } = await client.query(
      "UPDATE credit_cards SET is_default = FALSE WHERE user_id = $1 AND deleted_at IS NULL",
      [req.user!.id]
    );
    const result = await client.query(
      `UPDATE credit_cards
       SET is_default = TRUE
       WHERE id = $1 AND user_id = $2 AND deleted_at IS NULL
       RETURNING id, cardholder_name, brand, display_last_four, billing_zip, is_default, created_at`,
      [req.params.id, req.user!.id]
    );

    if (!result.rows[0]) {
      await client.query("ROLLBACK");
      return res.status(404).json({ message: "Card not found" });
    }

    await client.query("COMMIT");
    res.json({ card: result.rows[0], previousDefaultCardsUpdated: rowCount });
  } catch (error) {
    await client.query("ROLLBACK");
    throw error;
  } finally {
    client.release();
  }
}
