export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppShellVariant = 'header' | 'sidebar';

export interface TableColumn {
    readonly key: string;
    readonly label: string;
    readonly align?: 'left' | 'center' | 'right';
    readonly width?: string;
    readonly class?: string;
    readonly sortable?: boolean;
    readonly sortKey?: string;
}
