import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';

import AuthPage              from './pages/auth/AuthPage';
import DashboardPage         from './pages/dashboard/DashboardPage';
import InterviewerListPage        from './pages/interviewers/InterviewerListPage';
import InterviewerDetailPage      from './pages/interviewers/InterviewerDetailPage';
import InterviewerProfileEditPage from './pages/interviewers/InterviewerProfileEditPage';
import AvailabilityPage           from './pages/interviewer/AvailabilityPage';
import MessagesPage               from './pages/messages/MessagesPage';
import BookingListPage       from './pages/bookings/BookingListPage';
import BookingCreatePage     from './pages/bookings/BookingCreatePage';
import BookingDetailPage     from './pages/bookings/BookingDetailPage';
import SubmissionListPage    from './pages/submissions/SubmissionListPage';
import SubmissionCreatePage  from './pages/submissions/SubmissionCreatePage';
import SubmissionDetailPage  from './pages/submissions/SubmissionDetailPage';
import HistoryPage           from './pages/history/HistoryPage';
import EvaluationCreatePage  from './pages/evaluations/EvaluationCreatePage';
import AdminPage             from './pages/admin/AdminPage';

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          {/* Public */}
          <Route path="/auth" element={<AuthPage />} />

          {/* Protected — all inside layout shell */}
          <Route element={<ProtectedRoute><Layout /></ProtectedRoute>}>
            <Route index element={<Navigate to="/dashboard" replace />} />
            <Route path="/dashboard" element={<DashboardPage />} />

            {/* Interviewer browsing — candidates only */}
            <Route path="/interviewers"     element={<ProtectedRoute role="candidate"><InterviewerListPage /></ProtectedRoute>} />
            <Route path="/interviewers/:id" element={<ProtectedRoute role="candidate"><InterviewerDetailPage /></ProtectedRoute>} />

            {/* Interviewer profile edit */}
            <Route path="/interviewer/profile/edit" element={<ProtectedRoute role="interviewer"><InterviewerProfileEditPage /></ProtectedRoute>} />
            <Route path="/interviewer/availability"  element={<ProtectedRoute role="interviewer"><AvailabilityPage /></ProtectedRoute>} />

            {/* Booking flow */}
            <Route path="/bookings"                       element={<BookingListPage />} />
            <Route path="/bookings/new/:interviewerId"    element={<ProtectedRoute role="candidate"><BookingCreatePage /></ProtectedRoute>} />
            <Route path="/bookings/:id"                   element={<BookingDetailPage />} />
            <Route path="/bookings/:id/evaluate"          element={<ProtectedRoute role="interviewer"><EvaluationCreatePage /></ProtectedRoute>} />

            {/* Submissions */}
            <Route path="/submissions"      element={<ProtectedRoute><SubmissionListPage /></ProtectedRoute>} />
            <Route path="/submissions/new"  element={<ProtectedRoute role="candidate"><SubmissionCreatePage /></ProtectedRoute>} />
            <Route path="/submissions/:id"  element={<SubmissionDetailPage />} />

            {/* Interview history */}
            <Route path="/history" element={<HistoryPage />} />

            {/* Messages */}
            <Route path="/messages"         element={<MessagesPage />} />
            <Route path="/messages/:userId" element={<MessagesPage />} />

            {/* Admin */}
            <Route path="/admin" element={<ProtectedRoute role="admin"><AdminPage /></ProtectedRoute>} />
          </Route>

          <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
