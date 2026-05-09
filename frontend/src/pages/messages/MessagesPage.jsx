import { useEffect, useRef, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { messageApi, bookingApi } from '../../api/client';

function ConversationItem({ conv, activeUserId, onClick }) {
  const other    = conv.other_participant;
  const isActive = other?.id === activeUserId;
  return (
    <button className={`conv-item ${isActive ? 'conv-item-active' : ''}`} onClick={() => onClick(other?.id)}>
      <div className="avatar avatar-sm">
        {other?.profile_picture
          ? <img src={other.profile_picture} alt={other.name} />
          : <span>{other?.name?.charAt(0)}</span>}
      </div>
      <div className="conv-info">
        <span className="conv-name">{other?.name || 'Unknown'}</span>
        <span className="conv-last">{conv.last_message || 'No messages yet'}</span>
      </div>
    </button>
  );
}

function ContactItem({ user, activeUserId, onClick }) {
  const isActive = user?.id === activeUserId;
  return (
    <button className={`conv-item ${isActive ? 'conv-item-active' : ''}`} onClick={() => onClick(user?.id)}>
      <div className="avatar avatar-sm">
        {user?.profile_picture
          ? <img src={user.profile_picture} alt={user.name} />
          : <span>{user?.name?.charAt(0)}</span>}
      </div>
      <div className="conv-info">
        <span className="conv-name">{user?.name}</span>
        <span className="conv-last">Start a conversation</span>
      </div>
    </button>
  );
}

function MessageBubble({ msg, isMine }) {
  return (
    <div className={`msg-row ${isMine ? 'msg-row-mine' : 'msg-row-theirs'}`}>
      <div className={`msg-bubble ${isMine ? 'msg-bubble-mine' : 'msg-bubble-theirs'}`}>
        {msg.type === 'code'
          ? <pre className="msg-code">{msg.content}</pre>
          : <p>{msg.content}</p>}
        <span className="msg-time">
          {new Date(msg.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
        </span>
      </div>
    </div>
  );
}

export default function MessagesPage() {
  const { userId }  = useParams();
  const navigate    = useNavigate();
  const { user, isCandidate } = useAuth();

  const [conversations, setConversations] = useState([]);
  const [contacts,      setContacts]      = useState([]);
  const [messages,      setMessages]      = useState([]);
  const [text,          setText]          = useState('');
  const [sending,       setSending]       = useState(false);
  const [loadingConvs,  setLoadingConvs]  = useState(true);
  const [loadingMsgs,   setLoadingMsgs]   = useState(false);
  const [error,         setError]         = useState(null);

  const bottomRef = useRef(null);
  const pollRef   = useRef(null);

  // Load conversations + booking contacts
  useEffect(() => {
    Promise.all([
      messageApi.conversations().catch(() => []),
      bookingApi.list().catch(() => ({ data: [] })),
    ]).then(([convs, bookings]) => {
      setConversations(convs || []);

      // Extract unique contacts from bookings not already in conversations
      const convUserIds = new Set((convs || []).map(c => c.other_participant?.id).filter(Boolean));
      const bookingList = bookings?.data || bookings || [];
      const seen        = new Set();
      const contacts    = [];

      bookingList.forEach(b => {
        const other = isCandidate ? b.interviewer : b.candidate;
        if (other?.id && !convUserIds.has(other.id) && !seen.has(other.id)) {
          seen.add(other.id);
          contacts.push(other);
        }
      });

      setContacts(contacts);
    }).finally(() => setLoadingConvs(false));
  }, [isCandidate]);

  // Load messages when userId param changes
  useEffect(() => {
    if (!userId) { setMessages([]); return; }
    loadMessages(userId);
    pollRef.current = setInterval(() => loadMessages(userId), 5000);
    return () => clearInterval(pollRef.current);
  }, [userId]);

  async function loadMessages(uid) {
    setLoadingMsgs(true);
    try {
      const res = await messageApi.messages(uid);
      setMessages(res.messages || []);
      // refresh sidebar
      messageApi.conversations().then(c => {
        setConversations(c || []);
        setContacts(prev => prev.filter(p => !c?.some(cv => cv.other_participant?.id === p.id)));
      }).catch(() => {});
    } catch (e) {
      setError(e.data?.message || e.message);
    } finally {
      setLoadingMsgs(false);
    }
  }

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  function openConversation(uid) {
    if (uid) navigate(`/messages/${uid}`);
  }

  async function handleSend(e) {
    e.preventDefault();
    if (!text.trim() || !userId) return;
    setSending(true);
    try {
      const msg = await messageApi.send(userId, text.trim());
      setMessages(prev => [...prev, msg]);
      setText('');
      messageApi.conversations().then(c => {
        setConversations(c || []);
        setContacts(prev => prev.filter(p => !c?.some(cv => cv.other_participant?.id === p.id)));
      }).catch(() => {});
    } catch (e) {
      setError(e.data?.message || e.message);
    } finally {
      setSending(false);
    }
  }

  function handleKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend(e); }
  }

  const allConvUserIds = conversations.map(c => c.other_participant?.id);
  const otherUser =
    conversations.find(c => c.other_participant?.id === userId)?.other_participant ||
    contacts.find(c => c.id === userId);

  const hasAnything = conversations.length > 0 || contacts.length > 0;

  return (
    <div className="page page-messages">
      <h1 className="page-title">Messages</h1>

      <div className="messages-layout">

        {/* Sidebar */}
        <aside className="conv-sidebar">
          {loadingConvs ? (
            <div className="spinner-wrap"><div className="spinner" /></div>
          ) : !hasAnything ? (
            <div style={{ padding: '1rem' }}>
              <p className="muted" style={{ marginBottom: '0.5rem' }}>No contacts yet.</p>
              <p className="muted" style={{ fontSize: '0.8rem' }}>Book or accept an interview to message the other person.</p>
            </div>
          ) : (
            <>
              {conversations.length > 0 && (
                <>
                  <div className="conv-section-label">Conversations</div>
                  {conversations.map(conv => (
                    <ConversationItem
                      key={conv.conversation_id}
                      conv={conv}
                      activeUserId={userId}
                      onClick={openConversation}
                    />
                  ))}
                </>
              )}
              {contacts.length > 0 && (
                <>
                  <div className="conv-section-label">Start a conversation</div>
                  {contacts.map(c => (
                    <ContactItem
                      key={c.id}
                      user={c}
                      activeUserId={userId}
                      onClick={openConversation}
                    />
                  ))}
                </>
              )}
            </>
          )}
        </aside>

        {/* Chat panel */}
        <div className="chat-panel">
          {!userId ? (
            <div className="chat-empty">
              <p className="muted">
                {hasAnything
                  ? 'Select a contact on the left to start chatting.'
                  : 'No bookings yet — book an interview to message someone.'}
              </p>
            </div>
          ) : (
            <>
              <div className="chat-header">
                <div className="avatar avatar-sm">
                  {otherUser?.profile_picture
                    ? <img src={otherUser.profile_picture} alt={otherUser.name} />
                    : <span>{otherUser?.name?.charAt(0)}</span>}
                </div>
                <span className="chat-header-name">{otherUser?.name || '…'}</span>
              </div>

              <div className="chat-messages">
                {loadingMsgs && messages.length === 0 ? (
                  <div className="spinner-wrap"><div className="spinner" /></div>
                ) : messages.length === 0 ? (
                  <p className="muted chat-empty-msg">No messages yet. Say hello!</p>
                ) : (
                  messages.map(msg => (
                    <MessageBubble key={msg.message_id} msg={msg} isMine={msg.sender_id === user?.id} />
                  ))
                )}
                <div ref={bottomRef} />
              </div>

              {error && <div className="alert alert-error" style={{ margin: '0 1rem' }}>{error}</div>}

              <form className="chat-input-bar" onSubmit={handleSend}>
                <textarea
                  className="chat-input"
                  rows={1}
                  placeholder="Type a message… (Enter to send, Shift+Enter for new line)"
                  value={text}
                  onChange={e => setText(e.target.value)}
                  onKeyDown={handleKeyDown}
                />
                <button type="submit" className="btn btn-primary" disabled={sending || !text.trim()}>
                  {sending ? '…' : 'Send'}
                </button>
              </form>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
