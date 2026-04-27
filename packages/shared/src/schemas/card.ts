import { z } from "zod";

export const cardSchema = z.object({
  cardholderName: z.string().min(2),
  cardNumber: z.string().regex(/^[0-9]{13,19}$/),
  expMonth: z.string().regex(/^(0?[1-9]|1[0-2])$/),
  expYear: z.string().regex(/^[0-9]{4}$/),
  cvc: z.string().regex(/^[0-9]{3,4}$/).optional(),
  brand: z.string().max(32).optional(),
  billingZip: z.string().max(20).optional(),
  isDefault: z.boolean().default(false)
});

export type CardInput = z.infer<typeof cardSchema>;
