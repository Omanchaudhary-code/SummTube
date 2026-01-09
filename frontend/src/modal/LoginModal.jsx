import { useEffect, useState, useRef } from "react";
import { HiEye, HiEyeOff } from "react-icons/hi";
import { useNavigate } from "react-router-dom";
import api from "../services/api";
import logo from "../assets/logo.png";

const LoginModal = ({ onClose, onSwitchToSignup }) => {
  const navigate = useNavigate();
  const googleButtonRef = useRef(null);
  const isInitializing = useRef(false);

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [googleReady, setGoogleReady] = useState(false);
  const [googleError, setGoogleError] = useState(null);

  /* ---------------- Lock background scroll ---------------- */
  useEffect(() => {
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = "auto";
    };
  }, []);

  /* ---------------- Load Google Identity SDK ---------------- */
  useEffect(() => {
    // Check if script already exists
    const existingScript = document.querySelector(
      'script[src="https://accounts.google.com/gsi/client"]'
    );

    if (existingScript) {
      // Script already loaded, just initialize
      if (window.google && !isInitializing.current) {
        initGoogle();
      }
      return;
    }

    // Load the script with language parameter
    const script = document.createElement("script");
    script.src = "https://accounts.google.com/gsi/client?hl=en";
    script.async = true;
    script.defer = true;
    script.onload = () => {
      if (!isInitializing.current) {
        initGoogle();
      }
    };
    script.onerror = () => {
      setGoogleError("Failed to load Google Sign-In. Please refresh the page.");
    };

    document.body.appendChild(script);

    return () => {
      // Cleanup is handled by React
    };
  }, []);

  /* ---------------- Render Google Button When Ready ---------------- */
  useEffect(() => {
    if (googleReady && googleButtonRef.current && window.google) {
      try {
        // Clear any existing button
        googleButtonRef.current.innerHTML = "";

        window.google.accounts.id.renderButton(googleButtonRef.current, {
          theme: "outline",
          size: "large",
          text: "continue_with",
          width: googleButtonRef.current.offsetWidth || 400,
          logo_alignment: "left",
          locale: "en",
        });
      } catch (error) {
        setGoogleError("Failed to render Google button",error);
      }
    }
  }, [googleReady]);

  /* ---------------- Init Google ---------------- */
  const initGoogle = async () => {
    if (isInitializing.current) {
      return; // Prevent double initialization
    }

    isInitializing.current = true;

    try {
      // Fetch config from backend
      const res = await api.get("/auth/google/config");

      if (!res.data.success) {
        throw new Error("Failed to get Google configuration");
      }

      if (!res.data.client_id) {
        throw new Error("Google Client ID not configured");
      }

      // Check if Google SDK is loaded
      if (!window.google?.accounts?.id) {
        throw new Error("Google SDK not loaded");
      }

      const googleClientId = res.data.client_id;

      // Initialize Google Sign-In
      window.google.accounts.id.initialize({
        client_id: googleClientId,
        callback: handleGoogleResponse,
        auto_select: false,
        cancel_on_tap_outside: true,
        itp_support: true,
        locale: "en",
      });

      setGoogleReady(true);
      setGoogleError(null);
    } catch (err) {
      setGoogleError(
        err.response?.data?.message ||
        err.message ||
        "Failed to initialize Google Sign-In"
      );
    } finally {
      isInitializing.current = false;
    }
  };

  /* ---------------- Google Login Callback ---------------- */
  const handleGoogleResponse = async (response) => {
    if (!response.credential) {
      alert("Google sign-in failed. No credential received.");
      return;
    }

    setIsLoading(true);

    try {
      const result = await api.post("/auth/google", 
        {
        token: response.credential,
        },
        { withCredentials: true }
    );

      if (result.data.success && result.data.user) {
        // Show success message
        alert(`Welcome, ${result.data.user.name}!`);

        // Close modal
        onClose();

        // Navigate to home or dashboard
        navigate("/dashboard");
      } else {
        throw new Error("Invalid response from server");
      }
    } catch (err) {
      const errorMessage =
        err.response?.data?.message ||
        err.response?.data?.error ||
        err.message ||
        "Google login failed. Please try again.";

      alert(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  /* ---------------- Local Login ---------------- */
  const handleLocalLogin = async (e) => {
    e.preventDefault();

    if (!email || !password) {
      alert("Please fill in all fields");
      return;
    }

    setIsLoading(true);

    try {
      const result = await api.post("/auth/login", {
        email: email.trim(),
        password,
      });

      if (result.data.success && result.data.user) {
        alert(`Welcome back, ${result.data.user.name}!`);
        onClose();
        navigate("/dashboard");
      } else {
        throw new Error("Invalid response from server");
      }
    } catch (err) {
      const errorMessage =
        err.response?.data?.message ||
        err.response?.data?.error ||
        err.message ||
        "Login failed. Please check your credentials.";

      alert(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  /* ---------------- UI ---------------- */
  return (
    <div
      onClick={onClose}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      aria-labelledby="login-modal-title"
    >
      <div
        onClick={(e) => e.stopPropagation()}
        className="w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto rounded-xl bg-white p-6 shadow-xl"
      >
        {/* Logo */}
        <div className="flex justify-center mb-4">
          <img
            src={logo}
            alt="SummTube"
            className="w-20 h-auto"
            onError={(e) => {
              e.target.style.display = "none";
            }}
          />
        </div>

        {/* Header */}
        <h2
          id="login-modal-title"
          className="text-2xl font-semibold mb-1 text-gray-800 text-center"
        >
          Welcome to SummTube
        </h2>
        <p className="text-gray-500 mb-6 text-center">
          Login or continue with Google
        </p>

        {/* ---------- Email Login Form ---------- */}
        <form onSubmit={handleLocalLogin} className="space-y-4">
          <div>
            <input
              type="email"
              placeholder="Email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              disabled={isLoading}
              className="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent text-gray-800 disabled:bg-gray-100 disabled:cursor-not-allowed transition"
              aria-label="Email"
            />
          </div>

          <div className="relative">
            <input
              type={showPassword ? "text" : "password"}
              placeholder="Password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              disabled={isLoading}
              className="w-full px-4 py-2.5 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent text-gray-800 disabled:bg-gray-100 disabled:cursor-not-allowed transition"
              aria-label="Password"
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              disabled={isLoading}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed"
              aria-label={showPassword ? "Hide password" : "Show password"}
            >
              {showPassword ? <HiEyeOff size={20} /> : <HiEye size={20} />}
            </button>
          </div>

          <button
            type="submit"
            disabled={isLoading}
            className="w-full bg-black text-white py-2.5 rounded-lg font-medium disabled:opacity-60 disabled:cursor-not-allowed hover:bg-gray-800 transition"
          >
            {isLoading ? "Logging in..." : "Login"}
          </button>
        </form>

        {/* ---------- Divider ---------- */}
        <div className="flex items-center my-6">
          <div className="flex-1 border-t border-gray-300" />
          <span className="px-4 text-sm text-gray-500">or</span>
          <div className="flex-1 border-t border-gray-300" />
        </div>

        {/* ---------- Google Sign-In ---------- */}
        <div className="w-full">
          {googleError ? (
            <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm text-center">
              {googleError}
              <button
                onClick={() => {
                  setGoogleError(null);
                  isInitializing.current = false;
                  initGoogle();
                }}
                className="block w-full mt-2 text-red-700 underline hover:text-red-800"
              >
                Retry
              </button>
            </div>
          ) : googleReady ? (
            <div ref={googleButtonRef} className="w-full flex justify-center" />
          ) : (
            <button
              disabled
              className="w-full flex items-center justify-center gap-3 border border-gray-300 py-2.5 rounded-lg bg-gray-50 cursor-not-allowed opacity-60"
            >
              <svg
                className="w-5 h-5 animate-spin text-gray-500"
                viewBox="0 0 24 24"
              >
                <circle
                  className="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  strokeWidth="4"
                  fill="none"
                />
                <path
                  className="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
              </svg>
              <span className="text-gray-600">Loading Google Sign-In...</span>
            </button>
          )}
        </div>

        {/* ---------- Footer ---------- */}
        <div className="border-t border-gray-200 mt-6 pt-4 text-center text-sm">
          <span className="text-gray-500">New to SummTube? </span>
          <button
            onClick={onSwitchToSignup}
            className="font-medium text-gray-800 hover:text-gray-600 transition"
            disabled={isLoading}
          >
            Create an account
          </button>
        </div>
      </div>
    </div>
  );
};

export default LoginModal;