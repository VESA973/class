import { useCallback, useMemo, useRef, useState } from 'react';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import type { DatesSetArg, EventClickArg, EventInput, EventSourceFuncArg } from '@fullcalendar/core';
import type { DateClickArg } from '@fullcalendar/interaction';
import { toast } from 'sonner';
import {
    AlertTriangle,
    CalendarClock,
    CarFront,
    Check,
    ChevronLeft,
    ChevronRight,
    ExternalLink,
    Loader2,
    Mail,
    MapPin,
    MessageSquare,
    Phone,
    RefreshCw,
    Users,
    X,
} from 'lucide-react';
import { cn } from 'cn';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { describePeriod, formatPrice, toApiDateTime } from '@/lib/booking';

type Status = 'pending' | 'confirmed' | 'cancelled' | 'completed';

type ReservationEvent = {
    id: string;
    title: string;
    start_at: string;
    end_at: string;
    extendedProps: {
        status: Status;
        vehicleId: number;
        vehicleName: string | null;
        customerName: string;
        customerEmail: string | null;
        customerPhone: string;
        pickupLocation: string;
        destination: string | null;
        passengers: number | null;
        serviceType: string;
        days: number;
        estimatedTotal: number;
        message: string | null;
        createdAt: string | null;
        adminUrl: string;
    };
};

type Props = {
    vehicles: { id: number; name: string }[];
    statusLabels: Record<Status, string>;
    csrfToken: string;
    urls: { events: string; status: string };
};

type ViewName = 'dayGridMonth' | 'timeGridWeek' | 'timeGridDay';

const VIEWS: { name: ViewName; label: string }[] = [
    { name: 'dayGridMonth', label: 'Mois' },
    { name: 'timeGridWeek', label: 'Semaine' },
    { name: 'timeGridDay', label: 'Jour' },
];

/** Couleurs par statut : contraste AA texte / fond. */
const STATUS_COLORS: Record<Status, { bg: string; text: string }> = {
    pending: { bg: '#f59e0b', text: '#1a1204' },
    confirmed: { bg: '#10b981', text: '#04130d' },
    cancelled: { bg: '#dc2626', text: '#ffffff' },
    completed: { bg: '#64748b', text: '#ffffff' },
};

const VEHICLE_COLORS = ['#60a5fa', '#f472b6', '#a78bfa', '#34d399', '#fbbf24', '#f87171', '#22d3ee', '#a3e635'];

export default function Planning({ vehicles, statusLabels, csrfToken, urls }: Props) {
    const calendarRef = useRef<FullCalendar>(null);
    const [title, setTitle] = useState('');
    const [view, setView] = useState<ViewName>(() =>
        window.matchMedia('(min-width: 768px)').matches ? 'dayGridMonth' : 'timeGridDay',
    );
    const [vehicleFilter, setVehicleFilter] = useState('all');
    const [colorBy, setColorBy] = useState<'status' | 'vehicle'>('status');
    const [loading, setLoading] = useState(true);
    const [loadedOnce, setLoadedOnce] = useState(false);
    const [error, setError] = useState(false);
    const [eventCount, setEventCount] = useState(0);
    const [selected, setSelected] = useState<ReservationEvent | null>(null);
    const [confirmCancel, setConfirmCancel] = useState(false);
    const [updating, setUpdating] = useState<Status | null>(null);

    const api = () => calendarRef.current?.getApi();

    const vehicleColor = useMemo(() => {
        const colors = new Map<number, string>();
        vehicles.forEach((vehicle, index) => colors.set(vehicle.id, VEHICLE_COLORS[index % VEHICLE_COLORS.length]));

        return colors;
    }, [vehicles]);

    const toCalendarEvent = useCallback(
        (event: ReservationEvent): EventInput => {
            const status = event.extendedProps.status;
            const color =
                colorBy === 'status'
                    ? STATUS_COLORS[status]
                    : { bg: vehicleColor.get(event.extendedProps.vehicleId) ?? '#9ca3af', text: '#0b0b0b' };

            // Jours entiers (minuit a minuit) : evenement "journee", sans heure affichee.
            const allDay = event.start_at.endsWith('T00:00') && event.end_at.endsWith('T00:00');

            return {
                id: event.id,
                title: event.title,
                start: allDay ? event.start_at.slice(0, 10) : event.start_at,
                end: allDay ? event.end_at.slice(0, 10) : event.end_at,
                allDay,
                backgroundColor: color.bg,
                borderColor: color.bg,
                textColor: color.text,
                classNames: status === 'cancelled' ? ['is-cancelled'] : [],
                extendedProps: { raw: event },
            };
        },
        [colorBy, vehicleColor],
    );

    // Nouvelle identite (filtre / couleurs) => FullCalendar recharge la source.
    const fetchEvents = useCallback(
        (info: EventSourceFuncArg, success: (events: EventInput[]) => void, failure: (error: Error) => void) => {
            const params = new URLSearchParams({ start: toApiDateTime(info.start), end: toApiDateTime(info.end) });

            if (vehicleFilter !== 'all') {
                params.set('vehicle_id', vehicleFilter);
            }

            setError(false);
            fetch(`${urls.events}?${params.toString()}`, { headers: { Accept: 'application/json' } })
                .then((response) => {
                    if (response.status === 401) {
                        toast.error('Votre session a expiré. Rechargez la page et reconnectez-vous.');
                    }

                    return response.ok ? response.json() : Promise.reject(new Error(`HTTP ${response.status}`));
                })
                .then((data: ReservationEvent[]) => {
                    setLoadedOnce(true);
                    success(data.map(toCalendarEvent));
                })
                .catch((reason: Error) => {
                    setError(true);
                    failure(reason);
                });
        },
        [urls.events, vehicleFilter, toCalendarEvent],
    );

    function changeView(name: ViewName, date?: Date) {
        api()?.changeView(name, date);
    }

    function onDatesSet(arg: DatesSetArg) {
        setTitle(arg.view.title);
        setView(arg.view.type as ViewName);

        // La hauteur change entre les vues : on repositionne sur le debut de journee une fois rendu.
        if (arg.view.type !== 'dayGridMonth') {
            window.requestAnimationFrame(() => api()?.scrollToTime('07:00:00'));
        }
    }

    function onEventClick(arg: EventClickArg) {
        arg.jsEvent.preventDefault();
        setSelected(arg.event.extendedProps.raw as ReservationEvent);
    }

    function onDateClick(arg: DateClickArg) {
        if (arg.view.type === 'dayGridMonth') {
            changeView('timeGridDay', arg.date);
        }
    }

    async function updateStatus(status: 'confirmed' | 'cancelled') {
        if (!selected) {
            return;
        }

        setUpdating(status);

        try {
            const response = await fetch(urls.status.replace('__ID__', selected.id), {
                method: 'PATCH',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ status }),
            });

            if (response.status === 419 || response.status === 401) {
                toast.error('Votre session a expiré. Rechargez la page et reconnectez-vous.');
                return;
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data: { message: string; event: ReservationEvent } = await response.json();
            setSelected(data.event);
            setConfirmCancel(false);
            api()?.refetchEvents();
            toast.success(data.message);
        } catch {
            toast.error("La mise à jour n'a pas pu être enregistrée. Réessayez.");
        } finally {
            setUpdating(null);
        }
    }

    return (
        <div className="grid gap-4 text-sm">
            {/* Barre d'outils */}
            <div className="flex flex-wrap items-center gap-3 rounded-xl border border-border bg-card p-3">
                <div className="flex items-center gap-1">
                    <Button type="button" variant="outline" size="icon" aria-label="Période précédente" onClick={() => api()?.prev()}>
                        <ChevronLeft aria-hidden />
                    </Button>
                    <Button type="button" variant="outline" size="icon" aria-label="Période suivante" onClick={() => api()?.next()}>
                        <ChevronRight aria-hidden />
                    </Button>
                    <Button type="button" variant="outline" onClick={() => api()?.today()}>
                        Aujourd'hui
                    </Button>
                </div>

                <h2 className="min-w-40 flex-1 text-base font-semibold capitalize" aria-live="polite">
                    {title}
                    {loading && <Loader2 aria-label="Chargement" className="ml-2 inline size-4 animate-spin text-muted-foreground" />}
                </h2>

                <div className="flex rounded-md border border-border p-0.5" role="group" aria-label="Vue du calendrier">
                    {VIEWS.map((item) => (
                        <Button
                            key={item.name}
                            type="button"
                            size="sm"
                            variant={view === item.name ? 'default' : 'ghost'}
                            aria-pressed={view === item.name}
                            onClick={() => changeView(item.name)}
                        >
                            {item.label}
                        </Button>
                    ))}
                </div>

                <Select value={vehicleFilter} onValueChange={setVehicleFilter}>
                    <SelectTrigger className="w-52" aria-label="Filtrer par véhicule">
                        <CarFront aria-hidden className="size-4 text-muted-foreground" />
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Tous les véhicules</SelectItem>
                        {vehicles.map((vehicle) => (
                            <SelectItem key={vehicle.id} value={String(vehicle.id)}>
                                {vehicle.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Select value={colorBy} onValueChange={(value) => setColorBy(value as 'status' | 'vehicle')}>
                    <SelectTrigger className="w-44" aria-label="Couleur des réservations">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="status">Couleur par statut</SelectItem>
                        <SelectItem value="vehicle">Couleur par véhicule</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {/* Legende */}
            <ul className="flex flex-wrap gap-x-5 gap-y-2 text-xs text-muted-foreground" aria-label="Légende">
                {colorBy === 'status'
                    ? (Object.keys(STATUS_COLORS) as Status[]).map((status) => (
                          <li key={status} className="flex items-center gap-1.5">
                              <span aria-hidden className="size-2.5 rounded-sm" style={{ background: STATUS_COLORS[status].bg }} />
                              {statusLabels[status]}
                          </li>
                      ))
                    : vehicles.map((vehicle) => (
                          <li key={vehicle.id} className="flex items-center gap-1.5">
                              <span aria-hidden className="size-2.5 rounded-sm" style={{ background: vehicleColor.get(vehicle.id) }} />
                              {vehicle.name}
                          </li>
                      ))}
            </ul>

            {error && (
                <div role="alert" className="flex flex-wrap items-center gap-3 rounded-lg border border-destructive/50 bg-destructive/10 p-3">
                    <AlertTriangle aria-hidden className="size-4 text-destructive" />
                    <span className="flex-1">Impossible de charger les réservations.</span>
                    <Button type="button" size="sm" variant="outline" onClick={() => api()?.refetchEvents()}>
                        <RefreshCw aria-hidden /> Réessayer
                    </Button>
                </div>
            )}

            <div className="relative overflow-hidden rounded-xl border border-border bg-card p-2">
                {!loadedOnce && !error && (
                    <div className="absolute inset-0 z-10 grid grid-cols-7 gap-1 bg-card p-3" aria-hidden>
                        {Array.from({ length: 35 }, (_, index) => (
                            <Skeleton key={index} className="h-24" />
                        ))}
                    </div>
                )}

                <FullCalendar
                    ref={calendarRef}
                    plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
                    locale={frLocale}
                    initialView={view}
                    headerToolbar={false}
                    height={view === 'dayGridMonth' ? 'auto' : 700}
                    events={fetchEvents}
                    loading={setLoading}
                    datesSet={onDatesSet}
                    eventsSet={(events) => setEventCount(events.length)}
                    eventClick={onEventClick}
                    dateClick={onDateClick}
                    allDayText="Journée"
                    nowIndicator
                    scrollTime="07:00:00"
                    slotDuration="00:30:00"
                    dayMaxEvents={3}
                    eventDisplay="block"
                    eventTimeFormat={{ hour: '2-digit', minute: '2-digit', meridiem: false }}
                    slotLabelFormat={{ hour: '2-digit', minute: '2-digit', meridiem: false }}
                />

                {loadedOnce && !loading && !error && eventCount === 0 && (
                    <p className="pointer-events-none absolute inset-x-0 top-1/2 mx-auto w-fit -translate-y-1/2 rounded-lg border border-border bg-card px-4 py-2 text-muted-foreground shadow-lg">
                        Aucune réservation sur cette période{vehicleFilter !== 'all' ? ' pour ce véhicule' : ''}.
                    </p>
                )}
            </div>

            <ReservationSheet
                reservation={selected}
                statusLabels={statusLabels}
                updating={updating}
                onClose={() => setSelected(null)}
                onConfirm={() => updateStatus('confirmed')}
                onCancel={() => setConfirmCancel(true)}
            />

            <Dialog open={confirmCancel} onOpenChange={setConfirmCancel}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Annuler cette réservation ?</DialogTitle>
                        <DialogDescription>
                            {selected && (
                                <>
                                    {selected.extendedProps.vehicleName} pour {selected.extendedProps.customerName},{' '}
                                    {describePeriod(selected)}. Le créneau redeviendra disponible à la réservation.
                                </>
                            )}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Garder
                            </Button>
                        </DialogClose>
                        <Button type="button" variant="destructive" disabled={updating !== null} onClick={() => updateStatus('cancelled')}>
                            {updating === 'cancelled' && <Loader2 aria-hidden className="animate-spin" />}
                            Annuler la réservation
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

function ReservationSheet({
    reservation,
    statusLabels,
    updating,
    onClose,
    onConfirm,
    onCancel,
}: {
    reservation: ReservationEvent | null;
    statusLabels: Record<Status, string>;
    updating: Status | null;
    onClose: () => void;
    onConfirm: () => void;
    onCancel: () => void;
}) {
    const details = reservation?.extendedProps;
    const status = details?.status;

    return (
        <Sheet open={reservation !== null} onOpenChange={(open) => !open && onClose()}>
            <SheetContent className="w-full overflow-y-auto sm:max-w-md">
                {reservation && details && status && (
                    <>
                        <SheetHeader>
                            <div className="flex items-center gap-2">
                                <Badge style={{ background: STATUS_COLORS[status].bg, color: STATUS_COLORS[status].text }}>
                                    {statusLabels[status]}
                                </Badge>
                                <span className="text-xs text-muted-foreground">Réf. #{reservation.id}</span>
                            </div>
                            <SheetTitle className="text-lg">{details.vehicleName ?? 'Véhicule supprimé'}</SheetTitle>
                            <SheetDescription>{details.serviceType}</SheetDescription>
                        </SheetHeader>

                        <div className="grid gap-5 px-4 text-sm">
                            <DetailBlock icon={CalendarClock} title="Période">
                                <p className="first-letter:uppercase">{describePeriod(reservation)}</p>
                                <p className="text-muted-foreground">
                                    {details.days} jour(s) · estimation {formatPrice(details.estimatedTotal)}
                                </p>
                            </DetailBlock>

                            <DetailBlock icon={MapPin} title="Trajet">
                                <dl className="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                                    <dt className="text-muted-foreground">Départ</dt>
                                    <dd>{details.pickupLocation}</dd>
                                    <dt className="text-muted-foreground">Destination</dt>
                                    <dd>{details.destination ?? <span className="text-muted-foreground">Non précisée</span>}</dd>
                                </dl>
                            </DetailBlock>

                            <DetailBlock icon={Users} title="Passagers">
                                <p>
                                    {details.passengers ? (
                                        `${details.passengers} passager${details.passengers > 1 ? 's' : ''}`
                                    ) : (
                                        <span className="text-muted-foreground">Non précisé</span>
                                    )}
                                </p>
                            </DetailBlock>

                            <DetailBlock icon={Phone} title="Client">
                                <p className="font-medium">{details.customerName}</p>
                                <a className="block text-primary hover:underline" href={`tel:${details.customerPhone.replace(/\s/g, '')}`}>
                                    {details.customerPhone}
                                </a>
                                {details.customerEmail && (
                                    <a className="flex items-center gap-1.5 text-primary hover:underline" href={`mailto:${details.customerEmail}`}>
                                        <Mail aria-hidden className="size-3.5" /> {details.customerEmail}
                                    </a>
                                )}
                            </DetailBlock>

                            {details.message && (
                                <DetailBlock icon={MessageSquare} title="Message">
                                    <p className="whitespace-pre-line rounded-md bg-muted p-3">{details.message}</p>
                                </DetailBlock>
                            )}

                            {details.createdAt && <p className="text-xs text-muted-foreground">Demande reçue le {details.createdAt}</p>}
                        </div>

                        <SheetFooter className="border-t border-border">
                            {status === 'pending' && (
                                <Button type="button" disabled={updating !== null} onClick={onConfirm}>
                                    {updating === 'confirmed' ? <Loader2 aria-hidden className="animate-spin" /> : <Check aria-hidden />}
                                    Confirmer la réservation
                                </Button>
                            )}
                            {(status === 'pending' || status === 'confirmed') && (
                                <Button type="button" variant="outline" disabled={updating !== null} onClick={onCancel} className="text-destructive">
                                    <X aria-hidden /> Annuler la réservation
                                </Button>
                            )}
                            <Button asChild variant="ghost">
                                <a href={details.adminUrl}>
                                    <ExternalLink aria-hidden /> Ouvrir la fiche complète
                                </a>
                            </Button>
                        </SheetFooter>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}

function DetailBlock({
    icon: Icon,
    title,
    children,
}: {
    icon: typeof MapPin;
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="grid grid-cols-[20px_1fr] gap-x-3 gap-y-1">
            <Icon aria-hidden className={cn('mt-0.5 size-4 text-muted-foreground')} />
            <div className="grid gap-1">
                <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{title}</h3>
                {children}
            </div>
        </section>
    );
}
