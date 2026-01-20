import { useState, useEffect, useRef } from "react";
import { ListCollapse, Send, X, Menu, Sparkles, Zap, Clock, CheckCircle2, AlertCircle, Loader2, Copy, Check, ExternalLink } from "lucide-react";
import logo from "../assets/logo.png";
import { HiEye, HiEyeOff } from "react-icons/hi";
import { useNavigate } from "react-router-dom";
import api from "../services/api.js";
import toast from "react-hot-toast";

const NavMenuBtn = ({ onLoginClick, onSignupClick, isMobile }) => {
  return (
    <div
      className={`flex ${isMobile ? "flex-col w-full px-6 gap-3" : "gap-3"
        }`}
    >
      <button
        onClick={onLoginClick}
        className={`${isMobile ? "w-full" : ""
          } px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors`}
      >
        Login
      </button>

      <button
        onClick={onSignupClick}
        className={`${isMobile ? "w-full" : ""
          } px-4 py-2 rounded-lg bg-black text-white hover:bg-gray-800 transition-colors`}
      >
        Sign up for free
      </button>
    </div>
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

  useEffect(() => {
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = "auto";
    };
  }, []);

  useEffect(() => {
    const existingScript = document.querySelector(
      'script[src="https://accounts.google.com/gsi/client"]'
    );

    if (existingScript) {
      if (window.google && !isInitializing.current) {
        initGoogle();
      }
      return;
    }

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
      // Cleanup
    };
  }, []);

  useEffect(() => {
    if (googleReady && googleButtonRef.current && window.google) {
      try {
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
        setGoogleError("Failed to render Google button", error);
      }
    }
  }, [googleReady]);

  const initGoogle = async () => {
    if (isInitializing.current) {
      return;
    }

    isInitializing.current = true;

    try {
      const res = await api.get("/auth/google/config");

      if (!res.data.success) {
        throw new Error("Failed to get Google configuration");
      }

      if (!res.data.client_id) {
        throw new Error("Google Client ID not configured");
      }

      if (!window.google?.accounts?.id) {
        throw new Error("Google SDK not loaded");
      }

      const googleClientId = res.data.client_id;

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
        toast.success(`Welcome, ${result.data.user.name}!`);
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
        "Google login failed. Please try again.";

      toast.error(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  const handleLocalLogin = async (e) => {
    e.preventDefault();

    if (!email || !password) {
      toast.error("Please fill in all fields");
      return;
    }

    setIsLoading(true);

    try {
      const result = await api.post("/auth/login", {
        email: email.trim(),
        password,
      });

      if (result.data.success && result.data.user) {
        toast.success(`Welcome back, ${result.data.user.name}!`);
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

      toast.error(errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div
      onClick={onClose}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
    >
      <div
        onClick={(e) => e.stopPropagation()}
        className="w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto rounded-xl bg-white p-6 shadow-xl"
      >
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

        <h2 className="text-2xl font-semibold mb-1 text-gray-800 text-center">
          Welcome to SummTube
        </h2>
        <p className="text-gray-500 mb-6 text-center">
          Login or continue with Google
        </p>

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
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              disabled={isLoading}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 disabled:cursor-not-allowed"
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

        <div className="flex items-center my-6">
          <div className="flex-1 border-t border-gray-300" />
          <span className="px-4 text-sm text-gray-500">or</span>
          <div className="flex-1 border-t border-gray-300" />
        </div>

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
              <Loader2 className="w-5 h-5 animate-spin text-gray-500" />
              <span className="text-gray-600">Loading Google Sign-In...</span>
            </button>
          )}
        </div>

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
}) => {
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: ""
  });

  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

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
        className="w-full max-w-[450px] rounded-xl p-6 shadow-xl animate-scaleIn bg-white text-center"
      >
        <div className="flex justify-center mb-3">
          <img src={logo} alt="logo" className="w-24" />
        </div>

        <h2 className="text-2xl font-medium">
          Welcome to SummTube
        </h2>

        <p className="text-gray-500 mb-6">
          Register with your email
        </p>

        <form onSubmit={handleSubmit}>
          <input
            name="name"
            placeholder="Full Name"
            value={form.name}
            onChange={handleChange}
            required
            className="w-full mb-3 px-3 py-2.5 border rounded-lg"
          />

          <input
            name="email"
            type="email"
            placeholder="Email"
            value={form.email}
            onChange={handleChange}
            required
            className="w-full mb-3 px-3 py-2.5 border rounded-lg"
          />

          <div className="relative mb-3">
            <input
              name="password"
              type={showPassword ? "text" : "password"}
              placeholder="Password"
              value={form.password}
              onChange={handleChange}
              required
              className="w-full px-3 py-2.5 border rounded-lg pr-10"
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
            className="w-full py-2.5 bg-black text-white rounded-lg"
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

// Typing animation component for real-time summary display
const TypingAnimation = ({ text, onComplete }) => {
  const [displayedText, setDisplayedText] = useState("");
  const [currentIndex, setCurrentIndex] = useState(0);
  const intervalRef = useRef(null);

  useEffect(() => {
    if (currentIndex < text.length) {
      intervalRef.current = setTimeout(() => {
        setDisplayedText(text.slice(0, currentIndex + 1));
        setCurrentIndex(currentIndex + 1);
      }, 15); // Adjust speed here (lower = faster)
    } else if (currentIndex === text.length && onComplete) {
      onComplete();
    }

    return () => {
      if (intervalRef.current) {
        clearTimeout(intervalRef.current);
      }
    };
  }, [currentIndex, text, onComplete]);

  useEffect(() => {
    // Reset when text changes
    setDisplayedText("");
    setCurrentIndex(0);
  }, [text]);

  return (
    <div className="leading-relaxed">
      {displayedText}
      {currentIndex < text.length && (
        <span className="inline-block w-0.5 h-5 bg-gray-400 ml-1 animate-pulse" />
      )}
    </div>
  );
};

// Progress steps component for generation process (responsive)
const GenerationSteps = ({ currentStep }) => {
  const steps = [
    { id: 1, label: "Fetching video", icon: "📹" },
    { id: 2, label: "Extracting transcript", icon: "📝" },
    { id: 3, label: "Analyzing content", icon: "🧠" },
    { id: 4, label: "Generating summary", icon: "✨" },
  ];

  return (
    <div className="flex items-center justify-center gap-2 sm:gap-3 md:gap-4 py-6 sm:py-8 px-2">
      {steps.map((step, index) => (
        <div key={step.id} className="flex flex-col sm:flex-row items-center gap-1 sm:gap-2">
          <div className="flex flex-col items-center">
            <div
              className={`w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center text-lg sm:text-xl transition-all duration-500 ${currentStep >= step.id
                ? "bg-gradient-to-br from-emerald-400 to-blue-500 text-white scale-110 shadow-lg"
                : "bg-gray-200 text-gray-400"
                }`}
            >
              {currentStep >= step.id ? "✓" : step.icon}
            </div>
            <span className={`text-xs sm:text-sm mt-1 sm:mt-2 text-center max-w-[60px] sm:max-w-none ${currentStep >= step.id ? "text-gray-800 font-medium" : "text-gray-500"
              }`}>
              {step.label}
            </span>
          </div>
          {index < steps.length - 1 && (
            <div
              className={`hidden sm:block w-8 md:w-12 lg:w-16 h-1 transition-all duration-500 ${currentStep > step.id ? "bg-gradient-to-r from-emerald-400 to-blue-500" : "bg-gray-200"
                }`}
            />
          )}
        </div>
      ))}
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
  const [generationStep, setGenerationStep] = useState(0);
  const [isTyping, setIsTyping] = useState(false);
  const [copied, setCopied] = useState(false);
  const summaryRef = useRef(null);

  // Fetch guest status on component mount and after each summary
  useEffect(() => {
    fetchGuestStatus();
  }, []);

  const fetchGuestStatus = async () => {
    try {
      const response = await api.get("/guest/status");
      if (response.data.success) {
        setTriesLeft(response.data.status.triesLeft || 0);
      }
    } catch (error) {
      console.error("Error fetching guest status:", error);
    }
  };

  const handleSubmit = async () => {
    if (!link.trim()) {
      toast.error("Please enter a YouTube link");
      return;
    }

    if (triesLeft <= 0) {
      toast.error("You've used all your free tries! Please login to continue.");
      setIsLoginOpen(true);
      return;
    }

    setIsLoading(true);
    setError(null);
    setSummary(null);
    setGenerationStep(1);
    setIsTyping(false);

    // Simulate progress steps
    const stepInterval = setInterval(() => {
      setGenerationStep((prev) => {
        if (prev >= 4) {
          clearInterval(stepInterval);
          return 4;
        }
        return prev + 1;
      });
    }, 2000);

    try {
      const response = await api.post("/summary/guest", {
        video_url: link,
        summary_type: "detailed",
      });

      clearInterval(stepInterval);
      setGenerationStep(4);

      if (response.data.success) {
        // Small delay before showing summary for smooth transition
        setTimeout(() => {
          setSummary(response.data);
          setTriesLeft(response.data.guest_status?.triesLeft || 0);
          setLink("");
          setIsTyping(true);

          // Scroll to summary when it appears
          setTimeout(() => {
            summaryRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
          }, 100);
        }, 500);

        if (response.data.message) {
          toast.success(response.data.message);
        }
      } else {
        setError(response.data.error || response.data.message || "Failed to generate summary");
        toast.error(response.data.error || "Failed to generate summary");
      }
    } catch (error) {
      clearInterval(stepInterval);
      setGenerationStep(0);
      console.error("Error submitting link:", error);
      const errorTitle = error.response?.data?.error || "Error generating summary";
      const errorDetail = error.response?.data?.message || "Please check your internet connection and try again.";
      setError(errorTitle);
      toast.error(`${errorTitle}: ${errorDetail}`);
    } finally {
      setIsLoading(false);
    }
  };

  const trialPercentage = (triesLeft / 3) * 100;

  const handleCopySummary = () => {
    if (summary?.summary) {
      navigator.clipboard.writeText(summary.summary);
      setCopied(true);
      toast.success("Summary copied to clipboard!");
      setTimeout(() => setCopied(false), 2000);
    }
  };

  const handleOpenVideo = () => {
    if (summary?.video_url) {
      window.open(summary.video_url, '_blank', 'noopener,noreferrer');
    }
  };

  return (
    <>
      <div className="min-h-screen w-full bg-[var(--bg-main)] flex flex-col md:flex-row overflow-hidden">
        {/* Mobile backdrop overlay */}
        {isSidebarOpen && (
          <div
            className="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 md:hidden animate-fadeIn"
            onClick={() => setIsSidebarOpen(false)}
            aria-hidden="true"
          />
        )}

        {/* LEFT SIDEBAR - Slides from left on mobile, sidebar on desktop */}
        <aside
          className={`fixed md:sticky top-0 left-0 h-full md:h-screen bg-white border-r border-gray-200 flex-shrink-0 z-50 md:z-auto shadow-2xl md:shadow-sm transition-all duration-300 ease-out ${isSidebarOpen
            ? "translate-x-0 w-80 md:w-80"
            : "-translate-x-full md:translate-x-0 md:w-20"
            }`}
        >
          <div className={`h-full flex flex-col ${isSidebarOpen ? "p-4 sm:p-6" : "p-3"}`}>
            {/* Logo and Toggle */}
            <div className="flex items-center justify-between mb-6 md:mb-8 flex-shrink-0">
              {isSidebarOpen ? (
                <>
                  <div className="flex items-center gap-2 sm:gap-3">
                    <div className="w-10 h-10 bg-black rounded-lg flex items-center justify-center flex-shrink-0">
                      <img src={logo} alt="Summtube logo" className="w-8 h-8" />
                    </div>
                    <span className="font-bold text-lg">SummTube</span>
                  </div>
                  <button
                    onClick={() => setIsSidebarOpen(false)}
                    className="hover:bg-gray-100 p-2 rounded-lg transition-colors"
                    aria-label="Close sidebar"
                  >
                    <X size={20} className="text-gray-600" />
                  </button>
                </>
              ) : (
                <button
                  onClick={() => setIsSidebarOpen(true)}
                  className="w-full flex justify-center hover:bg-gray-100 p-2 rounded-lg transition-colors"
                  aria-label="Open sidebar"
                >
                  <Menu size={24} className="text-gray-600" />
                </button>
              )}
            </div>

            {/* Sidebar Content - Scrollable */}
            {isSidebarOpen && (
              <div className="flex-1 overflow-y-auto space-y-4">
                {/* Guest Trial Info */}
                <div className="bg-gradient-to-br from-emerald-50 to-blue-50 rounded-xl p-5 border border-emerald-100">
                  <div className="flex items-center gap-2 mb-3">
                    <Sparkles className="w-5 h-5 text-emerald-600 flex-shrink-0" />
                    <h3 className="font-semibold text-lg text-gray-800">Free Trial</h3>
                  </div>

                  <div className="mb-4">
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-sm text-gray-600">Tries remaining</span>
                      <span className={`font-bold text-2xl ${triesLeft === 0 ? "text-red-500" : triesLeft === 1 ? "text-orange-500" : "text-emerald-600"}`}>
                        {triesLeft}
                      </span>
                    </div>

                    {/* Progress Bar */}
                    <div className="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                      <div
                        className={`h-full rounded-full transition-all duration-500 ease-out ${triesLeft === 0
                          ? "bg-red-400"
                          : triesLeft === 1
                            ? "bg-orange-400"
                            : "bg-gradient-to-r from-emerald-400 to-blue-500"
                          }`}
                        style={{ width: `${trialPercentage}%` }}
                      />
                    </div>

                    <p className="text-xs text-gray-500 mt-2">
                      {triesLeft === 0
                        ? "All trials used"
                        : triesLeft === 1
                          ? "Last trial remaining"
                          : `${triesLeft} trials remaining`}
                    </p>
                  </div>

                  <button
                    onClick={() => setIsLoginOpen(true)}
                    className="w-full bg-black text-white py-2.5 rounded-lg font-semibold hover:bg-gray-800 transition-colors text-sm"
                  >
                    Login for Unlimited Access
                  </button>
                </div>

                {/* Benefits Card */}
                <div className="bg-white border border-gray-200 rounded-xl p-5">
                  <h4 className="font-semibold mb-3 text-sm text-gray-800">Why Login?</h4>
                  <ul className="space-y-2 text-xs text-gray-600">
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-500 flex-shrink-0" />
                      <span>Unlimited summaries</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-500 flex-shrink-0" />
                      <span>Save your history</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-500 flex-shrink-0" />
                      <span>Download summaries</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-500 flex-shrink-0" />
                      <span>Priority support</span>
                    </li>
                  </ul>
                </div>
              </div>
            )}
          </div>
        </aside>

        {/* MAIN CONTENT */}
        <div className="flex-1 min-h-screen md:h-screen flex flex-col overflow-hidden bg-[var(--bg-main)]">
          {/* Top Navigation */}
          <div className="top-section py-3 sm:py-4 px-4 sm:px-6 lg:px-10 flex items-center justify-between border-b border-gray-200 bg-white flex-shrink-0 sticky top-0 z-30">

            <div className="flex items-center gap-2 sm:gap-3">
              {/* Mobile menu button */}
              <button
                onClick={() => setIsSidebarOpen(true)}
                className="md:hidden p-2 hover:bg-gray-100 rounded-lg transition-colors"
                aria-label="Open menu"
              >
                <Menu size={20} className="text-gray-600" />
              </button>
              <h1 className="text-xl sm:text-2xl lg:text-3xl font-bold bg-gradient-to-r from-emerald-600 to-blue-600 bg-clip-text text-transparent">
                SummTube
              </h1>
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
                className="px-3 sm:px-4 py-1.5 sm:py-2 bg-black text-white rounded-lg text-xs sm:text-sm hover:bg-gray-800 transition whitespace-nowrap"
              >
                Login
              </button>
            </div>
          </div >

          {/* Main Content Area */}
          < div className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 xl:p-10" >
            <div className="max-w-4xl mx-auto space-y-4 sm:space-y-6">
              {/* Welcome Card */}
              <div className="bg-gradient-to-br from-emerald-50 via-blue-50 to-purple-50 rounded-xl sm:rounded-2xl p-5 sm:p-6 lg:p-8 border border-emerald-100 shadow-sm">
                <div className="flex flex-col sm:flex-row items-start gap-3 sm:gap-4 mb-4">
                  <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-emerald-400 to-blue-500 rounded-lg sm:rounded-xl flex items-center justify-center flex-shrink-0">
                    <Zap className="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <h2 className="text-xl sm:text-2xl lg:text-3xl font-bold mb-2 text-gray-800">
                      Try SummTube Free
                    </h2>
                    <p className="text-sm sm:text-base text-gray-600 leading-relaxed">
                      Paste a YouTube link below to get an AI-generated summary. Experience the power of AI-powered video summarization.
                    </p>
                  </div>
                </div>

                <div className="mt-4 flex flex-wrap items-center gap-2 text-xs sm:text-sm">
                  <Clock className="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-500 flex-shrink-0" />
                  <span className="text-gray-600">
                    You have{" "}
                    <span className={`font-bold ${triesLeft === 0 ? "text-red-500" : triesLeft === 1 ? "text-orange-500" : "text-emerald-600"}`}>
                      {triesLeft} free {triesLeft === 1 ? "try" : "tries"}
                    </span>{" "}
                    remaining
                  </span>
                </div>
              </div>

              {/* Generation Steps (shown during loading) */}
              {isLoading && (
                <div className="bg-white rounded-xl sm:rounded-2xl p-5 sm:p-6 lg:p-8 border border-gray-200 shadow-sm animate-slideDown">
                  <h3 className="text-base sm:text-lg font-semibold mb-4 sm:mb-6 text-center text-gray-800">
                    Generating your summary...
                  </h3>
                  <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <GenerationSteps currentStep={generationStep} />
                  </div>
                  <p className="text-center text-xs sm:text-sm text-gray-500 mt-3 sm:mt-4">
                    This may take 15-30 seconds
                  </p>
                </div>
              )}

              {/* Error Display */}
              {error && !isLoading && (
                <div className="bg-red-50 border border-red-200 rounded-xl sm:rounded-2xl p-4 sm:p-6 animate-slideDown">
                  <div className="flex items-start gap-2 sm:gap-3">
                    <AlertCircle className="w-4 h-4 sm:w-5 sm:h-5 text-red-500 flex-shrink-0 mt-0.5" />
                    <div className="min-w-0 flex-1">
                      <h4 className="font-semibold text-sm sm:text-base text-red-800 mb-1">Error</h4>
                      <p className="text-xs sm:text-sm text-red-700 break-words">{error}</p>
                    </div>
                  </div>
                </div>
              )}

              {/* Summary Display */}
              {summary && !isLoading && (
                <div
                  ref={summaryRef}
                  className="bg-white rounded-xl sm:rounded-2xl p-5 sm:p-6 lg:p-8 border border-gray-200 shadow-lg animate-slideDown space-y-4 sm:space-y-6"
                >
                  {/* Video Header */}
                  <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 pb-4 sm:pb-6 border-b border-gray-200">
                    {summary.thumbnail && (
                      <div className="relative group cursor-pointer" onClick={handleOpenVideo}>
                        <img
                          src={summary.thumbnail}
                          alt={summary.video_title}
                          className="w-full sm:w-40 md:w-48 h-24 sm:h-32 object-cover rounded-lg sm:rounded-xl flex-shrink-0 transition-transform group-hover:scale-105"
                        />
                        <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg sm:rounded-xl flex items-center justify-center">
                          <ExternalLink className="w-5 h-5 text-white" />
                        </div>
                      </div>
                    )}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-start justify-between gap-2 mb-2">
                        <h3 className="text-lg sm:text-xl lg:text-2xl font-bold text-gray-800 break-words flex-1">
                          {summary.video_title}
                        </h3>
                        <div className="flex items-center gap-2 flex-shrink-0">
                          <button
                            onClick={handleCopySummary}
                            className="p-2 hover:bg-gray-100 rounded-lg transition-colors group"
                            title="Copy summary"
                          >
                            {copied ? (
                              <Check className="w-4 h-4 text-emerald-500" />
                            ) : (
                              <Copy className="w-4 h-4 text-gray-600 group-hover:text-gray-800" />
                            )}
                          </button>
                          {summary.video_url && (
                            <button
                              onClick={handleOpenVideo}
                              className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                              title="Open video"
                            >
                              <ExternalLink className="w-4 h-4 text-gray-600 hover:text-gray-800" />
                            </button>
                          )}
                        </div>
                      </div>
                      <div className="flex flex-wrap items-center gap-3 sm:gap-4 text-xs sm:text-sm text-gray-600">
                        <div className="flex items-center gap-1">
                          <Clock className="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0" />
                          <span>{Math.floor(summary.duration / 60)}:{String(summary.duration % 60).padStart(2, '0')}</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <Zap className="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0" />
                          <span>{summary.processing_time}s</span>
                        </div>
                        <div className="text-gray-500">
                          {summary.transcript_length?.toLocaleString()} chars
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Summary Content */}
                  <div>
                    <h4 className="text-base sm:text-lg font-semibold mb-3 sm:mb-4 text-gray-800 flex items-center gap-2">
                      <Sparkles className="w-4 h-4 sm:w-5 sm:h-5 text-emerald-500 flex-shrink-0" />
                      AI Summary
                    </h4>
                    <div className="prose prose-sm max-w-none">
                      {isTyping ? (
                        <div className="text-sm sm:text-base text-gray-700 leading-relaxed min-h-[150px] sm:min-h-[200px]">
                          <TypingAnimation
                            text={summary.summary}
                            onComplete={() => setIsTyping(false)}
                          />
                        </div>
                      ) : (
                        <p className="text-sm sm:text-base text-gray-700 leading-relaxed whitespace-pre-wrap break-words">
                          {summary.summary}
                        </p>
                      )}
                    </div>
                  </div>

                  {/* Footer Stats */}
                  <div className="pt-4 sm:pt-6 border-t border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs sm:text-sm text-gray-500">
                    <span>Summary generated successfully</span>
                    <span className="flex items-center gap-1">
                      <CheckCircle2 className="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-500 flex-shrink-0" />
                      Complete
                    </span>
                  </div>
                </div>
              )}
            </div>
          </div >

          {/* Bottom Input Section - Fixed */}
          < div className="bottom-section p-4 sm:p-6 border-t border-gray-200 bg-white flex-shrink-0 sticky bottom-0 z-20" >
            <div className="max-w-4xl mx-auto">
              <div className="relative">
                <div className="flex items-center gap-2 sm:gap-3 bg-gray-50 rounded-xl sm:rounded-2xl py-2.5 sm:py-3 px-3 sm:px-5 border border-gray-200 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition-all">
                  <input
                    type="text"
                    value={link}
                    onChange={(e) => setLink(e.target.value)}
                    onKeyPress={(e) => {
                      if (e.key === "Enter" && !isLoading && triesLeft > 0) {
                        handleSubmit();
                      }
                    }}
                    placeholder="Paste YouTube link here..."
                    className="flex-1 bg-transparent outline-none text-gray-800 placeholder-gray-400 text-sm sm:text-base min-w-0"
                    disabled={isLoading || triesLeft <= 0}
                  />
                  <button
                    type="button"
                    onClick={handleSubmit}
                    disabled={isLoading || triesLeft <= 0 || !link.trim()}
                    className={`p-2.5 sm:p-3 rounded-lg sm:rounded-xl transition-all flex-shrink-0 ${isLoading || triesLeft <= 0 || !link.trim()
                      ? "bg-gray-300 cursor-not-allowed"
                      : "bg-gradient-to-r from-emerald-500 to-blue-500 hover:from-emerald-600 hover:to-blue-600 text-white shadow-lg hover:shadow-xl active:scale-95"
                      }`}
                    aria-label="Generate summary"
                  >
                    {isLoading ? (
                      <Loader2 className="w-4 h-4 sm:w-5 sm:h-5 animate-spin" />
                    ) : (
                      <Send className="w-4 h-4 sm:w-5 sm:h-5" />
                    )}
                  </button>
                </div>
              </div>
              <p className="text-xs sm:text-sm text-gray-500 mt-2 sm:mt-3 text-center px-2">
                {triesLeft <= 0
                  ? "You've used all free trials. Login for unlimited access!"
                  : "Supported: YouTube video links with captions"}
              </p>
            </div>
          </div >
        </div >
      </div >

      {/* Modals */}
      {
        isLoginOpen && (
          <LoginModal
            onClose={() => setIsLoginOpen(false)}
            onSwitchToSignup={() => {
              setIsLoginOpen(false);
              setIsSignupOpen(true);
            }}
          />
        )
      }
      {
        isSignupOpen && (
          <SignupModal
            onClose={() => setIsSignupOpen(false)}
            onSwitchToLogin={() => {
              setIsSignupOpen(false);
              setIsLoginOpen(true);
            }}
          />
        )
      }
    </>
  );
};

export default TryBoard;
