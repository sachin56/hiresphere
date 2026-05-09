import { useEffect, useState } from 'react';
import { adminApi } from '../../api/client';

export default function AdminPage() {
  const [pending, setPending]   = useState([]);
  const [loading, setLoading]   = useState(true);
  const [working, setWorking]   = useState(null); // id currently being processed
  const [message, setMessage]   = useState(null);

  async function load() {
    try {
      const data = await adminApi.pendingInterviewers();
      setPending(data.data ?? data);
    } catch {
      setMessage({ type: 'error', text: 'Failed to load pending interviewers.' });
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  async function handleApprove(id) {
    setWorking(id);
    try {
      await adminApi.approve(id);
      setMessage({ type: 'success', text: 'Interviewer approved.' });
      setPending(prev => prev.filter(p => p.id !== id));
    } catch {
      setMessage({ type: 'error', text: 'Approval failed. Try again.' });
    } finally {
      setWorking(null);
    }
  }

  async function handleReject(id) {
    setWorking(id);
    try {
      await adminApi.reject(id);
      setMessage({ type: 'success', text: 'Interviewer rejected.' });
      setPending(prev => prev.filter(p => p.id !== id));
    } catch {
      setMessage({ type: 'error', text: 'Rejection failed. Try again.' });
    } finally {
      setWorking(null);
    }
  }

  return (
    <div style={{ maxWidth: 900, margin: '0 auto', padding: '32px 24px' }}>
      <h1 style={{ fontSize: 24, fontWeight: 700, marginBottom: 4 }}>Admin — Interviewer Approvals</h1>
      <p style={{ color: '#64748b', marginBottom: 24 }}>
        Review and approve interviewer registrations before they appear in search results.
      </p>

      {message && (
        <div style={{
          padding: '12px 16px', borderRadius: 8, marginBottom: 20,
          background: message.type === 'success' ? '#f0fdf4' : '#fef2f2',
          color:      message.type === 'success' ? '#15803d'  : '#dc2626',
          border:     `1px solid ${message.type === 'success' ? '#bbf7d0' : '#fecaca'}`,
        }}>
          {message.text}
          <button onClick={() => setMessage(null)} style={{ float: 'right', background: 'none', border: 'none', cursor: 'pointer', color: 'inherit' }}>✕</button>
        </div>
      )}

      {loading ? (
        <div style={{ textAlign: 'center', padding: 48, color: '#94a3b8' }}>Loading…</div>
      ) : pending.length === 0 ? (
        <div style={{ textAlign: 'center', padding: 48, color: '#94a3b8', background: '#f8fafc', borderRadius: 12, border: '1px dashed #e2e8f0' }}>
          No pending interviewers to review.
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          {pending.map(profile => (
            <div key={profile.id} style={{
              background: '#fff', border: '1px solid #e2e8f0', borderRadius: 12, padding: 20,
              display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16,
            }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
                <div style={{
                  width: 44, height: 44, borderRadius: '50%', background: '#6366f1',
                  color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center',
                  fontWeight: 700, fontSize: 16, flexShrink: 0,
                }}>
                  {(profile.user?.name || 'U').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()}
                </div>
                <div>
                  <div style={{ fontWeight: 600, fontSize: 15 }}>{profile.user?.name || '—'}</div>
                  <div style={{ color: '#64748b', fontSize: 13 }}>{profile.user?.email || '—'}</div>
                  <div style={{ color: '#94a3b8', fontSize: 12, marginTop: 2 }}>
                    {profile.current_title || 'No title set'}
                    {profile.years_of_experience != null ? ` · ${profile.years_of_experience}y exp` : ''}
                    {profile.domains?.length ? ` · ${profile.domains.join(', ')}` : ''}
                  </div>
                </div>
              </div>

              <div style={{ display: 'flex', gap: 10, flexShrink: 0 }}>
                <button
                  onClick={() => handleApprove(profile.id)}
                  disabled={working === profile.id}
                  style={{
                    padding: '8px 18px', borderRadius: 8, border: 'none', cursor: 'pointer',
                    background: '#22c55e', color: '#fff', fontWeight: 600, fontSize: 14,
                    opacity: working === profile.id ? 0.6 : 1,
                  }}
                >
                  {working === profile.id ? '…' : 'Approve'}
                </button>
                <button
                  onClick={() => handleReject(profile.id)}
                  disabled={working === profile.id}
                  style={{
                    padding: '8px 18px', borderRadius: 8, border: '1px solid #e2e8f0', cursor: 'pointer',
                    background: '#fff', color: '#ef4444', fontWeight: 600, fontSize: 14,
                    opacity: working === profile.id ? 0.6 : 1,
                  }}
                >
                  {working === profile.id ? '…' : 'Reject'}
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
