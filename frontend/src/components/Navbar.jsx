import React, { useState } from "react";
import LogoSlogan from "./LogoSlogan.jsx";
import Menu from "./Menu.jsx";
import LoginModal from "../modal/LoginModal.jsx";
import SignupModal from "../modal/SignupModal.jsx";
import { HiMenu, HiX } from "react-icons/hi";

const Navbar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [isLoginOpen, setIsLoginOpen] = useState(false);
  const [isSignupOpen, setIsSignupOpen] = useState(false);

  return (
    <>
      <header className="h-[10vh] w-screen flex items-center justify-center bg-[var(--bg-main)] sticky top-0 z-50 px-4 sm:px-8">
        <nav className="h-full w-full max-w-[1300px] flex items-center justify-between">
          <LogoSlogan />

          {/* Desktop Menu */}
          <div className="hidden sm:flex">
            <Menu
              onLoginClick={() => setIsLoginOpen(true)}
              onSignupClick={() => setIsSignupOpen(true)}
            />
          </div>

          {/* Mobile Menu Button */}
          <div className="sm:hidden">
            <button onClick={() => setIsOpen(!isOpen)} className="text-2xl">
              {isOpen ? <HiX /> : <HiMenu />}
            </button>
          </div>
        </nav>

        {/* Mobile Menu */}
        {isOpen && (
          <div className="sm:hidden absolute top-[10vh] left-0 w-full bg-[var(--bg-main)] shadow-md z-40">
            <Menu
              onLoginClick={() => {
                setIsLoginOpen(true);
                setIsOpen(false);
              }}
              onSignupClick={() => {
                setIsSignupOpen(true);
                setIsOpen(false);
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
