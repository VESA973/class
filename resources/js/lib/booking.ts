import { addDays, format, isBefore, parseISO, startOfDay } from 'date-fns';

/** Periode reservee renvoyee par l'API, en heure locale sans fuseau ("2026-10-01T09:00"). */
export type Period = { start_at: string; end_at: string };

export type BookingVehicle = {
    id: number;
    name: string;
    category: string;
    daily_price: number;
    image: string;
    fuel_type: string;
    transmission: string;
    horsepower: number | null;
    with_chauffeur: boolean;
};

export const DATETIME_FORMAT = "yyyy-MM-dd'T'HH:mm";

export function toApiDateTime(date: Date): string {
    return format(date, DATETIME_FORMAT);
}

/** parseISO lit une date sans fuseau comme une heure locale : c'est voulu. */
export function fromApiDateTime(value: string): Date {
    return parseISO(value);
}

export function combine(day: Date, time: string): Date {
    const [hours, minutes] = time.split(':').map(Number);
    const result = startOfDay(day);
    result.setHours(hours, minutes, 0, 0);

    return result;
}

export function dayKey(date: Date): string {
    return format(date, 'yyyy-MM-dd');
}

export function formatDateTime(date: Date): string {
    return format(date, "dd/MM/yyyy 'à' HH:mm");
}

/** Meme logique que ReservationAvailability::describe() cote serveur. */
export function describePeriod(period: Period): string {
    const start = fromApiDateTime(period.start_at);
    const end = fromApiDateTime(period.end_at);
    const midnight = (date: Date) => date.getHours() === 0 && date.getMinutes() === 0;

    if (midnight(start) && midnight(end)) {
        const lastDay = addDays(end, -1);
        const day = (date: Date) => format(date, 'dd/MM/yyyy');

        return dayKey(lastDay) === dayKey(start) ? `le ${day(start)}` : `du ${day(start)} au ${day(lastDay)}`;
    }

    return `du ${formatDateTime(start)} au ${formatDateTime(end)}`;
}

/**
 * Classe chaque jour touche par une reservation :
 * - "full" : le jour entier (00:00 -> 24:00) est occupe -> desactive dans le calendrier ;
 * - "partial" : seulement une partie de la journee -> reste selectionnable selon l'heure.
 */
export function classifyDays(periods: Period[]): { full: Set<string>; partial: Set<string> } {
    const full = new Set<string>();
    const partial = new Set<string>();

    for (const period of periods) {
        const start = fromApiDateTime(period.start_at);
        const end = fromApiDateTime(period.end_at);

        for (let day = startOfDay(start); isBefore(day, end); day = addDays(day, 1)) {
            const nextDay = addDays(day, 1);
            const coversWholeDay = start <= day && end >= nextDay;

            (coversWholeDay ? full : partial).add(dayKey(day));
        }
    }

    for (const key of full) {
        partial.delete(key);
    }

    return { full, partial };
}

/** Periodes qui touchent l'intervalle [from 00:00, to 24:00[. */
export function periodsTouchingDays(periods: Period[], from: Date, to: Date): Period[] {
    const rangeStart = startOfDay(from);
    const rangeEnd = addDays(startOfDay(to), 1);

    return periods.filter(
        (period) => fromApiDateTime(period.start_at) < rangeEnd && fromApiDateTime(period.end_at) > rangeStart,
    );
}

export function rentalDays(start: Date, end: Date): number {
    const minutes = (end.getTime() - start.getTime()) / 60000;

    return Math.max(1, Math.ceil(minutes / 1440));
}

export function formatPrice(value: number): string {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(value);
}

/** Creneaux de 30 minutes proposes pour le depart et le retour (memes bornes que ReservationAvailability::OPENING/CLOSING_MINUTES). */
export const TIME_SLOTS: string[] = Array.from({ length: (22 - 7) * 2 + 1 }, (_, index) => {
    const minutes = 7 * 60 + index * 30;

    return `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
});
