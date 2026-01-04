import { motion } from "framer-motion";

const cardVariants = {
  hidden: { opacity: 0, y: 50 },
  visible: (i) => ({
    opacity: 1,
    y: 0,
    transition: {
      delay: i * 0.15,
      duration: 0.6,
      ease: "easeOut",
    },
  }),
};

const PrimaryCardSection = ({ cardsData }) => {
  return (
    <section className="w-full bg-[var(--bg-main)] flex flex-col items-center gap-12 sm:gap-16 px-4 sm:px-8">
      
      {/* Title animation */}
      <motion.div
        initial={{ opacity: 0, y: 40 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
        transition={{ duration: 0.6 }}
        className="text-center"
      >
        <h1 className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl">
          Why choose SummTube?
        </h1>
      </motion.div>

      {cardsData.map((card, index) => {
        const Icon = card.icon;

        return (
          <motion.div
            key={index}
            custom={index}
            variants={cardVariants}
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, amount: 0.3 }}
            className="
              w-full max-w-[1200px]
              flex flex-col md:flex-row
              items-center justify-between
              gap-10 md:gap-10
              p-4 sm:p-6
            "
          >
            {/* Left Text */}
            <div
              className="
                w-full md:w-[35%]
                flex flex-col gap-4 sm:gap-5
                p-2 sm:p-3
                order-2 md:order-1
              "
            >
              <Icon className="w-5 h-5 text-gray-600" />

              <p className="text-lg sm:text-xl md:text-[1.4rem] font-thin">
                {card.title}
              </p>

              <p className="text-sm sm:text-base text-[var(--text-tertiary)] tracking-wide font-thin leading-6">
                {card.desc}
              </p>
            </div>

            {/* Right Visual */}
            <div
              className="
                w-full md:w-[60%]
                min-h-[180px] sm:min-h-[220px] md:min-h-[260px]
                flex justify-center items-center
                border rounded-lg
                p-4 text-center
                order-1 md:order-2
              "
            >
              visual / video / svg goes here
            </div>
          </motion.div>
        );
      })}
    </section>
  );
};

export default PrimaryCardSection;
