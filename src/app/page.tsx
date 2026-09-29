import Link from "next/link";
import { Upload, Sparkles, Layers, ArrowRight, CheckCircle2, ShieldCheck, Search } from "lucide-react";

export default function Home() {
  return (
    <div className="relative overflow-hidden">
      {/* Background glow effects */}
      <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-[#00d4aa]/10 blur-[140px] pointer-events-none rounded-full" />
      <div className="absolute top-1/3 right-10 w-[400px] h-[400px] bg-[#037ef3]/10 blur-[120px] pointer-events-none rounded-full" />

      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32 flex flex-col items-center text-center relative z-10">
        <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#161a24] border border-[#242a3a] text-xs font-semibold text-[#00d4aa] mb-8">
          <Sparkles className="w-3.5 h-3.5" />
          <span>Next.js + TypeScript + Groq AI Engine</span>
        </div>

        <h1 className="text-4xl sm:text-6xl font-extrabold tracking-tight text-white max-w-4xl leading-tight">
          Find Your Ideal <span className="gradient-text">AIESEC Global Opportunity</span> in Seconds
        </h1>

        <p className="mt-6 text-lg sm:text-xl text-[#8b95a6] max-w-2xl leading-relaxed">
          Upload your CV to extract your skills & profile data automatically. Our AI engine ranks the best international exchange opportunities tailored specifically for you.
        </p>

        <div className="mt-10 flex flex-wrap gap-4 justify-center">
          <Link
            href="/upload"
            className="flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold bg-[#00d4aa] text-black hover:bg-[#00c099] transition-all shadow-lg shadow-[#00d4aa]/25 hover:scale-105"
          >
            <Upload className="w-5 h-5" />
            Upload Your CV
            <ArrowRight className="w-4 h-4 ml-1" />
          </Link>

          <Link
            href="/ep-management"
            className="flex items-center gap-2 px-6 py-3.5 rounded-xl font-bold bg-[#161a24] border border-[#242a3a] text-white hover:bg-[#242a3a] transition-all"
          >
            <Layers className="w-5 h-5 text-[#037ef3]" />
            EP Pipeline Dashboard
          </Link>
        </div>

        {/* Feature Highlights Grid */}
        <div className="mt-24 grid grid-cols-1 md:grid-cols-3 gap-8 w-full max-w-5xl">
          <div className="bg-[#161a24] border border-[#242a3a] p-6 rounded-2xl text-left hover:border-[#00d4aa]/40 transition-colors">
            <div className="w-12 h-12 rounded-xl bg-[#00d4aa]/15 flex items-center justify-center text-[#00d4aa] mb-4">
              <Search className="w-6 h-6" />
            </div>
            <h3 className="text-lg font-bold text-white mb-2">Smart AI Matcher</h3>
            <p className="text-sm text-[#8b95a6] leading-relaxed">
              Extracts education, skills, and background from PDF CVs to calculate candidate match scores.
            </p>
          </div>

          <div className="bg-[#161a24] border border-[#242a3a] p-6 rounded-2xl text-left hover:border-[#037ef3]/40 transition-colors">
            <div className="w-12 h-12 rounded-xl bg-[#037ef3]/15 flex items-center justify-center text-[#037ef3] mb-4">
              <Layers className="w-6 h-6" />
            </div>
            <h3 className="text-lg font-bold text-white mb-2">EP Lifecycle Management</h3>
            <p className="text-sm text-[#8b95a6] leading-relaxed">
              Track participants through Applied, Accepted, Confirmed, Surveys, and Experience completion stages.
            </p>
          </div>

          <div className="bg-[#161a24] border border-[#242a3a] p-6 rounded-2xl text-left hover:border-[#00d4aa]/40 transition-colors">
            <div className="w-12 h-12 rounded-xl bg-purple-500/15 flex items-center justify-center text-purple-400 mb-4">
              <ShieldCheck className="w-6 h-6" />
            </div>
            <h3 className="text-lg font-bold text-white mb-2">Admin Backoffice</h3>
            <p className="text-sm text-[#8b95a6] leading-relaxed">
              Upload opportunity CSVs, monitor user signups, manage EP document vaults, and review stats.
            </p>
          </div>
        </div>

        {/* Deployment Badge */}
        <div className="mt-16 flex items-center gap-2 text-xs text-[#8b95a6] bg-[#161a24]/60 px-4 py-2 rounded-lg border border-[#242a3a]">
          <CheckCircle2 className="w-4 h-4 text-[#00d4aa]" />
          <span>Vercel Ready: Serverless API Route Handlers + Prisma ORM SQLite/PostgreSQL support.</span>
        </div>
      </section>
    </div>
  );
}
