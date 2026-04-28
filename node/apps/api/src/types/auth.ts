export type UserRole = "admin" | "user";

export type AuthUser = {
  id: string;
  email: string;
  fullName: string;
  role: UserRole;
};

export type AuthenticatedUser = AuthUser;

declare global {
  namespace Express {
    interface Request {
      user?: AuthUser;
    }
  }
}
