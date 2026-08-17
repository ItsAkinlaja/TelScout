import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatDate(date: string | null | undefined): string {
  if (!date) return '—'
  return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(date))
}

export function formatCurrency(amount: number | null | undefined, currency = 'USD'): string {
  if (amount == null) return '—'
  return new Intl.NumberFormat('en-US', { style: 'currency', currency, maximumFractionDigits: 0 }).format(amount)
}

export function formatScore(score: number): string {
  return `${Math.round(score)}%`
}

export const SCORE_COLORS: Record<string, string> = {
  excellent: 'text-emerald-600 bg-emerald-50',
  strong:    'text-blue-600 bg-blue-50',
  good:      'text-sky-600 bg-sky-50',
  possible:  'text-amber-600 bg-amber-50',
  low:       'text-gray-500 bg-gray-50',
}

export const STATUS_COLORS: Record<string, string> = {
  discovered:  'bg-gray-100 text-gray-700',
  shortlisted: 'bg-blue-100 text-blue-700',
  contacted:   'bg-indigo-100 text-indigo-700',
  follow_up:   'bg-yellow-100 text-yellow-700',
  replied:     'bg-purple-100 text-purple-700',
  interview:   'bg-orange-100 text-orange-700',
  offer:       'bg-emerald-100 text-emerald-700',
  rejected:    'bg-red-100 text-red-700',
  closed:      'bg-gray-100 text-gray-500',
  draft:       'bg-gray-100 text-gray-700',
  approved:    'bg-blue-100 text-blue-700',
  queued:      'bg-indigo-100 text-indigo-700',
  sent:        'bg-emerald-100 text-emerald-700',
  failed:      'bg-red-100 text-red-700',
  pending:     'bg-yellow-100 text-yellow-700',
  completed:   'bg-emerald-100 text-emerald-700',
  cancelled:   'bg-gray-100 text-gray-500',
}

export function statusLabel(status: string): string {
  return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}
