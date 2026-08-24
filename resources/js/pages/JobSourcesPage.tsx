import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Database, Plus, X, Play, Trash2, ChevronDown, ChevronUp } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../lib/api'
import LoadingSpinner from '../components/ui/LoadingSpinner'
import EmptyState from '../components/ui/EmptyState'
import { formatDate } from '../lib/utils'
import { TsPageStyles } from '../components/ui/TsShared'

// ── Types ─────────────────────────────────────────────────────────────────────

type AtsType = 'greenhouse' | 'lever' | 'ashby' | 'workable' | 'generic'

interface FormState {
  company_id: string
  source_type: AtsType
  source_url: string
  ats_type: AtsType | ''
  // ATS-specific meta fields
  board_token: string      // Greenhouse
  company_slug: string     // Lever / Ashby
  organization_id: string  // Ashby alternative
}

const EMPTY_FORM: FormState = {
  company_id:      '',
  source_type:     'greenhouse',
  source_url:      '',
  ats_type:        'greenhouse',
  board_token:     '',
  company_slug:    '',
  organization_id: '',
}

// ── ATS config — labels, placeholder hints, help text ────────────────────────

const ATS_CONFIG: Record<AtsType, {
  label: string
  color: string
  urlPlaceholder: string
  urlHelp: string
  metaFields: { key: keyof FormState; label: string; placeholder: string; help: string }[]
}> = {
  greenhouse: {
    label: 'Greenhouse',
    color: '#24a148',
    urlPlaceholder: 'https://boards-api.greenhouse.io/v1/boards/stripe/jobs',
    urlHelp: 'The Greenhouse board API URL. Replace "stripe" with the company\'s board token.',
    metaFields: [
      {
        key: 'board_token',
        label: 'Board Token',
        placeholder: 'stripe',
        help: 'The slug after /boards/ in your Greenhouse job board URL, e.g. "stripe" from boards.greenhouse.io/stripe',
      },
    ],
  },
  lever: {
    label: 'Lever',
    color: '#0066cc',
    urlPlaceholder: 'https://api.lever.co/v0/postings/acme-corp?mode=json',
    urlHelp: 'The Lever postings API URL. Replace "acme-corp" with the company\'s Lever slug.',
    metaFields: [
      {
        key: 'company_slug',
        label: 'Company Slug',
        placeholder: 'acme-corp',
        help: 'The slug from jobs.lever.co/{slug} — usually the company name in kebab-case.',
      },
    ],
  },
  ashby: {
    label: 'Ashby',
    color: '#7c3aed',
    urlPlaceholder: 'https://api.ashbyhq.com/posting-api/job-board/linear',
    urlHelp: 'The Ashby job board API URL. Replace "linear" with the company\'s organization ID.',
    metaFields: [
      {
        key: 'organization_id',
        label: 'Organization ID / Slug',
        placeholder: 'linear',
        help: 'Found in the URL at jobs.ashbyhq.com/{slug}',
      },
    ],
  },
  workable: {
    label: 'Workable',
    color: '#f97316',
    urlPlaceholder: 'https://apply.workable.com/api/v3/accounts/company-name/jobs',
    urlHelp: 'The Workable jobs API URL for this company.',
    metaFields: [
      {
        key: 'company_slug',
        label: 'Company Slug',
        placeholder: 'company-name',
        help: 'The company identifier used in their Workable URL.',
      },
    ],
  },
  generic: {
    label: 'Generic / Other',
    color: 'var(--text3)',
    urlPlaceholder: 'https://company.com/careers',
    urlHelp: 'The careers page or jobs feed URL for this company.',
    metaFields: [],
  },
}

// ── Status pill ───────────────────────────────────────────────────────────────

function StatusPill({ active }: { active: boolean }) {
  return (
    <span className={`ts-pill ${active ? 'ts-pill-green' : ''}`} style={{ fontSize: 11 }}>
      {active ? 'Active' : 'Inactive'}
    </span>
  )
}

// ── ATS badge ─────────────────────────────────────────────────────────────────

function AtsBadge({ type }: { type: string | null }) {
  const cfg = ATS_CONFIG[type as AtsType]
  if (!cfg) return <span className="ts-td-dim">{type ?? '—'}</span>
  return (
    <span style={{
      fontSize: 11, fontWeight: 600, padding: '2px 7px', borderRadius: 4,
      background: cfg.color + '18', color: cfg.color,
      letterSpacing: '0.02em', textTransform: 'uppercase',
    }}>
      {cfg.label}
    </span>
  )
}

// ── Add source form ───────────────────────────────────────────────────────────

function AddSourceForm({
  onSubmit,
  onCancel,
  loading,
}: {
  onSubmit: (data: any) => void
  onCancel: () => void
  loading: boolean
}) {
  const [form, setForm] = useState<FormState>(EMPTY_FORM)
  const set = (k: keyof FormState, v: string) => setForm(f => ({ ...f, [k]: v }))

  // Fetch companies for the picker (up to 200 — enough for any single user)
  const { data: companiesData } = useQuery({
    queryKey: ['companies-picker'],
    queryFn: () => api.get('/companies', { params: { per_page: 200 } }).then(r => r.data),
    staleTime: 60_000,
  })
  const companies: any[] = companiesData?.data ?? []

  const cfg = ATS_CONFIG[form.source_type]

  // Auto-populate source_url when board_token / company_slug changes
  function autoFillUrl(ats: AtsType, token: string) {
    if (!token) return
    const urls: Record<AtsType, string> = {
      greenhouse: `https://boards-api.greenhouse.io/v1/boards/${token}/jobs`,
      lever:      `https://api.lever.co/v0/postings/${token}?mode=json`,
      ashby:      `https://api.ashbyhq.com/posting-api/job-board/${token}`,
      workable:   `https://apply.workable.com/api/v3/accounts/${token}/jobs`,
      generic:    '',
    }
    setForm(f => ({ ...f, source_url: urls[ats] || f.source_url }))
  }

  function handleAtsChange(val: AtsType) {
    setForm(f => ({
      ...EMPTY_FORM,
      company_id: f.company_id,
      source_type: val,
      ats_type:    val,
    }))
  }

  function handleMetaChange(key: keyof FormState, val: string) {
    set(key, val)
    // Auto-fill URL from the primary meta field (first one)
    if (cfg.metaFields[0]?.key === key) {
      autoFillUrl(form.source_type, val)
    }
  }

  function buildPayload() {
    const meta: Record<string, string> = {}
    if (form.board_token)     meta['board_token']     = form.board_token
    if (form.company_slug)    meta['company_slug']    = form.company_slug
    if (form.organization_id) meta['organization_id'] = form.organization_id

    return {
      company_id:  parseInt(form.company_id),
      source_type: form.source_type,
      source_url:  form.source_url,
      ats_type:    form.ats_type || form.source_type,
      meta:        Object.keys(meta).length ? meta : undefined,
    }
  }

  const canSubmit = form.company_id && form.source_url && form.source_type

  return (
    <div style={{
      background: 'var(--surface)', border: '1px solid var(--border2)',
      borderRadius: 10, padding: 20, marginBottom: 20,
    }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 18 }}>
        <p style={{ fontSize: 14, fontWeight: 600, color: 'var(--text)', margin: 0 }}>
          Register a Job Source
        </p>
        <button onClick={onCancel} style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text3)', padding: 4 }}>
          <X size={16} strokeWidth={1.75} />
        </button>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>

        {/* Company picker */}
        <div className="ts-field" style={{ margin: 0, gridColumn: '1 / -1' }}>
          <label className="ts-label">Company *</label>
          <select
            className="ts-input"
            value={form.company_id}
            onChange={e => set('company_id', e.target.value)}
          >
            <option value="">— Select a company —</option>
            {companies.map((c: any) => (
              <option key={c.id} value={c.id}>{c.name}</option>
            ))}
          </select>
          {companies.length === 0 && (
            <p style={{ fontSize: 12, color: 'var(--text4)', marginTop: 4 }}>
              No companies yet — run a job search first to populate companies.
            </p>
          )}
        </div>

        {/* ATS type */}
        <div className="ts-field" style={{ margin: 0 }}>
          <label className="ts-label">ATS / Source Type *</label>
          <select
            className="ts-input"
            value={form.source_type}
            onChange={e => handleAtsChange(e.target.value as AtsType)}
          >
            {(Object.keys(ATS_CONFIG) as AtsType[]).map(k => (
              <option key={k} value={k}>{ATS_CONFIG[k].label}</option>
            ))}
          </select>
        </div>

        {/* ATS-specific meta fields */}
        {cfg.metaFields.map(field => (
          <div key={field.key} className="ts-field" style={{ margin: 0 }}>
            <label className="ts-label">{field.label} *</label>
            <input
              type="text"
              className="ts-input"
              placeholder={field.placeholder}
              value={form[field.key] as string}
              onChange={e => handleMetaChange(field.key, e.target.value)}
            />
            <p style={{ fontSize: 11.5, color: 'var(--text4)', marginTop: 4 }}>{field.help}</p>
          </div>
        ))}

        {/* Source URL — auto-filled but editable */}
        <div className="ts-field" style={{ margin: 0, gridColumn: '1 / -1' }}>
          <label className="ts-label">Feed URL *</label>
          <input
            type="url"
            className="ts-input"
            placeholder={cfg.urlPlaceholder}
            value={form.source_url}
            onChange={e => set('source_url', e.target.value)}
          />
          <p style={{ fontSize: 11.5, color: 'var(--text4)', marginTop: 4 }}>{cfg.urlHelp}</p>
        </div>

      </div>

      <div style={{ display: 'flex', gap: 10, marginTop: 18 }}>
        <button
          className="ts-btn-primary"
          onClick={() => canSubmit && onSubmit(buildPayload())}
          disabled={loading || !canSubmit}
        >
          {loading ? 'Saving…' : 'Register Source'}
        </button>
        <button className="ts-btn-secondary" onClick={onCancel}>Cancel</button>
      </div>
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function JobSourcesPage() {
  const [showForm, setShowForm] = useState(false)
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['job-sources'],
    queryFn: () => api.get('/job-sources').then(r => r.data),
  })

  const sources: any[] = Array.isArray(data) ? data : (data?.data ?? [])

  const addSource = useMutation({
    mutationFn: (payload: any) => api.post('/job-sources', payload),
    onSuccess: () => {
      toast.success('Job source registered')
      setShowForm(false)
      qc.invalidateQueries({ queryKey: ['job-sources'] })
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed to register source'),
  })

  const triggerFetch = useMutation({
    mutationFn: (id: number) => api.post(`/job-sources/${id}/trigger`),
    onSuccess: () => toast.success('Fetch queued'),
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed to queue fetch'),
  })

  const deleteSource = useMutation({
    mutationFn: (id: number) => api.delete(`/job-sources/${id}`),
    onSuccess: () => {
      toast.success('Source removed')
      qc.invalidateQueries({ queryKey: ['job-sources'] })
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed to remove source'),
  })

  return (
    <>
      <div className="ts-page">

        {/* Header */}
        <div className="ts-filters" style={{ marginBottom: 16 }}>
          <h1 style={{ fontSize: 18, fontWeight: 700, color: 'var(--text)', letterSpacing: '-0.02em', margin: 0 }}>
            Job Sources
          </h1>
          <button
            className="ts-btn-primary"
            onClick={() => setShowForm(v => !v)}
            style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '8px 12px', fontSize: 13 }}
          >
            {showForm
              ? <><X size={14} strokeWidth={2} /> Cancel</>
              : <><Plus size={14} strokeWidth={2} /> Register Source</>}
          </button>
          <span className="ts-count">{sources.length} source{sources.length !== 1 ? 's' : ''}</span>
        </div>

        {/* Add form */}
        {showForm && (
          <AddSourceForm
            onSubmit={d => addSource.mutate(d)}
            onCancel={() => setShowForm(false)}
            loading={addSource.isPending}
          />
        )}

        {/* Table */}
        {isLoading ? (
          <LoadingSpinner />
        ) : sources.length === 0 ? (
          <EmptyState
            icon={Database}
            title="No job sources registered yet"
            description="Register a Greenhouse, Lever, or Ashby board to start ingesting jobs automatically."
          />
        ) : (
          <div className="ts-table-wrap">
            <table className="ts-table">
              <thead>
                <tr>
                  {['Company', 'ATS', 'Feed URL', 'Status', 'Last Fetched', 'Next Fetch', 'Fails', 'Actions'].map(h => (
                    <th key={h} className="ts-th">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {sources.map((s: any) => (
                  <tr key={s.id} className="ts-tr">
                    <td className="ts-td" style={{ fontWeight: 500 }}>
                      {s.company?.name ?? '—'}
                    </td>
                    <td className="ts-td">
                      <AtsBadge type={s.ats_type ?? s.source_type} />
                    </td>
                    <td className="ts-td ts-td-muted" style={{ maxWidth: 220 }}>
                      <span style={{ fontSize: 12, wordBreak: 'break-all' }}>
                        {s.source_url ?? '—'}
                      </span>
                    </td>
                    <td className="ts-td">
                      <StatusPill active={!!(s.active)} />
                    </td>
                    <td className="ts-td ts-td-muted" style={{ whiteSpace: 'nowrap' }}>
                      {s.last_fetched_at ? formatDate(s.last_fetched_at) : '—'}
                    </td>
                    <td className="ts-td ts-td-muted" style={{ whiteSpace: 'nowrap' }}>
                      {s.next_fetch_at ? formatDate(s.next_fetch_at) : '—'}
                    </td>
                    <td className="ts-td ts-td-muted">
                      {s.failure_count ?? 0}
                      {(s.failure_count ?? 0) >= 3 && (
                        <span style={{ marginLeft: 4, fontSize: 11, color: '#ef4444' }}>⚠</span>
                      )}
                    </td>
                    <td className="ts-td">
                      <div style={{ display: 'flex', gap: 6 }}>
                        <button
                          title="Trigger fetch now"
                          onClick={() => triggerFetch.mutate(s.id)}
                          disabled={triggerFetch.isPending}
                          style={{
                            background: 'none', border: '1px solid var(--border2)',
                            borderRadius: 5, padding: '4px 8px', cursor: 'pointer',
                            color: 'var(--accent)', display: 'flex', alignItems: 'center', gap: 4,
                            fontSize: 12,
                          }}
                        >
                          <Play size={11} strokeWidth={2} /> Fetch
                        </button>
                        <button
                          title="Remove source"
                          onClick={() => {
                            if (confirm(`Remove ${s.company?.name ?? 'this'} source?`)) {
                              deleteSource.mutate(s.id)
                            }
                          }}
                          style={{
                            background: 'none', border: '1px solid var(--border2)',
                            borderRadius: 5, padding: '4px 8px', cursor: 'pointer',
                            color: '#ef4444', display: 'flex', alignItems: 'center',
                          }}
                        >
                          <Trash2 size={11} strokeWidth={2} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* How it works */}
        <div style={{
          marginTop: 28, padding: '16px 20px',
          background: 'var(--surface)', border: '1px solid var(--border2)',
          borderRadius: 10, fontSize: 13, color: 'var(--text2)', lineHeight: 1.7,
        }}>
          <p style={{ fontWeight: 600, color: 'var(--text)', marginBottom: 8 }}>How it works</p>
          <ul style={{ margin: 0, paddingLeft: 18 }}>
            <li><strong>Greenhouse</strong> — enter the board token (e.g. <code>stripe</code> from boards.greenhouse.io/stripe). No API key needed.</li>
            <li><strong>Lever</strong> — enter the company slug from jobs.lever.co/{'{slug}'}. No API key needed.</li>
            <li><strong>Ashby</strong> — enter the organization ID from jobs.ashbyhq.com/{'{slug}'}. No API key needed.</li>
            <li>Each source is fetched every 6 hours automatically. Use <strong>Fetch</strong> to trigger a manual pull now.</li>
            <li>A source is automatically disabled after 5 consecutive failures.</li>
          </ul>
        </div>

      </div>
      <TsPageStyles />
    </>
  )
}
