const YoutubeLinkVisual = () => {
  return (
    <div className="visual-card">
      <svg viewBox="0 0 480 280">
        <rect
          x="20"
          y="110"
          width="200"
          height="36"
          rx="8"
          fill="#fff"
          stroke="var(--accent-link)"
        />

        <text x="32" y="133" fontSize="14" fill="var(--visual-text)">
          youtube.com/...
        </text>

        <rect
          x="155"
          y="118"
          width="2"
          height="20"
          fill="var(--accent-link)"
          style={{ animation: "blink 1s steps(1) infinite" }}
        />

        <line
          x1="240"
          y1="128"
          x2="300"
          y2="128"
          stroke="var(--accent-link)"
          strokeWidth="2"
          markerEnd="url(#arrow)"
        />

        <g>
          <rect
            x="320"
            y="95"
            width="120"
            height="6"
            fill="var(--visual-line)"
            style={{ opacity: 0, animation: "fadeIn 6s infinite 1s" }}
          />
          <rect
            x="320"
            y="115"
            width="140"
            height="6"
            fill="var(--visual-line)"
            style={{ opacity: 0, animation: "fadeIn 6s infinite 1.4s" }}
          />
          <rect
            x="320"
            y="135"
            width="110"
            height="6"
            fill="var(--visual-line)"
            style={{ opacity: 0, animation: "fadeIn 6s infinite 1.8s" }}
          />
        </g>

        <defs>
          <marker
            id="arrow"
            markerWidth="6"
            markerHeight="6"
            refX="5"
            refY="3"
            orient="auto"
          >
            <path d="M0,0 L6,3 L0,6" fill="var(--accent-link)" />
          </marker>
        </defs>
      </svg>
    </div>
  );
};

export default YoutubeLinkVisual;
