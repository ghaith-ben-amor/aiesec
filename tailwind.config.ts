import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./src/pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/components/**/*.{js,ts,jsx,tsx,mdx}",
    "./src/app/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    extend: {
      colors: {
        background: "var(--background)",
        foreground: "var(--foreground)",
        aiesec: {
          blue: "#037Ef3",
          teal: "#00D4AA",
          dark: "#0B0D12",
          card: "#161A24",
          border: "#242A3A",
          muted: "#8B95A6"
        }
      },
    },
  },
  plugins: [],
};
export default config;
