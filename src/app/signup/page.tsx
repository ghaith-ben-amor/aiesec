"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { UserPlus, Mail, Lock, User, Shield, AlertCircle, ArrowRight } from "lucide-react";

export default function SignupPage() {
  const router = useRouter();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [role, setRole] = useState<"member" | "admin">("member");
  const [adminCode, setAdminCode] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
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
    <div className="min-h-[85vh] flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-md bg-[#161a24] border border-[#242a3a] rounded-2xl p-8 shadow-xl">
        <div className="flex flex-col items-center text-center mb-8">
          <div className="w-12 h-12 rounded-xl bg-[#00d4aa]/15 flex items-center justify-center text-[#00d4aa] mb-3">
            <UserPlus className="w-6 h-6" />
          </div>
          <h2 className="text-2xl font-bold text-white">Create Your Account</h2>
          <p className="text-sm text-[#8b95a6] mt-1">Join AIESEC Opportunity Matcher</p>
        </div>

        {error && (
          <div className="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 flex items-center gap-3 text-red-400 text-sm">
            <AlertCircle className="w-5 h-5 shrink-0" />
            <span>{error}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-[#8b95a6] uppercase tracking-wider mb-2">
              Full Name
            </label>
            <div className="relative">
              <User className="w-5 h-5 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                required
                value={name}
                onChange={e => setName(e.target.value)}
                placeholder="Ghaith Ben Amor"
                className="w-full pl-11 pr-4 py-3 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white placeholder-gray-500 focus:outline-none focus:border-[#00d4aa] transition-colors text-sm"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#8b95a6] uppercase tracking-wider mb-2">
              Email Address
            </label>
            <div className="relative">
              <Mail className="w-5 h-5 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="email"
                required
                value={email}
                onChange={e => setEmail(e.target.value)}
                placeholder="name@aiesec.net"
                className="w-full pl-11 pr-4 py-3 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white placeholder-gray-500 focus:outline-none focus:border-[#00d4aa] transition-colors text-sm"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#8b95a6] uppercase tracking-wider mb-2">
              Password
            </label>
            <div className="relative">
              <Lock className="w-5 h-5 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="password"
                required
                value={password}
                onChange={e => setPassword(e.target.value)}
                placeholder="••••••••"
                className="w-full pl-11 pr-4 py-3 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white placeholder-gray-500 focus:outline-none focus:border-[#00d4aa] transition-colors text-sm"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[#8b95a6] uppercase tracking-wider mb-2">
              Account Role
            </label>
            <div className="grid grid-cols-2 gap-3">
              <button
                type="button"
                onClick={() => setRole("member")}
                className={`py-2.5 px-4 rounded-xl border text-sm font-medium transition-all flex items-center justify-center gap-2 ${
                  role === "member"
                    ? "bg-[#00d4aa]/15 border-[#00d4aa] text-[#00d4aa]"
                    : "bg-[#0b0d12] border-[#242a3a] text-gray-400 hover:text-white"
                }`}
              >
                <User className="w-4 h-4" />
                Member
              </button>
              <button
                type="button"
                onClick={() => setRole("admin")}
                className={`py-2.5 px-4 rounded-xl border text-sm font-medium transition-all flex items-center justify-center gap-2 ${
                  role === "admin"
                    ? "bg-[#037ef3]/15 border-[#037ef3] text-[#037ef3]"
                    : "bg-[#0b0d12] border-[#242a3a] text-gray-400 hover:text-white"
                }`}
              >
                <Shield className="w-4 h-4" />
                Admin
              </button>
            </div>
          </div>

          {role === "admin" && (
            <div>
              <label className="block text-xs font-semibold text-[#037ef3] uppercase tracking-wider mb-2">
                Admin Security Code
              </label>
              <div className="relative">
                <Shield className="w-5 h-5 text-[#037ef3] absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="password"
                  required
                  value={adminCode}
                  onChange={e => setAdminCode(e.target.value)}
                  placeholder="Enter admin key (e.g. AIESEC2026)"
                  className="w-full pl-11 pr-4 py-3 rounded-xl bg-[#0b0d12] border border-[#037ef3]/50 text-white placeholder-gray-500 focus:outline-none focus:border-[#037ef3] transition-colors text-sm"
                />
              </div>
            </div>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full py-3.5 rounded-xl font-bold bg-[#00d4aa] text-black hover:bg-[#00c099] transition-all flex items-center justify-center gap-2 shadow-lg shadow-[#00d4aa]/20 disabled:opacity-50 mt-6"
          >
            {loading ? "Creating account..." : "Sign Up"}
            <ArrowRight className="w-4 h-4" />
          </button>
        </form>

        <p className="mt-8 text-center text-sm text-[#8b95a6]">
          Already registered?{" "}
          <Link href="/login" className="text-[#00d4aa] font-medium hover:underline">
            Sign In
          </Link>
        </p>
      </div>
    </div>
  );
}
