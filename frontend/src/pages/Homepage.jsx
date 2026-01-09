import Navbar from '../components/Navbar.jsx'
import HeroSection from '../sections/HeroSection.jsx'
import PrimaryCardSection from '../sections/PrimaryCardSection.jsx'
import TertiarySection from '../sections/TertiarySection.jsx';
import Footer from '../components/Footer.jsx';


import {
  Link,
  Brain,
  BookOpen,
  Clock
} from "lucide-react";


const Homepage = () => {

  const cardsData = [
    {
      icon: Link,
      title: "Paste Any YouTube Link",
      desc: "Paste a YouTube video URL and SummTube automatically retrieves the available transcript. It supports long lectures, tutorials, podcasts, and educational videos, allowing you to access the spoken content without watching the entire video.",
      animationType: "sources"
    },
    {
      icon: Brain,
      title: "AI-Powered Smart Summaries",
      desc: "SummTube processes the extracted transcript using advanced AI models to generate accurate and well-structured summaries. The system identifies key ideas, explanations, and conclusions, presenting them in a clear format that is easy to read, understand, and review later.",
      animationType: "summary"
    },
    {
      icon: BookOpen,
      title: "Learn at Your Own Pace",
      desc: "Instead of navigating video timelines, SummTube lets you consume content as text. You can skim summaries, focus on important sections, revisit concepts, or read at your own speed. This approach is especially useful for studying, note-taking, and revision.",
      animationType: "learning"
    },
    {
      icon: Clock,
      title: "Save Time While Preserving Meaning",
      desc: "SummTube helps students, educators, and professionals reduce the time spent watching videos while retaining essential context and meaning. By converting long videos into concise summaries, it enables faster learning without sacrificing understanding or depth.",
      animationType: "time"
    },
  ];


  return (
    <div className='wrapper min-h-screen w-full'>
      <Navbar />
      <HeroSection />
      <PrimaryCardSection cardsData={cardsData} />
      <TertiarySection />
      <Footer />

    </div>
  )
}

export default Homepage
