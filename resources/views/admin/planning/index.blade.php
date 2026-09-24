@extends('admin.layout')

@section('title', 'Planning')

@section('content')
    @vite(['resources/css/islands.css', 'resources/js/islands.tsx'])

    <style>
        /* Couleurs de l'admin (or #d6a95f sur noir) pour les composants shadcn de cette page. */
        :root.dark {
            --ui-background: #0b0b0b;
            --ui-card: #151412;
            --ui-popover: #151412;
            --ui-secondary: #1c1a17;
            --ui-muted: #1c1a17;
            --ui-accent: #262320;
            --ui-muted-foreground: #b9afa4;
            --ui-primary: #d6a95f;
            --ui-primary-foreground: #0b0b0b;
            --ui-ring: #d6a95f;
            --ui-border: rgba(255, 255, 255, 0.12);
            --ui-input: rgba(255, 255, 255, 0.16);
        }

        /* admin.css met en forme tous les tableaux et labels : on l'annule pour le calendrier. */
        .planning-root :is(th, td) {
            padding: 0;
            text-align: inherit;
            vertical-align: top;
            color: inherit;
            font-size: inherit;
            text-transform: none;
        }

        .planning-root td span {
            display: inline;
            color: inherit;
            font-size: inherit;
        }

        .planning-root .fc {
            --fc-border-color: rgba(255, 255, 255, 0.1);
            --fc-page-bg-color: #151412;
            --fc-neutral-bg-color: #1c1a17;
            --fc-today-bg-color: rgba(214, 169, 95, 0.1);
            --fc-now-indicator-color: #d6a95f;
            --fc-event-border-color: transparent;
            font-size: 13px;
        }

        .planning-root .fc .fc-col-header-cell-cushion,
        .planning-root .fc .fc-daygrid-day-number,
        .planning-root .fc .fc-timegrid-slot-label-cushion {
            color: #b9afa4;
            padding: 6px 8px;
            text-transform: capitalize;
        }

        .planning-root .fc .fc-event {
            cursor: pointer;
        }

        .planning-root .fc .fc-event.is-cancelled {
            opacity: 0.5;
        }

        .planning-root .fc .fc-event.is-cancelled .fc-event-title {
            text-decoration: line-through;
        }
    </style>

    <div class="page-head">
        <div>
            <p class="eyebrow">Réservations</p>
            <h1>Planning</h1>
        </div>
    </div>

    <div class="planning-root" data-island="Planning" data-props="{{ json_encode($props) }}">
        <noscript>Le planning nécessite JavaScript. <a href="{{ route('admin.reservations.index') }}">Voir la liste des réservations</a>.</noscript>
    </div>
@endsection
