/**
 * Sinkron dengan App\Support\Search\GlobalSearch::run().
 */
export type GlobalSearchItem = {
    id: number;
    title: string;
    subtitle: string;
    url: string;
};

export type GlobalSearchGroup = {
    key: string;
    label: string;
    items: GlobalSearchItem[];
};
