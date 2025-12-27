import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { HiEye, HiEyeOff } from "react-icons/hi";
import googleicon from "../assets/googleicon.png";
import logo from "../assets/Logo.png";

const LoginModal = ({ onClose, onSwitchToSignup }) => {
  const [showPassword, setShowPassword] = useState(false);

  useEffect(() => {
    document.body.style.overflow = "hidden";
    return () => (document.body.style.overflow = "auto");
  }, []);

  return (
    <div
      onClick={onClose}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm text-[var(--font-sans)]"
    >
      <div
        onClick={(e) => e.stopPropagation()}
        className="w-[450px] rounded-xl bg-white p-6 shadow-xl animate-scaleIn text-center"
      >
        <div className="flex items-center justify-center">
          <img 
        src={logo} 
        alt="Summtube-logo"
        className="w-1/4"
        />
        </div>

        <h2 className="text-2xl font-medium mb-1">Welcome to SummTube</h2>
        <p
        className="text-[var(--text-tertiary)] mb-10"
        >
          Login or register with your email
          </p>

        {/* Email */}
        <input
          type="email"
          placeholder="Email"
          className="w-full mb-3 px-3 py-2 border rounded focus:ring-1 focus:ring-black outline-none"
        />

        {/* Password with eye icon */}
        <div className="relative mb-4">
          <input
            type={showPassword ? "text" : "password"}
            placeholder="Password"
            className="w-full px-3 py-2 border rounded focus:ring-1 focus:ring-black outline-none pr-10"
          />
          <button
            type="button"
            onClick={() => setShowPassword(!showPassword)}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500"
          >
            {showPassword ? <HiEyeOff size={20} /> : <HiEye size={20} />}
          </button>
        </div>

        {/* Buttons */}
        <div className="flex items-center justify-between">
          <button className="py-2 px-6 rounded bg-black text-white hover:scale-102 cursor-pointer transition">
            Login
          </button>

          <button className="bg-white border py-1 px-3 rounded flex items-center gap-2 hover:scale-102 cursor-pointer transition">
            <img className="w-[30px]" src={googleicon} alt="Google" />
            Log in with Google
          </button>
        </div>

        {/* Bottom section */}
        <div className="border-t mt-8 pt-4 text-sm">
          <span className="mr-2 text-[var(--text-tertiary)]">
            New to SummTube?
          </span>

          <button
            onClick={onSwitchToSignup}
            className="text-black hover:text-gray-500 font-medium"
          >
            Create an account
          </button>
        </div>
      </div>
    </div>
  );
};

export default LoginModal;
