<ol class="timeline">
    @foreach ($events as $event)
        <li>
            <span class="timeline-dot" aria-hidden="true"></span>
            <div>
                <p>{{ $event->description }}</p>
                <small>{{ $event->created_at->timezone(config('app.local_timezone'))->format('d/m/Y à H:i') }}{{ $event->user ? ' · '.$event->user->name : ' · automatique' }}</small>
            </div>
        </li>
    @endforeach
</ol>
