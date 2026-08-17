export default function LoadingSpinner({ className }: { className?: string }) {
  return (
    <div className={className} style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '48px 0' }}>
      <div style={{
        width: 28, height: 28,
        border: '2px solid var(--border2)',
        borderTopColor: 'var(--accent)',
        borderRadius: '50%',
        animation: 'ts-spin 0.7s linear infinite',
      }} />
      <style>{`@keyframes ts-spin { to { transform: rotate(360deg); } }`}</style>
    </div>
  )
}
