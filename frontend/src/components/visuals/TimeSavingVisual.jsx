import React from 'react'

const TimeSavingVisual = () => {
  return (
   <div class="visual-card">
  <svg viewBox="0 0 480 280">
    <rect x="60" y="130" height="6"
      fill="var(--accent-time)"
      style="animation:compress 5s infinite;" />

    <rect x="360" y="115" width="60" height="30" rx="6"
      fill="var(--visual-strong)" />
  </svg>
</div>

  )
}

export default TimeSavingVisual
