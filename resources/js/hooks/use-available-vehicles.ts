import { useEffect, useState } from 'react';

/**
 * Identifiants des vehicules libres sur une periode ("2026-10-01T09:00").
 * null tant que la periode n'est pas complete ou pendant le chargement.
 */
export function useAvailableVehicles(url: string, start: string | null, end: string | null): Set<number> | null {
    const [available, setAvailable] = useState<Set<number> | null>(null);

    useEffect(() => {
        setAvailable(null);

        if (!start || !end) {
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            const params = new URLSearchParams({ start_at: start, end_at: end });

            fetch(`${url}?${params.toString()}`, { headers: { Accept: 'application/json' }, signal: controller.signal })
                .then((response) => (response.ok ? response.json() : Promise.reject(new Error(`HTTP ${response.status}`))))
                .then((data: { vehicles: { id: number }[] }) => setAvailable(new Set(data.vehicles.map((vehicle) => vehicle.id))))
                // Indication facultative : en cas d'erreur, tous les vehicules restent proposes.
                .catch(() => undefined);
        }, 300);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [url, start, end]);

    return available;
}
