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

    public function monitor()
    {
        $rooms = Room::with(['bookings' => function ($q) {
            $q->where('date', today())
              ->where('status', 'approved');
        }])->get();

        return view('monitor', compact('rooms'));
    }

    public function checkQuota(Request $request)
    {
        $roomId = $request->query('room_id');
        $date = $request->query('date');

        if (!$roomId || !$date) {
            return response()->json(['error' => 'Missing parameters'], 400);
        }

        $room = Room::find($roomId);
        if (!$room) {
            return response()->json(['error' => 'Room not found'], 404);
        }

        if ($room->booking_quota === null) {
            return response()->json([
                'quota' => null,
                'remaining' => null,
            ]);
        }

        $count = Booking::where('room_id', $roomId)
            ->whereDate('date', $date)
            ->whereIn('status', ['approved', 'pending'])
            ->count();

        $remaining = max(0, $room->booking_quota - $count);

        return response()->json([
            'quota' => $room->booking_quota,
            'remaining' => $remaining,
        ]);
    }

    public function book(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('filament.user.auth.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $validated = $request->validate([
            'room_id'      => 'required|exists:rooms,id',
            'renter_name'  => 'required|string|max:255',
            'renter_email' => 'required|email|max:255',
            'renter_phone' => 'required|string|max:20',
            'date'         => 'required|date|after:today',
            'start_time'   => 'required',
            'end_time'     => 'required|after:start_time',
            'purpose'      => 'required|string',
        ], [
            'date.after' => 'Pemesanan maksimal dilakukan sehari sebelumnya (minimal untuk besok).',
        ]);

        $room = Room::find($validated['room_id']);
        if ($room && $room->booking_quota !== null) {
            $approvedCount = Booking::where('room_id', $validated['room_id'])
                ->whereDate('date', $validated['date'])
                ->whereIn('status', ['approved', 'pending'])
                ->count();
            if ($approvedCount >= $room->booking_quota) {
                return back()->withErrors(['quota' => "Kuota peminjaman untuk ruangan {$room->name} pada tanggal tersebut sudah penuh atau sedang diajukan (Maksimal {$room->booking_quota} peminjaman)."])->withInput();
            }
        }

        if (Booking::hasConflict($validated['room_id'], $validated['date'], $validated['start_time'], $validated['end_time'])) {
            return back()->withErrors(['conflict' => 'Jadwal bentrok dengan peminjaman lain yang sudah disetujui. Silakan pilih waktu lain.'])->withInput();
        }

        $validated['user_id'] = auth()->id();

        Booking::create($validated);

        return back()->with('success', 'Permohonan peminjaman berhasil dikirim! Tunggu konfirmasi dari admin.');
    }

    public function complaint(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email_or_phone' => 'required|string|max:255',
            'complaint_text' => 'required|string',
        ]);

        Complaint::create($validated);

        return back()->with('complaint_success', 'Keluhan Anda berhasil dikirim. Terima kasih!');
    }
}
