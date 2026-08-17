import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import api from '../../lib/api'
import Button from '../../components/ui/Button'
import LoadingSpinner from '../../components/ui/LoadingSpinner'
import { TsPageStyles } from '../../components/ui/TsShared'

export default function AutomationSettingsPage() {
  const qc = useQueryClient()
  const { data: settings, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => api.get('/settings').then(r => r.data),
  })
  const [form, setForm] = useState<any>({})

  useEffect(() => { if (settings) setForm(settings) }, [settings])

  const save = useMutation({
    mutationFn: () => api.put('/settings', form),
    onSuccess: () => { toast.success('Settings saved'); qc.invalidateQueries({ queryKey: ['settings'] }) },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Save failed'),
  })

  if (isLoading) return <LoadingSpinner />
  const set = (k: string, v: any) => setForm((f: any) => ({ ...f, [k]: v }))

  return (
    <>
      <div style={{ maxWidth: 560, display: 'flex', flexDirection: 'column', gap: 14 }}>

        <Card title="Outreach Settings">
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 14 }}>
            <NumField label="Daily Send Limit"    value={form.daily_send_limit}    onChange={v => set('daily_send_limit', v)}    min={1}  max={100} />
            <NumField label="Hourly Send Limit"   value={form.hourly_send_limit}   onChange={v => set('hourly_send_limit', v)}   min={1}  max={50} />
            <NumField label="Min Delay (seconds)" value={form.min_delay_seconds}   onChange={v => set('min_delay_seconds', v)}   min={5} />
            <NumField label="Max Delay (seconds)" value={form.max_delay_seconds}   onChange={v => set('max_delay_seconds', v)}   min={5} />
            <StrField label="Work Hours Start"    value={form.working_hours_start} onChange={v => set('working_hours_start', v)} placeholder="08:00" />
            <StrField label="Work Hours End"      value={form.working_hours_end}   onChange={v => set('working_hours_end', v)}   placeholder="18:00" />
            <div style={{ gridColumn: '1/-1' }}>
              <label className="ts-label">Timezone</label>
              <input value={form.timezone ?? ''} onChange={e => set('timezone', e.target.value)}
                placeholder="Africa/Lagos" className="ts-input" style={{ marginTop: 6 }} />
            </div>
          </div>
          <div style={{ borderTop: '1px solid var(--border)', paddingTop: 16, marginTop: 16, display: 'flex', flexDirection: 'column', gap: 12 }}>
            <Toggle label="Require Approval" desc="Emails must be manually approved before queuing." checked={form.require_approval} onChange={v => set('require_approval', v)} />
            <Toggle label="Auto-send"        desc="Send approved emails automatically without manual trigger." checked={form.auto_send}        onChange={v => set('auto_send', v)} />
            <Toggle label="Daily Discovery"  desc="Run automated job discovery via scheduler each day." checked={form.discovery_enabled} onChange={v => set('discovery_enabled', v)} />
          </div>
        </Card>

        <Card title="Follow-up Settings">
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 14 }}>
            <NumField label="Follow-up Interval (days)" value={form.follow_up_interval_days} onChange={v => set('follow_up_interval_days', v)} min={1} max={30} />
            <NumField label="Max Follow-ups"            value={form.max_follow_ups}           onChange={v => set('max_follow_ups', v)}           min={0} max={10} />
          </div>
        </Card>

        <Card title="AI Settings">
          <p style={{ fontSize: 13, color: 'var(--text3)', marginBottom: 16 }}>
            Used for email generation. Get a key at{' '}
            <a href="https://platform.openai.com" target="_blank" rel="noopener noreferrer" style={{ color: 'var(--accent)', textDecoration: 'none' }}>platform.openai.com</a>.
          </p>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2,1fr)', gap: 14 }}>
            <div>
              <label className="ts-label">Provider</label>
              <select value={form.ai_provider ?? 'openai'} onChange={e => set('ai_provider', e.target.value)}
                className="ts-select" style={{ marginTop: 6, width: '100%' }}>
                <option value="openai">OpenAI</option>
              </select>
            </div>
            <StrField label="Model" value={form.ai_model ?? 'gpt-4o-mini'} onChange={v => set('ai_model', v)} placeholder="gpt-4o-mini" />
            <div style={{ gridColumn: '1/-1' }}>
              <label className="ts-label">
                API Key{' '}
                {form.has_ai_key && <span style={{ color: '#4ade80', fontSize: 11.5 }}>(configured — leave blank to keep)</span>}
              </label>
              <input type="password" value={form.ai_api_key ?? ''}
                onChange={e => set('ai_api_key', e.target.value)}
                placeholder={form.has_ai_key ? '••••••••••••••• (configured)' : 'sk-...your-key'}
                className="ts-input" style={{ marginTop: 6 }} />
            </div>
            <NumField label="Temperature" value={form.ai_temperature ?? 0.7} onChange={v => set('ai_temperature', v)} min={0} max={2} />
            <NumField label="Max Tokens"  value={form.ai_max_tokens  ?? 1000} onChange={v => set('ai_max_tokens', v)}  min={100} max={4000} />
          </div>
        </Card>

        <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
          <Button loading={save.isPending} onClick={() => save.mutate()}>Save Settings</Button>
        </div>
      </div>
      <TsPageStyles />
    </>
  )
}

function Card({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div style={{ background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 10, padding: 20 }}>
      <p style={{ fontSize: 14, fontWeight: 600, color: 'var(--text)', marginBottom: 16, letterSpacing: '-0.01em' }}>{title}</p>
      {children}
    </div>
  )
}

function NumField({ label, value, onChange, min, max }: any) {
  return (
    <div>
      <label className="ts-label">{label}</label>
      <input type="number" value={value ?? ''} min={min} max={max}
        onChange={e => onChange(Number(e.target.value))}
        className="ts-input" style={{ marginTop: 6 }} />
    </div>
  )
}

function StrField({ label, value, onChange, placeholder }: any) {
  return (
    <div>
      <label className="ts-label">{label}</label>
      <input type="text" value={value ?? ''} placeholder={placeholder}
        onChange={e => onChange(e.target.value)}
        className="ts-input" style={{ marginTop: 6 }} />
    </div>
  )
}

function Toggle({ label, desc, checked, onChange }: any) {
  return (
    <label style={{ display: 'flex', alignItems: 'flex-start', gap: 12, cursor: 'pointer' }}>
      <div style={{ position: 'relative', flexShrink: 0, marginTop: 2 }}>
        <input type="checkbox" checked={checked ?? false} onChange={e => onChange(e.target.checked)} style={{ position: 'absolute', opacity: 0, width: 0, height: 0 }} />
        <div onClick={() => onChange(!checked)} style={{
          width: 36, height: 20, borderRadius: 100, cursor: 'pointer',
          background: checked ? 'var(--accent)' : 'var(--surface3)',
          border: '1px solid var(--border2)',
          position: 'relative', transition: 'background .2s',
        }}>
          <div style={{
            position: 'absolute', top: 2, left: checked ? 17 : 2,
            width: 14, height: 14, borderRadius: '50%', background: '#fff',
            boxShadow: '0 1px 3px rgba(0,0,0,0.2)',
            transition: 'left .15s',
          }} />
        </div>
      </div>
      <div>
        <p style={{ fontSize: 13.5, fontWeight: 500, color: 'var(--text)' }}>{label}</p>
        {desc && <p style={{ fontSize: 12, color: 'var(--text4)', marginTop: 2 }}>{desc}</p>}
      </div>
    </label>
  )
}
