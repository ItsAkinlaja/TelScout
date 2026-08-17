import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useLocation } from 'react-router-dom'
import {
  Mail, CheckCircle, Trash2, Star, Plug,
  Eye, EyeOff, AlertCircle, ExternalLink, RefreshCw,
} from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../../lib/api'
import Button from '../../components/ui/Button'
import LoadingSpinner from '../../components/ui/LoadingSpinner'
import { formatDate } from '../../lib/utils'
import { TsPageStyles } from '../../components/ui/TsShared'

const PROVIDERS = [
  { id: 'gmail',   label: 'Gmail',                   type: 'oauth' },
  { id: 'outlook', label: 'Outlook / Microsoft 365', type: 'oauth' },
  { id: 'zoho',    label: 'Zoho Mail',                type: 'smtp' },
  { id: 'yahoo',   label: 'Yahoo Mail',               type: 'smtp' },
  { id: 'smtp',    label: 'Custom SMTP',              type: 'smtp' },
]

const SMTP_DEFAULTS: Record<string, { host: string; port: number; encryption: string }> = {
  zoho:  { host: 'smtp.zoho.com',       port: 587, encryption: 'tls' },
  yahoo: { host: 'smtp.mail.yahoo.com', port: 587, encryption: 'tls' },
  smtp:  { host: '',                     port: 587, encryption: 'tls' },
}

export default function MailSettingsPage() {
  const qc = useQueryClient()
  const location = useLocation()
  const [selectedProvider, setSelectedProvider] = useState<string | null>(null)
  const [showForm, setShowForm] = useState(false)

  useEffect(() => {
    const p = new URLSearchParams(location.search)
    if (p.get('connected') === '1') {
      toast.success(`${(p.get('provider') ?? 'Mail')} connected!`)
      qc.invalidateQueries({ queryKey: ['mail-accounts'] })
    } else if (p.get('error')) {
      toast.error(`Connection failed: ${p.get('error')}`)
    }
  }, [location.search])

  const { data: accounts = [], isLoading } = useQuery({
    queryKey: ['mail-accounts'],
    queryFn: () => api.get('/mail/accounts').then(r => r.data),
  })

  const disconnect = useMutation({
    mutationFn: (id: number) => api.delete(`/mail/accounts/${id}`),
    onSuccess: () => { toast.success('Account disconnected'); qc.invalidateQueries({ queryKey: ['mail-accounts'] }) },
  })
  const setDefault = useMutation({
    mutationFn: (id: number) => api.post(`/mail/accounts/${id}/default`),
    onSuccess: () => { toast.success('Default updated'); qc.invalidateQueries({ queryKey: ['mail-accounts'] }) },
  })
  const testConn = useMutation({
    mutationFn: (id: number) => api.post(`/mail/accounts/${id}/test`),
    onSuccess: (r) => toast.success(r.data.message),
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Test failed'),
  })

  if (isLoading) return <LoadingSpinner />

  return (
    <>
      <div style={{ maxWidth: 600, display: 'flex', flexDirection: 'column', gap: 14 }}>

        {/* Header */}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <div>
            <p style={{ fontSize: 14, fontWeight: 600, color: 'var(--text)' }}>Mail Accounts</p>
            <p style={{ fontSize: 13, color: 'var(--text3)', marginTop: 2 }}>Connect any email provider to send outreach emails.</p>
          </div>
          <Button size="sm" icon={<Plug size={13} strokeWidth={1.75} />} onClick={() => setShowForm(v => !v)}>
            {showForm ? 'Cancel' : 'Connect Account'}
          </Button>
        </div>

        {/* Connected accounts */}
        {accounts.map((acc: any) => (
          <div key={acc.id} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '14px 16px', background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 10 }}>
            <div style={{ width: 36, height: 36, borderRadius: 8, background: 'var(--accent-bg)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, color: 'var(--accent-t)' }}>
              <Mail size={16} strokeWidth={1.75} />
            </div>
            <div style={{ flex: 1, minWidth: 0 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
                <p style={{ fontSize: 13.5, fontWeight: 500, color: 'var(--text)' }}>{acc.provider_label}</p>
                {acc.is_default && <span className="ts-pill ts-pill-accent">Default</span>}
                {acc.is_oauth && acc.token_expired && <span className="ts-pill ts-pill-red">Token expired</span>}
              </div>
              <p style={{ fontSize: 12, color: 'var(--text4)', marginTop: 2 }}>{acc.email ?? acc.smtp_host ?? '—'}</p>
              {acc.connected_at && <p style={{ fontSize: 11, color: 'var(--text5)', marginTop: 1 }}>Connected {formatDate(acc.connected_at)}</p>}
            </div>
            <div style={{ display: 'flex', gap: 4 }}>
              <button onClick={() => testConn.mutate(acc.id)} title="Test connection"
                style={{ width: 30, height: 30, borderRadius: 7, background: 'none', border: 'none', color: 'var(--text4)', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <RefreshCw size={14} strokeWidth={1.75} />
              </button>
              {!acc.is_default && (
                <button onClick={() => setDefault.mutate(acc.id)} title="Set as default"
                  style={{ width: 30, height: 30, borderRadius: 7, background: 'none', border: 'none', color: 'var(--text4)', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <Star size={14} strokeWidth={1.75} />
                </button>
              )}
              <button onClick={() => disconnect.mutate(acc.id)} title="Disconnect"
                style={{ width: 30, height: 30, borderRadius: 7, background: 'none', border: 'none', color: 'var(--text4)', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <Trash2 size={14} strokeWidth={1.75} />
              </button>
            </div>
          </div>
        ))}

        {accounts.length === 0 && !showForm && (
          <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', padding: '48px 24px', background: 'var(--surface)', border: '1px dashed var(--border2)', borderRadius: 10 }}>
            <Mail size={28} strokeWidth={1.25} style={{ color: 'var(--text5)', marginBottom: 12 }} />
            <p style={{ fontSize: 14, fontWeight: 500, color: 'var(--text2)' }}>No mail accounts connected</p>
            <p style={{ fontSize: 13, color: 'var(--text4)', marginTop: 4, textAlign: 'center' }}>Connect Gmail, Outlook, Zoho, or any SMTP account.</p>
          </div>
        )}

        {/* Add account form */}
        {showForm && (
          <div style={{ background: 'var(--surface)', border: '1px solid var(--border2)', borderRadius: 10, padding: 20 }}>
            <p style={{ fontSize: 13.5, fontWeight: 600, color: 'var(--text)', marginBottom: 14 }}>Choose a provider</p>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 8, marginBottom: 20 }}>
              {PROVIDERS.map(p => (
                <button key={p.id} onClick={() => setSelectedProvider(p.id)}
                  style={{
                    padding: '10px 12px', borderRadius: 9, textAlign: 'left',
                    background: selectedProvider === p.id ? 'var(--accent-bg)' : 'var(--surface2)',
                    border: `1px solid ${selectedProvider === p.id ? 'rgba(59,130,246,0.3)' : 'var(--border)'}`,
                    cursor: 'pointer', fontFamily: 'inherit', transition: 'all .15s',
                  }}>
                  <p style={{ fontSize: 12.5, fontWeight: 600, color: 'var(--text)' }}>{p.label}</p>
                  <p style={{ fontSize: 11, color: 'var(--text4)', marginTop: 2 }}>{p.type === 'oauth' ? 'OAuth 2.0' : 'SMTP'}</p>
                </button>
              ))}
            </div>
            {selectedProvider && (
              <ProviderForm provider={selectedProvider}
                onClose={() => { setShowForm(false); setSelectedProvider(null) }}
                onSuccess={() => { setShowForm(false); setSelectedProvider(null); qc.invalidateQueries({ queryKey: ['mail-accounts'] }) }} />
            )}
          </div>
        )}

        {/* Security note */}
        <div style={{ background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 10, padding: 16 }}>
          <p style={{ fontSize: 13, fontWeight: 500, color: 'var(--text2)', marginBottom: 6 }}>Security</p>
          <p style={{ fontSize: 12.5, color: 'var(--text4)', lineHeight: 1.65 }}>
            All credentials are encrypted at rest. OAuth tokens are stored encrypted and never logged.
            For SMTP, use an app password — not your main account password.
            The default account is used for all outreach sending.
          </p>
        </div>
      </div>
      <TsPageStyles />
    </>
  )
}

function ProviderForm({ provider, onClose, onSuccess }: { provider: string; onClose: () => void; onSuccess: () => void }) {
  const pInfo = PROVIDERS.find(p => p.id === provider)!
  const isOAuth = pInfo.type === 'oauth'
  const defaults = SMTP_DEFAULTS[provider] ?? SMTP_DEFAULTS.smtp
  const [form, setForm] = useState<any>({
    provider,
    label: '',
    email: '',
    oauth_client_id: '',
    oauth_client_secret: '',
    oauth_redirect_uri: `${window.location.origin.replace('5173', '8000')}/api/mail/callback`,
    smtp_host: defaults.host,
    smtp_port: defaults.port,
    smtp_encryption: defaults.encryption,
    smtp_username: '',
    smtp_password: '',
  })
  const [showPw, setShowPw] = useState(false)

  const connect = useMutation({
    mutationFn: () => api.post('/mail/accounts', form),
    onSuccess: (res) => {
      if (res.data.redirect_url) {
        window.location.href = res.data.redirect_url
      } else {
        toast.success(res.data.message ?? 'Account connected!')
        onSuccess()
      }
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Connection failed'),
  })

  const set = (k: string, v: any) => setForm((f: any) => ({ ...f, [k]: v }))

  return (
    <div style={{ borderTop: '1px solid var(--border)', paddingTop: 16, display: 'flex', flexDirection: 'column', gap: 12 }}>
      <p style={{ fontSize: 13.5, fontWeight: 600, color: 'var(--text)' }}>{pInfo.label}</p>

      <div>
        <label className="ts-label">Account Label (optional)</label>
        <input value={form.label} onChange={e => set('label', e.target.value)} placeholder="e.g. Work Gmail" className="ts-input" style={{ marginTop: 6 }} />
      </div>

      {isOAuth ? (
        <>
          <div style={{ background: 'var(--accent-bg)', border: '1px solid rgba(59,130,246,0.2)', borderRadius: 8, padding: 12, fontSize: 12.5, color: 'var(--text2)', lineHeight: 1.6 }}>
            {provider === 'gmail' && <>Create OAuth credentials at <a href="https://console.cloud.google.com" target="_blank" rel="noopener noreferrer" style={{ color: 'var(--accent)' }}>console.cloud.google.com</a> → Enable Gmail API → OAuth 2.0 credentials</>}
            {provider === 'outlook' && <>Register at <a href="https://portal.azure.com" target="_blank" rel="noopener noreferrer" style={{ color: 'var(--accent)' }}>portal.azure.com</a> → App registrations</>}
            <br />Redirect URI: <code style={{ background: 'var(--surface2)', padding: '1px 5px', borderRadius: 4, fontSize: 11 }}>{form.oauth_redirect_uri}</code>
          </div>
          <div>
            <label className="ts-label">Client ID</label>
            <input value={form.oauth_client_id} onChange={e => set('oauth_client_id', e.target.value)} placeholder="OAuth Client ID" className="ts-input" style={{ marginTop: 6 }} />
          </div>
          <div>
            <label className="ts-label">Client Secret</label>
            <div style={{ position: 'relative', marginTop: 6 }}>
              <input type={showPw ? 'text' : 'password'} value={form.oauth_client_secret} onChange={e => set('oauth_client_secret', e.target.value)}
                placeholder="OAuth Client Secret" className="ts-input" style={{ paddingRight: 40 }} />
              <button type="button" onClick={() => setShowPw(v => !v)}
                style={{ position: 'absolute', right: 10, top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', color: 'var(--text4)', cursor: 'pointer' }}>
                {showPw ? <EyeOff size={15} strokeWidth={1.75} /> : <Eye size={15} strokeWidth={1.75} />}
              </button>
            </div>
          </div>
        </>
      ) : (
        <>
          {(provider === 'zoho' || provider === 'yahoo') && (
            <div style={{ background: 'var(--surface2)', border: '1px solid var(--border)', borderRadius: 8, padding: 12, fontSize: 12.5, color: 'var(--text2)' }}>
              Use an <strong>App Password</strong>, not your main account password. Generate one in your {provider === 'zoho' ? 'Zoho' : 'Yahoo'} account security settings.
            </div>
          )}
          <div>
            <label className="ts-label">Email Address</label>
            <input type="email" value={form.email} onChange={e => set('email', e.target.value)} placeholder="you@example.com" className="ts-input" style={{ marginTop: 6 }} />
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 100px', gap: 10 }}>
            <div>
              <label className="ts-label">SMTP Host</label>
              <input value={form.smtp_host} onChange={e => set('smtp_host', e.target.value)} placeholder="smtp.example.com" className="ts-input" style={{ marginTop: 6 }} />
            </div>
            <div>
              <label className="ts-label">Port</label>
              <input type="number" value={form.smtp_port} onChange={e => set('smtp_port', Number(e.target.value))} className="ts-input" style={{ marginTop: 6 }} />
            </div>
          </div>
          <div>
            <label className="ts-label">Encryption</label>
            <select value={form.smtp_encryption} onChange={e => set('smtp_encryption', e.target.value)} className="ts-select" style={{ marginTop: 6, width: '100%' }}>
              <option value="tls">TLS (recommended)</option>
              <option value="ssl">SSL</option>
              <option value="none">None</option>
            </select>
          </div>
          <div>
            <label className="ts-label">Username / Email</label>
            <input value={form.smtp_username} onChange={e => set('smtp_username', e.target.value)} placeholder="your@email.com" className="ts-input" style={{ marginTop: 6 }} />
          </div>
          <div>
            <label className="ts-label">App Password</label>
            <div style={{ position: 'relative', marginTop: 6 }}>
              <input type={showPw ? 'text' : 'password'} value={form.smtp_password} onChange={e => set('smtp_password', e.target.value)}
                placeholder="App password" className="ts-input" style={{ paddingRight: 40 }} />
              <button type="button" onClick={() => setShowPw(v => !v)}
                style={{ position: 'absolute', right: 10, top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', color: 'var(--text4)', cursor: 'pointer' }}>
                {showPw ? <EyeOff size={15} strokeWidth={1.75} /> : <Eye size={15} strokeWidth={1.75} />}
              </button>
            </div>
          </div>
        </>
      )}

      <div style={{ display: 'flex', gap: 8, paddingTop: 4 }}>
        <Button loading={connect.isPending} onClick={() => connect.mutate()}>
          {isOAuth ? 'Continue to OAuth →' : 'Connect Account'}
        </Button>
        <Button variant="secondary" onClick={onClose}>Cancel</Button>
      </div>
    </div>
  )
}
