import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { submissionApi } from '../../api/client';

function fmtDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function SubmissionCard({ s }) {
  const isGithub   = s.submission_type === 'github_link';
  const isReviewed = s.status === 'reviewed';

  return (
    <div className={`sub-card ${isReviewed ? 'sub-card-green' : 'sub-card-amber'}`}>
      <div className={`sub-icon ${isGithub ? 'sub-icon-github' : 'sub-icon-file'}`}>
        {isGithub ? '🔗' : '📄'}
      </div>

      <div className="sub-card-body">
        <div className="sub-card-row1">
          <span className="sub-card-title">{s.title || 'Untitled'}</span>
          <span className={`badge ${isReviewed ? 'badge-success' : 'badge-warning'}`}>
            {isReviewed ? 'Reviewed' : 'Pending'}
          </span>
        </div>
        <div className="sub-card-row2">
          {s.language && <span className="tag tag-domain">{s.language}</span>}
          <span className={`tag ${isGithub ? 'tag-type' : 'tag-domain'}`}>
            {isGithub ? 'GitHub' : 'File Upload'}
          </span>
          <span className="sub-card-date">{fmtDate(s.created_at)}</span>
        </div>
      </div>

      <Link to={`/submissions/${s.id}`} className="sub-view-btn">View →</Link>
    </div>
  );
}

export default function SubmissionListPage() {
  const [submissions, setSubmissions] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    submissionApi.list()
      .then(r => setSubmissions(r?.data || []))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  const reviewed = submissions.filter(s => s.status === 'reviewed').length;
  const pending  = submissions.length - reviewed;

  return (
    <div>
      <div className="sub-page-header">
        <div>
          <h1>Code Submissions</h1>
          <p>Your submitted coding challenges and interviewer feedback</p>
        </div>
        <Link to="/submissions/new" className="btn btn-primary">+ New Submission</Link>
      </div>

      {/* Stats strip — only when there's data */}
      {!loading && submissions.length > 0 && (
        <div className="sub-stats-row">
          <div className="sub-stat-cell">
            <div className="sub-stat-num">{submissions.length}</div>
            <div className="sub-stat-cap">Total</div>
          </div>
          <div className="sub-stat-cell">
            <div className="sub-stat-num green">{reviewed}</div>
            <div className="sub-stat-cap">Reviewed</div>
          </div>
          <div className="sub-stat-cell">
            <div className="sub-stat-num amber">{pending}</div>
            <div className="sub-stat-cap">Pending</div>
          </div>
        </div>
      )}

      {loading ? (
        <div className="spinner-wrap"><div className="spinner" /></div>
      ) : submissions.length === 0 ? (
        <div className="sub-empty">
          <div className="sub-empty-icon">📂</div>
          <h3>No submissions yet</h3>
          <p>Upload your code or link a GitHub repo for your interviewer to review.</p>
          <Link to="/submissions/new" className="btn btn-primary btn-sm">Submit your first challenge</Link>
        </div>
      ) : (
        <div className="sub-list">
          {submissions.map(s => <SubmissionCard key={s.id} s={s} />)}
        </div>
      )}
    </div>
  );
}
