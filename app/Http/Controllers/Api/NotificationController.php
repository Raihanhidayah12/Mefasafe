<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Reminder;
use App\Models\Transaction;
use App\Models\HospitalRegistration;
use App\Models\DoctorConsultation;
use App\Models\InsurancePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     */
    public function index(Request $request)
    {
        $userId = $request->query('user_id') ?? Auth::id();
        if (!$userId) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }
        $notifications = [];

        // 1. Notifikasi dari Claims
        $claims = Claim::where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($claims as $claim) {
            $notification = [
                'id' => 'claim-' . $claim->id,
                'type' => 'claim',
                'title' => $this->getClaimTitle($claim->status),
                'desc' => $this->getClaimDescription($claim),
                'status' => $this->getClaimStatusLabel($claim->status),
                'time' => $this->getTimeAgo($claim->updated_at),
                'icon' => 'kertas',
                'tone' => $this->getClaimTone($claim->status),
                'created_at' => $claim->updated_at,
            ];
            $notifications[] = $notification;
        }

        // 2. Notifikasi dari Transactions
        $transactions = Transaction::where('user_id', $userId)
            ->orderBy('transaction_date', 'desc')
            ->get();

        foreach ($transactions as $transaction) {
            $notification = [
                'id' => 'transaction-' . $transaction->id,
                'type' => 'transaction',
                'title' => $this->getTransactionTitle($transaction),
                'desc' => $this->getTransactionDescription($transaction),
                'status' => $this->getTransactionStatusLabel($transaction->status),
                'time' => $this->getTimeAgo($transaction->transaction_date),
                'icon' => 'surat',
                'tone' => $this->getTransactionTone($transaction->status),
                'created_at' => $transaction->transaction_date,
            ];
            $notifications[] = $notification;
        }

        // 3. Notifikasi dari Hospital Registrations
        $hospitalRegs = HospitalRegistration::where('user_id', $userId)
            ->orderBy('schedule_date', 'desc')
            ->get();

        foreach ($hospitalRegs as $reg) {
            $notification = [
                'id' => 'hospital-' . $reg->id,
                'type' => 'hospital',
                'title' => 'Pendaftaran Rumah Sakit',
                'desc' => $this->getHospitalDescription($reg),
                'status' => $reg->status === 'registered' ? 'Terdaftar' : 'Dibatalkan',
                'time' => $this->getTimeAgo($reg->created_at),
                'icon' => 'bel',
                'tone' => $reg->status === 'registered' ? 'from-blue-400 to-blue-600' : 'from-gray-400 to-gray-600',
                'created_at' => $reg->created_at,
            ];
            $notifications[] = $notification;
        }

        // 4. Notifikasi dari Doctor Consultations
        $consultations = DoctorConsultation::where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($consultations as $consultation) {
            $notification = [
                'id' => 'consultation-' . $consultation->id,
                'type' => 'consultation',
                'title' => $this->getConsultationTitle($consultation->status),
                'desc' => $this->getConsultationDescription($consultation),
                'status' => $this->getConsultationStatusLabel($consultation->status),
                'time' => $this->getTimeAgo($consultation->updated_at),
                'icon' => 'toa',
                'tone' => $this->getConsultationTone($consultation->status),
                'created_at' => $consultation->updated_at,
            ];
            $notifications[] = $notification;
        }

        // 5. Notifikasi premi asuransi
        $premiumPolicies = InsurancePolicy::where('user_id', $userId)
            ->whereNotNull('next_payment_due_date')
            ->whereDate('next_payment_due_date', '<=', Carbon::today()->addDays(7))
            ->orderBy('next_payment_due_date')
            ->get();

        foreach ($premiumPolicies as $policy) {
            $dueDate = Carbon::parse($policy->next_payment_due_date);
            $graceEndsAt = $dueDate->copy()->addDays((int) ($policy->grace_period_days ?? 30));
            $isOverGrace = Carbon::today()->greaterThan($graceEndsAt);
            $isDue = Carbon::today()->greaterThanOrEqualTo($dueDate);

            if ($policy->payment_status === 'verified' && ! $isDue) {
                $title = 'Premi Segera Jatuh Tempo';
                $status = $dueDate->diffForHumans();
                $tone = 'from-blue-400 to-blue-600';
            } elseif ($isOverGrace || $policy->status === 'inactive') {
                $title = 'Polis Tidak Aktif';
                $status = 'Lewat Masa Tenggang';
                $tone = 'from-red-400 to-red-600';
            } elseif ($policy->payment_status === 'pending') {
                $title = 'Pembayaran Premi Menunggu Verifikasi';
                $status = 'Menunggu';
                $tone = 'from-amber-400 to-amber-600';
            } else {
                $title = 'Premi Sudah Jatuh Tempo';
                $status = 'Jatuh Tempo';
                $tone = 'from-orange-400 to-amber-500';
            }

            $amount = 'Rp ' . number_format((float) $policy->premium_amount, 0, ',', '.');
            $notifications[] = [
                'id' => 'premium-' . $policy->id,
                'type' => 'premium',
                'title' => $title,
                'desc' => "Premi polis {$policy->policy_number} sebesar {$amount} jatuh tempo pada " . $dueDate->format('d M Y') . ". Masa tenggang sampai " . $graceEndsAt->format('d M Y') . ".",
                'status' => $status,
                'time' => $dueDate->diffForHumans(),
                'icon' => 'Pembayaran',
                'tone' => $tone,
                'created_at' => $policy->next_payment_due_date->toDateTimeString(),
            ];
        }

        // 6. Notifikasi dari Reminders yang jatuh tempo hari ini
        $todayReminders = Reminder::where('user_id', $userId)
            ->whereDate('reminder_date', Carbon::today())
            ->where('is_done', false)
            ->orderBy('reminder_time')
            ->get();

        foreach ($todayReminders as $reminder) {
            $categoryLabel = match ($reminder->category) {
                'kontrol' => 'Kontrol Kesehatan',
                'obat'    => 'Minum Obat',
                'vaksin'  => 'Vaksinasi',
                default   => 'Pengingat',
            };
            $timeStr = $reminder->reminder_time
                ? ' pukul ' . substr($reminder->reminder_time, 0, 5)
                : '';
            $notification = [
                'id'         => 'reminder-' . $reminder->id,
                'type'       => 'reminder',
                'title'      => '🔔 ' . $categoryLabel . ' Hari Ini',
                'desc'       => $reminder->title . ($reminder->description ? ' — ' . $reminder->description : '') . $timeStr,
                'status'     => 'Hari Ini',
                'time'       => 'Hari ini' . $timeStr,
                'icon'       => 'bel',
                'tone'       => 'from-orange-400 to-amber-500',
                'created_at' => $reminder->reminder_date->toDateTimeString(),
            ];
            $notifications[] = $notification;
        }

        // Sort by created_at descending
        usort($notifications, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get notification summary/statistics
     */
    public function summary(Request $request)
    {
        $userId = $request->query('user_id') ?? Auth::id();
        if (!$userId) {
            return response()->json([
                'success' => true,
                'data' => [
                    'today_notifications' => 0,
                    'unread_count' => 0,
                    'verified_count' => 0,
                    'needs_attention_count' => 0,
                    'priority_count' => 0,
                    'weekly_activity' => [
                        'claims_submitted' => 0,
                        'successful_payments' => 0,
                        'active_reminders' => 0,
                    ],
                ],
            ]);
        }
        
        $weekAgo = Carbon::now()->subDays(7);

        // Count claims in last 7 days
        $claimsCount = Claim::where('user_id', $userId)
            ->where('created_at', '>=', $weekAgo)
            ->count();

        // Count successful transactions in last 7 days
        $successfulTransactions = Transaction::where('user_id', $userId)
            ->where('status', 'success')
            ->where('transaction_date', '>=', $weekAgo)
            ->count();

        // Count upcoming hospital appointments (reminders)
        $upcomingAppointments = HospitalRegistration::where('user_id', $userId)
            ->where('status', 'registered')
            ->where('schedule_date', '>=', Carbon::now())
            ->count();

        // Count unread notifications (pending/waiting status)
        $unreadCount = Claim::where('user_id', $userId)
            ->where('status', 'pending')
            ->count();

        $unreadCount += DoctorConsultation::where('user_id', $userId)
            ->where('status', 'waiting_approval')
            ->count();

        $unreadCount += InsurancePolicy::where('user_id', $userId)
            ->where('payment_status', 'pending')
            ->count();

        // Count verified items (approved claims + successful transactions)
        $verifiedCount = Claim::where('user_id', $userId)
            ->where('status', 'approved')
            ->count();

        $verifiedCount += Transaction::where('user_id', $userId)
            ->where('status', 'success')
            ->count();

        // Count items needing attention (rejected, failed, pending)
        $needsAttentionCount = Claim::where('user_id', $userId)
            ->whereIn('status', ['rejected', 'pending'])
            ->count();

        $needsAttentionCount += Transaction::where('user_id', $userId)
            ->whereIn('status', ['failed', 'pending'])
            ->count();

        $needsAttentionCount += InsurancePolicy::where('user_id', $userId)
            ->where(function ($query): void {
                $query->where('payment_status', 'pending')
                    ->orWhere('status', 'inactive');
            })
            ->count();

        // Total active notifications today
        $todayNotifications = Claim::where('user_id', $userId)
            ->whereDate('updated_at', Carbon::today())
            ->count();

        $todayNotifications += Transaction::where('user_id', $userId)
            ->whereDate('transaction_date', Carbon::today())
            ->count();

        $todayNotifications += DoctorConsultation::where('user_id', $userId)
            ->whereDate('updated_at', Carbon::today())
            ->count();

        $todayNotifications += \App\Models\Reminder::where('user_id', $userId)
            ->whereDate('reminder_date', Carbon::today())
            ->where('is_done', false)
            ->count();

        $todayNotifications += InsurancePolicy::where('user_id', $userId)
            ->whereNotNull('next_payment_due_date')
            ->whereDate('next_payment_due_date', '<=', Carbon::today())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'today_notifications' => $todayNotifications,
                'unread_count' => $unreadCount,
                'verified_count' => $verifiedCount,
                'needs_attention_count' => $needsAttentionCount,
                'priority_count' => $needsAttentionCount, // Same as needs attention
                'weekly_activity' => [
                    'claims_submitted' => $claimsCount,
                    'successful_payments' => $successfulTransactions,
                    'active_reminders' => $upcomingAppointments,
                ],
            ],
        ]);
    }

    // Helper methods for Claims
    private function getClaimTitle($status)
    {
        return match ($status) {
            'pending' => 'Klaim Sedang Diproses',
            'approved' => 'Klaim Disetujui',
            'rejected' => 'Klaim Ditolak',
            'partial' => 'Klaim Disetujui Sebagian',
            default => 'Update Klaim',
        };
    }

    private function getClaimDescription($claim)
    {
        $amount = 'Rp ' . number_format($claim->claim_amount, 0, ',', '.');
        return match ($claim->status) {
            'pending' => "Klaim Anda sebesar {$amount} sedang dalam proses verifikasi oleh tim kami.",
            'approved' => "Selamat! Klaim Anda sebesar {$amount} telah disetujui dan akan segera diproses.",
            'rejected' => "Mohon maaf, klaim Anda sebesar {$amount} ditolak. Silakan hubungi customer service untuk informasi lebih lanjut.",
            'partial' => "Klaim Anda sebesar {$amount} disetujui sebagian. Silakan cek detail untuk informasi lebih lanjut.",
            default => $claim->description,
        };
    }

    private function getClaimStatusLabel($status)
    {
        return match ($status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'partial' => 'Sebagian',
            default => ucfirst($status),
        };
    }

    private function getClaimTone($status)
    {
        return match ($status) {
            'pending' => 'from-amber-400 to-amber-600',
            'approved' => 'from-emerald-400 to-emerald-600',
            'rejected' => 'from-red-400 to-red-600',
            'partial' => 'from-blue-400 to-blue-600',
            default => 'from-gray-400 to-gray-600',
        };
    }

    // Helper methods for Transactions
    private function getTransactionTitle($transaction)
    {
        if ($transaction->transaction_type === 'premi_masuk') {
            return $transaction->status === 'success' ? 'Pembayaran Premi Berhasil' : 'Pembayaran Premi Gagal';
        } else {
            return $transaction->status === 'success' ? 'Pencairan Klaim Berhasil' : 'Pencairan Klaim Gagal';
        }
    }

    private function getTransactionDescription($transaction)
    {
        $amount = 'Rp ' . number_format($transaction->amount, 0, ',', '.');
        $type = $transaction->transaction_type === 'premi_masuk' ? 'pembayaran premi' : 'pencairan klaim';

        return match ($transaction->status) {
            'success' => "Transaksi {$type} sebesar {$amount} telah berhasil diproses.",
            'failed' => "Transaksi {$type} sebesar {$amount} gagal. Silakan coba lagi atau hubungi customer service.",
            'pending' => "Transaksi {$type} sebesar {$amount} sedang dalam proses.",
            default => "Transaksi {$type} sebesar {$amount}.",
        };
    }

    private function getTransactionStatusLabel($status)
    {
        return match ($status) {
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            'pending' => 'Menunggu',
            default => ucfirst($status),
        };
    }

    private function getTransactionTone($status)
    {
        return match ($status) {
            'success' => 'from-emerald-400 to-emerald-600',
            'failed' => 'from-red-400 to-red-600',
            'pending' => 'from-amber-400 to-amber-600',
            default => 'from-gray-400 to-gray-600',
        };
    }

    // Helper methods for Hospital Registrations
    private function getHospitalDescription($reg)
    {
        $date = Carbon::parse($reg->schedule_date)->format('d M Y');
        return "Pendaftaran Anda di {$reg->hospital_name} dengan Dr. {$reg->doctor_name} pada {$date}. Nomor antrian: {$reg->queue_number}";
    }

    // Helper methods for Doctor Consultations
    private function getConsultationTitle($status)
    {
        return match ($status) {
            'waiting_approval' => 'Konsultasi Menunggu Persetujuan',
            'approved' => 'Konsultasi Disetujui',
            'rejected' => 'Konsultasi Ditolak',
            'completed' => 'Konsultasi Selesai',
            default => 'Update Konsultasi',
        };
    }

    private function getConsultationDescription($consultation)
    {
        $type = $consultation->consultation_type === 'chat' ? 'chat' : 'panggilan';
        return match ($consultation->status) {
            'waiting_approval' => "Permintaan konsultasi {$type} dengan {$consultation->doctor_name} ({$consultation->specialist_type}) sedang menunggu persetujuan.",
            'approved' => "Konsultasi {$type} Anda dengan {$consultation->doctor_name} telah disetujui. Durasi: {$consultation->session_duration_minutes} menit.",
            'rejected' => "Mohon maaf, permintaan konsultasi {$type} dengan {$consultation->doctor_name} ditolak.",
            'completed' => "Konsultasi {$type} dengan {$consultation->doctor_name} telah selesai.",
            default => "Konsultasi dengan {$consultation->doctor_name} ({$consultation->specialist_type}).",
        };
    }

    private function getConsultationStatusLabel($status)
    {
        return match ($status) {
            'waiting_approval' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'completed' => 'Selesai',
            default => ucfirst($status),
        };
    }

    private function getConsultationTone($status)
    {
        return match ($status) {
            'waiting_approval' => 'from-amber-400 to-amber-600',
            'approved' => 'from-emerald-400 to-emerald-600',
            'rejected' => 'from-red-400 to-red-600',
            'completed' => 'from-blue-400 to-blue-600',
            default => 'from-gray-400 to-gray-600',
        };
    }

    // Helper method for time ago
    private function getTimeAgo($datetime)
    {
        $carbon = Carbon::parse($datetime);
        return $carbon->diffForHumans();
    }
}
