<x-filament-panels::page>
    <style>
        .dashboard-container {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --accent: #f59e0b;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --success: #16a34a;
            --danger: #dc2626;
            --radius: 12px;
            --shadow: 0 4px 24px rgba(0,0,0,0.08);
        }

        .dashboard-container .rooms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .dashboard-container .room-card { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid var(--border); }
        .dashboard-container .room-card:hover { transform: translateY(-4px); box-shadow: 0 8px 32px rgba(0,0,0,0.12); }
        .dashboard-container .room-card img { width: 100%; height: 200px; object-fit: cover; }
        .dashboard-container .room-card-placeholder { width: 100%; height: 200px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); display: flex; align-items: center; justify-content: center; font-size: 3rem; }
        .dashboard-container .room-card-body { padding: 1.25rem; }
        .dashboard-container .room-card-body h3 { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.25rem; color: var(--text); }
        .dashboard-container .room-card-body .capacity { display: inline-block; background: #eff6ff; color: var(--primary); font-size: 0.8rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 20px; margin-bottom: 0.5rem; }
        .dashboard-container .room-card-body p { color: var(--muted); font-size: 0.9rem; }

        .dashboard-container .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.78rem; font-weight: 700; }
        .dashboard-container .badge-approved { background: #dcfce7; color: #15803d; }

        .dashboard-container .schedule-table { width: 100%; border-collapse: collapse; background: var(--card); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); margin-bottom: 3rem; border: 1px solid var(--border); }
        .dashboard-container .schedule-table th { background: var(--primary); color: white; padding: 0.85rem 1rem; text-align: left; font-size: 0.9rem; }
        .dashboard-container .schedule-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; color: var(--text); }
        .dashboard-container .schedule-table tr:last-child td { border-bottom: none; }
        .dashboard-container .schedule-table tr:hover td { background: #f8fafc; }

        .dashboard-container .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 3rem;
            border: 1px solid var(--border);
        }
        .dashboard-container .table-responsive .schedule-table {
            margin-bottom: 0;
            box-shadow: none;
            border-radius: 0;
            border: none;
        }

        .dashboard-container .section-title {
            font-size: 1.6rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--text);
            display: flex; align-items: center; gap: 0.5rem;
        }
        .dashboard-container .section-title::after { content: ''; flex: 1; height: 2px; background: var(--border); margin-left: 0.5rem; }

        .dashboard-container .form-card { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 2rem; margin-bottom: 3rem; border: 1px solid var(--border); }
        .dashboard-container .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 640px) { .dashboard-container .form-grid { grid-template-columns: 1fr; } }
        .dashboard-container .form-group { display: flex; flex-direction: column; gap: 0.4rem; }
        .dashboard-container .form-group.full { grid-column: 1 / -1; }
        .dashboard-container label { font-weight: 600; font-size: 0.9rem; color: var(--text); }
        .dashboard-container input, .dashboard-container select, .dashboard-container textarea { border: 1.5px solid var(--border); border-radius: 8px; padding: 0.6rem 0.9rem; font-size: 0.95rem; font-family: inherit; transition: border-color 0.2s; background: white; color: var(--text); }
        .dashboard-container input:focus, .dashboard-container select:focus, .dashboard-container textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .dashboard-container textarea { resize: vertical; min-height: 90px; }
        .dashboard-container .btn { display: inline-block; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; border: none; transition: background 0.2s, transform 0.1s; }
        .dashboard-container .btn:active { transform: scale(0.98); }
        .dashboard-container .btn-primary { background: var(--primary); color: white; }
        .dashboard-container .btn-primary:hover { background: var(--primary-dark); }
        .dashboard-container .btn-warning { background: var(--accent); color: white; }
        .dashboard-container .btn-warning:hover { background: #d97706; }

        @media (max-width: 768px) {
            .dashboard-container .section-title { font-size: 1.3rem; }
            .dashboard-container .rooms-grid { grid-template-columns: 1fr; gap: 1rem; }
            .dashboard-container .form-card { padding: 1.25rem; }
            .dashboard-container .table-responsive .schedule-table { min-width: 650px; }
        }
    </style>

    <div class="dashboard-container">
        <section id="ruangan">
            <h2 class="section-title">Daftar Ruangan</h2>
            @if($rooms->isEmpty())
                <p style="color:var(--muted);margin-bottom:2rem">Belum ada ruangan yang terdaftar.</p>
            @else
            <div class="rooms-grid">
                @foreach($rooms as $room)
                <div class="room-card">
                    @if($room->image_path)
                        <img src="{{ asset('storage/' . $room->image_path) }}" alt="{{ $room->name }}">
                    @else
                        <div class="room-card-placeholder">
                            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m-1.5 3h1.5m4.5-12h1.5m-1.5 3h1.5m-1.5 3h1.5m-1.5 3h1.5"></path></svg>
                        </div>
                    @endif
                    <div class="room-card-body">
                        <h3>{{ $room->name }}</h3>
                        <span class="capacity">Kapasitas: {{ $room->capacity }} orang</span>
                        @php
                            $nowTime = now()->format('H:i:s');
                            $hasActiveBooking = $room->current_booking_id || $room->bookings()
                                ->where('date', today())
                                ->where('status', 'approved')
                                ->where('start_time', '<=', $nowTime)
                                ->where('end_time', '>', $nowTime)
                                ->exists();
                            $isOccupied = $room->is_occupied || $hasActiveBooking;
                            $isCleaning = $room->is_cleaning;
                        @endphp
                        @if($isCleaning)
                            <span class="badge" style="background: #fef3c7; color: #d97706; border: 1px solid #fcd34d; margin-bottom: 0.5rem;">Sedang Dibersihkan</span>
                        @elseif($isOccupied)
                            <span class="badge" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; margin-bottom: 0.5rem;">Sedang Digunakan</span>
                        @else
                            <span class="badge" style="background: #dcfce7; color: #15803d; border: 1px solid #86efac; margin-bottom: 0.5rem;">Tersedia</span>
                        @endif
                        @if($room->description)
                            <p>{{ $room->description }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </section>

        <section id="jadwal">
            <h2 class="section-title">Jadwal Peminjaman Disetujui</h2>
            @if($approvedBookings->isEmpty())
                <p style="color:var(--muted);margin-bottom:2rem">Belum ada jadwal peminjaman yang disetujui.</p>
            @else
            <div class="table-responsive">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Ruangan</th><th>Tanggal</th><th>Jam</th><th>Peminjam</th><th>Keperluan</th><th>Catatan Admin</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($approvedBookings as $b)
                        <tr>
                            <td><strong>{{ $b->room->name }}</strong></td>
                            <td>{{ $b->date->format('d M Y') }}</td>
                            <td>{{ substr($b->start_time, 0, 5) }} – {{ substr($b->end_time, 0, 5) }}</td>
                            <td>{{ $b->renter_name }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($b->purpose, 40) }}</td>
                            <td style="white-space: pre-wrap;">{{ $b->admin_note ?? '-' }}</td>
                            <td>
                                <span class="badge badge-approved">✓ Disetujui</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </section>

        <section id="daftar-keluhan">
            <h2 class="section-title">📋 Riwayat Keluhan Saya</h2>
            @if($myComplaints->isEmpty())
                <p style="color:var(--muted);margin-bottom:2rem">Anda belum pernah mengirimkan keluhan.</p>
            @else
            <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 3rem;">
                @foreach($myComplaints as $complaint)
                    <div style="background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 1.5rem; border: 1px solid var(--border); display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                            <div>
                                <span style="font-size: 0.85rem; color: var(--muted);">Dikirim pada: {{ $complaint->created_at->format('d M Y H:i') }} WIB</span>
                            </div>
                            <div>
                                @if($complaint->status === 'resolved')
                                    <span class="badge" style="background: #dcfce7; color: #15803d; border: 1px solid #86efac;">🟢 Selesai</span>
                                @else
                                    <span class="badge" style="background: #fef3c7; color: #d97706; border: 1px solid #fcd34d;">🟡 Pending</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <p style="font-weight: 600; margin-bottom: 0.5rem; color: var(--text);">Isi Keluhan:</p>
                            <p style="color: var(--text); white-space: pre-line;">{{ $complaint->complaint_text }}</p>
                            @if($complaint->photo_path)
                                <div style="margin-top: 0.75rem;">
                                    <a href="{{ asset('storage/' . $complaint->photo_path) }}" target="_blank" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; color: var(--primary); text-decoration: none; font-weight: 600;">
                                        🖼️ Lihat Foto Lampiran
                                    </a>
                                </div>
                            @endif
                        </div>
                        @if($complaint->admin_response)
                            <div style="background: #f8fafc; border-left: 4px solid var(--primary); padding: 1rem; border-radius: 8px; margin-top: 0.5rem;">
                                <p style="font-weight: 700; color: var(--primary); margin-bottom: 0.25rem; font-size: 0.9rem;">Tindak Lanjut Admin:</p>
                                <p style="color: var(--text); font-size: 0.9rem; white-space: pre-line;">{{ $complaint->admin_response }}</p>
                                <div style="font-size: 0.75rem; color: var(--muted); display: flex; flex-direction: column; gap: 0.15rem; margin-top: 0.5rem;">
                                    @if($complaint->resolved_at)
                                        <span>📅 Tanggal & Waktu: {{ $complaint->resolved_at->format('d M Y H:i') }} WIB</span>
                                    @endif
                                    @if($complaint->resolver)
                                        <span>👤 Ditanggapi oleh: {{ $complaint->resolver->name }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif
        </section>

        <section id="keluhan">
            <h2 class="section-title">📣 Kirim Keluhan</h2>
            <div class="form-card">
                @if(session('complaint_success'))
                    <div class="alert alert-success" style="padding: 1rem 1.25rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-weight: 500; background: #dcfce7; color: #15803d; border-left: 4px solid #16a34a;">✅ {{ session('complaint_success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger" style="padding: 1rem 1.25rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-weight: 500; background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444;">
                        <ul style="margin-left: 1.25rem; list-style-type: disc;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('complaint') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="c_name">Nama</label>
                            <input type="text" name="name" id="c_name" value="{{ auth()->user()->name }}" placeholder="Nama Anda" required>
                        </div>
                        <div class="form-group">
                            <label for="c_contact">No. HP</label>
                            <input type="tel" name="email_or_phone" id="c_contact" value="{{ old('email_or_phone') }}" placeholder="No. HP Anda (contoh: 08123456789)" pattern="^(08|\+62|62)[0-9]{7,13}$" title="Format nomor HP tidak valid. Gunakan format seperti 08123456789 atau +628123456789." required>
                        </div>
                        <div class="form-group full">
                            <label for="c_text">Isi Keluhan</label>
                            <textarea name="complaint_text" id="c_text" placeholder="Tuliskan keluhan atau masukan Anda..." required></textarea>
                        </div>
                        <div class="form-group full">
                            <label for="c_photo">Foto Keluhan (Opsional)</label>
                            <input type="file" name="photo" id="c_photo" accept="image/*" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px;">
                        </div>
                    </div>
                    <div style="margin-top:1.25rem">
                        <button type="submit" class="btn btn-warning">📤 Kirim Keluhan</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-filament-panels::page>
