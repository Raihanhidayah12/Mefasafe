import { useEffect, useState } from "react";
import axios from "axios";
import { Search, Loader2, CheckCircle2, XCircle, Clock, Shield, Eye } from "lucide-react";

const API = "/api/v1/admin";
const token = () => localStorage.getItem("admin_token");
const fmt = (n) => "Rp " + Number(n || 0).toLocaleString("id-ID");

const PAY_CFG = {
    pending:  { label: "Pending",      color: "bg-amber-100 text-amber-700",   icon: Clock },
    verified: { label: "Terverifikasi",color: "bg-green-100 text-green-700",   icon: CheckCircle2 },
    rejected: { label: "Ditolak",      color: "bg-red-100 text-red-700",       icon: XCircle },
};

const TYPE_LABEL = { jiwa: "Jiwa", kesehatan: "Kesehatan", kendaraan: "Kendaraan" };
const CYCLE_LABEL = { monthly: "Bulanan", yearly: "Tahunan" };

export default function AdminPolicies() {
    const [policies, setPolicies] = useState([]);
    const [meta, setMeta]         = useState({});
    const [loading, setLoading]   = useState(true);
    const [search, setSearch]     = useState("");
    const [payStatus, setPayStatus] = useState("");
    const [page, setPage]         = useState(1);
    const [updating, setUpdating] = useState(null);
    const [toast, setToast]       = useState("");
    const [detail, setDetail]     = useState(null);

    const fetchPolicies = async () => {
        setLoading(true);
        try {
            const res = await axios.get(`${API}/policies`, {
                headers: { Authorization: `Bearer ${token()}` },
                params: { search, payment_status: payStatus, page, per_page: 12 },
            });
            setPolicies(res.data.data.data || []);
            setMeta(res.data.data);
        } catch (e) { console.error(e); }
        finally { setLoading(false); }
    };

    useEffect(() => { fetchPolicies(); }, [search, payStatus, page]);

    const showToast = (msg) => { setToast(msg); setTimeout(() => setToast(""), 3000); };

    const handleUpdate = async (id, payment_status) => {
        setUpdating(id + payment_status);
        try {
            await axios.put(`${API}/policies/${id}`, { payment_status }, { headers: { Authorization: `Bearer ${token()}` } });
            showToast(`Pembayaran ${payment_status === "verified" ? "diverifikasi" : "ditolak"}.`);
            fetchPolicies();
        } catch (e) { showToast("Gagal memperbarui."); }
        finally { setUpdating(null); }
    };

    return (
        <div className="space-y-4">
            {toast && (
                <div className="fixed top-4 right-4 z-50 bg-slate-900 text-white px-4 py-2 rounded-xl shadow-xl text-sm">{toast}</div>
            )}

            {/* Detail Modal */}
            {detail && (
                <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" onClick={() => setDetail(null)}>
                    <div className="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl" onClick={(e) => e.stopPropagation()}>
                        <h3 className="font-bold text-lg mb-4 flex items-center gap-2">
                            <Shield className="w-5 h-5 text-blue-500" /> Detail Polis #{detail.id}
                        </h3>
                        <div className="space-y-2 text-sm">
                            {[
                                ["Pengguna",        detail.user?.name],
                                ["No. Polis",       detail.policy_number],
                                ["Tipe",            TYPE_LABEL[detail.insurance_type] || detail.insurance_type],
                                ["Tertanggung",     detail.insured_name],
                                ["Premi",           fmt(detail.premium_amount)],
                                ["Limit Klaim",     fmt(detail.coverage_limit)],
                                ["Mulai",           detail.start_date],
                                ["Berakhir",        detail.end_date],
                                ["Siklus Premi",    CYCLE_LABEL[detail.billing_cycle] || "-"],
                                ["Jatuh Tempo",     detail.next_payment_due_date],
                                ["Masa Tenggang",   detail.grace_period_days ? `${detail.grace_period_days} hari` : "-"],
                                ["Status Polis",    detail.status],
                                ["Metode Bayar",    detail.payment_method],
                                ["Status Bayar",    detail.payment_status],
                            ].map(([l, v]) => (
                                <div key={l} className="flex gap-2">
                                    <span className="text-slate-500 w-28 flex-shrink-0">{l}:</span>
                                    <span className="text-slate-800 font-medium">{v || "-"}</span>
                                </div>
                            ))}
                            {detail.payment_proof_path && (
                                <div>
                                    <span className="text-slate-500">Bukti Bayar: </span>
                                    <a href={`/${detail.payment_proof_path}`} target="_blank" rel="noreferrer"
                                        className="text-blue-600 underline">Lihat Bukti</a>
                                </div>
                            )}
                        </div>
                        <button onClick={() => setDetail(null)} className="mt-5 w-full py-2 rounded-xl bg-slate-100 text-slate-700 font-medium hover:bg-slate-200">
                            Tutup
                        </button>
                    </div>
                </div>
            )}

            {/* Filters */}
            <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-wrap gap-3 items-center">
                <div className="relative flex-1 min-w-48">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                        placeholder="Cari nama atau no. polis..."
                        className="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
                </div>
                <select value={payStatus} onChange={(e) => { setPayStatus(e.target.value); setPage(1); }}
                    className="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Semua Status Bayar</option>
                    <option value="pending">Pending</option>
                    <option value="verified">Terverifikasi</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>

            {/* Table */}
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th className="text-left px-4 py-3 font-semibold text-slate-600">Pengguna</th>
                                <th className="text-left px-4 py-3 font-semibold text-slate-600">No. Polis</th>
                                <th className="text-left px-4 py-3 font-semibold text-slate-600">Tipe</th>
                                <th className="text-left px-4 py-3 font-semibold text-slate-600">Premi</th>
                                <th className="text-left px-4 py-3 font-semibold text-slate-600">Jatuh Tempo</th>
                                <th className="text-left px-4 py-3 font-semibold text-slate-600">Status Bayar</th>
                                <th className="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {loading ? (
                                <tr><td colSpan={7} className="text-center py-12">
                                    <Loader2 className="w-6 h-6 animate-spin text-blue-500 mx-auto" />
                                </td></tr>
                            ) : policies.length === 0 ? (
                                <tr><td colSpan={7} className="text-center py-12 text-slate-400">Tidak ada polis.</td></tr>
                            ) : policies.map((p) => {
                                const cfg = PAY_CFG[p.payment_status] || { label: "-", color: "bg-slate-100 text-slate-500", icon: Clock };
                                const Icon = cfg.icon;
                                return (
                                    <tr key={p.id} className="hover:bg-slate-50 transition-colors">
                                        <td className="px-4 py-3 font-medium text-slate-800">{p.user?.name || "-"}</td>
                                        <td className="px-4 py-3 text-slate-500 font-mono text-xs">{p.policy_number}</td>
                                        <td className="px-4 py-3">
                                            <span className="px-2 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold">
                                                {TYPE_LABEL[p.insurance_type] || p.insurance_type}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 font-bold text-slate-900">{fmt(p.premium_amount)}</td>
                                        <td className="px-4 py-3 text-slate-600">
                                            <div className="font-semibold">{p.next_payment_due_date || "-"}</div>
                                            <div className="text-[11px] text-slate-400">{CYCLE_LABEL[p.billing_cycle] || "-"}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold ${cfg.color}`}>
                                                <Icon className="w-3 h-3" />{cfg.label}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center justify-end gap-2">
                                                <button onClick={() => setDetail(p)}
                                                    className="p-1.5 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200">
                                                    <Eye className="w-4 h-4" />
                                                </button>
                                                {p.payment_status === "pending" && (
                                                    <>
                                                        <button onClick={() => handleUpdate(p.id, "verified")}
                                                            disabled={!!updating || !p.payment_proof_path}
                                                            title={!p.payment_proof_path ? "Belum ada bukti pembayaran" : "Verifikasi pembayaran"}
                                                            className="px-3 py-1.5 rounded-lg bg-green-100 text-green-700 text-xs font-semibold hover:bg-green-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                                            {updating === p.id + "verified" ? <Loader2 className="w-3 h-3 animate-spin" /> : "Verifikasi"}
                                                        </button>
                                                        <button onClick={() => handleUpdate(p.id, "rejected")}
                                                            disabled={!!updating}
                                                            className="px-3 py-1.5 rounded-lg bg-red-100 text-red-700 text-xs font-semibold hover:bg-red-200 disabled:opacity-50">
                                                            {updating === p.id + "rejected" ? <Loader2 className="w-3 h-3 animate-spin" /> : "Tolak"}
                                                        </button>
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
                {meta.last_page > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-slate-100 text-sm text-slate-500">
                        <span>Halaman {meta.current_page} dari {meta.last_page} ({meta.total} polis)</span>
                        <div className="flex gap-2">
                            <button disabled={page <= 1} onClick={() => setPage(p => p - 1)}
                                className="px-3 py-1 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50">← Prev</button>
                            <button disabled={page >= meta.last_page} onClick={() => setPage(p => p + 1)}
                                className="px-3 py-1 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50">Next →</button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
