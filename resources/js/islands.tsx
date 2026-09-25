import { StrictMode, type ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';

/*
 * Point d'entree des "iles" React : chaque page Blade garde son rendu serveur
 * et peut monter un composant React dans un element
 *   <div data-island="NomDuComposant" data-props='{"cle":"valeur"}'></div>
 * Les composants sont charges a la demande (un fichier JS par ile).
 */
type IslandLoader = () => Promise<{ default: ComponentType<any> }>;

const islands: Record<string, IslandLoader> = {
    BookingForm: () => import('@/islands/BookingForm'),
    HeroSearch: () => import('@/islands/HeroSearch'),
    Planning: () => import('@/islands/Planning'),
};

function readProps(element: HTMLElement): Record<string, unknown> {
    try {
        return element.dataset.props ? JSON.parse(element.dataset.props) : {};
    } catch (error) {
        console.error('Proprietes invalides pour une ile React', element, error);
        return {};
    }
}

async function mountIslands(): Promise<void> {
    const elements = document.querySelectorAll<HTMLElement>('[data-island]');

    // Theme sombre uniquement (les pages Blade plus anciennes n'ont pas la classe en dur).
    document.documentElement.classList.add('dark');

    await Promise.all(
        Array.from(elements).map(async (element) => {
            const name = element.dataset.island ?? '';
            const load = islands[name];

            if (!load) {
                console.error(`Ile React inconnue : "${name}"`);
                return;
            }

            const { default: Component } = await load();

            createRoot(element).render(
                <StrictMode>
                    <Component {...readProps(element)} />
                </StrictMode>,
            );
        }),
    );

    if (elements.length > 0) {
        const toasterRoot = document.createElement('div');
        document.body.appendChild(toasterRoot);
        createRoot(toasterRoot).render(<Toaster richColors position="top-center" />);
    }
}

void mountIslands();
