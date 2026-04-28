import type { Config } from "tailwindcss";

const config: Config = {
  content: ["./app/**/*.{ts,tsx}", "./components/**/*.{ts,tsx}", "./lib/**/*.{ts,tsx}"],
  theme: {
    extend: {
      colors: {
        ink: "#121018",
        cream: "#f8f1e7",
        citron: "#d7ff4f",
        coral: "#ff6b57",
        violet: "#7c5cff",
        asphalt: "#23212b",
      },
      fontFamily: {
        sans: ["var(--font-sans)", "Inter", "system-ui", "sans-serif"],
        display: ["var(--font-display)", "Inter Tight", "system-ui", "sans-serif"],
      },
      boxShadow: {
        glow: "0 24px 80px rgba(124, 92, 255, 0.22)",
        card: "0 30px 70px rgba(18, 16, 24, 0.14)",
      },
    },
  },
  plugins: [],
};

export default config;
