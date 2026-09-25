import { useEffect, useMemo, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { addMonths, startOfDay } from 'date-fns';
import { fr } from 'react-day-picker/locale';
import type { DateRange } from 'react-day-picker';
import { AnimatePresence, motion } from 'framer-motion';
import { toast } from 'sonner';
import {
    AlertTriangle,
    CalendarCheck2,
    CalendarDays,
    CarFront,
    CheckCircle2,
    Fuel,
    Gauge,
    Loader2,
    RefreshCw,
    Settings2,
} from 'lucide-react';
import { cn } from 'cn';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { useAvailableVehicles } from '@/hooks/use-available-vehicles';
import { useBookedPeriods } from '@/hooks/use-booked-periods';
import {
    TIME_SLOTS,
    classifyDays,
    combine,
    dayKey,
    describePeriod,
    formatDateTime,
    formatPrice,
    fromApiDateTime,
    periodsTouchingDays,
    rentalDays,
    toApiDateTime,
    type BookingVehicle,
    type Period,
} from '@/lib/booking';

type Props = {
    vehicles: BookingVehicle[];
    initialVehicleId: number | null;
    initialStart?: string | null;
    initialEnd?: string | null;
    initialPickup?: string | null;
    csrfToken: string;
    contactPhone?: string | null;
    phoneCountryCode?: string | null;
    urls: { store: string; bookedPeriods: string; available: string; home: string; privacy?: string | null };
};

type Confirmation = {
    id: number;
    start_at: string;
    end_at: string;
    days: number;
    estimated_total: number;
    pickup_location: string;
    customer_email: string;
    vehicle: BookingVehicle;
};

const schema = z
    .object({
        vehicle_id: z.string().min(1, 'Choisissez un véhicule.'),
        range: z.custom<DateRange | undefined>().refine((range) => !!range?.from, 'Choisissez vos dates.'),
        start_time: z.string().min(1, "Choisissez l'heure de départ."),
        end_time: z.string().min(1, "Choisissez l'heure de retour."),
        pickup_location: z.string().trim().min(2, 'Indiquez le lieu de prise en charge.').max(180, 'Ce champ est trop long.'),
        customer_name: z.string().trim().min(2, 'Indiquez votre nom.').max(120, 'Ce champ est trop long.'),
        customer_email: z.string().trim().pipe(z.email('Adresse email invalide.')),
        customer_phone: z
            .string()
            .trim()
            .regex(/^[+0-9 ().-]{6,40}$/, 'Numéro de téléphone invalide.'),
        message: z.string().max(2000, 'Message trop long.').optional(),
    })
    .superRefine((values, ctx) => {
        if (!values.range?.from || !values.start_time || !values.end_time) {
            return;
        }

        const start = combine(values.range.from, values.start_time);
        const end = combine(values.range.to ?? values.range.from, values.end_time);

        if (start < new Date()) {
            ctx.addIssue({ code: 'custom', path: ['start_time'], message: 'Le départ doit être dans le futur.' });
        }

        if (end <= start) {
            ctx.addIssue({ code: 'custom', path: ['end_time'], message: 'Le retour doit être après le départ.' });
        }
    });

type FormValues = z.infer<typeof schema>;

/** Champs renvoyes par l'API -> champs du formulaire. */
const SERVER_FIELD_MAP: Record<string, keyof FormValues> = {
    vehicle_id: 'vehicle_id',
    start_at: 'start_time',
    end_at: 'end_time',
    pickup_location: 'pickup_location',
    customer_name: 'customer_name',
    customer_email: 'customer_email',
    customer_phone: 'customer_phone',
    message: 'message',
};

function useIsDesktop(): boolean {
    const query = '(min-width: 768px)';
    const [matches, setMatches] = useState(() => window.matchMedia(query).matches);

    useEffect(() => {
        const media = window.matchMedia(query);
        const onChange = () => setMatches(media.matches);
        media.addEventListener('change', onChange);

        return () => media.removeEventListener('change', onChange);
    }, []);

    return matches;
}

/** Valeurs initiales (lien depuis le module de recherche de l'accueil). */
function initialPeriod(start?: string | null, end?: string | null) {
    if (!start || !end) {
        return { range: undefined, startTime: '10:00', endTime: '10:00' };
    }

    const from = fromApiDateTime(start);
    const to = fromApiDateTime(end);
    const time = (date: Date) => {
        const value = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
        return TIME_SLOTS.includes(value) ? value : '10:00';
    };

    return { range: { from: startOfDay(from), to: startOfDay(to) }, startTime: time(from), endTime: time(to) };
}

export default function BookingForm({ vehicles, initialVehicleId, initialStart, initialEnd, initialPickup, csrfToken, contactPhone, phoneCountryCode, urls }: Props) {
    const [confirmation, setConfirmation] = useState<Confirmation | null>(null);
    const [suggestions, setSuggestions] = useState<Period[]>([]);
    const [calendarOpen, setCalendarOpen] = useState(false);
    const isDesktop = useIsDesktop();
    const [initial] = useState(() => initialPeriod(initialStart, initialEnd));

    const form = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: {
            vehicle_id: initialVehicleId ? String(initialVehicleId) : '',
            range: initial.range,
            start_time: initial.startTime,
            end_time: initial.endTime,
            pickup_location: initialPickup ?? '',
            customer_name: '',
            customer_email: '',
            customer_phone: '',
            message: '',
        },
    });

    const [vehicleId, range, startTime, endTime] = useWatch({
        control: form.control,
        name: ['vehicle_id', 'range', 'start_time', 'end_time'],
    });

    const vehicle = vehicles.find((item) => String(item.id) === vehicleId);
    const booked = useBookedPeriods(urls.bookedPeriods, vehicleId || undefined);
    const days = useMemo(() => classifyDays(booked.periods), [booked.periods]);
    const today = startOfDay(new Date());

    const selectedStart = range?.from && startTime ? combine(range.from, startTime) : null;
    const selectedEnd = range?.from && endTime ? combine(range.to ?? range.from, endTime) : null;
    const validPeriod = selectedStart && selectedEnd && selectedEnd > selectedStart;
    const estimate = vehicle && validPeriod ? vehicle.daily_price * rentalDays(selectedStart, selectedEnd) : null;
    const nearbyBookings = range?.from ? periodsTouchingDays(booked.periods, range.from, range.to ?? range.from) : [];
    const availableIds = useAvailableVehicles(
        urls.available,
        validPeriod ? toApiDateTime(selectedStart) : null,
        validPeriod ? toApiDateTime(selectedEnd) : null,
    );

    // Changement de vehicule : les dates choisies ne sont plus forcement libres.
    useEffect(() => {
        setSuggestions([]);
    }, [vehicleId]);

    function applySuggestion(period: Period) {
        const start = fromApiDateTime(period.start_at);
        const end = fromApiDateTime(period.end_at);
        const pad = (value: number) => String(value).padStart(2, '0');

        form.setValue('range', { from: startOfDay(start), to: startOfDay(end) }, { shouldValidate: true });
        form.setValue('start_time', `${pad(start.getHours())}:${pad(start.getMinutes())}`, { shouldValidate: true });
        form.setValue('end_time', `${pad(end.getHours())}:${pad(end.getMinutes())}`, { shouldValidate: true });
        setSuggestions([]);
        toast.success(`Dates mises à jour : ${describePeriod(period)}.`);
    }

    async function onSubmit(values: FormValues) {
        const from = values.range!.from!;
        const start = combine(from, values.start_time);
        const end = combine(values.range!.to ?? from, values.end_time);

        let response: Response;

        try {
            response = await fetch(urls.store, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    vehicle_id: Number(values.vehicle_id),
                    start_at: toApiDateTime(start),
                    end_at: toApiDateTime(end),
                    pickup_location: values.pickup_location,
                    customer_name: values.customer_name,
                    customer_email: values.customer_email,
                    customer_phone: values.customer_phone,
                    message: values.message || null,
                }),
            });
        } catch {
            toast.error('Connexion impossible. Vérifiez votre réseau et réessayez.');
            return;
        }

        const data = await response.json().catch(() => ({}));

        if (response.status === 201) {
            setSuggestions([]);
            setConfirmation(data.reservation);
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        if (response.status === 409) {
            setSuggestions(data.suggestions ?? []);
            booked.reload();
            toast.error(data.message ?? 'Ce véhicule est déjà réservé sur cette période.', {
                description: data.suggestions?.length ? 'Nous vous proposons les prochaines disponibilités sous le calendrier.' : undefined,
                duration: 8000,
            });
            return;
        }

        if (response.status === 422 && data.errors) {
            for (const [field, messages] of Object.entries(data.errors as Record<string, string[]>)) {
                const target = SERVER_FIELD_MAP[field];

                if (target) {
                    form.setError(target, { type: 'server', message: messages[0] });
                }
            }

            toast.error('Veuillez corriger les champs signalés.');
            return;
        }

        if (response.status === 419) {
            toast.error('Votre session a expiré. Rechargez la page puis réessayez.');
            return;
        }

        if (response.status === 429) {
            toast.error('Trop de demandes envoyées. Patientez une minute puis réessayez.');
            return;
        }

        toast.error(contactPhone ? `Une erreur est survenue. Réessayez ou appelez-nous au ${contactPhone}.` : 'Une erreur est survenue. Réessayez dans un instant.');
    }

    function startOver() {
        setConfirmation(null);
        form.reset({ ...form.getValues(), range: undefined, message: '' });
        booked.reload();
    }

    if (vehicles.length === 0) {
        return (
            <Container>
                <Card>
                    <CardHeader>
                        <CardTitle>Aucun véhicule disponible</CardTitle>
                        <CardDescription>
                            Notre flotte n'est pas réservable en ligne pour le moment.{contactPhone ? ` Appelez-nous au ${contactPhone}.` : ''}
                        </CardDescription>
                    </CardHeader>
                </Card>
            </Container>
        );
    }

    if (confirmation) {
        return (
            <Container>
                <BookingConfirmation confirmation={confirmation} homeUrl={urls.home} onStartOver={startOver} />
            </Container>
        );
    }

    return (
        <Container>
            <Form {...form}>
                <form
                    onSubmit={form.handleSubmit(onSubmit)}
                    noValidate
                    className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start"
                >
                    <div className="grid gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">1. Véhicule et dates</CardTitle>
                                <CardDescription>Les jours barrés sont indisponibles pour le véhicule choisi.</CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-5">
                                <FormField
                                    control={form.control}
                                    name="vehicle_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Véhicule</FormLabel>
                                            <Select value={field.value} onValueChange={field.onChange}>
                                                <FormControl>
                                                    <SelectTrigger className="w-full">
                                                        <SelectValue placeholder="Choisissez un véhicule" />
                                                    </SelectTrigger>
                                                </FormControl>
                                                <SelectContent>
                                                    {vehicles.map((item) => {
                                                        const unavailable = availableIds !== null && !availableIds.has(item.id);

                                                        return (
                                                            <SelectItem key={item.id} value={String(item.id)} disabled={unavailable}>
                                                                {item.name} · {formatPrice(item.daily_price)}/jour
                                                                {unavailable && ' · indisponible sur ces dates'}
                                                            </SelectItem>
                                                        );
                                                    })}
                                                </SelectContent>
                                            </Select>
                                            {availableIds !== null && (
                                                <FormDescription>
                                                    {availableIds.size === 0
                                                        ? 'Aucun véhicule libre sur ces dates : essayez une autre période.'
                                                        : `${availableIds.size} véhicule(s) disponible(s) sur la période choisie.`}
                                                </FormDescription>
                                            )}
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <FormField
                                    control={form.control}
                                    name="range"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Dates de location</FormLabel>
                                            <Popover open={calendarOpen} onOpenChange={setCalendarOpen}>
                                                <PopoverTrigger asChild>
                                                    <FormControl>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            disabled={!vehicleId}
                                                            className={cn(
                                                                'w-full justify-start font-normal',
                                                                !field.value?.from && 'text-muted-foreground',
                                                            )}
                                                        >
                                                            <CalendarDays aria-hidden className="size-4" />
                                                            {rangeLabel(field.value, vehicleId)}
                                                        </Button>
                                                    </FormControl>
                                                </PopoverTrigger>
                                                <PopoverContent className="w-auto p-0" align="start">
                                                    {booked.status === 'loading' ? (
                                                        <CalendarSkeleton months={isDesktop ? 2 : 1} />
                                                    ) : booked.status === 'error' ? (
                                                        <LoadError onRetry={booked.reload} />
                                                    ) : (
                                                        <Calendar
                                                            mode="range"
                                                            locale={fr}
                                                            numberOfMonths={isDesktop ? 2 : 1}
                                                            selected={field.value}
                                                            onSelect={field.onChange}
                                                            defaultMonth={field.value?.from ?? today}
                                                            startMonth={today}
                                                            endMonth={addMonths(today, 12)}
                                                            excludeDisabled
                                                            disabled={(date) => date < today || days.full.has(dayKey(date))}
                                                            modifiers={{
                                                                booked: (date) => days.full.has(dayKey(date)),
                                                                partial: (date) => days.partial.has(dayKey(date)),
                                                            }}
                                                            modifiersClassNames={{
                                                                booked: '[&_button]:line-through [&_button]:decoration-destructive/70',
                                                                partial:
                                                                    'relative after:pointer-events-none after:absolute after:bottom-1 after:left-1/2 after:size-1 after:-translate-x-1/2 after:rounded-full after:bg-amber-400',
                                                            }}
                                                        />
                                                    )}
                                                    <CalendarLegend />
                                                </PopoverContent>
                                            </Popover>
                                            {booked.status === 'loading' && (
                                                <FormDescription className="flex items-center gap-2">
                                                    <Loader2 aria-hidden className="size-3.5 animate-spin" /> Chargement des disponibilités…
                                                </FormDescription>
                                            )}
                                            {booked.status === 'error' && (
                                                <FormDescription className="text-destructive">
                                                    Impossible de charger les disponibilités.{' '}
                                                    <button type="button" className="underline" onClick={booked.reload}>
                                                        Réessayer
                                                    </button>
                                                </FormDescription>
                                            )}
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                <div className="grid gap-5 sm:grid-cols-2">
                                    <TimeField control={form.control} name="start_time" label="Heure de départ" />
                                    <TimeField control={form.control} name="end_time" label="Heure de retour" />
                                </div>

                                {nearbyBookings.length > 0 && (
                                    <div className="rounded-lg border border-amber-400/40 bg-amber-400/10 p-3 text-sm" role="note">
                                        <p className="mb-1 flex items-center gap-2 font-medium">
                                            <AlertTriangle aria-hidden className="size-4 text-amber-400" /> Créneaux déjà réservés ces jours-là
                                        </p>
                                        <ul className="grid gap-0.5 text-muted-foreground">
                                            {nearbyBookings.map((period) => (
                                                <li key={period.start_at}>Réservé {describePeriod(period)}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                <AnimatePresence>
                                    {suggestions.length > 0 && (
                                        <motion.div
                                            initial={{ opacity: 0, y: -6 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            exit={{ opacity: 0, y: -6 }}
                                            className="rounded-lg border border-border bg-secondary p-4"
                                        >
                                            <p className="mb-3 flex items-center gap-2 text-sm font-medium">
                                                <CalendarCheck2 aria-hidden className="size-4" /> Prochaines disponibilités pour la même durée
                                            </p>
                                            <div className="flex flex-wrap gap-2">
                                                {suggestions.map((period) => (
                                                    <Button
                                                        key={period.start_at}
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => applySuggestion(period)}
                                                    >
                                                        {describePeriod(period)}
                                                    </Button>
                                                ))}
                                            </div>
                                        </motion.div>
                                    )}
                                </AnimatePresence>

                                <FormField
                                    control={form.control}
                                    name="pickup_location"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Lieu de prise en charge</FormLabel>
                                            <FormControl>
                                                <Input placeholder="Paris, aéroport, hôtel…" autoComplete="street-address" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">2. Vos coordonnées</CardTitle>
                                <CardDescription>Nous vous recontactons pour confirmer la réservation.</CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-5 sm:grid-cols-2">
                                <FormField
                                    control={form.control}
                                    name="customer_name"
                                    render={({ field }) => (
                                        <FormItem className="sm:col-span-2">
                                            <FormLabel>Nom complet</FormLabel>
                                            <FormControl>
                                                <Input autoComplete="name" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                                <FormField
                                    control={form.control}
                                    name="customer_email"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Email</FormLabel>
                                            <FormControl>
                                                <Input type="email" autoComplete="email" {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                                <FormField
                                    control={form.control}
                                    name="customer_phone"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>Téléphone</FormLabel>
                                            <FormControl>
                                                <Input type="tel" autoComplete="tel" placeholder={phoneCountryCode ? `+${phoneCountryCode}…` : "Votre numéro"} {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                                <FormField
                                    control={form.control}
                                    name="message"
                                    render={({ field }) => (
                                        <FormItem className="sm:col-span-2">
                                            <FormLabel>Message (facultatif)</FormLabel>
                                            <FormControl>
                                                <textarea
                                                    rows={3}
                                                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive"
                                                    {...field}
                                                />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <BookingSummary
                        vehicle={vehicle}
                        start={validPeriod ? selectedStart : null}
                        end={validPeriod ? selectedEnd : null}
                        estimate={estimate}
                        submitting={form.formState.isSubmitting}
                        privacyUrl={urls.privacy}
                    />
                </form>
            </Form>
        </Container>
    );
}

function Container({ children }: { children: React.ReactNode }) {
    return <div className="mx-auto w-[min(1180px,calc(100%-40px))] pb-20">{children}</div>;
}

function rangeLabel(range: DateRange | undefined, vehicleId: string): string {
    if (!range?.from) {
        return vehicleId ? 'Sélectionnez le départ puis le retour' : "Choisissez d'abord un véhicule";
    }

    const format = (date: Date) => date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

    return range.to && dayKey(range.to) !== dayKey(range.from)
        ? `Du ${format(range.from)} au ${format(range.to)}`
        : `Le ${format(range.from)}`;
}

function TimeField({
    control,
    name,
    label,
}: {
    control: ReturnType<typeof useForm<FormValues>>['control'];
    name: 'start_time' | 'end_time';
    label: string;
}) {
    return (
        <FormField
            control={control}
            name={name}
            render={({ field }) => (
                <FormItem>
                    <FormLabel>{label}</FormLabel>
                    <Select value={field.value} onValueChange={field.onChange}>
                        <FormControl>
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="--:--" />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent className="max-h-64">
                            {TIME_SLOTS.map((slot) => (
                                <SelectItem key={slot} value={slot}>
                                    {slot}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <FormMessage />
                </FormItem>
            )}
        />
    );
}

function CalendarLegend() {
    return (
        <div className="flex flex-wrap gap-x-4 gap-y-1 border-t border-border px-4 py-2.5 text-xs text-muted-foreground">
            <span className="flex items-center gap-1.5">
                <span aria-hidden className="text-sm line-through decoration-destructive/70 opacity-50">
                    12
                </span>
                Indisponible
            </span>
            <span className="flex items-center gap-1.5">
                <span aria-hidden className="size-1.5 rounded-full bg-amber-400" />
                Partiellement réservé
            </span>
        </div>
    );
}

function CalendarSkeleton({ months }: { months: number }) {
    return (
        <div className="flex gap-4 p-3" aria-busy="true" aria-label="Chargement du calendrier">
            {Array.from({ length: months }, (_, month) => (
                <div key={month} className="grid w-64 gap-2">
                    <Skeleton className="mx-auto h-5 w-32" />
                    <div className="grid grid-cols-7 gap-1">
                        {Array.from({ length: 35 }, (_, day) => (
                            <Skeleton key={day} className="aspect-square" />
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}

function LoadError({ onRetry }: { onRetry: () => void }) {
    return (
        <div className="grid w-72 justify-items-center gap-3 p-6 text-center text-sm" role="alert">
            <AlertTriangle aria-hidden className="size-6 text-destructive" />
            <p>Impossible de charger les disponibilités de ce véhicule.</p>
            <Button type="button" size="sm" variant="outline" onClick={onRetry}>
                <RefreshCw aria-hidden className="size-4" /> Réessayer
            </Button>
        </div>
    );
}

function BookingSummary({
    vehicle,
    start,
    end,
    estimate,
    submitting,
    privacyUrl,
}: {
    privacyUrl?: string | null;
    vehicle: BookingVehicle | undefined;
    start: Date | null;
    end: Date | null;
    estimate: number | null;
    submitting: boolean;
}) {
    return (
        <Card className="overflow-hidden pt-0 lg:sticky lg:top-28">
            {vehicle ? (
                <img src={vehicle.image} alt={vehicle.name} loading="lazy" className="aspect-[16/10] w-full object-cover" />
            ) : (
                <div className="flex aspect-[16/10] w-full items-center justify-center bg-muted text-muted-foreground">
                    <CarFront aria-hidden className="size-10" />
                </div>
            )}
            <CardHeader>
                <CardTitle className="text-lg">{vehicle?.name ?? 'Votre véhicule'}</CardTitle>
                {vehicle && (
                    <div className="flex flex-wrap gap-1.5 pt-1">
                        <Badge variant="secondary">
                            <Fuel aria-hidden /> {vehicle.fuel_type}
                        </Badge>
                        <Badge variant="secondary">
                            <Settings2 aria-hidden /> {vehicle.transmission}
                        </Badge>
                        {vehicle.horsepower && (
                            <Badge variant="secondary">
                                <Gauge aria-hidden /> {vehicle.horsepower} ch
                            </Badge>
                        )}
                    </div>
                )}
            </CardHeader>
            <CardContent className="grid gap-2 text-sm">
                <SummaryRow label="Départ" value={start ? formatDateTime(start) : '—'} />
                <SummaryRow label="Retour" value={end ? formatDateTime(end) : '—'} />
                <SummaryRow label="Durée" value={start && end ? `${rentalDays(start, end)} jour(s)` : '—'} />
                <div className="mt-2 flex items-baseline justify-between border-t border-border pt-3">
                    <span className="text-muted-foreground">Estimation</span>
                    <span className="text-xl font-semibold">{estimate !== null ? formatPrice(estimate) : '—'}</span>
                </div>
            </CardContent>
            <CardFooter className="grid gap-2">
                <Button type="submit" size="lg" className="w-full" disabled={submitting}>
                    {submitting ? <Loader2 aria-hidden className="size-4 animate-spin" /> : <CalendarCheck2 aria-hidden className="size-4" />}
                    {submitting ? 'Envoi en cours…' : 'Envoyer ma demande'}
                </Button>
                <p className="text-center text-xs text-muted-foreground">Aucun paiement en ligne. Confirmation par notre équipe.</p>
                <p className="text-center text-xs leading-relaxed text-muted-foreground">
                    Vos données servent uniquement à traiter votre demande.{' '}
                    {privacyUrl && (
                        <a href={privacyUrl} className="underline underline-offset-2 hover:text-foreground">
                            Politique de confidentialité
                        </a>
                    )}
                </p>
            </CardFooter>
        </Card>
    );
}

function SummaryRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-4">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right">{value}</span>
        </div>
    );
}

function BookingConfirmation({
    confirmation,
    homeUrl,
    onStartOver,
}: {
    confirmation: Confirmation;
    homeUrl: string;
    onStartOver: () => void;
}) {
    const start = fromApiDateTime(confirmation.start_at);
    const end = fromApiDateTime(confirmation.end_at);

    return (
        <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.35 }}>
            <Card className="mx-auto max-w-2xl" role="status" aria-live="polite">
                <CardHeader className="justify-items-center text-center">
                    <motion.div
                        initial={{ scale: 0.6, opacity: 0 }}
                        animate={{ scale: 1, opacity: 1 }}
                        transition={{ delay: 0.15, type: 'spring', stiffness: 260, damping: 18 }}
                    >
                        <CheckCircle2 aria-hidden className="size-14 text-success" />
                    </motion.div>
                    <CardTitle className="pt-2 text-2xl">Demande de réservation envoyée</CardTitle>
                    <CardDescription>
                        Référence <strong className="text-foreground">#{confirmation.id}</strong>. Nous vous recontactons
                        rapidement pour confirmer. Un récapitulatif vous est envoyé à {confirmation.customer_email}.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 rounded-lg border border-border p-4 sm:grid-cols-[140px_1fr]">
                        <img
                            src={confirmation.vehicle.image}
                            alt={confirmation.vehicle.name}
                            className="aspect-[16/10] w-full rounded-md object-cover"
                        />
                        <div className="grid gap-1.5 text-sm">
                            <p className="text-base font-semibold">{confirmation.vehicle.name}</p>
                            <SummaryRow label="Départ" value={formatDateTime(start)} />
                            <SummaryRow label="Retour" value={formatDateTime(end)} />
                            <SummaryRow label="Lieu" value={confirmation.pickup_location} />
                            <SummaryRow
                                label="Estimation"
                                value={`${formatPrice(confirmation.estimated_total)} (${confirmation.days} j)`}
                            />
                        </div>
                    </div>
                </CardContent>
                <CardFooter className="flex flex-col gap-2 sm:flex-row sm:justify-center">
                    <Button type="button" variant="outline" onClick={onStartOver}>
                        Nouvelle réservation
                    </Button>
                    <Button asChild>
                        <a href={homeUrl}>Retour à l'accueil</a>
                    </Button>
                </CardFooter>
            </Card>
        </motion.div>
    );
}
