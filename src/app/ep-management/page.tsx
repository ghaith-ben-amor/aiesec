"use client";

import { useEffect, useState } from "react";
import {
  Layers, Plus, Search, Filter, CheckCircle2, FileText, UserCheck, Clock, ShieldAlert, X, ChevronRight, Upload, Globe, GraduationCap, Building2, Phone, Mail
} from "lucide-react";

const STAGES = [
  "Applied",
  "Accepted",
  "Payment",
  "Confirmed",
  "Preparation Survey",
  "Midway Survey",
  "Experience Survey",
  "Completed",
];

interface EPDocument {
  id: number;
  documentType: string;
  originalName: string;
  createdAt: string;
}

interface EPStatusHistory {
  id: number;
  status: string;
  changedByLabel: string;
  createdAt: string;
}

interface EPItem {
  id: number;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  nationality: string;
  university: string;
  fieldOfStudy: string;
  opportunityTitle: string;
  country: string;
  organization: string;
  status: string;
  stageIndex: number;
  createdAt: string;
  documents: EPDocument[];
  statusHistory: EPStatusHistory[];
}

export default function EpManagementPage() {
  const [eps, setEps] = useState<EPItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [selectedStageFilter, setSelectedStageFilter] = useState("All");

  // Modal States
  const [showAddModal, setShowAddModal] = useState(false);
  const [selectedEp, setSelectedEp] = useState<EPItem | null>(null);

  // New EP Form State
  const [newEp, setNewEp] = useState({
    firstName: "",
    lastName: "",
    email: "",
    phone: "",
    nationality: "Tunisia",
    university: "",
    fieldOfStudy: "",
    opportunityTitle: "",
    country: "Germany",
    organization: "",
  });

  const fetchEps = async () => {
    setLoading(true);
    try {
      const res = await fetch(`/api/ep?search=${encodeURIComponent(search)}`);
      const data = await res.json();
      setEps(data.eps || []);
    } catch (err) {
      console.error("Failed to fetch EPs", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchEps();
  }, [search]);

  const handleCreateEp = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch("/api/ep", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(newEp),
      });
      if (res.ok) {
        setShowAddModal(false);
        setNewEp({
          firstName: "",
          lastName: "",
          email: "",
          phone: "",
          nationality: "Tunisia",
          university: "",
          fieldOfStudy: "",
          opportunityTitle: "",
          country: "Germany",
          organization: "",
        });
        fetchEps();
      }
    } catch (err) {
      console.error("Error adding EP", err);
    }
  };

  const handleUpdateStage = async (epId: number, nextStage: string, nextIndex: number) => {
    try {
      const res = await fetch(`/api/ep/${epId}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status: nextStage, stageIndex: nextIndex }),
      });
      if (res.ok) {
        fetchEps();
        if (selectedEp && selectedEp.id === epId) {
          setSelectedEp(prev => (prev ? { ...prev, status: nextStage, stageIndex: nextIndex } : null));
        }
      }
    } catch (err) {
      console.error("Failed to update EP stage", err);
    }
  };

  const filteredEps = eps.filter(ep => {
    if (selectedStageFilter !== "All" && ep.status !== selectedStageFilter) return false;
    return true;
  });

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 className="text-3xl font-extrabold text-white flex items-center gap-3">
            EP Pipeline Management
            <span className="text-xs bg-[#00d4aa]/15 text-[#00d4aa] border border-[#00d4aa]/30 px-3 py-1 rounded-full font-semibold">
              Live Tracker
            </span>
          </h1>
          <p className="text-sm text-[#8b95a6] mt-1">
            Track Exchange Participants through preparation, experience, and completion milestones.
          </p>
        </div>

        <button
          onClick={() => setShowAddModal(true)}
          className="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-bold bg-[#00d4aa] text-black hover:bg-[#00c099] transition-all shadow-lg shadow-[#00d4aa]/20"
        >
          <Plus className="w-5 h-5" />
          Register New EP
        </button>
      </div>

      {/* Stats Header */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <div className="bg-[#161a24] border border-[#242a3a] p-4 rounded-2xl">
          <p className="text-xs text-[#8b95a6] font-semibold uppercase">Total Registered</p>
          <p className="text-2xl font-extrabold text-white mt-1">{eps.length}</p>
        </div>
        <div className="bg-[#161a24] border border-[#242a3a] p-4 rounded-2xl">
          <p className="text-xs text-[#8b95a6] font-semibold uppercase">Active Pipeline</p>
          <p className="text-2xl font-extrabold text-[#037ef3] mt-1">
            {eps.filter(e => e.status !== "Completed").length}
          </p>
        </div>
        <div className="bg-[#161a24] border border-[#242a3a] p-4 rounded-2xl">
          <p className="text-xs text-[#8b95a6] font-semibold uppercase">Confirmed & Ready</p>
          <p className="text-2xl font-extrabold text-[#00d4aa] mt-1">
            {eps.filter(e => ["Confirmed", "Preparation Survey", "Midway Survey"].includes(e.status)).length}
          </p>
        </div>
        <div className="bg-[#161a24] border border-[#242a3a] p-4 rounded-2xl">
          <p className="text-xs text-[#8b95a6] font-semibold uppercase">Completed Exchanges</p>
          <p className="text-2xl font-extrabold text-purple-400 mt-1">
            {eps.filter(e => e.status === "Completed").length}
          </p>
        </div>
      </div>

      {/* Search & Stage Filters */}
      <div className="bg-[#161a24] border border-[#242a3a] p-4 rounded-2xl mb-8 space-y-4">
        <div className="relative">
          <Search className="w-5 h-5 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            value={search}
            onChange={e => setSearch(e.target.value)}
            placeholder="Search EPs by name, university, email, or country..."
            className="w-full pl-11 pr-4 py-2.5 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-sm focus:outline-none focus:border-[#00d4aa]"
          />
        </div>

        <div className="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
          <button
            onClick={() => setSelectedStageFilter("All")}
            className={`px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors ${
              selectedStageFilter === "All"
                ? "bg-[#00d4aa] text-black"
                : "bg-[#0b0d12] text-gray-400 hover:text-white border border-[#242a3a]"
            }`}
          >
            All Stages ({eps.length})
          </button>
          {STAGES.map((stg, i) => {
            const count = eps.filter(e => e.status === stg).length;
            return (
              <button
                key={stg}
                onClick={() => setSelectedStageFilter(stg)}
                className={`px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors ${
                  selectedStageFilter === stg
                    ? "bg-[#00d4aa] text-black"
                    : "bg-[#0b0d12] text-gray-400 hover:text-white border border-[#242a3a]"
                }`}
              >
                {stg} ({count})
              </button>
            );
          })}
        </div>
      </div>

      {/* EP List Grid */}
      {loading ? (
        <div className="text-center py-20 text-gray-500">Loading EP pipeline data...</div>
      ) : filteredEps.length === 0 ? (
        <div className="text-center py-16 bg-[#161a24] border border-[#242a3a] rounded-2xl">
          <Layers className="w-10 h-10 text-gray-500 mx-auto mb-3" />
          <p className="text-white font-semibold text-lg">No EP records found.</p>
          <p className="text-sm text-[#8b95a6] mt-1">Register a new EP to start tracking their exchange journey.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {filteredEps.map(ep => {
            const currentIdx = ep.stageIndex || 0;
            return (
              <div
                key={ep.id}
                className="bg-[#161a24] border border-[#242a3a] p-6 rounded-2xl flex flex-col justify-between hover:border-[#00d4aa]/40 transition-all shadow-md"
              >
                <div>
                  <div className="flex items-start justify-between gap-4 mb-3">
                    <div>
                      <h3 className="text-lg font-bold text-white">
                        {ep.firstName} {ep.lastName}
                      </h3>
                      <p className="text-xs text-[#8b95a6] flex items-center gap-1.5 mt-0.5">
                        <GraduationCap className="w-3.5 h-3.5 text-[#00d4aa]" />
                        {ep.university} • {ep.fieldOfStudy}
                      </p>
                    </div>
                    <span className="text-xs font-extrabold bg-[#037ef3]/15 text-[#037ef3] border border-[#037ef3]/30 px-3 py-1 rounded-full shrink-0">
                      {ep.status}
                    </span>
                  </div>

                  <div className="space-y-1.5 text-xs text-gray-300 mb-6 bg-[#0b0d12]/50 p-3 rounded-xl border border-[#242a3a]">
                    <div className="flex items-center gap-2">
                      <Building2 className="w-3.5 h-3.5 text-[#00d4aa]" />
                      <span>{ep.opportunityTitle} ({ep.organization})</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <Globe className="w-3.5 h-3.5 text-[#037ef3]" />
                      <span>Host Destination: {ep.country}</span>
                    </div>
                  </div>

                  {/* Stage Stepper Progress Bar */}
                  <div className="mb-6">
                    <div className="flex items-center justify-between text-[11px] font-semibold text-[#8b95a6] mb-2">
                      <span>Stage {currentIdx + 1} of {STAGES.length}</span>
                      <span>{Math.round(((currentIdx + 1) / STAGES.length) * 100)}% Complete</span>
                    </div>
                    <div className="w-full bg-[#0b0d12] rounded-full h-2 overflow-hidden flex">
                      {STAGES.map((_, i) => (
                        <div
                          key={i}
                          className={`h-full flex-1 border-r border-[#161a24] transition-all ${
                            i <= currentIdx ? "bg-[#00d4aa]" : "bg-[#242a3a]"
                          }`}
                        />
                      ))}
                    </div>
                  </div>
                </div>

                <div className="flex items-center justify-between pt-4 border-t border-[#242a3a]">
                  <button
                    onClick={() => setSelectedEp(ep)}
                    className="text-xs font-semibold text-[#00d4aa] hover:underline flex items-center gap-1"
                  >
                    View Details & Documents
                    <ChevronRight className="w-4 h-4" />
                  </button>

                  {currentIdx < STAGES.length - 1 && (
                    <button
                      onClick={() => handleUpdateStage(ep.id, STAGES[currentIdx + 1], currentIdx + 1)}
                      className="px-3 py-1.5 rounded-lg text-xs font-bold bg-[#00d4aa]/15 text-[#00d4aa] border border-[#00d4aa]/30 hover:bg-[#00d4aa] hover:text-black transition-colors"
                    >
                      Advance to {STAGES[currentIdx + 1]}
                    </button>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Register New EP Modal */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-[#161a24] border border-[#242a3a] rounded-2xl w-full max-w-lg p-6 relative max-h-[90vh] overflow-y-auto">
            <button
              onClick={() => setShowAddModal(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-white"
            >
              <X className="w-5 h-5" />
            </button>
            <h2 className="text-xl font-bold text-white mb-4">Register New EP</h2>

            <form onSubmit={handleCreateEp} className="space-y-4">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-[#8b95a6] mb-1">First Name</label>
                  <input
                    type="text"
                    required
                    value={newEp.firstName}
                    onChange={e => setNewEp({ ...newEp, firstName: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-sm focus:outline-none focus:border-[#00d4aa]"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-[#8b95a6] mb-1">Last Name</label>
                  <input
                    type="text"
                    required
                    value={newEp.lastName}
                    onChange={e => setNewEp({ ...newEp, lastName: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-sm focus:outline-none focus:border-[#00d4aa]"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#8b95a6] mb-1">Email</label>
                <input
                  type="email"
                  required
                  value={newEp.email}
                  onChange={e => setNewEp({ ...newEp, email: e.target.value })}
                  className="w-full px-3 py-2 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-sm focus:outline-none focus:border-[#00d4aa]"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-[#8b95a6] mb-1">Phone</label>
                  <input
                    type="text"
                    value={newEp.phone}
                    onChange={e => setNewEp({ ...newEp, phone: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-sm focus:outline-none focus:border-[#00d4aa]"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-[#8b95a6] mb-1">University</label>
                  <input
                    type="text"
                    value={newEp.university}
                    onChange={e => setNewEp({ ...newEp, university: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-sm focus:outline-none focus:border-[#00d4aa]"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-[#8b95a6] mb-1">Opportunity Title</label>
                  <input
                    type="text"
                    value={newEp.opportunityTitle}
                    onChange={e => setNewEp({ ...newEp, opportunityTitle: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-sm focus:outline-none focus:border-[#00d4aa]"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-[#8b95a6] mb-1">Host Country</label>
                  <input
                    type="text"
                    value={newEp.country}
                    onChange={e => setNewEp({ ...newEp, country: e.target.value })}
                    className="w-full px-3 py-2 rounded-xl bg-[#0b0d12] border border-[#242a3a] text-white text-sm focus:outline-none focus:border-[#00d4aa]"
                  />
                </div>
              </div>

              <button
                type="submit"
                className="w-full py-3 rounded-xl font-bold bg-[#00d4aa] text-black hover:bg-[#00c099] transition-all mt-4"
              >
                Save EP Record
              </button>
            </form>
          </div>
        </div>
      )}

      {/* EP Detail Modal */}
      {selectedEp && (
        <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-[#161a24] border border-[#242a3a] rounded-2xl w-full max-w-2xl p-6 relative max-h-[90vh] overflow-y-auto">
            <button
              onClick={() => setSelectedEp(null)}
              className="absolute top-4 right-4 text-gray-400 hover:text-white"
            >
              <X className="w-5 h-5" />
            </button>

            <h2 className="text-xl font-bold text-white mb-1">
              {selectedEp.firstName} {selectedEp.lastName}
            </h2>
            <p className="text-xs text-[#8b95a6] mb-6">{selectedEp.email} • {selectedEp.phone}</p>

            <div className="space-y-6">
              {/* Stage Stepper Dropdown */}
              <div>
                <label className="block text-xs font-semibold text-[#8b95a6] uppercase tracking-wider mb-2">
                  Update Stage Status
                </label>
                <div className="flex gap-2 flex-wrap">
                  {STAGES.map((s, idx) => (
                    <button
                      key={s}
                      onClick={() => handleUpdateStage(selectedEp.id, s, idx)}
                      className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                        selectedEp.status === s
                          ? "bg-[#00d4aa] text-black shadow-md shadow-[#00d4aa]/20"
                          : "bg-[#0b0d12] text-gray-400 hover:text-white border border-[#242a3a]"
                      }`}
                    >
                      {s}
                    </button>
                  ))}
                </div>
              </div>

              {/* Status Audit Trail */}
              <div>
                <h4 className="text-sm font-semibold text-white mb-3">Status Audit History</h4>
                <div className="bg-[#0b0d12] p-4 rounded-xl border border-[#242a3a] space-y-2 max-h-40 overflow-y-auto">
                  {selectedEp.statusHistory?.map((h, i) => (
                    <div key={i} className="flex items-center justify-between text-xs text-gray-400">
                      <span className="text-white font-medium">{h.status}</span>
                      <span>{new Date(h.createdAt).toLocaleDateString()}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
