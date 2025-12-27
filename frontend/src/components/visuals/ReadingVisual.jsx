const ReadingVisual = () => {
  return (
    <div className="visual-card">
      <svg viewBox="0 0 480 280">
        <rect
          x={100}
          y={40}
          width={280}
          height={200}
          rx={8}
          fill="#fff"
          stroke="var(--visual-border)"
        />

        <rect
          x={120}
          y={70}
          width={240}
          height={6}
          fill="var(--visual-line)"
        />

        <rect
          x={120}
          y={100}
          width={240}
          height={12}
          fill="rgba(16,185,129,0.25)"
          style={{ animation: "focus 5s infinite" }}
        />

        <rect
          x={120}
          y={140}
          width={200}
          height={6}
          fill="var(--visual-line)"
        />
      </svg>
    </div>
  );
};

export default ReadingVisual;
