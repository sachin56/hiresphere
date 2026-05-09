import { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { signIn, confirmSignUp } from 'aws-amplify/auth';
import { useAuth } from '../../contexts/AuthContext';

const BASE_URL = import.meta.env.VITE_API_URL || '/api';

async function apiPost(path, body) {
  const res = await fetch(`${BASE_URL}${path}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(body),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.message || 'Request failed');
  return data;
}

// ── Shared input style ────────────────────────────────────────────────────────
const inputStyle = {
  width: '100%', padding: '10px 14px', border: '1px solid #e2e8f0',
  borderRadius: 8, fontSize: 14, outline: 'none', boxSizing: 'border-box',
  fontFamily: 'inherit',
};
const labelStyle = { display: 'block', fontSize: 13, fontWeight: 600, color: '#374151', marginBottom: 4 };
const btnStyle   = {
  width: '100%', padding: '11px', borderRadius: 8, border: 'none',
  background: '#6366f1', color: '#fff', fontWeight: 700, fontSize: 15,
  cursor: 'pointer', marginTop: 8,
};
const errStyle   = {
  background: '#fef2f2', color: '#dc2626', border: '1px solid #fecaca',
  borderRadius: 8, padding: '10px 14px', fontSize: 13, marginBottom: 12,
};

// ── Sign In ───────────────────────────────────────────────────────────────────
function SignInForm({ onSwitch }) {
  const [email, setEmail]       = useState('');
  const [password, setPassword] = useState('');
  const [error, setError]       = useState('');
  const [loading, setLoading]   = useState(false);
  const { refresh }             = useAuth();
  const navigate                = useNavigate();
  const location                = useLocation();
  const from                    = location.state?.from?.pathname || '/dashboard';

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await signIn({ username: email, password });
      await refresh();
      navigate(from, { replace: true });
    } catch (err) {
      setError(err.message || 'Invalid credentials.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
      <div>
        <h2 className="auth-form-title">Welcome back</h2>
        <p className="auth-form-sub">Sign in to your HireSphere account</p>
      </div>

      {error && <div style={errStyle}>{error}</div>}

      <div>
        <label style={labelStyle}>Email</label>
        <input style={inputStyle} type="email" value={email} onChange={e => setEmail(e.target.value)} required placeholder="you@example.com" />
      </div>
      <div>
        <label style={labelStyle}>Password</label>
        <input style={inputStyle} type="password" value={password} onChange={e => setPassword(e.target.value)} required placeholder="••••••••" />
      </div>

      <button style={btnStyle} type="submit" disabled={loading}>
        {loading ? 'Signing in…' : 'Sign in'}
      </button>

      <p style={{ textAlign: 'center', fontSize: 13, color: '#64748b', marginTop: 4 }}>
        No account?{' '}
        <button type="button" onClick={() => onSwitch('register')} style={{ background: 'none', border: 'none', color: '#6366f1', fontWeight: 600, cursor: 'pointer' }}>
          Create one
        </button>
      </p>
    </form>
  );
}

// ── Register ──────────────────────────────────────────────────────────────────
function RegisterForm({ onSwitch, onConfirmNeeded }) {
  const [form, setForm]       = useState({ name: '', email: '', password: '', password_confirmation: '', role: 'candidate' });
  const [error, setError]     = useState('');
  const [loading, setLoading] = useState(false);

  function set(field) { return e => setForm(f => ({ ...f, [field]: e.target.value })); }

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    if (form.password !== form.password_confirmation) {
      setError('Passwords do not match.');
      return;
    }
    setLoading(true);
    try {
      await apiPost('/auth/register', form);
      onConfirmNeeded(form.email);
    } catch (err) {
      setError(err.message || 'Registration failed.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
      <div>
        <h2 className="auth-form-title">Create account</h2>
        <p className="auth-form-sub">Join HireSphere to get started</p>
      </div>

      {error && <div style={errStyle}>{error}</div>}

      <div>
        <label style={labelStyle}>Full Name</label>
        <input style={inputStyle} type="text" value={form.name} onChange={set('name')} required placeholder="Jane Smith" />
      </div>
      <div>
        <label style={labelStyle}>Email</label>
        <input style={inputStyle} type="email" value={form.email} onChange={set('email')} required placeholder="you@example.com" />
      </div>
      <div>
        <label style={labelStyle}>I am a…</label>
        <select style={inputStyle} value={form.role} onChange={set('role')}>
          <option value="candidate">Candidate — looking for mock interviews</option>
          <option value="interviewer">Interviewer — conducting mock interviews</option>
        </select>
      </div>
      <div>
        <label style={labelStyle}>Password</label>
        <input style={inputStyle} type="password" value={form.password} onChange={set('password')} required placeholder="Min 8 characters" />
      </div>
      <div>
        <label style={labelStyle}>Confirm Password</label>
        <input style={inputStyle} type="password" value={form.password_confirmation} onChange={set('password_confirmation')} required placeholder="Repeat password" />
      </div>

      <button style={btnStyle} type="submit" disabled={loading}>
        {loading ? 'Creating account…' : 'Create account'}
      </button>

      <p style={{ textAlign: 'center', fontSize: 13, color: '#64748b', marginTop: 4 }}>
        Already have an account?{' '}
        <button type="button" onClick={() => onSwitch('login')} style={{ background: 'none', border: 'none', color: '#6366f1', fontWeight: 600, cursor: 'pointer' }}>
          Sign in
        </button>
      </p>
    </form>
  );
}

// ── Confirm OTP ───────────────────────────────────────────────────────────────
function ConfirmForm({ email, onDone }) {
  const [code, setCode]       = useState('');
  const [error, setError]     = useState('');
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      // Confirm via Amplify (works even when registered via backend)
      await confirmSignUp({ username: email, confirmationCode: code });
      // Also tell the backend so it marks email_verified_at
      await apiPost('/auth/confirm', { email, code }).catch(() => {});
      onDone();
    } catch (err) {
      setError(err.message || 'Invalid code.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
      <div>
        <h2 className="auth-form-title">Check your email</h2>
        <p className="auth-form-sub">We sent a 6-digit code to <strong>{email}</strong></p>
      </div>

      {error && <div style={errStyle}>{error}</div>}

      <div>
        <label style={labelStyle}>Verification Code</label>
        <input style={{ ...inputStyle, letterSpacing: 6, fontSize: 20, textAlign: 'center' }}
          type="text" inputMode="numeric" maxLength={6}
          value={code} onChange={e => setCode(e.target.value.replace(/\D/g, ''))}
          required placeholder="123456" />
      </div>

      <button style={btnStyle} type="submit" disabled={loading || code.length < 6}>
        {loading ? 'Verifying…' : 'Verify email'}
      </button>
    </form>
  );
}

// ── Hero panel (left side) ────────────────────────────────────────────────────
function Hero() {
  return (
    <div className="auth-hero">
      <div className="auth-logo">
        <div className="auth-logo-box">H</div>
        <h1>HireSphere</h1>
      </div>
      <h2 className="auth-headline">Your next offer<br />starts here.</h2>
      <p className="auth-subtext">
        Get matched with engineers from Google, Meta, Amazon and OpenAI for
        realistic mock interviews with actionable feedback.
      </p>
      <div className="auth-stats">
        <div className="auth-stat"><div className="auth-stat-num">500+</div><div className="auth-stat-label">Expert interviewers</div></div>
        <div className="auth-stat"><div className="auth-stat-num">4.9</div><div className="auth-stat-label">Avg. rating</div></div>
        <div className="auth-stat"><div className="auth-stat-num">92%</div><div className="auth-stat-label">Offer rate</div></div>
      </div>
      <div className="auth-testimonial">
        <div className="auth-stars">★★★★★</div>
        <p className="auth-quote">"Three sessions in, I got an offer from Meta. The feedback was brutal, honest and exactly what I needed."</p>
        <div className="auth-reviewer">
          <div className="auth-reviewer-avatar">SM</div>
          <div>
            <div className="auth-reviewer-name">Sara M.</div>
            <div className="auth-reviewer-role">Software Engineer · Meta</div>
          </div>
        </div>
      </div>
    </div>
  );
}

// ── Main export ───────────────────────────────────────────────────────────────
export default function AuthPage() {
  const [view, setView]             = useState('login');   // 'login' | 'register' | 'confirm'
  const [pendingEmail, setPending]  = useState('');

  function handleConfirmNeeded(email) {
    setPending(email);
    setView('confirm');
  }

  function handleConfirmed() {
    setView('login');
  }

  return (
    <div className="auth-page">
      <Hero />
      <div className="auth-form-panel">
        <div className="auth-form-wrap">
          {view === 'login'    && <SignInForm    onSwitch={setView} />}
          {view === 'register' && <RegisterForm  onSwitch={setView} onConfirmNeeded={handleConfirmNeeded} />}
          {view === 'confirm'  && <ConfirmForm   email={pendingEmail} onDone={handleConfirmed} />}
        </div>
      </div>
    </div>
  );
}
