import { useState, useEffect } from "react";
import { ListCollapse, Send, Menu, Download, Copy, Check, LogOut, User } from "lucide-react";
import api from "../services/api.js"; 

const Dashboard = () => {
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [link, setLink] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [summary, setSummary] = useState(null);
  const [error, setError] = useState(null);
  const [displayedText, setDisplayedText] = useState("");
  const [isGenerating, setIsGenerating] = useState(false);
  const [copied, setCopied] = useState(false);
  const [history, setHistory] = useState([]);
  const [user, setUser] = useState(null);
  const [notification, setNotification] = useState(null);

  // Show notification helper
  const showNotification = (message, type = "success") => {
    setNotification({ message, type });
    setTimeout(() => setNotification(null), 3000);
  };

  // Fetch user profile and history on mount
  useEffect(() => {
    fetchUserProfile();
    fetchHistory();
  }, []);

  // Simulate streaming text effect
  useEffect(() => {
    if (summary?.summary && isGenerating) {
      let index = 0;
      const text = summary.summary;
      const interval = setInterval(() => {
        if (index < text.length) {
          setDisplayedText(text.slice(0, index + 1));
          index++;
        } else {
          setIsGenerating(false);
          clearInterval(interval);
        }
      }, 20);

      return () => clearInterval(interval);
    }
  }, [summary, isGenerating]);

  const fetchUserProfile = async () => {
    try {
      const response = await api.get("/user/profile");
      if (response.data.success) {
        setUser(response.data.user);
      }
    } catch (error) {
      console.error("Error fetching profile:", error);
    }
  };

  const fetchHistory = async () => {
    try {
      const response = await api.get("/summary/history");
      if (response.data.success) {
        setHistory(response.data.summaries || []);
      }
    } catch (error) {
      console.error("Error fetching history:", error);
    }
  };

  const handleSubmit = async () => {
    if (!link.trim()) {
      showNotification("Please enter a YouTube link", "error");
      return;
    }

    setIsLoading(true);
    setError(null);
    setSummary(null);
    setDisplayedText("");
    setIsGenerating(false);

    try {
      const response = await api.post("/summary", {
        video_url: link,
        summary_type: "detailed"
      });

      if (response.data.success) {
        setSummary(response.data);
        setIsGenerating(true);
        setLink("");
        showNotification("Summary generated successfully!");
        fetchHistory();
      } else {
        throw new Error(response.data.error || response.data.message || "Failed to generate summary");
      }
    } catch (error) {
      console.error("Error submitting link:", error);
      const errorMsg = error.response?.data?.error || error.response?.data?.message || error.message || "Failed to generate summary. Please try again.";
      setError(errorMsg);
      showNotification(errorMsg, "error");
    } finally {
      setIsLoading(false);
    }
  };

  const handleCopy = () => {
    navigator.clipboard.writeText(summary?.summary || displayedText);
    setCopied(true);
    showNotification("Copied to clipboard!");
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDownload = () => {
    if (!summary) return;
    const blob = new Blob([summary.summary], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${summary.video_title?.replace(/[^a-z0-9]/gi, '_') || 'summary'}_summary.txt`;
    a.click();
    URL.revokeObjectURL(url);
    showNotification("Download started!");
  };

  const handleLogout = async () => {
    try {
      await api.post("/auth/logout");
      showNotification("Logged out successfully!");
      setTimeout(() => {
        window.location.href = "/";
      }, 1000);
    } catch (error) {
      console.error("Logout error:", error);
      window.location.href = "/";
    }
  };

  const loadHistoryItem = async (summaryId) => {
    try {
      const response = await api.get(`/summary/${summaryId}`);
      if (response.data.success) {
        setSummary(response.data);
        setDisplayedText(response.data.summary);
        setIsGenerating(false);
        setError(null);
      }
    } catch (error) {
      showNotification("Failed to load summary", error);
    }
  };

  const hasStartedGeneration = summary || isLoading;

  return (
    <div className="h-screen w-screen flex bg-[#181818] text-white overflow-hidden">
      {/* Notification Toast */}
      {notification && (
        <div className={`fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg animate-fade-in ${
          notification.type === "error" 
            ? "bg-red-600 text-white" 
            : "bg-green-600 text-white"
        }`}>
          {notification.message}
        </div>
      )}

      {/* LEFT SIDEBAR */}
      <div
        className={`h-full bg-[#202124] flex-shrink-0 transition-all duration-300 ease-in-out ${
          isSidebarOpen ? "w-64" : "w-0 md:w-16"
        } overflow-hidden`}
      >
        <div className={`h-full flex flex-col ${isSidebarOpen ? "p-5" : "p-2 md:p-3"}`}>
          {/* Logo and Toggle */}
          <div className="flex items-center justify-between mb-8 flex-shrink-0">
            {isSidebarOpen ? (
              <>
                <div className="flex items-center gap-2">
                  <div className="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <span className="text-white font-bold text-xl">ST</span>
                  </div>
                  <span className="font-semibold text-lg">SummTube</span>
                </div>
                <button
                  onClick={() => setIsSidebarOpen(false)}
                  className="hover:bg-[#181818] p-1 rounded transition-colors"
                >
                  <ListCollapse size={24} />
                </button>
              </>
            ) : (
              <button
                onClick={() => setIsSidebarOpen(true)}
                className="w-full flex justify-center hover:bg-[#181818] p-2 rounded transition-colors"
              >
                <Menu size={24} />
              </button>
            )}
          </div>

          {/* Sidebar Content */}
          {isSidebarOpen && (
            <div className="flex-1 overflow-y-auto space-y-4">
              {/* User Info */}
              {user && (
                <div className="bg-[#181818] rounded-lg p-4">
                  <div className="flex items-center gap-3 mb-2">
                    <div className="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center">
                      <User size={20} />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-semibold truncate">{user.name}</p>
                      <p className="text-xs text-gray-400 truncate">{user.email}</p>
                    </div>
                  </div>
                </div>
              )}

              {/* History */}
              <div className="bg-[#181818] rounded-lg p-4">
                <h3 className="font-semibold text-lg mb-3">Your History</h3>
                <div className="space-y-2 max-h-64 overflow-y-auto">
                  {history.length > 0 ? (
                    history.slice(0, 10).map((item) => (
                      <div
                        key={item.id}
                        onClick={() => loadHistoryItem(item.id)}
                        className="hover:bg-[#282828] p-2 rounded cursor-pointer transition-colors"
                      >
                        <p className="text-sm truncate">{item.video_title || "Untitled"}</p>
                        <p className="text-xs text-gray-400">
                          {new Date(item.created_at).toLocaleDateString()}
                        </p>
                      </div>
                    ))
                  ) : (
                    <p className="text-sm text-gray-400">No history yet</p>
                  )}
                </div>
              </div>

              {/* Features */}
              <div className="bg-[#181818] rounded-lg p-4">
                <h4 className="font-semibold mb-2 text-sm">Features</h4>
                <ul className="space-y-2 text-xs text-gray-300">
                  <li>✓ Unlimited summaries</li>
                  <li>✓ Save your history</li>
                  <li>✓ Download summaries</li>
                  <li>✓ Priority support</li>
                </ul>
              </div>
            </div>
          )}

          {/* Logout Button */}
          {isSidebarOpen && (
            <div className="mt-4 pt-4 border-t border-gray-700 flex-shrink-0">
              <button
                onClick={handleLogout}
                className="w-full flex items-center gap-2 px-4 py-2 hover:bg-[#181818] rounded transition-colors text-red-400 hover:text-red-300"
              >
                <LogOut size={18} />
                <span>Logout</span>
              </button>
            </div>
          )}
        </div>
      </div>

      {/* MAIN CONTENT */}
      <div className="flex-1 h-full flex flex-col overflow-hidden">
        {/* Top Navigation */}
        <div className="py-3 px-4 md:py-4 md:px-6 lg:px-10 flex items-center justify-between border-b border-gray-700 flex-shrink-0 bg-[#202124]">
          <h1 className="text-xl md:text-2xl lg:text-3xl font-bold">Dashboard</h1>
          <div className="flex items-center gap-3">
            {user && (
              <div className="hidden md:block text-right">
                <p className="text-sm font-medium">{user.name}</p>
                <p className="text-xs text-gray-400">{user.email}</p>
              </div>
            )}
          </div>
        </div>

        {/* Content Area */}
        <div className="flex-1 overflow-y-auto relative">
          {/* Centered Input (when no summary) */}
          {!hasStartedGeneration && (
            <div className="absolute inset-0 flex items-center justify-center p-4">
              <div className="w-full max-w-3xl">
                <div className="text-center mb-8">
                  <h2 className="text-3xl md:text-4xl font-bold mb-4 bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                    Welcome to SummTube
                  </h2>
                  <p className="text-gray-400 text-lg">
                    Paste a YouTube link to get an AI-generated summary
                  </p>
                </div>

                <div className="relative">
                  <div className="flex items-center gap-2 bg-[#202124] rounded-full py-2 px-5 shadow-lg border border-gray-700">
                    <input
                      type="text"
                      value={link}
                      onChange={(e) => setLink(e.target.value)}
                      onKeyPress={(e) => {
                        if (e.key === "Enter" && !isLoading) {
                          handleSubmit();
                        }
                      }}
                      placeholder="Paste YouTube link here..."
                      className="flex-1 px-3 py-3 bg-transparent text-white outline-none text-base placeholder-gray-500"
                      disabled={isLoading}
                    />
                    <button
                      onClick={handleSubmit}
                      disabled={isLoading}
                      className="bg-gradient-to-r from-cyan-500 to-blue-600 text-white p-3 rounded-lg hover:from-cyan-600 hover:to-blue-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {isLoading ? (
                        <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                      ) : (
                        <Send size={20} />
                      )}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Summary Display Area */}
          {hasStartedGeneration && (
            <div className="p-4 md:p-6 lg:p-8 pb-32">
              <div className="max-w-4xl mx-auto">
                {error && (
                  <div className="bg-red-900 bg-opacity-30 border border-red-600 rounded-lg p-4 mb-4">
                    <p className="text-red-200">{error}</p>
                  </div>
                )}

                {isLoading && (
                  <div className="bg-[#202124] rounded-lg p-8 text-center border border-gray-700">
                    <div className="flex flex-col items-center gap-4">
                      <div className="w-12 h-12 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin" />
                      <p className="text-gray-300 text-lg">Generating summary...</p>
                      <p className="text-gray-500 text-sm">This may take a few moments</p>
                    </div>
                  </div>
                )}

                {summary && (
                  <div className="bg-[#202124] rounded-lg p-6 space-y-4 shadow-xl border border-gray-700">
                    {/* Video Info */}
                    <div className="flex items-start gap-4 pb-4 border-b border-gray-700">
                      {summary.thumbnail && (
                        <img
                          src={summary.thumbnail}
                          alt={summary.video_title}
                          className="w-40 h-24 object-cover rounded-lg flex-shrink-0"
                          onError={(e) => {
                            e.target.style.display = "none";
                          }}
                        />
                      )}
                      <div className="flex-1">
                        <h3 className="text-xl font-semibold mb-2">{summary.video_title}</h3>
                        <div className="flex gap-4 text-sm text-gray-400">
                          {summary.duration && (
                            <>
                              <span>Duration: {Math.floor(summary.duration / 60)}:{String(summary.duration % 60).padStart(2, '0')}</span>
                              <span>•</span>
                            </>
                          )}
                          {summary.processing_time && (
                            <span>Processed in {summary.processing_time}s</span>
                          )}
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <button
                          onClick={handleCopy}
                          className="p-2 hover:bg-[#181818] rounded-lg transition-colors border border-gray-700"
                          title="Copy summary"
                        >
                          {copied ? <Check size={20} className="text-green-500" /> : <Copy size={20} />}
                        </button>
                        <button
                          onClick={handleDownload}
                          className="p-2 hover:bg-[#181818] rounded-lg transition-colors border border-gray-700"
                          title="Download summary"
                        >
                          <Download size={20} />
                        </button>
                      </div>
                    </div>

                    {/* Summary Content */}
                    <div>
                      <h4 className="text-lg font-semibold mb-3 text-cyan-400">Summary:</h4>
                      <p className="text-gray-300 leading-relaxed whitespace-pre-wrap text-base">
                        {displayedText}
                        {isGenerating && (
                          <span className="inline-block w-2 h-5 bg-cyan-400 ml-1 animate-pulse" />
                        )}
                      </p>
                    </div>

                    {/* Footer Info */}
                    {!isGenerating && summary.transcript_length && (
                      <div className="flex items-center justify-between text-sm text-gray-400 border-t border-gray-700 pt-4">
                        <span>Transcript length: {summary.transcript_length.toLocaleString()} characters</span>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          )}
        </div>

        {/* Bottom Input (when summary exists) */}
        {hasStartedGeneration && (
          <div className="p-4 md:p-6 border-t border-gray-700 bg-[#202124] flex-shrink-0">
            <div className="max-w-4xl mx-auto">
              <div className="relative">
                <div className="flex items-center gap-2 bg-[#181818] rounded-full py-2 px-5 border border-gray-700">
                  <input
                    type="text"
                    value={link}
                    onChange={(e) => setLink(e.target.value)}
                    onKeyPress={(e) => {
                      if (e.key === "Enter" && !isLoading) {
                        handleSubmit();
                      }
                    }}
                    placeholder="Paste another YouTube link..."
                    className="flex-1 px-3 py-2 md:py-3 bg-transparent text-white outline-none text-sm md:text-base placeholder-gray-500"
                    disabled={isLoading}
                  />
                  <button
                    onClick={handleSubmit}
                    disabled={isLoading}
                    className="bg-gradient-to-r from-cyan-500 to-blue-600 text-white p-2 md:p-3 rounded-lg hover:from-cyan-600 hover:to-blue-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {isLoading ? (
                      <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    ) : (
                      <Send size={20} />
                    )}
                  </button>
                </div>
              </div>
              <p className="text-xs md:text-sm text-gray-500 mt-2 text-center">
                Supported: YouTube video links
              </p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default Dashboard;