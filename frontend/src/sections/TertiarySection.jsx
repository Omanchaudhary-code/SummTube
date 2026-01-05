import LogoLoop from '../components/custom/LogoLoop.jsx'
import {
  SiReact,
  SiTailwindcss,
  SiPhp,
  SiPython,
  SiFastapi
} from 'react-icons/si'

const TertiarySection = () => {
  const techLogos = [
    { node: <SiReact />, title: "React", href: "https://react.dev" },
    { node: <SiTailwindcss />, title: "Tailwind CSS", href: "https://tailwindcss.com" },
    { node: <SiPhp />, title: "PHP", href: "https://www.php.net" },
    { node: <SiPython />, title: "Python", href: "https://www.python.org" },
    { node: <SiFastapi />, title: "FastAPI", href: "https://fastapi.tiangolo.com" },
  ]

  return (
    <div className="techStackWrapper w-full bg-[var(--bg-main)]">

      {/* Heading */}
      <div className="heading text-center
                      py-5 sm:py-6 md:py-8 lg:py-8">
        <h2
          className="
            font-semibold
            text-xl sm:text-2xl md:text-[2.5rem] lg:text-[2.5rem]
          "
        >
          Used Tech Stack
        </h2>
      </div>

      {/* Logo Loop */}
      <div
        className="
          relative overflow-hidden
          h-[120px] sm:h-[150px] md:h-[180px] lg:h-[200px]
          xl:h-[220px]
        "
      >
        <LogoLoop
          logos={techLogos}
          speed={70}
          direction="left"
          logoHeight={48}   // unchanged for laptop
          gap={40}          // unchanged for laptop
          hoverSpeed={0}
          scaleOnHover
          fadeOut
          fadeOutColor="#ffffff"
          ariaLabel="Tech Stack Used"
        />
      </div>

    </div>
  )
}

export default TertiarySection;
