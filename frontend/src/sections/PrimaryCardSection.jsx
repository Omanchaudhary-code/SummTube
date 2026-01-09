import { motion } from "framer-motion";
import { Link, Brain, Clock } from "lucide-react";

const cardVariants = {
  hidden: { opacity: 0, y: 30 },
  visible: (i) => ({
    opacity: 1,
    y: 0,
    transition: {
      delay: i * 0.1,
      duration: 0.5,
      ease: [0.22, 1, 0.36, 1], // Smooth custom easing
    },
  }),
};

// --- Animation Components (Abstract UI) ---

const FloatingGlow = () => (
  <div className="absolute inset-0 overflow-hidden pointer-events-none">
    <motion.div
      animate={{
        x: [-20, 20, -20],
        y: [-20, 20, -20],
        scale: [1, 1.1, 1],
      }}
      transition={{
        duration: 8,
        repeat: Infinity,
        ease: "easeInOut",
      }}
      className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[150%] h-[150%] opacity-[0.15]"
    >
      <div className="absolute top-1/4 left-1/4 w-[40%] h-[40%] bg-blue-400 rounded-full blur-[80px]" />
      <div className="absolute bottom-1/4 right-1/4 w-[40%] h-[40%] bg-purple-400 rounded-full blur-[80px]" />
      <div className="absolute top-1/3 right-1/4 w-[30%] h-[30%] bg-teal-400 rounded-full blur-[80px]" />
    </motion.div>
  </div>
);

const SourcesAnimation = () => (
  <div className="relative w-full h-full flex items-center justify-center p-6">
    <motion.div
      initial={{ scale: 0.95, opacity: 0 }}
      animate={{ scale: 1, opacity: 1 }}
      className="relative w-full max-w-[280px] h-[180px] bg-white rounded-2xl border border-[var(--border-default)] flex items-center justify-center overflow-hidden shadow-sm"
    >
      <div className="absolute inset-x-3 top-1/2 -translate-y-1/2 h-8 bg-[var(--bg-main)] rounded-full border border-[var(--border-subtle)] px-3 flex items-center gap-2">
        <div className="w-1.5 h-1.5 rounded-full bg-blue-500" />
        <div className="h-1.5 w-24 bg-gray-200 rounded-full" />
      </div>

      {[...Array(5)].map((_, i) => (
        <motion.div
          key={i}
          initial={{ y: 80, opacity: 0, x: (i - 2) * 45 }}
          animate={{
            y: [-80, 80],
            opacity: [0, 1, 1, 0]
          }}
          transition={{
            duration: 3.5,
            repeat: Infinity,
            delay: i * 0.8,
            ease: "linear"
          }}
          className="absolute"
        >
          <div className="p-2.5 rounded-xl bg-white border border-[var(--border-subtle)] shadow-md">
            {i % 2 === 0 ? (
              <div className="w-5 h-5 rounded-full bg-[#FF0000]/10 flex items-center justify-center">
                <div className="w-0 h-0 border-l-[4px] border-l-[#FF0000] border-y-[3px] border-y-transparent ml-0.5" />
              </div>
            ) : (
              <Link className="w-5 h-5 text-blue-500" />
            )}
          </div>
        </motion.div>
      ))}
    </motion.div>
    <div className="absolute inset-0 bg-gradient-to-t from-blue-500/5 to-transparent pointer-events-none" />
  </div>
);

const SummaryAnimation = () => (
  <div className="relative w-full h-full flex items-center justify-center p-6">
    <motion.div
      className="w-full max-w-[300px] bg-white rounded-2xl border border-[var(--border-default)] p-5 flex flex-col gap-3 shadow-lg"
      initial={{ opacity: 0, y: 10 }}
      whileInView={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.8 }}
    >
      <div className="flex items-center gap-2.5 border-b border-[var(--border-subtle)] pb-3">
        <div className="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
          <Brain className="w-5 h-5 text-blue-600" />
        </div>
        <div className="h-3 w-24 bg-gray-100 rounded-full" />
      </div>

      <div className="flex flex-col gap-2">
        {[...Array(3)].map((_, i) => (
          <motion.div
            key={i}
            initial={{ scaleX: 0 }}
            whileInView={{ scaleX: 1 }}
            transition={{ duration: 0.8, delay: 0.2 + i * 0.2 }}
            style={{ originX: 0 }}
            className={`h-1.5 rounded-full ${i === 2 ? 'w-[70%] bg-gray-50' : 'w-full bg-gray-100'}`}
          />
        ))}
      </div>

      <div className="grid grid-cols-4 gap-2 mt-1">
        {[...Array(4)].map((_, i) => (
          <motion.div
            key={i}
            animate={{ opacity: [0.4, 1, 0.4] }}
            transition={{ duration: 2, delay: i * 0.3, repeat: Infinity }}
            className="h-6 rounded-md bg-gray-50 border border-[var(--border-subtle)]"
          />
        ))}
      </div>
    </motion.div>

    <motion.div
      animate={{
        y: [0, -8, 0],
        rotate: [0, 5, 0]
      }}
      transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
      className="absolute top-[20%] right-[15%] p-2 rounded-lg bg-white shadow-xl border border-[var(--border-subtle)]"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" className="w-5 h-5 text-purple-500">
        <path d="M12 2l2.4 7.2L22 12l-7.6 2.4L12 22l-2.4-7.2L2 12l7.6-2.4z" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    </motion.div>
  </div>
);

const LearningAnimation = () => (
  <div className="relative w-full h-full flex items-center justify-center overflow-hidden p-6">
    <div className="flex flex-wrap gap-2.5 max-w-[280px] justify-center">
      {['Key Insights', 'Quick Summary', 'Actionable', 'Timestamps', 'Flashcards'].map((text, i) => (
        <motion.div
          key={text}
          animate={{
            y: [0, (i % 2 === 0 ? -12 : 12), 0],
          }}
          transition={{
            duration: 4 + i,
            repeat: Infinity,
            ease: "easeInOut"
          }}
          className={`
            px-4 py-1.5 rounded-full border shadow-sm text-sm font-medium
            ${i % 2 === 0
              ? 'bg-blue-50 border-blue-100 text-blue-600'
              : 'bg-purple-50 border-purple-100 text-purple-600'}
          `}
        >
          {text}
        </motion.div>
      ))}
    </div>

    <motion.div
      className="absolute inset-0 pointer-events-none opacity-40"
      animate={{
        background: [
          'radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%)',
          'radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%)',
          'radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%)'
        ]
      }}
      transition={{ duration: 6, repeat: Infinity }}
    />
  </div>
);

const TimeAnimation = () => (
  <div className="relative w-full h-full flex items-center justify-center p-6">
    <div className="w-full max-w-[280px] flex flex-col gap-6">
      {/* Before */}
      <div className="flex flex-col gap-1.5">
        <div className="flex justify-between text-[10px] text-gray-400 uppercase tracking-widest px-0.5">
          <span>60 Min Video</span>
          <Clock className="w-3 h-3" />
        </div>
        <div className="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden border border-gray-200 relative">
          <motion.div
            animate={{ left: ['0%', '100%'] }}
            transition={{ duration: 8, repeat: Infinity, ease: "linear" }}
            className="absolute top-0 bottom-0 w-0.5 bg-red-400"
          />
        </div>
      </div>

      <motion.div
        animate={{ y: [0, 4, 0], opacity: [0.4, 0.8, 0.4] }}
        transition={{ duration: 2, repeat: Infinity }}
        className="self-center"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" className="w-5 h-5 text-gray-300">
          <path d="M12 5v14M19 12l-7 7-7-7" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      </motion.div>

      {/* After */}
      <div className="flex flex-col gap-1.5">
        <div className="flex justify-between text-[10px] text-blue-600 font-bold uppercase tracking-widest px-0.5">
          <span>2 Min Read</span>
          <span className="text-[11px]">-96% Time</span>
        </div>
        <div className="h-5 w-full bg-gray-50 rounded-lg overflow-hidden border border-blue-100 relative p-1">
          <motion.div
            initial={{ width: 0 }}
            whileInView={{ width: '100%' }}
            transition={{ duration: 1.2, ease: "easeOut" }}
            className="h-full bg-blue-500 rounded-md shadow-sm"
          />
        </div>
      </div>
    </div>
  </div>
);

const PrimaryCardSection = ({ cardsData }) => {
  return (
    <section className="w-full bg-[var(--bg-main)] flex flex-col items-center gap-12 sm:gap-20 py-16 sm:py-20 px-6 sm:px-12">

      {/* Title animation */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
        transition={{ duration: 0.5 }}
        className="text-center mb-6 sm:mb-10"
      >
        <h2 className="text-3xl sm:text-4xl font-semibold tracking-tight text-[var(--text-primary)]">
          Why choose SummTube?
        </h2>
      </motion.div>

      <div className="w-full max-w-[1100px] flex flex-col gap-16 sm:gap-24">
        {cardsData.map((card, index) => {
          const Icon = card.icon;
          const isEven = index % 2 === 0;

          return (
            <motion.div
              key={index}
              custom={index}
              variants={cardVariants}
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.2 }}
              className={`
                w-full flex flex-col md:flex-row
                items-center justify-between
                gap-10 md:gap-16
                ${isEven ? 'md:flex-row' : 'md:flex-row-reverse'}
              `}
            >
              {/* Text Side */}
              <div className="w-full md:w-[42%] flex flex-col gap-5">
                <div className="p-2.5 w-fit rounded-xl bg-white border border-[var(--border-default)] shadow-sm">
                  <Icon className="w-5 h-5 text-blue-600" />
                </div>

                <div className="flex flex-col gap-3">
                  <h3 className="text-2xl sm:text-2xl font-semibold tracking-tight text-[var(--text-primary)]">
                    {card.title}
                  </h3>
                  <p className="text-base sm:text-[1.05rem] text-[var(--text-secondary)] font-normal leading-relaxed">
                    {card.desc}
                  </p>
                </div>
              </div>

              {/* Visual Side (Abstract UI) */}
              <div
                className="
                  w-full md:w-[48%]
                  aspect-[4/3] sm:aspect-video
                  max-h-[300px] sm:max-h-[380px]
                  flex justify-center items-center
                  relative overflow-hidden
                  rounded-[2rem] border border-[var(--border-default)]
                  bg-white
                  shadow-md
                  group
                "
              >
                <FloatingGlow />
                {card.animationType === "sources" && <SourcesAnimation />}
                {card.animationType === "summary" && <SummaryAnimation />}
                {card.animationType === "learning" && <LearningAnimation />}
                {card.animationType === "time" && <TimeAnimation />}

                {/* Subtle sheen overlay */}
                <div className="absolute inset-0 pointer-events-none bg-gradient-to-tr from-white/20 via-transparent to-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-700" />
              </div>
            </motion.div>
          );
        })}
      </div>
    </section>
  );
};

export default PrimaryCardSection;
