import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { bookingApi, submissionApi, evaluationApi } from '../../api/client';

function initials(name = '') {
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
}

function fmtDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function SessionRow({ booking, isCandidate }) {
  const other = isCandidate ? booking.interviewer : booking.candidate;
  const name  = other?.name || 'Unknown';
  return (
    <div className="db-session">
      <div className="db-session-avatar">{initials(name)}</div>
      <div className="db-session-info">
        <div className="db-session-name">{name}</div>
        <div className="db-session-time">{fmtDate(booking.scheduled_at)}</div>
      </div>
      <div className="db-session-btns">
        <Link to={`/bookings/${booking.id}`} className="db-btn-join">Join</Link>
        {!isCandidate && (
          <Link to={`/bookings/${booking.id}`} className="db-btn-eval">Evaluate</Link>
        )}
      </div>
    </div>
  );
}

function PendingRow({ booking, isCandidate }) {
  const other = isCandidate ? booking.interviewer : booking.candidate;
  const name  = other?.name || 'Unknown';
  return (
    <div className="db-session">
      <div className="db-session-avatar" style={{ background: '#f59e0b' }}>{initials(name)}</div>
      <div className="db-session-info">
        <div className="db-session-name">{name}</div>
        <div className="db-session-time">{fmtDate(booking.scheduled_at)}</div>
      </div>
      <div className="db-session-btns">
        <Link to={`/bookings/${booking.id}`} className="db-btn-eval">Review</Link>
      </div>
    </div>
  );
}

export default function DashboardPage() {
  const { user, isCandidate } = useAuth();
  const [bookings,    setBookings]    = useState([]);
  const [submissions, setSubmissions] = useState([]);
  const [evaluations, setEvaluations] = useState([]);
  const [loading,     setLoading]     = useState(true);

  useEffect(() => {
    (async () => {
      try {
        const [bRes, eRes] = await Promise.all([bookingApi.list(), evaluationApi.list()]);
        setBookings(bRes?.data || []);
        setEvaluations(eRes?.data || []);
        if (isCandidate) {
          const sRes = await submissionApi.list();
          setSubmissions(sRes?.data || []);
        }
      } catch (e) { console.error(e); }
      finally { setLoading(false); }
    })();
  }, [isCandidate]);

  const pending   = bookings.filter(b => b.status === 'pending');
  const accepted  = bookings.filter(b => b.status === 'accepted');
  const completed = bookings.filter(b => b.status === 'completed');
  const upcoming  = bookings.filter(b => ['pending', 'accepted'].includes(b.status));

  const avgRating = evaluations.length > 0
    ? (evaluations.reduce((s, e) => s + (e.overall_score || 0), 0) / evaluations.length).toFixed(1)
    : null;

  const userInitials = initials(user?.name);
  const pageTitle    = isCandidate ? 'Candidate Dashboard' : 'Interviewer Dashboard';

  return (
    <div>
      {/* Top bar */}
      <div className="db-topbar">
        <h1>{pageTitle}</h1>
        <div className="db-user-chip">
          <div className="db-user-chip-avatar">{userInitials}</div>
          <span className="db-user-chip-name">{user?.name}</span>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2" strokeLinecap="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
      </div>

      {/* Stat cards */}
      <div className="db-stats">
        <div className="db-stat">
          <div className="db-stat-label">Total Sessions</div>
          <div className="db-stat-value">{completed.length}</div>
        </div>
        {isCandidate ? (
          <div className="db-stat">
            <div className="db-stat-label">Submissions</div>
            <div className="db-stat-value indigo">{submissions.length}</div>
          </div>
        ) : (
          <div className="db-stat">
            <div className="db-stat-label">Total Earnings</div>
            <div className="db-stat-value green">$0</div>
          </div>
        )}
        <div className="db-stat">
          <div className="db-stat-label">Rating</div>
          {avgRating ? (
            <div className="db-stat-value yellow">★ {avgRating}</div>
          ) : (
            <>
              <div className="db-stat-value yellow">—</div>
              <div className="db-stat-bar"/>
            </>
          )}
        </div>
        <div className="db-stat">
          <div className="db-stat-label">Notifications</div>
          <div className="db-stat-value indigo">{pending.length}</div>
        </div>
      </div>

      {/* Two panels */}
      <div className="db-panels">
        {/* Pending Requests */}
        <div className="db-panel">
          <div className="db-panel-head">
            <h2>Pending Requests</h2>
            <Link to="/bookings">View all</Link>
          </div>
          <div className="db-panel-body">
            {loading ? (
              <div className="spinner-wrap"><div className="spinner"/></div>
            ) : pending.length === 0 ? (
              <p className="db-empty-msg">No pending requests.</p>
            ) : (
              pending.slice(0, 5).map(b => (
                <PendingRow key={b.id} booking={b} isCandidate={isCandidate}/>
              ))
            )}
          </div>
        </div>

        {/* Upcoming Sessions */}
        <div className="db-panel">
          <div className="db-panel-head">
            <h2>Upcoming Sessions</h2>
            <Link to="/bookings">View all</Link>
          </div>
          <div className="db-panel-body">
            {loading ? (
              <div className="spinner-wrap"><div className="spinner"/></div>
            ) : accepted.length === 0 ? (
              <p className="db-empty-msg">No upcoming sessions.</p>
            ) : (
              accepted.slice(0, 5).map(b => (
                <SessionRow key={b.id} booking={b} isCandidate={isCandidate}/>
              ))
            )}
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="db-actions">
        {isCandidate ? (
          <>
            <Link to="/interviewers"    className="db-action primary">
              <span className="db-action-icon">🔍</span>Find Interviewers
            </Link>
            <Link to="/submissions/new" className="db-action">
              <span className="db-action-icon">📤</span>Submit Challenge
            </Link>
            <Link to="/history"         className="db-action">
              <span className="db-action-icon">📋</span>Evaluations
            </Link>
            <Link to="/messages"        className="db-action">
              <span className="db-action-icon">💬</span>Messages
            </Link>
          </>
        ) : (
          <>
            <Link to="/interviewer/availability"  className="db-action primary">
              <span className="db-action-icon">📅</span>Manage Availability
            </Link>
            <Link to="/interviewer/profile/edit"  className="db-action">
              <span className="db-action-icon">👤</span>Edit Profile
            </Link>
            <Link to="/history"                   className="db-action">
              <span className="db-action-icon">📋</span>Evaluations
            </Link>
            <Link to="/messages"                  className="db-action">
              <span className="db-action-icon">💬</span>Messages
            </Link>
          </>
        )}
      </div>
    </div>
  );
}
