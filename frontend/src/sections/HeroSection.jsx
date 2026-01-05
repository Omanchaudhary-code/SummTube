// import SplashCursor from '../components/custom/SplashCursor.jsx'
// import Antigravity from '../components/custom/Antigravity.jsx';
import { useNavigate } from "react-router-dom";

const HeroSection = () => {
  const navigate = useNavigate();

  return (
    <div className="wrapper min-h-screen w-full bg-[var(--bg-main)] overflow-x-hidden flex flex-col">
      
      {/* <SplashCursor /> */}
      {/* <div style={{ width: '100%', height: '100%', position: 'relative' }}>
        <Antigravity
          count={300}
          magnetRadius={6}
          ringRadius={7}
          waveSpeed={0.4}
          waveAmplitude={1}
          particleSize={1.5}
          lerpSpeed={0.05}
          color={'#FF9FFC'}
          autoAnimate={true}
          particleVariance={1}
        />
      </div> */}

      {/* Upper Section */}
      <div className="upperSection flex-1 flex flex-col justify-center items-center
                      gap-8 sm:gap-10 md:gap-12
                      px-4 sm:px-8 lg:px-16">

        {/* Title */}
        <div className="title text-center">
          <h2 className="font-bold leading-tight
                         text-3xl
                         sm:text-4xl
                         md:text-5xl
                         lg:text-[5rem]">
            Watch Less,{" "}
            <span
              className="bg-[linear-gradient(90deg,#34d399,#60a5fa,#a78bfa,#34d399)]
                         bg-[length:300%_300%]
                         bg-clip-text
                         text-transparent
                         animate-gradient">
              Learn
            </span>{" "}
            More
          </h2>
        </div>

        {/* Description */}
        <div className="description w-full sm:w-4/5 lg:w-[70%] text-center">
          <p className="text-base sm:text-lg md:text-xl lg:text-2xl
                        leading-7 sm:leading-8 md:leading-9
                        tracking-wide
                        text-[var(--text-secondary)]">
            An intelligent platform that extracts, analyzes, and summarizes
            YouTube video transcripts from your link.
          </p>
        </div>

        {/* CTA Button */}
        <div className="button">
          <button
            onClick={() => navigate("/try")}
            className="bg-black text-white rounded-2xl
                       text-base sm:text-lg md:text-xl
                       py-3 sm:py-4 px-8 sm:px-12
                       mt-2
                       hover:scale-105 transition-transform
                       active:scale-95">
            Try SummTube
          </button>
        </div>

        {/* Lower Section */}
        <div className="lowerSection text-center pt-6 sm:pt-8">
          <h2 className="font-extralight tracking-wider
                         text-base sm:text-xl md:text-2xl lg:text-3xl">
            Your AI-Powered Video Summarizer
          </h2>
        </div>

      </div>
    </div>
  );
};

export default HeroSection;
