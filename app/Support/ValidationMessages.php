<?php

namespace App\Support;

final class ValidationMessages
{
	/**
	 * @return array<string, string>
	 */
	public static function identity(): array
	{
		return [
			'email.required' => 'Alamat email wajib diisi.',
			'email.email' => 'Format alamat email tidak valid.',
			'email.unique' => 'Alamat email sudah terdaftar. Gunakan email lain atau login jika sudah punya akun.',
			'email_personal.email' => 'Format email pribadi tidak valid.',
			'nik_ktp.required' => 'NIK KTP wajib diisi.',
			'nik_ktp.digits' => 'NIK KTP harus berisi 16 digit angka.',
			'nik_ktp.size' => 'NIK KTP harus berisi 16 digit angka.',
			'nik_ktp.unique' => 'NIK KTP sudah terdaftar. Periksa kembali input Anda.',
			'nrk_pegawai.required' => 'NRK pegawai wajib diisi.',
			'nrk_pegawai.unique' => 'NRK pegawai sudah terdaftar. Periksa kembali input Anda.',
		];
	}

	/**
	 * @return array<string, string>
	 */
	public static function participantForm(): array
	{
		return array_merge(self::identity(), [
			'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
			'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
			'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
			'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
			'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
			'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
			'skpd.required' => 'SKPD wajib diisi.',
			'ukpd.required' => 'UKPD wajib diisi.',
			'status_pegawai.required' => 'Status pegawai wajib dipilih.',
			'status_pegawai.in' => 'Status pegawai tidak valid.',
			'no_telp.required' => 'Nomor telepon wajib diisi.',
			'pendidikan_terakhir.required' => 'Pendidikan terakhir wajib dipilih.',
			'pendidikan_terakhir.in' => 'Pendidikan terakhir tidak valid.',
		]);
	}

	/**
	 * Pesan validasi pembuatan akun admin (CLI / UserController).
	 *
	 * @return array<string, string>
	 */
	public static function adminUser(): array
	{
		return array_merge(self::identity(), [
			'name.required' => 'Nama lengkap wajib diisi.',
			'password.required' => 'Kata sandi wajib diisi.',
			'password.min' => 'Kata sandi minimal 8 karakter.',
			'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
			'role.required' => 'Role wajib dipilih.',
			'role.in' => 'Role tidak valid. Pilih super_admin, admin, atau pimpinan.',
		]);
	}

	/**
	{
		return array_merge(self::identity(), [
			'name.required' => 'Nama lengkap wajib diisi.',
			'password.required' => 'Kata sandi wajib diisi.',
			'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
			'status_pegawai.required' => 'Status pegawai wajib dipilih.',
			'pendidikan_terakhir.required' => 'Pendidikan terakhir wajib dipilih.',
		]);
	}

	/**
	 * @return array<string, string>
	 */
	public static function passwordActivation(): array
	{
		return [
			'password.required' => 'Kata sandi wajib diisi.',
			'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
			'password.min' => 'Kata sandi minimal 8 karakter.',
		];
	}

	/**
	 * @param  array<string, string>  ...$sets
	 * @return array<string, string>
	 */
	public static function merge(array ...$sets): array
	{
		return array_merge(...$sets);
	}
}
