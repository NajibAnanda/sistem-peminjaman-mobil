import { ChevronsUpDown } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

export type ComboboxOption = {
    value: number;
    label: string;
    description?: string;
    /** Teks tambahan yang ikut dicari (mis. NIP, divisi). */
    keywords?: string;
    /** Penanda di kanan item, mis. alasan mobil tidak tersedia. */
    hint?: ReactNode;
    disabled?: boolean;
};

type Props = {
    id: string;
    options: ComboboxOption[];
    value: number | null;
    onChange: (value: number) => void;
    placeholder: string;
    searchPlaceholder: string;
    emptyText: string;
    invalid?: boolean;
    disabled?: boolean;
};

/**
 * Kotak pilihan yang dapat dicari (shadcn command + popover), dipakai untuk
 * memilih karyawan dan mobil (prd.md 11.5).
 */
export function SearchableCombobox({
    id,
    options,
    value,
    onChange,
    placeholder,
    searchPlaceholder,
    emptyText,
    invalid = false,
    disabled = false,
}: Props) {
    const [open, setOpen] = useState(false);
    const selected = options.find((option) => option.value === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    aria-invalid={invalid || undefined}
                    disabled={disabled}
                    className="h-auto min-h-9 w-full justify-between py-1.5 font-normal"
                >
                    {selected ? (
                        <span className="flex min-w-0 flex-col items-start text-left">
                            <span className="truncate">{selected.label}</span>
                            {selected.description && (
                                <span className="truncate text-xs text-muted-foreground">
                                    {selected.description}
                                </span>
                            )}
                        </span>
                    ) : (
                        <span className="text-muted-foreground">
                            {placeholder}
                        </span>
                    )}
                    <ChevronsUpDown
                        className="text-muted-foreground"
                        aria-hidden="true"
                    />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-(--radix-popover-trigger-width) min-w-72 p-0"
                align="start"
            >
                <Command>
                    <CommandInput placeholder={searchPlaceholder} />
                    <CommandList>
                        <CommandEmpty>{emptyText}</CommandEmpty>
                        <CommandGroup>
                            {options.map((option) => (
                                <CommandItem
                                    key={option.value}
                                    // cmdk menyaring berdasarkan value; id disertakan
                                    // agar unik meskipun ada nama yang sama.
                                    value={`${option.label} ${option.keywords ?? ''} #${option.value}`}
                                    disabled={option.disabled}
                                    data-checked={option.value === value}
                                    onSelect={() => {
                                        onChange(option.value);
                                        setOpen(false);
                                    }}
                                >
                                    <span className="flex min-w-0 flex-1 flex-col">
                                        <span className="truncate">
                                            {option.label}
                                        </span>
                                        {option.description && (
                                            <span className="truncate text-xs text-muted-foreground">
                                                {option.description}
                                            </span>
                                        )}
                                    </span>
                                    {option.hint && (
                                        <span className="shrink-0 text-xs">
                                            {option.hint}
                                        </span>
                                    )}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
