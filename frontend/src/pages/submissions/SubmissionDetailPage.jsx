import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { submissionApi } from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';

const STATUS_BADGE = {
  submitted:    'badge-warning',
  under_review: 'badge-warning',
  reviewed:     'badge-success',
  archived:     'badge-neutral',
};

function fmtDate(iso, long = false) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('en-US', long
    ? { month: 'long', day: 'numeric', year: 'numeric' }
    : { month: 'short', day: 'numeric', year: 'numeric' });
}

function StatusLabel(s) {
  if (s === 'reviewed')     return 'Reviewed';
  if (s === 'under_review') return 'Under Review';
  return 'Pending Review';
}

export default function SubmissionDetailPage() {
  const { id } = useParams();
  const { isInterviewer } = useAuth();
  const [submission, setSubmission] = useState(null);
  const [loading, setLoading]       = useState(true);
  const [error, setError]           = useState(null);
  const [annotation, setAnnotation] = useState('');
  const [saving, setSaving]         = useState(false);
  const [saveError, setSaveError]   = useState(null);

  useEffect(() => {
    submissionApi.get(id)
      .then(s => { setSubmission(s); setAnnotation(s.interviewer_annotation || ''); })
      .catch(e => setError(e.data?.message || e.message))
      .finally(() => setLoading(false));
  }, [id]);

  async function handleAnnotate(e) {
    e.preventDefault();
    setSaving(true); setSaveError(null);
    try {
      const updated = await submissionApi.annotate(id, annotation);
      setSubmission(updated);
    } catch (e) {
      setSaveError(e.data?.message || e.message);
    } finally {
      setSaving(false);
    }
  }

  if (loading) return <div className="spinner-wrap"><div className="spinner" /></div>;
  if (error)   return (
    <div style={{ maxWidth: 680 }}>
      <div className="alert alert-error">{error}</div>
      <Link to="/submissions" className="back-link">← Back to submissions</Link>
    </div>
  );
  if (!submission) return null;

  const isGithub   = submission.submission_type === 'github_link';
  const isReviewed = submission.status === 'reviewed';

  return (
    <div style={{ maxWidth: 760 }}>
      <Link to="/submissions" className="back-link" style={{ display: 'inline-flex', alignItems: 'center', gap: 6, marginBottom: 20, fontSize: '.85rem', color: 'var(--text-muted)' }}>
        ← Back to submissions
      </Link>

      {/* Hero */}
      <div className="sub-hero">
        <div>
          <h1 className="sub-hero-title">{submission.title}</h1>
          <div className="sub-hero-date">Submitted {fmtDate(submission.created_at, true)}</div>
        </div>
        <div className="sub-hero-right">
          <span className={`badge badge-lg ${STATUS_BADGE[submission.status] || 'badge-warning'}`}>
            {StatusLabel(submission.status)}
          </span>
          <span className={`tag ${isGithub ? 'tag-type' : 'tag-domain'}`} style={{ fontSize: '.78rem' }}>
            {isGithub ? '🔗 GitHub Link' : '📄 File Upload'}
          </span>
        </div>
      </div>

      {/* Info grid */}
      <div className="sub-info-grid">
        <div className="sub-info-cell">
          <div className="sub-info-label">Language</div>
          <div className="sub-info-value">{submission.language || '—'}</div>
        </div>
        <div className="sub-info-cell">
          <div className="sub-info-label">Type</div>
          <div className="sub-info-value">{isGithub ? 'GitHub Link' : 'File Upload'}</div>
        </div>
        <div className="sub-info-cell">
          <div className="sub-info-label">Linked Booking</div>
          <div className="sub-info-value">
            {submission.booking
              ? <Link to={`/bookings/${submission.booking.id}`} className="link">View session →</Link>
              : '—'}
          </div>
        </div>
      </div>

      {/* Description */}
      {submission.description && (
        <div className="sub-section">
          <div className="sub-section-title">Description / Notes</div>
          <p className="sub-section-text">{submission.description}</p>
        </div>
      )}

      {/* GitHub */}
      {isGithub && submission.github_url && (
        <div className="sub-section">
          <div className="sub-section-title">Repository</div>
          <div className="sub-repo-box">
            <span className="sub-repo-icon">🔗</span>
            <div>
              <a href={submission.github_url} target="_blank" rel="noopener noreferrer" className="sub-repo-url">
                {submission.github_url}
              </a>
              {submission.github_branch && (
                <div className="sub-repo-branch">Branch: {submission.github_branch}</div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* File */}
      {!isGithub && submission.file_name && (
        <div className="sub-section">
          <div className="sub-section-title">Submitted File</div>
          <div className="sub-file-box">
            <span className="sub-file-icon">📄</span>
            <div style={{ flex: 1 }}>
              <div className="sub-file-name">{submission.file_name}</div>
              {submission.file_size && (
                <div className="sub-file-size">{(submission.file_size / 1024).toFixed(1)} KB</div>
              )}
            </div>
            {submission.download_url && (
              <a href={submission.download_url} className="btn btn-outline btn-sm">Download</a>
            )}
          </div>
        </div>
      )}

      {/* Interviewer feedback form */}
      {isInterviewer && (
        <div className="sub-section">
          <div className="sub-review-header">
            <div className="sub-review-icon">✏️</div>
            <div className="sub-review-title">
              {isReviewed ? 'Edit Feedback' : 'Add Feedback'}
            </div>
          </div>

          {saveError && <div className="alert alert-error" style={{ marginBottom: 12 }}>{saveError}</div>}

          <form onSubmit={handleAnnotate}>
            <div className="form-group">
              <textarea
                className="form-control"
                rows={5}
                placeholder="Write your feedback, code review notes, or suggestions…"
                value={annotation}
                onChange={e => setAnnotation(e.target.value)}
                required
              />
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary" disabled={saving}>
                {saving ? 'Saving…' : isReviewed ? 'Update Feedback' : 'Submit Review'}
              </button>
            </div>
          </form>

          {submission.reviewed_at && (
            <p style={{ marginTop: 10, fontSize: '.78rem', color: 'var(--text-muted)' }}>
              Last reviewed {fmtDate(submission.reviewed_at, true)}
            </p>
          )}
        </div>
      )}

      {/* Candidate view: feedback */}
      {!isInterviewer && submission.interviewer_annotation && (
        <div className="sub-section">
          <div className="sub-section-title">Interviewer Feedback</div>
          <div className="sub-feedback-box">
            <p className="sub-feedback-text">{submission.interviewer_annotation}</p>
            {submission.reviewed_at && (
              <div className="sub-feedback-reviewed-at">
                Reviewed on {fmtDate(submission.reviewed_at, true)}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Candidate: no feedback yet */}
      {!isInterviewer && !submission.interviewer_annotation && isReviewed && (
        <div className="sub-section" style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '28px' }}>
          <p>Feedback will appear here once your interviewer reviews your submission.</p>
        </div>
      )}
    </div>
  );
}
