import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { interviewerApi } from '../../api/client';

export default function InterviewerDetailPage() {
  const { id } = useParams();
  const [interviewer, setInterviewer] = useState(null);
  const [reviews, setReviews]         = useState([]);
  const [availability, setAvailability] = useState([]);
  const [loading, setLoading]         = useState(true);

  useEffect(() => {
    Promise.all([
      interviewerApi.get(id),
      interviewerApi.reviews(id),
      interviewerApi.availability(id),
    ]).then(([i, r, a]) => {
      setInterviewer(i);
      setReviews(r?.data || []);
      setAvailability(a?.data || a || []);
    }).catch(console.error)
      .finally(() => setLoading(false));
  }, [id]);

  if (loading) return <div className="spinner-wrap"><div className="spinner" /></div>;
  if (!interviewer) return <div className="alert alert-error">Interviewer not found.</div>;

  // API returns InterviewerProfile with nested user
  const p    = interviewer;
  const user = interviewer.user || {};
  const rating = p.average_rating ? parseFloat(p.average_rating).toFixed(1) : null;

  return (
    <div className="page page-narrow">
      <Link to="/interviewers" className="back-link">← Back to search</Link>

      {/* Profile header */}
      <div className="profile-hero card">
        <div className="profile-hero-inner">
          <div className="avatar avatar-lg">
            {user.profile_picture
              ? <img src={user.profile_picture} alt={user.name} />
              : <span>{user.name?.charAt(0)}</span>}
          </div>
          <div className="profile-info">
            <h1>{user.name}</h1>
            <p className="profile-title">{p.current_title} · {p.years_of_experience}y exp</p>
            {rating && <p className="profile-rating">⭐ {rating} ({reviews.length} reviews)</p>}
            <div className="tag-row">
              {(p.domains || []).map(d => <span key={d} className="tag tag-domain">{d}</span>)}
            </div>
          </div>
          <div className="profile-cta">
            <p className="price-lg">${p.hourly_rate || 0}<small>/hr</small></p>
            <Link to={`/bookings/new/${interviewer.id}`} className="btn btn-primary">Book a Session</Link>
          </div>
        </div>
      </div>

      <div className="profile-grid">
        {/* Left column */}
        <div>
          {p.interview_approach && (
            <section className="card section-card">
              <h2>About</h2>
              <p>{p.interview_approach}</p>
            </section>
          )}

          <section className="card section-card">
            <h2>Interview Types</h2>
            <div className="tag-row">
              {(p.interview_types || []).map(t =>
                <span key={t} className="tag tag-type">{t.replace('_', ' ')}</span>
              )}
            </div>
          </section>

          {(p.specialization_badges || []).length > 0 && (
            <section className="card section-card">
              <h2>Specialization Badges</h2>
              <div className="tag-row">
                {p.specialization_badges.map(b => <span key={b} className="tag tag-badge">🏅 {b}</span>)}
              </div>
            </section>
          )}
        </div>

        {/* Right column */}
        <div>
          <section className="card section-card">
            <h2>Available Slots</h2>
            {availability.length === 0 ? (
              <p className="muted">No slots available right now.</p>
            ) : (
              <ul className="slot-list">
                {availability.slice(0, 6).map(slot => (
                  <li key={slot.id} className="slot-item">
                    <span>📅 {new Date(slot.start_time).toLocaleString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                    <Link to={`/bookings/new/${interviewer.id}?slot=${slot.id}`} className="btn btn-outline btn-sm">Select</Link>
                  </li>
                ))}
              </ul>
            )}
          </section>

          {reviews.length > 0 && (
            <section className="card section-card">
              <h2>Reviews</h2>
              <ul className="review-list">
                {reviews.slice(0, 3).map(r => (
                  <li key={r.id} className="review-item">
                    <div className="review-header">
                      <span className="review-author">{r.candidate?.name || 'Candidate'}</span>
                      <span className="review-stars">{'⭐'.repeat(r.rating)}</span>
                    </div>
                    {r.comment && <p className="review-comment">{r.comment}</p>}
                  </li>
                ))}
              </ul>
            </section>
          )}
        </div>
      </div>
    </div>
  );
}
