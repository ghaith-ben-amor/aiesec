import Link from "next/link";

export default function WelcomePage() {
  return (
    <div className="relative min-h-screen bg-[#0b0d12] text-[#f1f4f9] flex flex-col items-center justify-center px-4 py-12 overflow-hidden">
      {/* Background Radial Glow */}
      <div 
        className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[160vw] h-[160vh] max-w-[1200px] max-h-[1200px] pointer-events-none rounded-full"
        style={{
          background: "radial-gradient(ellipse at center, rgba(0, 212, 170, 0.15) 0%, transparent 70%)"
        }}
      />

      <main className="relative z-10 max-w-[760px] w-full text-center flex flex-col items-center">
        {/* Layer Icon Logo */}
        <div 
          className="w-20 h-20 rounded-[20px] flex items-center justify-center mb-6"
          style={{
            background: "linear-gradient(135deg, #00d4aa 0%, #0099ff 100%)",
            boxShadow: "0 20px 40px rgba(0, 212, 170, 0.3)"
          }}
        >
          <svg 
            className="w-12 h-12 text-[#0b0d12]" 
            viewBox="0 0 24 24" 
            fill="none" 
            stroke="currentColor" 
            strokeWidth="2" 
            strokeLinecap="round" 
            strokeLinejoin="round"
          >
            <path d="M12 2L2 7l10 5 10-5-10-5z" />
            <path d="M2 17l10 5 10-5" />
            <path d="M2 12l10 5 10-5" />
          </svg>
        </div>

        {/* Title */}
        <h1 
          className="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight mb-4 leading-[1.1]"
          style={{
            background: "linear-gradient(135deg, #f1f4f9 0%, #00d4aa 100%)",
            WebkitBackgroundClip: "text",
            WebkitTextFillColor: "transparent"
          }}
        >
          AIESEC Opportunity Matcher
        </h1>

        {/* Tagline */}
        <p className="text-base sm:text-lg md:text-xl text-[#8b95a6] mb-10 max-w-[560px] leading-relaxed">
          AI-powered CV analysis to match you with the perfect AIESEC Global Talent and Global Volunteer opportunities worldwide.
        </p>

        {/* Button Group */}
        <div className="flex flex-wrap gap-4 justify-center mb-14">
          <Link
            href="/login"
            className="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl font-bold text-[#0b0d12] transition-all hover:-translate-y-0.5"
            style={{
              background: "linear-gradient(135deg, #00d4aa 0%, #0099ff 100%)",
              boxShadow: "0 10px 30px rgba(0, 212, 170, 0.3)"
            }}
          >
            <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M9 18l6-6-6-6" />
            </svg>
            Launch Application
          </Link>

          <Link
            href="/signup"
            className="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl font-semibold text-[#f1f4f9] bg-[#161a24] border border-[#242a3a] hover:border-[#00d4aa] hover:bg-[#12151d] hover:text-[#00d4aa] transition-all"
          >
            <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="12" cy="12" r="10" />
              <path d="M12 8v8M8 12h8" />
            </svg>
            Create Account
          </Link>
        </div>

        {/* Feature Cards Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 w-full text-left">
          <article className="bg-[#161a24] border border-[#242a3a] rounded-2xl p-6 transition-all hover:border-[#00d4aa] hover:-translate-y-1">
            <div className="w-12 h-12 rounded-xl bg-[rgba(0,212,170,0.15)] flex items-center justify-center text-[#00d4aa] mb-4">
              <svg className="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" y1="13" x2="8" y2="13" />
                <line x1="16" y1="17" x2="8" y2="17" />
              </svg>
            </div>
            <h3 className="text-lg font-bold text-white mb-2">Smart CV Analysis</h3>
            <p className="text-sm text-[#8b95a6] leading-relaxed">
              Upload your PDF CV and our AI extracts skills, experience, and education automatically.
            </p>
          </article>

          <article className="bg-[#161a24] border border-[#242a3a] rounded-2xl p-6 transition-all hover:border-[#00d4aa] hover:-translate-y-1">
            <div className="w-12 h-12 rounded-xl bg-[rgba(0,212,170,0.15)] flex items-center justify-center text-[#00d4aa] mb-4">
              <svg className="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                <line x1="8" y1="11" x2="8" y2="11.01" />
                <line x1="14" y1="11" x2="14" y2="11.01" />
              </svg>
            </div>
            <h3 className="text-lg font-bold text-white mb-2">Global Opportunities</h3>
            <p className="text-sm text-[#8b95a6] leading-relaxed">
              Access thousands of AIESEC Global Talent and Volunteer opportunities worldwide.
            </p>
          </article>

          <article className="bg-[#161a24] border border-[#242a3a] rounded-2xl p-6 transition-all hover:border-[#00d4aa] hover:-translate-y-1">
            <div className="w-12 h-12 rounded-xl bg-[rgba(0,212,170,0.15)] flex items-center justify-center text-[#00d4aa] mb-4">
              <svg className="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                <line x1="12" y1="22.08" x2="12" y2="12" />
              </svg>
            </div>
            <h3 className="text-lg font-bold text-white mb-2">Intelligent Matching</h3>
            <p className="text-sm text-[#8b95a6] leading-relaxed">
              Get ranked matches based on your skills, location preferences, and program type.
            </p>
          </article>
        </div>
      </main>

      <footer className="mt-16 text-center text-xs text-[#8b95a6] space-y-1">
        <p>Built for AIESEC members &copy; 2025 · <a href="https://aiesec.org" target="_blank" rel="noopener" className="text-[#00d4aa] hover:underline">aiesec.org</a></p>
        <p><a href="https://github.com/ghaith-ben-amor/aiesec" target="_blank" rel="noopener" className="text-[#00d4aa] hover:underline">View Source on GitHub</a></p>
      </footer>
    </div>
  );
}
