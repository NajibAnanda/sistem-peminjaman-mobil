/**
 * Pencarian & filter tabel yang dikirim server (App\Support\TableQuery::toArray).
 */
export type TableQueryState = {
    search: string | null;
    filters: Record<string, string[]>;
};
