import { useState } from "react";
import { ListCollapse, Send, X, Menu } from "lucide-react";
import logo  from "../assets/logo.png";

// Mock components - replace with your actual components
const NavMenuBtn = ({ onLoginClick, onSignupClick }) => (
  <div className="flex gap-3">
    <button
      onClick={onLoginClick}
      className="px-4 py-2 bg-white text-cyan-700 rounded-lg hover:bg-gray-100 transition-colors"
    >
      Login
    </button>
    <button
      onClick={onSignupClick}
      className="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-500 transition-colors"
    >
      Sign Up
    </button>
  </div>
);

const LoginModal = ({ onClose, onSwitchToSignup }) => {
  const handleLogin = () => {
    console.log("Login clicked");
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg p-6 w-full max-w-md relative">
        <button onClick={onClose} className="absolute top-4 right-4 text-gray-600">
          <X size={24} />
        </button>
        <h2 className="text-2xl font-bold text-gray-800 mb-4">Login</h2>
        <div className="space-y-4">
          <input
            type="email"
            placeholder="Email"
            className="w-full px-4 py-2 border rounded-lg text-gray-800"
          />
          <input
            type="password"
            placeholder="Password"
            className="w-full px-4 py-2 border rounded-lg text-gray-800"
          />
          <button onClick={handleLogin} className="w-full bg-cyan-600 text-white py-2 rounded-lg hover:bg-cyan-700">
            Login
          </button>
        </div>
        <p className="text-center mt-4 text-gray-600">
          Don't have an account?{" "}
          <button onClick={onSwitchToSignup} className="text-cyan-600 hover:underline">
            Sign Up
          </button>
        </p>
      </div>
    </div>
  );
};

const SignupModal = ({ onClose, onSwitchToLogin }) => {
  const handleSignup = () => {
    console.log("Signup clicked");
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg p-6 w-full max-w-md relative">
        <button onClick={onClose} className="absolute top-4 right-4 text-gray-600">
          <X size={24} />
        </button>
        <h2 className="text-2xl font-bold text-gray-800 mb-4">Sign Up</h2>
        <div className="space-y-4">
          <input
            type="text"
            placeholder="Name"
            className="w-full px-4 py-2 border rounded-lg text-gray-800"
          />
          <input
            type="email"
            placeholder="Email"
            className="w-full px-4 py-2 border rounded-lg text-gray-800"
          />
          <input
            type="password"
            placeholder="Password"
            className="w-full px-4 py-2 border rounded-lg text-gray-800"
          />
          <button onClick={handleSignup} className="w-full bg-cyan-600 text-white py-2 rounded-lg hover:bg-cyan-700">
            Sign Up
          </button>
        </div>
        <p className="text-center mt-4 text-gray-600">
          Already have an account?{" "}
          <button onClick={onSwitchToLogin} className="text-cyan-600 hover:underline">
            Login
          </button>
        </p>
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

  const handleSubmit = async () => {
    if (!link.trim()) {
      alert("Please enter a link");
      return;
    }

    if (triesLeft <= 0) {
      alert("You've used all your free tries! Please login to continue.");
      setIsLoginOpen(true);
      return;
    }

    setIsLoading(true);
    try {
      const response = await fetch("https://api.example.com/summarize", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          url: link,
        }),
      });
      const data = await response.json();
      console.log("Response:", data);
      setTriesLeft(triesLeft - 1);
      alert(`Link submitted successfully! You have ${triesLeft - 1} tries left.`);
      setLink("");
    } catch (error) {
      console.error("Error submitting link:", error);
      alert("Failed to submit link. Please try again.");
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
                  <span><img src={logo} alt="Summtube logo" /></span>
                    </div>
                    <span className="font-bold text-lg whitespace-nowrap">SummTube</span>
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
                    You can try SummTube {triesLeft} more {triesLeft === 1 ? 'time' : 'times'} for free!
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
              {!isSidebarOpen && (
                <button
                  onClick={() => setIsSidebarOpen(true)}
                  className="hover:bg-cyan-600 p-2 rounded transition-colors"
                >
                  <Menu size={24} />
                </button>
              )}
              <h1 className="text-xl md:text-2xl lg:text-3xl font-bold">
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
                  💡 You have <span className="font-bold text-white">{triesLeft} free {triesLeft === 1 ? 'try' : 'tries'}</span> remaining. Login for unlimited access!
                </p>
              </div>
              
              {/* Placeholder for summary results */}
              <div className="space-y-4">
                {/* Add your summary content here */}
              </div>
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
                      if (e.key === 'Enter' && !isLoading) {
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