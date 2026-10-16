/**
 * Pencarian & filter tabel yang dikirim server (App\Support\TableQuery::toArray).
 */
export type TableQueryState = {
    search: string | null;
    filters: Record<string, string[]>;
    /** Hanya ada pada tabel yang memakai filter periode (Y-m-d). */
    period?: { from: string | null; to: string | null };
};
