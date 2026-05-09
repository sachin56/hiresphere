import { NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const IC = ({ d, d2, circle, rect, line, poly }) => (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    {d  && <path d={d}/>}
    {d2 && <path d={d2}/>}
    {circle && <circle cx={circle[0]} cy={circle[1]} r={circle[2]}/>}
    {rect && <rect x={rect[0]} y={rect[1]} width={rect[2]} height={rect[3]} rx={rect[4]}/>}
    {line && line.map((l, i) => <line key={i} x1={l[0]} y1={l[1]} x2={l[2]} y2={l[3]}/>)}
    {poly && <polyline points={poly}/>}
  </svg>
);

const icons = {
  dashboard: <IC d="M3 3h7v7H3z" d2="M14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/>,
  users:     <IC d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" circle={[12, 7, 4]}/>,
  calendar:  <IC rect={[3, 4, 18, 18, 2]} line={[[16,2,16,6],[8,2,8,6],[3,10,21,10]]}/>,
  file:      <IC d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" poly="14 2 14 8 20 8" line={[[16,13,8,13],[16,17,8,17]]}/>,
  chart:     <IC line={[[18,20,18,10],[12,20,12,4],[6,20,6,14]]}/>,
  message:   <IC d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>,
  clock:     <IC circle={[12, 12, 10]} poly="12 6 12 12 16 14"/>,
  shield:    <IC d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>,
};

function initials(name = '') {
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
}

export default function Navbar() {
  const { user, logout, isCandidate, isInterviewer, isAdmin } = useAuth();
  const navigate = useNavigate();

  async function handleLogout() {
    await logout();
    navigate('/auth');
  }

  return (
    <aside className="sidebar">
      <NavLink to="/dashboard" className="sidebar-brand" style={{ textDecoration: 'none' }}>
        <div className="sidebar-brand-box">H</div>
        <span className="sidebar-brand-name">HireSphere</span>
      </NavLink>

      <ul className="sidebar-nav">
        <li>
          <NavLink to="/dashboard" className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}>
            {icons.dashboard} Dashboard
          </NavLink>
        </li>

        {isCandidate && (
          <>
            <li>
              <NavLink to="/interviewers" className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}>
                {icons.users} Interviewers
              </NavLink>
            </li>
            <li>
              <NavLink to="/bookings" className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}>
                {icons.calendar} Bookings
              </NavLink>
            </li>
            <li>
              <NavLink to="/submissions" className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}>
                {icons.file} Submissions
              </NavLink>
            </li>
          </>
        )}

        {isInterviewer && (
          <>
            <li>
              <NavLink to="/bookings" className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}>
                {icons.calendar} Bookings
              </NavLink>
            </li>
            <li>
              <NavLink to="/submissions" className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}>
                {icons.file} Submissions
              </NavLink>
            </li>
          </>
        )}

        <li>
          <NavLink to="/history" className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}>
            {icons.chart} Evaluations
          </NavLink>
        </li>
        <li>
          <NavLink to="/messages" className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}>
            {icons.message} Messages
          </NavLink>
        </li>

        {isInterviewer && (
          <li>
            <NavLink to="/interviewer/availability" className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}>
              {icons.clock} Availability
            </NavLink>
          </li>
        )}

        {isAdmin && (
          <li>
            <NavLink to="/admin" className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}>
              {icons.shield} Approvals
            </NavLink>
          </li>
        )}
      </ul>

      <div className="sidebar-user">
        <div className="sidebar-avatar">{initials(user?.name)}</div>
        <div style={{ flex: 1, minWidth: 0 }}>
          <div className="sidebar-user-name">{user?.name}</div>
          <div className="sidebar-user-role">
            {user?.role === 'admin' ? 'Administrator' : user?.role === 'interviewer' ? 'Interviewer' : 'Candidate'}
          </div>
        </div>
        <button
          onClick={handleLogout}
          title="Sign out"
          style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#64748b', padding: '4px', display: 'flex', alignItems: 'center' }}
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
        </button>
      </div>
    </aside>
  );
}
