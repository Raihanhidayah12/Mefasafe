<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Doctor;
use App\Models\DoctorConsultation;
use App\Models\Feedback;
use App\Models\Hospital;
use App\Models\HospitalRegistration;
use App\Models\InsurancePackage;
use App\Models\InsurancePolicy;
use App\Models\Announcement;
use App\Models\PromoCode;
use App\Models\Promotion;
use App\Models\ServiceRegistration;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InsuranceBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function __construct(private readonly InsuranceBillingService $billingService)
    {
    }

    // ─── OVERVIEW / STATS ────────────────────────────────────────────────────

    public function stats(): JsonResponse
    {
        $totalUsers        = User::where('role', 'pengguna')->count();
        $totalAdmins       = User::where('role', 'admin')->count();
        $totalPolicies     = InsurancePolicy::count();
        $activePolicies    = InsurancePolicy::where('status', 'active')->count();
        $pendingClaims     = Claim::where('status', 'pending')->count();
        $totalClaims       = Claim::count();
        $totalTransactions = Transaction::count();
        $automaticInsuranceClaimCount = Claim::where('status', 'approved')
            ->where('description', 'like', 'Klaim otomatis:%')
            ->count();
        $totalTransactions += $automaticInsuranceClaimCount;

        $totalRevenue = Transaction::where('transaction_type', 'premi_masuk')
                                ->where('status', 'success')
                                ->sum('amount');

        // Include premium amounts from verified policies that may not have a
        // corresponding `premi_masuk` Transaction record (e.g. verified via
        // admin panel or fixtures). This ensures dashboard shows expected
        // premi income even if the Transaction row wasn't created.
        $premiumFromVerifiedPolicies = InsurancePolicy::where('payment_status', 'verified')
            ->sum('premium_amount');
        $totalRevenue += $premiumFromVerifiedPolicies;

        $totalPayout = Transaction::where('transaction_type', 'klaim_keluar')
                                ->where('status', 'success')
                                ->sum('amount');
        $automaticInsurancePayout = Claim::where('status', 'approved')
            ->where('description', 'like', 'Klaim otomatis:%')
            ->sum('claim_amount');
        $totalPayout += $automaticInsurancePayout;
        $pendingPolicies = InsurancePolicy::where('payment_status', 'pending')->count();
        $pendingConsultations = DoctorConsultation::where('payment_status', 'pending')->count();
        $pendingTransactions = Transaction::where('status', 'pending')->count();

        // Aggregate all sources that represent payments waiting verification
        $pendingPayments   = $pendingPolicies + $pendingConsultations + $pendingTransactions;
        $totalHospitals    = Hospital::count();
        $inactiveHospitals = Hospital::where('is_active', false)->count();
        $totalDoctors      = Doctor::count();
        $inactiveDoctors   = Doctor::where('availability', 'unavailable')->count();
        $totalConsultations = DoctorConsultation::count();
        $waitingConsultations = DoctorConsultation::where('status', 'waiting_approval')->count();
        $totalFeedbacks     = Feedback::count();
        $totalPackages      = InsurancePackage::count();
        $totalPromotions    = Promotion::count();
        $activePromotions   = Promotion::where('is_active', true)->count();
        $totalAnnouncements = Announcement::count();
        $activeAnnouncements = Announcement::where('is_active', true)->count();
        $totalPromoCodes     = PromoCode::count();
        $activePromoCodes    = PromoCode::where('is_active', true)->count();
        $pendingRegistrations = HospitalRegistration::where('status', 'registered')->count()
            + ServiceRegistration::where('status', 'registered')->count();
        $avgRating         = Feedback::avg('rating') ?? 0;

        // Monthly revenue (last 6 months) — uses substr() which works on SQLite & MySQL
        $monthlyRevenue = Transaction::select(
                DB::raw("substr(transaction_date, 1, 7) as month"),
                DB::raw('SUM(CASE WHEN transaction_type = "premi_masuk" THEN amount ELSE 0 END) as revenue'),
                DB::raw('SUM(CASE WHEN transaction_type = "klaim_keluar" THEN amount ELSE 0 END) as payout')
            )
            ->where('status', 'success')
            ->where('transaction_date', '>=', now()->subMonths(6)->toDateTimeString())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Claims by status
        $claimsByStatus = Claim::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        // Policies by type
        $policiesByType = InsurancePolicy::select('insurance_type', DB::raw('count(*) as total'))
            ->groupBy('insurance_type')
            ->get()
            ->pluck('total', 'insurance_type');

        return response()->json([
            'success' => true,
            'data' => [
                'total_users'          => $totalUsers,
                'total_admins'         => $totalAdmins,
                'total_policies'       => $totalPolicies,
                'active_policies'      => $activePolicies,
                'pending_claims'       => $pendingClaims,
                'total_claims'         => $totalClaims,
                'total_transactions'   => $totalTransactions,
                'total_revenue'        => $totalRevenue,
                'total_payout'         => $totalPayout,
                'pending_payments'     => $pendingPayments,
                'pending_policies'     => $pendingPolicies,
                'pending_transactions' => $pendingTransactions,
                'total_hospitals'      => $totalHospitals,
                'inactive_hospitals'   => $inactiveHospitals,
                'total_doctors'        => $totalDoctors,
                'inactive_doctors'     => $inactiveDoctors,
                'total_consultations'  => $totalConsultations,
                'pending_consultations'=> $pendingConsultations + $waitingConsultations,
                'pending_registrations'=> $pendingRegistrations,
                'total_feedbacks'      => $totalFeedbacks,
                'total_packages'       => $totalPackages,
                'total_promotions'     => $totalPromotions,
                'active_promotions'    => $activePromotions,
                'total_announcements'  => $totalAnnouncements,
                'active_announcements' => $activeAnnouncements,
                'total_promo_codes'    => $totalPromoCodes,
                'active_promo_codes'   => $activePromoCodes,
                'avg_rating'           => round($avgRating, 1),
                'monthly_revenue'      => $monthlyRevenue,
                'claims_by_status'     => $claimsByStatus,
                'policies_by_type'     => $policiesByType,
            ],
        ]);
    }

    // ─── USERS ───────────────────────────────────────────────────────────────

    public function users(Request $request): JsonResponse
    {
        $query = User::with('profile')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->latest();

        $users = $query->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function updateUser(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', 'unique:users,email,' . $id],
            'role'     => ['sometimes', 'in:pengguna,admin'],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json(['success' => true, 'data' => $user->fresh('profile'), 'message' => 'User updated.']);
    }

    public function deleteUser(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Hapus akun admin melalui menu Kelola Admin.',
            ], 422);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User deleted.']);
    }

    // ─── ADMIN ACCOUNTS (role: admin) ────────────────────────────────────────

    public function admins(Request $request): JsonResponse
    {
        $query = User::where('role', 'admin')
            ->when($request->search, fn ($q) => $q->where(function ($inner) use ($request) {
                $inner->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->latest();

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->per_page ?? 12),
        ]);
    }

    public function storeAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'admin',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin berhasil ditambahkan.',
            'data' => $admin,
        ], 201);
    }

    public function updateAdmin(Request $request, string $id): JsonResponse
    {
        $admin = User::where('role', 'admin')->findOrFail($id);

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email:rfc,dns', 'max:255', 'unique:users,email,' . $admin->id],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $admin->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data admin diperbarui.',
            'data' => $admin->fresh(),
        ]);
    }

    public function deleteAdmin(Request $request, string $id): JsonResponse
    {
        $admin = User::where('role', 'admin')->findOrFail($id);

        if ($request->user() && (int) $request->user()->id === (int) $admin->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun admin yang sedang login.',
            ], 422);
        }

        if (User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus admin terakhir.',
            ], 422);
        }

        $admin->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin berhasil dihapus.',
        ]);
    }

    // ─── CLAIMS ──────────────────────────────────────────────────────────────

    public function claims(Request $request): JsonResponse
    {
        $query = Claim::with(['user', 'insurancePolicy'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%")))
            ->latest();

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    public function updateClaim(Request $request, string $id): JsonResponse
    {
        $claim = Claim::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected,partial'],
        ]);

        $previousStatus = $claim->status;
        $claim->update($validated);

        if (! in_array($previousStatus, ['approved', 'partial']) && in_array($claim->status, ['approved', 'partial'])) {
            Transaction::create([
                'user_id'              => $claim->user_id,
                'insurance_policy_id'  => $claim->insurance_policy_id,
                'transaction_type'     => 'klaim_keluar',
                'amount'               => $claim->claim_amount,
                'transaction_date'     => now(),
                'status'               => 'success',
            ]);
        }

        return response()->json(['success' => true, 'data' => $claim->fresh(['user', 'insurancePolicy']), 'message' => 'Claim updated.']);
    }

    // ─── INSURANCE POLICIES ──────────────────────────────────────────────────

    public function policies(Request $request): JsonResponse
    {
        $query = InsurancePolicy::with('user')
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%"))
                ->orWhere('policy_number', 'like', "%{$request->search}%"))
            ->latest();

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    public function updatePolicy(Request $request, string $id): JsonResponse
    {
        $policy = InsurancePolicy::findOrFail($id);

        $validated = $request->validate([
            'payment_status' => ['sometimes', 'in:pending,verified,rejected'],
            'status'         => ['sometimes', 'in:active,inactive'],
            'billing_cycle' => ['sometimes', 'in:monthly,yearly'],
            'grace_period_days' => ['sometimes', 'integer', 'min:1', 'max:90'],
            'next_payment_due_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $previousStatus = $policy->payment_status;
        $policy->update($validated);

        if ($policy->payment_status === 'verified' && $previousStatus !== 'verified') {
            $this->billingService->markPaymentVerified($policy);

            Transaction::create([
                'user_id'             => $policy->user_id,
                'insurance_policy_id' => $policy->id,
                'transaction_type'    => 'premi_masuk',
                'amount'              => $policy->premium_amount,
                'transaction_date'    => now(),
                'status'              => 'success',
            ]);
        }

        return response()->json(['success' => true, 'data' => $policy->fresh('user'), 'message' => 'Policy updated.']);
    }

    // ─── TRANSACTIONS ────────────────────────────────────────────────────────

    public function transactions(Request $request): JsonResponse
    {
        $query = Transaction::with(['user', 'insurancePolicy'])
            ->when($request->type, fn($q) => $q->where('transaction_type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%")))
            ->latest('transaction_date');

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    // ─── HOSPITALS ───────────────────────────────────────────────────────────

    public function hospitals(Request $request): JsonResponse
    {
        $query = Hospital::withCount('doctors')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('city', 'like', "%{$request->search}%"))
            ->latest();

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    public function storeHospital(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'address'    => ['required', 'string'],
            'city'       => ['required', 'string', 'max:100'],
            'province'   => ['nullable', 'string', 'max:100'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'email'      => ['nullable', 'email'],
            'latitude'   => ['required', 'numeric', 'between:-90,90'],
            'longitude'  => ['required', 'numeric', 'between:-180,180'],
            'type'       => ['required', 'in:umum,swasta,khusus,puskesmas'],
            'facilities' => ['nullable', 'string'],
            'is_partner' => ['sometimes', 'boolean'],
            'is_active'  => ['sometimes', 'boolean'],
        ]);

        $hospital = Hospital::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Rumah sakit berhasil ditambahkan.',
            'data'    => $hospital->loadCount('doctors'),
        ], 201);
    }

    public function updateHospital(Request $request, string $id): JsonResponse
    {
        $hospital = Hospital::findOrFail($id);

        $validated = $request->validate([
            'name'       => ['sometimes', 'string', 'max:255'],
            'address'    => ['sometimes', 'string'],
            'city'       => ['sometimes', 'string', 'max:100'],
            'province'   => ['nullable', 'string', 'max:100'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'email'      => ['nullable', 'email'],
            'latitude'   => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude'  => ['sometimes', 'numeric', 'between:-180,180'],
            'type'       => ['sometimes', 'in:umum,swasta,khusus,puskesmas'],
            'facilities' => ['nullable', 'string'],
            'is_partner' => ['sometimes', 'boolean'],
            'is_active'  => ['sometimes', 'boolean'],
        ]);

        $hospital->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Rumah sakit berhasil diperbarui.',
            'data'    => $hospital->fresh()->loadCount('doctors'),
        ]);
    }

    public function deleteHospital(string $id): JsonResponse
    {
        Hospital::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Rumah sakit berhasil dihapus.']);
    }

    // ─── CONSULTATIONS ───────────────────────────────────────────────────────

    public function doctors(Request $request): JsonResponse
    {
        $query = Doctor::with('hospital')
            ->when($request->hospital_id, fn($q) => $q->where('hospital_id', $request->hospital_id))
            ->when($request->availability, fn($q) => $q->where('availability', $request->availability))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('specialist', 'like', "%{$request->search}%")
                ->orWhereHas('hospital', fn($h) => $h->where('name', 'like', "%{$request->search}%")))
            ->latest();

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    public function storeDoctor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hospital_id'  => ['required', 'exists:hospitals,id'],
            'name'         => ['required', 'string', 'max:255'],
            'specialist'   => ['required', 'string', 'max:255'],
            'photo'        => ['nullable', 'string', 'max:255'],
            'availability' => ['sometimes', 'in:available,unavailable'],
        ]);

        $doctor = Doctor::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Dokter berhasil ditambahkan.',
            'data'    => $doctor->load('hospital'),
        ], 201);
    }

    public function updateDoctor(Request $request, string $id): JsonResponse
    {
        $doctor = Doctor::findOrFail($id);

        $validated = $request->validate([
            'hospital_id'  => ['sometimes', 'exists:hospitals,id'],
            'name'         => ['sometimes', 'string', 'max:255'],
            'specialist'   => ['sometimes', 'string', 'max:255'],
            'photo'        => ['nullable', 'string', 'max:255'],
            'availability' => ['sometimes', 'in:available,unavailable'],
        ]);

        $doctor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Dokter berhasil diperbarui.',
            'data'    => $doctor->fresh('hospital'),
        ]);
    }

    public function deleteDoctor(string $id): JsonResponse
    {
        Doctor::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Dokter berhasil dihapus.']);
    }

    public function consultations(Request $request): JsonResponse
    {
        $query = DoctorConsultation::with(['user', 'messages'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%"))
                ->orWhere('doctor_name', 'like', "%{$request->search}%"))
            ->latest();

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    public function updateConsultation(Request $request, string $id): JsonResponse
    {
        $consultation = DoctorConsultation::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:waiting_approval,approved,rejected,completed'],
        ]);

        $consultation->update($validated);

        return response()->json(['success' => true, 'data' => $consultation->fresh('user'), 'message' => 'Consultation updated.']);
    }

    public function verifyConsultationPayment(Request $request, string $id): JsonResponse
    {
        $consultation = DoctorConsultation::findOrFail($id);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
        ]);

        if ($consultation->payment_method !== 'transfer') {
            return response()->json(['success' => false, 'message' => 'Hanya konsultasi dengan metode transfer yang perlu diverifikasi.'], 422);
        }

        if ($validated['action'] === 'approve') {
            $consultation->update(['payment_status' => 'paid']);
            return response()->json(['success' => true, 'data' => $consultation->fresh('user'), 'message' => 'Pembayaran transfer berhasil diverifikasi. Konsultasi siap disetujui.']);
        }

        // Reject payment → mark payment as failed and set consultation as rejected
        $consultation->update(['payment_status' => 'failed', 'status' => 'rejected']);
        return response()->json(['success' => true, 'data' => $consultation->fresh('user'), 'message' => 'Bukti transfer ditolak. Konsultasi telah dibatalkan.']);
    }

    public function sendConsultationMessage(Request $request, string $id): JsonResponse
    {
        $consultation = DoctorConsultation::findOrFail($id);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $msg = $consultation->messages()->create([
            'sender'  => 'admin',
            'message' => $validated['message'],
        ]);

        return response()->json(['success' => true, 'data' => $msg, 'message' => 'Message sent.']);
    }

    // ─── FEEDBACKS ───────────────────────────────────────────────────────────

    public function feedbacks(Request $request): JsonResponse
    {
        $query = Feedback::with('user')
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->filled('is_featured'), fn($q) => $q->where('is_featured', $request->boolean('is_featured')))
            ->latest();

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    public function updateFeedbackFeatured(Request $request, string $id): JsonResponse
    {
        $feedback = Feedback::findOrFail($id);

        $validated = $request->validate([
            'is_featured' => ['required', 'boolean'],
        ]);

        $feedback->update($validated);

        return response()->json([
            'success' => true,
            'message' => $validated['is_featured']
                ? 'Feedback ditampilkan di halaman pengguna.'
                : 'Feedback disembunyikan dari halaman pengguna.',
            'data' => $feedback->fresh('user'),
        ]);
    }

    // ─── HOSPITAL REGISTRATIONS ──────────────────────────────────────────────

    public function hospitalRegistrations(Request $request): JsonResponse
    {
        $query = HospitalRegistration::with(['user', 'hospital'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%"))
                ->orWhere('hospital_name', 'like', "%{$request->search}%"))
            ->latest();

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    // ─── SERVICE REGISTRATIONS ───────────────────────────────────────────────

    public function serviceRegistrations(Request $request): JsonResponse
    {
        $query = ServiceRegistration::with(['user', 'hospital'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%"))
                ->orWhere('service_name', 'like', "%{$request->search}%"))
            ->latest();

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    // ─── INSURANCE PACKAGES ──────────────────────────────────────────────────

    public function packages(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => InsurancePackage::all()]);
    }

    public function storePackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'           => ['required', 'in:jiwa,kesehatan,kendaraan'],
            'label'          => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string'],
            'coverage_limit' => ['required', 'numeric', 'min:0'],
            'premium_amount' => ['required', 'numeric', 'min:0'],
            'benefits'       => ['required', 'array', 'min:1'],
            'benefits.*'     => ['required', 'string', 'max:255'],
            'is_active'      => ['sometimes', 'boolean'],
        ]);

        $package = InsurancePackage::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $package,
            'message' => 'Package created.',
        ], 201);
    }

    public function updatePackage(Request $request, string $id): JsonResponse
    {
        $package = InsurancePackage::findOrFail($id);

        $validated = $request->validate([
            'type'           => ['sometimes', 'in:jiwa,kesehatan,kendaraan'],
            'label'          => ['sometimes', 'string', 'max:255'],
            'description'    => ['sometimes', 'string'],
            'coverage_limit' => ['sometimes', 'numeric', 'min:0'],
            'premium_amount' => ['sometimes', 'numeric', 'min:0'],
            'benefits'       => ['sometimes', 'array'],
            'benefits.*'     => ['required_with:benefits', 'string', 'max:255'],
            'is_active'      => ['sometimes', 'boolean'],
        ]);

        $package->update($validated);

        return response()->json(['success' => true, 'data' => $package->fresh(), 'message' => 'Package updated.']);
    }

    public function deletePackage(string $id): JsonResponse
    {
        InsurancePackage::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Package deleted.']);
    }

    // ─── PROMOTIONS ──────────────────────────────────────────────────────────

    public function promotions(Request $request): JsonResponse
    {
        $query = Promotion::query()
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%")
                ->orWhere('badge', 'like', "%{$request->search}%"))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->latest();

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    public function storePromotion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'badge'              => ['required', 'string', 'max:255'],
            'title'              => ['required', 'string', 'max:255'],
            'description'        => ['required', 'string'],
            'discount_percent'   => ['required', 'integer', 'min:1', 'max:100'],
            'required_referrals' => ['required', 'integer', 'min:1', 'max:50'],
            'button_label'       => ['required', 'string', 'max:255'],
            'button_url'         => ['required', 'string', 'max:255'],
            'image_file'         => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'is_active'          => ['sometimes', 'boolean'],
            'sort_order'         => ['sometimes', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('image_file')) {
            $validated['image'] = $this->uploadPromotionImage($request->file('image_file'));
        }

        unset($validated['image_file']);

        $promotion = Promotion::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil ditambahkan.',
            'data'    => $promotion,
        ], 201);
    }

    public function updatePromotion(Request $request, string $id): JsonResponse
    {
        $promotion = Promotion::findOrFail($id);

        $validated = $request->validate([
            'badge'              => ['sometimes', 'string', 'max:255'],
            'title'              => ['sometimes', 'string', 'max:255'],
            'description'        => ['sometimes', 'string'],
            'discount_percent'   => ['sometimes', 'integer', 'min:1', 'max:100'],
            'required_referrals' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'button_label'       => ['sometimes', 'string', 'max:255'],
            'button_url'         => ['sometimes', 'string', 'max:255'],
            'image_file'         => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image'       => ['sometimes', 'boolean'],
            'is_active'          => ['sometimes', 'boolean'],
            'sort_order'         => ['sometimes', 'integer', 'min:0'],
        ]);

        if ($request->boolean('remove_image')) {
            $this->deletePromotionImage($promotion->image);
            $validated['image'] = null;
        } elseif ($request->hasFile('image_file')) {
            $this->deletePromotionImage($promotion->image);
            $validated['image'] = $this->uploadPromotionImage($request->file('image_file'));
        }

        unset($validated['image_file'], $validated['remove_image']);

        $promotion->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil diperbarui.',
            'data'    => $promotion->fresh(),
        ]);
    }

    public function deletePromotion(string $id): JsonResponse
    {
        $promotion = Promotion::findOrFail($id);
        $this->deletePromotionImage($promotion->image);
        $promotion->delete();

        return response()->json(['success' => true, 'message' => 'Promo berhasil dihapus.']);
    }

    private function uploadPromotionImage($file): string
    {
        return $this->uploadPublicImage($file, 'promotions');
    }

    private function deletePromotionImage(?string $path): void
    {
        $this->deletePublicImage($path);
    }

    // ─── ANNOUNCEMENTS / INFORMASI ───────────────────────────────────────────

    public function announcements(Request $request): JsonResponse
    {
        $query = Announcement::query()
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%")
                ->orWhere('badge', 'like', "%{$request->search}%"))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->latest();

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    public function storeAnnouncement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'badge'        => ['required', 'string', 'max:255'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string'],
            'button_label' => ['nullable', 'string', 'max:255'],
            'button_url'   => ['nullable', 'string', 'max:255'],
            'image_file'   => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'is_active'    => ['sometimes', 'boolean'],
            'sort_order'   => ['sometimes', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('image_file')) {
            $validated['image'] = $this->uploadPublicImage($request->file('image_file'), 'announcements');
        }

        unset($validated['image_file']);

        $announcement = Announcement::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Informasi berhasil ditambahkan.',
            'data'    => $announcement,
        ], 201);
    }

    public function updateAnnouncement(Request $request, string $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $validated = $request->validate([
            'badge'        => ['sometimes', 'string', 'max:255'],
            'title'        => ['sometimes', 'string', 'max:255'],
            'description'  => ['sometimes', 'string'],
            'button_label' => ['nullable', 'string', 'max:255'],
            'button_url'   => ['nullable', 'string', 'max:255'],
            'image_file'   => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
            'is_active'    => ['sometimes', 'boolean'],
            'sort_order'   => ['sometimes', 'integer', 'min:0'],
        ]);

        if ($request->boolean('remove_image')) {
            $this->deletePublicImage($announcement->image);
            $validated['image'] = null;
        } elseif ($request->hasFile('image_file')) {
            $this->deletePublicImage($announcement->image);
            $validated['image'] = $this->uploadPublicImage($request->file('image_file'), 'announcements');
        }

        unset($validated['image_file'], $validated['remove_image']);

        $announcement->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Informasi berhasil diperbarui.',
            'data'    => $announcement->fresh(),
        ]);
    }

    public function deleteAnnouncement(string $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);
        $this->deletePublicImage($announcement->image);
        $announcement->delete();

        return response()->json(['success' => true, 'message' => 'Informasi berhasil dihapus.']);
    }

    private function uploadPublicImage($file, string $folder): string
    {
        $directory = public_path($folder);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return $folder . '/' . $filename;
    }

    private function deletePublicImage(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $fullPath = public_path($path);

        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    // ─── PROMO CODES (KODE DISKON PEMBAYARAN) ────────────────────────────────

    public function promoCodes(Request $request): JsonResponse
    {
        $query = PromoCode::query()
            ->when($request->search, fn ($q) => $q->where('code', 'like', "%{$request->search}%")
                ->orWhere('title', 'like', "%{$request->search}%"))
            ->orderByDesc('id');

        return response()->json(['success' => true, 'data' => $query->paginate($request->per_page ?? 15)]);
    }

    public function storePromoCode(Request $request): JsonResponse
    {
        $validated = $this->validatePromoCodePayload($request);
        $validated['code'] = strtoupper($validated['code']);

        $promoCode = PromoCode::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kode promo berhasil ditambahkan.',
            'data'    => $promoCode,
        ], 201);
    }

    public function updatePromoCode(Request $request, string $id): JsonResponse
    {
        $promoCode = PromoCode::findOrFail($id);
        $validated = $this->validatePromoCodePayload($request, true);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        $promoCode->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kode promo berhasil diperbarui.',
            'data'    => $promoCode->fresh(),
        ]);
    }

    public function deletePromoCode(string $id): JsonResponse
    {
        PromoCode::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Kode promo berhasil dihapus.']);
    }

    private function validatePromoCodePayload(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'code'                => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],
            'title'               => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'discount_percent'    => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:1', 'max:100'],
            'applicable_features' => [$isUpdate ? 'sometimes' : 'required', 'array', 'min:1'],
            'applicable_features.*' => ['string', 'in:' . implode(',', array_keys(PromoCode::FEATURE_LABELS))],
            'usage_limit'         => ['nullable', 'integer', 'min:1'],
            'per_user_limit'      => ['sometimes', 'integer', 'min:1', 'max:100'],
            'starts_at'           => ['nullable', 'date'],
            'ends_at'             => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active'           => ['sometimes', 'boolean'],
        ];

        if ($isUpdate) {
            $rules['code'][] = 'unique:promo_codes,code,' . $request->route('id');
        } else {
            $rules['code'][] = 'unique:promo_codes,code';
        }

        return $request->validate($rules);
    }
}
