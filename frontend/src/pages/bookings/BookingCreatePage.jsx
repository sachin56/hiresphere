import { useEffect, useState } from 'react';
import { useParams, useSearchParams, useNavigate, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { interviewerApi, bookingApi } from '../../api/client';

const TYPES = [
  { value: 'dsa',           label: '💻 Data Structures & Algorithms', desc: 'Arrays, trees, graphs, dynamic programming' },
  { value: 'system_design', label: '🏗️ System Design',                desc: 'Architecture, scalability, distributed systems' },
  { value: 'behavioral',    label: '🗣️ Behavioral',                   desc: 'Leadership, culture fit, situational questions' },
  { value: 'mixed',         label: '🔀 Mixed',                        desc: 'Combination of technical and behavioral' },
];

export default function BookingCreatePage() {
  const { interviewerId } = useParams();
  const [searchParams]    = useSearchParams();
  const navigate          = useNavigate();

  const [interviewer,  setInterviewer]  = useState(null);
  const [availability, setAvailability] = useState([]);
  const [loading,      setLoading]      = useState(true);
  const [submitting,   setSubmitting]   = useState(false);
  const [error,        setError]        = useState(null);

  const { register, handleSubmit, watch, formState: { errors } } = useForm({
    defaultValues: {
      slot_id:           searchParams.get('slot') || '',
      interview_type:    'dsa',
      candidate_notes:   '',
      recording_consent: false,
    },
  });

  const selectedType = watch('interview_type');

  useEffect(() => {
    Promise.all([
      interviewerApi.get(interviewerId),
      interviewerApi.availability(interviewerId),
    ]).then(([i, a]) => {
      setInterviewer(i);
      setAvailability(a?.data || a || []);
    }).catch(console.error)
      .finally(() => setLoading(false));
  }, [interviewerId]);

  async function onSubmit(data) {
    setSubmitting(true); setError(null);
    try {
      const booking = await bookingApi.create({
        ...data,
        interviewer_id: interviewer.user_id,
        recording_consent: data.recording_consent === true || data.recording_consent === 'true',
      });
      navigate(`/bookings/${booking.id || booking.data?.id}`);
    } catch (e) {
      setError(e.data?.message || e.message);
      setSubmitting(false);
    }
  }

  if (loading) return <div className="spinner-wrap"><div className="spinner" /></div>;
  if (!interviewer) return <div className="alert alert-error">Interviewer not found.</div>;

  const user           = interviewer.user || {};
  const availableSlots = availability.filter(s => s.status === 'available');
  const selectedTypeInfo = TYPES.find(t => t.value === selectedType);

  return (
    <div style={{ maxWidth: 680 }}>
      <Link to={`/interviewers/${interviewerId}`} className="back-link" style={{ display: 'inline-flex', alignItems: 'center', gap: 6, marginBottom: 20, fontSize: '.85rem', color: 'var(--text-muted)' }}>
        ← Back to profile
      </Link>

      <div style={{ marginBottom: 24 }}>
        <h1 className="page-title">Book a Session</h1>
        <p className="page-subtitle">Choose a time slot and session type below.</p>
      </div>

      {/* Interviewer mini card */}
      <div className="bk-create-interviewer">
        <div className="bk-create-avatar">
          {user.profile_picture
            ? <img src={user.profile_picture} alt={user.name} />
            : <span>{user.name?.charAt(0) || '?'}</span>}
        </div>
        <div style={{ flex: 1 }}>
          <div className="bk-create-name">{user.name}</div>
          {interviewer.current_title && <div className="bk-create-title">{interviewer.current_title}</div>}
          <div className="bk-create-rate">${interviewer.hourly_rate || 0} / hr</div>
        </div>
        {interviewer.avg_rating && (
          <div style={{ fontSize: '.9rem', color: 'var(--warning)', fontWeight: 700 }}>
            ★ {interviewer.avg_rating}
          </div>
        )}
      </div>

      {error && <div className="alert alert-error">{error}</div>}

      <form onSubmit={handleSubmit(onSubmit)}>
        {/* Time slot */}
        <div className="bk-section" style={{ marginBottom: 14 }}>
          <div className="bk-section-title">Select a Time Slot</div>

          {availableSlots.length === 0 ? (
            <div className="bk-empty" style={{ padding: '28px 20px' }}>
              <div className="bk-empty-icon">📅</div>
              <h3>No available slots</h3>
              <p>This interviewer hasn't added availability yet. Check back soon.</p>
            </div>
          ) : (
            <>
              <div className="bk-slot-grid">
                {availableSlots.map(slot => (
                  <div key={slot.id} className="bk-slot-option">
                    <input
                      type="radio"
                      id={`slot-${slot.id}`}
                      value={slot.id}
                      {...register('slot_id', { required: 'Please select a time slot' })}
                    />
                    <label htmlFor={`slot-${slot.id}`} className="bk-slot-label">
                      <span className="bk-slot-date">
                        {new Date(slot.start_time).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })}
                      </span>
                      <span className="bk-slot-time">
                        {new Date(slot.start_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                      </span>
                      {slot.duration_minutes && (
                        <span className="bk-slot-dur">{slot.duration_minutes} min</span>
                      )}
                    </label>
                  </div>
                ))}
              </div>
              {errors.slot_id && <span className="field-error" style={{ marginTop: 8, display: 'block' }}>{errors.slot_id.message}</span>}
            </>
          )}
        </div>

        {/* Interview type */}
        <div className="bk-section" style={{ marginBottom: 14 }}>
          <div className="bk-section-title">Interview Type</div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
            {TYPES.map(t => (
              <label key={t.value} style={{
                display: 'flex', alignItems: 'flex-start', gap: 10,
                padding: '12px 14px', borderRadius: 8, cursor: 'pointer',
                border: `1.5px solid ${selectedType === t.value ? 'var(--primary)' : 'var(--border)'}`,
                background: selectedType === t.value ? 'var(--primary-light)' : 'var(--surface)',
                transition: 'all .15s',
              }}>
                <input type="radio" value={t.value} {...register('interview_type')} style={{ marginTop: 3, flexShrink: 0, accentColor: 'var(--primary)' }} />
                <div>
                  <div style={{ fontSize: '.88rem', fontWeight: 600, color: 'var(--text)' }}>{t.label}</div>
                  <div style={{ fontSize: '.75rem', color: 'var(--text-muted)', marginTop: 2 }}>{t.desc}</div>
                </div>
              </label>
            ))}
          </div>
        </div>

        {/* Notes + consent */}
        <div className="bk-section" style={{ marginBottom: 14 }}>
          <div className="bk-section-title">Additional Details</div>

          <div className="form-group">
            <label>Notes for Interviewer</label>
            <textarea
              className="form-control"
              rows={3}
              placeholder="What topics do you want to focus on? Any context about your background?"
              {...register('candidate_notes', { maxLength: 1000 })}
            />
          </div>

          <div className="form-check form-group" style={{ marginBottom: 0 }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: 10, cursor: 'pointer', fontWeight: 400 }}>
              <input type="checkbox" {...register('recording_consent')} style={{ width: 16, height: 16, accentColor: 'var(--primary)' }} />
              <span style={{ fontSize: '.88rem', color: 'var(--text)' }}>
                I consent to recording this session for review purposes
              </span>
            </label>
          </div>
        </div>

        {/* Actions */}
        <div className="form-actions">
          <Link to={`/interviewers/${interviewerId}`} className="btn btn-ghost">Cancel</Link>
          <button
            type="submit"
            className="btn btn-primary"
            disabled={submitting || availableSlots.length === 0}
          >
            {submitting ? 'Booking…' : 'Confirm Booking'}
          </button>
        </div>
      </form>
    </div>
  );
}
