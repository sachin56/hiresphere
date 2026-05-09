import { useEffect, useRef, useState } from 'react';
import { useParams, Link, useNavigate, useSearchParams } from 'react-router-dom';
import { api, bookingApi, paymentApi } from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';

const STATUS_BADGE = {
  pending:   'badge-warning',
  accepted:  'badge-success',
  completed: 'badge-neutral',
  cancelled: 'badge-error',
  rejected:  'badge-error',
};

const TYPE_ICON = {
  dsa: '💻', system_design: '🏗️', behavioral: '🗣️', mixed: '🔀',
};

function fmtLong(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

function StatusLabel(s) {
  const map = { pending: 'Pending', accepted: 'Accepted', completed: 'Completed', cancelled: 'Cancelled', rejected: 'Rejected' };
  return map[s] || s;
}

export default function BookingDetailPage() {
  const { id } = useParams();
  const { isCandidate, isInterviewer } = useAuth();
  const navigate = useNavigate();

  const [searchParams, setSearchParams] = useSearchParams();

  const [booking,   setBooking]   = useState(null);
  const [loading,   setLoading]   = useState(true);
  const [acting,    setActing]    = useState(false);
  const [error,     setError]     = useState(null);
  const [roomUrl,   setRoomUrl]   = useState('');
  const [urlSaving, setUrlSaving] = useState(false);
  const [paying,        setPaying]        = useState(false);
  const [verifying,     setVerifying]     = useState(false);

  const paymentResult = searchParams.get('payment');
  const pollRef = useRef(null);
  const sessionId = searchParams.get('session_id');

  useEffect(() => {
    bookingApi.get(id)
      .then(b => {
        setBooking(b);
        setRoomUrl(b?.webrtc_room_url || '');
        if (paymentResult === 'success' && b.payment_status !== 'paid') {
          verifyPayment();
        }
      })
      .catch(() => setError('Booking not found.'))
      .finally(() => setLoading(false));

    return () => clearTimeout(pollRef.current);
  }, [id]); // eslint-disable-line react-hooks/exhaustive-deps

  async function verifyPayment() {
    setVerifying(true);
    try {
      // Ask the backend to confirm with Stripe and update the DB immediately
      const updated = await paymentApi.verify(id, sessionId);
      setBooking(updated);
      setVerifying(false);
    } catch {
      // Stripe said not paid yet — fall back to polling
      pollForPaid(0);
    }
  }

  function pollForPaid(attempt) {
    if (attempt >= 6) { setVerifying(false); return; }
    pollRef.current = setTimeout(async () => {
      try {
        const b = await bookingApi.get(id);
        setBooking(b);
        if (b.payment_status === 'paid') {
          setVerifying(false);
        } else {
          pollForPaid(attempt + 1);
        }
      } catch {
        setVerifying(false);
      }
    }, 3000);
  }

  async function action(fn, redirectAfter) {
    setActing(true); setError(null);
    try {
      await fn();
      if (redirectAfter) { navigate(redirectAfter); return; }
      const updated = await bookingApi.get(id);
      setBooking(updated);
    } catch (e) {
      setError(e.data?.message || e.message);
    } finally {
      setActing(false);
    }
  }

  async function saveRoomUrl(e) {
    e.preventDefault(); setUrlSaving(true); setError(null);
    try {
      const updated = await api.put(`/bookings/${id}/room-url`, { room_url: roomUrl });
      setBooking(updated);
    } catch (e) {
      setError(e.data?.message || e.message);
    } finally { setUrlSaving(false); }
  }

  async function handlePay() {
    setPaying(true); setError(null);
    try {
      const { url } = await paymentApi.createCheckout(id);
      window.location.href = url;
    } catch (e) {
      setError(e.data?.error || e.data?.message || 'Payment could not be started. Please try again.');
      setPaying(false);
    }
  }

  async function handleJoin() {
    try {
      const res = await bookingApi.join(id);
      const url = res?.room_url || booking.webrtc_room_url;
      if (url) window.open(url, '_blank');
      else setError('Session room not ready yet.');
    } catch (e) { setError(e.data?.message || e.message); }
  }

  if (loading) return <div className="spinner-wrap"><div className="spinner" /></div>;
  if (error && !booking) return (
    <div style={{ maxWidth: 760 }}>
      <div className="alert alert-error">{error}</div>
      <Link to="/bookings" className="back-link">← Back</Link>
    </div>
  );
  if (!booking) return null;

  const other      = isCandidate ? booking.interviewer : booking.candidate;
  const typeIcon   = TYPE_ICON[booking.interview_type] || '📋';
  const paymentRequired = booking.amount > 0 && booking.payment_status !== 'paid';
  const canJoin     = booking.status === 'accepted' && booking.webrtc_room_url && (!isCandidate || !paymentRequired);
  const canAccept   = isInterviewer && booking.status === 'pending';
  const canReject   = isInterviewer && booking.status === 'pending';
  const canCancel   = booking.status === 'pending';
  const canComplete = isInterviewer && booking.status === 'accepted';
  const canPay      = isCandidate && booking.status === 'accepted' && paymentRequired;

  return (
    <div style={{ maxWidth: 820 }}>
      <Link to="/bookings" className="back-link" style={{ display: 'inline-flex', alignItems: 'center', gap: 6, marginBottom: 20, fontSize: '.85rem', color: 'var(--text-muted)' }}>
        ← Back to sessions
      </Link>

      {error && <div className="alert alert-error" style={{ marginBottom: 14 }}>{error}</div>}

      {paymentResult === 'success' && (
        <div className={`alert ${verifying ? 'alert-info' : 'alert-success'}`} style={{ marginBottom: 14, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <span>
            {verifying
              ? '⏳ Verifying payment, please wait…'
              : '✅ Payment completed! Your session is confirmed.'}
          </span>
          {!verifying && (
            <button style={{ background: 'none', border: 'none', cursor: 'pointer', fontWeight: 700, fontSize: '1rem' }} onClick={() => setSearchParams({})}>×</button>
          )}
        </div>
      )}
      {paymentResult === 'cancelled' && (
        <div className="alert alert-warning" style={{ marginBottom: 14, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <span>Payment was cancelled. You can try again when ready.</span>
          <button style={{ background: 'none', border: 'none', cursor: 'pointer', fontWeight: 700, fontSize: '1rem' }} onClick={() => setSearchParams({})}>×</button>
        </div>
      )}

      {/* Hero */}
      <div className="bk-hero">
        <div className="bk-hero-top">
          <div>
            <div className="bk-hero-type-row">
              <div className="bk-hero-icon">{typeIcon}</div>
              <div className="bk-hero-type">{booking.interview_type?.replace(/_/g, ' ') || 'Interview'}</div>
            </div>
            <div className="bk-hero-with">
              with <strong>{other?.name || '—'}</strong>
              {booking.interview_domain && ` · ${booking.interview_domain}`}
            </div>
          </div>
          <div className="bk-hero-right">
            <span className={`badge badge-lg ${STATUS_BADGE[booking.status] || 'badge-neutral'}`}>
              {StatusLabel(booking.status)}
            </span>
          </div>
        </div>

        {booking.scheduled_at && (
          <div className="bk-hero-datetime">
            📅 {fmtLong(booking.scheduled_at)}
            {booking.duration_minutes && ` · ${booking.duration_minutes} min`}
          </div>
        )}
      </div>

      {/* Info grid */}
      <div className="bk-info-grid">
        <div className="bk-info-cell">
          <div className="bk-info-label">Type</div>
          <div className="bk-info-value" style={{ textTransform: 'capitalize' }}>
            {booking.interview_type?.replace(/_/g, ' ') || '—'}
          </div>
        </div>
        <div className="bk-info-cell">
          <div className="bk-info-label">Duration</div>
          <div className="bk-info-value">{booking.duration_minutes ? `${booking.duration_minutes} min` : '—'}</div>
        </div>
        <div className="bk-info-cell">
          <div className="bk-info-label">Domain</div>
          <div className="bk-info-value">{booking.interview_domain || '—'}</div>
        </div>
        <div className="bk-info-cell">
          <div className="bk-info-label">Amount</div>
          <div className="bk-info-value">{booking.amount ? `${booking.currency?.toUpperCase() || 'USD'} ${Number(booking.amount).toFixed(2)}` : '—'}</div>
        </div>
        <div className="bk-info-cell">
          <div className="bk-info-label">Payment</div>
          <div className="bk-info-value">
            <span className={`badge ${
              booking.payment_status === 'paid'     ? 'badge-success' :
              booking.payment_status === 'refunded' ? 'badge-neutral' :
              booking.payment_status === 'failed'   ? 'badge-error'   : 'badge-warning'
            }`}>
              {booking.payment_status || 'pending'}
            </span>
          </div>
        </div>
      </div>

      {/* Candidate notes */}
      {booking.candidate_notes && (
        <div className="bk-section">
          <div className="bk-section-title">Candidate Notes</div>
          <p className="bk-notes-text">{booking.candidate_notes}</p>
        </div>
      )}

      {/* Payment required callout */}
      {isCandidate && booking.status === 'accepted' && paymentRequired && (
        <div className="alert alert-warning" style={{ marginBottom: 16, display: 'flex', alignItems: 'center', gap: 12 }}>
          <span style={{ fontSize: '1.2rem' }}>💳</span>
          <div>
            <strong>Payment required to join this session.</strong>
            <div style={{ fontSize: '.85rem', marginTop: 2 }}>
              Complete your payment of {booking.currency?.toUpperCase() || 'USD'} {Number(booking.amount).toFixed(2)} to unlock the session room.
            </div>
          </div>
        </div>
      )}

      {/* Action bar */}
      <div className="bk-action-bar">
        {canPay && (
          <button className="btn btn-primary" disabled={paying} onClick={handlePay} style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
            💳 {paying ? 'Redirecting to Stripe…' : `Pay ${booking.currency?.toUpperCase() || 'USD'} ${Number(booking.amount).toFixed(2)}`}
          </button>
        )}
        {canJoin && (
          <button className="bk-btn-join" onClick={handleJoin}>
            🎥 Join Live Session
          </button>
        )}
        {canAccept && (
          <button className="bk-btn-accept" disabled={acting} onClick={() => action(() => bookingApi.accept(id))}>
            ✓ {acting ? 'Accepting…' : 'Accept'}
          </button>
        )}
        {canReject && (
          <button className="bk-btn-reject" disabled={acting} onClick={() => action(() => bookingApi.reject(id))}>
            ✕ {acting ? 'Rejecting…' : 'Reject'}
          </button>
        )}
        {canCancel && (
          <button className="btn btn-ghost" disabled={acting} onClick={() => action(() => bookingApi.cancel(id))}>
            {acting ? 'Cancelling…' : 'Cancel Booking'}
          </button>
        )}
        {isCandidate && booking.status === 'accepted' && (
          <Link to={`/submissions/new?booking_id=${booking.id}`} className="btn btn-outline">
            📄 Submit Code
          </Link>
        )}
        {other?.id && (
          <Link to={`/messages/${other.id}`} className="btn btn-outline">
            💬 Message {other.name?.split(' ')[0]}
          </Link>
        )}
        {canComplete && (
          <button className="btn btn-success" disabled={acting} onClick={() => action(() => bookingApi.complete(id))}>
            {acting ? 'Completing…' : '✓ Complete Session'}
          </button>
        )}
        {booking.status === 'completed' && isInterviewer && !booking.evaluation_report && (
          <Link to={`/bookings/${id}/evaluate`} className="btn btn-primary">Write Evaluation</Link>
        )}
      </div>

      {/* Meeting link — interviewer sets, candidate sees */}
      {isInterviewer && booking.status === 'accepted' && (
        <div className="bk-section">
          <div className="bk-section-title">Meeting Link</div>
          {booking.webrtc_room_url && (
            <div className="bk-meeting-box" style={{ marginBottom: 14 }}>
              <span>🔗</span>
              <a href={booking.webrtc_room_url} target="_blank" rel="noreferrer" className="bk-meeting-url">
                {booking.webrtc_room_url}
              </a>
            </div>
          )}
          <form onSubmit={saveRoomUrl} style={{ display: 'flex', gap: 10, alignItems: 'flex-end' }}>
            <div className="form-group" style={{ flex: 1, marginBottom: 0 }}>
              <label style={{ fontSize: '.82rem', fontWeight: 600, color: 'var(--text)', marginBottom: 6, display: 'block' }}>
                {booking.webrtc_room_url ? 'Update URL' : 'Set meeting URL'} (Zoom, Google Meet, Jitsi…)
              </label>
              <input
                className="form-control"
                type="url"
                placeholder="https://meet.google.com/xxx-xxxx-xxx"
                value={roomUrl}
                onChange={e => setRoomUrl(e.target.value)}
                required
              />
            </div>
            <button type="submit" className="btn btn-primary" disabled={urlSaving}>
              {urlSaving ? 'Saving…' : booking.webrtc_room_url ? 'Update' : 'Set URL'}
            </button>
          </form>
        </div>
      )}

      {/* Candidate: show meeting link if set */}
      {isCandidate && booking.status === 'accepted' && booking.webrtc_room_url && (
        <div className="bk-section">
          <div className="bk-section-title">Meeting Link</div>
          <div className="bk-meeting-box">
            <span>🔗</span>
            <a href={booking.webrtc_room_url} target="_blank" rel="noreferrer" className="bk-meeting-url">
              {booking.webrtc_room_url}
            </a>
          </div>
        </div>
      )}

      {/* Code Submissions */}
      {(booking.submissions?.length > 0 || isInterviewer) && (
        <div className="bk-section">
          <div className="bk-section-title">
            Code Submissions
            {isCandidate && booking.status === 'accepted' && (
              <Link to={`/submissions/new?booking_id=${booking.id}`} style={{ marginLeft: 12, fontSize: '.78rem', fontWeight: 600, color: 'var(--primary)' }}>
                + Add submission
              </Link>
            )}
          </div>

          {booking.submissions?.length > 0 ? (
            <div>
              {booking.submissions.map(s => (
                <div key={s.id} className="bk-sub-row">
                  <div className="bk-sub-icon">
                    {s.submission_type === 'github_link' ? '🔗' : '📄'}
                  </div>
                  <div className="bk-sub-info">
                    <div className="bk-sub-name">{s.title || 'Untitled'}</div>
                    {s.language && <div className="bk-sub-lang">{s.language}</div>}
                  </div>
                  <span className={`badge ${s.status === 'reviewed' ? 'badge-success' : 'badge-warning'}`}>
                    {s.status === 'reviewed' ? 'Reviewed' : 'Pending'}
                  </span>
                  <Link to={`/submissions/${s.id}`} className="sub-view-btn" style={{ marginLeft: 8 }}>
                    {isInterviewer && s.status !== 'reviewed' ? 'Review →' : 'View →'}
                  </Link>
                </div>
              ))}
            </div>
          ) : (
            <p style={{ fontSize: '.88rem', color: 'var(--text-muted)' }}>
              No submissions linked to this session yet.
              {isInterviewer && ' The candidate can submit code from their bookings page.'}
            </p>
          )}
        </div>
      )}

      {/* Evaluation report */}
      {booking.evaluation_report && (
        <div className="bk-section">
          <div className="bk-section-title">Evaluation Report</div>
          <div className="bk-eval-box">
            <div className="bk-eval-score-row">
              <div className="bk-eval-score-circle">
                {booking.evaluation_report.overall_score}
                <span className="bk-eval-score-label">/10</span>
              </div>
              <div>
                <div className="bk-eval-score-title">Overall Score</div>
                <div className="bk-eval-score-sub">Session evaluation</div>
              </div>
            </div>
            {booking.evaluation_report.feedback && (
              <p className="bk-eval-feedback">{booking.evaluation_report.feedback}</p>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
