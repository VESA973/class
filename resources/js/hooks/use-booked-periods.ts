import { useCallback, useEffect, useState } from 'react';
import type { Period } from '@/lib/booking';

type State =
    | { status: 'idle'; periods: Period[] }
    | { status: 'loading'; periods: Period[] }
    | { status: 'ready'; periods: Period[] }
    | { status: 'error'; periods: Period[] };

/** Charge les periodes reservees d'un vehicule ; urlTemplate contient "__VEHICLE__". */
export function useBookedPeriods(urlTemplate: string, vehicleId: string | undefined) {
    const [state, setState] = useState<State>({ status: 'idle', periods: [] });
    const [reloadKey, setReloadKey] = useState(0);

    useEffect(() => {
        if (!vehicleId) {
            setState({ status: 'idle', periods: [] });
            return;
        }

        const controller = new AbortController();
        setState((previous) => ({ status: 'loading', periods: previous.periods }));

        fetch(urlTemplate.replace('__VEHICLE__', encodeURIComponent(vehicleId)), {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data: { periods: Period[] } = await response.json();
                setState({ status: 'ready', periods: data.periods });
            })
            .catch((error: unknown) => {
                if (error instanceof DOMException && error.name === 'AbortError') {
                    return;
                }

                setState({ status: 'error', periods: [] });
            });

        return () => controller.abort();
    }, [urlTemplate, vehicleId, reloadKey]);

    const reload = useCallback(() => setReloadKey((key) => key + 1), []);

    return { ...state, reload };
}
