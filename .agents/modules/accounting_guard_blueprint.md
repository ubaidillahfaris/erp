# Blueprint: Accounting Immutability (Ledger)

## Overview
Implementasi prinsip "Insert-Only" pada buku besar (Ledger). Semua entri jurnal yang sudah masuk ke sistem tidak boleh diubah atau dihapus untuk menjamin integritas laporan keuangan.

## Implementasi Teknis
1. **Model Guard:** Menggunakan Eloquent `booted` method pada `JournalEntry` dan `JournalItem` untuk mencegat event `updating` dan `deleting`.
2. **Error Handling:** Melempar `RuntimeException` jika ada percobaan modifikasi. Hal ini memastikan tidak ada celah di level ORM (selain raw SQL).

## Keuntungan Bisnis
- **Integritas Audit:** Akuntan dan Auditor dapat menjamin bahwa data yang mereka lihat adalah data asli.
- **Kepatuhan:** Sesuai dengan standar akuntansi di mana kesalahan tidak diperbaiki dengan mengedit, melainkan dengan jurnal koreksi (Storno).

## Verifikasi
Telah diuji dengan `tests/Feature/Sprint1/JournalImmutabilityTest.php`.
