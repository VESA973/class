import { useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { addDays, addMonths, isAfter, isBefore, startOfDay } from 'date-fns';
import { fr } from 'react-day-picker/locale';
import { motion } from 'framer-motion';
import { CalendarDays, MapPin, Search } from 'lucide-react';
import { cn } from 'cn';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TIME_SLOTS, combine, toApiDateTime } from '@/lib/booking';

type Props = { action: string };

const schema = z
    .object({
        pickup: z.string().trim().max(180, 'Ce champ est trop long.'),
        startDate: z.date({ error: 'Date de départ requise.' }),
        startTime: z.string(),
        endDate: z.date({ error: 'Date de retour requise.' }),
        endTime: z.string(),
    })
    .superRefine((values, ctx) => {
        const start = combine(values.startDate, values.startTime);
        const end = combine(values.endDate, values.endTime);

        if (start < new Date()) {
            ctx.addIssue({ code: 'custom', path: ['startDate'], message: 'Départ déjà passé.' });
        }

        if (end <= start) {
            ctx.addIssue({ code: 'custom', path: ['endDate'], message: 'Retour avant le départ.' });
        }
    });

type Values = z.infer<typeof schema>;

/* Champs "verre depoli" sur la photo du hero : texte blanc, contraste AA sur le degrade sombre. */
const glassField =
    'h-11 border-white/25 bg-white/10 text-white placeholder:text-white/65 hover:bg-white/15 focus-visible:border-white/60 focus-visible:ring-white/30 dark:bg-white/10 dark:hover:bg-white/15';

export default function HeroSearch({ action }: Props) {
    const [submitting, setSubmitting] = useState(false);
    const form = useForm<Values>({
        resolver: zodResolver(schema),
        defaultValues: { pickup: '', startTime: '10:00', endTime: '10:00' },
    });
    const startDate = useWatch({ control: form.control, name: 'startDate' });
    const today = startOfDay(new Date());

    function onSubmit(values: Values) {
        setSubmitting(true);
        const params = new URLSearchParams({
            start: toApiDateTime(combine(values.startDate, values.startTime)),
            end: toApiDateTime(combine(values.endDate, values.endTime)),
        });

        if (values.pickup) {
            params.set('pickup', values.pickup);
        }

        window.location.assign(`${action}?${params.toString()}`);
    }

    return (
        <motion.div
            initial={{ opacity: 0, y: 24 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.15, ease: [0.2, 0.7, 0.2, 1] }}
            className="rounded-2xl border border-white/15 bg-black/45 p-4 text-white shadow-2xl shadow-black/40 backdrop-blur-xl sm:p-5"
        >
            <Form {...form}>
                {/* Barre horizontale sur grand ecran, carte empilee sur mobile */}
                <form
                    onSubmit={form.handleSubmit(onSubmit)}
                    noValidate
                    aria-label="Rechercher un véhicule"
                    className="grid gap-3 md:grid-cols-2 md:gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)_minmax(0,1fr)_auto] xl:items-start"
                >
                    <FormField
                        control={form.control}
                        name="pickup"
                        render={({ field }) => (
                            <FormItem className="md:col-span-2 xl:col-span-1">
                                <FormLabel className="text-white data-[error=true]:text-red-200">Lieu de prise en charge</FormLabel>
                                <div className="relative">
                                    <MapPin aria-hidden className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-white/70" />
                                    <FormControl>
                                        <Input {...field} placeholder="Paris, aéroport, hôtel…" autoComplete="street-address" className={cn(glassField, 'pl-9')} />
                                    </FormControl>
                                </div>
                                <FormMessage className="text-red-200" />
                            </FormItem>
                        )}
                    />

                    <DateTimeRow
                        form={form}
                        dateName="startDate"
                        timeName="startTime"
                        label="Départ"
                        disabled={(date) => isBefore(date, today)}
                        onPicked={(date) => {
                            // Retour propose par defaut : le lendemain (location d'une journee).
                            const end = form.getValues('endDate');
                            if (!end || !isAfter(end, date)) {
                                form.setValue('endDate', addDays(date, 1), { shouldValidate: form.formState.isSubmitted });
                            }
                        }}
                    />
                    <DateTimeRow
                        form={form}
                        dateName="endDate"
                        timeName="endTime"
                        label="Retour"
                        disabled={(date) => isBefore(date, startDate ?? today)}
                        defaultMonth={startDate}
                    />

                    <Button type="submit" size="lg" disabled={submitting} className="h-11 bg-white px-6 md:col-span-2 xl:col-span-1 xl:mt-[22px] text-[#101820] hover:bg-white/90 dark:bg-white dark:text-[#101820] dark:hover:bg-white/90">
                        <Search aria-hidden className="size-4" /> Rechercher
                    </Button>
                </form>
            </Form>
        </motion.div>
    );
}

function DateTimeRow({
    form,
    dateName,
    timeName,
    label,
    disabled,
    defaultMonth,
    onPicked,
}: {
    form: ReturnType<typeof useForm<Values>>;
    dateName: 'startDate' | 'endDate';
    timeName: 'startTime' | 'endTime';
    label: string;
    disabled: (date: Date) => boolean;
    defaultMonth?: Date;
    onPicked?: (date: Date) => void;
}) {
    const [open, setOpen] = useState(false);
    const today = startOfDay(new Date());

    return (
        <div className="grid grid-cols-[minmax(0,1fr)_96px] items-start gap-2">
            <FormField
                control={form.control}
                name={dateName}
                render={({ field }) => (
                    <FormItem>
                        <FormLabel className="text-white data-[error=true]:text-red-200">{label}</FormLabel>
                        <Popover open={open} onOpenChange={setOpen}>
                            <PopoverTrigger asChild>
                                <FormControl>
                                    <Button type="button" variant="outline" className={cn(glassField, 'w-full min-w-0 justify-start overflow-hidden font-normal', !field.value && 'text-white/65')}>
                                        <CalendarDays aria-hidden className="size-4" />
                                        <span className="truncate">
                                            {field.value
                                                ? field.value.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })
                                                : 'Choisir une date'}
                                        </span>
                                    </Button>
                                </FormControl>
                            </PopoverTrigger>
                            <PopoverContent className="w-auto p-0" align="start">
                                <Calendar
                                    mode="single"
                                    locale={fr}
                                    selected={field.value}
                                    defaultMonth={field.value ?? defaultMonth ?? today}
                                    startMonth={today}
                                    endMonth={addMonths(today, 12)}
                                    disabled={disabled}
                                    onSelect={(date) => {
                                        field.onChange(date);
                                        setOpen(false);
                                        if (date) onPicked?.(date);
                                    }}
                                />
                            </PopoverContent>
                        </Popover>
                        <FormMessage className="text-red-200" />
                    </FormItem>
                )}
            />
            <FormField
                control={form.control}
                name={timeName}
                render={({ field }) => (
                    <FormItem>
                        <FormLabel className="text-white">Heure</FormLabel>
                        <Select value={field.value} onValueChange={field.onChange}>
                            <FormControl>
                                <SelectTrigger className={cn(glassField, 'w-full data-[size=default]:h-11 [&_svg]:text-white/70!')}>
                                    <SelectValue />
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
                    </FormItem>
                )}
            />
        </div>
    );
}
