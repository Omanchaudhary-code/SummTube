// import SplashCursor from '../components/custom/SplashCursor.jsx'
// import Antigravity from '../components/custom/Antigravity.jsx';


const HeroSection = () => {
  return (
    <div className="wrapper h-screen w-screen bg-[var(--bg-main)] text-[var(--font-sans)] overflow-hidden flex flex-col justify-between">
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
      <div className="upperSection flex-1 flex flex-col gap-13 justify-center items-center font-sans px-4 sm:px-8 lg:px-16">
        <div className="title text-center">
          <h2 className="text-3xl sm:text-4xl md:text-5xl lg:text-[5rem] font-bold">
            Watch Less,{" "}
            <span
              className="bg-[linear-gradient(90deg,#34d399,#60a5fa,#a78bfa,#34d399)]
              bg-[length:300%_300%]
              bg-clip-text
              text-transparent
              animate-gradient"
            >
              Learn
            </span>{" "}
            More
          </h2>
        </div>

        <div className="description w-full sm:w-4/5 lg:w-[75%] text-center">
          <p className="text-lg sm:text-md md:text-2xl leading-10 md:leading-8 tracking-wide text-[var(--text-secondary)]">
          An intelligent platform that extracts, analyzes, and summarizes YouTube video transcripts from your link.
          </p>
        </div>

        <div className="button">
          <button className="text-lg sm:text-xl bg-black rounded-2xl text-white py-3 sm:py-5 px-6 sm:px-12 mt-4 hover:cursor-pointer hover:scale-102">
            Try SummTube
          </button>
        </div>
        <div className="lowerSection text-center py-4 sm:py-6 my-15">
        <h2 className="text-xl sm:text-3xl md:text-4xl font-extralight tracking-wider">
          Your AI-Powered Video Summarizer
        </h2>
      </div>
    </div>
      </div>

     
  );
};

export default HeroSection;
