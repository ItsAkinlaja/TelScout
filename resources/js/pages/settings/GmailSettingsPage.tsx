/**
 * GmailSettingsPage — redirects to the unified MailSettingsPage.
 * Kept for backward-compat with any old links.
 */
import { Navigate } from 'react-router-dom'

export default function GmailSettingsPage() {
  return <Navigate to="/settings/mail" replace />
}
