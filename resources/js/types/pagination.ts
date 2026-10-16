/**
 * Bentuk JSON dari koleksi API Resource Laravel yang dipaginasi.
 */
export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
        path: string;
    };
};

export type Option<T extends string = string> = {
    value: T;
    label: string;
};
