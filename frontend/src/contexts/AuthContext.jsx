import { createContext, useContext, useEffect, useState } from 'react';
import { getCurrentUser, signOut, fetchAuthSession, fetchUserAttributes } from 'aws-amplify/auth';
import { Hub } from 'aws-amplify/utils';
import { authApi } from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser]       = useState(null);
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(true);

  async function loadUser() {
    try {
      // Step 1: confirm Cognito session is active
      const cognitoUser = await getCurrentUser();
      const attrs        = await fetchUserAttributes();

      // Step 2: try to enrich with backend profile
      try {
        const data = await authApi.me();
        setUser(data);
        setProfile(data.candidate_profile || data.interviewer_profile || null);
      } catch {
        // Backend unreachable or user not yet in DB — build user from Cognito claims
        setUser({
          id:          cognitoUser.userId,
          cognito_sub: cognitoUser.userId,
          name:        attrs.name || attrs.email?.split('@')[0] || 'User',
          email:       attrs.email || '',
          role:        attrs['custom:role'] || 'candidate',
        });
        setProfile(null);
      }
    } catch {
      // No active Cognito session
      setUser(null);
      setProfile(null);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadUser();

    const unsubscribe = Hub.listen('auth', ({ payload }) => {
      if (payload.event === 'signedIn')  loadUser();
      if (payload.event === 'signedOut') { setUser(null); setProfile(null); }
    });

    return unsubscribe;
  }, []);

  async function logout() {
    try { await authApi.logout(); } catch { /* best effort */ }
    await signOut();
    setUser(null);
    setProfile(null);
  }

  async function getToken() {
    const session = await fetchAuthSession();
    return session.tokens?.idToken?.toString() || null;
  }

  const isCandidate   = user?.role === 'candidate';
  const isInterviewer = user?.role === 'interviewer';
  const isAdmin       = user?.role === 'admin';

  return (
    <AuthContext.Provider value={{ user, profile, loading, logout, getToken, isCandidate, isInterviewer, isAdmin, refresh: loadUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider');
  return ctx;
}
