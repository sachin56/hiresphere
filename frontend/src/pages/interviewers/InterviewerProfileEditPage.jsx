import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { interviewerApi } from '../../api/client';

const DOMAINS         = ['backend', 'frontend', 'devops', 'aiml', 'mobile', 'fullstack', 'security'];
const INTERVIEW_TYPES = ['dsa', 'system_design', 'behavioral', 'mixed'];
const EXPERIENCE_LEVELS = ['senior', 'staff', 'principal', 'distinguished'];
const DURATIONS       = [30, 45, 60, 90, 120];

function CheckboxGroup({ label, options, selected, onChange }) {
  function toggle(val) {
    onChange(selected.includes(val) ? selected.filter(v => v !== val) : [...selected, val]);
  }
  return (
    <div className="form-group">
      <label>{label}</label>
      <div className="checkbox-group">
        {options.map(opt => (
          <label key={opt} className="checkbox-item">
            <input type="checkbox" checked={selected.includes(opt)} onChange={() => toggle(opt)} />
            <span>{opt.replace('_', ' ')}</span>
          </label>
        ))}
      </div>
    </div>
  );
}

export default function InterviewerProfileEditPage() {
  const { user } = useAuth();
  const navigate = useNavigate();

  const [form, setForm] = useState({
    current_title:            '',
    current_company:          '',
    years_of_experience:      0,
    experience_level:         'senior',
    domains:                  [],
    interview_types:          [],
    hourly_rate:              0,
    session_duration_minutes: 60,
    interview_approach:       '',
    is_available:             true,
  });

  const [loading,    setLoading]    = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error,      setError]      = useState(null);
  const [success,    setSuccess]    = useState(false);

  useEffect(() => {
    if (!user?.interviewer_profile) { setLoading(false); return; }
    const p = user.interviewer_profile;
    setForm({
      current_title:            p.current_title            || '',
      current_company:          p.current_company          || '',
      years_of_experience:      p.years_of_experience      ?? 0,
      experience_level:         p.experience_level         || 'senior',
      domains:                  p.domains                  || [],
      interview_types:          p.interview_types          || [],
      hourly_rate:              p.hourly_rate              ?? 0,
      session_duration_minutes: p.session_duration_minutes ?? 60,
      interview_approach:       p.interview_approach       || '',
      is_available:             p.is_available             ?? true,
    });
    setLoading(false);
  }, [user]);

  function set(key, value) { setForm(prev => ({ ...prev, [key]: value })); }

  async function handleSubmit(e) {
    e.preventDefault();
    setSubmitting(true);
    setError(null);
    setSuccess(false);
    try {
      await interviewerApi.update(user.id, {
        ...form,
        years_of_experience:      Number(form.years_of_experience),
        hourly_rate:              Number(form.hourly_rate),
        session_duration_minutes: Number(form.session_duration_minutes),
      });
      setSuccess(true);
      setTimeout(() => navigate('/dashboard'), 1200);
    } catch (e) {
      setError(e.data?.message || e.message);
      setSubmitting(false);
    }
  }

  if (loading) return <div className="spinner-wrap"><div className="spinner" /></div>;

  return (
    <div className="page page-narrow">
      <h1 className="page-title">Edit Profile</h1>

      {error   && <div className="alert alert-error">{error}</div>}
      {success && <div className="alert alert-success">Profile saved!</div>}

      <form className="card form-card" onSubmit={handleSubmit}>

        <div className="form-row">
          <div className="form-group">
            <label>Job Title *</label>
            <input className="form-control" value={form.current_title} required
              onChange={e => set('current_title', e.target.value)} placeholder="e.g. Senior Software Engineer" />
          </div>
          <div className="form-group">
            <label>Company</label>
            <input className="form-control" value={form.current_company}
              onChange={e => set('current_company', e.target.value)} placeholder="e.g. Google" />
          </div>
        </div>

        <div className="form-row">
          <div className="form-group">
            <label>Years of Experience *</label>
            <input className="form-control" type="number" min="0" max="50" value={form.years_of_experience} required
              onChange={e => set('years_of_experience', e.target.value)} />
          </div>
          <div className="form-group">
            <label>Experience Level *</label>
            <select className="form-control" value={form.experience_level}
              onChange={e => set('experience_level', e.target.value)}>
              {EXPERIENCE_LEVELS.map(l => <option key={l} value={l}>{l.charAt(0).toUpperCase() + l.slice(1)}</option>)}
            </select>
          </div>
        </div>

        <CheckboxGroup label="Domains *" options={DOMAINS}
          selected={form.domains} onChange={v => set('domains', v)} />

        <CheckboxGroup label="Interview Types *" options={INTERVIEW_TYPES}
          selected={form.interview_types} onChange={v => set('interview_types', v)} />

        <div className="form-row">
          <div className="form-group">
            <label>Hourly Rate (USD) *</label>
            <input className="form-control" type="number" min="0" step="5" value={form.hourly_rate} required
              onChange={e => set('hourly_rate', e.target.value)} />
          </div>
          <div className="form-group">
            <label>Session Duration *</label>
            <select className="form-control" value={form.session_duration_minutes}
              onChange={e => set('session_duration_minutes', e.target.value)}>
              {DURATIONS.map(d => <option key={d} value={d}>{d} min</option>)}
            </select>
          </div>
        </div>

        <div className="form-group">
          <label>About / Interview Approach</label>
          <textarea className="form-control" rows={4} value={form.interview_approach}
            onChange={e => set('interview_approach', e.target.value)}
            placeholder="Describe your interview style and what candidates can expect…" />
        </div>

        <div className="form-group form-check">
          <label>
            <input type="checkbox" checked={form.is_available}
              onChange={e => set('is_available', e.target.checked)} />
            <span>Available for bookings</span>
          </label>
        </div>

        <div className="form-actions">
          <button type="button" className="btn btn-ghost" onClick={() => navigate(-1)}>Cancel</button>
          <button type="submit" className="btn btn-primary" disabled={submitting}>
            {submitting ? 'Saving…' : 'Save Profile'}
          </button>
        </div>
      </form>
    </div>
  );
}
