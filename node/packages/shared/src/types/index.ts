export type UserRole = "admin" | "user";

export interface AuthUser {
  id: string;
  email: string;
  fullName: string;
  role: UserRole;
}
