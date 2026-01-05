import { Link } from "react-router-dom";
import logo from "../assets/Logo.png";
import { Mail, Github, Copyright } from "lucide-react";

const Footer = () => {
  return (
    <footer className="bg-slate-50 border-t border-slate-200">
      <div className="max-w-6xl mx-auto px-4 py-10">

        {/* TOP SECTION */}
        <div className="flex flex-col gap-10 lg:flex-row lg:justify-between">

          {/* LEFT */}
          <div className="flex flex-col gap-4 text-center lg:text-left max-w-md">
            <Link
              to="/"
              className="flex items-center justify-center lg:justify-start gap-2"
            >
              <span className="border border-slate-300 p-2 rounded-md inline-flex">
                <img src={logo} alt="SummTube Logo" className="h-6 w-auto" />
              </span>
              <h2 className="text-base font-semibold text-[var(--text-primary)]">
                SummTube
              </h2>
            </Link>

            <p className="text-sm text-[var(--text-secondary)]">
              An intelligent platform that extracts, analyzes, and summarizes
              YouTube video transcripts.
            </p>
          </div>

          {/* RIGHT */}
          <div className="flex flex-col gap-3 text-center lg:text-left">
            <h4 className="font-semibold text-sm">Connect</h4>

            <div>
              <p className="text-sm font-medium">Email</p>
              <p className="text-sm text-[var(--text-secondary)]">
                summtube@gmail.com
              </p>
            </div>

            <div>
              <p className="text-sm font-medium">University</p>
              <p className="text-sm text-[var(--text-secondary)]">
                Kathmandu University
              </p>
            </div>

            <div className="flex gap-2 pt-2 justify-center lg:justify-start">
              <span className="border border-slate-300 p-2 rounded-md inline-flex">
                <Mail size={16} />
              </span>
              <span className="border border-slate-300 p-2 rounded-md inline-flex">
                <Github size={16} />
              </span>
            </div>
          </div>
        </div>

        {/* DIVIDER */}
        <div className="my-6 h-px bg-slate-200" />

        {/* BOTTOM */}
        <div className="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center text-center sm:text-left">
          <p className="flex items-center justify-center sm:justify-start gap-2 text-sm text-[var(--text-secondary)]">
            <Copyright size={14} />
            <span>2026 SummTube. All rights reserved.</span>
          </p>

          <p className="text-sm text-[var(--text-secondary)]">
            Designed and Developed by Team-8
          </p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
