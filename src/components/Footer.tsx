export default function Footer() {
  return (
    <footer className="border-t border-[#242a3a] bg-[#0b0d12] py-8 mt-auto">
      <div className="max-w-7xl mx-auto px-4 text-center text-sm text-[#8b95a6]">
        <p>© {new Date().getFullYear()} AIESEC Opportunity Matcher & EP Management Platform.</p>
        <p className="mt-1 text-xs text-gray-500">
          Powered by Next.js, TypeScript, Prisma & Groq AI engine. Ready for Vercel deployment.
        </p>
      </div>
    </footer>
  );
}
