<?php

return [
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'email' => 'Format email tidak valid.',
    'unique' => ':attribute sudah digunakan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'boolean' => 'Pilihan :attribute tidak valid.',
    'url' => ':attribute harus berupa URL HTTPS yang valid.',
    'regex' => 'Format :attribute tidak valid.',
    'digits' => ':attribute harus terdiri dari :digits angka.',
    'in' => 'Pilihan :attribute tidak didukung.',
    'min' => ['numeric' => ':attribute minimal :min.', 'string' => ':attribute minimal :min karakter.'],
    'max' => ['numeric' => ':attribute maksimal :max.', 'string' => ':attribute maksimal :max karakter.'],
    'size' => ['string' => ':attribute harus terdiri dari :size karakter.'],
    'attributes' => ['name' => 'Nama guru', 'email' => 'Email', 'password' => 'Kata sandi', 'school_name' => 'Nama sekolah / instansi', 'pin' => 'PIN pengawas', 'title' => 'Judul ujian', 'url' => 'Alamat ujian', 'duration_minutes' => 'Durasi ujian', 'valid_hours' => 'Masa berlaku kode', 'code' => 'Kode ujian', 'full_name' => 'Nama lengkap', 'participant_number' => 'Nomor peserta', 'require_managed_lock' => 'Kebijakan penguncian'],
];
