import { createCipheriv, createDecipheriv, createHash, randomBytes } from "node:crypto";
import { env } from "../config/env";

const ALGORITHM = "aes-256-gcm";
const IV_LENGTH = 12;
const AUTH_TAG_LENGTH = 16;

function getEncryptionKey(): Buffer {
  const rawKey = env.CARD_ENCRYPTION_KEY;
  const base64Key = Buffer.from(rawKey, "base64");

  if (base64Key.length === 32) {
    return base64Key;
  }

  if (rawKey.length === 64 && /^[a-f0-9]+$/i.test(rawKey)) {
    return Buffer.from(rawKey, "hex");
  }

  if (rawKey.length >= 32) {
    return createHash("sha256").update(rawKey).digest();
  }

  throw new Error("CARD_ENCRYPTION_KEY must be a 32-byte base64 key, 64-char hex key, or passphrase >= 32 chars.");
}

export interface EncryptedField {
  ciphertext: Buffer;
  iv: Buffer;
  authTag: Buffer;
}

export function encryptField(plaintext: string): EncryptedField {
  const iv = randomBytes(IV_LENGTH);
  const cipher = createCipheriv(ALGORITHM, getEncryptionKey(), iv, {
    authTagLength: AUTH_TAG_LENGTH,
  });

  const ciphertext = Buffer.concat([cipher.update(plaintext, "utf8"), cipher.final()]);
  const authTag = cipher.getAuthTag();

  return { ciphertext, iv, authTag };
}

export const encryptValue = encryptField;

export function decryptField(field: EncryptedField): string {
  const decipher = createDecipheriv(ALGORITHM, getEncryptionKey(), field.iv, {
    authTagLength: AUTH_TAG_LENGTH,
  });
  decipher.setAuthTag(field.authTag);

  return Buffer.concat([decipher.update(field.ciphertext), decipher.final()]).toString("utf8");
}

export function hashLastFour(lastFour: string): string {
  return createHash("sha256")
    .update(`${env.LAST_FOUR_HASH_PEPPER}:${lastFour}`)
    .digest("hex");
}

export const hashCardLastFour = hashLastFour;
