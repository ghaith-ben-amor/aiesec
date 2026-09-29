"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";

export default function SignupPage() {
  const router = useRouter();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [role, setRole] = useState<"member" | "admin">("member");
  const [adminCode, setAdminCode] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (password !== passwordConfirmation) {
      setError("Passwords do not match");
      return;
    }

    setError("");
    setLoading(true);

    try {
      const res = await fetch("/api/auth/signup", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name, email, password, role, adminCode }),
      });
      const data = await res.json();

      if (!res.ok) throw new Error(data.error || "Signup failed");

      if (role === "admin") {
        router.push("/admin");
      } else {
        router.push("/upload");
      }
      router.refresh();
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div 
      className="min-h-screen relative flex items-center justify-center px-4 py-12"
      style={{
        background: `
          radial-gradient(circle at top left, rgba(124, 140, 255, 0.22), transparent 28%),
          radial-gradient(circle at 85% 12%, rgba(110, 231, 255, 0.18), transparent 22%),
          radial-gradient(circle at bottom right, rgba(155, 123, 255, 0.18), transparent 26%),
          linear-gradient(160deg, #050816 0%, #0a1024 52%, #111a38 100%)
        `,
      }}
    >
      {/* Grid Pattern Overlay */}
      <div 
        className="fixed inset-0 pointer-events-none opacity-30"
        style={{
          backgroundImage: `
            linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px)
          `,
          backgroundSize: "28px 28px",
          maskImage: "radial-gradient(circle at center, black 58%, transparent 100%)",
        }}
      />

      <div className="relative z-10 max-w-5xl w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
        {/* Left Column (order 2 on desktop to match signup.php) */}
        <div className="lg:col-span-5 lg:order-2 pl-lg-4 text-left">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold mb-6 border border-[rgba(124,140,255,0.18)] bg-[rgba(124,140,255,0.1)] text-[#dfe5ff]">
            <span className="w-2 h-2 rounded-full bg-[#60f0b0] shadow-[0_0_12px_#60f0b0]" />
            Join the storm
          </div>
          <h1 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-[#f5f7ff] tracking-tight leading-[1.08] mb-4">
            Create your account in a sharper, modern style.
          </h1>
          <p className="text-base sm:text-lg text-[#aeb8d8] leading-relaxed">
            The signup flow matches the login page with a darker visual identity, electric light, and a cleaner premium feel.
          </p>
        </div>

        {/* Right Column: Auth Panel Card */}
        <div className="lg:col-span-7 lg:order-1">
          <div 
            className="rounded-[1.6rem] p-6 sm:p-10 border border-[rgba(145,160,255,0.16)] bg-[rgba(12,18,40,0.85)] backdrop-blur-xl shadow-[0_30px_90px_rgba(0,0,0,0.45)]"
          >
            <div className="mb-6">
              <span className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider text-[#6ee7ff] border border-[rgba(110,231,255,0.22)] bg-[rgba(110,231,255,0.08)] mb-3">
                <span className="w-2 h-2 rounded-full bg-[#6ee7ff] shadow-[0_0_12px_#6ee7ff]" />
                Create account
              </span>
              <h2 className="text-2xl sm:text-3xl font-bold text-[#f5f7ff] mb-1">Sign up for AIESEC Matcher</h2>
              <p className="text-sm text-[#aeb8d8]">Create your account, then log in to continue.</p>
            </div>

            {error && (
              <div className="mb-5 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-semibold text-[#d8deff] mb-2">Role</label>
                <select
                  value={role}
                  onChange={e => setRole(e.target.value as "member" | "admin")}
                  className="w-full px-4 py-3 rounded-2xl bg-[rgba(7,12,30,0.82)] border border-[rgba(145,160,255,0.18)] text-white focus:outline-none focus:border-[#6ee7ff] text-sm"
                >
                  <option value="member">Member</option>
                  <option value="admin">Admin</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-semibold text-[#d8deff] mb-2">Full name</label>
                <input
                  type="text"
                  required
                  value={name}
                  onChange={e => setName(e.target.value)}
                  placeholder="Your full name"
                  className="w-full px-4 py-3 rounded-2xl bg-[rgba(7,12,30,0.82)] border border-[rgba(145,160,255,0.18)] text-white placeholder-[rgba(174,184,216,0.6)] focus:outline-none focus:border-[#6ee7ff] text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-semibold text-[#d8deff] mb-2">Email</label>
                <input
                  type="email"
                  required
                  autoComplete="username"
                  value={email}
                  onChange={e => setEmail(e.target.value)}
                  placeholder="you@example.com"
                  className="w-full px-4 py-3 rounded-2xl bg-[rgba(7,12,30,0.82)] border border-[rgba(145,160,255,0.18)] text-white placeholder-[rgba(174,184,216,0.6)] focus:outline-none focus:border-[#6ee7ff] text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-semibold text-[#d8deff] mb-2">Password</label>
                <input
                  type="password"
                  required
                  minLength={8}
                  autoComplete="new-password"
                  value={password}
                  onChange={e => setPassword(e.target.value)}
                  placeholder="At least 8 characters"
                  className="w-full px-4 py-3 rounded-2xl bg-[rgba(7,12,30,0.82)] border border-[rgba(145,160,255,0.18)] text-white placeholder-[rgba(174,184,216,0.6)] focus:outline-none focus:border-[#6ee7ff] text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-semibold text-[#d8deff] mb-2">Confirm password</label>
                <input
                  type="password"
                  required
                  minLength={8}
                  autoComplete="new-password"
                  value={passwordConfirmation}
                  onChange={e => setPasswordConfirmation(e.target.value)}
                  placeholder="Repeat your password"
                  className="w-full px-4 py-3 rounded-2xl bg-[rgba(7,12,30,0.82)] border border-[rgba(145,160,255,0.18)] text-white placeholder-[rgba(174,184,216,0.6)] focus:outline-none focus:border-[#6ee7ff] text-sm"
                />
              </div>

              {role === "admin" && (
                <div>
                  <label className="block text-sm font-semibold text-[#6ee7ff] mb-2">Admin Code</label>
                  <input
                    type="password"
                    required
                    value={adminCode}
                    onChange={e => setAdminCode(e.target.value)}
                    placeholder="Enter admin code"
                    className="w-full px-4 py-3 rounded-2xl bg-[rgba(7,12,30,0.82)] border border-[rgba(110,231,255,0.4)] text-white placeholder-[rgba(174,184,216,0.6)] focus:outline-none focus:border-[#6ee7ff] text-sm"
                  />
                </div>
              )}

              <button
                type="submit"
                disabled={loading}
                className="w-full py-3.5 mt-2 rounded-2xl font-bold text-white bg-[#050816] border border-[rgba(145,160,255,0.3)] hover:border-[#6ee7ff] hover:bg-[#0a1024] hover:shadow-[0_0_20px_rgba(110,231,255,0.2)] transition-all disabled:opacity-50 text-base"
              >
                {loading ? "Creating account..." : "Create account"}
              </button>
            </form>

            <p className="text-center text-sm text-[rgba(255,255,255,0.6)] mt-6">
              Already have an account?{" "}
              <Link href="/login" className="font-semibold text-[#6ee7ff] hover:underline">
                Log in
              </Link>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
