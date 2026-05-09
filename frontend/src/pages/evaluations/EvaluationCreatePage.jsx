import { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { bookingApi, evaluationApi } from '../../api/client';

const HIRE_OPTIONS = [
  { value: 'strong_hire',    label: 'Strong Hire' },
  { value: 'hire',           label: 'Hire' },
  { value: 'no_hire',        label: 'No Hire' },
  { value: 'strong_no_hire', label: 'Strong No Hire' },
];

const SCORE_FIELDS = [
  { key: 'technical_score',       label: 'Technical' },
  { key: 'communication_score',   label: 'Communication' },
  { key: 'problem_solving_score', label: 'Problem Solving' },
  { key: 'system_design_score',   label: 'System Design' },
  { key: 'behavioral_score',      label: 'Behavioral' },
];

const COMMON_TOPICS = [
  'Arrays', 'Strings', 'Trees', 'Graphs', 'Dynamic Programming',
  'System Design', 'OOP', 'SQL', 'APIs', 'Concurrency',
];

function ScoreInput({ label, value, onChange }) {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
      <label style={{ fontSize: '.82rem', fontWeight: 600, color: 'var(--text)' }}>{label}</label>
      <div style={{ display: 'flex', gap: 4 }}>
        {Array.from({ length: 10 }, (_, i) => i + 1).map(n => (
          <button
            key={n}
            type="button"
            onClick={() => onChange(value === n ? null : n)}
            style={{
              width: 32, height: 32, borderRadius: 6, border: '1.5px solid',
              borderColor: value === n ? 'var(--primary)' : 'var(--border)',
              background: value === n ? 'var(--primary)' : 'var(--surface)',
              color: value === n ? '#fff' : 'var(--text)',
              fontWeight: value === n ? 700 : 400,
              fontSize: '.82rem', cursor: 'pointer', transition: 'all .12s',
            }}
          >
            {n}
          </button>
        ))}
      </div>
    </div>
  );
}

export default function EvaluationCreatePage() {
  const { id: bookingId } = useParams();
  const navigate = useNavigate();

  const [booking,  setBooking]  = useState(null);
  const [loading,  setLoading]  = useState(true);
  const [saving,   setSaving]   = useState(false);
  const [error,    setError]    = useState(null);

  const [overallScore,          setOverallScore]          = useState(null);
  const [technicalScore,        setTechnicalScore]        = useState(null);
  const [communicationScore,    setCommunicationScore]    = useState(null);
  const [problemSolvingScore,   setProblemSolvingScore]   = useState(null);
  const [systemDesignScore,     setSystemDesignScore]     = useState(null);
  const [behavioralScore,       setBehavioralScore]       = useState(null);
  const [strengths,             setStrengths]             = useState('');
  const [areasForImprovement,   setAreasForImprovement]   = useState('');
  const [detailedFeedback,      setDetailedFeedback]      = useState('');
  const [topicsCovered,         setTopicsCovered]         = useState([]);
  const [hireRecommendation,    setHireRecommendation]    = useState('');
  const [shareWithCandidate,    setShareWithCandidate]    = useState(true);

  useEffect(() => {
    bookingApi.get(bookingId)
      .then(setBooking)
      .catch(() => setError('Booking not found.'))
      .finally(() => setLoading(false));
  }, [bookingId]);

  function toggleTopic(topic) {
    setTopicsCovered(prev =>
      prev.includes(topic) ? prev.filter(t => t !== topic) : [...prev, topic]
    );
  }

  async function handleSubmit(e) {
    e.preventDefault();
    if (!overallScore) { setError('Overall score is required.'); return; }

    setSaving(true); setError(null);
    try {
      await evaluationApi.create({
        booking_id:               bookingId,
        overall_score:            overallScore,
        technical_score:          technicalScore,
        communication_score:      communicationScore,
        problem_solving_score:    problemSolvingScore,
        system_design_score:      systemDesignScore,
        behavioral_score:         behavioralScore,
        strengths,
        areas_for_improvement:    areasForImprovement,
        detailed_feedback:        detailedFeedback,
        topics_covered:           topicsCovered.length ? topicsCovered : undefined,
        hire_recommendation:      hireRecommendation || undefined,
        is_shared_with_candidate: shareWithCandidate,
      });
      navigate(`/bookings/${bookingId}`);
    } catch (e) {
      setError(e.data?.message || e.message || 'Failed to submit evaluation.');
      setSaving(false);
    }
  }

  if (loading) return <div className="spinner-wrap"><div className="spinner" /></div>;
  if (error && !booking) return (
    <div style={{ maxWidth: 760 }}>
      <div className="alert alert-error">{error}</div>
      <Link to="/bookings" className="back-link">← Back</Link>
    </div>
  );

  const candidate = booking?.candidate;

  return (
    <div style={{ maxWidth: 760 }}>
      <Link
        to={`/bookings/${bookingId}`}
        style={{ display: 'inline-flex', alignItems: 'center', gap: 6, marginBottom: 20, fontSize: '.85rem', color: 'var(--text-muted)' }}
      >
        ← Back to session
      </Link>

      <div style={{ marginBottom: 28 }}>
        <h1 style={{ fontSize: '1.5rem', fontWeight: 700, color: 'var(--text)', margin: 0 }}>
          Write Evaluation
        </h1>
        {candidate && (
          <p style={{ margin: '4px 0 0', fontSize: '.9rem', color: 'var(--text-muted)' }}>
            for <strong>{candidate.name}</strong>
          </p>
        )}
      </div>

      {error && <div className="alert alert-error">{error}</div>}

      <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 28 }}>

        {/* Overall score */}
        <div className="card" style={{ padding: 24 }}>
          <div style={{ fontSize: '1rem', fontWeight: 700, marginBottom: 16 }}>Overall Score *</div>
          <ScoreInput label="Overall (1–10)" value={overallScore} onChange={setOverallScore} />
        </div>

        {/* Category scores */}
        <div className="card" style={{ padding: 24 }}>
          <div style={{ fontSize: '1rem', fontWeight: 700, marginBottom: 16 }}>Category Scores (optional)</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            <ScoreInput label="Technical"        value={technicalScore}      onChange={setTechnicalScore} />
            <ScoreInput label="Communication"    value={communicationScore}  onChange={setCommunicationScore} />
            <ScoreInput label="Problem Solving"  value={problemSolvingScore} onChange={setProblemSolvingScore} />
            <ScoreInput label="System Design"    value={systemDesignScore}   onChange={setSystemDesignScore} />
            <ScoreInput label="Behavioral"       value={behavioralScore}     onChange={setBehavioralScore} />
          </div>
        </div>

        {/* Written feedback */}
        <div className="card" style={{ padding: 24 }}>
          <div style={{ fontSize: '1rem', fontWeight: 700, marginBottom: 16 }}>Written Feedback *</div>

          <div className="form-group">
            <label>Strengths <span style={{ color: 'var(--text-muted)', fontWeight: 400 }}>(min 50 chars)</span></label>
            <textarea
              className="form-control"
              rows={4}
              placeholder="What did the candidate do well?"
              value={strengths}
              onChange={e => setStrengths(e.target.value)}
              required
              minLength={50}
            />
            <div style={{ fontSize: '.75rem', color: strengths.length < 50 ? 'var(--error)' : 'var(--text-muted)', textAlign: 'right' }}>
              {strengths.length} / 50 min
            </div>
          </div>

          <div className="form-group">
            <label>Areas for Improvement <span style={{ color: 'var(--text-muted)', fontWeight: 400 }}>(min 50 chars)</span></label>
            <textarea
              className="form-control"
              rows={4}
              placeholder="What could the candidate improve?"
              value={areasForImprovement}
              onChange={e => setAreasForImprovement(e.target.value)}
              required
              minLength={50}
            />
            <div style={{ fontSize: '.75rem', color: areasForImprovement.length < 50 ? 'var(--error)' : 'var(--text-muted)', textAlign: 'right' }}>
              {areasForImprovement.length} / 50 min
            </div>
          </div>

          <div className="form-group" style={{ marginBottom: 0 }}>
            <label>Detailed Feedback <span style={{ color: 'var(--text-muted)', fontWeight: 400 }}>(min 100 chars)</span></label>
            <textarea
              className="form-control"
              rows={6}
              placeholder="Provide a detailed assessment of the session..."
              value={detailedFeedback}
              onChange={e => setDetailedFeedback(e.target.value)}
              required
              minLength={100}
            />
            <div style={{ fontSize: '.75rem', color: detailedFeedback.length < 100 ? 'var(--error)' : 'var(--text-muted)', textAlign: 'right' }}>
              {detailedFeedback.length} / 100 min
            </div>
          </div>
        </div>

        {/* Topics covered */}
        <div className="card" style={{ padding: 24 }}>
          <div style={{ fontSize: '1rem', fontWeight: 700, marginBottom: 12 }}>Topics Covered (optional)</div>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
            {COMMON_TOPICS.map(topic => (
              <button
                key={topic}
                type="button"
                onClick={() => toggleTopic(topic)}
                className={topicsCovered.includes(topic) ? 'btn btn-primary btn-sm' : 'btn btn-outline btn-sm'}
              >
                {topic}
              </button>
            ))}
          </div>
        </div>

        {/* Hire recommendation */}
        <div className="card" style={{ padding: 24 }}>
          <div style={{ fontSize: '1rem', fontWeight: 700, marginBottom: 12 }}>Hire Recommendation (optional)</div>
          <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
            {HIRE_OPTIONS.map(opt => (
              <button
                key={opt.value}
                type="button"
                onClick={() => setHireRecommendation(hireRecommendation === opt.value ? '' : opt.value)}
                className={hireRecommendation === opt.value ? 'btn btn-primary btn-sm' : 'btn btn-outline btn-sm'}
              >
                {opt.label}
              </button>
            ))}
          </div>
        </div>

        {/* Visibility */}
        <div className="card" style={{ padding: 24 }}>
          <label style={{ display: 'flex', alignItems: 'center', gap: 12, cursor: 'pointer' }}>
            <input
              type="checkbox"
              checked={shareWithCandidate}
              onChange={e => setShareWithCandidate(e.target.checked)}
              style={{ width: 18, height: 18, accentColor: 'var(--primary)', cursor: 'pointer' }}
            />
            <div>
              <div style={{ fontWeight: 600, fontSize: '.9rem' }}>Share with candidate</div>
              <div style={{ fontSize: '.82rem', color: 'var(--text-muted)' }}>
                The candidate will be notified and can view this evaluation.
              </div>
            </div>
          </label>
        </div>

        {/* Submit */}
        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
          <Link to={`/bookings/${bookingId}`} className="btn btn-ghost">Cancel</Link>
          <button type="submit" className="btn btn-primary" disabled={saving}>
            {saving ? 'Submitting…' : 'Submit Evaluation'}
          </button>
        </div>

      </form>
    </div>
  );
}
