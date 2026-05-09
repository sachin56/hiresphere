import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { bookingApi } from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';

const STATUS_BADGE = {
  pending:   'badge-warning',
  accepted:  'badge-success',
  completed: 'badge-neutral',
  cancelled: 'badge-error',
  rejected:  'badge-error',
};

const CARD_COLOR = {
  pending:   'bk-card-amber',
  accepted:  'bk-card-indigo',
  completed: 'bk-card-green',
  cancelled: 'bk-card-red',
  rejected:  'bk-card-red',
};

const TYPE_ICON = {
  dsa: '💻', system_design: '🏗️', behavioral: '🗣️', mixed: '🔀',
};

const TABS = [
  { label: 'All',       value: '' },
  { label: 'Pending',   value: 'pending' },
  { label: 'Upcoming',  value: 'accepted' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
];

function initials(name = '') {
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() || '?';
}

function fmtScheduled(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function SessionCard({ booking, isCandidate }) {
  const other = isCandidate ? booking.interviewer : booking.candidate;
  const icon  = TYPE_ICON[booking.interview_type] || '📋';

  return (
    <div className={`bk-card ${CARD_COLOR[booking.status] || 'bk-card-gray'}`}>
      <div className="bk-avatar-sm">{initials(other?.name)}</div>

      <div className="bk-card-body">
        <div className="bk-card-row1">
          <span className="bk-card-name">{other?.name || '—'}</span>
          <span className={`badge ${STATUS_BADGE[booking.status] || 'badge-neutral'}`}>
            {booking.status}
          </span>
        </div>
        <div className="bk-card-row2">
          <span className="bk-card-type">{icon} {booking.interview_type?.replace(/_/g, ' ')}</span>
          {booking.scheduled_at && (
            <span className="bk-card-meta">📅 {fmtScheduled(booking.scheduled_at)}</span>
          )}
          {booking.duration_minutes && (
            <span className="bk-card-meta">⏱ {booking.duration_minutes} min</span>
          )}
        </div>
      </div>

      <Link to={`/bookings/${booking.id}`} className="bk-view-btn">View →</Link>
    </div>
  );
}

export default function BookingListPage() {
  const { isCandidate } = useAuth();
  const [bookings, setBookings] = useState([]);
  const [filter,   setFilter]   = useState('');
  const [loading,  setLoading]  = useState(true);

  useEffect(() => {
    setLoading(true);
    bookingApi.list(filter ? { status: filter } : {})
      .then(r => setBookings(r?.data || []))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [filter]);

  const upcoming  = bookings.filter(b => b.status === 'accepted').length;
  const completed = bookings.filter(b => b.status === 'completed').length;
  const pending   = bookings.filter(b => b.status === 'pending').length;

  return (
    <div>
      <div className="bk-header">
        <div>
          <h1>{isCandidate ? 'My Bookings' : 'Booking Requests'}</h1>
          <p>Manage your interview sessions</p>
        </div>
        {isCandidate && (
          <Link to="/interviewers" className="btn btn-primary">+ New Booking</Link>
        )}
      </div>

      {/* Stats strip — show when we have data */}
      {!loading && bookings.length > 0 && (
        <div className="bk-stats">
          <div className="bk-stat">
            <div className="bk-stat-num">{bookings.length}</div>
            <div className="bk-stat-cap">Total</div>
          </div>
          <div className="bk-stat">
            <div className="bk-stat-num indigo">{upcoming}</div>
            <div className="bk-stat-cap">Upcoming</div>
          </div>
          <div className="bk-stat">
            <div className="bk-stat-num green">{completed}</div>
            <div className="bk-stat-cap">Completed</div>
          </div>
          <div className="bk-stat">
            <div className="bk-stat-num amber">{pending}</div>
            <div className="bk-stat-cap">Pending</div>
          </div>
        </div>
      )}

      {/* Filter tabs */}
      <div className="bk-tabs">
        {TABS.map(t => (
          <button
            key={t.value}
            className={`bk-tab ${filter === t.value ? 'active' : ''}`}
            onClick={() => setFilter(t.value)}
          >
            {t.label}
          </button>
        ))}
      </div>

      {loading ? (
        <div className="spinner-wrap"><div className="spinner" /></div>
      ) : bookings.length === 0 ? (
        <div className="bk-empty">
          <div className="bk-empty-icon">📅</div>
          <h3>No sessions found</h3>
          <p>
            {filter
              ? `No ${filter} sessions. Try a different filter.`
              : isCandidate
                ? 'Book your first mock interview to get started.'
                : 'No booking requests yet.'}
          </p>
          {isCandidate && !filter && (
            <Link to="/interviewers" className="btn btn-primary btn-sm">Find an interviewer</Link>
          )}
        </div>
      ) : (
        <div className="bk-list">
          {bookings.map(b => (
            <SessionCard key={b.id} booking={b} isCandidate={isCandidate} />
          ))}
        </div>
      )}
    </div>
  );
}
