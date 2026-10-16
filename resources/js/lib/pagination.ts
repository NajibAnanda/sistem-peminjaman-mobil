/** Jumlah maksimal tombol nomor halaman yang ditampilkan sekaligus. */
export const MAX_PAGE_BUTTONS = 5;

/**
 * Nomor halaman yang ditampilkan: jendela bergeser dengan halaman aktif di
 * tengah bila memungkinkan. Contoh total 12 halaman: halaman 1 → 1–5,
 * halaman 6 → 4–8, halaman 12 → 8–12.
 */
export function pageWindow(
    current: number,
    last: number,
    size: number = MAX_PAGE_BUTTONS,
): number[] {
    const lastPage = Math.max(1, last);
    const half = Math.floor(size / 2);

    let start = Math.max(1, current - half);
    const end = Math.min(lastPage, start + size - 1);
    start = Math.max(1, end - size + 1);

    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
}

/**
 * Nomor urut baris yang berlanjut antar halaman:
 * (halaman - 1) × per_halaman + indeks + 1.
 */
export function rowNumber(
    meta: { current_page: number; per_page: number },
    index: number,
): number {
    return (meta.current_page - 1) * meta.per_page + index + 1;
}
