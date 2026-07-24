<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::all();
        $approvedBookings = Booking::with('room')
            ->where('status', 'approved')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('welcome', compact('rooms', 'approvedBookings'));
    }

    public function jadwalPage()
    {
        $rooms = Room::all();
        $approvedBookings = Booking::with('room')
            ->where('status', 'approved')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('jadwal', compact('rooms', 'approvedBookings'));
    }

    public function peminjamanPage()
    {
        $rooms = Room::all();
        $myBookings = collect();

        if (auth()->check()) {
            $myBookings = Booking::with('room')
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('peminjaman', compact('rooms', 'myBookings'));
    }

    public function complaintPage()
    {
        $rooms = Room::all();
        $myComplaints = collect();

        if (auth()->check()) {
            $myComplaints = Complaint::with(['resolver', 'room'])->where(function ($q) {
                    $q->where('user_id', auth()->id())
                      ->orWhere('email_or_phone', auth()->user()->email);
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('keluhan', compact('rooms', 'myComplaints'));
    }



    public function monitor()
    {
        if (!auth()->check() || !(auth()->user()->is_admin || auth()->user()->email === 'admin@ruangan.com')) {
            abort(403, 'Hanya admin yang dapat mengakses halaman ini.');
        }

        $rooms = Room::with(['currentBooking', 'bookings' => function ($q) {
            $q->where('date', today())
              ->where('status', 'approved')
              ->orderBy('start_time');
        }])->get();

        return view('monitor', compact('rooms'));
    }

    public function checkQuota(Request $request)
    {
        return response()->json([
            'quota' => null,
            'remaining' => null,
            'blocked_times' => [],
        ]);
    }

    public function checkStatus()
    {
        $lastBookingUpdate = Booking::max('updated_at');
        $lastRoomUpdate = Room::max('updated_at');
        $lastComplaintUpdate = Complaint::max('updated_at');
        $totalApproved = Booking::where('status', 'approved')->count();
        $hash = md5($lastBookingUpdate . '|' . $lastRoomUpdate . '|' . $lastComplaintUpdate . '|' . $totalApproved);

        return response()->json([
            'hash' => $hash,
        ]);
    }

    public function book(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('filament.user.auth.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Automatically set renter_phone from authenticated user's phone
        $request->merge([
            'renter_phone' => auth()->user()->phone ?? '08123456789',
        ]);

        $rules = [
            'room_id'         => 'required|exists:rooms,id',
            'renter_name'     => 'required|string|max:255',
            'renter_phone'    => 'required|string|max:20',
            'date'            => 'required|date|after:today',
            'start_time'      => 'required',
            'end_time'        => 'required|after:start_time',
            'purpose'         => 'required|string',
            'permit_letter'   => 'nullable|file|mimes:pdf|max:5120',
            'is_recurring'    => 'nullable|boolean',
            'end_date'        => 'required_if:is_recurring,1|nullable|date|after_or_equal:date',
            'recurring_day'   => 'required_if:is_recurring,1|nullable|integer|between:0,6',
        ];

        $messages = [
            'date.after' => 'Pemesanan maksimal dilakukan sehari sebelumnya (minimal untuk besok).',
            'end_time.after' => 'Jam selesai harus lebih lambat dari jam mulai.',
            'permit_letter.mimes' => 'Surat permohonan harus berformat PDF.',
            'permit_letter.max' => 'Ukuran surat permohonan maksimal 5 MB.',
            'end_date.required_if' => 'Tanggal selesai berkala wajib diisi jika peminjaman berkala dipilih.',
            'end_date.after_or_equal' => 'Tanggal selesai berkala harus setelah atau sama dengan tanggal mulai.',
            'recurring_day.required_if' => 'Hari berkala wajib dipilih jika peminjaman berkala dipilih.',
        ];

        $validated = $request->validate($rules, $messages);

        // Handle file upload
        $permitLetterPath = null;
        if ($request->hasFile('permit_letter')) {
            $file = $request->file('permit_letter');
            $filename = uniqid() . '.pdf';
            $permitLetterPath = $file->storeAs('permit_letters', $filename, 'public');
        }

        $userId = auth()->id();

        if ($request->boolean('is_recurring')) {
            $startDate = \Carbon\Carbon::parse($validated['date']);
            $endDate = \Carbon\Carbon::parse($validated['end_date']);
            $dayOfWeek = (int) $validated['recurring_day'];

            $dates = [];
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                if ($current->dayOfWeek === $dayOfWeek) {
                    $dates[] = $current->format('Y-m-d');
                }
                $current->addDay();
            }

            if (empty($dates)) {
                return back()->withErrors(['conflict' => 'Tidak ada hari yang cocok dalam rentang tanggal yang dipilih.'])->withInput();
            }

            // Check conflicts for all dates
            $conflicts = [];
            foreach ($dates as $d) {
                if (Booking::hasConflict($validated['room_id'], $d, $validated['start_time'], $validated['end_time'])) {
                    $conflicts[] = \Carbon\Carbon::parse($d)->format('d M Y');
                }
            }

            if (!empty($conflicts)) {
                return back()->withErrors(['conflict' => 'Jadwal bentrok pada tanggal: ' . implode(', ', $conflicts)])->withInput();
            }

            // Create bookings
            $recurringToken = uniqid('rec_');
            foreach ($dates as $d) {
                Booking::create([
                    'room_id'            => $validated['room_id'],
                    'user_id'            => $userId,
                    'renter_name'        => $validated['renter_name'],
                    'renter_phone'       => $validated['renter_phone'],
                    'date'               => $d,
                    'start_time'         => $validated['start_time'],
                    'end_time'           => $validated['end_time'],
                    'purpose'            => $validated['purpose'],
                    'permit_letter_path' => $permitLetterPath,
                    'recurring_token'    => $recurringToken,
                    'status'             => 'pending',
                ]);
            }

            return back()->with('success', 'Permohonan peminjaman berkala berhasil dikirim! Tunggu konfirmasi dari admin.');
        } else {
            if (Booking::hasConflict($validated['room_id'], $validated['date'], $validated['start_time'], $validated['end_time'])) {
                return back()->withErrors(['conflict' => 'Jadwal bentrok dengan peminjaman lain yang sudah disetujui.'])->withInput();
            }

            Booking::create([
                'room_id'            => $validated['room_id'],
                'user_id'            => $userId,
                'renter_name'        => $validated['renter_name'],
                'renter_phone'       => $validated['renter_phone'],
                'date'               => $validated['date'],
                'start_time'         => $validated['start_time'],
                'end_time'           => $validated['end_time'],
                'purpose'            => $validated['purpose'],
                'permit_letter_path' => $permitLetterPath,
                'status'             => 'pending',
            ]);

            return back()->with('success', 'Permohonan peminjaman berhasil dikirim! Tunggu konfirmasi dari admin.');
        }
    }

    public function complaint(Request $request)
    {
        // Automatically set email_or_phone from authenticated user's phone if logged in
        if (auth()->check()) {
            $request->merge([
                'email_or_phone' => auth()->user()->phone ?? '08123456789',
            ]);
        } else {
            $request->merge([
                'email_or_phone' => '08123456789',
            ]);
        }

        $validated = $request->validate([
            'room_id'        => ['nullable', 'exists:rooms,id'],
            'name'           => ['required', 'string', 'max:255'],
            'email_or_phone' => ['required', 'string', 'regex:/^(08|\+62|62)[0-9]{7,13}$/'],
            'complaint_text' => ['required', 'string'],
            'photo'          => ['nullable', 'image', 'max:2048'],
        ], [
            'email_or_phone.regex' => 'Format nomor HP tidak valid. Gunakan format seperti 08123456789 atau +628123456789.',
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = uniqid() . '.webp';
            
            $info = getimagesize($file->getRealPath());
            $image = null;
            if ($info) {
                $mime = $info['mime'];
                switch ($mime) {
                    case 'image/jpeg':
                        $image = imagecreatefromjpeg($file->getRealPath());
                        break;
                    case 'image/png':
                        $image = imagecreatefrompng($file->getRealPath());
                        if ($image) {
                            imagepalettetotruecolor($image);
                            imagesavealpha($image, true);
                        }
                        break;
                    case 'image/gif':
                        $image = imagecreatefromgif($file->getRealPath());
                        break;
                    case 'image/webp':
                        $image = imagecreatefromwebp($file->getRealPath());
                        break;
                }
            }

            if ($image) {
                ob_start();
                imagewebp($image, null, 80);
                $webpData = ob_get_clean();
                imagedestroy($image);

                \Illuminate\Support\Facades\Storage::disk('public')->put('complaints/' . $filename, $webpData);
                $validated['photo_path'] = 'complaints/' . $filename;
            } else {
                $path = $file->store('complaints', 'public');
                $validated['photo_path'] = $path;
            }
        }

        if (auth()->check()) {
            $validated['user_id'] = auth()->id();
        }

        Complaint::create($validated);

        return back()->with('complaint_success', 'Keluhan Anda berhasil dikirim. Terima kasih!');
    }



    public function cancelBooking(Booking $booking)
    {
        if (!auth()->check() || $booking->user_id !== auth()->id()) {
            abort(403);
        }

        if ($booking->status !== 'pending') {
            return back()->withErrors(['booking_error' => 'Hanya peminjaman berstatus pending yang dapat dibatalkan.']);
        }

        $booking->delete();

        return back()->with('success', 'Permohonan peminjaman berhasil dibatalkan.');
    }

    public function unreadNotifications()
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $notifications = auth()->user()->unreadNotifications;

        return response()->json($notifications->map(fn($n) => [
            'id' => $n->id,
            'title' => $n->data['title'] ?? 'Notifikasi Baru',
            'body' => $n->data['body'] ?? '',
        ]));
    }

    public function profilePage()
    {
        return view('profile');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => ['required', 'string', 'regex:/^(08|\+62|62)[0-9]{7,13}$/'],
            'password' => 'nullable|string|min:4|confirmed',
        ], [
            'phone.regex' => 'Format nomor HP tidak valid. Gunakan format seperti 08123456789 atau +628123456789.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        if (filled($request->password)) {
            $newPassword = $request->password;

            // Check password history (cannot reuse passwords from the last 3 months)
            $threeMonthsAgo = now()->subMonths(3);
            $recentPasswords = $user->passwordHistories()
                ->where('created_at', '>=', $threeMonthsAgo)
                ->pluck('password');

            foreach ($recentPasswords as $oldPassword) {
                if (\Illuminate\Support\Facades\Hash::check($newPassword, $oldPassword)) {
                    return back()->withErrors(['password' => 'Anda tidak boleh menggunakan password yang pernah digunakan dalam 3 bulan terakhir.'])->withInput();
                }
            }

            // Also check current password
            if (\Illuminate\Support\Facades\Hash::check($newPassword, $user->password)) {
                return back()->withErrors(['password' => 'Password baru tidak boleh sama dengan password saat ini.'])->withInput();
            }

            // Save old password to history
            $user->passwordHistories()->create([
                'password' => $user->password,
            ]);

            $user->password = \Illuminate\Support\Facades\Hash::make($newPassword);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'];
        $user->save();

        return back()->with('success', 'Profil Anda berhasil diperbarui.');
    }
}
