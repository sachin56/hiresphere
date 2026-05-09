import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { submissionApi, bookingApi } from '../../api/client';

const LANGUAGES = ['JavaScript', 'TypeScript', 'Python', 'Java', 'Go', 'C++', 'C#', 'Rust', 'Other'];

export default function SubmissionCreatePage() {
  const navigate       = useNavigate();
  const [searchParams] = useSearchParams();
  const [mode, setMode]         = useState('file_upload');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError]       = useState(null);
  const [fileInfo, setFileInfo] = useState(null);
  const [bookings, setBookings] = useState([]);

  const { register, handleSubmit, formState: { errors } } = useForm({
    defaultValues: {
      title:      '',
      language:   '',
      description:'',
      github_url: '',
      booking_id: searchParams.get('booking_id') || '',
    },
  });

  useEffect(() => {
    bookingApi.list()
      .then(r => setBookings((r?.data || []).filter(b => !['cancelled', 'rejected'].includes(b.status))))
      .catch(() => {});
  }, []);

  function handleFileChange(e) {
    const file = e.target.files?.[0];
    if (file) setFileInfo({ name: file.name, size: (file.size / 1024).toFixed(1) + ' KB' });
  }

  async function onSubmit(data) {
    setSubmitting(true); setError(null);
    try {
      const form = new FormData();
      form.append('title',           data.title);
      form.append('language',        data.language);
      form.append('description',     data.description || '');
      form.append('submission_type', mode);
      if (data.booking_id) form.append('booking_id', data.booking_id);

      if (mode === 'github_link') {
        form.append('github_url', data.github_url);
      } else {
        const fileInput = document.getElementById('file-upload');
        const file = fileInput?.files?.[0];
        if (!file) { setError('Please select a file.'); setSubmitting(false); return; }
        form.append('file', file);
      }

      const res = await submissionApi.create(form);
      navigate(`/submissions/${res?.id || ''}`);
    } catch (e) {
      setError(e.data?.message || e.message);
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div style={{ maxWidth: 680 }}>
      <Link to="/submissions" className="back-link" style={{ display: 'inline-flex', alignItems: 'center', gap: 6, marginBottom: 20, fontSize: '.85rem', color: 'var(--text-muted)' }}>
        ← Back to submissions
      </Link>

      <div style={{ marginBottom: 28 }}>
        <h1 className="page-title">Submit Code Challenge</h1>
        <p className="page-subtitle">Upload a file or share a GitHub repository for your interviewer to review.</p>
      </div>

      {error && <div className="alert alert-error">{error}</div>}

      {/* Mode toggle */}
      <div className="sub-mode-toggle">
        <button
          type="button"
          className={`sub-mode-btn ${mode === 'file_upload' ? 'active' : ''}`}
          onClick={() => setMode('file_upload')}
        >
          📄 File Upload
        </button>
        <button
          type="button"
          className={`sub-mode-btn ${mode === 'github_link' ? 'active' : ''}`}
          onClick={() => setMode('github_link')}
        >
          🔗 GitHub Link
        </button>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        {/* Card 1 — basic info */}
        <div className="sub-section" style={{ marginBottom: 14 }}>
          <div className="sub-section-title">Basic Information</div>

          <div className="form-group">
            <label>Title <span style={{ color: 'var(--error)' }}>*</span></label>
            <input
              type="text"
              className="form-control"
              placeholder="e.g. Two Sum — LeetCode 1"
              {...register('title', { required: 'Title is required' })}
            />
            {errors.title && <span className="field-error">{errors.title.message}</span>}
          </div>

          <div className="form-group" style={{ marginBottom: 0 }}>
            <label>Language</label>
            <select className="form-control" {...register('language')}>
              <option value="">— Select language —</option>
              {LANGUAGES.map(l => <option key={l} value={l}>{l}</option>)}
            </select>
          </div>
        </div>

        {/* Card 2 — submission content */}
        <div className="sub-section" style={{ marginBottom: 14 }}>
          <div className="sub-section-title">
            {mode === 'file_upload' ? 'Upload File' : 'GitHub Repository'}
          </div>

          {mode === 'file_upload' ? (
            <div className="form-group" style={{ marginBottom: 0 }}>
              <div className={`sub-drop-area ${fileInfo ? 'has-file' : ''}`}>
                <input
                  id="file-upload"
                  type="file"
                  className="file-input-hidden"
                  accept=".js,.ts,.py,.java,.go,.cpp,.cs,.rs,.txt,.zip"
                  onChange={handleFileChange}
                />
                <label htmlFor="file-upload" className="sub-drop-label">
                  {fileInfo ? (
                    <>
                      <div className="sub-drop-icon">✅</div>
                      <div className="sub-drop-file">{fileInfo.name}</div>
                      <div className="sub-drop-hint">{fileInfo.size} · Click to change</div>
                    </>
                  ) : (
                    <>
                      <div className="sub-drop-icon">📁</div>
                      <div className="sub-drop-text">
                        Click to select a file or drag &amp; drop<br />
                        <span className="sub-drop-hint">.js .ts .py .java .go .cpp .cs .rs .zip</span>
                      </div>
                    </>
                  )}
                </label>
              </div>
            </div>
          ) : (
            <div className="form-group" style={{ marginBottom: 0 }}>
              <label>Repository URL <span style={{ color: 'var(--error)' }}>*</span></label>
              <input
                type="url"
                className="form-control"
                placeholder="https://github.com/yourusername/repo"
                {...register('github_url', {
                  required: mode === 'github_link' ? 'GitHub URL is required' : false,
                  pattern: { value: /^https:\/\/github\.com\/.+/, message: 'Must be a valid GitHub URL' },
                })}
              />
              {errors.github_url && <span className="field-error">{errors.github_url.message}</span>}
            </div>
          )}
        </div>

        {/* Card 3 — optional details */}
        <div className="sub-section" style={{ marginBottom: 14 }}>
          <div className="sub-section-title">Optional Details</div>

          {bookings.length > 0 && (
            <div className="form-group">
              <label>Link to Booking <span className="muted">(optional)</span></label>
              <select className="form-control" {...register('booking_id')}>
                <option value="">— Not linked to a session —</option>
                {bookings.map(b => (
                  <option key={b.id} value={b.id}>
                    {b.interviewer?.name
                      ? `${b.interview_type?.replace('_', ' ')} with ${b.interviewer.name}`
                      : b.interview_type?.replace('_', ' ')}
                    {' — '}
                    {new Date(b.scheduled_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                  </option>
                ))}
              </select>
              <span className="field-hint" style={{ fontSize: '.78rem', color: 'var(--text-muted)', marginTop: 4 }}>
                Linking lets your interviewer see and review this submission.
              </span>
            </div>
          )}

          <div className="form-group" style={{ marginBottom: 0 }}>
            <label>Description / Notes</label>
            <textarea
              className="form-control"
              rows={3}
              placeholder="Describe your approach, time complexity, or anything you'd like the reviewer to know…"
              {...register('description')}
            />
          </div>
        </div>

        {/* Actions */}
        <div className="form-actions">
          <Link to="/submissions" className="btn btn-ghost">Cancel</Link>
          <button type="submit" className="btn btn-primary" disabled={submitting}>
            {submitting ? 'Submitting…' : 'Submit Challenge'}
          </button>
        </div>
      </form>
    </div>
  );
}
