import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import PromoCodeInput from "./PromoCodeInput";
import {
  Shield,
  Heart,
  Car,
  CheckCircle2,
  ChevronRight,
  Loader2,
  Sparkles,
  X,
  AlertCircle,
  BadgeCheck,
} from "lucide-react";

const PACKAGE_STYLES = {
  jiwa: {
    icon: Heart,
    gradient: "from-rose-500 to-pink-600",
    bgLight: "bg-rose-50",
    borderActive: "border-rose-400",
    textColor: "text-rose-600",
  },
  kesehatan: {
    icon: Shield,
    gradient: "from-blue-500 to-indigo-600",
    bgLight: "bg-blue-50",
    borderActive: "border-blue-400",
    textColor: "text-blue-600",
  },
  kendaraan: {
    icon: Car,
    gradient: "from-emerald-500 to-teal-600",
    bgLight: "bg-emerald-50",
    borderActive: "border-emerald-400",
    textColor: "text-emerald-600",
  },
};

const PAYMENT_METHODS = [
  "Transfer Bank",
  "GoPay / OVO",
  "Dana / ShopeePay",
  "Alfamart / Indomaret",
];

// ── Step indicator ───────────────────────────────────────────────────────────────
function StepIndicator({ step }) {
  const steps = ["Pilih Polis", "Isi Data", "Konfirmasi", "Selesai"];
  return (
    <div className="flex items-center justify-center gap-2 mb-8">
      {steps.map((label, i) => {
        const idx = i + 1;
        const active = idx === step;
        const done = idx < step;
        return (
          <div key={label} className="flex items-center gap-2">
            <div className="flex flex-col items-center gap-1">
              <div
                className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 ${
                  done
                    ? "bg-green-500 text-white"
                    : active
                    ? "bg-gradient-to-br from-blue-500 to-purple-500 text-white shadow-lg scale-110"
                    : "bg-slate-200 text-slate-400"
                }`}
              >
                {done ? <CheckCircle2 className="w-4 h-4" /> : idx}
              </div>
              <span
                className={`text-[10px] font-semibold hidden sm:block ${
                  active ? "text-blue-600" : done ? "text-green-600" : "text-slate-400"
                }`}
              >
                {label}
              </span>
            </div>
            {i < steps.length - 1 && (
              <div
                className={`w-8 sm:w-16 h-0.5 mb-4 transition-all duration-300 ${
                  done ? "bg-green-400" : "bg-slate-200"
                }`}
              />
            )}
          </div>
        );
      })}
    </div>
  );
}

// ── Main Component ───────────────────────────────────────────────────────────
export default function HealthService({ user }) {
  const navigate = useNavigate();
  const [step, setStep] = useState(1);
  const [selectedPolis, setSelectedPolis] = useState(null);
  const [packages, setPackages] = useState([]);
  const [existingPolicies, setExistingPolicies] = useState([]);
  const [loadingPolicies, setLoadingPolicies] = useState(true);
  const [loadingPackages, setLoadingPackages] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [successData, setSuccessData] = useState(null);
  const [error, setError] = useState("");
  const [paymentMethod, setPaymentMethod] = useState("");
  const [paymentProof, setPaymentProof] = useState(null);
  const [paymentProofName, setPaymentProofName] = useState("");
  const [billingCycle, setBillingCycle] = useState("monthly");
  const [discountCode, setDiscountCode] = useState("");
  const [appliedPromo, setAppliedPromo] = useState(null);

  const [form, setForm] = useState({
    insured_name: user?.name || "",
    start_date: new Date().toISOString().split("T")[0],
    end_date: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
  });

  const token = localStorage.getItem("mefasafe_token");

  // Fetch paket polis dari database
  useEffect(() => {
    const fetchPackages = async () => {
      try {
        const res = await axios.get("/api/v1/insurance-packages", {
          headers: { Authorization: `Bearer ${token}` },
        });
        setPackages(res.data.data || []);
      } catch {
        setPackages([]);
      } finally {
        setLoadingPackages(false);
      }
    };
    fetchPackages();
  }, []);

  // Fetch polis yang sudah dimiliki user
  useEffect(() => {
    const fetchPolicies = async () => {
      try {
        const res = await axios.get("/api/v1/my-policies", {
          headers: { Authorization: `Bearer ${token}` },
          params: { user_id: user?.id },
        });
        setExistingPolicies(res.data.data || []);
      } catch {
        setExistingPolicies([]);
      } finally {
        setLoadingPolicies(false);
      }
    };
    fetchPolicies();
  }, []);

  const hasType = (type) => existingPolicies.some((p) => p.insurance_type === type && p.status === "active");

  const formatRupiah = (n) => "Rp " + Number(n).toLocaleString("id-ID");

  const generatePolicyNumber = (type) => {
    const prefix = { jiwa: "JW", kesehatan: "KS", kendaraan: "KD" }[type] || "PL";
    const ts = Date.now().toString().slice(-6);
    const rand = Math.floor(Math.random() * 1000).toString().padStart(3, "0");
    return `${prefix}-${ts}-${rand}`;
  };

  const getPackageStyle = (pkg) => PACKAGE_STYLES[pkg.type] || PACKAGE_STYLES.kesehatan;

  // ── Step 1: Pilih Polis ──────────────────────────────────────────────────
  const handleSelectPolis = (pkg) => {
    if (hasType(pkg.type)) return; // sudah punya, skip
    setSelectedPolis(pkg);
    setStep(2);
  };

  // ── Step 2: Isi Data ─────────────────────────────────────────────────────
  const handleFormChange = (e) => {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleFormNext = (e) => {
    e.preventDefault();
    if (!form.insured_name.trim()) {
      setError("Nama tertanggung wajib diisi.");
      return;
    }
    setError("");
    setStep(3);
  };

  const handlePaymentProofChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    setPaymentProof(file);
    setPaymentProofName(file ? file.name : "");
  };

  // ── Step 3: Konfirmasi & Submit ──────────────────────────────────────────
  const handleSubmit = async () => {
    setSubmitting(true);
    setError("");

    if (!paymentMethod) {
      setError("Harap pilih metode pembayaran.");
      setSubmitting(false);
      return;
    }
    if (!paymentProof) {
      setError("Harap unggah bukti pembayaran.");
      setSubmitting(false);
      return;
    }

    try {
      const formData = new FormData();
      formData.append("user_id", user?.id);
      formData.append("policy_number", generatePolicyNumber(selectedPolis.type));
      formData.append("insurance_type", selectedPolis.type);
      formData.append("insured_name", form.insured_name);
      formData.append("premium_amount", selectedPolis.premium_amount);
      formData.append("coverage_limit", selectedPolis.coverage_limit);
      formData.append("start_date", form.start_date);
      formData.append("end_date", form.end_date);
      formData.append("status", "inactive");
      formData.append("payment_method", paymentMethod);
      formData.append("payment_proof", paymentProof);
      formData.append("billing_cycle", billingCycle);
      if (appliedPromo?.code) {
        formData.append("promo_code", appliedPromo.code);
      } else if (discountCode.trim()) {
        formData.append("promo_code", discountCode.trim().toUpperCase());
      }

      const res = await axios.post("/api/v1/insurance-policies", formData, {
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "multipart/form-data",
        },
      });

      setSuccessData(res.data.data);
      setStep(4);
    } catch (err) {
      const msg =
        err?.response?.data?.message ||
        Object.values(err?.response?.data?.errors || {})[0]?.[0] ||
        "Terjadi kesalahan. Silakan coba lagi.";
      setError(msg);
    } finally {
      setSubmitting(false);
    }
  };

  // ── Render ───────────────────────────────────────────────────────────────
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/40 to-indigo-50/70 relative overflow-hidden animate-fadeIn">
      {/* Background blobs */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        <div className="absolute top-[-140px] right-[-80px] h-72 w-72 rounded-full bg-blue-200/40 blur-3xl" />
        <div className="absolute bottom-[-120px] left-[-60px] h-80 w-80 rounded-full bg-violet-200/40 blur-3xl" />
      </div>

      <div className="relative max-w-4xl mx-auto px-4 py-8 md:px-8">
        {/* Header */}
        <div className="flex items-center gap-4 mb-8">
          <div>
            <h1 className="text-2xl md:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-2">
              <Sparkles className="w-6 h-6 text-indigo-500" />
              Health Service
            </h1>
            <p className="text-sm text-slate-500">Beli polis asuransi sesuai kebutuhan Anda</p>
          </div>
        </div>

        {/* Step Indicator */}
        <StepIndicator step={step} />

        {/* ── STEP 1: Pilih Polis ── */}
        {step === 1 && (
          <div className="space-y-4">
            <h2 className="text-xl font-bold text-slate-800 mb-4">Pilih Jenis Polis</h2>

            {loadingPackages ? (
              <div className="flex justify-center py-12">
                <Loader2 className="w-8 h-8 animate-spin text-blue-500" />
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                {packages.map((pkg) => {
                  const style = getPackageStyle(pkg);
                  const Icon = style.icon;
                  const owned = hasType(pkg.type);
                  return (
                    <button
                      key={pkg.type}
                      onClick={() => handleSelectPolis(pkg)}
                      disabled={owned}
                      className={`relative rounded-[24px] border-2 p-6 text-left transition-all duration-300 shadow-md group ${
                        owned
                          ? "border-green-300 bg-green-50/80 cursor-not-allowed opacity-80"
                          : `border-slate-200 bg-white/90 hover:${style.borderActive} hover:shadow-xl hover:-translate-y-1 cursor-pointer`
                      }`}
                    >
                      {owned && (
                        <div className="absolute top-3 right-3 flex items-center gap-1 bg-green-500 text-white text-[10px] font-bold px-2 py-1 rounded-full">
                          <BadgeCheck className="w-3 h-3" />
                          Aktif
                        </div>
                      )}

                      <div className={`w-14 h-14 rounded-2xl bg-gradient-to-br ${style.gradient} flex items-center justify-center mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300`}>
                        <Icon className="w-7 h-7 text-white" />
                      </div>

                      <h3 className="text-lg font-black text-slate-900 mb-1">{pkg.label}</h3>
                      <p className="text-xs text-slate-500 mb-4 leading-relaxed">{pkg.description}</p>

                      <div className={`text-xs font-semibold ${style.textColor} mb-3`}>
                        Coverage: {formatRupiah(pkg.coverage_limit)}
                      </div>

                      <ul className="space-y-1.5 mb-5">
                        {pkg.benefits.map((b) => (
                          <li key={b} className="flex items-start gap-2 text-xs text-slate-600">
                            <CheckCircle2 className={`w-3.5 h-3.5 mt-0.5 shrink-0 ${style.textColor}`} />
                            {b}
                          </li>
                        ))}
                      </ul>

                      <div className="border-t border-slate-100 pt-4">
                        <p className="text-[11px] text-slate-400 uppercase tracking-wider mb-1">Premi / bulan</p>
                        <p className={`text-xl font-black ${style.textColor}`}>{formatRupiah(pkg.premium_amount)}</p>
                      </div>

                      {!owned && (
                        <div className={`mt-4 w-full py-2.5 rounded-xl bg-gradient-to-r ${style.gradient} text-white text-sm font-bold text-center flex items-center justify-center gap-2 group-hover:shadow-lg transition-all duration-300`}>
                          Pilih Polis
                          <ChevronRight className="w-4 h-4" />
                        </div>
                      )}
                    </button>
                  );
                })}
              </div>
            )}
          </div>
        )}

        {/* ── STEP 2: Isi Data ── */}
        {step === 2 && selectedPolis && (
          <div className="max-w-xl mx-auto">
            <div className={`rounded-2xl bg-gradient-to-r ${getPackageStyle(selectedPolis).gradient} p-4 text-white mb-6 flex items-center gap-4`}>
              <div className="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                {(() => {
                  const Icon = getPackageStyle(selectedPolis).icon;
                  return <Icon className="w-6 h-6 text-white" />;
                })()}
              </div>
              <div>
                <p className="text-xs font-semibold opacity-80 uppercase tracking-wider">Polis Dipilih</p>
                <p className="text-lg font-black">{selectedPolis.label}</p>
                <p className="text-sm opacity-90">Premi {formatRupiah(selectedPolis.premium_amount)}/bulan</p>
              </div>
            </div>

            <form onSubmit={handleFormNext} className="rounded-[24px] border border-white/70 bg-white/90 backdrop-blur-xl p-6 shadow-xl space-y-5">
              <h2 className="text-lg font-black text-slate-900">Data Tertanggung</h2>

              {error && (
                <div className="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">
                  <AlertCircle className="w-4 h-4 shrink-0" />
                  {error}
                </div>
              )}

              <div>
                <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                  Nama Tertanggung <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  name="insured_name"
                  value={form.insured_name}
                  onChange={handleFormChange}
                  placeholder="Masukkan nama lengkap"
                  className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-sm transition-all"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1.5">Tanggal Mulai</label>
                  <input
                    type="date"
                    name="start_date"
                    value={form.start_date}
                    onChange={handleFormChange}
                    className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-sm transition-all"
                  />
                </div>
                <div>
                  <label className="block text-sm font-semibold text-slate-700 mb-1.5">Tanggal Berakhir</label>
                  <input
                    type="date"
                    name="end_date"
                    value={form.end_date}
                    onChange={handleFormChange}
                    className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-sm transition-all"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-semibold text-slate-700 mb-1.5">Siklus Pembayaran Premi</label>
                <select
                  value={billingCycle}
                  onChange={(e) => setBillingCycle(e.target.value)}
                  className="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-sm transition-all"
                >
                  <option value="monthly">Bulanan</option>
                  <option value="yearly">Tahunan</option>
                </select>
              </div>

              <button
                type="submit"
                className={`w-full py-3.5 rounded-xl bg-gradient-to-r ${getPackageStyle(selectedPolis).gradient} text-white font-bold text-sm flex items-center justify-center gap-2 hover:shadow-lg hover:scale-[1.02] transition-all duration-300`}
              >
                Lanjut ke Konfirmasi
                <ChevronRight className="w-4 h-4" />
              </button>
            </form>
          </div>
        )}

        {/* ── STEP 3: Konfirmasi ── */}
        {step === 3 && selectedPolis && (
          <div className="max-w-xl mx-auto">
            <div className="rounded-[24px] border border-white/70 bg-white/90 backdrop-blur-xl p-6 shadow-xl space-y-5">
              <h2 className="text-lg font-black text-slate-900">Ringkasan Pembelian</h2>

              {error && (
                <div className="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">
                  <AlertCircle className="w-4 h-4 shrink-0" />
                  {error}
                </div>
              )}

              {/* Detail polis */}
              <div className="rounded-2xl bg-slate-50 border border-slate-100 divide-y divide-slate-100">
                {[
                  { label: "Jenis Polis", value: selectedPolis.label },
                  { label: "Nama Tertanggung", value: form.insured_name },
                  { label: "Berlaku Mulai", value: form.start_date },
                  { label: "Berlaku Hingga", value: form.end_date },
                  { label: "Siklus Premi", value: billingCycle === "yearly" ? "Tahunan" : "Bulanan" },
                  { label: "Coverage Limit", value: formatRupiah(selectedPolis.coverage_limit) },
                  { label: "Premi / Bulan", value: formatRupiah(selectedPolis.premium_amount) },
                  ...(appliedPromo ? [
                    { label: "Diskon Promo", value: `- ${formatRupiah(appliedPromo.discount_amount)} (${appliedPromo.discount_percent}%)` },
                    { label: "Total Bayar", value: formatRupiah(appliedPromo.final_amount), highlight: true },
                    { label: "Kode Promo", value: appliedPromo.code },
                  ] : [{ label: "Total Bayar", value: formatRupiah(selectedPolis.premium_amount), highlight: true }]),
                ].map(({ label, value, highlight }) => (
                  <div key={label} className="flex justify-between items-center px-4 py-3">
                    <span className="text-sm text-slate-500">{label}</span>
                    <span className={`text-sm font-bold ${highlight ? "text-blue-600 text-base" : "text-slate-900"}`}>
                      {value}
                    </span>
                  </div>
                ))}
              </div>

              {/* Metode pembayaran (UI only) */}
              <div>
                <p className="text-sm font-semibold text-slate-700 mb-3">Metode Pembayaran</p>
                <div className="grid grid-cols-2 gap-2">
                  {PAYMENT_METHODS.map((m) => (
                    <button
                      key={m}
                      type="button"
                      onClick={() => setPaymentMethod(m)}
                      className={`w-full text-left px-3 py-2.5 rounded-xl border transition-all text-xs font-medium ${
                        paymentMethod === m
                          ? 'border-blue-500 bg-blue-50 text-blue-700'
                          : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300 hover:bg-slate-50'
                      }`}
                    >
                      {m}
                    </button>
                  ))}
                </div>
              </div>

              <PromoCodeInput
                userId={user?.id}
                feature="insurance"
                amount={Number(selectedPolis?.premium_amount || 0)}
                value={discountCode}
                onChange={setDiscountCode}
                onApplied={setAppliedPromo}
                label="Kode Promo (opsional)"
              />

              <div>
                <label className="block text-sm font-semibold text-slate-700 mb-1.5">Bukti Pembayaran</label>
                <input
                  type="file"
                  accept=".jpg,.jpeg,.png,.pdf"
                  onChange={handlePaymentProofChange}
                  className="w-full text-sm text-slate-600 file:border-0 file:bg-blue-500 file:text-white file:px-4 file:py-2 file:rounded-xl file:cursor-pointer"
                />
                {paymentProofName && (
                  <p className="mt-2 text-xs text-slate-500">File terpilih: {paymentProofName}</p>
                )}
              </div>

              <div className="rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-700">
                Unggah bukti pembayaran untuk verifikasi admin. Setelah bukti diverifikasi, polis akan diaktifkan dan saldo premi akan tercatat.
              </div>

              <button
                onClick={handleSubmit}
                disabled={submitting}
                className="w-full py-3.5 rounded-xl bg-gradient-to-r from-blue-500 to-purple-500 text-white font-bold text-sm flex items-center justify-center gap-2 hover:shadow-lg hover:scale-[1.02] disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100 transition-all duration-300"
              >
                {submitting ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    Memproses...
                  </>
                ) : (
                  <>
                    <CheckCircle2 className="w-4 h-4" />
                    Kirim Bukti & Selesaikan
                  </>
                )}
              </button>
            </div>
          </div>
        )}

        {/* ── STEP 4: Sukses ── */}
        {step === 4 && successData && (
          <div className="max-w-md mx-auto text-center">
            <div className="rounded-[28px] border border-white/70 bg-white/90 backdrop-blur-xl p-8 shadow-xl">
              {/* Animasi centang */}
              <div className="w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center mx-auto mb-6 shadow-lg">
                <CheckCircle2 className="w-10 h-10 text-white" />
              </div>

              <h2 className="text-2xl font-black text-slate-900 mb-2">Pengajuan Diterima</h2>
              <p className="text-slate-500 text-sm mb-6">
                Bukti pembayaran Anda telah terkirim. Tunggu verifikasi admin sebelum polis diaktifkan.
              </p>

              {/* Nomor polis */}
              <div className="rounded-2xl bg-gradient-to-br from-blue-500 to-purple-500 p-5 text-white mb-6">
                <p className="text-xs font-semibold opacity-80 uppercase tracking-wider mb-1">Nomor Polis</p>
                <p className="text-2xl font-black tracking-widest">{successData.policy_number}</p>
                <p className="text-sm opacity-90 mt-2">{selectedPolis?.label}</p>
              </div>

              <div className="rounded-2xl bg-slate-50 border border-slate-100 divide-y divide-slate-100 text-left mb-6">
                {[
                  { label: "Nama Tertanggung", value: successData.insured_name },
                  { label: "Coverage", value: formatRupiah(successData.coverage_limit) },
                  { label: "Berlaku Hingga", value: successData.end_date },
                  {
                    label: "Status",
                    value:
                      successData.payment_status === 'pending'
                        ? 'Menunggu Verifikasi Admin'
                        : successData.status === 'active'
                        ? 'Aktif ✓'
                        : 'Tidak Aktif',
                  },
                ].map(({ label, value }) => (
                  <div key={label} className="flex justify-between px-4 py-3">
                    <span className="text-xs text-slate-500">{label}</span>
                    <span className="text-xs font-bold text-slate-900">{value}</span>
                  </div>
                ))}
              </div>

              <div className="flex gap-3">
                <button
                  onClick={() => navigate("/home")}
                  className="flex-1 py-3 rounded-xl border border-slate-200 bg-white text-slate-700 font-semibold text-sm hover:bg-slate-50 transition-all"
                >
                  Ke Beranda
                </button>
                <button
                  onClick={() => { setStep(1); setSelectedPolis(null); setSuccessData(null); }}
                  className="flex-1 py-3 rounded-xl bg-gradient-to-r from-blue-500 to-purple-500 text-white font-semibold text-sm hover:shadow-lg transition-all"
                >
                  Beli Polis Lain
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
