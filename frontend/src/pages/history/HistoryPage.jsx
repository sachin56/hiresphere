import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { bookingApi, evaluationApi } from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';

const STATUS_CLASS = {
  pending: 'badge-warning', accepted: 'badge-success',
  completed: 'badge-neutral', cancelled: 'badge-error', rejected: 'badge-error',
};

function EvalCard({ evaluation }) {
  const score = evaluation.overall_score;
  const scoreColor = score >= 8 ? '#16a34a' : score >= 5 ? '#d97706' : '#dc2626';

  return (
    <div className="card eval-card">
      <div className="eval-header">
        <div>
          <h3 className="eval-title">Evaluation Report</h3>
          <p className="muted">
            {evaluation.created_at ? new Date(evaluation.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : '—'}
          </p>
        </div>
        <div className="score-circle" style={{ borderColor: scoreColor, color: scoreColor }}>
          {score}<small>/10</small>
        </div>
      </div>

      {evaluation.technical_score !== undefined && (
        <div className="eval-scores">
          <div className="eval-score-item">
            <span>Technical</span>
            <div className="score-bar"><div className="score-fill" style={{ width: `${evaluation.technical_score * 10}%` }} /></div>
            <span>{evaluation.technical_score}/10</span>
          </div>
          <div className="eval-score-item">
            <span>Communication</span>
            <div className="score-bar"><div className="score-fill" style={{ width: `${evaluation.communication_score * 10}%` }} /></div>
            <span>{evaluation.communication_score}/10</span>
          </div>
          <div className="eval-score-item">
            <span>Problem Solving</span>
            <div className="score-bar"><div className="score-fill" style={{ width: `${evaluation.problem_solving_score * 10}%` }} /></div>
            <span>{evaluation.problem_solving_score}/10</span>
          </div>
        </div>
      )}

      {evaluation.feedback && (
        <div className="eval-feedback">
          <strong>Feedback</strong>
          <p>{evaluation.feedback}</p>
        </div>
      )}

      {evaluation.strengths && (
        <div className="eval-feedback">
          <strong>Strengths</strong>
          <p>{evaluation.strengths}</p>
        </div>
      )}

      {evaluation.areas_for_improvement && (
        <div className="eval-feedback">
          <strong>Areas for Improvement</strong>
          <p>{evaluation.areas_for_improvement}</p>
        </div>
      )}
    </div>
  );
}

function BookingHistoryRow({ booking, isCandidate }) {
  const other = isCandidate ? booking.interviewer : booking.candidate;
  return (
    <tr>
      <td>{other?.name || '—'}</td>
      <td className="capitalize">{booking.interview_type?.replace('_', ' ')}</td>
      <td>{booking.scheduled_at ? new Date(booking.scheduled_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
      <td><span className={`badge ${STATUS_CLASS[booking.status] || 'badge-neutral'}`}>{booking.status}</span></td>
      <td><Link to={`/bookings/${booking.id}`} className="btn btn-ghost btn-sm">View</Link></td>
    </tr>
  );
}

export default function HistoryPage() {
  const { isCandidate } = useAuth();
  const [bookings, setBookings]         = useState([]);
  const [evaluations, setEvaluations]   = useState([]);
  const [activeTab, setActiveTab]       = useState('bookings');
  const [loading, setLoading]           = useState(true);

  useEffect(() => {
    Promise.all([bookingApi.list(), evaluationApi.list()])
      .then(([b, e]) => {
        setBookings(b?.data || []);
        setEvaluations(e?.data || []);
      })
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  const pastBookings = [...bookings].sort((a, b) => new Date(b.scheduled_at) - new Date(a.scheduled_at));

  return (
    <div className="page">
      <div className="page-header">
        <div>
          <h1 className="page-title">Interview History</h1>
          <p className="page-subtitle">Past sessions and evaluation reports</p>
        </div>
      </div>

      <div className="tab-bar">
        <button className={`tab-btn ${activeTab === 'bookings' ? 'tab-active' : ''}`} onClick={() => setActiveTab('bookings')}>
          Sessions ({pastBookings.length})
        </button>
        <button className={`tab-btn ${activeTab === 'evaluations' ? 'tab-active' : ''}`} onClick={() => setActiveTab('evaluations')}>
          Evaluations ({evaluations.length})
        </button>
      </div>

      {loading ? (
        <div className="spinner-wrap"><div className="spinner" /></div>
      ) : activeTab === 'bookings' ? (
        pastBookings.length === 0 ? (
          <div className="empty-state">
            <p>No past sessions yet.</p>
            {isCandidate && <Link to="/interviewers" className="btn btn-primary btn-sm">Book your first interview</Link>}
          </div>
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>{isCandidate ? 'Interviewer' : 'Candidate'}</th>
                  <th>Type</th><th>Date</th><th>Status</th><th></th>
                </tr>
              </thead>
              <tbody>
                {pastBookings.map(b => <BookingHistoryRow key={b.id} booking={b} isCandidate={isCandidate} />)}
              </tbody>
            </table>
          </div>
        )
      ) : (
        evaluations.length === 0 ? (
          <div className="empty-state"><p>No evaluation reports yet.</p></div>
        ) : (
          <div className="eval-grid">
            {evaluations.map(e => <EvalCard key={e.id} evaluation={e} />)}
          </div>
        )
      )}
    </div>
  );
}
