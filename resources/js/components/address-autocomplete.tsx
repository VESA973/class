import { useEffect, useId, useRef, useState, type ComponentProps } from 'react';
import { Loader2, MapPin } from 'lucide-react';
import { cn } from 'cn';

import { Input } from '@/components/ui/input';

/**
 * Champ d'adresse avec suggestions de l'API Adresse de l'IGN (Geoplateforme) :
 * gratuite, sans cle, adresses francaises + lieux (gares, aeroports...).
 * La saisie libre reste possible si l'API ne repond pas.
 */
const ENDPOINT = 'https://data.geopf.fr/geocodage/completion/';

type Props = Omit<ComponentProps<typeof Input>, 'onChange' | 'value'> & {
    value: string;
    onChange: (value: string) => void;
    listClassName?: string;
};

export function AddressAutocomplete({ value, onChange, className, listClassName, onBlur, ...props }: Props) {
    const listId = useId();
    const [suggestions, setSuggestions] = useState<string[]>([]);
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(-1);
    const [loading, setLoading] = useState(false);
    const skipNextFetch = useRef(false);

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        const query = value.trim();

        if (query.length < 3) {
            setSuggestions([]);
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            setLoading(true);
            const params = new URLSearchParams({ text: query, maximumResponses: '6' });

            fetch(`${ENDPOINT}?${params.toString()}`, { signal: controller.signal })
                .then((response) => (response.ok ? response.json() : Promise.reject(new Error(`HTTP ${response.status}`))))
                .then((data: { results?: { fulltext: string }[] }) => {
                    const unique = Array.from(new Set((data.results ?? []).map((result) => result.fulltext)));
                    setSuggestions(unique);
                    setActive(-1);
                    setOpen(unique.length > 0);
                })
                .catch(() => setSuggestions([]))
                .finally(() => setLoading(false));
        }, 250);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [value]);

    function choose(suggestion: string) {
        skipNextFetch.current = true;
        onChange(suggestion);
        setOpen(false);
        setSuggestions([]);
    }

    return (
        <div className="relative">
            <MapPin aria-hidden className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 opacity-70" />
            <Input
                {...props}
                value={value}
                role="combobox"
                aria-autocomplete="list"
                aria-expanded={open}
                aria-controls={listId}
                aria-activedescendant={open && active >= 0 ? `${listId}-${active}` : undefined}
                autoComplete="off"
                className={cn('pl-9 pr-9', className)}
                onChange={(event) => onChange(event.target.value)}
                onFocus={() => setOpen(suggestions.length > 0)}
                onBlur={(event) => {
                    // Laisse le temps au clic sur une suggestion d'etre pris en compte.
                    window.setTimeout(() => setOpen(false), 120);
                    onBlur?.(event);
                }}
                onKeyDown={(event) => {
                    if (!open || suggestions.length === 0) {
                        return;
                    }

                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        const step = event.key === 'ArrowDown' ? 1 : -1;
                        setActive((index) => (index + step + suggestions.length) % suggestions.length);
                    } else if (event.key === 'Enter' && active >= 0) {
                        event.preventDefault();
                        choose(suggestions[active]);
                    } else if (event.key === 'Escape') {
                        setOpen(false);
                    }
                }}
            />
            {loading && <Loader2 aria-hidden className="absolute right-3 top-1/2 size-4 -translate-y-1/2 animate-spin opacity-70" />}

            {open && (
                <ul
                    id={listId}
                    role="listbox"
                    className={cn(
                        'absolute inset-x-0 top-full z-50 mt-1 max-h-64 overflow-auto rounded-md border border-border bg-popover p-1 text-sm text-popover-foreground shadow-lg',
                        listClassName,
                    )}
                >
                    {suggestions.map((suggestion, index) => (
                        <li
                            key={suggestion}
                            id={`${listId}-${index}`}
                            role="option"
                            aria-selected={index === active}
                            className={cn(
                                'flex cursor-pointer items-start gap-2 rounded-sm px-2 py-2',
                                index === active ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/60',
                            )}
                            onMouseDown={(event) => {
                                event.preventDefault();
                                choose(suggestion);
                            }}
                            onMouseEnter={() => setActive(index)}
                        >
                            <MapPin aria-hidden className="mt-0.5 size-3.5 shrink-0 opacity-60" />
                            {suggestion}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
