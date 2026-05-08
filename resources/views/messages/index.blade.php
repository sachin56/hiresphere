@extends('layouts.app')
@section('title', 'Messages')
@section('page-title', 'Messages')

@section('content')
<div class="d-flex gap-3" style="height:calc(100vh - 160px)">

    {{-- Conversation list --}}
    <div class="flex-shrink-0 card stat-card d-flex flex-column overflow-hidden" style="width:272px">
        <div class="px-3 py-3 border-bottom">
            <p class="fw-semibold small mb-0">Conversations</p>
        </div>
        <div class="flex-grow-1 overflow-auto">
            @forelse($conversations ?? [] as $conv)
                @php $other = $conv['other_participant'] ?? null; @endphp
                @if($other)
                <a href="{{ route('messages.conversation', $other->id) }}"
                   class="d-flex align-items-center gap-3 px-3 py-3 text-decoration-none text-dark border-bottom {{ isset($otherUser) && $otherUser->id === $other->id ? 'bg-primary bg-opacity-10' : '' }}"
                   style="transition:background .15s">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                         style="width:36px;height:36px;background:#4f46e5;font-size:.8rem">
                        {{ strtoupper(substr($other->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="mb-0 fw-medium small text-truncate">{{ $other->name }}</p>
                        <p class="mb-0 text-muted text-truncate" style="font-size:.72rem">{{ $conv['last_message'] ?? 'Start chatting...' }}</p>
                    </div>
                </a>
                @endif
            @empty
                <div class="p-4 text-center text-muted small">
                    No conversations yet.<br>Book an interview to start messaging.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Chat area --}}
    <div class="flex-grow-1 card stat-card d-flex flex-column overflow-hidden">
        @if(isset($otherUser))
            {{-- Chat header --}}
            <div class="px-4 py-3 border-bottom d-flex align-items-center gap-3 flex-shrink-0">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                     style="width:36px;height:36px;background:#4f46e5;font-size:.8rem">
                    {{ strtoupper(substr($otherUser->name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <p class="fw-semibold small mb-0">{{ $otherUser->name }}</p>
                    <p class="text-muted mb-0 text-capitalize" style="font-size:.72rem">{{ $otherUser->role }}</p>
                </div>
            </div>

            {{-- Messages --}}
            <div class="flex-grow-1 overflow-auto px-4 py-3" id="msgContainer"></div>

            {{-- Input --}}
            <div class="px-4 py-3 border-top flex-shrink-0">
                <div class="d-flex gap-2 align-items-end">
                    <textarea id="msgInput" rows="1" class="form-control"
                              placeholder="Type a message… (Enter to send, Shift+Enter for newline)"
                              style="resize:none"></textarea>
                    <button id="sendBtn" class="btn btn-primary flex-shrink-0 px-3" aria-label="Send">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
            </div>
        @else
            <div class="flex-grow-1 d-flex align-items-center justify-content-center text-muted">
                <div class="text-center">
                    <div class="display-4 mb-3">💬</div>
                    <p class="fw-medium">Select a conversation</p>
                    <p class="small">Choose from the list to start chatting</p>
                </div>
            </div>
        @endif
    </div>

</div>

@if(isset($otherUser))
<script>
(function() {
    let messages = @json($messages ?? []);
    const authUserId = '{{ $authUser->id }}';
    const sendUrl   = '{{ route('messages.send', $otherUser->id) }}';
    const csrf      = document.querySelector('meta[name=csrf-token]').content;

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function render() {
        const container = document.getElementById('msgContainer');
        if (!container) return;
        if (messages.length === 0) {
            container.innerHTML = '<div class="text-center text-muted small py-5">No messages yet. Say hello! 👋</div>';
            return;
        }
        container.innerHTML = messages.map(msg => {
            const isOwn = String(msg.sender_id) === String(authUserId);
            const time  = new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
            return `<div class="d-flex ${isOwn ? 'justify-content-end' : 'justify-content-start'} mb-2">
                <div class="${isOwn ? 'chat-bubble-out' : 'chat-bubble-in'} px-3 py-2">
                    <p class="mb-1 small">${esc(msg.content)}</p>
                    <p class="mb-0 opacity-50" style="font-size:.65rem">${time}</p>
                </div>
            </div>`;
        }).join('');
        container.scrollTop = container.scrollHeight;
    }

    async function send() {
        const input = document.getElementById('msgInput');
        const btn   = document.getElementById('sendBtn');
        const content = input.value.trim();
        if (!content || btn.disabled) return;
        btn.disabled = true;
        try {
            const resp = await fetch(sendUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({content})
            });
            if (resp.ok) {
                const msg = await resp.json();
                messages.push(msg);
                render();
                input.value = '';
            }
        } finally {
            btn.disabled = false;
            input.focus();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        render();
        const input = document.getElementById('msgInput');
        const btn   = document.getElementById('sendBtn');
        if (input) input.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
        });
        if (btn) btn.addEventListener('click', send);
    });
})();
</script>
@endif
@endsection
