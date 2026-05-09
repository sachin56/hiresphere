import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { interviewerApi } from '../../api/client';

const DOMAINS      = ['Backend', 'Frontend', 'DevOps', 'AI/ML', 'Mobile'];
const TYPES        = ['dsa', 'system_design', 'behavioral'];
const LEVELS       = ['Senior', 'Staff', 'Principal'];

function InterviewerCard({ interviewer }) {
  // API returns InterviewerProfile with nested user
  const p    = interviewer;
  const user = interviewer.user || {};
  const rating = p.average_rating ? parseFloat(p.average_rating).toFixed(1) : null;

  return (
    <div className="card interviewer-card">
      <div className="card-header">
        <div className="avatar">
          {user.profile_picture
            ? <img src={user.profile_picture} alt={user.name} />
            : <span>{user.name?.charAt(0)}</span>}
        </div>
        <div>
          <h3 className="interviewer-name">{user.name}</h3>
          <p className="interviewer-title">{p.current_title || 'Software Engineer'}</p>
        </div>
        {rating && <div className="rating-badge">⭐ {rating}</div>}
      </div>

      <div className="card-body">
        <div className="tag-row">
          {(p.domains || []).slice(0, 3).map(d => <span key={d} className="tag tag-domain">{d}</span>)}
          {(p.interview_types || []).map(t => <span key={t} className="tag tag-type">{t.replace('_', ' ')}</span>)}
        </div>
        <p className="interviewer-bio">{p.interview_approach?.slice(0, 100)}{p.interview_approach?.length > 100 ? '…' : ''}</p>
      </div>

      <div className="card-footer">
        <span className="price">${p.hourly_rate || 0}<small>/hr</small></span>
        <div className="card-actions">
          <Link to={`/interviewers/${interviewer.id}`} className="btn btn-outline btn-sm">Profile</Link>
          <Link to={`/bookings/new/${interviewer.id}`} className="btn btn-primary btn-sm">Book</Link>
        </div>
      </div>
    </div>
  );
}

export default function InterviewerListPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [interviewers, setInterviewers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState(null);

  const filters = {
    domain:        searchParams.get('domain')        || '',
    interview_type: searchParams.get('interview_type') || '',
    experience_level: searchParams.get('experience_level') || '',
    search:        searchParams.get('search')        || '',
  };

  useEffect(() => {
    setLoading(true);
    const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v));
    interviewerApi.list(params)
      .then(res => setInterviewers(res?.data || res || []))
      .catch(e  => setError(e.message))
      .finally(() => setLoading(false));
  }, [searchParams.toString()]);

  function setFilter(key, value) {
    const next = new URLSearchParams(searchParams);
    if (value) next.set(key, value); else next.delete(key);
    setSearchParams(next);
  }

  function clearFilters() { setSearchParams({}); }

  const hasFilters = Object.values(filters).some(Boolean);

  return (
    <div className="page">
      <div className="page-header">
        <div>
          <h1 className="page-title">Find Interviewers</h1>
          <p className="page-subtitle">Browse verified industry professionals</p>
        </div>
      </div>

      {/* Filter bar */}
      <div className="filter-bar">
        <input
          type="search"
          placeholder="Search by name or company…"
          value={filters.search}
          onChange={e => setFilter('search', e.target.value)}
          className="filter-input"
        />
        <select value={filters.domain} onChange={e => setFilter('domain', e.target.value)} className="filter-select">
          <option value="">All Domains</option>
          {DOMAINS.map(d => <option key={d} value={d}>{d}</option>)}
        </select>
        <select value={filters.interview_type} onChange={e => setFilter('interview_type', e.target.value)} className="filter-select">
          <option value="">All Types</option>
          {TYPES.map(t => <option key={t} value={t}>{t.replace('_', ' ')}</option>)}
        </select>
        <select value={filters.experience_level} onChange={e => setFilter('experience_level', e.target.value)} className="filter-select">
          <option value="">All Levels</option>
          {LEVELS.map(l => <option key={l} value={l}>{l}</option>)}
        </select>
        {hasFilters && <button className="btn btn-ghost btn-sm" onClick={clearFilters}>Clear</button>}
      </div>

      {loading ? (
        <div className="spinner-wrap"><div className="spinner" /></div>
      ) : error ? (
        <div className="alert alert-error">{error}</div>
      ) : interviewers.length === 0 ? (
        <div className="empty-state">
          <p>No interviewers found matching your filters.</p>
          {hasFilters && <button className="btn btn-outline" onClick={clearFilters}>Clear filters</button>}
        </div>
      ) : (
        <div className="interviewer-grid">
          {interviewers.map(i => <InterviewerCard key={i.id} interviewer={i} />)}
        </div>
      )}
    </div>
  );
}
