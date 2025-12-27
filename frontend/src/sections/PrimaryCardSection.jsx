const PrimaryCardSection = ({ cardsData }) => {
  return (
    <section className="w-screen bg-[var(--bg-main)] flex flex-col items-center gap-12 sm:gap-16 py-10 px-4 sm:px-8">
      {cardsData.map((card, index) => {
        const Icon = card.icon;

        return (
          <div
            key={index}
            className="
              w-full max-w-[1200px]
              flex flex-col md:flex-row
              items-center justify-between
              gap-8 md:gap-10
              p-4 sm:p-6
            "
          >
            {/* Left Text Section */}
            <div
              className="
                w-full md:w-[35%]
                flex flex-col gap-4 sm:gap-5
                p-2 sm:p-3
                order-2 md:order-1
              "
            >
              <div className="top-section">
                <Icon className="w-5 h-5 text-gray-600" />
                <p className="text-lg sm:text-xl md:text-[1.4rem] font-thin mt-3 sm:mt-4">
                  {card.title}
                </p>
              </div>

              <p className="text-sm sm:text-base text-[var(--text-tertiary)] tracking-wide font-thin leading-6">
                {card.desc}
              </p>
            </div>

            {/* Right Visual / Video Section */}
            <div
              className="
                w-full md:w-[60%]
                min-h-[180px] sm:min-h-[220px] md:min-h-[260px]
                flex justify-center items-center
                border rounded-lg
                text-sm sm:text-base
                p-4 text-center
                order-1 md:order-2
              "
            >
              visual / video / svg goes here
            </div>
          </div>
        );
      })}
    </section>
  );
};

export default PrimaryCardSection;
