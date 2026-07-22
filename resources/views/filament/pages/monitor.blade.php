<x-filament-panels::page>
    <style>
        .rooms-grid-monitor {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .room-card-monitor {
            border-radius: 16px;
            padding: 1.75rem;
            border: 2px solid transparent;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        .room-card-monitor.available {
            background: #f0fdf4;
            border-color: #16a34a;
            color: #14532d;
        }
        .dark .room-card-monitor.available {
            background: #061f0e;
            border-color: #16a34a;
            color: #4ade80;
        }
        .room-card-monitor.available .status-badge {
            background: #16a34a;
            color: white;
        }
        .room-card-monitor.available .room-name { color: #16a34a; }
        .dark .room-card-monitor.available .room-name { color: #4ade80; }

        .room-card-monitor.occupied {
            background: #fef2f2;
            border-color: #dc2626;
            color: #7f1d1d;
        }
        .dark .room-card-monitor.occupied {
            background: #1f0707;
            border-color: #dc2626;
            color: #f87171;
        }
        .room-card-monitor.occupied .status-badge {
            background: #dc2626;
            color: white;
        }
        .room-card-monitor.occupied .room-name { color: #dc2626; }
        .dark .room-card-monitor.occupied .room-name { color: #f87171; }

        .room-card-monitor.cleaning {
            background: #fffbeb;
            border-color: #d97706;
            color: #78350f;
        }
        .dark .room-card-monitor.cleaning {
            background: #1c1202;
            border-color: #d97706;
            color: #fbbf24;
        }
        .room-card-monitor.cleaning .status-badge {
            background: #d97706;
            color: white;
        }
        .room-card-monitor.cleaning .room-name { color: #d97706; }
        .dark .room-card-monitor.cleaning .room-name { color: #fbbf24; }

        .room-name { font-size: 1.6rem; font-weight: 800; margin-bottom: 0.5rem; }
        .room-capacity { font-size: 0.85rem; color: #64748b; margin-bottom: 1rem; }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .booking-info { margin-top: 0.75rem; }
        .booking-info .info-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.4rem;
            font-size: 1rem;
        }
        .booking-info .info-row .label { color: #64748b; font-size: 0.85rem; min-width: 80px; }
        .booking-info .info-row .value { font-weight: 600; }

        .next-booking {
            margin-top: 1rem;
            padding: 0.75rem;
            background: rgba(0,0,0,0.04);
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
        }
        .dark .next-booking {
            background: rgba(255,255,255,0.04);
        }
        .next-booking .next-label { font-size: 0.75rem; color: #3b82f6; font-weight: 700; text-transform: uppercase; margin-bottom: 0.25rem; }
        .next-booking .next-info { font-size: 0.9rem; color: #64748b; }
    </style>

    <div class="rooms-grid-monitor">
        @forelse($rooms as $room)
        @php
            $nowTime = $now;
            $activeBooking = $room->bookings->first(function($b) use ($nowTime) {
                return $b->start_time <= $nowTime && $b->end_time > $nowTime && $b->status === 'approved';
            });
            $nextBooking = $room->bookings->first(function($b) use ($nowTime) {
                return $b->start_time > $nowTime;
            });
            $isOccupied = $room->is_occupied || ($room->current_booking_id !== null) || ($activeBooking !== null);
            $isCleaning = $room->is_cleaning;
            $cardClass = $isCleaning ? 'cleaning' : ($isOccupied ? 'occupied' : 'available');
        @endphp
        <div class="room-card-monitor {{ $cardClass }}">
            <div class="room-name">{{ $room->name }}</div>
            <div class="room-capacity">👥 Kapasitas: {{ $room->capacity }} orang</div>

            <div class="status-badge">
                @if($isCleaning)
                    🟡 SEDANG DIBERSIHKAN
                @else
                    {{ $isOccupied ? '🔴 SEDANG DIPAKAI' : '🟢 TERSEDIA' }}
                @endif
            </div>

            <div class="booking-info">
                @if($isCleaning)
                    <div class="info-row">
                        <span class="value" style="color: #d97706; font-weight: bold;">Sedang dibersihkan oleh petugas</span>
                    </div>
                @elseif($isOccupied)
                    @if($room->currentBooking)
                        <div class="info-row">
                            <span class="value" style="color: #dc2626; font-weight: bold;">Set manual oleh admin</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Peminjam</span>
                            <span class="value">{{ $room->currentBooking->renter_name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Keperluan</span>
                            <span class="value">{{ Str::limit($room->currentBooking->purpose, 35) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Selesai</span>
                            <span class="value">{{ substr($room->currentBooking->end_time, 0, 5) }} WIB</span>
                        </div>
                    @elseif($room->is_occupied && !$activeBooking)
                        <div class="info-row">
                            <span class="value" style="color: #dc2626;">Ditandai manual oleh admin</span>
                        </div>
                    @else
                        <div class="info-row">
                            <span class="label">Peminjam</span>
                            <span class="value">{{ $activeBooking->renter_name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Keperluan</span>
                            <span class="value">{{ Str::limit($activeBooking->purpose, 35) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Selesai</span>
                            <span class="value">{{ substr($activeBooking->end_time, 0, 5) }} WIB</span>
                        </div>
                    @endif
                @else
                    <div class="info-row">
                        <span>✓ Ruangan siap digunakan</span>
                    </div>
                @endif
            </div>

            @if($nextBooking)
            <div class="next-booking">
                <div class="next-label">⏰ Peminjaman Berikutnya</div>
                <div class="next-info">{{ substr($nextBooking->start_time,0,5) }} – {{ substr($nextBooking->end_time,0,5) }} &bull; {{ $nextBooking->renter_name }}</div>
            </div>
            @endif
        </div>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:4rem;color:#475569">
            <div style="font-size:4rem;margin-bottom:1rem">🏛️</div>
            <div style="font-size:1.5rem;font-weight:700">Belum ada ruangan terdaftar</div>
        </div>
        @endforelse
    </div>
</x-filament-panels::page>
