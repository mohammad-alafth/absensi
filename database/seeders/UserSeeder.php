<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Mapping jabatan -> role singkat
        |--------------------------------------------------------------------------
        */
        $roleMap = [
            'DIREKTUR' => 'director',
            'HRD' => 'hrd',
            'STAFF IT' => 'it',
            'SECURITY' => 'security',
            'PJ SECURITY' => 'pj_security',

            'STAFF PERAWAT' => 'nurse',
            'STAFF PERAWAT OK' => 'nurse_ok',
            'KOORDINATOR PERAWAT' => 'koor_nurse',

            'STAFF ADM' => 'admin',
            'PJ ADM' => 'pj_admin',

            'KABAG KEUANGAN' => 'finance_mgr',
            'STAFF KEUANGAN' => 'finance',

            'STAFF CS' => 'cs',
            'STAFF RO' => 'ro',

            'STAFF APOTEKER' => 'pharmacist',
            'PJ APOTEKER' => 'pj_pharmacist',

            'STAFF CASEMIX' => 'casemix',
            'PJ CASEMIX / K3' => 'pj_casemix',

            'STAFF IPSRS' => 'ipsrs',
            'PJ IPSRS' => 'pj_ipsrs',

            'STAFF PPP' => 'ppp',
            'STAFF GIZI' => 'nutrition',
            'STAFF MARKETING' => 'marketing',
            'PJ MARKETING' => 'pj_marketing',

            'YANMED' => 'medical_service',
        ];

        /*
        |--------------------------------------------------------------------------
        | Data Karyawan
        |--------------------------------------------------------------------------
        */
        $employees = [
            ['ADIB MUHAMMAD AMD', 'PJ IPSRS'],
            ['AGUSRIANSA S.KEP', 'STAFF PERAWAT OK'],
            ['AHMAD PIRDAUS S.KEP', 'STAFF PERAWAT OK'],
            ['AHMAD TOHAR S.KOM', 'STAFF IT'],
            ['ANDHIKA NANDA PRATAMA, S.KEP', 'STAFF PERAWAT'],
            ['ARIANTO', 'SECURITY'],
            ['ASWANDI SE', 'KABAG KEUANGAN'],
            ['BUDI PURNOMO', 'SECURITY'],
            ['CITRA ANGGRAINI SKM', 'PJ CASEMIX / K3'],
            ['DAHLIA AMD.KEP', 'KOORDINATOR PERAWAT'],
            ['DARSIH', 'PJ SECURITY'],
            ['DEBBY YULIASTRI AMD.KEP', 'STAFF PERAWAT OK'],
            ['DEFRIAN ANDESTA AMD.KEP', 'STAFF PERAWAT'],
            ['DEWI WILLIANTI AMF', 'STAFF APOTEKER'],
            ['DIANTI ANDITA CELFIZ, SKM', 'HRD'],
            ['EFRIANTO ANAS S.SOS', 'KABAG KEUANGAN'],
            ['EKA YANA HAPSARI S.A.B', 'PJ ADM'],
            ['ELSI MANDA UTARI S.KEP', 'STAFF PERAWAT'],
            ['FEBI ANDINI SE', 'STAFF KEUANGAN'],
            ['GUSTIA MARLIYUNA S.KEP', 'STAFF PERAWAT'],
            ['HENDRIAN APRILLEO VIEDRO', 'STAFF PERAWAT'],
            ['IRA MAYASARI AMD.PK', 'STAFF REKAM MEDIS'],
            ['LEFI YUNIARTI AMD.RO', 'PJ RO'],
            ['MUHAMMAD IQBAL RIZA A.MD.KES', 'STAFF ADM'],
            ['MUHAMMAD SHAHRUL FAZRI', 'STAFF SECURITY'],
            ['MUNZIR MUBARAK S.KEP', 'STAFF PERAWAT'],
            ['NIKO WAHYUDI S.I.KOM', 'STAFF ADM'],
            ['DR. NURFARHIN, MKM', 'DIREKTUR'],
            ['PUTRI MAHDAYENI', 'STAFF CS'],
            ['RACHMAT PARMAN AMD.RO', 'STAFF RO'],
            ['DR. RESKI DWI INDAH SARI', 'YANMED'],
            ['ROHANI AMD.KEP', 'STAFF PERAWAT'],
            ['RYO MEDIA MITRA', 'STAFF IPSRS'],
            ['SEPTIANTI PUTRI AMD', 'STAFF PPP'],
            ['SRI ASTUTI', 'STAFF CS'],
            ['SUCI PRIMA PUTRI S.FARM.APT', 'PJ APOTEKER'],
            ['THESSA KARTIKA PUTRI SE', 'STAFF MARKETING'],
            ['VINA HIKMAHARDIYA S.KEP', 'STAFF PERAWAT'],
            ['WAFA AMIZA SE', 'STAFF KEUANGAN'],
            ['WIDIYA NUSANTARI A.MD.GZ', 'STAFF GIZI'],
            ['YOGA ZIKRI GENALI MUNTHE S.KEP', 'STAFF PERAWAT'],
            ['YOLA ROSALIANA AMD.F', 'STAFF APOTEKER'],
            ['LINAWATI AMD', 'PJ MARKETING'],
            ['ADILLAH GUSMAN A.MD.KES', 'STAFF ADM'],
            ['MUHAMMAD HANIF IMADUDDIN EFHANDI S.E', 'STAFF KEUANGAN'],
            ['ANNISA RIZKY AULIA SH', 'STAFF KASIR'],
            ['NATASYA MULIA PUTRIADI A.MD.KES', 'STAFF RO'],
            ['YUTHI MOUDYLLA AMD.KEP', 'STAFF PERAWAT'],
            ['HURIYAH ISTY S.KEP', 'STAFF PERAWAT'],
            ['SONYA DESTARI AMD.KEP', 'STAFF PERAWAT'],
            ['SALSABILLA A.MD.RMIK', 'STAFF CASEMIX'],
            ['YULIA EKA PUTRI', 'STAFF CS'],
            ['MUHAMMAD GAZALI AKBAR A.MD.RO', 'STAFF RO'],
            ['HANIFAH RAHMANITA S.I.KOM', 'STAFF KONTEN KREATOR'],
            ['PANJI ALFARIDZI', 'STAFF SECURITY'],
            ['ISNAINI FALUPI A.MD.KES', 'STAFF RO'],
            ['CHYNTIA BELLA ANDRELVA S.FARM.APT', 'STAFF APOTEKER'],
            ['ANNISA AINI A.MD', 'STAFF KASIR'],
            ['WIDIAWATI', 'STAFF CS'],
            ['SISNO HARIATMAN', 'STAFF SECURITY'],
            ['M. ALLIF ALFATH', 'STAFF IT'],
        ];


        foreach ($employees as $index => $employee) {
            $name = $employee[0];
            $jabatan = strtoupper($employee[1]);

            User::create([
                'name' => $name,
                'email' => 'rsbt' . ($index + 1) . '@gmail.com',
                // contoh: rsbt1@gmail.com, rsbt2@gmail.com, dst
                // kalau email sama semua pasti gagal karena unique

                'email_verified_at' => Carbon::now(),

                'password' => '$2y$12$6k0AzmVCi9GtBentuOkh0eokWJsvdBQ.UP3vMjuv/3N7JoWfTMkTq',

                'role' => $roleMap[$jabatan] ?? 'staff',

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}