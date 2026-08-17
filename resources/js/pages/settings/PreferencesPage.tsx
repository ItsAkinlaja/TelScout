import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import api from '../../lib/api'
import Button from '../../components/ui/Button'
import LoadingSpinner from '../../components/ui/LoadingSpinner'
import { TsPageStyles } from '../../components/ui/TsShared'

export default function PreferencesPage() {
  const qc = useQueryClient()
  const { data: settings, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => api.get('/settings').then(r => r.data),
  })
  const [form, setForm] = useState<any>({})

  useEffect(() => {
    if (!settings) return
    setForm({
      ...settings,
      search_keywords:  (settings.search_keywords  ?? []).join(', '),
      search_locations: (settings.search_locations ?? []).join(', '),
    })
  }, [settings])

  const save = useMutation({
    mutationFn: () => api.put('/settings', {
      ...form,
      search_keywords:  csv(form.search_keywords),
      search_locations: csv(form.search_locations),
    }),
    onSuccess: () => { toast.success('Preferences saved'); qc.invalidateQueries({ queryKey: ['settings'] }) },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Save failed'),
  })

  if (isLoading) return <LoadingSpinner />
  const set = (k: string, v: any) => setForm((f: any) => ({ ...f, [k]: v }))

  return (
    <>
      <div style={{ maxWidth: 560, display: 'flex', flexDirection: 'column', gap: 14 }}>
        <div style={{ background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 10, padding: 20, display: 'flex', flexDirection: 'column', gap: 16 }}>
          <p style={{ fontSize: 14, fontWeight: 600, color: 'var(--text)', letterSpacing: '-0.01em' }}>Discovery Preferences</p>

          <div className="ts-field" style={{ margin: 0 }}>
            <label className="ts-label">Search Keywords (comma separated)</label>
            <input value={form.search_keywords ?? ''} onChange={e => set('search_keywords', e.target.value)}
              placeholder="react developer, laravel, full stack" className="ts-input" style={{ marginTop: 6 }} />
          </div>

          <div className="ts-field" style={{ margin: 0 }}>
            <label className="ts-label">Search Locations (comma separated)</label>
            <input value={form.search_locations ?? ''} onChange={e => set('search_locations', e.target.value)}
              placeholder="Lagos Nigeria, Remote, Worldwide" className="ts-input" style={{ marginTop: 6 }} />
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 14 }}>
            <div>
              <label className="ts-label">Minimum Match Score (%)</label>
              <input type="number" min={0} max={100} value={form.minimum_match_score ?? 70}
                onChange={e => set('minimum_match_score', Number(e.target.value))}
                className="ts-input" style={{ marginTop: 6 }} />
            </div>
            <div>
              <label className="ts-label">Minimum Salary</label>
              <input type="number" min={0} value={form.minimum_salary ?? ''}
                onChange={e => set('minimum_salary', e.target.value || null)}
                placeholder="Optional" className="ts-input" style={{ marginTop: 6 }} />
            </div>
          </div>

          <label style={{ display: 'flex', alignItems: 'center', gap: 12, cursor: 'pointer' }}>
            <div style={{ position: 'relative', flexShrink: 0 }}>
              <div onClick={() => set('remote_only', !form.remote_only)} style={{
                width: 36, height: 20, borderRadius: 100, cursor: 'pointer',
                background: form.remote_only ? 'var(--accent)' : 'var(--surface3)',
                border: '1px solid var(--border2)',
                position: 'relative', transition: 'background .2s',
              }}>
                <div style={{
                  position: 'absolute', top: 2, left: form.remote_only ? 17 : 2,
                  width: 14, height: 14, borderRadius: '50%', background: '#fff',
                  boxShadow: '0 1px 3px rgba(0,0,0,0.2)', transition: 'left .15s',
                }} />
              </div>
            </div>
            <div>
              <p style={{ fontSize: 13.5, fontWeight: 500, color: 'var(--text)' }}>Remote Only</p>
              <p style={{ fontSize: 12, color: 'var(--text4)', marginTop: 2 }}>Only discover remote job opportunities</p>
            </div>
          </label>
        </div>

        <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
          <Button loading={save.isPending} onClick={() => save.mutate()}>Save Preferences</Button>
        </div>
      </div>
      <TsPageStyles />
    </>
  )
}

function csv(str: string): string[] {
  return (str ?? '').split(',').map((s: string) => s.trim()).filter(Boolean)
}
