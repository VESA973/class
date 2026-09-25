@extends('admin.layout')

@section('title', 'Planning')

@section('content')
    @vite(['resources/css/islands.css', 'resources/js/islands.tsx'])

    <style>
        /* Les composants shadcn utilisent les couleurs sobres du site (theme.css). */

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
            --fc-page-bg-color: #18181b;
            --fc-neutral-bg-color: #27272a;
            --fc-today-bg-color: rgba(255, 255, 255, 0.05);
            --fc-now-indicator-color: #fafafa;
            --fc-event-border-color: transparent;
            font-size: 13px;
        }

        .planning-root .fc .fc-col-header-cell-cushion,
        .planning-root .fc .fc-daygrid-day-number,
        .planning-root .fc .fc-timegrid-slot-label-cushion {
            color: #a1a1aa;
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
