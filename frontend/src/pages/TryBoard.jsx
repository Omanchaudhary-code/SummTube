import { useState, useEffect, useRef } from "react";
import { ListCollapse, Send, X, Menu } from "lucide-react";
import logo from "../assets/logo.png";
import { HiEye, HiEyeOff } from "react-icons/hi";
import { useNavigate } from "react-router-dom";
import api from "../services/api.js";
import toast from "react-hot-toast";

const NavMenuBtn = ({ onLoginClick, onSignupClick, isMobile }) => {
  return (
    <li
      className={`flex ${
        isMobile ? "flex-col w-full px-6 gap-3" : "gap-3"
      }`}
    >
      <button
        onClick={onLoginClick}
        className={`${
          isMobile ? "w-full" : ""
        } px-4 py-2 rounded border hover:bg-gray-100 transition`}
      >
        Login
      </button>

      <button
        onClick={onSignupClick}
        className={`${
          isMobile ? "w-full" : ""
        } px-4 py-2 rounded bg-white text-black hover:opacity-90 transition`}
      >
        Sign up for free
      </button>
    </li>
  );
};


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
      const result = await api.post("/auth/google", {
        token: response.credential,
      });

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

const SignupModal = ({
  onClose,
  onSwitchToLogin,
  variant = "default" 
}) => {
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: ""
  });

  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const isTryBoard = variant === "tryboard"; 

  useEffect(() => {
    document.body.style.overflow = "hidden";
    return () => (document.body.style.overflow = "auto");
  }, []);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
    setError("");
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      await api.post("/auth/register", {
        name: form.name.trim(),
        email: form.email.trim(),
        password: form.password
      });

      toast.success("Account created successfully! Please log in.");
      setTimeout(onSwitchToLogin, 800);
    } catch (err) {
      const msg =
        err.response?.data?.message || "Something went wrong.";
      toast.error(msg);
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      onClick={onClose}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm px-3"
    >
      <div
        onClick={(e) => e.stopPropagation()}
        className={`
          w-full max-w-[450px]
          rounded-xl p-6 shadow-xl
          animate-scaleIn text-center
          ${isTryBoard ? "bg-white text-black" : "bg-white"}
        `}
      >
        <div className="flex justify-center mb-3">
          <img src={logo} alt="logo" className="w-24" />
        </div>

        <h2 className="text-2xl font-medium">
          Welcome to SummTube
        </h2>

        <p className={`mb-6 ${isTryBoard ? "text-gray-700" : "text-gray-500"}`}>
          Register with your email
        </p>

        <form onSubmit={handleSubmit}>
          <input
            name="name"
            placeholder="Full Name"
            value={form.name}
            onChange={handleChange}
            required
            className="w-full mb-3 px-3 py-2.5 border rounded"
          />

          <input
            name="email"
            type="email"
            placeholder="Email"
            value={form.email}
            onChange={handleChange}
            required
            className="w-full mb-3 px-3 py-2.5 border rounded"
          />

          <div className="relative mb-3">
            <input
              name="password"
              type={showPassword ? "text" : "password"}
              placeholder="Password"
              value={form.password}
              onChange={handleChange}
              required
              className="w-full px-3 py-2.5 border rounded pr-10"
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-1/2 -translate-y-1/2"
            >
              {showPassword ? <HiEyeOff /> : <HiEye />}
            </button>
          </div>

          {error && (
            <p className="text-sm text-red-600 mb-3">{error}</p>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full py-2.5 bg-black text-white rounded"
          >
            {loading ? "Creating..." : "Sign Up"}
          </button>
        </form>

        <div className="border-t mt-5 pt-4 text-sm">
          Already have an account?
          <button
            onClick={onSwitchToLogin}
            className="ml-2 font-medium"
          >
            Login
          </button>
        </div>
      </div>
    </div>
  );
};
const TryBoard = () => {
  const [isLoginOpen, setIsLoginOpen] = useState(false);
  const [isSignupOpen, setIsSignupOpen] = useState(false);
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [link, setLink] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [triesLeft, setTriesLeft] = useState(3);
  const [summary, setSummary] = useState(null);
  const [error, setError] = useState(null);

  // Fetch guest status on component mount
  useEffect(() => {
    fetchGuestStatus();
  }, []);

  const fetchGuestStatus = async () => {
    try {
      const response = await api.get("/guest/status");
      
      if (response.data.success) {
        setTriesLeft(response.data.status.triesLeft);
      }
    } catch (error) {
      console.error("Error fetching guest status:", error);
    }
  };

  const handleSubmit = async () => {
    if (!link.trim()) {
      alert("Please enter a YouTube link");
      return;
    }

    if (triesLeft <= 0) {
      alert("You've used all your free tries! Please login to continue.");
      setIsLoginOpen(true);
      return;
    }

    setIsLoading(true);
    setError(null);
    setSummary(null);

    try {
      const response = await api.post("/summary/guest", {
        video_url: link,
        summary_type: "detailed",
      });

      if (response.data.success) {
        setSummary(response.data);
        setTriesLeft(response.data.guest_status.triesLeft);
        setLink("");

        if (response.data.message) {
          alert(response.data.message);
        }
      } else {
        setError(response.data.error || response.data.message || "Failed to generate summary");
        alert(response.data.error || "Failed to generate summary. Please try again.");
      }
    } catch (error) {
      console.error("Error submitting link:", error);
      const errorMsg = error.response?.data?.error || 
                       error.response?.data?.message || 
                       "Network error. Please check your connection.";
      setError(errorMsg);
      alert(errorMsg);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <>
      <div className="wrapper h-screen w-screen flex text-white overflow-hidden">
        {/* LEFT SIDEBAR */}
        <div
          className={`left-section h-full bg-[#202124] flex-shrink-0 transition-all duration-300 ease-in-out ${
            isSidebarOpen ? "w-64" : "w-0 md:w-16"
          } overflow-hidden`}
        >
          <div className={`h-full ${isSidebarOpen ? "p-5" : "p-2 md:p-3"}`}>
            {/* Logo and Toggle Section */}
            <div className="flex items-center justify-between mb-8">
              {isSidebarOpen ? (
                <>
                  <div className="flex items-center gap-2">
                    <div className="w-10 h-10 bg-white rounded-lg flex items-center justify-center flex-shrink-0">
                      <span>
                        <img src={logo} alt="Summtube logo" />
                      </span>
                    </div>
                  </div>
                  <button
                    onClick={() => setIsSidebarOpen(false)}
                    className="hover:bg-cyan-400 p-1 rounded transition-colors flex-shrink-0"
                  >
                    <ListCollapse size={24} />
                  </button>
                </>
              ) : (
                <button
                  onClick={() => setIsSidebarOpen(true)}
                  className="w-full flex justify-center hover:bg-cyan-400 p-2 rounded transition-colors"
                >
                  <Menu size={24} />
                </button>
              )}
            </div>

            {/* Guest Trial Info - Only show when sidebar is open */}
            {isSidebarOpen && (
              <div className="space-y-4">
                <div className="bg-[#181818] rounded-lg p-4">
                  <h3 className="font-semibold text-lg mb-2">Guest Trial</h3>
                  <p className="text-sm text-cyan-100 mb-3">
                    You can try SummTube {triesLeft} more {triesLeft === 1 ? "time" : "times"} for
                    free!
                  </p>
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-xs">Tries remaining:</span>
                    <span className="font-bold text-lg">{triesLeft}/3</span>
                  </div>
                  <div className="w-full bg-[#282828] rounded-full h-2 mb-3">
                    <div
                      className="bg-white h-2 rounded-full transition-all duration-300"
                      style={{ width: `${(triesLeft / 3) * 100}%` }}
                    />
                  </div>
                  <button
                    onClick={() => setIsLoginOpen(true)}
                    className="w-full bg-white text-black py-2 rounded font-semibold hover:bg-cyan-50 transition-colors text-sm px-3"
                  >
                    Login for Unlimited Access
                  </button>
                </div>

                <div className="bg-[#181818] bg-opacity-50 rounded-lg p-4">
                  <h4 className="font-semibold mb-2 text-sm">Why Login?</h4>
                  <ul className="space-y-2 text-xs text-cyan-100">
                    <li>✓ Unlimited summaries</li>
                    <li>✓ Save your history</li>
                    <li>✓ Download summaries</li>
                    <li>✓ Priority support</li>
                  </ul>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* RIGHT SECTION */}
        <div className="right-section flex-1 h-full bg-[#181818] flex flex-col overflow-hidden">
          {/* Top Navigation */}
          <div className="top-section py-3 px-4 md:py-4 md:px-6 lg:px-10 flex items-center justify-between border-b border-cyan-600 flex-shrink-0">
            <div className="flex items-center gap-3">
              <h1 className="text-xl md:text-2xl lg:text-3xl font-bold">SummTube</h1>
            </div>
            <div className="hidden md:block">
              <NavMenuBtn
                onLoginClick={() => setIsLoginOpen(true)}
                onSignupClick={() => setIsSignupOpen(true)}
              />
            </div>
            <div className="md:hidden">
              <button
                onClick={() => setIsLoginOpen(true)}
                className="px-3 py-1.5 bg-white text-cyan-700 rounded text-sm"
              >
                Login
              </button>
            </div>
          </div>

          {/* Summary Content Section */}
          <div className="summary-content-section flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
            <div className="max-w-4xl mx-auto">
              <div className="bg-cyan-600 rounded-lg p-6 mb-4">
                <h2 className="text-xl md:text-2xl font-semibold mb-3">
                  Welcome to SummTube - Trial Mode
                </h2>
                <p className="text-cyan-100 text-sm md:text-base mb-2">
                  Paste a YouTube link below to get an AI-generated summary of the video content.
                </p>
                <p className="text-cyan-200 text-xs md:text-sm">
                  💡 You have{" "}
                  <span className="font-bold text-white">
                    {triesLeft} free {triesLeft === 1 ? "try" : "tries"}
                  </span>{" "}
                  remaining. Login for unlimited access!
                </p>
              </div>

              {/* Error Display */}
              {error && (
                <div className="bg-red-500 bg-opacity-20 border border-red-500 rounded-lg p-4 mb-4">
                  <p className="text-red-200">{error}</p>
                </div>
              )}

              {/* Summary Display */}
              {summary && (
                <div className="bg-[#202124] rounded-lg p-6 space-y-4">
                  <div className="flex items-start gap-4">
                    {summary.thumbnail && (
                      <img
                        src={summary.thumbnail}
                        alt={summary.video_title}
                        className="w-32 h-20 object-cover rounded"
                      />
                    )}
                    <div>
                      <h3 className="text-xl font-semibold mb-2">{summary.video_title}</h3>
                      <p className="text-sm text-cyan-300">
                        Duration: {Math.floor(summary.duration / 60)}:{String(summary.duration % 60).padStart(2, '0')}
                      </p>
                    </div>
                  </div>

                  <div className="border-t border-cyan-600 pt-4">
                    <h4 className="text-lg font-semibold mb-2">Summary:</h4>
                    <p className="text-cyan-100 leading-relaxed whitespace-pre-wrap">
                      {summary.summary}
                    </p>
                  </div>

                  <div className="flex items-center justify-between text-sm text-cyan-300 border-t border-cyan-600 pt-4">
                    <span>Transcript length: {summary.transcript_length} characters</span>
                    <span>Processed in {summary.processing_time}s</span>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Bottom Input Section - Fixed at bottom right */}
          <div className="bottom-section p-4 md:p-6 border-t border-cyan-600 flex-shrink-0">
            <div className="max-w-4xl mx-auto">
              <div className="relative">
                <div className="flex items-center gap-2 bg-white rounded-full py-2 px-5">
                  <input
                    type="text"
                    name="text"
                    id="text"
                    value={link}
                    onChange={(e) => setLink(e.target.value)}
                    onKeyPress={(e) => {
                      if (e.key === "Enter" && !isLoading) {
                        handleSubmit();
                      }
                    }}
                    placeholder="Paste YouTube link here..."
                    className="flex-1 px-3 py-2 md:py-3 text-gray-800 outline-none text-sm md:text-base"
                    disabled={isLoading}
                  />
                  <button
                    type="button"
                    onClick={handleSubmit}
                    disabled={isLoading}
                    className="bg-cyan-600 text-white p-2 md:p-3 rounded-lg hover:bg-cyan-500 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                  >
                    {isLoading ? (
                      <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    ) : (
                      <Send size={20} />
                    )}
                  </button>
                </div>
              </div>
              <p className="text-xs md:text-sm text-cyan-200 mt-2 text-center">
                Supported: YouTube video links
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Modals */}
      {isLoginOpen && (
        <LoginModal
          onClose={() => setIsLoginOpen(false)}
          onSwitchToSignup={() => {
            setIsLoginOpen(false);
            setIsSignupOpen(true);
          }}
        />
      )}
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

export default TryBoard;