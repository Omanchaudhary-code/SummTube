import { useEffect, useState } from "react";
import { HiEye, HiEyeOff } from "react-icons/hi";
import logo from "../assets/Logo.png";

const SignupModal = ({ onClose, onSwitchToLogin }) => {
  const [showPassword, setShowPassword] = useState(false);

  useEffect(() => {
    document.body.style.overflow = "hidden";
    return () => (document.body.style.overflow = "auto");
  }, []);

  return (
    <div
      onClick={onClose}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
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
                  Register with your email
                  </p>
        <h2 className="text-2xl font-light mb-1">Create Account</h2>

        <input
          type="text"
          placeholder="Full Name"
          className="w-full mb-3 px-3 py-2 border rounded focus:ring-1 focus:ring-black outline-none"
        />

        <input
          type="email"
          placeholder="Email"
          className="w-full mb-3 px-3 py-2 border rounded focus:ring-1 focus:ring-black outline-none"
        />

        {/* Password */}
        <div className="relative mb-4">
          <input
            type={showPassword ? "text" : "password"}
            placeholder="Password"
            className="w-full px-3 py-2 border rounded focus:ring-1 focus:ring-black outline-none pr-10"
          />
          <button
            onClick={() => setShowPassword(!showPassword)}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500"
          >
            {showPassword ? <HiEyeOff size={20} /> : <HiEye size={20} />}
          </button>
        </div>

        <button className="w-full py-2 rounded bg-black text-white hover:bg-gray-800 transition">
          Sign Up
        </button>

        <div className="border-t mt-6 pt-4 text-sm">
          <span className="mr-2 text-gray-500">
            Already have an account?
          </span>

          <button
            onClick={onSwitchToLogin}
            className="font-medium hover:text-gray-600"
          >
            Login
          </button>
        </div>
      </div>
    </div>
  );
};

export default SignupModal;
