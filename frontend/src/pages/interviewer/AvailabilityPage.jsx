import { useEffect, useState, useMemo } from 'react';
import { availabilityApi } from '../../api/client';

function calcDuration(startTime, endTime) {
  if (!startTime || !endTime) return null;
  const [sh, sm] = startTime.split(':').map(Number);
  const [eh, em] = endTime.split(':').map(Number);
  const mins = (eh * 60 + em) - (sh * 60 + sm);
  return mins > 0 ? mins : null;
}

function fmtTime(iso) {
  return new Date(iso).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function fmtDateKey(iso) {
  const d = new Date(iso);
  return d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
}

function SlotCard({ slot, onDelete }) {
  const [deleting, setDeleting] = useState(false);
  const start    = new Date(slot.start_time);
  const end      = new Date(slot.end_time);
  const isPast   = start < new Date();
  const duration = Math.round((end - start) / 60000);

  async function handleDelete() {
    setDeleting(true);
    try { await onDelete(slot.id); } catch { setDeleting(false); }
  }

  const cardClass = isPast ? 'avail-slot-past'
    : slot.status === 'booked' ? 'avail-slot-booked'
    : 'avail-slot-available';

  const badgeClass = isPast ? 'badge-neutral'
    : slot.status === 'booked' ? 'badge-warning'
    : 'badge-success';

  const badgeLabel = isPast ? 'Past'
    : slot.status === 'booked' ? 'Booked'
    : 'Available';

  return (
    <div className={`avail-slot ${cardClass}`}>
      <div className="avail-slot-clock">
        {isPast ? '🕐' : slot.status === 'booked' ? '📌' : '🟢'}
      </div>
      <div className="avail-slot-info">
        <div className="avail-slot-time">
          {fmtTime(slot.start_time)} – {fmtTime(slot.end_time)}
        </div>
        <div className="avail-slot-dur">{duration} min session</div>
      </div>
      <div className="avail-slot-right">
        <span className={`badge ${badgeClass}`}>{badgeLabel}</span>
        {slot.status === 'available' && !isPast && (
          <button className="avail-del-btn" onClick={handleDelete} disabled={deleting}>
            {deleting ? '…' : 'Delete'}
          </button>
        )}
      </div>
    </div>
  );
}

export default function AvailabilityPage() {
  const [slots,     setSlots]     = useState([]);
  const [loading,   setLoading]   = useState(true);
  const [error,     setError]     = useState(null);
  const [success,   setSuccess]   = useState('');
  const [tab,       setTab]       = useState('upcoming'); // 'upcoming' | 'past'

  const [date,      setDate]      = useState('');
  const [startTime, setStartTime] = useState('');
  const [endTime,   setEndTime]   = useState('');
  const [timezone,  setTimezone]  = useState(Intl.DateTimeFormat().resolvedOptions().timeZone);
  const [adding,    setAdding]    = useState(false);
  const [formError, setFormError] = useState(null);

  const today   = new Date().toISOString().split('T')[0];
  const preview = calcDuration(startTime, endTime);

  async function loadSlots() {
    try {
      const res = await availabilityApi.mySlots();
      setSlots(Array.isArray(res) ? res : res?.data || []);
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { loadSlots(); }, []);

  async function handleAdd(e) {
    e.preventDefault();
    setFormError(null);
    const startISO = new Date(`${date}T${startTime}`).toISOString();
    const endISO   = new Date(`${date}T${endTime}`).toISOString();

    if (new Date(endISO) <= new Date(startISO)) {
      setFormError('End time must be after start time.'); return;
    }
    if (new Date(startISO) <= new Date()) {
      setFormError('Start time must be in the future.'); return;
    }

    setAdding(true);
    try {
      await availabilityApi.addSlots([{ start_time: startISO, end_time: endISO, timezone }]);
      setSuccess('Slot added successfully!');
      setDate(''); setStartTime(''); setEndTime('');
      setTimeout(() => setSuccess(''), 3000);
      await loadSlots();
    } catch (e) {
      setFormError(e.data?.message || e.message);
    } finally {
      setAdding(false);
    }
  }

  async function handleDelete(slotId) {
    await availabilityApi.deleteSlot(slotId);
    setSlots(prev => prev.filter(s => s.id !== slotId));
  }

  /* ── Computed stats + grouped slots ── */
  const now = new Date();
  const sorted   = useMemo(() => [...slots].sort((a, b) => new Date(a.start_time) - new Date(b.start_time)), [slots]);
  const upcoming = useMemo(() => sorted.filter(s => new Date(s.start_time) >= now), [sorted]);
  const past     = useMemo(() => sorted.filter(s => new Date(s.start_time) < now).reverse(), [sorted]);
  const available= slots.filter(s => s.status === 'available' && new Date(s.start_time) >= now).length;
  const booked   = slots.filter(s => s.status === 'booked').length;

  const displayed = tab === 'upcoming' ? upcoming : past;

  function groupByDate(list) {
    return list.reduce((acc, slot) => {
      const key = fmtDateKey(slot.start_time);
      if (!acc[key]) acc[key] = [];
      acc[key].push(slot);
      return acc;
    }, {});
  }

  const grouped = groupByDate(displayed);

  return (
    <div style={{ maxWidth: 760 }}>
      {/* Header */}
      <div className="avail-header">
        <div>
          <h1>Manage Availability</h1>
          <p>Set the time slots when you're open for interview sessions</p>
        </div>
      </div>

      {/* Stats */}
      {!loading && slots.length > 0 && (
        <div className="avail-stats">
          <div className="avail-stat">
            <div className="avail-stat-num">{slots.length}</div>
            <div className="avail-stat-cap">Total Slots</div>
          </div>
          <div className="avail-stat">
            <div className="avail-stat-num green">{available}</div>
            <div className="avail-stat-cap">Available</div>
          </div>
          <div className="avail-stat">
            <div className="avail-stat-num amber">{booked}</div>
            <div className="avail-stat-cap">Booked</div>
          </div>
          <div className="avail-stat">
            <div className="avail-stat-num indigo">{upcoming.length}</div>
            <div className="avail-stat-cap">Upcoming</div>
          </div>
        </div>
      )}

      {/* Add slot card */}
      <div className="avail-add-card">
        <div className="avail-add-head">
          <div className="avail-add-icon">📅</div>
          <div>
            <div className="avail-add-title">Add Time Slot</div>
            <div className="avail-add-sub">Choose a date and time window when you're available</div>
          </div>
        </div>

        {formError && <div className="alert alert-error" style={{ marginBottom: 14 }}>{formError}</div>}
        {success   && <div className="alert alert-success" style={{ marginBottom: 14 }}>{success}</div>}

        <form onSubmit={handleAdd}>
          <div className="avail-form-grid">
            <div className="form-group" style={{ marginBottom: 0 }}>
              <label>Date <span style={{ color: 'var(--error)' }}>*</span></label>
              <input
                className="form-control" type="date" min={today} required
                value={date} onChange={e => setDate(e.target.value)}
              />
            </div>
            <div className="form-group" style={{ marginBottom: 0 }}>
              <label>Start Time <span style={{ color: 'var(--error)' }}>*</span></label>
              <input
                className="form-control" type="time" required
                value={startTime} onChange={e => setStartTime(e.target.value)}
              />
            </div>
            <div className="form-group" style={{ marginBottom: 0 }}>
              <label>End Time <span style={{ color: 'var(--error)' }}>*</span></label>
              <input
                className="form-control" type="time" required
                value={endTime} onChange={e => setEndTime(e.target.value)}
              />
            </div>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', flexWrap: 'wrap', marginBottom: 18, marginTop: 4 }}>
            <div className="avail-tz-pill">
              🌐 {timezone}
            </div>
            {preview && (
              <div className="avail-duration-preview">
                ⏱ {preview} min session
              </div>
            )}
          </div>

          <div className="form-actions" style={{ marginTop: 0 }}>
            <button type="submit" className="btn btn-primary" disabled={adding}>
              {adding ? 'Adding…' : '+ Add Slot'}
            </button>
          </div>
        </form>
      </div>

      {/* Schedule */}
      <div className="avail-schedule-header">
        <div className="avail-schedule-title">Your Schedule</div>
        <div className="avail-tabs">
          <button
            className={`avail-tab ${tab === 'upcoming' ? 'active' : ''}`}
            onClick={() => setTab('upcoming')}
          >
            Upcoming ({upcoming.length})
          </button>
          <button
            className={`avail-tab ${tab === 'past' ? 'active' : ''}`}
            onClick={() => setTab('past')}
          >
            Past ({past.length})
          </button>
        </div>
      </div>

      {loading ? (
        <div className="spinner-wrap"><div className="spinner" /></div>
      ) : error ? (
        <div className="alert alert-error">{error}</div>
      ) : displayed.length === 0 ? (
        <div className="avail-empty">
          <div className="avail-empty-icon">{tab === 'upcoming' ? '📭' : '📋'}</div>
          <h3>{tab === 'upcoming' ? 'No upcoming slots' : 'No past slots'}</h3>
          <p>
            {tab === 'upcoming'
              ? 'Use the form above to add your available time slots.'
              : 'Your completed and expired slots will appear here.'}
          </p>
        </div>
      ) : (
        Object.entries(grouped).map(([dateLabel, dateSlots]) => (
          <div key={dateLabel} className="avail-date-group">
            <div className="avail-date-label">
              <div className="avail-date-dot" />
              {dateLabel}
            </div>
            {dateSlots.map(slot => (
              <SlotCard key={slot.id} slot={slot} onDelete={handleDelete} />
            ))}
          </div>
        ))
      )}
    </div>
  );
}
