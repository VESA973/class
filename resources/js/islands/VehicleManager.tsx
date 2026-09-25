import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { toast } from 'sonner';
import {
    AlertTriangle,
    ArrowLeft,
    Box,
    CalendarDays,
    CarFront,
    ExternalLink,
    ImageUp,
    Loader2,
    Plus,
    RefreshCw,
    Rotate3d,
    Save,
    Search,
    Trash2,
    Undo2,
} from 'lucide-react';
import { cn } from 'cn';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { formatPrice } from '@/lib/booking';

type Vehicle = {
    id: number;
    name: string;
    slug: string | null;
    category: string;
    horsepower: number | null;
    fuel_type: string;
    transmission: string;
    seats: number | null;
    daily_price: number;
    image_url: string | null;
    has_uploaded_image: boolean;
    display_image: string;
    model_url: string | null;
    video_url: string | null;
    description: string | null;
    is_available: boolean;
    with_chauffeur: boolean;
    reserved_now: boolean;
    upcoming_count: number;
    public_url: string | null;
    updated_at: string | null;
};

type Props = {
    csrfToken: string;
    initialVehicleId: number | null;
    urls: { data: string; store: string; item: string; planning: string };
};

type Selection = number | 'new' | null;

const SORTS = {
    name: { label: 'Nom (A → Z)', compare: (a: Vehicle, b: Vehicle) => a.name.localeCompare(b.name, 'fr') },
    price_asc: { label: 'Prix croissant', compare: (a: Vehicle, b: Vehicle) => a.daily_price - b.daily_price },
    price_desc: { label: 'Prix décroissant', compare: (a: Vehicle, b: Vehicle) => b.daily_price - a.daily_price },
    category: {
        label: 'Catégorie',
        compare: (a: Vehicle, b: Vehicle) => a.category.localeCompare(b.category, 'fr') || a.name.localeCompare(b.name, 'fr'),
    },
    recent: { label: 'Modifiés récemment', compare: (a: Vehicle, b: Vehicle) => (b.updated_at ?? '').localeCompare(a.updated_at ?? '') },
} as const;

type SortKey = keyof typeof SORTS;

const optionalInt = (min: number, max: number, message: string) =>
    z.string().trim().refine((value) => value === '' || (/^\d+$/.test(value) && +value >= min && +value <= max), message);
const optionalUrl = z.string().trim().max(500, 'Adresse trop longue.').refine((value) => value === '' || /^https?:\/\/\S+$/i.test(value), 'Adresse web invalide (https://…).');

const schema = z.object({
    name: z.string().trim().min(1, 'Indiquez le nom du modèle.').max(140, 'Nom trop long.'),
    category: z.string().trim().min(1, 'Indiquez une catégorie.').max(80, 'Catégorie trop longue.'),
    daily_price: z.string().trim().refine((value) => /^\d+$/.test(value) && +value >= 1 && +value <= 100000, 'Prix entre 1 et 100 000 €.'),
    seats: optionalInt(1, 9, 'Entre 1 et 9 places.'),
    horsepower: optionalInt(1, 3000, 'Puissance entre 1 et 3 000 ch.'),
    fuel_type: z.string().trim().min(1, 'Indiquez le carburant.').max(60, 'Trop long.'),
    transmission: z.string().trim().min(1, 'Indiquez la transmission.').max(60, 'Trop long.'),
    image_url: optionalUrl,
    video_url: optionalUrl,
    description: z.string().max(2000, '2 000 caractères maximum.'),
    is_available: z.boolean(),
    with_chauffeur: z.boolean(),
    remove_model: z.boolean(),
});

type Values = z.infer<typeof schema>;

function toValues(vehicle: Vehicle | null): Values {
    return {
        name: vehicle?.name ?? '',
        category: vehicle?.category ?? '',
        daily_price: vehicle ? String(vehicle.daily_price) : '',
        seats: vehicle?.seats ? String(vehicle.seats) : '',
        horsepower: vehicle?.horsepower ? String(vehicle.horsepower) : '',
        fuel_type: vehicle?.fuel_type ?? 'Essence',
        transmission: vehicle?.transmission ?? 'Auto',
        image_url: vehicle?.image_url ?? '',
        video_url: vehicle?.video_url ?? '',
        description: vehicle?.description ?? '',
        is_available: vehicle?.is_available ?? true,
        with_chauffeur: vehicle?.with_chauffeur ?? false,
        remove_model: false,
    };
}

function useIsDesktop(): boolean {
    const query = '(min-width: 1024px)';
    const [matches, setMatches] = useState(() => window.matchMedia(query).matches);

    useEffect(() => {
        const media = window.matchMedia(query);
        const onChange = () => setMatches(media.matches);
        media.addEventListener('change', onChange);

        return () => media.removeEventListener('change', onChange);
    }, []);

    return matches;
}

export default function VehicleManager({ csrfToken, initialVehicleId, urls }: Props) {
    const [vehicles, setVehicles] = useState<Vehicle[]>([]);
    const [categories, setCategories] = useState<string[]>([]);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');
    const [selected, setSelected] = useState<Selection>(initialVehicleId);
    const [dirty, setDirty] = useState(false);
    const [pending, setPending] = useState<Selection | undefined>(undefined);
    const isDesktop = useIsDesktop();

    const [search, setSearch] = useState('');
    const [category, setCategory] = useState('all');
    const [visibility, setVisibility] = useState('all');
    const [availability, setAvailability] = useState('all');
    const [sort, setSort] = useState<SortKey>('name');

    const load = useCallback(async () => {
        setStatus('loading');

        try {
            const response = await fetch(urls.data, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data: { vehicles: Vehicle[]; categories: string[] } = await response.json();
            setVehicles(data.vehicles);
            setCategories(data.categories);
            setStatus('ready');
        } catch {
            setStatus('error');
        }
    }, [urls.data]);

    useEffect(() => {
        void load();
    }, [load]);

    // Sur grand ecran, un vehicule est toujours affiche a droite.
    useEffect(() => {
        if (status === 'ready' && isDesktop && selected === null && vehicles.length > 0) {
            setSelected(vehicles.slice().sort(SORTS.name.compare)[0].id);
        }
    }, [status, isDesktop, selected, vehicles]);

    // Selection dans l'URL (?vehicle=12) : un rechargement garde la fiche ouverte.
    useEffect(() => {
        const url = new URL(window.location.href);
        if (selected === null) {
            url.searchParams.delete('vehicle');
        } else {
            url.searchParams.set('vehicle', String(selected));
        }
        window.history.replaceState(null, '', url);
    }, [selected]);

    const filtered = useMemo(() => {
        const query = search.trim().toLocaleLowerCase('fr');

        return vehicles
            .filter((vehicle) => !query || `${vehicle.name} ${vehicle.category} ${vehicle.fuel_type}`.toLocaleLowerCase('fr').includes(query))
            .filter((vehicle) => category === 'all' || vehicle.category === category)
            .filter((vehicle) => visibility === 'all' || (visibility === 'visible') === vehicle.is_available)
            .filter((vehicle) => availability === 'all' || (availability === 'rented') === vehicle.reserved_now)
            .sort(SORTS[sort].compare);
    }, [vehicles, search, category, visibility, availability, sort]);

    const current = typeof selected === 'number' ? (vehicles.find((vehicle) => vehicle.id === selected) ?? null) : null;

    function select(next: Selection) {
        if (next === selected) {
            return;
        }

        if (dirty) {
            setPending(next);
            return;
        }

        setSelected(next);
    }

    function onSaved(vehicle: Vehicle, created: boolean) {
        setVehicles((list) => (created ? [...list, vehicle] : list.map((item) => (item.id === vehicle.id ? vehicle : item))));
        setCategories((list) => (list.includes(vehicle.category) ? list : [...list, vehicle.category].sort()));
        setDirty(false);

        if (created) {
            setSelected(vehicle.id);
        }
    }

    function onDeleted(id: number) {
        const remaining = vehicles.filter((vehicle) => vehicle.id !== id);
        setVehicles(remaining);
        setDirty(false);
        setSelected(isDesktop && remaining.length > 0 ? remaining.slice().sort(SORTS.name.compare)[0].id : null);
    }

    const showList = isDesktop || selected === null;
    const showEditor = isDesktop || selected !== null;

    return (
        <div className="grid gap-4 text-sm lg:h-[calc(100vh-170px)] lg:min-h-[560px] lg:grid-cols-[380px_minmax(0,1fr)]">
            {showList && (
                <section aria-label="Liste des véhicules" className="flex min-h-0 flex-col overflow-hidden rounded-2xl border border-border bg-card">
                    <div className="grid gap-3 border-b border-border p-4">
                        <div className="flex gap-2">
                            <div className="relative flex-1">
                                <Search aria-hidden className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="search"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Rechercher un véhicule…"
                                    aria-label="Rechercher un véhicule"
                                    className="pl-9"
                                />
                            </div>
                            <Button type="button" onClick={() => select('new')} aria-label="Ajouter un véhicule">
                                <Plus aria-hidden /> <span className="hidden sm:inline">Nouveau</span>
                            </Button>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <FilterSelect label="Filtrer par catégorie" value={category} onChange={setCategory} options={[['all', 'Toutes catégories'], ...categories.map((item) => [item, item] as [string, string])]} />
                            <FilterSelect label="Filtrer par statut sur le site" value={visibility} onChange={setVisibility} options={[['all', 'Tous statuts'], ['visible', 'Visibles'], ['hidden', 'Masqués']]} />
                            <FilterSelect label="Filtrer par disponibilité" value={availability} onChange={setAvailability} options={[['all', 'Toute disponibilité'], ['free', 'Libres maintenant'], ['rented', 'En location']]} />
                            <FilterSelect label="Trier par" value={sort} onChange={(value) => setSort(value as SortKey)} options={Object.entries(SORTS).map(([key, item]) => [key, item.label] as [string, string])} />
                        </div>
                        <p className="text-xs text-muted-foreground" aria-live="polite">
                            {status === 'ready' ? `${filtered.length} véhicule(s) sur ${vehicles.length}` : ' '}
                        </p>
                    </div>

                    <div className="min-h-0 flex-1 overflow-y-auto p-2">
                        {status === 'loading' && <ListSkeleton />}
                        {status === 'error' && (
                            <div role="alert" className="grid justify-items-center gap-3 p-8 text-center">
                                <AlertTriangle aria-hidden className="size-6 text-destructive" />
                                <p>Impossible de charger les véhicules.</p>
                                <Button type="button" size="sm" variant="outline" onClick={() => void load()}>
                                    <RefreshCw aria-hidden /> Réessayer
                                </Button>
                            </div>
                        )}
                        {status === 'ready' && filtered.length === 0 && (
                            <p className="p-8 text-center text-muted-foreground">
                                {vehicles.length === 0 ? 'Aucun véhicule pour le moment. Cliquez sur « Nouveau ».' : 'Aucun véhicule ne correspond à ces critères.'}
                            </p>
                        )}
                        {status === 'ready' && (
                            <ul className="grid gap-1">
                                {filtered.map((vehicle) => (
                                    <li key={vehicle.id}>
                                        <VehicleRow vehicle={vehicle} active={vehicle.id === selected} onSelect={() => select(vehicle.id)} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>
            )}

            {showEditor && (
                <section aria-label="Fiche du véhicule" className="flex min-h-0 flex-col overflow-hidden rounded-2xl border border-border bg-card">
                    {!isDesktop && (
                        <div className="border-b border-border p-3">
                            <Button type="button" variant="ghost" size="sm" onClick={() => select(null)}>
                                <ArrowLeft aria-hidden /> Retour à la liste
                            </Button>
                        </div>
                    )}
                    {status === 'loading' && <EditorSkeleton />}
                    {status === 'ready' && selected !== null && (selected === 'new' || current) && (
                        <VehicleEditor
                            key={String(selected)}
                            vehicle={current}
                            categories={categories}
                            csrfToken={csrfToken}
                            urls={urls}
                            onDirtyChange={setDirty}
                            onSaved={onSaved}
                            onDeleted={onDeleted}
                        />
                    )}
                    {status === 'ready' && typeof selected === 'number' && !current && (
                        <p className="p-10 text-center text-muted-foreground">Ce véhicule n’existe plus. Choisissez-en un dans la liste.</p>
                    )}
                    {status === 'ready' && selected === null && vehicles.length === 0 && (
                        <div className="grid flex-1 place-items-center p-10 text-center text-muted-foreground">
                            <div className="grid justify-items-center gap-3">
                                <CarFront aria-hidden className="size-10" />
                                <p>Ajoutez votre premier véhicule.</p>
                                <Button type="button" onClick={() => select('new')}>
                                    <Plus aria-hidden /> Nouveau véhicule
                                </Button>
                            </div>
                        </div>
                    )}
                </section>
            )}

            <Dialog open={pending !== undefined} onOpenChange={(open) => !open && setPending(undefined)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Modifications non enregistrées</DialogTitle>
                        <DialogDescription>Si vous changez de véhicule maintenant, les modifications de cette fiche seront perdues.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Rester sur la fiche
                            </Button>
                        </DialogClose>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() => {
                                setDirty(false);
                                setSelected(pending ?? null);
                                setPending(undefined);
                            }}
                        >
                            Abandonner les modifications
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

function FilterSelect({
    label,
    value,
    onChange,
    options,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: [string, string][];
}) {
    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger size="sm" className="w-full" aria-label={label}>
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {options.map(([key, text]) => (
                    <SelectItem key={key} value={key}>
                        {text}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function VehicleRow({ vehicle, active, onSelect }: { vehicle: Vehicle; active: boolean; onSelect: () => void }) {
    return (
        <button
            type="button"
            onClick={onSelect}
            aria-current={active ? 'true' : undefined}
            className={cn(
                'flex w-full items-center gap-3 rounded-xl p-2 text-left transition-colors hover:bg-accent',
                active && 'bg-accent ring-1 ring-border',
            )}
        >
            <span className="relative grid h-12 w-16 shrink-0 place-items-center overflow-hidden rounded-lg bg-muted">
                <CarFront aria-hidden className="size-5 text-muted-foreground" />
                <img src={vehicle.display_image} alt="" loading="lazy" onError={(event) => (event.currentTarget.hidden = true)} className="absolute inset-0 size-full object-cover" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="flex items-center gap-1.5">
                    <span className="truncate font-medium">{vehicle.name}</span>
                    {(vehicle.model_url || vehicle.video_url) && <Rotate3d aria-label="Vue 3D disponible" className="size-3.5 shrink-0 text-muted-foreground" />}
                </span>
                <span className="block truncate text-xs text-muted-foreground">
                    {vehicle.category} · {formatPrice(vehicle.daily_price)}/jour
                </span>
                <span className="mt-1 flex flex-wrap gap-1">
                    {vehicle.is_available ? (
                        <Badge variant="outline" className="border-emerald-400/30 text-emerald-300">Visible</Badge>
                    ) : (
                        <Badge variant="outline" className="text-muted-foreground">Masqué</Badge>
                    )}
                    {vehicle.reserved_now ? (
                        <Badge variant="outline" className="border-amber-400/40 text-amber-300">En location</Badge>
                    ) : (
                        <Badge variant="outline" className="text-muted-foreground">Libre</Badge>
                    )}
                </span>
            </span>
        </button>
    );
}

function VehicleEditor({
    vehicle,
    categories,
    csrfToken,
    urls,
    onDirtyChange,
    onSaved,
    onDeleted,
}: {
    vehicle: Vehicle | null;
    categories: string[];
    csrfToken: string;
    urls: Props['urls'];
    onDirtyChange: (dirty: boolean) => void;
    onSaved: (vehicle: Vehicle, created: boolean) => void;
    onDeleted: (id: number) => void;
}) {
    const isNew = vehicle === null;
    const form = useForm<Values>({ resolver: zodResolver(schema), defaultValues: toValues(vehicle) });
    const [image, setImage] = useState<File | null>(null);
    const [model, setModel] = useState<File | null>(null);
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const imageInput = useRef<HTMLInputElement>(null);
    const modelInput = useRef<HTMLInputElement>(null);
    const imagePreview = useMemo(() => (image ? URL.createObjectURL(image) : null), [image]);
    const isDirty = form.formState.isDirty || image !== null || model !== null;

    useEffect(() => onDirtyChange(isDirty), [isDirty, onDirtyChange]);
    useEffect(() => () => {
        if (imagePreview) URL.revokeObjectURL(imagePreview);
    }, [imagePreview]);

    // Protection a la fermeture de l'onglet avec des modifications en cours.
    useEffect(() => {
        if (!isDirty) return;
        const warn = (event: BeforeUnloadEvent) => event.preventDefault();
        window.addEventListener('beforeunload', warn);

        return () => window.removeEventListener('beforeunload', warn);
    }, [isDirty]);

    const itemUrl = vehicle ? urls.item.replace('__ID__', String(vehicle.id)) : urls.store;

    async function onSubmit(values: Values) {
        const body = new FormData();
        for (const [key, value] of Object.entries(values)) {
            body.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
        }
        if (image) body.append('image', image);
        if (model) body.append('model', model);
        if (!isNew) body.append('_method', 'PUT');

        let response: Response;

        try {
            response = await fetch(itemUrl, { method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        } catch {
            toast.error('Connexion impossible. Vérifiez votre réseau.');
            return;
        }

        const data = await response.json().catch(() => ({}));

        if (response.ok) {
            toast.success(data.message ?? 'Enregistré.');
            setImage(null);
            setModel(null);
            form.reset(toValues(data.vehicle));
            onSaved(data.vehicle, isNew);
            return;
        }

        if (response.status === 422 && data.errors) {
            let fileError = '';
            for (const [field, messages] of Object.entries(data.errors as Record<string, string[]>)) {
                if (field in toValues(null)) {
                    form.setError(field as keyof Values, { type: 'server', message: messages[0] });
                } else {
                    fileError = messages[0];
                }
            }
            toast.error(fileError || 'Veuillez corriger les champs signalés.');
            return;
        }

        if (response.status === 413) {
            toast.error('Fichier trop lourd pour le serveur (limite PHP post_max_size).');
            return;
        }

        if (response.status === 419 || response.status === 401) {
            toast.error('Votre session a expiré. Rechargez la page et reconnectez-vous.');
            return;
        }

        toast.error("L'enregistrement a échoué. Réessayez.");
    }

    async function onDelete() {
        if (!vehicle) return;
        setDeleting(true);

        try {
            const response = await fetch(itemUrl, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            toast.success(`${vehicle.name} a été supprimé.`);
            setConfirmDelete(false);
            onDeleted(vehicle.id);
        } catch {
            toast.error('La suppression a échoué.');
        } finally {
            setDeleting(false);
        }
    }

    const previewSrc = imagePreview ?? (form.watch('image_url') && !vehicle?.has_uploaded_image ? form.watch('image_url') : vehicle?.display_image);

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} noValidate className="flex min-h-0 flex-1 flex-col">
                <div className="min-h-0 flex-1 overflow-y-auto">
                    {/* En-tete : photo + nom */}
                    <div className="grid gap-4 border-b border-border p-4 sm:grid-cols-[220px_minmax(0,1fr)] sm:p-5">
                        <div className="relative aspect-[16/10] overflow-hidden rounded-xl bg-muted">
                            <CarFront aria-hidden className="absolute left-1/2 top-1/2 size-8 -translate-x-1/2 -translate-y-1/2 text-muted-foreground" />
                            {previewSrc && <img src={previewSrc} alt="" className="absolute inset-0 size-full object-cover" onError={(event) => (event.currentTarget.hidden = true)} />}
                            <Button type="button" size="sm" variant="secondary" className="absolute bottom-2 right-2 shadow" onClick={() => imageInput.current?.click()}>
                                <ImageUp aria-hidden /> Photo
                            </Button>
                            <input
                                ref={imageInput}
                                type="file"
                                accept="image/*"
                                className="sr-only"
                                aria-label="Changer la photo du véhicule"
                                onChange={(event) => setImage(event.target.files?.[0] ?? null)}
                            />
                        </div>
                        <div className="grid content-start gap-2">
                            <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{isNew ? 'Nouveau véhicule' : `Réf. #${vehicle.id}`}</p>
                            <h2 className="text-2xl font-semibold tracking-tight">{form.watch('name') || 'Sans nom'}</h2>
                            {image && <p className="text-xs text-amber-300">Nouvelle photo sélectionnée : {image.name} (enregistrez pour l’appliquer)</p>}
                            {!isNew && (
                                <div className="flex flex-wrap gap-2 pt-1">
                                    {vehicle.public_url && (
                                        <Button asChild size="sm" variant="outline">
                                            <a href={vehicle.public_url} target="_blank" rel="noopener">
                                                <ExternalLink aria-hidden /> Voir la fiche publique
                                            </a>
                                        </Button>
                                    )}
                                    <Button asChild size="sm" variant="outline">
                                        <a href={urls.planning}>
                                            <CalendarDays aria-hidden /> {vehicle.reserved_now ? 'En location' : 'Libre'} · {vehicle.upcoming_count} à venir
                                        </a>
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="grid gap-6 p-4 sm:p-5">
                        <Section title="Informations">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <TextField form={form} name="name" label="Nom du modèle" />
                                <TextField form={form} name="category" label="Catégorie" list="vehicle-categories" />
                                <datalist id="vehicle-categories">
                                    {categories.map((item) => (
                                        <option key={item} value={item} />
                                    ))}
                                </datalist>
                                <TextField form={form} name="daily_price" label="Prix par jour (€)" inputMode="numeric" />
                                <TextField form={form} name="seats" label="Nombre de places" inputMode="numeric" placeholder="5" />
                                <TextField form={form} name="horsepower" label="Puissance (ch)" inputMode="numeric" placeholder="641" />
                                <TextField form={form} name="fuel_type" label="Carburant" />
                                <TextField form={form} name="transmission" label="Transmission" />
                            </div>
                        </Section>

                        <Section title="Statut">
                            <div className="grid gap-3 sm:grid-cols-2">
                                <SwitchField form={form} name="is_available" label="Visible et réservable sur le site" />
                                <SwitchField form={form} name="with_chauffeur" label="Avec chauffeur" />
                            </div>
                        </Section>

                        <Section title="Photo">
                            <TextField
                                form={form}
                                name="image_url"
                                label="Ou adresse d’une image externe"
                                placeholder="https://…"
                                description={vehicle?.has_uploaded_image ? 'Une photo envoyée est utilisée en priorité sur cette adresse.' : undefined}
                            />
                        </Section>

                        <Section title="3D">
                            <div className="grid gap-4">
                                <div className="flex flex-wrap items-center gap-3 rounded-xl border border-dashed border-border p-4">
                                    <Box aria-hidden className="size-5 text-muted-foreground" />
                                    <div className="min-w-0 flex-1">
                                        <p className="font-medium">Modèle 3D (.glb)</p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {model ? `Sélectionné : ${model.name}` : vehicle?.model_url ? 'Un modèle est en ligne.' : 'Aucun modèle. 50 Mo maximum.'}
                                        </p>
                                    </div>
                                    <Button type="button" size="sm" variant="outline" onClick={() => modelInput.current?.click()}>
                                        {vehicle?.model_url ? 'Remplacer' : 'Choisir un fichier'}
                                    </Button>
                                    <input
                                        ref={modelInput}
                                        type="file"
                                        accept=".glb,model/gltf-binary"
                                        className="sr-only"
                                        aria-label="Choisir un modèle 3D"
                                        onChange={(event) => setModel(event.target.files?.[0] ?? null)}
                                    />
                                </div>
                                {vehicle?.model_url && !model && <SwitchField form={form} name="remove_model" label="Supprimer le modèle 3D actuel" />}
                                <TextField form={form} name="video_url" label="Vidéo 3D (adresse .mp4, facultatif)" placeholder="https://…" />
                            </div>
                        </Section>

                        <Section title="Description">
                            <FormField
                                control={form.control}
                                name="description"
                                render={({ field }) => (
                                    <FormItem>
                                        <FormLabel className="sr-only">Description</FormLabel>
                                        <FormControl>
                                            <Textarea rows={6} placeholder="Présentation du véhicule affichée sur sa fiche publique…" {...field} />
                                        </FormControl>
                                        <FormDescription>{field.value.length} / 2 000 caractères</FormDescription>
                                        <FormMessage />
                                    </FormItem>
                                )}
                            />
                        </Section>

                        {!isNew && (
                            <Section title="Zone sensible">
                                <Button type="button" variant="outline" className="w-fit text-destructive" onClick={() => setConfirmDelete(true)}>
                                    <Trash2 aria-hidden /> Supprimer ce véhicule
                                </Button>
                            </Section>
                        )}
                    </div>
                </div>

                {/* Barre d'enregistrement */}
                <div className="flex flex-wrap items-center justify-end gap-2 border-t border-border bg-card/95 p-3 backdrop-blur sm:p-4">
                    <p className="mr-auto text-xs text-muted-foreground" aria-live="polite">
                        {isDirty ? 'Modifications non enregistrées' : isNew ? 'Remplissez la fiche puis enregistrez.' : 'Tout est enregistré.'}
                    </p>
                    <Button
                        type="button"
                        variant="ghost"
                        disabled={!isDirty || form.formState.isSubmitting}
                        onClick={() => {
                            form.reset(toValues(vehicle));
                            setImage(null);
                            setModel(null);
                        }}
                    >
                        <Undo2 aria-hidden /> Annuler
                    </Button>
                    <Button type="submit" disabled={(!isDirty && !isNew) || form.formState.isSubmitting}>
                        {form.formState.isSubmitting ? <Loader2 aria-hidden className="animate-spin" /> : <Save aria-hidden />}
                        {isNew ? 'Créer le véhicule' : 'Enregistrer'}
                    </Button>
                </div>
            </form>

            <Dialog open={confirmDelete} onOpenChange={setConfirmDelete}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Supprimer {vehicle?.name} ?</DialogTitle>
                        <DialogDescription>
                            Le véhicule, sa photo et son modèle 3D seront supprimés définitivement, ainsi que ses réservations. Pour le retirer
                            du site sans rien perdre, désactivez plutôt « Visible et réservable ».
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Garder
                            </Button>
                        </DialogClose>
                        <Button type="button" variant="destructive" disabled={deleting} onClick={() => void onDelete()}>
                            {deleting && <Loader2 aria-hidden className="animate-spin" />} Supprimer définitivement
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Form>
    );
}

type FormApi = ReturnType<typeof useForm<Values>>;
type TextName = 'name' | 'category' | 'daily_price' | 'seats' | 'horsepower' | 'fuel_type' | 'transmission' | 'image_url' | 'video_url';

function TextField({
    form,
    name,
    label,
    description,
    ...props
}: { form: FormApi; name: TextName; label: string; description?: string } & Omit<React.ComponentProps<typeof Input>, 'form' | 'name'>) {
    return (
        <FormField
            control={form.control}
            name={name}
            render={({ field }) => (
                <FormItem>
                    <FormLabel>{label}</FormLabel>
                    <FormControl>
                        <Input {...props} {...field} />
                    </FormControl>
                    {description && <FormDescription>{description}</FormDescription>}
                    <FormMessage />
                </FormItem>
            )}
        />
    );
}

function SwitchField({ form, name, label }: { form: FormApi; name: 'is_available' | 'with_chauffeur' | 'remove_model'; label: string }) {
    return (
        <FormField
            control={form.control}
            name={name}
            render={({ field }) => (
                <FormItem className="flex flex-row items-center justify-between gap-3 rounded-xl border border-border p-3">
                    <FormLabel className="font-normal">{label}</FormLabel>
                    <FormControl>
                        <Switch checked={field.value} onCheckedChange={field.onChange} />
                    </FormControl>
                </FormItem>
            )}
        />
    );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <fieldset className="grid gap-3">
            <legend className="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">{title}</legend>
            {children}
        </fieldset>
    );
}

function ListSkeleton() {
    return (
        <div className="grid gap-2 p-1" aria-busy="true" aria-label="Chargement des véhicules">
            {Array.from({ length: 6 }, (_, index) => (
                <div key={index} className="flex items-center gap-3 p-2">
                    <Skeleton className="h-12 w-16 rounded-lg" />
                    <div className="grid flex-1 gap-2">
                        <Skeleton className="h-4 w-3/4" />
                        <Skeleton className="h-3 w-1/2" />
                    </div>
                </div>
            ))}
        </div>
    );
}

function EditorSkeleton() {
    return (
        <div className="grid gap-4 p-5" aria-busy="true" aria-label="Chargement de la fiche">
            <div className="grid gap-4 sm:grid-cols-[220px_1fr]">
                <Skeleton className="aspect-[16/10] rounded-xl" />
                <div className="grid content-start gap-2">
                    <Skeleton className="h-3 w-24" />
                    <Skeleton className="h-7 w-2/3" />
                </div>
            </div>
            {Array.from({ length: 4 }, (_, index) => (
                <Skeleton key={index} className="h-10" />
            ))}
        </div>
    );
}
