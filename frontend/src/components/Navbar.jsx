import React, { useEffect, useState } from "react";
import LogoSlogan from "./LogoSlogan";
import Menu from "./Menu";
import LoginModal from "../modal/LoginModal";
import SignupModal from "../modal/SignupModal";
import { HiMenu, HiX } from "react-icons/hi";

const Navbar = () => {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isLoginOpen, setIsLoginOpen] = useState(false);
  const [isSignupOpen, setIsSignupOpen] = useState(false);

  /* Lock scroll when mobile menu open */
  useEffect(() => {
    document.body.style.overflow = isMobileMenuOpen ? "hidden" : "auto";
  }, [isMobileMenuOpen]);

  return (
    <>
      <header className="sticky top-0 z-50 bg-[var(--bg-main)] ">
        <nav className="mx-auto max-w-[1300px] h-[64px] md:h-[72px] px-4 sm:px-6 lg:px-8 flex items-center justify-between">
          <LogoSlogan />

          {/* Desktop Menu */}
          <div className="hidden md:flex items-center">
            <Menu
              onLoginClick={() => setIsLoginOpen(true)}
              onSignupClick={() => setIsSignupOpen(true)}
              isMobile={false}
            />
          </div>

          {/* Mobile Toggle */}
          <button
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            className="md:hidden text-3xl"
            aria-label="Toggle menu"
          >
            {isMobileMenuOpen ? <HiX /> : <HiMenu />}
          </button>
        </nav>

        {/* Mobile Menu */}
        {isMobileMenuOpen && (
          <div className="md:hidden absolute top-[64px] left-0 w-full bg-[var(--bg-main)] border-t shadow-lg animate-slideDown">
            <Menu
              isMobile
              onLoginClick={() => {
                setIsLoginOpen(true);
                setIsMobileMenuOpen(false);
              }}
              onSignupClick={() => {
                setIsSignupOpen(true);
                setIsMobileMenuOpen(false);
              }}
            />
          </div>
        )}
      </header>

      {/* Login Modal */}
      {isLoginOpen && (
        <LoginModal
          onClose={() => setIsLoginOpen(false)}
          onSwitchToSignup={() => {
            setIsLoginOpen(false);
            setIsSignupOpen(true);
          }}
        />
      )}

      {/* Signup Modal */}
      {isSignupOpen && (
        <SignupModal
          onClose={() => setIsSignupOpen(false)}
          onSwitchToLogin={() => {
            setIsSignupOpen(false);
            setIsLoginOpen(true);
          }}
        />
      )}
    </>
  );
};

export default Navbar;
