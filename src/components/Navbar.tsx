"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { usePathname } from "next/navigation";
import { Briefcase, User, LogOut, Shield, FileText, Layers, LogIn } from "lucide-react";

interface UserSession {
  id: number;
  email: string;
  name: string;
  role: "member" | "admin";
}

export default function Navbar() {
  const pathname = usePathname();
  const [user, setUser] = useState<UserSession | null>(null);

  useEffect(() => {
    fetch("/api/auth/me")
      .then(res => res.json())
      .then(data => {
        if (data.user) setUser(data.user);
      })
      .catch(() => {});
  }, []);

  const handleLogout = async () => {
    await fetch("/api/auth/logout", { method: "POST" });
    setUser(null);
    window.location.href = "/";
  };

  // Hide global navbar on welcome landing page to match original standalone design
  if (pathname === "/") {
    return null;
  }

  return (
    <header className="border-b border-[#242a3a] bg-[#0b0d12]/90 backdrop-blur sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <Link href="/" className="flex items-center gap-3 group">
          <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#00d4aa] to-[#037ef3] flex items-center justify-center shadow-lg shadow-[#00d4aa]/20 group-hover:scale-105 transition-transform">
            <Briefcase className="w-5 h-5 text-white" />
          </div>
          <span className="font-extrabold text-xl tracking-tight text-white">
            AIESEC <span className="text-[#00d4aa]">Matcher</span>
          </span>
        </Link>

        <nav className="flex items-center gap-6">
          <Link href="/upload" className="flex items-center gap-2 text-sm text-[#8b95a6] hover:text-white transition-colors">
            <FileText className="w-4 h-4" />
            Upload CV
          </Link>
          
          <Link href="/ep-management" className="flex items-center gap-2 text-sm text-[#8b95a6] hover:text-white transition-colors">
            <Layers className="w-4 h-4" />
            EP Pipeline
          </Link>

          {user?.role === "admin" && (
            <Link href="/admin" className="flex items-center gap-2 text-sm text-[#00d4aa] font-medium hover:underline">
              <Shield className="w-4 h-4" />
              Admin Portal
            </Link>
          )}

          {user ? (
            <div className="flex items-center gap-4 border-l border-[#242a3a] pl-6">
              <div className="flex items-center gap-2 text-sm text-gray-300">
                <User className="w-4 h-4 text-[#00d4aa]" />
                <span className="font-medium">{user.name}</span>
                <span className="text-xs bg-[#242a3a] text-gray-400 px-2 py-0.5 rounded-full capitalize">{user.role}</span>
              </div>
              <button
                onClick={handleLogout}
                className="p-2 rounded-lg bg-[#161a24] text-gray-400 hover:text-red-400 hover:bg-[#242a3a] transition-colors"
                title="Log out"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          ) : (
            <div className="flex items-center gap-3 border-l border-[#242a3a] pl-6">
              <Link href="/login" className="flex items-center gap-1.5 text-sm font-medium text-gray-300 hover:text-white px-3 py-1.5 rounded-lg hover:bg-[#161a24] transition-colors">
                <LogIn className="w-4 h-4" />
                Sign In
              </Link>
              <Link
                href="/signup"
                className="text-sm font-semibold text-black bg-[#00d4aa] hover:bg-[#00c099] px-4 py-1.5 rounded-lg transition-all shadow-md shadow-[#00d4aa]/20"
              >
                Sign Up
              </Link>
            </div>
          )}
        </nav>
      </div>
    </header>
  );
}
