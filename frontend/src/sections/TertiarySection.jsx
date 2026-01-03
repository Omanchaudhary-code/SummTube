import LogoLoop from '../components/custom/LogoLoop.jsx'
import { SiReact, SiNextdotjs, SiTypescript, SiTailwindcss } from 'react-icons/si'
import { 
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
    <div className="techStackWrapper w-screen bg-[var(--bg-main)]">

    <div className="heading text-center py-8">
    <h2
    className="text-[2.5rem]"
    >Used TechStack </h2>
    </div>
    <div className="relative h-[200px] overflow-hidden">
      <LogoLoop
        logos={techLogos}
        speed={70}
        direction="left"
        logoHeight={48}
        gap={40}
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
