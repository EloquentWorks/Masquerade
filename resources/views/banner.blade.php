@masquerading
    <div style="background:#7c3aed;color:#fff;padding:12px 16px;text-align:center;font-weight:600;z-index:9999;position:relative;">
        You are currently masquerading as another user.

        <form method="POST" action="{{ route(config('masquerade.routes.name', 'masquerade.').'stop') }}" style="display:inline;margin-left:12px;">
            @csrf
            <button type="submit" style="background:#fff;color:#4c1d95;border:0;border-radius:6px;padding:6px 10px;font-weight:700;cursor:pointer;">
                Return to my account
            </button>
        </form>
    </div>
@endmasquerading
