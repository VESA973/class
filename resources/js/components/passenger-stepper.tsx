import { Minus, Plus, Users } from 'lucide-react';
import { cn } from 'cn';

import { Button } from '@/components/ui/button';

type Props = {
    value: number;
    onChange: (value: number) => void;
    min?: number;
    max?: number;
    id?: string;
    className?: string;
    buttonClassName?: string;
    'aria-describedby'?: string;
    'aria-invalid'?: boolean;
};

/** Selecteur de passagers - / + (1 a 9 par defaut). */
export function PassengerStepper({ value, onChange, min = 1, max = 9, id, className, buttonClassName, ...aria }: Props) {
    return (
        <div
            id={id}
            role="group"
            aria-describedby={aria['aria-describedby']}
            aria-invalid={aria['aria-invalid']}
            className={cn('flex h-11 items-center justify-between gap-1 rounded-md border px-1', className)}
        >
            <Button
                type="button"
                size="icon"
                variant="ghost"
                className={cn('size-9', buttonClassName)}
                aria-label="Retirer un passager"
                disabled={value <= min}
                onClick={() => onChange(Math.max(min, value - 1))}
            >
                <Minus aria-hidden />
            </Button>
            <output aria-live="polite" className="flex items-center gap-1.5 font-medium tabular-nums">
                <Users aria-hidden className="size-4 opacity-70" />
                {value}
                <span className="sr-only"> passager{value > 1 ? 's' : ''}</span>
            </output>
            <Button
                type="button"
                size="icon"
                variant="ghost"
                className={cn('size-9', buttonClassName)}
                aria-label="Ajouter un passager"
                disabled={value >= max}
                onClick={() => onChange(Math.min(max, value + 1))}
            >
                <Plus aria-hidden />
            </Button>
        </div>
    );
}
