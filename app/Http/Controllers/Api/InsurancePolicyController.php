<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InsurancePolicy;
use App\Models\DiscountCoupon;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Services\InsuranceBillingService;
use App\Services\PromoCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InsurancePolicyController extends Controller
{
    public function __construct(
        private readonly PromoCodeService $promoCodeService,
        private readonly InsuranceBillingService $billingService
    )
    {
    }

    public function myPolicies(Request $request): JsonResponse
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            $userId = $request->query('user_id');
            if (!$userId) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            $user = \App\Models\User::query()->find($userId);
            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }
        }

        $policies = InsurancePolicy::query()
            ->where('user_id', $user->id)
            ->with('claims')
            ->orderByDesc('id')
            ->get()
            ->map(function ($policy) {
                $totalApprovedClaims = $policy->claims->whereIn('status', ['approved', 'partial'])->sum('claim_amount');
                $policy->remaining_limit = max(0, $policy->coverage_limit - $totalApprovedClaims);
                return $policy;
            });

        return response()->json([
            'message' => 'User policies retrieved successfully.',
            'data'    => $policies,
        ], 200);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Insurance policies retrieved successfully.',
            'data' => InsurancePolicy::query()->orderByDesc('id')->get(),
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'policy_number' => ['required', 'string', 'max:255', 'unique:insurance_policies,policy_number'],
            'insurance_type' => ['required', 'in:jiwa,kesehatan,kendaraan'],
            'insured_name' => ['required', 'string', 'max:255'],
            'premium_amount' => ['required', 'numeric', 'min:0'],
            'coverage_limit' => ['sometimes', 'numeric', 'min:0'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'in:active,inactive'],
            'payment_method' => ['sometimes', 'nullable', 'in:Transfer Bank,GoPay / OVO,Dana / ShopeePay,Alfamart / Indomaret'],
            'payment_proof' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'billing_cycle' => ['sometimes', 'in:monthly,yearly'],
            'grace_period_days' => ['sometimes', 'integer', 'min:1', 'max:90'],
            'discount_code' => ['sometimes', 'nullable', 'string'],
            'promo_code' => ['sometimes', 'nullable', 'string'],
        ]);

        $promoCodeInput = $validated['promo_code'] ?? $validated['discount_code'] ?? null;

        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $paymentProofPath = $this->uploadFile($request->file('payment_proof'));
        }

        $paymentStatus = null;
        $policyStatus = $validated['status'] ?? 'active';
        if (! empty($validated['payment_method']) || $paymentProofPath) {
            $paymentStatus = 'pending';
            $policyStatus = 'inactive';
        }

        $originalPremium = (float) $validated['premium_amount'];
        $premiumAmount = $originalPremium;
        $discountAmount = 0;
        $appliedPromoCode = null;
        $promoResult = null;

        if (! empty($promoCodeInput)) {
            try {
                $promoResult = $this->promoCodeService->validate(
                    $promoCodeInput,
                    (int) $validated['user_id'],
                    PromoCode::FEATURE_INSURANCE,
                    $originalPremium
                );
            } catch (ValidationException $e) {
                return response()->json([
                    'message' => collect($e->errors())->flatten()->first() ?? 'Kode promo tidak valid.',
                ], 422);
            }

            $premiumAmount = (float) $promoResult['final_amount'];
            $discountAmount = (float) $promoResult['discount_amount'];
            $appliedPromoCode = $promoResult['code'];
        }

        $insurancePolicy = InsurancePolicy::create([
            'user_id' => $validated['user_id'],
            'policy_number' => $validated['policy_number'],
            'insurance_type' => $validated['insurance_type'],
            'insured_name' => $validated['insured_name'],
            'premium_amount' => $premiumAmount,
            'original_premium_amount' => $appliedPromoCode ? $originalPremium : null,
            'discount_amount' => $appliedPromoCode ? $discountAmount : null,
            'promo_code' => $appliedPromoCode,
            'coverage_limit' => $validated['coverage_limit'] ?? 100000000,
            'start_date' => $validated['start_date'] ?? now()->toDateString(),
            'end_date' => $validated['end_date'] ?? now()->addYear()->toDateString(),
            'status' => $policyStatus,
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_proof_path' => $paymentProofPath,
            'payment_status' => $paymentStatus,
            'billing_cycle' => $validated['billing_cycle'] ?? 'monthly',
            'grace_period_days' => $validated['grace_period_days'] ?? 30,
            'next_payment_due_date' => $validated['start_date'] ?? now()->toDateString(),
        ]);

        if ($promoResult) {
            if ($promoResult['source'] === 'admin') {
                $this->promoCodeService->recordAdminUsage(
                    PromoCode::findOrFail($promoResult['source_id']),
                    (int) $validated['user_id'],
                    PromoCode::FEATURE_INSURANCE,
                    $originalPremium,
                    $discountAmount,
                    $premiumAmount,
                    'insurance_policy',
                    $insurancePolicy->id
                );
            } else {
                $this->promoCodeService->recordReferralUsage(
                    DiscountCoupon::findOrFail($promoResult['source_id'])
                );
            }
        }

        return response()->json([
            'message' => 'Insurance policy created successfully.',
            'data'    => $insurancePolicy,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $insurancePolicy = InsurancePolicy::findOrFail($id);

        return response()->json([
            'message' => 'Insurance policy retrieved successfully.',
            'data' => $insurancePolicy,
        ], 200);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $insurancePolicy = InsurancePolicy::findOrFail($id);

        $validated = $request->validate([
            'user_id' => ['sometimes', 'exists:users,id'],
            'policy_number' => ['sometimes', 'string', 'max:255', 'unique:insurance_policies,policy_number,' . $insurancePolicy->id],
            'insurance_type' => ['sometimes', 'in:jiwa,kesehatan,kendaraan'],
            'insured_name' => ['sometimes', 'string', 'max:255'],
            'premium_amount' => ['sometimes', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/' ],
            'status' => ['sometimes', 'in:active,inactive'],
            'payment_method' => ['sometimes', 'nullable', 'in:Transfer Bank,GoPay / OVO,Dana / ShopeePay,Alfamart / Indomaret'],
            'payment_proof' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'payment_status' => ['sometimes', 'nullable', 'in:pending,verified,rejected'],
            'billing_cycle' => ['sometimes', 'in:monthly,yearly'],
            'grace_period_days' => ['sometimes', 'integer', 'min:1', 'max:90'],
            'next_payment_due_date' => ['sometimes', 'nullable', 'date'],
        ]);

        if ($request->hasFile('payment_proof')) {
            $validated['payment_proof_path'] = $this->uploadFile($request->file('payment_proof'));
        }

        $previousPaymentStatus = $insurancePolicy->payment_status;
        $insurancePolicy->fill($validated)->save();

        if ($insurancePolicy->payment_status === 'verified' && $previousPaymentStatus !== 'verified') {
            $this->billingService->markPaymentVerified($insurancePolicy);

            Transaction::create([
                'user_id' => $insurancePolicy->user_id,
                'insurance_policy_id' => $insurancePolicy->id,
                'transaction_type' => 'premi_masuk',
                'amount' => $insurancePolicy->premium_amount,
                'transaction_date' => now(),
                'status' => 'success',
            ]);
        }

        return response()->json([
            'message' => 'Insurance policy updated successfully.',
            'data' => $insurancePolicy->fresh(),
        ], 200);
    }

    public function submitPremiumPayment(Request $request, string $id): JsonResponse
    {
        $insurancePolicy = InsurancePolicy::findOrFail($id);

        $validated = $request->validate([
            'user_id' => ['sometimes', 'exists:users,id'],
            'payment_method' => ['required', 'in:Transfer Bank,GoPay / OVO,Dana / ShopeePay,Alfamart / Indomaret'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $user = \Illuminate\Support\Facades\Auth::user();
        $requestedUserId = $validated['user_id'] ?? null;

        if ($user && (int) $insurancePolicy->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $user && $requestedUserId && (int) $insurancePolicy->user_id !== (int) $requestedUserId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $insurancePolicy->update([
            'payment_method' => $validated['payment_method'],
            'payment_proof_path' => $this->uploadFile($request->file('payment_proof')),
            'payment_status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Bukti pembayaran premi berhasil dikirim. Menunggu verifikasi admin.',
            'data' => $insurancePolicy->fresh(),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $insurancePolicy = InsurancePolicy::findOrFail($id);
        $insurancePolicy->delete();

        return response()->json([
            'message' => 'Insurance policy deleted successfully.',
        ], 200);
    }

    private function uploadFile($file): string
    {
        $directory = public_path('payments');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'payments/' . $filename;
    }
}
