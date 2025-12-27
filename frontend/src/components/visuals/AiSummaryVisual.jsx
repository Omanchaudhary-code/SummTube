const AiSummaryVisual = () => {
  return (
    <div className="visual-card">
      <svg viewBox="0 0 480 280">
        <g
          style={{
            fill: "var(--visual-line)",
            animation: "collapse 6s infinite",
          }}
        >
          <rect x="80" y="60" width="320" height="6" />
          <rect x="80" y="80" width="300" height="6" />
          <rect x="80" y="100" width="280" height="6" />
          <rect x="80" y="120" width="320" height="6" />
        </g>

        <g
          style={{
            opacity: 0,
            animation: "appear 6s infinite",
          }}
        >
          <rect
            x="120"
            y="170"
            width="240"
            height="8"
            fill="var(--accent-ai)"
          />
          <rect
            x="120"
            y="195"
            width="200"
            height="8"
            fill="var(--accent-ai)"
          />
        </g>
      </svg>
    </div>
  );
};

export default AiSummaryVisual;
